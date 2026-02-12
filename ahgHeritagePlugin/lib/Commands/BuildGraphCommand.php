<?php

namespace AtomFramework\Console\Commands\Heritage;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Build the entity relationship graph from heritage entity cache data.
 */
class BuildGraphCommand extends BaseCommand
{
    protected string $name = 'heritage:build-graph';
    protected string $description = 'Build entity relationship graph from entity cache';
    protected string $detailedDescription = <<<'EOF'
    Builds the knowledge graph from heritage_entity_cache data.
    Creates nodes for unique entities and edges for co-occurrence relationships.

    Examples:
      php bin/atom heritage:build-graph                        Incremental build
      php bin/atom heritage:build-graph --rebuild              Full rebuild (clears existing)
      php bin/atom heritage:build-graph --limit=5000           Process up to 5000 objects
      php bin/atom heritage:build-graph --stats                Show graph statistics
      php bin/atom heritage:build-graph --min-confidence=0.8   Higher confidence threshold
      php bin/atom heritage:build-graph --min-cooccurrence=2   Require at least 2 co-occurrences for edge
    EOF;

    protected function configure(): void
    {
        $this->addOption('rebuild', null, 'Rebuild entire graph (clears existing data)');
        $this->addOption('limit', null, 'Maximum objects to process', '5000');
        $this->addOption('stats', null, 'Show graph statistics');
        $this->addOption('min-confidence', null, 'Minimum confidence score (0.0-1.0)', '0.70');
        $this->addOption('min-cooccurrence', null, 'Minimum co-occurrence count for edges', '1');
    }

    protected function handle(): int
    {
        // Load the graph service
        $servicePath = $this->getFrameworkRoot() . '/src/Heritage/Services/KnowledgeGraphService.php';
        require_once $servicePath;

        $graphService = new \AtomFramework\Heritage\Services\KnowledgeGraphService();

        // Set thresholds
        $minConfidence = (float) $this->option('min-confidence');
        $minCoOccurrence = (int) $this->option('min-cooccurrence');

        $graphService->setMinConfidence($minConfidence);
        $graphService->setMinCoOccurrence($minCoOccurrence);

        // Statistics mode
        if ($this->hasOption('stats')) {
            $this->showStats($graphService);

            return 0;
        }

        // Build graph
        $this->buildGraph(
            $graphService,
            (int) $this->option('limit'),
            $this->hasOption('rebuild')
        );

        return 0;
    }

    /**
     * Show graph statistics.
     */
    private function showStats(\AtomFramework\Heritage\Services\KnowledgeGraphService $graphService): void
    {
        $this->bold('Knowledge Graph Statistics');
        $this->line(str_repeat('-', 50));

        $stats = $graphService->getStats();

        $this->line(sprintf('Total nodes: %d', $stats['total_nodes']));
        $this->line(sprintf('Total edges: %d', $stats['total_edges']));
        $this->line(sprintf('Total object links: %d', $stats['total_object_links']));
        $this->line(sprintf('Avg connections per node: %.2f', $stats['avg_connections_per_node']));

        $this->newline();
        $this->info('Nodes by Type:');
        foreach ($stats['nodes_by_type'] as $type => $count) {
            $this->line(sprintf('  %s: %d', $type, $count));
        }

        $this->newline();
        $this->info('Edges by Type:');
        foreach ($stats['edges_by_type'] as $type => $count) {
            $this->line(sprintf('  %s: %d', $type, $count));
        }

        // Show top connected entities
        $this->newline();
        $this->info('Top Connected Entities:');
        $topEntities = $graphService->getTopConnectedEntities(null, 10);
        foreach ($topEntities as $entity) {
            $this->line(sprintf(
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
        $history = DB::table('heritage_graph_build_log')
            ->orderByDesc('started_at')
            ->limit(5)
            ->get();

        if ($history->isEmpty()) {
            return;
        }

        $this->newline();
        $this->info('Recent Build History:');

        foreach ($history as $build) {
            $duration = $build->completed_at
                ? (strtotime($build->completed_at) - strtotime($build->started_at)) . 's'
                : 'in progress';

            $this->line(sprintf(
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
                $this->error(sprintf('    Error: %s', substr($build->error_message, 0, 100)));
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
        $this->info(sprintf('Starting %s build (limit: %d objects)...', $buildType, $limit));

        if ($rebuild) {
            $this->warning('WARNING: This will clear all existing graph data');

            // Check if there's existing data
            $existingNodes = DB::table('heritage_entity_graph_node')->count();
            if ($existingNodes > 0) {
                $this->line(sprintf('Clearing %d existing nodes...', $existingNodes));
            }
        }

        $startTime = microtime(true);

        try {
            $results = $graphService->buildFromCache($limit, $rebuild);

            $elapsed = round(microtime(true) - $startTime, 2);

            $this->line(str_repeat('-', 50));
            $this->line(sprintf('Build completed in %s seconds', $elapsed));
            $this->line(sprintf('Objects processed: %d', $results['objects_processed']));
            $this->line(sprintf('Nodes created: %d', $results['nodes_created']));
            $this->line(sprintf('Nodes updated: %d', $results['nodes_updated']));
            $this->line(sprintf('Edges created: %d', $results['edges_created']));

            if (!empty($results['errors'])) {
                $this->error(sprintf('Errors: %d', count($results['errors'])));
                foreach (array_slice($results['errors'], 0, 5) as $error) {
                    $this->error(sprintf('  %s', $error));
                }
            }

            $this->success('Graph build completed successfully');
        } catch (\Exception $e) {
            $this->error(sprintf('Build failed: %s', $e->getMessage()));

            return;
        }
    }
}
