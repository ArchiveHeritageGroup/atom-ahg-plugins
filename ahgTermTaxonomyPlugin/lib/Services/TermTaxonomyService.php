<?php

namespace AhgTermTaxonomy\Services;

use Illuminate\Database\Capsule\Manager as DB;

class TermTaxonomyService
{
    protected string $culture;
    protected string $esHost;
    protected int $esPort;
    protected string $ioIndexName;
    protected string $termIndexName;

    // Taxonomy IDs
    protected const PLACE_ID = 42;
    protected const SUBJECT_ID = 35;
    protected const GENRE_ID = 78;

    // Aggregation definitions for term browse (information object facets)
    protected const AGGS = [
        'languages' => ['field' => 'i18n.languages', 'size' => 10],
        'places' => ['field' => 'places.id', 'size' => 10],
        'subjects' => ['field' => 'subjects.id', 'size' => 10],
        'genres' => ['field' => 'genres.id', 'size' => 10],
    ];

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
        $this->esHost = \sfConfig::get('app_opensearch_host', 'localhost');
        $this->esPort = (int) \sfConfig::get('app_opensearch_port', 9200);

        $indexName = \sfConfig::get('app_opensearch_index_name', '');
        if (empty($indexName)) {
            try {
                $dbName = DB::connection()->getDatabaseName();
            } catch (\Exception $e) {
                $dbName = 'archive';
            }
            $indexName = $dbName;
        }
        $this->ioIndexName = $indexName . '_qubitinformationobject';
        $this->termIndexName = $indexName . '_qubitterm';
    }

    // -----------------------------------------------------------------------
    // Term browse (information objects for a given term)
    // -----------------------------------------------------------------------

    /**
     * Browse information objects for a given term.
     */
    public function browse(int $termId, int $taxonomyId, array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 30)));
        $skip = ($page - 1) * $limit;

        $maxResultWindow = (int) \sfConfig::get('app_opensearch_max_result_window', 10000);
        if ($skip + $limit > $maxResultWindow) {
            $skip = max(0, $maxResultWindow - $limit);
            $page = (int) floor($skip / $limit) + 1;
        }

        // Build the main filter: term must match
        $filter = [];
        $mustNot = [];

        // Filter by term in the appropriate field
        $termField = $this->getTermField($taxonomyId);
        $filter[] = ['terms' => [$termField => [$termId]]];

        // Only direct results?
        if (!empty($params['onlyDirect'])) {
            $directField = $this->getDirectField($taxonomyId);
            if ($directField) {
                $filter[] = ['terms' => [$directField => [$termId]]];
            }
        }

        // Facet filters
        $activeFilters = [];
        foreach (self::AGGS as $name => $aggDef) {
            if (!empty($params[$name])) {
                $filter[] = ['term' => [$aggDef['field'] => $params[$name]]];
                $activeFilters[$name] = $params[$name];
            }
        }

        // Draft filter — exclude drafts for non-authenticated users
        $mustNot[] = ['term' => ['publicationStatusId' => \QubitTerm::PUBLICATION_STATUS_DRAFT_ID]];

        // Build bool query
        $boolQuery = ['filter' => $filter];
        if (!empty($mustNot)) {
            $boolQuery['must_not'] = $mustNot;
        }

        // Sort
        $sort = $this->buildIoSort(
            $params['sort'] ?? 'lastUpdated',
            $params['sortDir'] ?? 'desc'
        );

        // Source fields
        $source = [
            'slug',
            'identifier',
            'referenceCode',
            'levelOfDescriptionId',
            'publicationStatusId',
            'hasDigitalObject',
            'digitalObject',
            'i18n',
            'dates',
            'creators',
            'partOf',
            'updatedAt',
        ];

        // Aggregations
        $aggs = $this->buildAggregations();

        // Direct count aggregation (filter agg)
        $directField = $this->getDirectField($taxonomyId);
        if ($directField) {
            $aggs['direct'] = [
                'filter' => ['terms' => [$directField => [$termId]]],
            ];
        }

        $body = [
            'size' => $limit,
            'from' => $skip,
            '_source' => $source,
            'query' => ['bool' => $boolQuery],
            'sort' => $sort,
            'aggs' => $aggs,
        ];

        $response = $this->esRequest($this->ioIndexName, '/_search', $body);

        if (!$response) {
            return [
                'hits' => [],
                'total' => 0,
                'aggs' => [],
                'direct' => 0,
                'page' => $page,
                'limit' => $limit,
                'filters' => $activeFilters,
            ];
        }

        // Extract hits
        $hits = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $doc = $hit['_source'] ?? [];
            $doc['_id'] = $hit['_id'];
            $hits[] = $doc;
        }

        $total = $response['hits']['total']['value'] ?? 0;
        $directCount = $response['aggregations']['direct']['doc_count'] ?? 0;

        // Populate aggregation display names (batch)
        $populatedAggs = $this->populateAggregations($response['aggregations'] ?? []);

        return [
            'hits' => $hits,
            'total' => $total,
            'aggs' => $populatedAggs,
            'direct' => $directCount,
            'page' => $page,
            'limit' => $limit,
            'filters' => $activeFilters,
        ];
    }

    /**
     * Load list of terms in the same taxonomy (for sidebar tree).
     */
    public function loadListTerms(int $taxonomyId, array $params): array
    {
        $c = $this->culture;
        $listLimit = max(1, (int) ($params['listLimit'] ?? \sfConfig::get('app_hits_per_page', 30)));
        $listPage = max(1, (int) ($params['listPage'] ?? 1));
        $skip = ($listPage - 1) * $listLimit;

        $body = [
            'size' => $listLimit,
            'from' => $skip,
            '_source' => ['slug', 'i18n', 'taxonomyId', 'numberOfDescendants'],
            'query' => [
                'term' => ['taxonomyId' => $taxonomyId],
            ],
            'sort' => [
                ["i18n.{$c}.name.alphasort" => ['order' => 'asc', 'unmapped_type' => 'keyword']],
            ],
        ];

        $response = $this->esRequest($this->termIndexName, '/_search', $body);

        if (!$response) {
            return ['hits' => [], 'total' => 0, 'page' => $listPage, 'limit' => $listLimit];
        }

        $hits = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $doc = $hit['_source'] ?? [];
            $doc['_id'] = $hit['_id'];
            $hits[] = $doc;
        }

        return [
            'hits' => $hits,
            'total' => $response['hits']['total']['value'] ?? 0,
            'page' => $listPage,
            'limit' => $listLimit,
        ];
    }

    // -----------------------------------------------------------------------
    // Taxonomy browse (list of terms within a taxonomy)
    // -----------------------------------------------------------------------

    /**
     * Browse terms within a taxonomy (replaces base AtoM's taxonomy/indexAction ES query).
     */
    public function browseTaxonomy(int $taxonomyId, array $params): array
    {
        $c = $this->culture;
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? \sfConfig::get('app_hits_per_page', 30))));
        $skip = ($page - 1) * $limit;

        $maxResultWindow = (int) \sfConfig::get('app_opensearch_max_result_window', 10000);
        if ($skip + $limit > $maxResultWindow) {
            $skip = max(0, $maxResultWindow - $limit);
            $page = (int) floor($skip / $limit) + 1;
        }

        // Build bool query
        $must = [];
        $must[] = ['term' => ['taxonomyId' => $taxonomyId]];

        // Subquery (text search within taxonomy)
        $subquery = trim($params['subquery'] ?? '');
        if ('' !== $subquery) {
            $subqueryField = $params['subqueryField'] ?? 'allLabels';

            switch ($subqueryField) {
                case 'preferredLabel':
                    $fields = ["i18n.{$c}.name^5"];
                    break;

                case 'useForLabels':
                    $fields = ["useFor.i18n.{$c}.name"];
                    break;

                case 'allLabels':
                default:
                    $fields = ["i18n.{$c}.name^5", "useFor.i18n.{$c}.name"];
                    break;
            }

            $must[] = [
                'query_string' => [
                    'query' => $subquery,
                    'fields' => $fields,
                    'default_operator' => 'AND',
                ],
            ];
        }

        // Sort
        $sort = $params['sort'] ?? 'lastUpdated';
        $sortDir = $params['sortDir'] ?? ('lastUpdated' === $sort ? 'desc' : 'asc');

        $esSort = match ($sort) {
            'alphabetic' => [["i18n.{$c}.name.alphasort" => ['order' => $sortDir, 'unmapped_type' => 'keyword']]],
            default => [['updatedAt' => ['order' => $sortDir]]],
        };

        // Source fields — include everything the template needs
        $source = [
            'slug',
            'i18n',
            'taxonomyId',
            'numberOfDescendants',
            'isProtected',
            'useFor',
            'scopeNotes',
            'updatedAt',
        ];

        $body = [
            'size' => $limit,
            'from' => $skip,
            '_source' => $source,
            'query' => ['bool' => ['must' => $must]],
            'sort' => $esSort,
        ];

        $response = $this->esRequest($this->termIndexName, '/_search', $body);

        if (!$response) {
            return [
                'hits' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ];
        }

        $hits = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $doc = $hit['_source'] ?? [];
            $doc['_id'] = $hit['_id'];
            $hits[] = $doc;
        }

        return [
            'hits' => $hits,
            'total' => $response['hits']['total']['value'] ?? 0,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    // -----------------------------------------------------------------------
    // Batch count methods (eliminates N+1 queries)
    // -----------------------------------------------------------------------

    /**
     * Batch count related information objects for multiple term IDs.
     * Single query instead of N individual queries.
     *
     * @return array<int, int> [termId => count]
     */
    public function batchCountRelatedIOs(array $termIds): array
    {
        if (empty($termIds)) {
            return [];
        }

        try {
            $rows = DB::table('object_term_relation as otr')
                ->join('object as o', 'otr.object_id', '=', 'o.id')
                ->whereIn('otr.term_id', $termIds)
                ->where('o.class_name', 'QubitInformationObject')
                ->groupBy('otr.term_id')
                ->select('otr.term_id', DB::raw('COUNT(*) as cnt'))
                ->get();

            $counts = [];
            foreach ($rows as $row) {
                $counts[(int) $row->term_id] = (int) $row->cnt;
            }

            return $counts;
        } catch (\Exception $e) {
            error_log('ahgTermTaxonomyPlugin batchCountRelatedIOs error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Batch count related actors for multiple term IDs.
     * Single query instead of N individual queries.
     *
     * @return array<int, int> [termId => count]
     */
    public function batchCountRelatedActors(array $termIds): array
    {
        if (empty($termIds)) {
            return [];
        }

        try {
            $rows = DB::table('object_term_relation as otr')
                ->join('object as o', 'otr.object_id', '=', 'o.id')
                ->whereIn('otr.term_id', $termIds)
                ->where('o.class_name', 'QubitActor')
                ->groupBy('otr.term_id')
                ->select('otr.term_id', DB::raw('COUNT(*) as cnt'))
                ->get();

            $counts = [];
            foreach ($rows as $row) {
                $counts[(int) $row->term_id] = (int) $row->cnt;
            }

            return $counts;
        } catch (\Exception $e) {
            error_log('ahgTermTaxonomyPlugin batchCountRelatedActors error: ' . $e->getMessage());

            return [];
        }
    }

    // -----------------------------------------------------------------------
    // Helper methods
    // -----------------------------------------------------------------------

    /**
     * Get the ES field name for filtering by term.
     */
    protected function getTermField(int $taxonomyId): string
    {
        return match ($taxonomyId) {
            self::PLACE_ID => 'places.id',
            self::SUBJECT_ID => 'subjects.id',
            self::GENRE_ID => 'genres.id',
            default => 'subjects.id',
        };
    }

    /**
     * Get the ES field name for direct-only filter.
     */
    protected function getDirectField(int $taxonomyId): string
    {
        return match ($taxonomyId) {
            self::PLACE_ID => 'directPlaces',
            self::SUBJECT_ID => 'directSubjects',
            self::GENRE_ID => 'directGenres',
            default => 'directSubjects',
        };
    }

    /**
     * Build ES sort for information object browse.
     */
    protected function buildIoSort(string $sort, string $dir): array
    {
        $c = $this->culture;
        $direction = ('asc' === strtolower($dir)) ? 'asc' : 'desc';

        return match ($sort) {
            'alphabetic' => [["i18n.{$c}.title.alphasort" => ['order' => $direction, 'unmapped_type' => 'keyword']]],
            'referenceCode' => [['referenceCode.untouched' => ['order' => $direction, 'unmapped_type' => 'keyword']]],
            'date' => [['startDateSort' => ['order' => $direction, 'unmapped_type' => 'long']]],
            default => [['updatedAt' => ['order' => $direction]]],
        };
    }

    /**
     * Build aggregation definitions.
     */
    protected function buildAggregations(): array
    {
        $aggs = [];
        foreach (self::AGGS as $name => $def) {
            $aggs[$name] = [
                'terms' => [
                    'field' => $def['field'],
                    'size' => $def['size'],
                ],
            ];
        }

        return $aggs;
    }

    /**
     * Populate aggregations with display names — batch resolve.
     */
    protected function populateAggregations(array $rawAggs): array
    {
        $result = [];
        $allTermIds = [];

        // First pass: collect all term IDs
        foreach (self::AGGS as $name => $def) {
            if (!isset($rawAggs[$name]['buckets'])) {
                $result[$name] = [];
                continue;
            }

            $buckets = $rawAggs[$name]['buckets'];

            if ('languages' === $name) {
                $result[$name] = [];
                foreach ($buckets as $bucket) {
                    $langCode = $bucket['key'] ?? '';
                    $result[$name][] = [
                        'key' => $langCode,
                        'display' => $this->resolveLanguageName($langCode),
                        'doc_count' => $bucket['doc_count'] ?? 0,
                    ];
                }
                continue;
            }

            foreach ($buckets as $bucket) {
                $allTermIds[] = (int) $bucket['key'];
            }

            $result[$name] = $buckets;
        }

        // Batch resolve all term names in one query
        $termNames = [];
        if (!empty($allTermIds)) {
            $termNames = $this->resolveTermNames(array_unique($allTermIds));
        }

        // Second pass: populate display names
        foreach (self::AGGS as $name => $def) {
            if ('languages' === $name) {
                continue;
            }

            $populated = [];
            foreach ($result[$name] as $bucket) {
                $key = $bucket['key'] ?? '';
                $intKey = (int) $key;
                $populated[] = [
                    'key' => $key,
                    'display' => $termNames[$intKey] ?? "#{$key}",
                    'doc_count' => $bucket['doc_count'] ?? 0,
                ];
            }

            $result[$name] = $populated;
        }

        return $result;
    }

    /**
     * Batch resolve term IDs to names.
     */
    public function resolveTermNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        try {
            return DB::table('term_i18n')
                ->whereIn('id', $ids)
                ->where('culture', $this->culture)
                ->pluck('name', 'id')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Resolve language code to name.
     */
    protected function resolveLanguageName(string $code): string
    {
        try {
            if (class_exists('sfCultureInfo')) {
                $ci = \sfCultureInfo::getInstance($this->culture);
                $languages = $ci->getLanguages();
                if (isset($languages[$code])) {
                    return $languages[$code];
                }
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return $code;
    }

    /**
     * Extract i18n field from ES doc.
     */
    public function extractI18nField(array $doc, string $field): string
    {
        $cultures = [$this->culture, 'en', 'fr', 'es'];
        foreach ($cultures as $c) {
            if (!empty($doc['i18n'][$c][$field])) {
                return $doc['i18n'][$c][$field];
            }
        }

        return '';
    }

    /**
     * Direct curl request to Elasticsearch.
     */
    protected function esRequest(string $index, string $endpoint, array $body): ?array
    {
        $url = sprintf('http://%s:%d/%s%s', $this->esHost, $this->esPort, $index, $endpoint);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 !== $httpCode || false === $response) {
            error_log(sprintf(
                'ahgTermTaxonomyPlugin ES error: HTTP %d, URL: %s',
                $httpCode,
                $url
            ));

            return null;
        }

        return json_decode($response, true);
    }
}
