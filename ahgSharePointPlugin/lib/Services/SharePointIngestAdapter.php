<?php

namespace AtomExtensions\SharePoint\Services;

use AtomExtensions\SharePoint\Repositories\SharePointDriveRepository;
use AtomExtensions\SharePoint\Repositories\SharePointEventRepository;
use AtomExtensions\SharePoint\Repositories\SharePointTenantRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointIngestAdapter — bridges a sharepoint_event row to the existing
 * IngestCommitService pipeline.
 *
 * Flow per plan §6.3 (auto/declare path) plus the §6.0 label-allowlist filter:
 *
 *   1. Load event row, mark processing.
 *   2. Idempotency check (drive_id, sp_item_id, sp_etag, status='completed').
 *   3. Resolve drive + tenant.
 *   4. GET driveItem from Graph.
 *   5. GET listItem.fields from Graph (for _ComplianceTag).
 *   6. Apply allowlist filter — drive.auto_ingest_labels must contain the tag.
 *   7. Project SP item via SharePointMappingService.
 *   8. Resolve disposition via SharePointRetentionMapper.
 *   9. Download driveItem content to uploads/sharepoint/{event_id}/.
 *  10. Create synthetic ingest_session (source='sharepoint_auto'), ingest_row,
 *      ingest_file. Hand to IngestCommitService.
 *  11. Audit + mark completed.
 *
 * @phase 2.A
 */
class SharePointIngestAdapter
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED_DUPLICATE = 'skipped_duplicate';
    public const STATUS_SKIPPED_NOT_ALLOWLISTED = 'skipped_not_allowlisted';

    public function __construct(
        private GraphClientService $graph,
        private SharePointTenantRepository $tenants,
        private SharePointDriveRepository $drives,
        private SharePointEventRepository $events,
        private SharePointMappingService $mapping,
        private SharePointRetentionMapper $retention,
    ) {
    }

    /**
     * Process a single sharepoint_event row. Returns the terminal status string.
     */
    public function ingest(int $eventId): string
    {
        $event = $this->events->find($eventId);
        if ($event === null) {
            throw new \InvalidArgumentException("Event {$eventId} not found");
        }

        $this->events->markStatus($eventId, 'processing');
        $this->events->incrementAttempts($eventId);

        try {
            // Idempotency
            if ($this->events->isDuplicate(
                (int) $event->drive_id,
                $event->sp_item_id,
                $event->sp_etag,
                $eventId,
            )) {
                $this->events->markStatus($eventId, self::STATUS_SKIPPED_DUPLICATE);
                return self::STATUS_SKIPPED_DUPLICATE;
            }

            $drive = $this->drives->find((int) $event->drive_id);
            if ($drive === null) {
                throw new \RuntimeException("Drive {$event->drive_id} not found");
            }
            $tenant = $this->tenants->find((int) $drive->tenant_id);
            if ($tenant === null) {
                throw new \RuntimeException("Tenant {$drive->tenant_id} not found");
            }

            if ($event->sp_item_id === null) {
                throw new \RuntimeException("Event {$eventId} has no sp_item_id");
            }

            $driveItem = $this->graph->get(
                (int) $tenant->id,
                "/sites/{$drive->site_id}/drives/{$drive->drive_id}/items/{$event->sp_item_id}",
            );
            $listItemFields = $this->graph->getListItemFields(
                (int) $tenant->id,
                $drive->site_id,
                $drive->drive_id,
                $event->sp_item_id,
            );

            // Label allowlist filter — auto/declare mode only ingests items
            // whose _ComplianceTag is in the drive's configured allowlist.
            // NULL/empty allowlist = auto ingest disabled (manual push only).
            if (!$this->isAllowlisted($drive, $listItemFields)) {
                $this->events->markStatus($eventId, self::STATUS_SKIPPED_NOT_ALLOWLISTED);
                return self::STATUS_SKIPPED_NOT_ALLOWLISTED;
            }

            // Projection + disposition
            $projected = $this->mapping->project((int) $drive->id, $driveItem, $listItemFields);
            $disposition = $this->retention->resolve($listItemFields);
            $rowData = array_merge($projected, $this->dispositionToRowFields($disposition));

            // File download
            $uploadDir = $this->resolveUploadDir($eventId);
            $fileName = $this->safeFileName((string) ($driveItem['name'] ?? "item-{$event->sp_item_id}"));
            $localPath = $uploadDir . '/' . $fileName;
            $this->graph->downloadDriveItem(
                (int) $tenant->id,
                $drive->site_id,
                $drive->drive_id,
                $event->sp_item_id,
                $localPath,
            );

            // Hand off to IngestCommitService
            $sessionId = $this->createIngestSession((int) $drive->id, $eventId, $rowData);
            $this->createIngestRow($sessionId, $rowData);
            $this->createIngestFile($sessionId, $localPath, (string) ($driveItem['file']['mimeType'] ?? 'application/octet-stream'));
            $jobId = $this->dispatchCommit($sessionId);

            $informationObjectId = $this->resolveInformationObjectId($jobId);

            $this->events->update($eventId, [
                'ingest_job_id' => $jobId,
                'information_object_id' => $informationObjectId,
            ]);
            $this->events->markStatus($eventId, self::STATUS_COMPLETED);
            $this->auditIngest($eventId, $informationObjectId, $event, $drive);

            return self::STATUS_COMPLETED;
        } catch (\Throwable $e) {
            $this->events->markStatus($eventId, self::STATUS_FAILED, $e->getMessage());
            // Caller (queue handler) decides on retry via QueueService backoff.
            throw $e;
        }
    }

    // ---- private helpers ----

    private function isAllowlisted(\stdClass $drive, array $listItemFields): bool
    {
        $raw = $drive->auto_ingest_labels ?? null;
        if (empty($raw)) {
            return false; // no allowlist = auto ingest disabled
        }
        $allowed = json_decode($raw, true);
        if (!is_array($allowed) || count($allowed) === 0) {
            return false;
        }
        $tag = $listItemFields['_ComplianceTag'] ?? null;
        if ($tag === null || $tag === '') {
            return false;
        }
        return in_array($tag, $allowed, true);
    }

    /**
     * Convert resolved disposition fields into the keys ingest_row.data uses.
     * Keep this conversion small and explicit so the ingest pipeline shape
     * doesn't leak across this boundary.
     */
    private function dispositionToRowFields(array $disposition): array
    {
        $out = [];
        foreach ([
            'level_of_description_id' => 'levelOfDescriptionId',
            'parent_id' => 'parentId',
            'security_classification_id' => 'securityClassificationId',
            'embargo_until' => 'embargoUntil',
        ] as $src => $dst) {
            if (isset($disposition[$src])) {
                $out[$dst] = $disposition[$src];
            }
        }
        if (!empty($disposition['compliance_tag'])) {
            $out['_compliance_tag'] = $disposition['compliance_tag'];
        }
        if (!empty($disposition['is_record'])) {
            $out['_is_record'] = true;
        }
        return $out;
    }

    private function resolveUploadDir(int $eventId): string
    {
        $base = function_exists('sfConfig::get')
            ? \sfConfig::get('sf_upload_dir')
            : (defined('SF_ROOT_DIR') ? SF_ROOT_DIR . '/uploads' : sys_get_temp_dir());
        $dir = $base . '/sharepoint/' . $eventId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function safeFileName(string $raw): string
    {
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $raw) ?? 'item';
        return substr($clean, 0, 200);
    }

    private function createIngestSession(int $driveId, int $eventId, array $rowData): int
    {
        // Reuse ingest_session schema from ahgIngestPlugin.
        // source/source_id columns added by Phase 1 migration.
        return (int) DB::table('ingest_session')->insertGetId([
            'user_id' => 1, // service identity for auto mode; resolved manual-push user replaces this in Phase 2.B
            'title' => 'SharePoint auto-ingest event ' . $eventId,
            'sector' => 'archive',
            'standard' => 'isadg',
            'source' => 'sharepoint_auto',
            'source_id' => $eventId,
            'parent_id' => $rowData['parentId'] ?? null,
            'parent_placement' => isset($rowData['parentId']) ? 'existing' : 'top_level',
            'output_create_records' => 1,
            'output_generate_sip' => 0,
            'output_generate_aip' => 0,
            'output_generate_dip' => 0,
            'derivative_thumbnails' => 1,
            'derivative_reference' => 1,
            'process_virus_scan' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createIngestRow(int $sessionId, array $rowData): void
    {
        DB::table('ingest_row')->insert([
            'session_id' => $sessionId,
            'row_index' => 0,
            'data' => json_encode($rowData, JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createIngestFile(int $sessionId, string $localPath, string $mimeType): void
    {
        DB::table('ingest_file')->insert([
            'session_id' => $sessionId,
            'filename' => basename($localPath),
            'path' => $localPath,
            'mime_type' => $mimeType,
            'size' => is_file($localPath) ? filesize($localPath) : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function dispatchCommit(int $sessionId): int
    {
        // ahgIngestPlugin's IngestCommitService is namespaced and not autoloaded
        // by Symfony 1.x. Lazy require + delegate.
        $svcFile = sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgIngestPlugin/lib/Services/IngestCommitService.php';
        if (!file_exists($svcFile)) {
            throw new \RuntimeException('IngestCommitService not found at ' . $svcFile);
        }
        require_once $svcFile;
        // TODO: instantiate via the framework's DI/container if available; for now
        // fall back to a minimal newable. The actual class name + signature must
        // be confirmed during integration; this is the documented hand-off point.
        if (!class_exists('\\AhgIngestPlugin\\Services\\IngestCommitService')) {
            throw new \RuntimeException('IngestCommitService class not visible after require');
        }
        $svc = new \AhgIngestPlugin\Services\IngestCommitService();
        return (int) $svc->startJob($sessionId);
    }

    private function resolveInformationObjectId(int $ingestJobId): ?int
    {
        // ahgIngestPlugin records the created IO on the job row (or in ingest_row).
        // Keep this best-effort — completing Phase 2.A integration confirms the path.
        $row = DB::table('ingest_job')
            ->where('id', $ingestJobId)
            ->first();
        if ($row === null) {
            return null;
        }
        return isset($row->primary_object_id) ? (int) $row->primary_object_id : null;
    }

    private function auditIngest(int $eventId, ?int $ioId, \stdClass $event, \stdClass $drive): void
    {
        if (!class_exists('\\AhgAuditTrailPlugin\\Services\\AuditService')) {
            return; // best-effort
        }
        try {
            \AhgAuditTrailPlugin\Services\AuditService::log(
                'sharepoint.ingest',
                'informationobject',
                $ioId,
                [
                    'source' => 'sharepoint_auto',
                    'sp_drive_id' => (int) $drive->id,
                    'sp_item_id' => $event->sp_item_id,
                    'sp_etag' => $event->sp_etag,
                    'event_id' => $eventId,
                ],
            );
        } catch (\Throwable $e) {
            // Audit failure must not abort ingest.
        }
    }
}
