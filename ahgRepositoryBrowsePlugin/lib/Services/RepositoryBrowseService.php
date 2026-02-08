<?php

namespace AhgRepositoryBrowse\Services;

use Illuminate\Database\Capsule\Manager as DB;

class RepositoryBrowseService
{
    protected string $culture;
    protected string $esHost;
    protected int $esPort;
    protected string $indexName;

    // Aggregation definitions (mirrors base AtoM RepositoryBrowseAction::$AGGS)
    protected const AGGS = [
        'languages' => ['field' => 'i18n.languages', 'size' => 10],
        'types' => ['field' => 'types', 'size' => 10],
        'regions' => ['field' => 'contactInformations.i18n.%s.region.untouched', 'size' => 10],
        'geographicSubregions' => ['field' => 'geographicSubregions', 'size' => 10],
        'locality' => ['field' => 'contactInformations.i18n.%s.city.untouched', 'size' => 10],
        'thematicAreas' => ['field' => 'thematicAreas', 'size' => 10],
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
        $this->indexName = $indexName . '_qubitrepository';
    }

    /**
     * Main browse orchestrator.
     */
    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? \sfConfig::get('app_hits_per_page', 30))));
        $skip = ($page - 1) * $limit;

        $maxResultWindow = (int) \sfConfig::get('app_opensearch_max_result_window', 10000);
        if ($skip + $limit > $maxResultWindow) {
            $skip = max(0, $maxResultWindow - $limit);
            $page = (int) floor($skip / $limit) + 1;
        }

        $c = $this->culture;

        // Build ES query
        $must = [];
        $filter = [];

        // Text search
        $subquery = trim($params['subquery'] ?? '');
        if ('' !== $subquery) {
            $must[] = [
                'query_string' => [
                    'query' => $subquery,
                    'fields' => [
                        "i18n.{$c}.authorizedFormOfName^5",
                        "i18n.{$c}.history",
                        "i18n.{$c}.geoculturalContext",
                        "i18n.{$c}.mandates",
                        "i18n.{$c}.internalStructures",
                        "i18n.{$c}.collectingPolicies",
                        "i18n.{$c}.findingAids",
                        "contactInformations.i18n.{$c}.city",
                        "contactInformations.i18n.{$c}.region",
                    ],
                    'default_operator' => 'AND',
                ],
            ];
        }

        // Facet filters
        $activeFilters = [];
        foreach ($this->getAggDefs() as $name => $aggDef) {
            if (!empty($params[$name])) {
                $filter[] = ['term' => [$aggDef['field'] => $params[$name]]];
                $activeFilters[$name] = $params[$name];
            }
        }

        // Build bool query
        $boolQuery = [];
        if (!empty($must)) {
            $boolQuery['must'] = $must;
        } else {
            $boolQuery['must'] = [['match_all' => new \stdClass()]];
        }
        if (!empty($filter)) {
            $boolQuery['filter'] = $filter;
        }

        // Sort
        $sort = $this->buildSort(
            $params['sort'] ?? 'lastUpdated',
            $params['sortDir'] ?? 'desc'
        );

        // Source fields
        $source = [
            'slug',
            'identifier',
            'i18n',
            'logoPath',
            'contactInformations',
            'thematicAreas',
            'types',
            'geographicSubregions',
            'updatedAt',
        ];

        // Aggregations
        $aggs = $this->buildAggregations();

        $body = [
            'size' => $limit,
            'from' => $skip,
            '_source' => $source,
            'query' => ['bool' => $boolQuery],
            'sort' => $sort,
            'aggs' => $aggs,
        ];

        $response = $this->esRequest($this->indexName, '/_search', $body);

        if (!$response) {
            return [
                'hits' => [],
                'total' => 0,
                'aggs' => [],
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

        // Populate aggregation display names (batch)
        $populatedAggs = $this->populateAggregations($response['aggregations'] ?? []);

        return [
            'hits' => $hits,
            'total' => $total,
            'aggs' => $populatedAggs,
            'page' => $page,
            'limit' => $limit,
            'filters' => $activeFilters,
        ];
    }

    /**
     * Load terms for advanced filter dropdowns.
     */
    public function getAdvancedFilterTerms(): array
    {
        $thematicAreas = $this->getTermsByTaxonomy(\QubitTaxonomy::THEMATIC_AREA_ID);
        $repositoryTypes = $this->getTermsByTaxonomy(\QubitTaxonomy::REPOSITORY_TYPE_ID);
        $regions = $this->getUniqueRegions();

        return [
            'thematicAreas' => $thematicAreas,
            'repositoryTypes' => $repositoryTypes,
            'regions' => $regions,
        ];
    }

    /**
     * Get terms for a given taxonomy via Laravel QB.
     *
     * @return array<int, string> [id => name]
     */
    public function getTermsByTaxonomy(int $taxonomyId): array
    {
        try {
            return DB::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', $taxonomyId)
                ->where('term_i18n.culture', $this->culture)
                ->whereNotNull('term_i18n.name')
                ->orderBy('term_i18n.name')
                ->pluck('term_i18n.name', 'term.id')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get unique region values from repository contact info.
     */
    public function getUniqueRegions(): array
    {
        try {
            return DB::table('contact_information')
                ->join('contact_information_i18n', 'contact_information.id', '=', 'contact_information_i18n.id')
                ->where('contact_information_i18n.culture', $this->culture)
                ->whereNotNull('contact_information_i18n.region')
                ->where('contact_information_i18n.region', '!=', '')
                ->distinct()
                ->orderBy('contact_information_i18n.region')
                ->pluck('contact_information_i18n.region')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Get aggregation definitions with culture-interpolated field names.
     */
    protected function getAggDefs(): array
    {
        $defs = [];
        foreach (self::AGGS as $name => $def) {
            $field = $def['field'];
            if (str_contains($field, '%s')) {
                $field = sprintf($field, $this->culture);
            }
            $defs[$name] = ['field' => $field, 'size' => $def['size']];
        }

        return $defs;
    }

    /**
     * Build ES sort.
     */
    protected function buildSort(string $sort, string $dir): array
    {
        $c = $this->culture;
        $direction = ('asc' === strtolower($dir)) ? 'asc' : 'desc';

        return match ($sort) {
            'nameUp' => [["i18n.{$c}.authorizedFormOfName.alphasort" => ['order' => 'asc', 'unmapped_type' => 'keyword']]],
            'nameDown' => [["i18n.{$c}.authorizedFormOfName.alphasort" => ['order' => 'desc', 'unmapped_type' => 'keyword']]],
            'regionUp' => [["i18n.{$c}.region.untouched" => ['order' => 'asc', 'unmapped_type' => 'keyword']]],
            'regionDown' => [["i18n.{$c}.region.untouched" => ['order' => 'desc', 'unmapped_type' => 'keyword']]],
            'localityUp' => [["i18n.{$c}.city.untouched" => ['order' => 'asc', 'unmapped_type' => 'keyword']]],
            'localityDown' => [["i18n.{$c}.city.untouched" => ['order' => 'desc', 'unmapped_type' => 'keyword']]],
            'identifier' => [
                ['identifier.untouched' => ['order' => $direction, 'unmapped_type' => 'keyword']],
                ["i18n.{$c}.authorizedFormOfName.alphasort" => ['order' => $direction, 'unmapped_type' => 'keyword']],
            ],
            'alphabetic' => [["i18n.{$c}.authorizedFormOfName.alphasort" => ['order' => $direction, 'unmapped_type' => 'keyword']]],
            default => [['updatedAt' => ['order' => $direction]]],
        };
    }

    /**
     * Build aggregation definitions for ES query.
     */
    protected function buildAggregations(): array
    {
        $aggs = [];
        foreach ($this->getAggDefs() as $name => $def) {
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

        // First pass: collect term IDs and handle simple aggs
        foreach ($this->getAggDefs() as $name => $def) {
            if (!isset($rawAggs[$name]['buckets']) || 0 === count($rawAggs[$name]['buckets'])) {
                $result[$name] = [];
                continue;
            }

            $buckets = $rawAggs[$name]['buckets'];

            // Language agg: resolve language codes to names
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

            // Regions and locality: key IS the display value
            if ('regions' === $name || 'locality' === $name) {
                $result[$name] = [];
                foreach ($buckets as $bucket) {
                    $result[$name][] = [
                        'key' => $bucket['key'],
                        'display' => $bucket['key'],
                        'doc_count' => $bucket['doc_count'] ?? 0,
                    ];
                }
                continue;
            }

            // Term-based aggs (types, geographicSubregions, thematicAreas): collect IDs
            foreach ($buckets as $bucket) {
                $allTermIds[] = (int) $bucket['key'];
            }
            $result[$name] = $buckets;
        }

        // Batch resolve term names
        $termNames = [];
        if (!empty($allTermIds)) {
            $termNames = $this->resolveTermNames(array_unique($allTermIds));
        }

        // Second pass: populate term-based agg display names
        foreach ($this->getAggDefs() as $name => $def) {
            if (in_array($name, ['languages', 'regions', 'locality'])) {
                continue;
            }

            if (empty($result[$name]) || !is_array($result[$name])) {
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
     * Resolve thematic area term IDs to names (for table view).
     */
    public function resolveThematicAreaNames(array $termIds): array
    {
        return $this->resolveTermNames($termIds);
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
                    return ucfirst($languages[$code]);
                }
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return $code;
    }

    /**
     * Extract i18n field from ES doc with culture fallback.
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
     * Extract contact information i18n field.
     */
    public function extractContactField(array $doc, string $field): string
    {
        if (empty($doc['contactInformations'])) {
            return '';
        }

        $cultures = [$this->culture, 'en', 'fr', 'es'];
        foreach ($doc['contactInformations'] as $contact) {
            foreach ($cultures as $c) {
                if (!empty($contact['i18n'][$c][$field])) {
                    return $contact['i18n'][$c][$field];
                }
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
                'ahgRepositoryBrowsePlugin ES error: HTTP %d, URL: %s',
                $httpCode,
                $url
            ));

            return null;
        }

        return json_decode($response, true);
    }
}
