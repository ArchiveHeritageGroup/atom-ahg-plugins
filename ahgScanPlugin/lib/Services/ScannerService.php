<?php

namespace AhgScanPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ScannerService — ahgScanPlugin.
 *
 * Performs one scan pass over a watched folder:
 *   1. Walk the folder (recursive), skipping dotfiles, partial/lock/tmp files,
 *      and the folder's own .processed/.failed disposition subdirs.
 *   2. Enforce the quiet-period (skip files still being written).
 *   3. Dedupe by SHA-256 against ingest_row.checksum_sha256 for the session.
 *   4. Stage each new file as an ingest_row (digital_object_path + checksum).
 *   5. When auto_commit is on, launch `php symfony ingest:commit --session-id`
 *      to drive the file through ahgIngestPlugin's commit pipeline.
 *   6. Dispose source files to the processed (success) directory once the
 *      file has been ingested (its master copied into uploads), or to the
 *      failed (quarantine) directory when the commit reports an error.
 *
 * Mirrors Heratio ahg-scan's ScanWatchCommand + ProcessScanFile disposition,
 * but feeds AtoM's row-based ingest pipeline rather than Heratio's per-file
 * job queue. All file work uses native PHP; the commit hand-off uses the
 * existing ingest:commit symfony task (launched via nohup, like the web UI).
 */
class ScannerService
{
    private WatchedFolderService $folders;

    public function __construct(?WatchedFolderService $folders = null)
    {
        $this->folders = $folders ?? new WatchedFolderService();
    }

    /**
     * Run a single scan pass for one folder. Returns a counts summary and
     * writes a scan_event audit row.
     *
     * @return array{detected:int,enqueued:int,skipped_duplicate:int,skipped_quiet:int,failed:int,job_id:?int,status:string,message:?string}
     */
    public function scanFolder(object $folder, ?callable $log = null): array
    {
        $log = $log ?? function (string $level, string $msg): void {};

        $counts = [
            'detected' => 0,
            'enqueued' => 0,
            'skipped_duplicate' => 0,
            'skipped_quiet' => 0,
            'failed' => 0,
            'job_id' => null,
            'status' => 'completed',
            'message' => null,
        ];

        if (!is_dir($folder->path)) {
            $counts['status'] = 'failed';
            $counts['message'] = "Watched path is not a directory: {$folder->path}";
            $log('error', $counts['message']);
            $this->recordEvent($folder->id, $counts);

            return $counts;
        }

        $session = DB::table('ingest_session')->where('id', $folder->ingest_session_id)->first();
        if (!$session) {
            $counts['status'] = 'failed';
            $counts['message'] = "Backing ingest_session {$folder->ingest_session_id} not found";
            $log('error', $counts['message']);
            $this->recordEvent($folder->id, $counts);

            return $counts;
        }

        $processedDir = $this->folders->processedDir($folder);
        $failedDir = $this->folders->failedDir($folder);
        $minQuiet = max(1, (int) $folder->min_quiet_seconds);
        $now = time();
        $stagedRowNumbers = [];

        try {
            $files = $this->walk($folder->path, $processedDir, $failedDir);

            foreach ($files as $full) {
                $counts['detected']++;
                $info = new \SplFileInfo($full);

                // Quiet-period guard — skip files still being written.
                if (($now - $info->getMTime()) < $minQuiet) {
                    $counts['skipped_quiet']++;
                    continue;
                }

                $hash = @hash_file('sha256', $full);
                if (!$hash) {
                    $counts['skipped_quiet']++;
                    continue;
                }

                // Dedupe within this session.
                $dup = DB::table('ingest_row')
                    ->where('session_id', $folder->ingest_session_id)
                    ->where('checksum_sha256', $hash)
                    ->exists();
                if ($dup) {
                    $counts['skipped_duplicate']++;
                    continue;
                }

                $rowNumber = $this->nextRowNumber($folder->ingest_session_id);
                $this->stageRow($folder, $session, $full, $hash, $rowNumber);
                $stagedRowNumbers[] = $rowNumber;
                $counts['enqueued']++;
                $log('info', "[{$folder->code}] staged {$info->getFilename()} (row {$rowNumber})");
            }

            if ($counts['enqueued'] > 0 && (int) $folder->auto_commit === 1) {
                $jobId = $this->launchCommit($folder->ingest_session_id, $log);
                $counts['job_id'] = $jobId;

                // Best-effort disposition: move staged source files whose row
                // now has a created_atom_id (ingested) to processed; rows that
                // ended without a created record to failed/quarantine.
                $this->disposeStaged($folder, $stagedRowNumbers, $processedDir, $failedDir, $counts, $log);
            }
        } catch (\Throwable $e) {
            $counts['status'] = 'failed';
            $counts['message'] = $e->getMessage();
            $counts['failed']++;
            $log('error', "[{$folder->code}] " . $e->getMessage());
        }

        if ($counts['status'] === 'completed' && $counts['enqueued'] === 0) {
            $counts['status'] = 'idle';
        }

        $this->folders->touchScanned($folder->id);
        $this->recordEvent($folder->id, $counts);

        return $counts;
    }

    /**
     * Walk a folder recursively, returning eligible primary file paths.
     *
     * @return array<int, string>
     */
    private function walk(string $root, string $processedDir, string $failedDir): array
    {
        $out = [];
        $rootReal = rtrim($root, '/');
        $skipPrefixes = [rtrim($processedDir, '/'), rtrim($failedDir, '/')];

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootReal, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($rii as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if (!$fileInfo->isFile()) {
                continue;
            }
            $full = $fileInfo->getPathname();

            // Skip disposition subdirs (in case they live under the watched root).
            foreach ($skipPrefixes as $prefix) {
                if ($prefix !== '' && str_starts_with($full, $prefix . '/')) {
                    continue 2;
                }
            }

            // Skip dotfiles / dot-directories anywhere in the relative path.
            $relative = substr($full, strlen($rootReal) + 1);
            if (preg_match('#(^|/)\.#', $relative)) {
                continue;
            }

            // Skip in-flight markers.
            if (
                str_ends_with($full, '.lock')
                || str_ends_with($full, '.part')
                || str_ends_with($full, '.tmp')
                || str_ends_with($full, '.filepart')
            ) {
                continue;
            }

            $out[] = $full;
        }

        sort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    private function nextRowNumber(int $sessionId): int
    {
        $max = (int) DB::table('ingest_row')->where('session_id', $sessionId)->max('row_number');

        return $max + 1;
    }

    /**
     * Stage one detected file as an ingest_row on the folder's session.
     * digital_object_matched=1 tells the commit pipeline the file path is
     * the digital object to attach.
     */
    private function stageRow(object $folder, object $session, string $full, string $hash, int $rowNumber): void
    {
        $base = pathinfo($full, PATHINFO_FILENAME);
        $title = ucfirst(str_replace(['_', '-'], ' ', $base));

        $data = [
            'title' => $title,
            'digitalObjectPath' => $full,
            'levelOfDescription' => 'Item',
        ];

        // For path-layout folders, derive a parent identifier hint from the
        // first sub-directory under the watched root (e.g. <root>/COLL-1/file).
        if (($folder->layout ?? 'flat') === 'path') {
            $rel = ltrim(substr($full, strlen(rtrim($folder->path, '/'))), '/');
            $parts = explode('/', $rel);
            if (count($parts) > 1) {
                $data['parentIdentifier'] = $parts[0];
            }
        }

        DB::table('ingest_row')->insert([
            'session_id' => $folder->ingest_session_id,
            'row_number' => $rowNumber,
            'level_of_description' => 'Item',
            'title' => $title,
            'data' => json_encode($data),
            'enriched_data' => json_encode($data),
            'digital_object_path' => $full,
            'digital_object_matched' => 1,
            'checksum_sha256' => $hash,
            'is_valid' => 1,
            'is_excluded' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Launch the ingest commit pipeline for the session as a background task,
     * exactly as the ingest wizard's web UI does. Returns the created job id
     * when the IngestCommitService is loadable, else null (the nohup task
     * will create its own job).
     */
    private function launchCommit(int $sessionId, callable $log): ?int
    {
        $jobId = null;

        // Move the session into a committable state and try to pre-create the
        // job row so callers/audit can track it immediately.
        $pluginDir = $this->ingestPluginDir();
        if ($pluginDir) {
            $commitSvc = $pluginDir . '/lib/Services/IngestCommitService.php';
            $ingestSvc = $pluginDir . '/lib/Services/IngestService.php';
            if (is_file($commitSvc) && is_file($ingestSvc)) {
                require_once $ingestSvc;
                require_once $commitSvc;
                if (class_exists('\\AhgIngestPlugin\\Services\\IngestCommitService')) {
                    try {
                        $svc = new \AhgIngestPlugin\Services\IngestCommitService();
                        $jobId = $svc->startJob($sessionId);
                    } catch (\Throwable $e) {
                        $log('warning', 'Could not pre-create commit job: ' . $e->getMessage());
                    }
                }
            }
        }

        $symfony = $this->atomRoot() . '/symfony';
        if (!is_file($symfony)) {
            $log('warning', 'symfony CLI not found; commit not launched. Run: php symfony ingest:commit --session-id=' . $sessionId);

            return $jobId;
        }

        $php = PHP_BINARY ?: 'php';
        if ($jobId) {
            $cmd = escapeshellarg($php) . ' ' . escapeshellarg($symfony)
                . ' ingest:commit --job-id=' . (int) $jobId;
        } else {
            $cmd = escapeshellarg($php) . ' ' . escapeshellarg($symfony)
                . ' ingest:commit --session-id=' . (int) $sessionId;
        }

        $logFile = $this->atomRoot() . '/log/ahg-scan-commit.log';
        $full = 'cd ' . escapeshellarg($this->atomRoot()) . ' && nohup ' . $cmd
            . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';

        @exec($full);
        $log('info', 'Launched ingest:commit for session ' . $sessionId . ($jobId ? " (job {$jobId})" : ''));

        return $jobId;
    }

    /**
     * Wait briefly for the launched commit to resolve created records, then
     * move each staged source file to processed (success) or failed
     * (quarantine). Disposition is best-effort and never fatal.
     */
    private function disposeStaged(
        object $folder,
        array $rowNumbers,
        string $processedDir,
        string $failedDir,
        array &$counts,
        callable $log
    ): void {
        if (empty($rowNumbers) || ($folder->disposition_success ?? 'move') === 'leave') {
            // For 'leave', dedupe-on-hash prevents re-ingest; nothing to move.
            if (($folder->disposition_success ?? 'move') === 'leave') {
                return;
            }
        }

        // Give the background commit a short window to create records.
        // The watcher loop's interval handles longer-running commits on the
        // next pass (rows already committed are then disposed).
        $deadline = time() + 20;
        $pending = $rowNumbers;

        while (!empty($pending) && time() < $deadline) {
            $rows = DB::table('ingest_row')
                ->where('session_id', $folder->ingest_session_id)
                ->whereIn('row_number', $pending)
                ->get(['row_number', 'digital_object_path', 'created_atom_id', 'created_do_id']);

            $stillPending = [];
            foreach ($rows as $row) {
                $done = !empty($row->created_atom_id) || !empty($row->created_do_id);
                if (!$done) {
                    $stillPending[] = $row->row_number;
                    continue;
                }
                $this->disposeSuccess($folder, $row->digital_object_path, $processedDir, $log);
            }
            $pending = $stillPending;
            if (!empty($pending)) {
                usleep(750000);
            }
        }

        // Anything that never produced a record and whose source still exists
        // is quarantined when the folder is configured for it.
        if (!empty($pending) && ($folder->disposition_failure ?? 'quarantine') === 'quarantine') {
            $rows = DB::table('ingest_row')
                ->where('session_id', $folder->ingest_session_id)
                ->whereIn('row_number', $pending)
                ->get(['digital_object_path', 'is_valid']);
            foreach ($rows as $row) {
                // Only quarantine rows the validator rejected; rows merely
                // still-in-progress are left for the next pass.
                if ((int) $row->is_valid === 0) {
                    $this->disposeFailure($folder, $row->digital_object_path, $failedDir, $log);
                    $counts['failed']++;
                }
            }
        }
    }

    private function disposeSuccess(object $folder, ?string $src, string $processedDir, callable $log): void
    {
        if (!$src || !is_file($src)) {
            return; // already moved by the ingest pipeline into uploads
        }
        switch ($folder->disposition_success ?? 'move') {
            case 'delete':
                @unlink($src);
                break;
            case 'leave':
                break;
            case 'move':
            default:
                $dest = rtrim($processedDir, '/') . '/' . date('Y/m');
                if (!is_dir($dest)) {
                    @mkdir($dest, 0775, true);
                }
                @rename($src, $dest . '/' . basename($src));
                $log('info', "[{$folder->code}] archived " . basename($src));
        }
    }

    private function disposeFailure(object $folder, ?string $src, string $failedDir, callable $log): void
    {
        if (!$src || !is_file($src)) {
            return;
        }
        $dest = rtrim($failedDir, '/') . '/' . date('Y/m');
        if (!is_dir($dest)) {
            @mkdir($dest, 0775, true);
        }
        @rename($src, $dest . '/' . basename($src));
        $log('warning', "[{$folder->code}] quarantined " . basename($src));
    }

    private function recordEvent(int $folderId, array $counts): void
    {
        try {
            DB::table('scan_event')->insert([
                'folder_id' => $folderId,
                'detected' => $counts['detected'],
                'enqueued' => $counts['enqueued'],
                'skipped_duplicate' => $counts['skipped_duplicate'],
                'skipped_quiet' => $counts['skipped_quiet'],
                'failed' => $counts['failed'],
                'job_id' => $counts['job_id'],
                'status' => $counts['status'],
                'message' => $counts['message'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Audit failure must never abort a scan.
        }
    }

    private function atomRoot(): string
    {
        if (\function_exists('sfConfig')) {
            $root = \sfConfig::get('sf_root_dir');
            if ($root) {
                return rtrim($root, '/');
            }
        }
        if (defined('ATOM_ROOT_PATH')) {
            return rtrim(ATOM_ROOT_PATH, '/');
        }
        if (defined('ATOM_ROOT')) {
            return rtrim(ATOM_ROOT, '/');
        }

        // lib/Services/ScannerService.php -> plugin -> atom-ahg-plugins -> root
        return rtrim(dirname(__DIR__, 4), '/');
    }

    /**
     * Resolve the ahgIngestPlugin directory across both web (Symfony, where
     * plugins are symlinked into <root>/plugins) and CLI (framework bootstrap)
     * contexts.
     */
    private function ingestPluginDir(): ?string
    {
        if (\function_exists('sfConfig')) {
            $dir = \sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin';
            if (is_dir($dir)) {
                return $dir;
            }
        }
        $root = $this->atomRoot();
        foreach (['/plugins/ahgIngestPlugin', '/atom-ahg-plugins/ahgIngestPlugin'] as $rel) {
            if (is_dir($root . $rel)) {
                return $root . $rel;
            }
        }

        return null;
    }
}
