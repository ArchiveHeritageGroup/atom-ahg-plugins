<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * NER Entity Cache Sync Command.
 *
 * Syncs approved NER entities from ahg_ner_entity to heritage_entity_cache
 * for use in heritage discovery filters.
 */
class SyncEntityCacheCommand extends BaseCommand
{
    protected string $name = 'ai:sync-entity-cache';
    protected string $description = 'Sync approved NER entities to heritage discovery cache';
    protected string $detailedDescription = <<<'EOF'
    Synchronizes approved NER entities from ahg_ner_entity to heritage_entity_cache
    for use in heritage discovery filters.

    Examples:
      php bin/atom ai:sync-entity-cache                       # Sync up to 1000 objects
      php bin/atom ai:sync-entity-cache --limit=500           # Sync up to 500 objects
      php bin/atom ai:sync-entity-cache --object-id=12345     # Sync specific object
      php bin/atom ai:sync-entity-cache --since-id=1000       # Sync objects after ID 1000
      php bin/atom ai:sync-entity-cache --dry-run             # Preview without changes
      php bin/atom ai:sync-entity-cache --stats               # Show sync statistics
      php bin/atom ai:sync-entity-cache --clean-orphaned      # Remove orphaned entries
      php bin/atom ai:sync-entity-cache --min-confidence=0.8  # Higher confidence threshold
    EOF;

    protected function configure(): void
    {
        $this->addOption('limit', null, 'Maximum objects to process', '1000');
        $this->addOption('object-id', null, 'Sync specific object only');
        $this->addOption('since-id', null, 'Only process objects with ID > this value');
        $this->addOption('dry-run', null, 'Show what would be synced without making changes');
        $this->addOption('stats', null, 'Show sync statistics');
        $this->addOption('clean-orphaned', null, 'Remove orphaned cache entries');
        $this->addOption('min-confidence', null, 'Minimum confidence score (0.0-1.0)', '0.70');
    }

    protected function handle(): int
    {
        // Load the sync service
        $servicePath = $this->getAtomRoot() . '/atom-framework/src/Heritage/Services/EntityCacheSyncService.php';
        require_once $servicePath;

        $syncService = new \AtomFramework\Heritage\Services\EntityCacheSyncService();

        // Set confidence threshold
        $minConfidence = (float) ($this->option('min-confidence') ?? 0.70);
        $syncService->setMinConfidence($minConfidence);

        // Statistics mode
        if ($this->hasOption('stats')) {
            $this->showStats($syncService);
            return 0;
        }

        // Clean orphaned mode
        if ($this->hasOption('clean-orphaned')) {
            $this->cleanOrphaned($syncService);
            return 0;
        }

        // Sync specific object
        if ($this->option('object-id')) {
            $this->syncSingleObject($syncService, (int) $this->option('object-id'), $this->hasOption('dry-run'));
            return 0;
        }

        // Batch sync
        $this->syncBatch(
            $syncService,
            (int) ($this->option('limit') ?? 1000),
            $this->option('since-id') ? (int) $this->option('since-id') : null,
            $this->hasOption('dry-run')
        );

        return 0;
    }

    private function showStats(\AtomFramework\Heritage\Services\EntityCacheSyncService $syncService): void
    {
        $this->info('Entity Cache Sync Statistics');
        $this->line(str_repeat('-', 50));

        $stats = $syncService->getStats();

        $this->bold('NER Entities by Status:');
        foreach ($stats['ner_entities_by_status'] as $status => $count) {
            $this->line(sprintf('  %s: %d', $status, $count));
        }

        $this->newline();
        $this->bold('Cache Entities by Method:');
        foreach ($stats['cache_entities_by_method'] as $method => $count) {
            $this->line(sprintf('  %s: %d', $method, $count));
        }

        $this->newline();
        $this->bold('NER Cache Entities by Type:');
        foreach ($stats['cache_ner_entities_by_type'] as $type => $count) {
            $this->line(sprintf('  %s: %d', $type, $count));
        }

        $this->newline();
        $this->info(sprintf('Objects with approved NER: %d', $stats['objects_with_approved_ner']));
        $this->info(sprintf('Objects in cache: %d', $stats['objects_in_cache']));
        $this->info(sprintf('Sync gap: %d objects', $stats['sync_gap']));

        // Show pending entities
        $pending = $syncService->getPendingSync(10);
        if (!empty($pending)) {
            $this->newline();
            $this->bold(sprintf('Pending sync (showing first %d):', count($pending)));
            foreach ($pending as $entity) {
                $this->line(sprintf(
                    '  [%d] Object %d: %s (%s) - %.2f confidence',
                    $entity->id,
                    $entity->object_id,
                    $entity->entity_value,
                    $entity->entity_type,
                    $entity->confidence
                ));
            }
        }
    }

    private function cleanOrphaned(\AtomFramework\Heritage\Services\EntityCacheSyncService $syncService): void
    {
        $this->info('Cleaning orphaned cache entries...');

        $removed = $syncService->cleanOrphaned();

        if ($removed > 0) {
            $this->success(sprintf('Removed %d orphaned entries', $removed));
        } else {
            $this->info('No orphaned entries found');
        }
    }

    private function syncSingleObject(
        \AtomFramework\Heritage\Services\EntityCacheSyncService $syncService,
        int $objectId,
        bool $dryRun
    ): void {
        if ($dryRun) {
            $this->info(sprintf('[DRY RUN] Would sync object %d', $objectId));

            $count = DB::table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->whereIn('status', ['linked', 'approved'])
                ->where('confidence', '>=', $syncService->getMinConfidence())
                ->count();

            $this->info(sprintf('Would sync %d entities', $count));

            return;
        }

        $this->info(sprintf('Syncing object %d...', $objectId));

        $synced = $syncService->syncFromNer($objectId);

        $this->success(sprintf('Synced %d entities', $synced));
    }

    private function syncBatch(
        \AtomFramework\Heritage\Services\EntityCacheSyncService $syncService,
        int $limit,
        ?int $sinceId,
        bool $dryRun
    ): void {
        $modeLabel = $dryRun ? '[DRY RUN] ' : '';
        $this->info(sprintf('%sSyncing up to %d objects...', $modeLabel, $limit));

        if ($sinceId !== null) {
            $this->info(sprintf('Processing objects after ID %d', $sinceId));
        }

        $results = $syncService->syncAllApproved($limit, $sinceId, $dryRun);

        $this->line(str_repeat('-', 50));
        $this->info(sprintf('Objects processed: %d', $results['objects_processed']));
        $this->info(sprintf('Entities synced: %d', $results['entities_synced']));
        $this->info(sprintf('Processing time: %d ms', $results['processing_time_ms']));

        if ($results['last_object_id']) {
            $this->info(sprintf('Last object ID: %d', $results['last_object_id']));
        }

        if (!empty($results['errors'])) {
            $this->error(sprintf('Errors: %d', count($results['errors'])));
            foreach (array_slice($results['errors'], 0, 5) as $error) {
                $this->error(sprintf('  %s', json_encode($error)));
            }
        }

        if (!$dryRun && $results['entities_synced'] > 0) {
            $this->success('Sync completed successfully');
        }
    }
}
