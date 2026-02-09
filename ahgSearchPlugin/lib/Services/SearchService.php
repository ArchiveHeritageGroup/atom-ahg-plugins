<?php

namespace AhgSearch\Services;

use Illuminate\Database\Capsule\Manager as DB;

class SearchService
{
    protected string $culture;
    protected string $esHost;
    protected int $esPort;
    protected string $indexPrefix;

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
        $this->indexPrefix = $indexName;
    }

    /**
     * Multi-type autocomplete using ES _msearch.
     *
     * Returns array with keys: descriptions, repositories, actors, places, subjects.
     * Each value has 'hits' (array of doc data) and 'total' (int).
     */
    public function autocomplete(string $query, array $options = []): array
    {
        $repoFilter = $options['repos'] ?? null;

        $searches = [
            'descriptions' => [
                'index' => $this->indexPrefix . '_qubitinformationobject',
                'field' => sprintf('i18n.%s.title', $this->culture),
                'source' => ['slug', sprintf('i18n.%s.title', $this->culture), 'levelOfDescriptionId'],
                'filter' => null,
            ],
            'repositories' => [
                'index' => $this->indexPrefix . '_qubitrepository',
                'field' => sprintf('i18n.%s.authorizedFormOfName', $this->culture),
                'source' => ['slug', sprintf('i18n.%s.authorizedFormOfName', $this->culture)],
                'filter' => null,
            ],
            'actors' => [
                'index' => $this->indexPrefix . '_qubitactor',
                'field' => sprintf('i18n.%s.authorizedFormOfName', $this->culture),
                'source' => ['slug', sprintf('i18n.%s.authorizedFormOfName', $this->culture)],
                'filter' => null,
            ],
            'places' => [
                'index' => $this->indexPrefix . '_qubitterm',
                'field' => sprintf('i18n.%s.name', $this->culture),
                'source' => ['slug', sprintf('i18n.%s.name', $this->culture)],
                'filter' => ['term' => ['taxonomyId' => \QubitTaxonomy::PLACE_ID]],
            ],
            'subjects' => [
                'index' => $this->indexPrefix . '_qubitterm',
                'field' => sprintf('i18n.%s.name', $this->culture),
                'source' => ['slug', sprintf('i18n.%s.name', $this->culture)],
                'filter' => ['term' => ['taxonomyId' => \QubitTaxonomy::SUBJECT_ID]],
            ],
        ];

        // Build _msearch body (newline-delimited JSON)
        $ndjson = '';
        foreach ($searches as $key => $config) {
            // Header line
            $ndjson .= json_encode(['index' => $config['index']]) . "\n";

            // Query body
            $must = [];
            $must[] = ['match' => [$config['field'] . '.autocomplete' => $query]];

            if ($config['filter']) {
                $must[] = $config['filter'];
            }

            if ($repoFilter && $key === 'descriptions') {
                $must[] = ['term' => ['repository.id' => (int) $repoFilter]];
            }

            // ACL draft filtering for information objects
            if ($key === 'descriptions') {
                $draftFilter = $this->buildDraftFilter();
                if ($draftFilter) {
                    $must[] = $draftFilter;
                }
            }

            // Optional semantic expansion
            $should = [];
            if (!empty($options['semanticTerms'])) {
                foreach ($options['semanticTerms'] as $term) {
                    $should[] = ['match' => [$config['field'] . '.autocomplete' => ['query' => $term, 'boost' => 0.3]]];
                }
            }

            $boolQuery = ['must' => $must];
            if (!empty($should)) {
                $boolQuery['should'] = $should;
            }

            $body = [
                'size' => 3,
                '_source' => $config['source'],
                'query' => ['bool' => $boolQuery],
            ];

            $ndjson .= json_encode($body) . "\n";
        }

        // Execute _msearch
        $url = sprintf('http://%s:%d/_msearch', $this->esHost, $this->esPort);
        $response = $this->esCurlRequest($url, $ndjson, 'application/x-ndjson');

        if (!$response || !isset($response['responses'])) {
            return array_fill_keys(array_keys($searches), ['hits' => [], 'total' => 0]);
        }

        $results = [];
        $keys = array_keys($searches);
        foreach ($response['responses'] as $i => $resp) {
            $key = $keys[$i] ?? 'unknown';
            $hits = [];
            $total = $resp['hits']['total']['value'] ?? 0;

            if (isset($resp['hits']['hits'])) {
                foreach ($resp['hits']['hits'] as $hit) {
                    $hits[] = $hit['_source'] ?? [];
                }
            }

            $results[$key] = ['hits' => $hits, 'total' => $total];
        }

        return $results;
    }

    /**
     * Search information objects (used by treeview search and XHR).
     * Returns JSON-compatible array: {results: [...], more: "html"}
     */
    public function searchIndex(string $query, array $options = []): array
    {
        $limit = (int) ($options['limit'] ?? \sfConfig::get('app_hits_per_page', 10));
        $page = max(1, (int) ($options['page'] ?? 1));
        $repoFilter = $options['repos'] ?? null;
        $collectionFilter = $options['collection'] ?? null;

        $must = [];

        // Multi-field query string
        $must[] = [
            'query_string' => [
                'query' => $this->sanitizeQuery($query),
                'fields' => $this->getIoSearchFields(),
                'default_operator' => 'AND',
            ],
        ];

        if ($repoFilter) {
            $must[] = ['term' => ['repository.id' => (int) $repoFilter]];
        }

        if ($collectionFilter) {
            $must[] = ['term' => ['ancestors' => (int) $collectionFilter]];
        }

        // ACL draft filtering
        $draftFilter = $this->buildDraftFilter();
        if ($draftFilter) {
            $must[] = $draftFilter;
        }

        $body = [
            'size' => $limit,
            'from' => ($page - 1) * $limit,
            '_source' => ['slug', sprintf('i18n.%s.title', $this->culture), 'identifier', 'levelOfDescriptionId'],
            'query' => ['bool' => ['must' => $must]],
        ];

        $index = $this->indexPrefix . '_qubitinformationobject';
        $result = $this->esRequest($index, '/_search', $body);

        if (!$result || !isset($result['hits'])) {
            return ['results' => [], 'total' => 0];
        }

        return [
            'results' => $result['hits']['hits'] ?? [],
            'total' => $result['hits']['total']['value'] ?? 0,
        ];
    }

    /**
     * Description updates search via ES.
     */
    public function descriptionUpdates(array $params): array
    {
        $className = $params['className'] ?? 'QubitInformationObject';
        $dateOf = $params['dateOf'] ?? 'CREATED_AT';
        $startDate = $params['startDate'] ?? null;
        $endDate = $params['endDate'] ?? null;
        $publicationStatus = $params['publicationStatus'] ?? 'all';
        $repository = $params['repository'] ?? null;
        $limit = (int) ($params['limit'] ?? \sfConfig::get('app_hits_per_page', 10));
        $page = max(1, (int) ($params['page'] ?? 1));

        $must = [];

        if ($className === 'QubitInformationObject') {
            if ($publicationStatus !== 'all') {
                $must[] = ['term' => ['publicationStatusId' => (int) $publicationStatus]];
            }
            if ($repository) {
                $must[] = ['term' => ['repository.id' => (int) $repository]];
            }
        }

        // Date range
        $must = array_merge($must, $this->buildDateRangeQuery($dateOf, $startDate, $endDate));

        $body = [
            'size' => $limit,
            'from' => ($page - 1) * $limit,
            'sort' => [['createdAt' => 'desc']],
            'query' => ['bool' => ['must' => !empty($must) ? $must : [['match_all' => (object) []]]]],
        ];

        // Map className to index
        $indexMap = [
            'QubitInformationObject' => '_qubitinformationobject',
            'QubitActor' => '_qubitactor',
            'QubitRepository' => '_qubitrepository',
            'QubitTerm' => '_qubitterm',
            'QubitFunctionObject' => '_qubitfunctionobject',
        ];

        $indexSuffix = $indexMap[$className] ?? '_qubitinformationobject';
        $index = $this->indexPrefix . $indexSuffix;

        $result = $this->esRequest($index, '/_search', $body);

        if (!$result || !isset($result['hits'])) {
            return ['hits' => [], 'total' => 0];
        }

        return [
            'hits' => $result['hits']['hits'] ?? [],
            'total' => $result['hits']['total']['value'] ?? 0,
        ];
    }

    /**
     * Get cached list of repositories for description updates filter.
     */
    public function getRepositoryList(): array
    {
        $cache = \QubitCache::getInstance();
        $cacheKey = 'ahgsearch:repository-list:' . $this->culture;

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $rows = DB::table('actor')
            ->join('actor_i18n', function ($join) {
                $join->on('actor.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $this->culture);
            })
            ->join('repository', 'repository.id', '=', 'actor.id')
            ->where('actor.id', '!=', \QubitRepository::ROOT_ID)
            ->orderBy('actor_i18n.authorized_form_of_name', 'asc')
            ->select('actor.id', 'actor_i18n.authorized_form_of_name')
            ->get();

        $choices = ['' => ''];
        foreach ($rows as $row) {
            $choices[$row->id] = $row->authorized_form_of_name ?: '[untitled]';
        }

        $cache->set($cacheKey, $choices, 3600);

        return $choices;
    }

    /**
     * Get levels of description for autocomplete display.
     */
    public function getLevelsOfDescription(): array
    {
        $rows = DB::table('term')
            ->leftJoin('term_i18n', function ($join) {
                $join->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $this->culture);
            })
            ->where('term.taxonomy_id', '=', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->select('term.id', 'term_i18n.name')
            ->get();

        $levels = [];
        foreach ($rows as $row) {
            $levels[$row->id] = $row->name;
        }

        return $levels;
    }

    /**
     * Get i18n column list for information_object_i18n table.
     */
    public function getIoI18nColumns(): array
    {
        static $columns = null;

        if ($columns === null) {
            try {
                $allColumns = DB::connection()->getSchemaBuilder()->getColumnListing('information_object_i18n');
                $columns = [];
                $excluded = ['id', 'culture'];
                foreach ($allColumns as $col) {
                    if (!in_array($col, $excluded)) {
                        $columns[$col] = ucfirst(str_replace('_', ' ', \sfInflector::underscore(\sfInflector::camelize($col))));
                    }
                }
                // Add identifier from main table
                $columns['identifier'] = 'Identifier';
            } catch (\Exception $e) {
                $columns = ['title' => 'Title', 'identifier' => 'Identifier'];
            }
        }

        return $columns;
    }

    /**
     * Build ACL draft filter for ES queries.
     */
    protected function buildDraftFilter(): ?array
    {
        $repositoryViewDrafts = \QubitAcl::getRepositoryAccess('viewDraft');

        if (1 === count($repositoryViewDrafts)) {
            if (\QubitAcl::DENY === $repositoryViewDrafts[0]['access']) {
                return ['term' => ['publicationStatusId' => \QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID]];
            }

            return null;
        }

        $globalRule = array_pop($repositoryViewDrafts);
        $should = [];

        while ($repo = array_shift($repositoryViewDrafts)) {
            $should[] = ['term' => ['repository.id' => (int) $repo['id']]];
        }

        $should[] = ['term' => ['publicationStatusId' => \QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID]];

        if (\QubitAcl::GRANT === $globalRule['access']) {
            return ['bool' => ['must_not' => [['bool' => ['should' => $should]]]]];
        }

        return ['bool' => ['should' => $should]];
    }

    /**
     * Build date range query clauses.
     */
    protected function buildDateRangeQuery(string $dateOf, ?string $startDate, ?string $endDate): array
    {
        $clauses = [];

        switch ($dateOf) {
            case 'CREATED_AT':
                $clauses = array_merge($clauses, $this->buildDateRangeClauses('createdAt', $startDate, $endDate));
                break;

            case 'UPDATED_AT':
                $clauses = array_merge($clauses, $this->buildDateRangeClauses('updatedAt', $startDate, $endDate));
                break;

            default: // 'both'
                $created = $this->buildDateRangeClauses('createdAt', $startDate, $endDate);
                $updated = $this->buildDateRangeClauses('updatedAt', $startDate, $endDate);

                if (!empty($created) || !empty($updated)) {
                    $should = [];
                    if (!empty($created)) {
                        $should[] = ['bool' => ['must' => $created]];
                    }
                    if (!empty($updated)) {
                        $should[] = ['bool' => ['must' => $updated]];
                    }
                    $clauses[] = ['bool' => ['should' => $should]];
                }
                break;
        }

        return $clauses;
    }

    protected function buildDateRangeClauses(string $field, ?string $startDate, ?string $endDate): array
    {
        $clauses = [];

        if ($startDate) {
            $clauses[] = ['range' => [$field => ['gte' => $startDate]]];
        }

        if ($endDate) {
            $clauses[] = ['range' => [$field => ['lte' => $endDate]]];
        }

        return $clauses;
    }

    /**
     * Get IO search fields for query_string.
     */
    protected function getIoSearchFields(): array
    {
        return [
            sprintf('i18n.%s.title', $this->culture),
            'identifier',
            sprintf('i18n.%s.scopeAndContent', $this->culture),
            sprintf('i18n.%s.archivalHistory', $this->culture),
            'referenceCode',
            sprintf('i18n.%s.extentAndMedium', $this->culture),
            sprintf('i18n.%s.locationOfOriginals', $this->culture),
            sprintf('i18n.%s.accessConditions', $this->culture),
        ];
    }

    /**
     * Sanitize query string for ES.
     */
    protected function sanitizeQuery(string $query): string
    {
        // Remove wildcards and escape special chars that could break query_string
        $query = strtr($query, ['*' => '', '?' => '']);

        return $query;
    }

    /**
     * Execute ES request via curl.
     */
    protected function esRequest(string $index, string $endpoint, array $body): ?array
    {
        $url = sprintf('http://%s:%d/%s%s', $this->esHost, $this->esPort, $index, $endpoint);

        return $this->esCurlRequest($url, json_encode($body));
    }

    /**
     * Low-level curl request to ES.
     */
    protected function esCurlRequest(string $url, string $body, string $contentType = 'application/json'): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: ' . $contentType],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 !== $httpCode || false === $response) {
            error_log(sprintf(
                'ahgSearchPlugin ES error: HTTP %d, URL: %s',
                $httpCode,
                $url
            ));

            return null;
        }

        return json_decode($response, true);
    }
}
