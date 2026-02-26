<?php

namespace AhgDiscovery\Services;

/**
 * Step 2A: Elasticsearch Keyword Search
 *
 * Builds a BM25 bool query using expanded keywords, synonyms,
 * and date range filters against the AtoM ES index.
 */
class KeywordSearchStrategy
{
    /**
     * ES field boosts for multi_match query.
     */
    private const FIELD_BOOSTS = [
        'i18n.%s.title'                          => 3.0,
        'i18n.%s.scopeAndContent'                => 2.0,
        'i18n.%s.archivalHistory'                => 1.5,
        'subjects.i18n.%s.name'                  => 2.0,
        'places.i18n.%s.name'                    => 2.0,
        'creators.i18n.%s.authorizedFormOfName'  => 1.5,
        'names.i18n.%s.authorizedFormOfName'     => 1.0,
    ];

    private string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Execute keyword search against Elasticsearch.
     *
     * @param array $expanded ExpandedQuery from QueryExpander
     * @param int   $limit    Max results to return
     * @return array [{object_id, es_score, highlights}, ...]
     */
    public function search(array $expanded, int $limit = 100): array
    {
        $client = $this->getEsClient();
        if (!$client) {
            return [];
        }

        $query = $this->buildQuery($expanded);

        try {
            $params = [
                'index' => $this->getIndexName(),
                'body'  => [
                    'query'     => $query,
                    'size'      => $limit,
                    '_source'   => ['slug'],
                    'highlight' => [
                        'fields' => [
                            'i18n.' . $this->culture . '.title'            => (object)[],
                            'i18n.' . $this->culture . '.scopeAndContent'  => (object)[],
                        ],
                        'pre_tags'  => ['<mark>'],
                        'post_tags' => ['</mark>'],
                    ],
                ],
            ];

            $response = $client->search($params);
            return $this->parseResults($response);
        } catch (\Exception $e) {
            error_log('[ahgDiscovery] ES keyword search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build the ES bool query.
     */
    private function buildQuery(array $expanded): array
    {
        $must = [];
        $should = [];
        $filter = [];

        // Build fields list with culture and boosts
        $fields = [];
        foreach (self::FIELD_BOOSTS as $pattern => $boost) {
            $fields[] = sprintf($pattern, $this->culture) . '^' . $boost;
        }

        // Primary keyword query
        $keywordText = implode(' ', $expanded['keywords']);
        if (!empty($expanded['phrases'])) {
            $keywordText .= ' ' . implode(' ', $expanded['phrases']);
        }

        if (!empty(trim($keywordText))) {
            $must[] = [
                'multi_match' => [
                    'query'    => trim($keywordText),
                    'fields'   => $fields,
                    'type'     => 'best_fields',
                    'fuzziness' => 'AUTO',
                    'minimum_should_match' => '50%',
                ],
            ];
        }

        // Synonym boosting
        if (!empty($expanded['synonyms'])) {
            foreach ($expanded['synonyms'] as $synonym) {
                $should[] = [
                    'multi_match' => [
                        'query'  => $synonym,
                        'fields' => $fields,
                        'boost'  => 0.7,
                    ],
                ];
            }
        }

        // Phrase boosting for entity terms
        if (!empty($expanded['entityTerms'])) {
            foreach ($expanded['entityTerms'] as $entity) {
                $should[] = [
                    'multi_match' => [
                        'query'  => $entity['value'],
                        'fields' => $fields,
                        'type'   => 'phrase',
                        'boost'  => 1.5,
                    ],
                ];
            }
        }

        // Date range filter
        if (!empty($expanded['dateRange'])) {
            $dateFilter = [];
            if ($expanded['dateRange']['start'] !== null) {
                $dateFilter['gte'] = (string)$expanded['dateRange']['start'];
            }
            if ($expanded['dateRange']['end'] !== null) {
                $dateFilter['lte'] = (string)$expanded['dateRange']['end'];
            }
            if (!empty($dateFilter)) {
                $filter[] = [
                    'bool' => [
                        'should' => [
                            ['range' => ['dates.startDate' => $dateFilter]],
                            ['range' => ['dates.endDate' => $dateFilter]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];
            }
        }

        // Exclude root node (id=1)
        $rootId = defined('QubitInformationObject::ROOT_ID')
            ? \QubitInformationObject::ROOT_ID
            : 1;

        // Build final bool query
        $bool = [];
        if (!empty($must)) {
            $bool['must'] = $must;
        }
        if (!empty($should)) {
            $bool['should'] = $should;
            $bool['minimum_should_match'] = 0;
        }
        if (!empty($filter)) {
            $bool['filter'] = $filter;
        }
        $bool['must_not'] = [
            ['term' => ['parentId' => $rootId]],
        ];

        // If no must clauses, use should as must
        if (empty($bool['must']) && !empty($bool['should'])) {
            $bool['must'] = [array_shift($bool['should'])];
        }

        return ['bool' => $bool];
    }

    /**
     * Parse ES response into normalized results.
     */
    private function parseResults(array $response): array
    {
        $results = [];

        if (!isset($response['hits']['hits'])) {
            return $results;
        }

        foreach ($response['hits']['hits'] as $hit) {
            $objectId = (int)str_replace('QubitInformationObject-', '', $hit['_id'] ?? '');
            if ($objectId <= 0) {
                // Try slug-based ID extraction
                $objectId = (int)($hit['_source']['_internal_id'] ?? 0);
                if ($objectId <= 0) {
                    continue;
                }
            }

            $highlights = [];
            if (isset($hit['highlight'])) {
                foreach ($hit['highlight'] as $field => $fragments) {
                    $highlights[$field] = $fragments;
                }
            }

            $results[] = [
                'object_id'  => $objectId,
                'es_score'   => (float)($hit['_score'] ?? 0),
                'highlights' => $highlights,
                'slug'       => $hit['_source']['slug'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Get the search engine client (OpenSearch or Elasticsearch).
     *
     * Uses QubitSearch singleton which returns the arOpenSearchPlugin instance.
     * The client is the OpenSearch\Client object on its public $client property.
     *
     * @return object|null  OpenSearch\Client or Elasticsearch\Client
     */
    private function getEsClient()
    {
        try {
            if (class_exists('QubitSearch', false)) {
                $engine = \QubitSearch::getInstance();
                if ($engine && isset($engine->client)) {
                    return $engine->client;
                }
            }

            // Fallback: build client directly from config
            $host = \sfConfig::get('app_elasticsearch_host', 'localhost');
            $port = \sfConfig::get('app_elasticsearch_port', '9200');

            if (class_exists('OpenSearch\\ClientBuilder')) {
                return \OpenSearch\ClientBuilder::create()
                    ->setHosts([$host . ':' . $port])
                    ->build();
            }

            return null;
        } catch (\Exception $e) {
            error_log('[ahgDiscovery] Cannot connect to search engine: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the search index name for AtoM information objects.
     *
     * Pattern: {prefix}_qubitinformationobject
     * e.g. archive_qubitinformationobject
     */
    private function getIndexName(): string
    {
        try {
            if (class_exists('QubitSearch', false)) {
                $engine = \QubitSearch::getInstance();
                if ($engine && isset($engine->config['index']['name'])) {
                    return $engine->config['index']['name'] . '_qubitinformationobject';
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }

        $index = \sfConfig::get('app_elasticsearch_index', 'atom');
        return $index . '_qubitinformationobject';
    }
}
