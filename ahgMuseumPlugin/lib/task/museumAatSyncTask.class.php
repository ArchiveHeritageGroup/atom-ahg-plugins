<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Sync Getty AAT vocabulary terms to local cache table.
 *
 * Downloads AAT terms via SPARQL and stores them in getty_aat_cache
 * for instant autocomplete instead of live Getty API queries.
 */
class museumAatSyncTask extends sfBaseTask
{
    /** SPARQL endpoint */
    private const ENDPOINT = 'http://vocab.getty.edu/sparql';

    /** AAT hierarchy root IDs by category */
    private const HIERARCHY_ROOTS = [
        'object_types' => [
            '300264092', // Objects Facet
            '300191086', // Visual Works
            '300037335', // Furnishings and Equipment
            '300026059', // Information Forms
        ],
        'materials' => [
            '300010358', // Materials (Hierarchy Name)
        ],
        'techniques' => [
            '300053001', // Processes and Techniques
        ],
        'styles_periods' => [
            '300264088', // Styles and Periods Facet
        ],
    ];

    /** Rate limit delay between SPARQL requests (microseconds) */
    private const RATE_LIMIT = 500000; // 0.5 seconds

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('category', null, sfCommandOption::PARAMETER_OPTIONAL, 'Category to sync: object_types, materials, techniques, styles_periods, all', 'all'),
            new sfCommandOption('depth', null, sfCommandOption::PARAMETER_OPTIONAL, 'Hierarchy depth to traverse (1-3)', '2'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be synced without writing'),
            new sfCommandOption('clear', null, sfCommandOption::PARAMETER_NONE, 'Clear existing cache before sync'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show cache statistics only'),
        ]);

        $this->namespace = 'museum';
        $this->name = 'aat-sync';
        $this->briefDescription = 'Sync Getty AAT vocabulary terms to local cache for fast autocomplete';
        $this->detailedDescription = <<<EOF
The [museum:aat-sync|INFO] task downloads Getty AAT terms and stores them locally
in the getty_aat_cache table for instant autocomplete search.

Examples:
  [php symfony museum:aat-sync|INFO]
    Sync all categories (object_types, materials, techniques, styles_periods)

  [php symfony museum:aat-sync --category=object_types|INFO]
    Sync only object types

  [php symfony museum:aat-sync --category=object_types --depth=3|INFO]
    Sync object types with deeper hierarchy traversal

  [php symfony museum:aat-sync --clear|INFO]
    Clear cache and re-sync all categories

  [php symfony museum:aat-sync --stats|INFO]
    Show current cache statistics
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $this->logSection('aat-sync', 'Getty AAT Local Cache Sync');
        $this->logSection('aat-sync', str_repeat('=', 40));

        // Ensure table exists
        $this->ensureTable();

        // Stats only mode
        if ($options['stats'] ?? false) {
            $this->showStats();
            return;
        }

        $category = $options['category'] ?? 'all';
        $depth = max(1, min(3, (int) ($options['depth'] ?? 2)));
        $dryRun = $options['dry-run'] ?? false;

        if ($dryRun) {
            $this->logSection('aat-sync', '*** DRY RUN — no data will be written ***');
        }

        // Clear if requested
        if ($options['clear'] ?? false) {
            if (!$dryRun) {
                if ($category === 'all') {
                    $deleted = DB::table('getty_aat_cache')->delete();
                } else {
                    $deleted = DB::table('getty_aat_cache')->where('category', $category)->delete();
                }
                $this->logSection('aat-sync', sprintf('Cleared %d cached terms', $deleted));
            }
        }

        $categories = $category === 'all' ? array_keys(self::HIERARCHY_ROOTS) : [$category];

        if (!isset(self::HIERARCHY_ROOTS[$categories[0] ?? '']) && $category !== 'all') {
            $this->logSection('error', sprintf('Unknown category: %s', $category));
            $this->logSection('info', 'Valid categories: object_types, materials, techniques, styles_periods, all');
            return;
        }

        $totalInserted = 0;
        $totalSkipped = 0;

        foreach ($categories as $cat) {
            $this->logSection('aat-sync', '');
            $this->logSection('aat-sync', sprintf('Syncing category: %s (depth: %d)', $cat, $depth));
            $this->logSection('aat-sync', str_repeat('-', 40));

            $roots = self::HIERARCHY_ROOTS[$cat];

            foreach ($roots as $rootId) {
                $this->logSection('aat-sync', sprintf('  Fetching hierarchy under AAT:%s...', $rootId));
                $terms = $this->fetchHierarchy($rootId, $depth, $cat);

                $this->logSection('aat-sync', sprintf('  Retrieved %d terms from Getty', count($terms)));

                foreach ($terms as $term) {
                    if ($dryRun) {
                        $this->logSection('dry-run', sprintf('    Would cache: %s (AAT:%s)', $term['pref_label'], $term['aat_id']));
                        $totalInserted++;
                        continue;
                    }

                    // Upsert - insert or update
                    $exists = DB::table('getty_aat_cache')
                        ->where('aat_id', $term['aat_id'])
                        ->exists();

                    if ($exists) {
                        DB::table('getty_aat_cache')
                            ->where('aat_id', $term['aat_id'])
                            ->update([
                                'pref_label' => $term['pref_label'],
                                'scope_note' => $term['scope_note'],
                                'broader_label' => $term['broader_label'],
                                'broader_id' => $term['broader_id'],
                                'category' => $cat,
                                'synced_at' => date('Y-m-d H:i:s'),
                            ]);
                        $totalSkipped++;
                    } else {
                        DB::table('getty_aat_cache')->insert([
                            'aat_id' => $term['aat_id'],
                            'uri' => $term['uri'],
                            'pref_label' => $term['pref_label'],
                            'scope_note' => $term['scope_note'],
                            'broader_label' => $term['broader_label'],
                            'broader_id' => $term['broader_id'],
                            'category' => $cat,
                            'synced_at' => date('Y-m-d H:i:s'),
                        ]);
                        $totalInserted++;
                    }
                }
            }
        }

        $this->logSection('aat-sync', '');
        $this->logSection('aat-sync', str_repeat('=', 40));
        $this->logSection('aat-sync', sprintf('New terms cached: %d', $totalInserted));
        $this->logSection('aat-sync', sprintf('Existing updated: %d', $totalSkipped));

        $this->showStats();
    }

    /**
     * Fetch AAT terms for a hierarchy root, traversing to given depth.
     */
    private function fetchHierarchy(string $rootId, int $depth, string $category): array
    {
        $allTerms = [];
        $processed = [];

        // Fetch immediate narrower terms for the root
        $queue = [['id' => $rootId, 'depth' => 0]];

        while (!empty($queue)) {
            $item = array_shift($queue);

            if ($item['depth'] >= $depth) {
                continue;
            }

            if (isset($processed[$item['id']])) {
                continue;
            }
            $processed[$item['id']] = true;

            $terms = $this->fetchNarrowerTerms($item['id']);

            foreach ($terms as $term) {
                $term['category'] = $category;
                $allTerms[$term['aat_id']] = $term;

                // Queue children for next depth level
                if ($item['depth'] + 1 < $depth) {
                    $queue[] = ['id' => $term['aat_id'], 'depth' => $item['depth'] + 1];
                }
            }

            // Rate limit
            usleep(self::RATE_LIMIT);
        }

        return array_values($allTerms);
    }

    /**
     * Fetch narrower (child) terms of a given AAT concept via SPARQL.
     */
    private function fetchNarrowerTerms(string $parentId): array
    {
        $sparql = <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT ?subject ?prefLabel ?scopeNote ?broaderLabel WHERE {
  ?subject gvp:broaderPreferred <http://vocab.getty.edu/aat/{$parentId}> ;
           gvp:prefLabelGVP/xl:literalForm ?prefLabel .
  FILTER(lang(?prefLabel) = "en" || lang(?prefLabel) = "")
  OPTIONAL { ?subject skos:scopeNote/rdf:value ?scopeNote . FILTER(lang(?scopeNote) = "en" || lang(?scopeNote) = "") }
  OPTIONAL {
    ?subject gvp:broaderPreferred ?broader .
    ?broader gvp:prefLabelGVP/xl:literalForm ?broaderLabel .
    FILTER(lang(?broaderLabel) = "en" || lang(?broaderLabel) = "")
  }
}
ORDER BY ?prefLabel
LIMIT 500
SPARQL;

        $url = self::ENDPOINT . '?' . http_build_query([
            'query' => $sparql,
            'format' => 'json',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => "Accept: application/sparql-results+json\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $this->logSection('warning', sprintf('    SPARQL request failed for AAT:%s', $parentId));
            return [];
        }

        $data = json_decode($response, true);

        if (!isset($data['results']['bindings'])) {
            return [];
        }

        $terms = [];
        foreach ($data['results']['bindings'] as $binding) {
            $uri = $binding['subject']['value'] ?? '';
            $aatId = basename($uri);

            if (empty($aatId) || !is_numeric($aatId)) {
                continue;
            }

            $terms[] = [
                'aat_id' => $aatId,
                'uri' => $uri,
                'pref_label' => $binding['prefLabel']['value'] ?? '',
                'scope_note' => isset($binding['scopeNote']['value']) ? mb_substr($binding['scopeNote']['value'], 0, 65535) : null,
                'broader_label' => $binding['broaderLabel']['value'] ?? null,
                'broader_id' => $parentId,
            ];
        }

        return $terms;
    }

    /**
     * Ensure the cache table exists.
     */
    private function ensureTable(): void
    {
        $tableExists = DB::select("SHOW TABLES LIKE 'getty_aat_cache'");

        if (empty($tableExists)) {
            $sqlFile = dirname(__DIR__, 2) . '/database/getty_aat_cache.sql';

            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                DB::unprepared($sql);
                $this->logSection('aat-sync', 'Created getty_aat_cache table');
            } else {
                $this->logSection('error', 'Cannot find getty_aat_cache.sql');
                throw new RuntimeException('Missing getty_aat_cache.sql');
            }
        }
    }

    /**
     * Show cache statistics.
     */
    private function showStats(): void
    {
        $this->logSection('aat-sync', '');
        $this->logSection('aat-sync', 'Cache Statistics:');

        $stats = DB::table('getty_aat_cache')
            ->selectRaw('category, COUNT(*) as term_count, MAX(synced_at) as last_sync')
            ->groupBy('category')
            ->get();

        $total = 0;
        foreach ($stats as $row) {
            $this->logSection('aat-sync', sprintf(
                '  %-20s %5d terms  (last sync: %s)',
                $row->category,
                $row->term_count,
                $row->last_sync
            ));
            $total += $row->term_count;
        }

        $this->logSection('aat-sync', sprintf('  %-20s %5d terms', 'TOTAL', $total));
    }
}
