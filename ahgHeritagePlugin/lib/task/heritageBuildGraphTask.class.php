<?php

/**
 * Heritage Knowledge Graph Build Task.
 *
 * Builds the entity relationship graph from heritage_entity_cache data.
 *
 * Usage:
 *   php symfony heritage:build-graph
 *   php symfony heritage:build-graph --rebuild
 *   php symfony heritage:build-graph --limit=5000
 *   php symfony heritage:build-graph --stats
 */
class heritageBuildGraphTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('rebuild', null, sfCommandOption::PARAMETER_NONE, 'Rebuild entire graph (clears existing data)'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum objects to process', 5000),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show graph statistics'),
            new sfCommandOption('min-confidence', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum confidence score (0.0-1.0)', 0.70),
            new sfCommandOption('min-cooccurrence', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum co-occurrence count for edges', 1),
        ]);

        $this->namespace = 'heritage';
        $this->name = 'build-graph';
        $this->briefDescription = 'Build entity relationship graph from entity cache';
        $this->detailedDescription = <<<EOF
Builds the knowledge graph from heritage_entity_cache data.
Creates nodes for unique entities and edges for co-occurrence relationships.

Examples:
  php symfony heritage:build-graph                # Incremental build
  php symfony heritage:build-graph --rebuild      # Full rebuild (clears existing)
  php symfony heritage:build-graph --limit=5000   # Process up to 5000 objects
  php symfony heritage:build-graph --stats        # Show graph statistics
  php symfony heritage:build-graph --min-confidence=0.8  # Higher confidence threshold
  php symfony heritage:build-graph --min-cooccurrence=2  # Require at least 2 co-occurrences for edge
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Initialize framework database connection
        \AhgCore\Core\AhgDb::init();

        // Load the graph service
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Heritage/Services/KnowledgeGraphService.php';

        $graphService = new \AtomFramework\Heritage\Services\KnowledgeGraphService();

        // Set thresholds
        $minConfidence = (float) ($options['min-confidence'] ?? 0.70);
        $minCoOccurrence = (int) ($options['min-cooccurrence'] ?? 1);

        $graphService->setMinConfidence($minConfidence);
        $graphService->setMinCoOccurrence($minCoOccurrence);

        // Statistics mode
        if ($options['stats']) {
            $this->showStats($graphService);

            return;
        }

        // Build graph
        $this->buildGraph(
            $graphService,
            (int) ($options['limit'] ?? 5000),
            !empty($options['rebuild'])
        );
    }

    /**
     * Show graph statistics.
     */
    private function showStats(\AtomFramework\Heritage\Services\KnowledgeGraphService $graphService): void
    {
        $this->logSection('heritage', 'Knowledge Graph Statistics');
        $this->logSection('heritage', str_repeat('-', 50));

        $stats = $graphService->getStats();

        $this->logSection('heritage', sprintf('Total nodes: %d', $stats['total_nodes']));
        $this->logSection('heritage', sprintf('Total edges: %d', $stats['total_edges']));
        $this->logSection('heritage', sprintf('Total object links: %d', $stats['total_object_links']));
        $this->logSection('heritage', sprintf('Avg connections per node: %.2f', $stats['avg_connections_per_node']));

        $this->logSection('heritage', '');
        $this->logSection('heritage', 'Nodes by Type:');
        foreach ($stats['nodes_by_type'] as $type => $count) {
            $this->logSection('heritage', sprintf('  %s: %d', $type, $count));
        }

        $this->logSection('heritage', '');
        $this->logSection('heritage', 'Edges by Type:');
        foreach ($stats['edges_by_type'] as $type => $count) {
            $this->logSection('heritage', sprintf('  %s: %d', $type, $count));
        }

        // Show top connected entities
        $this->logSection('heritage', '');
        $this->logSection('heritage', 'Top Connected Entities:');
        $topEntities = $graphService->getTopConnectedEntities(null, 10);
        foreach ($topEntities as $entity) {
            $this->logSection('heritage', sprintf(
                '  [%s] %s - %d connections, %d occurrences',
                $entity->entity_type,
                $entity->canonical_value,
                $entity->connection_count,
                $entity->occurrence_count
            ));
        }

        // Show build history
        $this->showBuildHistory();
    }

    /**
     * Show recent build history.
     */
    private function showBuildHistory(): void
    {
        $history = \Illuminate\Database\Capsule\Manager::table('heritage_graph_build_log')
            ->orderByDesc('started_at')
            ->limit(5)
            ->get();

        if ($history->isEmpty()) {
            return;
        }

        $this->logSection('heritage', '');
        $this->logSection('heritage', 'Recent Build History:');

        foreach ($history as $build) {
            $duration = $build->completed_at
                ? (strtotime($build->completed_at) - strtotime($build->started_at)) . 's'
                : 'in progress';

            $this->logSection('heritage', sprintf(
                '  [%s] %s - %s (%d nodes, %d edges, %d objects) - %s',
                $build->started_at,
                $build->build_type,
                $build->status,
                $build->nodes_created,
                $build->edges_created,
                $build->objects_processed,
                $duration
            ));

            if ($build->error_message) {
                $this->logSection('heritage', sprintf('    Error: %s', substr($build->error_message, 0, 100)));
            }
        }
    }

    /**
     * Build the knowledge graph.
     */
    private function buildGraph(
        \AtomFramework\Heritage\Services\KnowledgeGraphService $graphService,
        int $limit,
        bool $rebuild
    ): void {
        $buildType = $rebuild ? 'full rebuild' : 'incremental';
        $this->logSection('heritage', sprintf('Starting %s build (limit: %d objects)...', $buildType, $limit));

        if ($rebuild) {
            $this->logSection('heritage', 'WARNING: This will clear all existing graph data', null, 'COMMENT');

            // Check if there's existing data
            $existingNodes = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')->count();
            if ($existingNodes > 0) {
                $this->logSection('heritage', sprintf('Clearing %d existing nodes...', $existingNodes));
            }
        }

        $startTime = microtime(true);

        try {
            $results = $graphService->buildFromCache($limit, $rebuild);

            $elapsed = round(microtime(true) - $startTime, 2);

            $this->logSection('heritage', str_repeat('-', 50));
            $this->logSection('heritage', sprintf('Build completed in %s seconds', $elapsed));
            $this->logSection('heritage', sprintf('Objects processed: %d', $results['objects_processed']));
            $this->logSection('heritage', sprintf('Nodes created: %d', $results['nodes_created']));
            $this->logSection('heritage', sprintf('Nodes updated: %d', $results['nodes_updated']));
            $this->logSection('heritage', sprintf('Edges created: %d', $results['edges_created']));

            if (!empty($results['errors'])) {
                $this->logSection('heritage', sprintf('Errors: %d', count($results['errors'])), null, 'ERROR');
                foreach (array_slice($results['errors'], 0, 5) as $error) {
                    $this->logSection('heritage', sprintf('  %s', $error), null, 'ERROR');
                }
            }

            $this->logSection('heritage', 'Graph build completed successfully', null, 'INFO');
        } catch (\Exception $e) {
            $this->logSection('heritage', sprintf('Build failed: %s', $e->getMessage()), null, 'ERROR');
            throw $e;
        }
    }
}
