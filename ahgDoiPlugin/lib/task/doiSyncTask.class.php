<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to sync DOI metadata with DataCite.
 */
class doiSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Sync all DOIs'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific DOI record ID'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by status (findable, registered, draft)'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by repository ID'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum DOIs to sync', 100),
            new sfCommandOption('queue', null, sfCommandOption::PARAMETER_NONE, 'Queue for background processing instead of direct sync'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without syncing'),
        ]);

        $this->namespace = 'doi';
        $this->name = 'sync';
        $this->briefDescription = 'Sync DOI metadata with DataCite';
        $this->detailedDescription = <<<EOF
Sync DOI metadata with DataCite to ensure records are up to date.

Examples:
  php symfony doi:sync --all                          # Sync all DOIs (up to limit)
  php symfony doi:sync --id=123                       # Sync single DOI record
  php symfony doi:sync --status=findable --limit=50   # Sync findable DOIs
  php symfony doi:sync --repository=1                 # Sync DOIs for repository
  php symfony doi:sync --all --queue                  # Queue all for background sync
  php symfony doi:sync --all --dry-run                # Preview without syncing
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';

        $service = new \ahgDoiPlugin\Services\DoiService();
        $dryRun = !empty($options['dry-run']);

        // Single DOI sync
        if (!empty($options['id'])) {
            return $this->syncSingle((int) $options['id'], $service, $dryRun);
        }

        // Batch sync
        if (empty($options['all'])) {
            $this->logSection('doi', 'Use --all to sync multiple DOIs or --id=X for single DOI', null, 'ERROR');

            return 1;
        }

        return $this->syncBatch($options, $service, $dryRun);
    }

    protected function syncSingle(int $doiId, $service, bool $dryRun): int
    {
        $doi = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doi) {
            $this->logSection('doi', "DOI record #{$doiId} not found", null, 'ERROR');

            return 1;
        }

        $this->logSection('doi', "Syncing DOI: {$doi->doi}");

        if ($dryRun) {
            $this->logSection('doi', '[DRY RUN] Would sync metadata', null, 'COMMENT');

            return 0;
        }

        $result = $service->updateDoi($doiId);

        if ($result['success']) {
            $this->logSection('doi', 'SUCCESS: Metadata synced', null, 'INFO');
        } else {
            $this->logSection('doi', "FAILED: {$result['error']}", null, 'ERROR');

            return 1;
        }

        return 0;
    }

    protected function syncBatch(array $options, $service, bool $dryRun): int
    {
        $syncOptions = [];

        if (!empty($options['status'])) {
            $syncOptions['status'] = $options['status'];
        }
        if (!empty($options['repository'])) {
            $syncOptions['repository_id'] = (int) $options['repository'];
        }

        $limit = (int) ($options['limit'] ?? 100);
        $syncOptions['limit'] = $limit;

        // Count total DOIs
        $query = DB::table('ahg_doi as d')
            ->join('information_object as io', 'd.information_object_id', '=', 'io.id')
            ->whereIn('d.status', ['findable', 'registered', 'draft']);

        if (!empty($syncOptions['status'])) {
            $query->where('d.status', $syncOptions['status']);
        }
        if (!empty($syncOptions['repository_id'])) {
            $query->where('io.repository_id', $syncOptions['repository_id']);
        }

        $totalCount = $query->count();
        $this->logSection('doi', "Found {$totalCount} DOIs eligible for sync" . ($dryRun ? ' [DRY RUN]' : ''));

        if ($totalCount === 0) {
            return 0;
        }

        if (!empty($options['queue'])) {
            // Queue for background processing
            if ($dryRun) {
                $this->logSection('doi', "[DRY RUN] Would queue {$totalCount} DOIs for sync", null, 'COMMENT');

                return 0;
            }

            $queued = $service->queueForSync($syncOptions);
            $this->logSection('doi', "Queued {$queued} DOIs for background sync", null, 'INFO');
            $this->logSection('doi', 'Run "php symfony doi:process-queue" to process the queue');

            return 0;
        }

        // Direct sync
        $processCount = min($limit, $totalCount);
        $this->logSection('doi', "Processing {$processCount} of {$totalCount} DOIs...");

        if ($dryRun) {
            $this->logSection('doi', "[DRY RUN] Would sync {$processCount} DOIs", null, 'COMMENT');

            return 0;
        }

        $result = $service->bulkSync($syncOptions);

        $this->logSection('doi', '----------------------------------------');
        $this->logSection('doi', "Total processed: {$result['total']}");
        $this->logSection('doi', "Synced:          {$result['synced']}", null, 'INFO');
        $this->logSection('doi', "Failed:          {$result['failed']}", null, $result['failed'] > 0 ? 'ERROR' : 'INFO');

        if (!empty($result['errors'])) {
            $this->logSection('doi', '');
            $this->logSection('doi', 'Errors:', null, 'ERROR');
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->logSection('doi', "  - {$error}", null, 'ERROR');
            }
            if (count($result['errors']) > 10) {
                $this->logSection('doi', '  ... and ' . (count($result['errors']) - 10) . ' more errors', null, 'ERROR');
            }
        }

        return $result['failed'] > 0 ? 1 : 0;
    }
}
