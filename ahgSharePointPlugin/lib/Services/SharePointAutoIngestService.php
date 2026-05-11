<?php

namespace AtomExtensions\SharePoint\Services;

use AtomExtensions\SharePoint\Repositories\SharePointDriveRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointAutoIngestService — cron-driven SP→AtoM ingest.
 *
 * Per run:
 *   1. Pick enabled rules whose schedule_cron is due (or all enabled when --force).
 *   2. For each rule:
 *      - lockfile per rule (skip if previous run still alive)
 *      - walk the drive subtree honoring folder_path + file_pattern
 *      - dedupe by (sp_item_id, sp_etag) against ingest_file.sidecar_json
 *      - create one ingest_session (source='sharepoint_auto', source_id=rule.id)
 *      - copy sharepoint_mapping for the drive into ingest_mapping
 *        (fallback to safe defaults when no template exists)
 *      - download new items to uploads/ingest/{session_id}/
 *      - insert one ingest_file per item (file_type='sharepoint', sidecar_json=SP meta)
 *      - hand off to IngestService::parseRows() + IngestCommitService
 *
 * @phase 2 (v2 ingest plan, steps 3 + 4 + C.5)
 */
class SharePointAutoIngestService
{
    private const LOCK_DIR = '/tmp';
    private const MAX_ITEMS_PER_RUN = 500;
    private const DEFAULT_MAPPINGS = [
        ['source' => 'Title', 'target' => 'title'],
        ['source' => 'Name', 'target' => 'title'],
        ['source' => 'Modified', 'target' => 'dates', 'transform' => 'date_iso'],
        ['source' => 'Created', 'target' => 'dates', 'transform' => 'date_iso'],
        ['source' => 'Author', 'target' => 'creator'],
        ['source' => 'CreatedBy', 'target' => 'creator'],
        ['source' => 'Description', 'target' => 'scopeAndContent'],
    ];

    private SharePointBrowserService $browser;
    private SharePointDriveRepository $drives;

    public function __construct(
        ?SharePointBrowserService $browser = null,
        ?SharePointDriveRepository $drives = null,
    ) {
        $this->browser = $browser ?? new SharePointBrowserService();
        $this->drives = $drives ?? new SharePointDriveRepository();
    }

    /**
     * Run all enabled rules whose schedule is due.
     *
     * @return array<int, array{rule_id:int, status:string, items_new:int, items_skipped:int, error?:string, session_id?:int, job_id?:int}>
     */
    public function runDueRules(bool $force = false, bool $dryRun = false): array
    {
        $rules = $this->loadDueRules($force);
        $results = [];
        foreach ($rules as $rule) {
            $results[] = $this->runRule((int) $rule->id, $dryRun);
        }
        return $results;
    }

    /**
     * Run a single rule by id.
     *
     * @return array{rule_id:int, status:string, items_new:int, items_skipped:int, error?:string, session_id?:int, job_id?:int}
     */
    public function runRule(int $ruleId, bool $dryRun = false): array
    {
        $result = [
            'rule_id' => $ruleId,
            'status' => 'pending',
            'items_new' => 0,
            'items_skipped' => 0,
        ];

        $rule = DB::table('sharepoint_ingest_rule')->where('id', $ruleId)->first();
        if (!$rule) {
            return $result + ['status' => 'error', 'error' => "Rule {$ruleId} not found"];
        }

        $drive = $this->drives->find((int) $rule->drive_id);
        if (!$drive) {
            $this->updateRuleStatus($ruleId, 'error');
            return $result + ['status' => 'error', 'error' => "Drive {$rule->drive_id} not found"];
        }

        $lock = $this->acquireLock($ruleId);
        if (!$lock) {
            return $result + ['status' => 'skipped', 'error' => 'Previous run still active'];
        }

        try {
            $items = $this->walkDrive((int) $drive->tenant_id, $drive->drive_id, $rule);
            $newItems = [];
            foreach ($items as $item) {
                if ($this->isAlreadyIngested((int) $rule->drive_id, $item['id'], (string) ($item['etag'] ?? ''))) {
                    ++$result['items_skipped'];
                    continue;
                }
                $newItems[] = $item;
            }
            $result['items_new'] = count($newItems);

            if ($dryRun) {
                $this->updateRuleStatus($ruleId, 'dry_run');
                return $result + ['status' => 'dry_run'];
            }

            if (empty($newItems)) {
                $this->updateRuleStatus($ruleId, 'ok');
                return $result + ['status' => 'no_new_items'];
            }

            // Cap per-run batch to keep memory + commit job bounded
            if (count($newItems) > self::MAX_ITEMS_PER_RUN) {
                $newItems = array_slice($newItems, 0, self::MAX_ITEMS_PER_RUN);
                $result['items_new'] = count($newItems);
            }

            $sessionId = $this->createSession((int) $rule->id, $rule, $drive);
            $this->materializeMappings($sessionId, (int) $drive->id, $rule->template_id ? (int) $rule->template_id : null);
            $this->downloadAndRegister($sessionId, (int) $drive->tenant_id, $drive->drive_id, $newItems);

            // Hand off to ingest pipeline (parse + enrich + validate + commit)
            $jobId = $this->dispatchCommit($sessionId);

            // Tally drive's running counter
            DB::table('sharepoint_ingest_rule')->where('id', $ruleId)->update([
                'items_ingested' => DB::raw('items_ingested + ' . count($newItems)),
                'last_run_at' => date('Y-m-d H:i:s'),
                'last_run_status' => 'ok',
            ]);

            return $result + ['status' => 'ok', 'session_id' => $sessionId, 'job_id' => $jobId];
        } catch (\Throwable $e) {
            $this->updateRuleStatus($ruleId, 'error');
            $this->logError($ruleId, $e);
            return $result + ['status' => 'error', 'error' => $e->getMessage()];
        } finally {
            $this->releaseLock($lock);
        }
    }

    // ---- internals ----

    /**
     * @return array<int, object>
     */
    private function loadDueRules(bool $force): array
    {
        $q = DB::table('sharepoint_ingest_rule')->where('is_enabled', 1);
        $rules = $q->get()->all();
        if ($force) {
            return $rules;
        }
        return array_values(array_filter($rules, fn ($r) => $this->isCronDue($r->schedule_cron ?? '*/15 * * * *', $r->last_run_at)));
    }

    /**
     * Minimal cron-due check. We don't need full cron semantics; the cron
     * daemon fires the task itself — this is a "has enough time elapsed since
     * last_run_at" check to throttle rules whose cron is broader than the
     * driver's fire frequency.
     */
    private function isCronDue(string $cronExpression, ?string $lastRunAt): bool
    {
        if (!$lastRunAt) {
            return true;
        }
        $intervalMinutes = $this->inferIntervalMinutes($cronExpression);
        $lastTs = strtotime($lastRunAt);
        return $lastTs === false || (time() - $lastTs) >= ($intervalMinutes * 60);
    }

    private function inferIntervalMinutes(string $cron): int
    {
        // Match the common `*/N * * * *` pattern; otherwise default to 15 min.
        if (preg_match('#^\*/(\d+)\s+\*\s+\*\s+\*\s+\*$#', trim($cron), $m)) {
            return max(1, (int) $m[1]);
        }
        if (preg_match('#^0\s+\*/(\d+)\s+\*\s+\*\s+\*$#', trim($cron), $m)) {
            return max(60, (int) $m[1] * 60);
        }
        return 15;
    }

    /**
     * Recursively walk a drive subtree.
     *
     * @return array<int, array>
     */
    private function walkDrive(int $tenantId, string $driveId, object $rule): array
    {
        $startItemId = $this->resolveStartItemId($tenantId, $driveId, $rule->folder_path ?? null);
        $patterns = $this->parsePatterns($rule->file_pattern ?? null);
        $requiredLabels = $this->parseLabels($rule->retention_label ?? null);
        $collected = [];
        $stack = [$startItemId];
        $visited = 0;
        $maxNodes = 5000;

        while (!empty($stack) && $visited < $maxNodes) {
            $itemId = array_pop($stack);
            ++$visited;
            $children = $this->browser->listChildren($tenantId, $driveId, $itemId);
            foreach ($children as $child) {
                if ($child['isFolder']) {
                    $stack[] = $child['id'];
                    continue;
                }
                if (!$this->matchesPattern($child['name'], $patterns)) {
                    continue;
                }
                if (!$this->matchesRetentionLabel($child['retentionLabel'] ?? null, $requiredLabels)) {
                    continue;
                }
                $collected[] = $child;
                if (count($collected) >= 10000) {
                    return $collected;
                }
            }
        }
        return $collected;
    }

    /**
     * @return array<int, string> lowercase label names; empty array means "no filter"
     */
    private function parseLabels(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return [];
        }
        $parts = array_map(
            fn ($s) => strtolower(trim((string) $s)),
            explode(',', $csv),
        );
        return array_values(array_filter($parts, fn ($s) => $s !== ''));
    }

    /**
     * @param array<int, string> $requiredLabels
     */
    private function matchesRetentionLabel(?string $itemLabel, array $requiredLabels): bool
    {
        if (empty($requiredLabels)) {
            return true; // no filter configured
        }
        if ($itemLabel === null || $itemLabel === '') {
            return false; // filter set, item has no label
        }
        return in_array(strtolower($itemLabel), $requiredLabels, true);
    }

    private function resolveStartItemId(int $tenantId, string $driveId, ?string $folderPath): string
    {
        if ($folderPath === null || $folderPath === '' || $folderPath === '/') {
            return 'root';
        }
        // Graph supports path-addressing: /drives/{drive}/root:/Folder/Sub:
        // Use it via getMetadata against a synthetic "root:/path:" itemId.
        $clean = trim($folderPath, '/');
        $itemId = 'root:/' . $clean . ':';
        return $itemId;
    }

    /**
     * Parse a CSV-of-globs ("*.pdf,*.tif") into an array of normalized patterns.
     *
     * @return array<int, string>
     */
    private function parsePatterns(?string $patternCsv): array
    {
        if ($patternCsv === null || trim($patternCsv) === '') {
            return ['*'];
        }
        return array_values(array_filter(array_map('trim', explode(',', $patternCsv))));
    }

    /**
     * @param array<int, string> $patterns
     */
    private function matchesPattern(string $name, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if (fnmatch($p, $name, FNM_CASEFOLD)) {
                return true;
            }
        }
        return false;
    }

    private function isAlreadyIngested(int $driveId, string $itemId, string $etag): bool
    {
        $q = DB::table('ingest_file')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sidecar_json, '$.sp_drive_id')) = ?", [$driveId])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sidecar_json, '$.sp_item_id')) = ?", [$itemId])
            ->whereIn('status', ['completed', 'imported', 'pending']);
        if ($etag !== '') {
            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sidecar_json, '$.sp_etag')) = ?", [$etag]);
        }
        return (bool) $q->exists();
    }

    private function createSession(int $ruleId, object $rule, object $drive): int
    {
        $title = sprintf(
            'SharePoint auto-ingest: %s [%s]',
            $rule->name,
            date('Y-m-d H:i'),
        );
        return (int) DB::table('ingest_session')->insertGetId([
            'user_id' => $this->systemUserId(),
            'title' => $title,
            'entity_type' => 'description',
            'sector' => $rule->sector ?? 'archive',
            'standard' => $rule->standard ?? 'isadg',
            'repository_id' => $rule->repository_id,
            'parent_id' => $rule->parent_id,
            'parent_placement' => $rule->parent_placement ?? 'top_level',
            'output_create_records' => 1,
            'output_generate_sip' => 0,
            'output_generate_aip' => 0,
            'output_generate_dip' => 0,
            'derivative_thumbnails' => 1,
            'derivative_reference' => 1,
            'process_ner' => $this->flag($rule->process_flags, 'ner'),
            'process_ocr' => $this->flag($rule->process_flags, 'ocr'),
            'process_virus_scan' => $this->flag($rule->process_flags, 'virus_scan', 1),
            'process_summarize' => $this->flag($rule->process_flags, 'summarize'),
            'process_spellcheck' => $this->flag($rule->process_flags, 'spellcheck'),
            'process_translate' => $this->flag($rule->process_flags, 'translate'),
            'process_format_id' => $this->flag($rule->process_flags, 'format_id'),
            'process_face_detect' => $this->flag($rule->process_flags, 'face_detect'),
            'status' => 'configure',
            'session_kind' => 'auto',
            'auto_commit' => 1,
            'source' => 'sharepoint_auto',
            'source_id' => $ruleId,
            'source_metadata' => json_encode([
                'sp_drive_id' => $drive->drive_id,
                'sp_drive_pk' => (int) $drive->id,
                'sp_drive_name' => $drive->drive_name,
                'sp_site_id' => $drive->site_id,
                'sp_site_title' => $drive->site_title,
                'sp_folder_path' => $rule->folder_path,
                'sp_file_pattern' => $rule->file_pattern,
                'rule_id' => $ruleId,
                'rule_name' => $rule->name,
                'run_started_at' => date('c'),
            ]),
            'config' => json_encode([
                'source' => 'sharepoint_auto',
                'rule_id' => $ruleId,
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Materialize sharepoint_mapping for the drive into ingest_mapping for the
     * new session. Resolution order:
     *   1) explicit $templateId (from rule.template_id)
     *   2) drive's is_default=1 template
     *   3) any single template on the drive
     *   4) safe defaults
     */
    public function materializeMappings(int $sessionId, int $drivePk, ?int $templateId = null): int
    {
        $chosenTemplate = null;
        if ($templateId !== null && $templateId > 0) {
            $chosenTemplate = DB::table('sharepoint_mapping_template')
                ->where('id', $templateId)
                ->where('drive_id', $drivePk)
                ->first();
        }
        if (!$chosenTemplate) {
            $chosenTemplate = DB::table('sharepoint_mapping_template')
                ->where('drive_id', $drivePk)
                ->where('is_default', 1)
                ->first();
        }
        if (!$chosenTemplate) {
            $chosenTemplate = DB::table('sharepoint_mapping_template')
                ->where('drive_id', $drivePk)
                ->orderBy('id')
                ->first();
        }

        if ($chosenTemplate) {
            $templates = DB::table('sharepoint_mapping')
                ->where('template_id', $chosenTemplate->id)
                ->orderBy('sort_order')
                ->get()
                ->all();
        } else {
            // Back-compat: pre-template rows (template_id IS NULL)
            $templates = DB::table('sharepoint_mapping')
                ->where('drive_id', $drivePk)
                ->whereNull('template_id')
                ->orderBy('sort_order')
                ->get()
                ->all();
        }

        if (empty($templates)) {
            error_log("SharePointAutoIngestService: no mapping template for drive {$drivePk}, falling back to defaults");
            foreach (self::DEFAULT_MAPPINGS as $i => $m) {
                DB::table('ingest_mapping')->insert([
                    'session_id' => $sessionId,
                    'source_column' => $m['source'],
                    'target_field' => $m['target'],
                    'transform' => $m['transform'] ?? null,
                    'is_ignored' => 0,
                    'sort_order' => $i,
                ]);
            }
            return count(self::DEFAULT_MAPPINGS);
        }

        foreach ($templates as $i => $t) {
            DB::table('ingest_mapping')->insert([
                'session_id' => $sessionId,
                'source_column' => $t->source_field,
                'target_field' => $t->target_field,
                'default_value' => $t->default_value,
                'transform' => $t->transform,
                'is_ignored' => 0,
                'sort_order' => (int) ($t->sort_order ?? $i),
            ]);
        }
        return count($templates);
    }

    /**
     * @param array<int, array> $items
     */
    private function downloadAndRegister(int $sessionId, int $tenantId, string $driveId, array $items): void
    {
        $baseDir = $this->sessionDownloadDir($sessionId);
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $rowNum = 0;
        foreach ($items as $item) {
            ++$rowNum;
            $safeName = $this->sanitizeFilename($item['name'] ?: $item['id']);
            $itemDir = $baseDir . '/' . $item['id'];
            if (!is_dir($itemDir)) {
                mkdir($itemDir, 0775, true);
            }
            $destPath = $itemDir . '/' . $safeName;
            $this->browser->downloadItem($tenantId, $driveId, $item['id'], $destPath);

            $listFields = [];
            try {
                $listFields = $this->browser->getMetadata($tenantId, $driveId, $item['id'], true);
                $listFields = $listFields['_raw']['listItem']['fields'] ?? [];
            } catch (\Throwable $e) {
                // Some drives (e.g. /personal/) deny listItem expansion; not fatal.
                $listFields = [];
            }

            $checksum = is_file($destPath) ? hash_file('sha256', $destPath) : null;

            DB::table('ingest_file')->insert([
                'session_id' => $sessionId,
                'file_type' => 'sharepoint',
                'original_name' => $item['name'],
                'stored_path' => $destPath,
                'file_size' => $item['size'] ?? filesize($destPath) ?: 0,
                'mime_type' => $item['mimeType'] ?? null,
                'status' => 'pending',
                'source_hash' => $checksum,
                'sidecar_json' => json_encode([
                    'sp_drive_id' => DB::table('sharepoint_drive')->where('drive_id', $driveId)->value('id'),
                    'sp_drive_graph_id' => $driveId,
                    'sp_item_id' => $item['id'],
                    'sp_etag' => $item['etag'] ?? null,
                    'sp_web_url' => $item['webUrl'] ?? null,
                    'sp_list_item_fields' => $listFields,
                    'sp_last_modified' => $item['lastModifiedDateTime'] ?? null,
                    'sp_created' => $item['createdDateTime'] ?? null,
                    'sp_retention_label' => $item['retentionLabel'] ?? null,
                    'sp_retention_label_applied_at' => $item['retentionLabelAppliedAt'] ?? null,
                ]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        }
    }

    /**
     * Run enrich + validate then hand off to IngestCommitService. Uses
     * QueueService when available, falls back to direct synchronous commit.
     */
    private function dispatchCommit(int $sessionId): int
    {
        $this->loadIngestServices();
        $ingest = new \AhgIngestPlugin\Services\IngestService();
        $commit = new \AhgIngestPlugin\Services\IngestCommitService();

        $ingest->parseRows($sessionId);
        $ingest->enrichRows($sessionId);
        $ingest->validateSession($sessionId);
        $ingest->updateSessionStatus($sessionId, 'commit');

        $jobId = $commit->startJob($sessionId);

        // Queue when available; otherwise run inline so cron can be the executor.
        $queued = $this->tryQueueCommit($jobId);
        if (!$queued) {
            try {
                $commit->executeJob($jobId);
            } catch (\Throwable $e) {
                error_log('SharePointAutoIngestService inline commit failed: ' . $e->getMessage());
                throw $e;
            }
        }
        return $jobId;
    }

    private function tryQueueCommit(int $jobId): bool
    {
        if (!class_exists('\AtomFramework\Services\QueueService')) {
            return false;
        }
        try {
            $q = new \AtomFramework\Services\QueueService();
            // Standard ahgIngestPlugin handler name (matches QueueJobRegistry)
            $q->dispatch('ingest.commit', ['job_id' => $jobId], 'integrations');
            return true;
        } catch (\Throwable $e) {
            error_log('QueueService dispatch failed, falling back to inline: ' . $e->getMessage());
            return false;
        }
    }

    private function loadIngestServices(): void
    {
        $base = \sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin/lib/Services';
        if (!class_exists('\AhgIngestPlugin\Services\IngestService')) {
            require_once $base . '/IngestService.php';
        }
        if (!class_exists('\AhgIngestPlugin\Services\IngestCommitService')) {
            require_once $base . '/IngestCommitService.php';
        }
    }

    private function flag(?string $jsonFlags, string $key, int $default = 0): int
    {
        if ($jsonFlags === null) {
            return $default;
        }
        $decoded = json_decode($jsonFlags, true);
        if (!is_array($decoded)) {
            return $default;
        }
        return !empty($decoded[$key]) ? 1 : $default;
    }

    private function sessionDownloadDir(int $sessionId): string
    {
        return rtrim(\sfConfig::get('sf_upload_dir'), '/') . '/ingest/' . $sessionId;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('#[\\\\/]+#', '_', $name);
        $name = preg_replace('#[^A-Za-z0-9._ \-]#', '_', $name);
        return substr($name, 0, 200);
    }

    private function systemUserId(): int
    {
        // First superuser; AtoM seed always provisions id=1 / id=100 admin.
        $u = DB::table('user')->orderBy('id')->first();
        return (int) ($u->id ?? 1);
    }

    private function updateRuleStatus(int $ruleId, string $status): void
    {
        DB::table('sharepoint_ingest_rule')->where('id', $ruleId)->update([
            'last_run_at' => date('Y-m-d H:i:s'),
            'last_run_status' => $status,
        ]);
    }

    private function logError(int $ruleId, \Throwable $e): void
    {
        if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('ahg_error_log')) {
            DB::table('ahg_error_log')->insert([
                'level' => 'error',
                'message' => substr('sharepoint_auto_ingest rule=' . $ruleId . ': ' . $e->getMessage(), 0, 65000),
                'file' => substr($e->getFile(), 0, 500),
                'line' => $e->getLine(),
                'exception_class' => get_class($e),
                'trace' => substr($e->getTraceAsString(), 0, 8000),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @return resource|false File handle held for the run; pass to releaseLock.
     */
    private function acquireLock(int $ruleId)
    {
        $path = self::LOCK_DIR . "/sp-rule-{$ruleId}.lock";
        $fh = fopen($path, 'c');
        if (!$fh) {
            return false;
        }
        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);
            return false;
        }
        return $fh;
    }

    /**
     * @param resource|false $handle
     */
    private function releaseLock($handle): void
    {
        if ($handle === false) {
            return;
        }
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
