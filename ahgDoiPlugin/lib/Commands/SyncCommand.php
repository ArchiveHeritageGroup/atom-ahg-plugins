<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Sync DOI metadata with DataCite.
 */
class SyncCommand extends BaseCommand
{
    protected string $name = 'doi:sync';
    protected string $description = 'Sync DOI metadata with DataCite';
    protected string $detailedDescription = <<<'EOF'
    Sync DOI metadata with DataCite to ensure records are up to date.

    Examples:
      php bin/atom doi:sync --all                          Sync all DOIs (up to limit)
      php bin/atom doi:sync --id=123                       Sync single DOI record
      php bin/atom doi:sync --status=findable --limit=50   Sync findable DOIs
      php bin/atom doi:sync --repository=1                 Sync DOIs for repository
      php bin/atom doi:sync --all --queue                  Queue all for background sync
      php bin/atom doi:sync --all --dry-run                Preview without syncing
    EOF;

    protected function configure(): void
    {
        $this->addOption('all', 'a', 'Sync all DOIs');
        $this->addOption('id', null, 'Specific DOI record ID');
        $this->addOption('status', null, 'Filter by status (findable, registered, draft)');
        $this->addOption('repository', null, 'Filter by repository ID');
        $this->addOption('limit', null, 'Maximum DOIs to sync', '100');
        $this->addOption('queue', null, 'Queue for background processing instead of direct sync');
        $this->addOption('dry-run', null, 'Preview without syncing');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        if (!file_exists($serviceFile)) {
            $this->error("DoiService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgDoiPlugin\Services\DoiService();
        $dryRun = $this->hasOption('dry-run');

        // Single DOI sync
        if ($this->hasOption('id')) {
            return $this->syncSingle((int) $this->option('id'), $service, $dryRun);
        }

        // Batch sync
        if (!$this->hasOption('all')) {
            $this->error('Use --all to sync multiple DOIs or --id=X for single DOI');

            return 1;
        }

        return $this->syncBatch($service, $dryRun);
    }

    protected function syncSingle(int $doiId, $service, bool $dryRun): int
    {
        $doi = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doi) {
            $this->error("DOI record #{$doiId} not found");

            return 1;
        }

        $this->info("Syncing DOI: {$doi->doi}");

        if ($dryRun) {
            $this->comment('[DRY RUN] Would sync metadata');

            return 0;
        }

        $result = $service->updateDoi($doiId);

        if ($result['success']) {
            $this->success('Metadata synced');
        } else {
            $this->error("FAILED: {$result['error']}");

            return 1;
        }

        return 0;
    }

    protected function syncBatch($service, bool $dryRun): int
    {
        $syncOptions = [];

        if ($this->hasOption('status')) {
            $syncOptions['status'] = $this->option('status');
        }
        if ($this->hasOption('repository')) {
            $syncOptions['repository_id'] = (int) $this->option('repository');
        }

        $limit = (int) $this->option('limit', '100');
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
        $this->info("Found {$totalCount} DOIs eligible for sync" . ($dryRun ? ' [DRY RUN]' : ''));

        if ($totalCount === 0) {
            return 0;
        }

        if ($this->hasOption('queue')) {
            // Queue for background processing
            if ($dryRun) {
                $this->comment("[DRY RUN] Would queue {$totalCount} DOIs for sync");

                return 0;
            }

            $queued = $service->queueForSync($syncOptions);
            $this->success("Queued {$queued} DOIs for background sync");
            $this->line('  Run "php bin/atom doi:process-queue" to process the queue');

            return 0;
        }

        // Direct sync
        $processCount = min($limit, $totalCount);
        $this->info("Processing {$processCount} of {$totalCount} DOIs...");

        if ($dryRun) {
            $this->comment("[DRY RUN] Would sync {$processCount} DOIs");

            return 0;
        }

        $result = $service->bulkSync($syncOptions);

        $this->newline();
        $this->bold('  Sync Results');
        $this->line("  Total processed: {$result['total']}");
        $this->success("Synced: {$result['synced']}");

        if ($result['failed'] > 0) {
            $this->error("Failed: {$result['failed']}");
        } else {
            $this->line("  Failed: 0");
        }

        if (!empty($result['errors'])) {
            $this->newline();
            $this->error('Errors:');
            foreach (array_slice($result['errors'], 0, 10) as $err) {
                $this->error("  - {$err}");
            }
            if (count($result['errors']) > 10) {
                $this->error('  ... and ' . (count($result['errors']) - 10) . ' more errors');
            }
        }

        return $result['failed'] > 0 ? 1 : 0;
    }
}
