<?php

/**
 * CrossFondsQueryService - fan out a single query across N fonds in parallel.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §1.6
 *
 * Fan-out: one ES/OpenSearch query per selected fonds, scoped by lft/rgt range
 * (lft >= fonds.lft AND rgt <= fonds.rgt). Top-K=10 per fonds, merged by _score,
 * final top-K=30.
 *
 * Optional thesaurus expansion via SemanticSearchService (if enabled).
 *
 * Uses arOpenSearchPlugin's REST endpoint directly (the AtoM instance is
 * OpenSearch-backed; HTTP POST to /search keeps the dependency surface small).
 */

use Illuminate\Database\Capsule\Manager as DB;

class CrossFondsQueryService
{
    /** @var string Index name (the AtoM convention is "<short>_qubitinformationobject"). */
    protected $indexName;

    /** @var string OpenSearch base URL */
    protected $baseUrl;

    public function __construct()
    {
        // Read the search.yml or fall back to host/port defaults
        $this->baseUrl   = (string) (sfConfig::get('app_search_host_url') ?: 'http://localhost:9200');
        $this->indexName = $this->resolveIndexName();
    }

    protected function resolveIndexName(): string
    {
        // Convention: $app_search_index_prefix . '_qubitinformationobject'
        $prefix = sfConfig::get('app_search_index_prefix') ?: 'archive';
        return $prefix . '_qubitinformationobject';
    }

    /**
     * List fonds/collections available for the picker, capped to $limit.
     */
    public function availableFonds(int $limit = 200): array
    {
        // Level-of-description term names that count as a fonds/collection.
        $culture = $this->culture();

        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('term as t', 'io.level_of_description_id', '=', 't.id')
            ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->whereIn('ti.name', ['Fonds', 'Collection', 'fonds', 'collection'])
            ->select('io.id', 'io.lft', 'io.rgt', 'ioi.title', 'slug.slug', 'ti.name as level')
            ->orderBy('ioi.title')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Execute the cross-fonds query.
     *
     * @return array{results: array, total: int, elapsed_ms: int, expanded_query: ?string}
     */
    public function query(string $query, array $fondsIds, ?int $researcherId = null, array $options = []): array
    {
        $started = microtime(true);
        $perFondsK = $options['per_fonds_k'] ?? 10;
        $finalK    = $options['final_k']     ?? 30;

        $expanded = null;
        $effectiveQuery = $query;
        if (!empty($options['expand']) && class_exists('SemanticSearchService')) {
            try {
                $sem = new \SemanticSearchService();
                if (method_exists($sem, 'expandQuery')) {
                    $expandResult = $sem->expandQuery($query);
                    if (is_array($expandResult) && !empty($expandResult['terms'])) {
                        $terms = array_map(fn ($t) => '"' . $t . '"', (array) $expandResult['terms']);
                        $expanded = $query . ' OR ' . implode(' OR ', $terms);
                        $effectiveQuery = $expanded;
                    }
                }
            } catch (\Throwable $e) {
                // Expansion is optional; fall through
            }
        }

        // Look up fonds lft/rgt ranges
        $fondsRows = DB::table('information_object')
            ->whereIn('id', $fondsIds)
            ->select('id', 'lft', 'rgt')
            ->get()
            ->all();

        $all = [];
        foreach ($fondsRows as $f) {
            $hits = $this->queryFonds($effectiveQuery, (int) $f->lft, (int) $f->rgt, $perFondsK);
            foreach ($hits as $h) {
                $h['fonds_id'] = (int) $f->id;
                $all[] = $h;
            }
        }

        // Merge by score and keep top finalK
        usort($all, fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        $top = array_slice($all, 0, $finalK);

        $elapsed = (int) round((microtime(true) - $started) * 1000);

        // Persist query log
        $logId = null;
        try {
            $logId = DB::table('research_cross_fonds_query')->insertGetId([
                'researcher_id' => $researcherId,
                'query_text'    => mb_substr($query, 0, 1000),
                'fonds_ids'     => json_encode(array_values($fondsIds)),
                'results_count' => count($top),
                'elapsed_ms'    => $elapsed,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // non-fatal
        }

        // Activity log (§2.2)
        if ($researcherId) {
            try {
                DB::table('research_activity_log')->insert([
                    'researcher_id' => $researcherId,
                    'activity_type' => 'search_cross_fonds',
                    'entity_type'   => 'cross_fonds_query',
                    'entity_id'     => $logId,
                    'entity_title'  => mb_substr($query, 0, 500),
                    'details'       => json_encode(['fonds' => $fondsIds, 'results' => count($top)]),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                // non-fatal
            }
        }

        return [
            'results'        => $top,
            'total'          => count($all),
            'elapsed_ms'     => $elapsed,
            'expanded_query' => $expanded,
        ];
    }

    /**
     * Issue one OpenSearch query scoped to the lft/rgt range of a single fonds.
     */
    protected function queryFonds(string $query, int $lft, int $rgt, int $k): array
    {
        $payload = [
            'size'    => $k,
            '_source' => ['i18n', 'identifier', 'slug', 'level_of_description_id'],
            'query' => [
                'bool' => [
                    'must' => [
                        ['query_string' => ['query' => $query, 'fields' => ['i18n.*.title^3', 'i18n.*.scope_and_content', 'i18n.*.extent_and_medium', 'identifier']]],
                    ],
                    'filter' => [
                        ['range' => ['lft' => ['gte' => $lft]]],
                        ['range' => ['rgt' => ['lte' => $rgt]]],
                    ],
                ],
            ],
        ];

        $url = rtrim($this->baseUrl, '/') . '/' . $this->indexName . '/_search';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300 || !$resp) {
            return [];
        }
        $data = json_decode((string) $resp, true);
        if (!is_array($data) || empty($data['hits']['hits'])) {
            return [];
        }

        $culture = $this->culture();
        $out = [];
        foreach ($data['hits']['hits'] as $hit) {
            $src = $hit['_source'] ?? [];
            $i18n = $src['i18n'][$culture] ?? ($src['i18n']['en'] ?? []);
            $out[] = [
                'object_id' => (int) ($hit['_id'] ?? 0),
                'score'     => (float) ($hit['_score'] ?? 0),
                'title'     => $i18n['title']             ?? 'Untitled',
                'snippet'   => $this->trim((string) ($i18n['scope_and_content'] ?? ''), 280),
                'reference' => $src['identifier']         ?? '',
                'slug'      => $src['slug']               ?? null,
            ];
        }
        return $out;
    }

    protected function trim(string $s, int $max): string
    {
        $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
        return mb_strlen($s) <= $max ? $s : mb_substr($s, 0, $max - 1) . '…';
    }

    protected function culture(): string
    {
        if (class_exists('\\AtomExtensions\\Helpers\\CultureHelper')) {
            return \AtomExtensions\Helpers\CultureHelper::getCulture();
        }
        return class_exists('\\sfContext') ? \sfContext::getInstance()->getUser()->getCulture() : 'en';
    }
}
