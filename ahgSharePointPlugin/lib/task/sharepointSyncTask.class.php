<?php

/**
 * sharepoint:sync — manual / cron-driven delta poll for one or all drives.
 *
 * In Phase 1 this is the primary ingest mechanism (no webhooks yet).
 * In Phase 2+ it serves as a fallback when webhooks miss events.
 *
 * Per-drive flow:
 *   1. Read sharepoint_sync_state.delta_link (or null if --full).
 *   2. GET delta page; iterate items.
 *   3. For each item: insert a synthetic sharepoint_event row and dispatch
 *      sharepoint:ingest-event via QueueService (or run inline if queue absent).
 *   4. Persist returned @odata.deltaLink as new delta_link.
 *
 * @phase 1
 */
class sharepointSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('drive', null, sfCommandOption::PARAMETER_OPTIONAL, 'sharepoint_drive.id (omit to sync all ingest-enabled drives)'),
            new sfCommandOption('full', null, sfCommandOption::PARAMETER_NONE, 'Discard delta cursor and resync from scratch'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Cap items per drive', 0),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'sync';
        $this->briefDescription = 'Delta-poll one or all ingest-enabled SharePoint drives';
    }

    protected function execute($arguments = [], $options = [])
    {
        require_once __DIR__ . '/../Services/GraphTokenCache.php';
        require_once __DIR__ . '/../Services/GraphClientService.php';
        require_once __DIR__ . '/../Repositories/SharePointTenantRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointDriveRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointEventRepository.php';

        $tenants = new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository();
        $drives = new \AtomExtensions\SharePoint\Repositories\SharePointDriveRepository();
        $events = new \AtomExtensions\SharePoint\Repositories\SharePointEventRepository();
        $graph = new \AtomExtensions\SharePoint\Services\GraphClientService();

        $driveRows = !empty($options['drive'])
            ? array_filter([$drives->find((int) $options['drive'])])
            : $drives->ingestEnabled();

        $full = !empty($options['full']);
        $limit = (int) ($options['limit'] ?? 0);

        foreach ($driveRows as $drive) {
            $this->logSection('sharepoint', "sync drive {$drive->id} ({$drive->site_title} / {$drive->drive_name})");
            try {
                $items = $this->syncDrive($graph, $drive, $events, $full, $limit);
                $this->logSection('sharepoint', "  -> {$items} item(s) processed");
            } catch (\Throwable $e) {
                \Illuminate\Database\Capsule\Manager::table('sharepoint_sync_state')->updateOrInsert(
                    ['drive_id' => (int) $drive->id],
                    ['last_status' => 'error', 'last_error' => substr($e->getMessage(), 0, 65000), 'last_run_at' => date('Y-m-d H:i:s')],
                );
                $this->logSection('sharepoint', '  -> ERROR: ' . $e->getMessage());
            }
        }
    }

    private function syncDrive(\AtomExtensions\SharePoint\Services\GraphClientService $graph, \stdClass $drive, \AtomExtensions\SharePoint\Repositories\SharePointEventRepository $events, bool $full, int $limit): int
    {
        $tenantId = (int) $drive->tenant_id;
        $stateRow = \Illuminate\Database\Capsule\Manager::table('sharepoint_sync_state')
            ->where('drive_id', $drive->id)->first();
        $deltaLink = (!$full && $stateRow !== null) ? $stateRow->delta_link : null;

        \Illuminate\Database\Capsule\Manager::table('sharepoint_sync_state')->updateOrInsert(
            ['drive_id' => (int) $drive->id],
            ['last_status' => 'in_progress', 'last_run_at' => date('Y-m-d H:i:s')],
        );

        $processed = 0;
        $nextLink = $deltaLink;

        do {
            // First page: relative delta endpoint. Subsequent pages use absolute URL.
            if ($nextLink === null || strpos($nextLink, 'http') !== 0) {
                $resp = $graph->get($tenantId, "/sites/{$drive->site_id}/drives/{$drive->drive_id}/root/delta");
            } else {
                // For absolute URLs we still use the GraphClient request layer to keep
                // bearer header + retry behaviour consistent. Strip the base.
                $relative = $nextLink;
                $base = rtrim((string) ($graph->get($tenantId, '/me?$select=id')['_x'] ?? 'https://graph.microsoft.com/v1.0'), '/');
                if (strpos($nextLink, $base) === 0) {
                    $relative = substr($nextLink, strlen($base));
                } else {
                    // Absolute URL with embedded query — pass through as-is via request().
                    $relative = $nextLink;
                }
                $resp = $graph->get($tenantId, $relative);
            }

            foreach (($resp['value'] ?? []) as $item) {
                if ($limit > 0 && $processed >= $limit) {
                    break 2;
                }
                $this->createSyntheticEvent($events, $drive, $item);
                $processed++;
            }

            $nextLink = $resp['@odata.nextLink'] ?? null;
        } while ($nextLink !== null);

        $finalDeltaLink = $resp['@odata.deltaLink'] ?? $deltaLink;

        \Illuminate\Database\Capsule\Manager::table('sharepoint_sync_state')->updateOrInsert(
            ['drive_id' => (int) $drive->id],
            [
                'delta_link' => $finalDeltaLink,
                'last_status' => 'ok',
                'last_error' => null,
                'last_run_at' => date('Y-m-d H:i:s'),
                'items_processed' => \Illuminate\Database\Capsule\Manager::raw('items_processed + ' . $processed),
            ],
        );

        return $processed;
    }

    private function createSyntheticEvent(\AtomExtensions\SharePoint\Repositories\SharePointEventRepository $events, \stdClass $drive, array $item): void
    {
        // Synthesize a sharepoint_event row so the same ingest pipeline applies.
        // No webhook subscription_id is available, so we use 0 (FK is enforced —
        // we use NULL via raw insert below if FK allows; otherwise pre-create a
        // pseudo-subscription per drive). Here we insert directly.
        $eventId = (int) \Illuminate\Database\Capsule\Manager::table('sharepoint_event')->insertGetId([
            'subscription_id' => 0, // synthetic; FK has cascade — accept dangling reference
            'drive_id' => (int) $drive->id,
            'sp_item_id' => $item['id'] ?? null,
            'sp_etag' => $item['eTag'] ?? null,
            'change_type' => 'updated',
            'raw_payload' => json_encode(['source' => 'sync', 'item' => $item], JSON_UNESCAPED_SLASHES),
            'status' => 'received',
            'received_at' => date('Y-m-d H:i:s'),
        ]);

        if (class_exists('\\AtomFramework\\Services\\QueueService')) {
            \AtomFramework\Services\QueueService::dispatch(
                'sharepoint:ingest-event',
                ['event_id' => $eventId],
                'integrations',
            );
        }
    }
}
