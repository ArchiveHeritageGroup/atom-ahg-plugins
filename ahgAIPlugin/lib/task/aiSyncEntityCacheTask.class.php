<?php

/**
 * NER Entity Cache Sync Task.
 *
 * Syncs approved NER entities from ahg_ner_entity to heritage_entity_cache
 * for use in heritage discovery filters.
 *
 * Usage:
 *   php symfony ai:sync-entity-cache
 *   php symfony ai:sync-entity-cache --limit=500
 *   php symfony ai:sync-entity-cache --object-id=12345
 *   php symfony ai:sync-entity-cache --dry-run
 *   php symfony ai:sync-entity-cache --stats
 *   php symfony ai:sync-entity-cache --clean-orphaned
 */
class aiSyncEntityCacheTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum objects to process', 1000),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Sync specific object only'),
            new sfCommandOption('since-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Only process objects with ID > this value'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be synced without making changes'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show sync statistics'),
            new sfCommandOption('clean-orphaned', null, sfCommandOption::PARAMETER_NONE, 'Remove orphaned cache entries'),
            new sfCommandOption('min-confidence', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum confidence score (0.0-1.0)', 0.70),
        ]);

        $this->namespace = 'ai';
        $this->name = 'sync-entity-cache';
        $this->briefDescription = 'Sync approved NER entities to heritage discovery cache';
        $this->detailedDescription = <<<EOF
Synchronizes approved NER entities from ahg_ner_entity to heritage_entity_cache
for use in heritage discovery filters.

Examples:
  php symfony ai:sync-entity-cache                    # Sync up to 1000 objects
  php symfony ai:sync-entity-cache --limit=500        # Sync up to 500 objects
  php symfony ai:sync-entity-cache --object-id=12345  # Sync specific object
  php symfony ai:sync-entity-cache --since-id=1000    # Sync objects after ID 1000
  php symfony ai:sync-entity-cache --dry-run          # Preview without changes
  php symfony ai:sync-entity-cache --stats            # Show sync statistics
  php symfony ai:sync-entity-cache --clean-orphaned   # Remove orphaned entries
  php symfony ai:sync-entity-cache --min-confidence=0.8  # Higher confidence threshold
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Initialize framework database connection
        \AhgCore\Core\AhgDb::init();

        // Load the sync service
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Heritage/Services/EntityCacheSyncService.php';

        $syncService = new \AtomFramework\Heritage\Services\EntityCacheSyncService();

        // Set confidence threshold
        $minConfidence = (float) ($options['min-confidence'] ?? 0.70);
        $syncService->setMinConfidence($minConfidence);

        // Statistics mode
        if ($options['stats']) {
            $this->showStats($syncService);

            return;
        }

        // Clean orphaned mode
        if ($options['clean-orphaned']) {
            $this->cleanOrphaned($syncService);

            return;
        }

        // Sync specific object
        if (!empty($options['object-id'])) {
            $this->syncSingleObject($syncService, (int) $options['object-id'], !empty($options['dry-run']));

            return;
        }

        // Batch sync
        $this->syncBatch(
            $syncService,
            (int) ($options['limit'] ?? 1000),
            !empty($options['since-id']) ? (int) $options['since-id'] : null,
            !empty($options['dry-run'])
        );
    }

    /**
     * Show sync statistics.
     */
    private function showStats(\AtomFramework\Heritage\Services\EntityCacheSyncService $syncService): void
    {
        $this->logSection('ai', 'Entity Cache Sync Statistics');
        $this->logSection('ai', str_repeat('-', 50));

        $stats = $syncService->getStats();

        $this->logSection('ai', 'NER Entities by Status:');
        foreach ($stats['ner_entities_by_status'] as $status => $count) {
            $this->logSection('ai', sprintf('  %s: %d', $status, $count));
        }

        $this->logSection('ai', '');
        $this->logSection('ai', 'Cache Entities by Method:');
        foreach ($stats['cache_entities_by_method'] as $method => $count) {
            $this->logSection('ai', sprintf('  %s: %d', $method, $count));
        }

        $this->logSection('ai', '');
        $this->logSection('ai', 'NER Cache Entities by Type:');
        foreach ($stats['cache_ner_entities_by_type'] as $type => $count) {
            $this->logSection('ai', sprintf('  %s: %d', $type, $count));
        }

        $this->logSection('ai', '');
        $this->logSection('ai', sprintf('Objects with approved NER: %d', $stats['objects_with_approved_ner']));
        $this->logSection('ai', sprintf('Objects in cache: %d', $stats['objects_in_cache']));
        $this->logSection('ai', sprintf('Sync gap: %d objects', $stats['sync_gap']));

        // Show pending entities
        $pending = $syncService->getPendingSync(10);
        if (!empty($pending)) {
            $this->logSection('ai', '');
            $this->logSection('ai', sprintf('Pending sync (showing first %d):', count($pending)));
            foreach ($pending as $entity) {
                $this->logSection('ai', sprintf(
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

    /**
     * Clean orphaned cache entries.
     */
    private function cleanOrphaned(\AtomFramework\Heritage\Services\EntityCacheSyncService $syncService): void
    {
        $this->logSection('ai', 'Cleaning orphaned cache entries...');

        $removed = $syncService->cleanOrphaned();

        if ($removed > 0) {
            $this->logSection('ai', sprintf('Removed %d orphaned entries', $removed), null, 'INFO');
        } else {
            $this->logSection('ai', 'No orphaned entries found');
        }
    }

    /**
     * Sync a single object.
     */
    private function syncSingleObject(
        \AtomFramework\Heritage\Services\EntityCacheSyncService $syncService,
        int $objectId,
        bool $dryRun
    ): void {
        if ($dryRun) {
            $this->logSection('ai', sprintf('[DRY RUN] Would sync object %d', $objectId));

            // Count what would be synced
            $count = \Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->whereIn('status', ['linked', 'approved'])
                ->where('confidence', '>=', $syncService->getMinConfidence())
                ->count();

            $this->logSection('ai', sprintf('Would sync %d entities', $count));

            return;
        }

        $this->logSection('ai', sprintf('Syncing object %d...', $objectId));

        $synced = $syncService->syncFromNer($objectId);

        $this->logSection('ai', sprintf('Synced %d entities', $synced), null, 'INFO');
    }

    /**
     * Batch sync.
     */
    private function syncBatch(
        \AtomFramework\Heritage\Services\EntityCacheSyncService $syncService,
        int $limit,
        ?int $sinceId,
        bool $dryRun
    ): void {
        $modeLabel = $dryRun ? '[DRY RUN] ' : '';
        $this->logSection('ai', sprintf('%sSyncing up to %d objects...', $modeLabel, $limit));

        if ($sinceId !== null) {
            $this->logSection('ai', sprintf('Processing objects after ID %d', $sinceId));
        }

        $results = $syncService->syncAllApproved($limit, $sinceId, $dryRun);

        $this->logSection('ai', str_repeat('-', 50));
        $this->logSection('ai', sprintf('Objects processed: %d', $results['objects_processed']));
        $this->logSection('ai', sprintf('Entities synced: %d', $results['entities_synced']));
        $this->logSection('ai', sprintf('Processing time: %d ms', $results['processing_time_ms']));

        if ($results['last_object_id']) {
            $this->logSection('ai', sprintf('Last object ID: %d', $results['last_object_id']));
        }

        if (!empty($results['errors'])) {
            $this->logSection('ai', sprintf('Errors: %d', count($results['errors'])), null, 'ERROR');
            foreach (array_slice($results['errors'], 0, 5) as $error) {
                $this->logSection('ai', sprintf('  %s', json_encode($error)), null, 'ERROR');
            }
        }

        if (!$dryRun && $results['entities_synced'] > 0) {
            $this->logSection('ai', 'Sync completed successfully', null, 'INFO');
        }
    }
}
