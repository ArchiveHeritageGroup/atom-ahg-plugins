<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * ahgDiscoveryPlugin - Discovery Actions
 *
 * Natural language search across collections using NER entities,
 * synonym expansion, and hierarchical context.
 */
class discoveryActions extends AhgController
{
    /** @var int Cache TTL in seconds (1 hour) */
    private const CACHE_TTL = 3600;

    public function boot(): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgDiscoveryPlugin/lib/Services';

        require_once $pluginDir . '/QueryExpander.php';
        require_once $pluginDir . '/KeywordSearchStrategy.php';
        require_once $pluginDir . '/EntitySearchStrategy.php';
        require_once $pluginDir . '/HierarchicalStrategy.php';
        require_once $pluginDir . '/ResultMerger.php';
        require_once $pluginDir . '/ResultEnricher.php';
    }

    /**
     * Main discovery page.
     */
    public function executeIndex($request)
    {
        $this->query = $request->getParameter('q', '');
        $this->popularTopics = $this->getPopularTopics(8);
    }

    /**
     * AJAX search endpoint — runs the full 4-step pipeline.
     *
     * GET /discovery/search?q=...&page=1&limit=20
     */
    public function executeSearch($request)
    {
        $query = trim($request->getParameter('q', ''));
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = min(50, max(5, (int) $request->getParameter('limit', 20)));

        if (empty($query)) {
            return $this->renderJson([
                'success' => true,
                'total' => 0,
                'collections' => [],
                'results' => [],
                'expanded' => null,
            ]);
        }

        $culture = $this->culture();
        $startTime = microtime(true);

        // Check cache first
        $cacheKey = md5($query . '|' . $culture . '|' . $page . '|' . $limit);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            $this->logSearch($query, $cached['expanded'] ?? null, $cached['total'] ?? 0, $startTime);

            return $this->renderJson($cached);
        }

        // Step 1: Query Expansion
        $expander = new \AhgDiscovery\Services\QueryExpander();
        $expanded = $expander->expand($query);

        // Step 2: Three-strategy search (parallel in concept, sequential in PHP)
        $keywordSearch = new \AhgDiscovery\Services\KeywordSearchStrategy($culture);
        $entitySearch = new \AhgDiscovery\Services\EntitySearchStrategy();
        $hierarchicalSearch = new \AhgDiscovery\Services\HierarchicalStrategy();

        $keywordResults = $keywordSearch->search($expanded, 100);
        $entityResults = $entitySearch->search($expanded, 200);

        // Hierarchical walk on top results from keyword + entity
        $topResults = array_merge(
            array_slice($keywordResults, 0, 10),
            array_slice($entityResults, 0, 10)
        );
        $allFoundIds = array_unique(array_merge(
            array_column($keywordResults, 'object_id'),
            array_column($entityResults, 'object_id')
        ));
        $hierarchicalResults = $hierarchicalSearch->search($topResults, $allFoundIds, 20);

        // Step 3: Merge & Rank
        $merger = new \AhgDiscovery\Services\ResultMerger();
        $merged = $merger->merge($keywordResults, $entityResults, $hierarchicalResults);

        // Step 4: Enrich with metadata (paginated slice)
        $enricher = new \AhgDiscovery\Services\ResultEnricher($culture);
        $flatResults = $merged['flat_results'] ?? [];
        $totalResults = count($flatResults);

        // Paginate
        $offset = ($page - 1) * $limit;
        $pageResults = array_slice($flatResults, $offset, $limit);
        $enrichedResults = $enricher->enrich($pageResults, $limit);

        // Re-group enriched results by fonds for display
        $collections = $this->groupEnrichedByFonds($enrichedResults);

        $response = [
            'success' => true,
            'total' => $totalResults,
            'page' => $page,
            'limit' => $limit,
            'pages' => max(1, (int) ceil($totalResults / $limit)),
            'collections' => $collections,
            'results' => $enrichedResults,
            'expanded' => [
                'keywords' => $expanded['keywords'],
                'phrases' => $expanded['phrases'],
                'synonyms' => $expanded['synonyms'],
                'dateRange' => $expanded['dateRange'],
                'entityTerms' => array_column($expanded['entityTerms'], 'value'),
            ],
        ];

        // Cache the response
        $this->putInCache($cacheKey, $query, $response);

        // Log the search
        $this->logSearch($query, $expanded, $totalResults, $startTime);

        return $this->renderJson($response);
    }

    /**
     * AJAX related content endpoint — finds records sharing entities with a given record.
     *
     * GET /discovery/related/:id
     */
    public function executeRelated($request)
    {
        $objectId = (int) $request->getParameter('id', 0);
        if ($objectId <= 0) {
            return $this->renderJsonError('Invalid object ID', 400);
        }

        $culture = $this->culture();
        $limit = min(20, max(1, (int) $request->getParameter('limit', 8)));

        $entitySearch = new \AhgDiscovery\Services\EntitySearchStrategy();
        $related = $entitySearch->findRelated($objectId, $limit);

        if (empty($related)) {
            return $this->renderJson([
                'success' => true,
                'results' => [],
            ]);
        }

        // Enrich the related results
        $enricher = new \AhgDiscovery\Services\ResultEnricher($culture);
        $enriched = $enricher->enrich(
            array_map(fn($r) => ['object_id' => $r['object_id'], 'score' => $r['match_count'], 'match_reasons' => ['ENTITY']], $related),
            $limit
        );

        // Attach shared entities info
        $relatedMap = [];
        foreach ($related as $r) {
            $relatedMap[$r['object_id']] = $r['shared_entities'] ?? [];
        }
        foreach ($enriched as &$item) {
            $item['shared_entities'] = $relatedMap[$item['object_id']] ?? [];
        }

        return $this->renderJson([
            'success' => true,
            'results' => $enriched,
        ]);
    }

    /**
     * AJAX click tracking endpoint.
     *
     * POST /discovery/click  {query, object_id, session_id}
     */
    public function executeClick($request)
    {
        if (!$request->isMethod('POST') && !$request->isMethod('post')) {
            return $this->renderJsonError('POST required', 405);
        }

        $query = trim($request->getParameter('query', ''));
        $objectId = (int) $request->getParameter('object_id', 0);
        $sessionId = $request->getParameter('session_id', '');

        if (empty($query) || $objectId <= 0) {
            return $this->renderJsonError('Missing query or object_id', 400);
        }

        try {
            DB::table('ahg_discovery_log')
                ->where('query_text', $query)
                ->where('session_id', $sessionId)
                ->whereNull('clicked_object')
                ->orderByDesc('created_at')
                ->limit(1)
                ->update(['clicked_object' => $objectId]);
        } catch (\Exception $e) {
            // Table may not exist yet — degrade gracefully
        }

        return $this->renderJson(['success' => true]);
    }

    /**
     * AJAX popular topics endpoint.
     *
     * GET /discovery/popular?limit=8
     */
    public function executePopular($request)
    {
        $limit = min(20, max(1, (int) $request->getParameter('limit', 8)));
        $topics = $this->getPopularTopics($limit);

        return $this->renderJson([
            'success' => true,
            'topics' => $topics,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Get popular search topics from the discovery log.
     */
    private function getPopularTopics(int $limit): array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_discovery_log'");
            if (empty($exists)) {
                return [];
            }

            return DB::table('ahg_discovery_log')
                ->select(
                    'query_text',
                    DB::raw('COUNT(*) as search_count'),
                    DB::raw('AVG(result_count) as avg_results')
                )
                ->where('created_at', '>=', DB::raw("DATE_SUB(NOW(), INTERVAL 30 DAY)"))
                ->groupBy('query_text')
                ->having('search_count', '>=', 2)
                ->orderByDesc('search_count')
                ->limit($limit)
                ->get()
                ->map(fn($row) => [
                    'query' => $row->query_text,
                    'count' => (int) $row->search_count,
                    'avg_results' => (int) $row->avg_results,
                ])
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Group enriched results by their root fonds.
     */
    private function groupEnrichedByFonds(array $results): array
    {
        $groups = [];

        foreach ($results as $result) {
            $fonds = \AhgDiscovery\Services\HierarchicalStrategy::findRootFonds($result['object_id']);
            $fondsKey = $fonds ? $fonds['id'] : 0;

            if (!isset($groups[$fondsKey])) {
                $groups[$fondsKey] = [
                    'fonds_id' => $fonds['id'] ?? 0,
                    'fonds_title' => $fonds['title'] ?? 'Ungrouped',
                    'fonds_slug' => $fonds['slug'] ?? '',
                    'records' => [],
                ];
            }

            $groups[$fondsKey]['records'][] = $result;
        }

        return array_values($groups);
    }

    /**
     * Retrieve cached search result.
     */
    private function getFromCache(string $hash): ?array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_discovery_cache'");
            if (empty($exists)) {
                return null;
            }

            $row = DB::table('ahg_discovery_cache')
                ->where('query_hash', $hash)
                ->where('expires_at', '>', DB::raw('NOW()'))
                ->first();

            if ($row) {
                return json_decode($row->result_json, true);
            }
        } catch (\Exception $e) {
            // Ignore cache errors
        }

        return null;
    }

    /**
     * Store search result in cache.
     */
    private function putInCache(string $hash, string $query, array $response): void
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_discovery_cache'");
            if (empty($exists)) {
                return;
            }

            DB::table('ahg_discovery_cache')->updateOrInsert(
                ['query_hash' => $hash],
                [
                    'query_text' => mb_substr($query, 0, 500),
                    'result_json' => json_encode($response),
                    'result_count' => $response['total'] ?? 0,
                    'created_at' => DB::raw('NOW()'),
                    'expires_at' => DB::raw('DATE_ADD(NOW(), INTERVAL ' . self::CACHE_TTL . ' SECOND)'),
                ]
            );
        } catch (\Exception $e) {
            // Ignore cache errors
        }
    }

    /**
     * Log a search query for analytics.
     */
    private function logSearch(string $query, ?array $expanded, int $resultCount, float $startTime): void
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_discovery_log'");
            if (empty($exists)) {
                return;
            }

            $responseMs = (int) ((microtime(true) - $startTime) * 1000);

            DB::table('ahg_discovery_log')->insert([
                'user_id' => $this->userId(),
                'query_text' => mb_substr($query, 0, 500),
                'expanded_terms' => $expanded ? json_encode([
                    'keywords' => $expanded['keywords'] ?? [],
                    'synonyms' => $expanded['synonyms'] ?? [],
                    'entityTerms' => array_column($expanded['entityTerms'] ?? [], 'value'),
                ]) : null,
                'result_count' => $resultCount,
                'response_ms' => $responseMs,
                'session_id' => session_id() ?: null,
                'created_at' => DB::raw('NOW()'),
            ]);
        } catch (\Exception $e) {
            // Ignore log errors
        }
    }
}
