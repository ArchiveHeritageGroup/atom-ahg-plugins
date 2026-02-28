<?php

/**
 * Linked Data Sync Task.
 *
 * Unified CLI for linking AtoM entities to external authority sources:
 * VIAF, Wikidata, and Getty vocabularies.
 *
 * Usage:
 *   php symfony linked-data:sync --source=all --limit=50
 *   php symfony linked-data:sync --source=viaf --entity-type=person --limit=20
 *   php symfony linked-data:sync --source=wikidata --limit=10 --dry-run
 *   php symfony linked-data:sync --source=getty --entity-type=place --limit=30
 *   php symfony linked-data:sync --stats
 */
class linkedDataSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('source', null, sfCommandOption::PARAMETER_OPTIONAL, 'Source: viaf|wikidata|getty|all', 'all'),
            new sfCommandOption('entity-type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Entity type: person|organization|place|all', 'all'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum entities to process per source', 50),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without writing to database'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show linking statistics'),
        ]);

        $this->namespace = 'linked-data';
        $this->name = 'sync';
        $this->briefDescription = 'Link entities to VIAF, Wikidata, and Getty authority sources';
        $this->detailedDescription = <<<EOF
Links heritage_entity_graph_node entities to external authority records.

Sources:
  viaf      - VIAF (Virtual International Authority File) for persons/organizations
  wikidata  - Wikidata for persons, organizations, places
  getty     - Getty vocabularies (AAT, TGN, ULAN) for concepts, places, persons

Entity types:
  person       - Link persons (VIAF personal, Wikidata Q5, Getty ULAN)
  organization - Link organizations (VIAF corporate, Wikidata Q43229)
  place        - Link places (Wikidata, Getty TGN)
  concept      - Link concepts (Getty AAT only)
  all          - Process all applicable types for the chosen source

Examples:
  php symfony linked-data:sync --source=viaf --limit=10 --dry-run
  php symfony linked-data:sync --source=wikidata --entity-type=person --limit=20
  php symfony linked-data:sync --source=getty --entity-type=place --limit=30
  php symfony linked-data:sync --source=all --limit=50
  php symfony linked-data:sync --stats
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Initialize framework database connection
        \AhgCore\Core\AhgDb::init();

        $source = strtolower($options['source'] ?? 'all');
        $entityType = strtolower($options['entity-type'] ?? 'all');
        $limit = (int) ($options['limit'] ?? 50);
        $dryRun = !empty($options['dry-run']);

        // Statistics mode
        if (!empty($options['stats'])) {
            $this->showStats();
            return;
        }

        $this->logSection('linked-data', sprintf(
            'Starting sync: source=%s, type=%s, limit=%d%s',
            $source,
            $entityType,
            $limit,
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $startTime = microtime(true);
        $totalStats = [
            'viaf' => null,
            'wikidata' => null,
            'getty' => null,
        ];

        // VIAF
        if (in_array($source, ['viaf', 'all'])) {
            $totalStats['viaf'] = $this->syncViaf($entityType, $limit, $dryRun);
        }

        // Wikidata
        if (in_array($source, ['wikidata', 'all'])) {
            $totalStats['wikidata'] = $this->syncWikidata($entityType, $limit, $dryRun);
        }

        // Getty
        if (in_array($source, ['getty', 'all'])) {
            $totalStats['getty'] = $this->syncGetty($entityType, $limit, $dryRun);
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        // Summary
        $this->logSection('linked-data', str_repeat('=', 60));
        $this->logSection('linked-data', sprintf('Completed in %s seconds', $elapsed));

        foreach ($totalStats as $src => $stats) {
            if ($stats !== null) {
                $this->logSection('linked-data', sprintf(
                    '  %s: %d processed, %d matched, %d failed',
                    strtoupper($src),
                    $stats['processed'] ?? 0,
                    $stats['matched'] ?? 0,
                    $stats['failed'] ?? 0
                ));
            }
        }
    }

    /**
     * Sync VIAF for persons/organizations.
     */
    private function syncViaf(string $entityType, int $limit, bool $dryRun): array
    {
        $this->logSection('linked-data', '--- VIAF Linking ---');

        $pluginDir = sfConfig::get('sf_root_dir') . '/plugins/ahgSemanticSearchPlugin';
        require_once $pluginDir . '/lib/Services/ViafLinkingService.php';

        $service = new \AtomFramework\Services\SemanticSearch\ViafLinkingService();

        // VIAF only supports person and organization
        $viafType = $entityType;
        if (!in_array($viafType, ['person', 'organization'])) {
            $viafType = 'all'; // Will filter to person+organization internally
        }

        $stats = $service->batchLink($viafType, $limit, $dryRun);

        $this->logSection('linked-data', sprintf(
            'VIAF: %d processed, %d matched, %d failed',
            $stats['processed'],
            $stats['matched'],
            $stats['failed']
        ));

        if (!empty($stats['errors'])) {
            foreach (array_slice($stats['errors'], 0, 5) as $error) {
                $this->logSection('linked-data', "  ERROR: {$error}", null, 'ERROR');
            }
        }

        return $stats;
    }

    /**
     * Sync Wikidata for persons/organizations/places.
     */
    private function syncWikidata(string $entityType, int $limit, bool $dryRun): array
    {
        $this->logSection('linked-data', '--- Wikidata Linking ---');

        $pluginDir = sfConfig::get('sf_root_dir') . '/plugins/ahgSemanticSearchPlugin';
        require_once $pluginDir . '/lib/Services/WikidataActorLinkingService.php';

        $service = new \AtomFramework\Services\SemanticSearch\WikidataActorLinkingService();

        $stats = $service->batchLink($entityType, $limit, $dryRun);

        $this->logSection('linked-data', sprintf(
            'Wikidata: %d processed, %d matched, %d failed',
            $stats['processed'],
            $stats['matched'],
            $stats['failed']
        ));

        if (!empty($stats['errors'])) {
            foreach (array_slice($stats['errors'], 0, 5) as $error) {
                $this->logSection('linked-data', "  ERROR: {$error}", null, 'ERROR');
            }
        }

        return $stats;
    }

    /**
     * Sync Getty for concepts/places/persons via heritage graph → Getty bridge.
     */
    private function syncGetty(string $entityType, int $limit, bool $dryRun): array
    {
        $this->logSection('linked-data', '--- Getty Linking ---');

        $stats = [
            'processed' => 0,
            'matched' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Getty linking requires the museum plugin's Getty services
        $museumPluginDir = sfConfig::get('sf_root_dir') . '/plugins/ahgMuseumPlugin';
        $gettyServicePath = $museumPluginDir . '/lib/Services/Getty/GettyLinkingService.php';

        if (!file_exists($gettyServicePath)) {
            $this->logSection('linked-data', 'Getty services not available (ahgMuseumPlugin not installed)', null, 'COMMENT');
            return $stats;
        }

        // Load Getty dependencies
        require_once $museumPluginDir . '/lib/Services/Getty/GettyVocabularyInterface.php';
        require_once $museumPluginDir . '/lib/Services/Getty/GettySparqlService.php';
        require_once $museumPluginDir . '/lib/Services/Getty/AatService.php';
        require_once $museumPluginDir . '/lib/Services/Getty/TgnService.php';
        require_once $museumPluginDir . '/lib/Services/Getty/UlanService.php';
        require_once $museumPluginDir . '/lib/Services/Getty/GettyCacheService.php';
        require_once $museumPluginDir . '/lib/Services/Getty/GettyLinkRepository.php';
        require_once $gettyServicePath;

        // Map entity types to Getty vocabularies
        $typeVocabMap = [
            'place' => 'tgn',
            'person' => 'ulan',
            'concept' => 'aat',
        ];

        // Build list of types to process
        $typesToProcess = ($entityType === 'all')
            ? array_keys($typeVocabMap)
            : (isset($typeVocabMap[$entityType]) ? [$entityType] : []);

        if (empty($typesToProcess)) {
            $this->logSection('linked-data', sprintf('Entity type "%s" not applicable for Getty', $entityType), null, 'COMMENT');
            return $stats;
        }

        foreach ($typesToProcess as $type) {
            $vocabulary = $typeVocabMap[$type];

            // Get unlinked graph nodes of this type that have a term_id
            $nodes = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->where('entity_type', $type)
                ->whereNotNull('term_id')
                ->limit($limit)
                ->get();

            foreach ($nodes as $node) {
                try {
                    if ($dryRun) {
                        $this->logSection('linked-data', sprintf(
                            '  [DRY RUN] Would link %s "%s" (term_id=%d) to Getty %s',
                            $type,
                            $node->canonical_value,
                            $node->term_id,
                            strtoupper($vocabulary)
                        ));
                        $stats['processed']++;
                        continue;
                    }

                    // Check if already linked in getty_vocabulary_link
                    $existingLink = \Illuminate\Database\Capsule\Manager::table('getty_vocabulary_link')
                        ->where('term_id', $node->term_id)
                        ->where('vocabulary', $vocabulary)
                        ->whereIn('status', ['confirmed', 'suggested'])
                        ->first();

                    if ($existingLink) {
                        $stats['processed']++;
                        $stats['matched']++;
                        continue;
                    }

                    $stats['processed']++;

                    // Rate limiting
                    usleep(500000);

                } catch (\Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = "{$node->canonical_value}: " . $e->getMessage();
                }
            }
        }

        $this->logSection('linked-data', sprintf(
            'Getty: %d processed, %d matched, %d failed',
            $stats['processed'],
            $stats['matched'],
            $stats['failed']
        ));

        return $stats;
    }

    /**
     * Show linking statistics.
     */
    private function showStats(): void
    {
        $this->logSection('linked-data', 'Linked Data Statistics');
        $this->logSection('linked-data', str_repeat('=', 60));

        // Heritage graph node stats
        try {
            $totalNodes = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')->count();
            $nodesByType = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->selectRaw('entity_type, COUNT(*) as cnt')
                ->groupBy('entity_type')
                ->pluck('cnt', 'entity_type')
                ->toArray();

            $this->logSection('linked-data', sprintf('Total graph nodes: %d', $totalNodes));
            foreach ($nodesByType as $type => $count) {
                $this->logSection('linked-data', sprintf('  %s: %d', $type, $count));
            }
        } catch (\Exception $e) {
            $this->logSection('linked-data', 'heritage_entity_graph_node table not available', null, 'COMMENT');
        }

        $this->logSection('linked-data', '');

        // VIAF stats
        try {
            $viafLinked = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->whereNotNull('viaf_id')
                ->count();
            $viafTotal = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->whereIn('entity_type', ['person', 'organization'])
                ->count();

            $this->logSection('linked-data', sprintf(
                'VIAF: %d/%d linked (%.1f%%)',
                $viafLinked,
                $viafTotal,
                $viafTotal > 0 ? ($viafLinked / $viafTotal) * 100 : 0
            ));
        } catch (\Exception $e) {
            $this->logSection('linked-data', 'VIAF stats unavailable');
        }

        // Wikidata stats
        try {
            $wdLinked = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->whereNotNull('wikidata_id')
                ->count();
            $wdTotal = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->whereIn('entity_type', ['person', 'organization', 'place'])
                ->count();

            $this->logSection('linked-data', sprintf(
                'Wikidata: %d/%d linked (%.1f%%)',
                $wdLinked,
                $wdTotal,
                $wdTotal > 0 ? ($wdLinked / $wdTotal) * 100 : 0
            ));
        } catch (\Exception $e) {
            $this->logSection('linked-data', 'Wikidata stats unavailable');
        }

        // Getty stats
        try {
            $gettyCount = \Illuminate\Database\Capsule\Manager::table('getty_vocabulary_link')
                ->whereIn('status', ['confirmed', 'suggested'])
                ->count();
            $gettyByVocab = \Illuminate\Database\Capsule\Manager::table('getty_vocabulary_link')
                ->whereIn('status', ['confirmed', 'suggested'])
                ->selectRaw('vocabulary, COUNT(*) as cnt')
                ->groupBy('vocabulary')
                ->pluck('cnt', 'vocabulary')
                ->toArray();

            $this->logSection('linked-data', sprintf('Getty: %d total links', $gettyCount));
            foreach ($gettyByVocab as $vocab => $count) {
                $this->logSection('linked-data', sprintf('  %s: %d', strtoupper($vocab), $count));
            }
        } catch (\Exception $e) {
            $this->logSection('linked-data', 'Getty stats unavailable');
        }

        // Sample linked entities
        $this->logSection('linked-data', '');
        $this->logSection('linked-data', 'Sample Linked Entities:');

        try {
            $samples = \Illuminate\Database\Capsule\Manager::table('heritage_entity_graph_node')
                ->where(function ($q) {
                    $q->whereNotNull('viaf_id')->orWhereNotNull('wikidata_id');
                })
                ->orderBy('occurrence_count', 'desc')
                ->limit(10)
                ->get();

            foreach ($samples as $sample) {
                $ids = [];
                if ($sample->viaf_id) {
                    $ids[] = "VIAF:{$sample->viaf_id}";
                }
                if ($sample->wikidata_id) {
                    $ids[] = "WD:{$sample->wikidata_id}";
                }

                $this->logSection('linked-data', sprintf(
                    '  [%s] %s — %s (occ: %d)',
                    $sample->entity_type,
                    $sample->canonical_value,
                    implode(', ', $ids),
                    $sample->occurrence_count
                ));
            }
        } catch (\Exception $e) {
            // No samples available
        }
    }
}
