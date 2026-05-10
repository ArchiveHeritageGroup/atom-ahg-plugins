<?php

namespace AtomExtensions\SharePoint\Services;

use AtomExtensions\SharePoint\Repositories\SharePointDriveRepository;
use AtomExtensions\SharePoint\Repositories\SharePointTenantRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointPushService — orchestrates a manual push from SPFx.
 *
 * Flow (plan §6.5.2 + §6.5.4):
 *   1. Caller (push action) has already validated the AAD JWT and resolved
 *      the AtoM user id via SharePointUserMappingService.
 *   2. For each requested SP item:
 *      a. Acquire OBO Graph token impersonating the user.
 *      b. Fetch driveItem + listItem.fields via Graph (user perms enforced).
 *      c. Project metadata via SharePointMappingService (mapping rules).
 *      d. Merge user-edited overrides from the dialog.
 *      e. Resolve disposition via SharePointRetentionMapper.
 *   3. Create one synthetic ingest_session (source='sharepoint_push',
 *      source_id = sharepoint_push_log.id).
 *   4. One ingest_row + one ingest_file per item.
 *   5. IngestCommitService::startJob -> returns ingest_job.id.
 *   6. Audit-log via AuditService for each pushed item.
 *
 * Returns the ingest job id so the SPFx dialog can poll status.
 *
 * @phase 2.B
 */
class SharePointPushService
{
    public function __construct(
        private GraphClientService $graph,
        private SharePointTenantRepository $tenants,
        private SharePointDriveRepository $drives,
        private SharePointMappingService $mapping,
        private SharePointRetentionMapper $retention,
    ) {
    }

    /**
     * Project metadata for the dialog form WITHOUT committing anything.
     * Called by /api/v2/sharepoint/push/projection so the dialog can
     * pre-fill the editable fields.
     *
     * @param array{tenant_id:int, drive_id:int, items:array<array{site_id:string,drive_id:string,item_id:string}>} $request
     * @param array $userClaims AAD claims (for OBO file fetch later — but here we just read metadata).
     * @return array<int, array<string, mixed>> One projected metadata payload per item.
     */
    public function project(array $request, array $userClaims): array
    {
        $tenantId = (int) $request['tenant_id'];
        $driveRow = $this->drives->find((int) $request['drive_id']);
        if ($driveRow === null) {
            throw new \InvalidArgumentException("Drive {$request['drive_id']} not found");
        }

        $userToken = (string) ($userClaims['_raw'] ?? '');
        $oboToken = $this->graph->acquireOboToken(
            $tenantId,
            $userToken,
            'https://graph.microsoft.com/Files.Read.All',
        );

        $out = [];
        foreach ($request['items'] as $item) {
            $driveItem = $this->graph->get(
                $tenantId,
                "/sites/{$item['site_id']}/drives/{$item['drive_id']}/items/{$item['item_id']}",
                ['Authorization' => 'Bearer ' . $oboToken],
            );
            $fields = $this->graph->getListItemFields(
                $tenantId,
                $item['site_id'],
                $item['drive_id'],
                $item['item_id'],
            );

            $projected = $this->mapping->project((int) $driveRow->id, $driveItem, $fields);
            $disposition = $this->retention->resolve($fields);

            $out[] = [
                'sp_item_id' => $item['item_id'],
                'metadata' => $projected,
                'disposition' => $disposition,
                'name' => $driveItem['name'] ?? null,
                'mimeType' => $driveItem['file']['mimeType'] ?? null,
                'size' => $driveItem['size'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Commit a manual push.
     *
     * @param array{
     *   tenant_id:int,
     *   drive_id:int,
     *   repository_id:?int,
     *   parent_id:?int,
     *   items:array<array{site_id:string,drive_id:string,item_id:string,metadata:array<string,mixed>}>
     * } $request
     * @param int   $atomUserId   Resolved AtoM user.id
     * @param array $userClaims   AAD claims (for OBO fetch + audit)
     * @return int ingest_job.id
     */
    public function commit(array $request, int $atomUserId, array $userClaims): int
    {
        $tenantId = (int) $request['tenant_id'];
        $driveRow = $this->drives->find((int) $request['drive_id']);
        if ($driveRow === null) {
            throw new \InvalidArgumentException("Drive {$request['drive_id']} not found");
        }

        $userToken = (string) ($userClaims['_raw'] ?? '');
        $oboToken = $this->graph->acquireOboToken(
            $tenantId,
            $userToken,
            'https://graph.microsoft.com/Files.Read.All',
        );

        $sessionId = (int) DB::table('ingest_session')->insertGetId([
            'user_id' => $atomUserId,
            'title' => 'SharePoint manual push by user ' . $atomUserId,
            'sector' => $driveRow->sector,
            'standard' => 'isadg',
            'source' => 'sharepoint_push',
            'source_id' => null,
            'repository_id' => $request['repository_id'] ?? $driveRow->default_repository_id,
            'parent_id' => $request['parent_id'] ?? $driveRow->default_parent_id,
            'parent_placement' => isset($request['parent_id']) ? 'existing' : ($driveRow->default_parent_placement ?? 'top_level'),
            'output_create_records' => 1,
            'output_generate_sip' => 0,
            'output_generate_aip' => 0,
            'output_generate_dip' => 0,
            'derivative_thumbnails' => 1,
            'derivative_reference' => 1,
            'process_virus_scan' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($request['items'] as $idx => $item) {
            $driveItem = $this->graph->get(
                $tenantId,
                "/sites/{$item['site_id']}/drives/{$item['drive_id']}/items/{$item['item_id']}",
                ['Authorization' => 'Bearer ' . $oboToken],
            );

            $localPath = $this->downloadItemAsUser(
                $tenantId, $oboToken, $item['site_id'], $item['drive_id'], $item['item_id'],
                $sessionId, (string) ($driveItem['name'] ?? "item-{$item['item_id']}"),
            );

            // The dialog supplied edited metadata; trust those values directly.
            $rowData = $item['metadata'];
            $rowData['_sharepoint_drive_id'] = (int) $driveRow->id;
            $rowData['_sharepoint_item_id'] = $item['item_id'];
            $rowData['_pushed_by_user_id'] = $atomUserId;
            $rowData['_pushed_by_aad_oid'] = $userClaims['oid'] ?? null;

            DB::table('ingest_row')->insert([
                'session_id' => $sessionId,
                'row_index' => $idx,
                'data' => json_encode($rowData, JSON_UNESCAPED_SLASHES),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            DB::table('ingest_file')->insert([
                'session_id' => $sessionId,
                'filename' => basename($localPath),
                'path' => $localPath,
                'mime_type' => (string) ($driveItem['file']['mimeType'] ?? 'application/octet-stream'),
                'size' => is_file($localPath) ? filesize($localPath) : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->dispatchCommit($sessionId, $userClaims);
    }

    private function downloadItemAsUser(int $tenantId, string $oboToken, string $siteId, string $driveId, string $itemId, int $sessionId, string $name): string
    {
        $base = sfConfig::get('sf_upload_dir');
        $dir = $base . '/sharepoint/push/' . $sessionId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? 'item';
        $absPath = $dir . '/' . substr($clean, 0, 200);

        // TODO: GraphClientService::downloadDriveItem must accept an explicit
        // bearer token override so we can pass the OBO token (instead of the
        // app-only token from acquireToken). Phase 2.B integration step.
        $this->graph->downloadDriveItem($tenantId, $siteId, $driveId, $itemId, $absPath);

        return $absPath;
    }

    private function dispatchCommit(int $sessionId, array $userClaims): int
    {
        $svcFile = sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgIngestPlugin/lib/Services/IngestCommitService.php';
        if (!file_exists($svcFile)) {
            throw new \RuntimeException('IngestCommitService not found at ' . $svcFile);
        }
        require_once $svcFile;
        if (!class_exists('\\AhgIngestPlugin\\Services\\IngestCommitService')) {
            throw new \RuntimeException('IngestCommitService class not visible after require');
        }
        $svc = new \AhgIngestPlugin\Services\IngestCommitService();
        $jobId = (int) $svc->startJob($sessionId);

        // Audit one log entry per push (job-level rather than per item).
        if (class_exists('\\AhgAuditTrailPlugin\\Services\\AuditService')) {
            try {
                \AhgAuditTrailPlugin\Services\AuditService::log(
                    'sharepoint.push',
                    'ingest_session',
                    $sessionId,
                    [
                        'job_id' => $jobId,
                        'aad_oid' => $userClaims['oid'] ?? null,
                        'aad_upn' => $userClaims['upn'] ?? null,
                    ],
                );
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        return $jobId;
    }
}
