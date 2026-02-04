<?php

namespace AhgFederation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use ahgCorePlugin\Services\AhgTaxonomyService;

/**
 * Federated Search Service
 *
 * Enables real-time search across multiple Heritage Platform instances.
 * Handles query distribution, result merging, timeout handling, and caching.
 *
 * Status values are stored in ahg_dropdown table - use AhgTaxonomyService
 * for dropdown values in UI forms.
 */
class FederatedSearchService
{
    // Status constants matching ahg_dropdown federation_search_status codes
    public const STATUS_SUCCESS = 'success';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_ERROR = 'error';

    protected int $defaultTimeout = 5000; // 5 seconds
    protected int $cacheMinutes = 15;
    protected int $maxResultsPerPeer = 50;

    /**
     * Execute a federated search across all active peers
     *
     * @param string $query Search query
     * @param array $options Search options (filters, sort, limit, etc.)
     * @return FederatedSearchResult
     */
    public function search(string $query, array $options = []): FederatedSearchResult
    {
        \AhgCore\Core\AhgDb::init();

        $startTime = microtime(true);
        $queryHash = hash('sha256', $query . json_encode($options));

        // Get active search peers
        $peers = $this->getSearchPeers();

        if ($peers->isEmpty()) {
            return new FederatedSearchResult(
                query: $query,
                queryHash: $queryHash,
                results: [],
                peerStats: [],
                totalResults: 0,
                duration: 0,
                fromCache: false
            );
        }

        // Check cache first
        $useCache = $options['cache'] ?? true;
        if ($useCache) {
            $cachedResults = $this->getCachedResults($queryHash, $peers->pluck('peer_id')->toArray());
            if (!empty($cachedResults)) {
                $totalTime = (microtime(true) - $startTime) * 1000;
                return new FederatedSearchResult(
                    query: $query,
                    queryHash: $queryHash,
                    results: $cachedResults['results'],
                    peerStats: $cachedResults['peerStats'],
                    totalResults: count($cachedResults['results']),
                    duration: $totalTime,
                    fromCache: true
                );
            }
        }

        // Execute parallel searches
        $peerResults = $this->executeParallelSearches($peers, $query, $options);

        // Merge and rank results
        $mergedResults = $this->mergeResults($peerResults, $options);

        // Calculate statistics
        $peerStats = $this->calculatePeerStats($peerResults);

        // Cache results
        if ($useCache) {
            $this->cacheResults($queryHash, $peerResults);
        }

        // Log the search
        $this->logSearch($query, $queryHash, $peerStats, count($mergedResults), microtime(true) - $startTime);

        $totalTime = (microtime(true) - $startTime) * 1000;

        return new FederatedSearchResult(
            query: $query,
            queryHash: $queryHash,
            results: $mergedResults,
            peerStats: $peerStats,
            totalResults: count($mergedResults),
            duration: $totalTime,
            fromCache: false
        );
    }

    /**
     * Search a single peer
     *
     * @param int $peerId Peer ID
     * @param string $query Search query
     * @param array $options Search options
     * @return array
     */
    public function searchPeer(int $peerId, string $query, array $options = []): array
    {
        \AhgCore\Core\AhgDb::init();

        $peer = DB::table('federation_peer as p')
            ->leftJoin('federation_peer_search as ps', 'p.id', '=', 'ps.peer_id')
            ->where('p.id', $peerId)
            ->where('p.is_active', 1)
            ->select('p.*', 'ps.*', 'p.id as peer_id')
            ->first();

        if (!$peer) {
            return ['success' => false, 'error' => 'Peer not found or inactive'];
        }

        $startTime = microtime(true);
        $result = $this->executePeerSearch($peer, $query, $options);
        $result['duration'] = (microtime(true) - $startTime) * 1000;

        return $result;
    }

    /**
     * Get peers configured for federated search
     */
    protected function getSearchPeers(): Collection
    {
        return DB::table('federation_peer as p')
            ->leftJoin('federation_peer_search as ps', 'p.id', '=', 'ps.peer_id')
            ->where('p.is_active', 1)
            ->where(function ($q) {
                $q->whereNull('ps.search_enabled')
                    ->orWhere('ps.search_enabled', 1);
            })
            ->select(
                'p.id as peer_id',
                'p.name as peer_name',
                'p.base_url',
                'p.api_key',
                DB::raw('COALESCE(ps.search_api_url, CONCAT(p.base_url, "/api/search")) as search_url'),
                DB::raw('COALESCE(ps.search_api_key, p.api_key) as search_api_key'),
                DB::raw('COALESCE(ps.search_timeout_ms, ' . $this->defaultTimeout . ') as timeout_ms'),
                DB::raw('COALESCE(ps.search_max_results, ' . $this->maxResultsPerPeer . ') as max_results'),
                DB::raw('COALESCE(ps.search_priority, 100) as priority')
            )
            ->orderBy('priority', 'asc')
            ->get();
    }

    /**
     * Execute parallel searches across all peers
     */
    protected function executeParallelSearches(Collection $peers, string $query, array $options): array
    {
        $results = [];
        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($peers as $peer) {
            $handle = $this->createSearchRequest($peer, $query, $options);
            curl_multi_add_handle($multiHandle, $handle);
            $handles[$peer->peer_id] = [
                'handle' => $handle,
                'peer' => $peer,
                'startTime' => microtime(true),
            ];
        }

        // Execute all requests
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        // Collect results
        foreach ($handles as $peerId => $data) {
            $handle = $data['handle'];
            $peer = $data['peer'];
            $duration = (microtime(true) - $data['startTime']) * 1000;

            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $error = curl_error($handle);
            $response = curl_multi_getcontent($handle);

            curl_multi_remove_handle($multiHandle, $handle);
            curl_close($handle);

            $results[$peerId] = $this->processPeerResponse(
                $peer,
                $response,
                $httpCode,
                $error,
                $duration
            );
        }

        curl_multi_close($multiHandle);

        // Update peer statistics
        $this->updatePeerStats($results);

        return $results;
    }

    /**
     * Create a cURL handle for a peer search request
     */
    protected function createSearchRequest(object $peer, string $query, array $options): \CurlHandle
    {
        $params = [
            'q' => $query,
            'limit' => $peer->max_results,
            'format' => 'json',
        ];

        // Add optional filters
        if (!empty($options['type'])) {
            $params['type'] = $options['type'];
        }
        if (!empty($options['repository'])) {
            $params['repository'] = $options['repository'];
        }
        if (!empty($options['dateFrom'])) {
            $params['dateFrom'] = $options['dateFrom'];
        }
        if (!empty($options['dateTo'])) {
            $params['dateTo'] = $options['dateTo'];
        }

        $url = $peer->search_url . '?' . http_build_query($params);

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $peer->timeout_ms,
            CURLOPT_CONNECTTIMEOUT_MS => min(2000, $peer->timeout_ms),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: AtoM-Heritage-Federation/1.0',
            ],
        ]);

        // Add API key if configured
        if (!empty($peer->search_api_key)) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, array_merge(
                curl_getopt($handle, CURLOPT_HTTPHEADER) ?: [],
                ['X-API-Key: ' . $peer->search_api_key]
            ));
        }

        return $handle;
    }

    /**
     * Process response from a peer
     */
    protected function processPeerResponse(
        object $peer,
        ?string $response,
        int $httpCode,
        string $error,
        float $duration
    ): array {
        $result = [
            'peerId' => $peer->peer_id,
            'peerName' => $peer->peer_name,
            'peerUrl' => $peer->base_url,
            'duration' => $duration,
            'status' => 'error',
            'results' => [],
            'totalCount' => 0,
            'error' => null,
        ];

        if (!empty($error)) {
            $result['error'] = $error;
            $result['status'] = strpos($error, 'timed out') !== false ? 'timeout' : 'error';
            return $result;
        }

        if ($httpCode !== 200) {
            $result['error'] = "HTTP $httpCode";
            return $result;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['error'] = 'Invalid JSON response';
            return $result;
        }

        // Parse results - support multiple response formats
        $items = $data['results'] ?? $data['items'] ?? $data['records'] ?? [];
        $totalCount = $data['total'] ?? $data['totalCount'] ?? count($items);

        // Transform results to common format
        $results = [];
        foreach ($items as $item) {
            $results[] = $this->transformSearchResult($item, $peer);
        }

        $result['status'] = 'success';
        $result['results'] = $results;
        $result['totalCount'] = $totalCount;

        return $result;
    }

    /**
     * Transform a search result to common format with source attribution
     */
    protected function transformSearchResult(array $item, object $peer): array
    {
        return [
            // Core fields
            'id' => $item['id'] ?? $item['identifier'] ?? null,
            'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
            'description' => $item['description'] ?? $item['scopeAndContent'] ?? null,
            'identifier' => $item['referenceCode'] ?? $item['identifier'] ?? null,
            'level' => $item['levelOfDescription'] ?? $item['level'] ?? null,
            'date' => $item['date'] ?? $item['dateDisplay'] ?? null,
            'type' => $item['type'] ?? $item['objectType'] ?? null,
            'thumbnailUrl' => $item['thumbnailUrl'] ?? $item['thumbnail'] ?? null,

            // Source attribution
            'source' => [
                'peerId' => $peer->peer_id,
                'peerName' => $peer->peer_name,
                'peerUrl' => $peer->base_url,
                'originalUrl' => $item['url'] ?? $item['permalink'] ?? null,
                'originalId' => $item['id'] ?? null,
            ],

            // Relevance scoring (if provided)
            'score' => $item['score'] ?? $item['relevance'] ?? 1.0,

            // Original data for reference
            '_original' => $item,
        ];
    }

    /**
     * Merge results from multiple peers with ranking
     */
    protected function mergeResults(array $peerResults, array $options): array
    {
        $allResults = [];

        foreach ($peerResults as $peerResult) {
            if ($peerResult['status'] !== 'success') {
                continue;
            }

            foreach ($peerResult['results'] as $result) {
                $allResults[] = $result;
            }
        }

        // Sort by relevance score and peer priority
        usort($allResults, function ($a, $b) use ($peerResults) {
            // Primary: relevance score (higher is better)
            $scoreA = $a['score'] ?? 1.0;
            $scoreB = $b['score'] ?? 1.0;

            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            // Secondary: peer priority (lower is better)
            $priorityA = $this->getPeerPriority($peerResults, $a['source']['peerId']);
            $priorityB = $this->getPeerPriority($peerResults, $b['source']['peerId']);

            return $priorityA <=> $priorityB;
        });

        // Apply limit
        $limit = $options['limit'] ?? 100;
        return array_slice($allResults, 0, $limit);
    }

    /**
     * Get peer priority from results
     */
    protected function getPeerPriority(array $peerResults, int $peerId): int
    {
        return $peerResults[$peerId]['priority'] ?? 100;
    }

    /**
     * Calculate peer statistics
     */
    protected function calculatePeerStats(array $peerResults): array
    {
        $stats = [
            'queried' => count($peerResults),
            'responded' => 0,
            'timeout' => 0,
            'error' => 0,
            'peers' => [],
        ];

        foreach ($peerResults as $result) {
            $stats['peers'][] = [
                'id' => $result['peerId'],
                'name' => $result['peerName'],
                'status' => $result['status'],
                'resultCount' => count($result['results']),
                'totalCount' => $result['totalCount'],
                'duration' => $result['duration'],
                'error' => $result['error'],
            ];

            switch ($result['status']) {
                case 'success':
                    $stats['responded']++;
                    break;
                case 'timeout':
                    $stats['timeout']++;
                    break;
                default:
                    $stats['error']++;
            }
        }

        return $stats;
    }

    /**
     * Update peer search statistics
     */
    protected function updatePeerStats(array $results): void
    {
        foreach ($results as $peerId => $result) {
            DB::table('federation_peer_search')
                ->updateOrInsert(
                    ['peer_id' => $peerId],
                    [
                        'last_search_at' => date('Y-m-d H:i:s'),
                        'last_search_status' => $result['status'],
                        'avg_response_time_ms' => DB::raw(
                            'CASE WHEN avg_response_time_ms = 0 THEN ' . (int)$result['duration'] .
                            ' ELSE (avg_response_time_ms + ' . (int)$result['duration'] . ') / 2 END'
                        ),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
        }
    }

    /**
     * Get cached results
     */
    protected function getCachedResults(string $queryHash, array $peerIds): ?array
    {
        $cached = DB::table('federation_search_cache')
            ->where('query_hash', $queryHash)
            ->whereIn('peer_id', $peerIds)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->get();

        if ($cached->isEmpty()) {
            return null;
        }

        $results = [];
        $peerStats = [
            'queried' => count($peerIds),
            'responded' => $cached->count(),
            'timeout' => 0,
            'error' => count($peerIds) - $cached->count(),
            'peers' => [],
        ];

        foreach ($cached as $cache) {
            $data = json_decode($cache->results_json, true);
            if (is_array($data)) {
                $results = array_merge($results, $data);
            }
        }

        return [
            'results' => $results,
            'peerStats' => $peerStats,
        ];
    }

    /**
     * Cache search results
     */
    protected function cacheResults(string $queryHash, array $peerResults): void
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->cacheMinutes} minutes"));

        foreach ($peerResults as $peerId => $result) {
            if ($result['status'] !== 'success') {
                continue;
            }

            DB::table('federation_search_cache')
                ->updateOrInsert(
                    ['query_hash' => $queryHash, 'peer_id' => $peerId],
                    [
                        'results_json' => json_encode($result['results']),
                        'result_count' => count($result['results']),
                        'created_at' => date('Y-m-d H:i:s'),
                        'expires_at' => $expiresAt,
                    ]
                );
        }
    }

    /**
     * Log search for analytics
     */
    protected function logSearch(
        string $query,
        string $queryHash,
        array $peerStats,
        int $totalResults,
        float $duration
    ): void {
        try {
            $userId = null;
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $user = \sfContext::getInstance()->getUser();
                if ($user && $user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                }
            }

            DB::table('federation_search_log')->insert([
                'query_text' => substr($query, 0, 500),
                'query_hash' => $queryHash,
                'user_id' => $userId,
                'peers_queried' => $peerStats['queried'],
                'peers_responded' => $peerStats['responded'],
                'peers_timeout' => $peerStats['timeout'],
                'peers_error' => $peerStats['error'],
                'total_results' => $totalResults,
                'total_time_ms' => (int)($duration * 1000),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Don't fail search due to logging error
            error_log('Federation search log failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache(): int
    {
        return DB::table('federation_search_cache')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Clear all cache for a query
     */
    public function clearQueryCache(string $query): int
    {
        $queryHash = hash('sha256', $query);
        return DB::table('federation_search_cache')
            ->where('query_hash', $queryHash)
            ->delete();
    }

    /**
     * Configure search settings for a peer
     */
    public function configurePeerSearch(int $peerId, array $settings): bool
    {
        $data = [
            'peer_id' => $peerId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $allowedFields = [
            'search_api_url', 'search_api_key', 'search_enabled',
            'search_timeout_ms', 'search_max_results', 'search_priority',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $settings)) {
                $data[$field] = $settings[$field];
            }
        }

        return DB::table('federation_peer_search')
            ->updateOrInsert(['peer_id' => $peerId], $data);
    }

    /**
     * Get dropdown choices for search status
     * Uses AhgTaxonomyService to get values from ahg_dropdown
     */
    public static function getSearchStatusChoices(bool $includeEmpty = true): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationSearchStatuses($includeEmpty);
    }

    /**
     * Get search status with display attributes (label, color)
     */
    public static function getSearchStatusWithAttributes(): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationSearchStatusesWithColors();
    }
}

/**
 * Result of a federated search
 */
class FederatedSearchResult
{
    public function __construct(
        public readonly string $query,
        public readonly string $queryHash,
        public readonly array $results,
        public readonly array $peerStats,
        public readonly int $totalResults,
        public readonly float $duration,
        public readonly bool $fromCache
    ) {}

    /**
     * Get results grouped by source peer
     */
    public function getResultsByPeer(): array
    {
        $grouped = [];
        foreach ($this->results as $result) {
            $peerId = $result['source']['peerId'];
            if (!isset($grouped[$peerId])) {
                $grouped[$peerId] = [
                    'peer' => $result['source'],
                    'results' => [],
                ];
            }
            $grouped[$peerId]['results'][] = $result;
        }
        return $grouped;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'totalResults' => $this->totalResults,
            'duration' => $this->duration,
            'fromCache' => $this->fromCache,
            'peerStats' => $this->peerStats,
            'results' => $this->results,
        ];
    }

    /**
     * Convert to JSON API response
     */
    public function toJsonResponse(): array
    {
        return [
            'success' => true,
            'data' => [
                'query' => $this->query,
                'total' => $this->totalResults,
                'duration_ms' => round($this->duration, 2),
                'cached' => $this->fromCache,
                'peers' => $this->peerStats,
                'results' => array_map(function ($r) {
                    // Remove internal fields for API response
                    unset($r['_original']);
                    return $r;
                }, $this->results),
            ],
        ];
    }
}
