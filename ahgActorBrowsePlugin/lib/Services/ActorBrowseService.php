<?php

namespace AhgActorBrowse\Services;

use Illuminate\Database\Capsule\Manager as DB;


class ActorBrowseService
{
    protected string $culture;
    protected string $esHost;
    protected int $esPort;
    protected string $indexName;

    // Taxonomy IDs (QubitTaxonomy constants)
    protected const ENTITY_TYPE_TAXONOMY_ID = 32;
    protected const MEDIA_TYPE_TAXONOMY_ID = 46;
    protected const ACTOR_RELATION_TYPE_TAXONOMY_ID = 60;
    protected const SUBJECT_TAXONOMY_ID = 35;
    protected const PLACE_TAXONOMY_ID = 42;

    // Entity type term IDs
    protected const CORPORATE_BODY_ID = 131;
    protected const PERSON_ID = 132;
    protected const FAMILY_ID = 133;

    // Aggregation definitions
    protected const AGGS = [
        'languages' => ['field' => 'i18n.languages', 'size' => 10],
        'entityType' => ['field' => 'entityTypeId', 'size' => 10],
        'repository' => ['field' => 'maintainingRepositoryId', 'size' => 10],
        'occupation' => ['field' => 'occupations.id', 'size' => 10],
        'place' => ['field' => 'places.id', 'size' => 10],
        'subject' => ['field' => 'subjects.id', 'size' => 10],
        'mediatypes' => ['field' => 'digitalObject.mediaTypeId', 'size' => 10],
    ];

    // Filter tag config
    protected const FILTER_TAG_MODELS = [
        'entityType' => 'term',
        'repository' => 'actor',
        'occupation' => 'term',
        'place' => 'term',
        'subject' => 'term',
        'mediatypes' => 'term',
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
        $this->indexName = $indexName . '_qubitactor';
    }

    /**
     * Main browse orchestrator.
     */
    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 30)));
        $skip = ($page - 1) * $limit;

        // Enforce ES max result window
        $maxResultWindow = (int) \sfConfig::get('app_opensearch_max_result_window', 10000);
        if ($skip + $limit > $maxResultWindow) {
            $skip = max(0, $maxResultWindow - $limit);
            $page = (int) floor($skip / $limit) + 1;
        }

        // Build ES query
        $must = [];
        $mustNot = [];
        $filter = [];

        // Text search
        if (!empty($params['subquery'])) {
            $must[] = $this->buildTextQuery($params['subquery'], $params['subqueryField'] ?? '');
        }

        // Advanced search criteria (sq0/sf0/so0 pattern)
        $advClauses = $this->buildAdvancedSearchClauses($params);
        if (!empty($advClauses['must'])) {
            $must = array_merge($must, $advClauses['must']);
        }
        if (!empty($advClauses['mustNot'])) {
            $mustNot = array_merge($mustNot, $advClauses['mustNot']);
        }

        // Facet filters
        $activeFilters = [];
        foreach (self::AGGS as $name => $aggDef) {
            if (!empty($params[$name])) {
                $filter[] = ['term' => [$aggDef['field'] => $params[$name]]];
                $activeFilters[$name] = $params[$name];
            }
        }

        // hasDigitalObject filter
        if (!empty($params['hasDigitalObject'])) {
            $filter[] = ['term' => ['hasDigitalObject' => true]];
            $activeFilters['hasDigitalObject'] = true;
        }

        // Related authority filter (nested on actorRelations)
        if (!empty($params['relatedAuthority'])) {
            $nestedMust = [];
            // The related authority is a slug — resolve to ID
            $relActorId = $this->resolveActorIdBySlug($params['relatedAuthority']);
            if ($relActorId) {
                $nestedMust[] = [
                    'bool' => [
                        'should' => [
                            ['term' => ['actorRelations.objectId' => $relActorId]],
                            ['term' => ['actorRelations.subjectId' => $relActorId]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];

                if (!empty($params['relatedType'])) {
                    $nestedMust[] = ['term' => ['actorRelations.type.id' => (int) $params['relatedType']]];
                }

                $filter[] = [
                    'nested' => [
                        'path' => 'actorRelations',
                        'query' => ['bool' => ['must' => $nestedMust]],
                    ],
                ];
            }
        } elseif (!empty($params['relatedType'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'actorRelations',
                    'query' => ['term' => ['actorRelations.type.id' => (int) $params['relatedType']]],
                ],
            ];
        }

        // Empty field filter
        if (!empty($params['emptyField'])) {
            $emptyFieldEs = $this->mapEmptyField($params['emptyField']);
            if ($emptyFieldEs) {
                $mustNot[] = ['exists' => ['field' => $emptyFieldEs]];
            }
        }

        // Build the bool query
        $boolQuery = [];
        if (!empty($must)) {
            $boolQuery['must'] = $must;
        }
        if (!empty($mustNot)) {
            $boolQuery['must_not'] = $mustNot;
        }
        if (!empty($filter)) {
            $boolQuery['filter'] = $filter;
        }

        $query = !empty($boolQuery)
            ? ['bool' => $boolQuery]
            : ['match_all' => new \stdClass()];

        // Build sort
        $sort = $this->buildSort(
            $params['sort'] ?? 'alphabetic',
            $params['sortDir'] ?? 'asc'
        );

        // Source fields to return
        $source = [
            'slug',
            'descriptionIdentifier',
            'entityTypeId',
            'hasDigitalObject',
            'digitalObject',
            'i18n',
            'updatedAt',
            'createdAt',
        ];

        // Build aggregations
        $aggs = $this->buildAggregations($activeFilters);

        // Full ES body
        $body = [
            'size' => $limit,
            'from' => $skip,
            '_source' => $source,
            'query' => $query,
            'sort' => $sort,
            'aggs' => $aggs,
        ];

        // Execute
        $response = $this->esRequest('/_search', $body);

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
     * Build text search query with multi_match.
     */
    protected function buildTextQuery(string $query, string $field = ''): array
    {
        $c = $this->culture;

        if (!empty($field)) {
            $esFields = $this->mapSearchField($field);
            if (!empty($esFields)) {
                return [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => $esFields,
                        'fuzziness' => 'AUTO',
                        'prefix_length' => 1,
                    ],
                ];
            }
        }

        // Default: search all actor fields
        return [
            'multi_match' => [
                'query' => $query,
                'fields' => [
                    "i18n.{$c}.authorizedFormOfName^3",
                    "i18n.{$c}.history",
                    "i18n.{$c}.places",
                    "i18n.{$c}.legalStatus",
                    "i18n.{$c}.generalContext",
                    "i18n.{$c}.datesOfExistence",
                    'descriptionIdentifier',
                    "parallelNames.i18n.{$c}.name",
                    "otherNames.i18n.{$c}.name",
                    "subjects.i18n.{$c}.name",
                    "places.i18n.{$c}.name",
                    "occupations.i18n.{$c}.name",
                    'all',
                ],
                'fuzziness' => 'AUTO',
                'prefix_length' => 1,
                'max_expansions' => 50,
            ],
        ];
    }

    /**
     * Map advanced search field name to ES field paths.
     */
    protected function mapSearchField(string $field): array
    {
        $c = $this->culture;
        $map = [
            'authorizedFormOfName' => ["i18n.{$c}.authorizedFormOfName"],
            'parallelNames' => ["parallelNames.i18n.{$c}.name"],
            'otherNames' => ["otherNames.i18n.{$c}.name"],
            'datesOfExistence' => ["i18n.{$c}.datesOfExistence"],
            'history' => ["i18n.{$c}.history"],
            'places' => ["i18n.{$c}.places"],
            'legalStatus' => ["i18n.{$c}.legalStatus"],
            'generalContext' => ["i18n.{$c}.generalContext"],
            'institutionResponsibleIdentifier' => ["i18n.{$c}.institutionResponsibleIdentifier"],
            'sources' => ["i18n.{$c}.sources"],
            'descriptionIdentifier' => ['descriptionIdentifier'],
            'occupation' => ["occupations.i18n.{$c}.name", "occupations.i18n.{$c}.content"],
            'subject' => ["subjects.i18n.{$c}.name"],
            'place' => ["places.i18n.{$c}.name"],
            'maintenanceNotes' => ["maintenanceNotes.i18n.{$c}.content"],
        ];

        return $map[$field] ?? [];
    }

    /**
     * Parse sq0/sf0/so0 advanced search params into ES clauses.
     */
    protected function buildAdvancedSearchClauses(array $params): array
    {
        $must = [];
        $mustNot = [];

        for ($i = 0; $i < 20; $i++) {
            $query = $params["sq{$i}"] ?? '';
            if ('' === $query) {
                continue;
            }

            $field = $params["sf{$i}"] ?? '';
            $operator = $params["so{$i}"] ?? 'and';
            $clause = $this->buildTextQuery($query, $field);

            if ('not' === $operator) {
                $mustNot[] = $clause;
            } else {
                // 'and' and 'or' both go into must for simplicity
                // (true boolean OR would require 'should' with minimum_should_match)
                if ('or' === $operator && !empty($must)) {
                    // Wrap existing must + new clause in a should
                    $existing = $must;
                    $must = [];
                    $must[] = [
                        'bool' => [
                            'should' => array_merge($existing, [$clause]),
                            'minimum_should_match' => 1,
                        ],
                    ];
                } else {
                    $must[] = $clause;
                }
            }
        }

        return ['must' => $must, 'mustNot' => $mustNot];
    }

    /**
     * Map empty field names to ES field paths.
     */
    protected function mapEmptyField(string $fieldName): string
    {
        $c = $this->culture;
        $map = [
            'authorizedFormOfName' => "i18n.{$c}.authorizedFormOfName",
            'datesOfExistence' => "i18n.{$c}.datesOfExistence",
            'history' => "i18n.{$c}.history",
            'places' => "i18n.{$c}.places",
            'legalStatus' => "i18n.{$c}.legalStatus",
            'generalContext' => "i18n.{$c}.generalContext",
            'descriptionIdentifier' => 'descriptionIdentifier',
        ];

        return $map[$fieldName] ?? '';
    }

    /**
     * Build ES sort configuration.
     */
    protected function buildSort(string $sort, string $dir): array
    {
        $c = $this->culture;
        $direction = ('desc' === strtolower($dir)) ? 'desc' : 'asc';

        switch ($sort) {
            case 'identifier':
                return [
                    ['descriptionIdentifier.untouched' => ['order' => $direction, 'unmapped_type' => 'keyword']],
                ];

            case 'lastUpdated':
                return [
                    ['updatedAt' => ['order' => ('asc' === $direction) ? 'asc' : 'desc']],
                ];

            case 'alphabetic':
            default:
                return [
                    ["i18n.{$c}.authorizedFormOfName.alphasort" => ['order' => $direction, 'unmapped_type' => 'keyword']],
                ];
        }
    }

    /**
     * Build ES aggregation definitions with post-filter support.
     */
    protected function buildAggregations(array $activeFilters): array
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
     * Populate aggregation buckets with display names.
     * 2 batch queries instead of N+1 Propel hydration.
     */
    protected function populateAggregations(array $rawAggs): array
    {
        $result = [];
        $allTermIds = [];
        $allRepoIds = [];

        // First pass: collect all IDs we need to resolve
        foreach (self::AGGS as $name => $def) {
            if (!isset($rawAggs[$name]['buckets'])) {
                $result[$name] = [];
                continue;
            }

            $buckets = $rawAggs[$name]['buckets'];

            if ('languages' === $name) {
                // Languages resolve from sfCultureInfo, no DB needed
                $result[$name] = [];
                foreach ($buckets as $bucket) {
                    $langCode = $bucket['key'] ?? '';
                    $display = $this->resolveLanguageName($langCode);
                    $result[$name][] = [
                        'key' => $langCode,
                        'display' => $display,
                        'doc_count' => $bucket['doc_count'] ?? 0,
                    ];
                }
                continue;
            }

            if ('repository' === $name) {
                foreach ($buckets as $bucket) {
                    $allRepoIds[] = (int) $bucket['key'];
                }
            } else {
                // All other facets use term IDs
                foreach ($buckets as $bucket) {
                    $allTermIds[] = (int) $bucket['key'];
                }
            }

            // Store raw buckets for second pass
            $result[$name] = $buckets;
        }

        // Batch resolve: term names
        $termNames = [];
        if (!empty($allTermIds)) {
            $termNames = $this->resolveTermNames(array_unique($allTermIds));
        }

        // Batch resolve: repository names
        $repoNames = [];
        if (!empty($allRepoIds)) {
            $repoNames = $this->resolveActorNames(array_unique($allRepoIds));
        }

        // Second pass: replace raw buckets with populated ones
        foreach (self::AGGS as $name => $def) {
            if ('languages' === $name) {
                continue; // Already resolved
            }

            $populated = [];
            foreach ($result[$name] as $bucket) {
                $key = $bucket['key'] ?? '';
                $intKey = (int) $key;

                if ('repository' === $name) {
                    $display = $repoNames[$intKey] ?? "#{$key}";
                } else {
                    $display = $termNames[$intKey] ?? "#{$key}";
                }

                $populated[] = [
                    'key' => $key,
                    'display' => $display,
                    'doc_count' => $bucket['doc_count'] ?? 0,
                ];
            }

            $result[$name] = $populated;
        }

        return $result;
    }

    /**
     * Batch resolve term IDs to display names.
     */
    protected function resolveTermNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        try {
            $rows = DB::table('term_i18n')
                ->whereIn('id', $ids)
                ->where('culture', $this->culture)
                ->pluck('name', 'id');

            return $rows->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Batch resolve actor IDs to authorized form of name.
     */
    protected function resolveActorNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        try {
            $rows = DB::table('actor_i18n')
                ->whereIn('id', $ids)
                ->where('culture', $this->culture)
                ->pluck('authorized_form_of_name', 'id');

            return $rows->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Resolve actor slug to ID.
     */
    protected function resolveActorIdBySlug(string $slug): ?int
    {
        try {
            $row = DB::table('slug')
                ->where('slug', $slug)
                ->first();

            return $row ? (int) $row->object_id : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Resolve a language code to a display name.
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
     * Build filter tags for active filters.
     * Returns data compatible with the search/filterTags partial.
     */
    public function buildFilterTags(array $params): array
    {
        $tags = [];

        // hasDigitalObject
        if (!empty($params['hasDigitalObject']) && filter_var($params['hasDigitalObject'], FILTER_VALIDATE_BOOLEAN)) {
            $tags['hasDigitalObject'] = [
                'label' => \__('With digital objects'),
            ];
        }

        // Term-based filters
        $termFilters = ['entityType', 'occupation', 'place', 'subject', 'mediatypes'];
        $termIds = [];
        foreach ($termFilters as $name) {
            if (!empty($params[$name])) {
                $termIds[(int) $params[$name]] = $name;
            }
        }

        if (!empty($termIds)) {
            $names = $this->resolveTermNames(array_keys($termIds));
            foreach ($termIds as $id => $filterName) {
                $tags[$filterName] = [
                    'label' => $names[$id] ?? "#{$id}",
                ];
            }
        }

        // Repository filter
        if (!empty($params['repository'])) {
            $repoId = (int) $params['repository'];
            $repoNames = $this->resolveActorNames([$repoId]);
            $tags['repository'] = [
                'label' => $repoNames[$repoId] ?? "#{$repoId}",
            ];
        }

        // Empty field
        if (!empty($params['emptyField'])) {
            $tags['emptyField'] = [
                'label' => \__('Empty: %1%', ['%1%' => $params['emptyField']]),
            ];
        }

        // Related authority
        if (!empty($params['relatedAuthority'])) {
            $actorId = $this->resolveActorIdBySlug($params['relatedAuthority']);
            if ($actorId) {
                $actorNames = $this->resolveActorNames([$actorId]);
                $tags['relatedAuthority'] = [
                    'label' => $actorNames[$actorId] ?? $params['relatedAuthority'],
                ];
            }
        }

        // Related type
        if (!empty($params['relatedType'])) {
            $typeId = (int) $params['relatedType'];
            $typeNames = $this->resolveTermNames([$typeId]);
            $tags['relatedType'] = [
                'label' => $typeNames[$typeId] ?? "#{$typeId}",
            ];
        }

        return $tags;
    }

    /**
     * Autocomplete via ES prefix search with DB fallback.
     */
    public function autocomplete(string $query, int $limit = 10): array
    {
        if (empty(trim($query))) {
            return [];
        }

        $c = $this->culture;

        // ES suggest/prefix query
        $body = [
            'size' => $limit,
            '_source' => ['slug', 'i18n', 'entityTypeId'],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                "i18n.{$c}.authorizedFormOfName.autocomplete" => [
                                    'query' => $query,
                                    'operator' => 'and',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                ["i18n.{$c}.authorizedFormOfName.alphasort" => ['order' => 'asc', 'unmapped_type' => 'keyword']],
            ],
        ];

        $response = $this->esRequest('/_search', $body);

        $results = [];
        if ($response && !empty($response['hits']['hits'])) {
            // Collect entity type IDs for batch resolution
            $entityTypeIds = [];
            foreach ($response['hits']['hits'] as $hit) {
                $doc = $hit['_source'] ?? [];
                if (!empty($doc['entityTypeId'])) {
                    $entityTypeIds[] = (int) $doc['entityTypeId'];
                }
            }

            $entityTypeNames = !empty($entityTypeIds) ? $this->resolveTermNames(array_unique($entityTypeIds)) : [];

            foreach ($response['hits']['hits'] as $hit) {
                $doc = $hit['_source'] ?? [];
                $name = $this->extractI18nField($doc, 'authorizedFormOfName');
                $entityTypeId = $doc['entityTypeId'] ?? null;

                $results[] = [
                    'name' => $name,
                    'slug' => $doc['slug'] ?? '',
                    'entityType' => $entityTypeId ? ($entityTypeNames[(int) $entityTypeId] ?? '') : '',
                ];
            }
        }

        // DB fallback if ES returned nothing
        if (empty($results)) {
            $results = $this->autocompleteFromDb($query, $limit);
        }

        return $results;
    }

    /**
     * DB fallback for autocomplete.
     */
    protected function autocompleteFromDb(string $query, int $limit): array
    {
        try {
            $rows = DB::table('actor')
                ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
                ->join('object', 'actor.id', '=', 'object.id')
                ->join('slug', 'actor.id', '=', 'slug.object_id')
                ->where('actor_i18n.culture', $this->culture)
                ->where('actor_i18n.authorized_form_of_name', 'LIKE', $query . '%')
                ->where('object.class_name', 'QubitActor')
                ->orderBy('actor_i18n.authorized_form_of_name')
                ->limit($limit)
                ->select([
                    'actor.id',
                    'actor_i18n.authorized_form_of_name as name',
                    'slug.slug',
                    'actor.entity_type_id',
                ])
                ->get();

            $entityTypeIds = $rows->pluck('entity_type_id')->filter()->unique()->toArray();
            $entityTypeNames = !empty($entityTypeIds) ? $this->resolveTermNames($entityTypeIds) : [];

            $results = [];
            foreach ($rows as $row) {
                $results[] = [
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'entityType' => $row->entity_type_id ? ($entityTypeNames[(int) $row->entity_type_id] ?? '') : '',
                ];
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get form choices for repository, entityType, relationType dropdowns.
     * Replaces addField() Propel calls.
     */
    public function getFormChoices(): array
    {
        $choices = [
            'repository' => [],
            'entityType' => [],
            'relatedType' => [],
            'hasDigitalObject' => ['' => '', '1' => \__('Yes')],
            'emptyField' => [
                '' => '',
                'authorizedFormOfName' => \__('Name'),
                'datesOfExistence' => \__('Dates of existence'),
                'history' => \__('History'),
                'places' => \__('Places'),
                'legalStatus' => \__('Legal status'),
                'generalContext' => \__('General context'),
                'descriptionIdentifier' => \__('Description identifier'),
            ],
        ];

        try {
            // Repositories
            $repos = DB::table('repository')
                ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
                ->where('actor_i18n.culture', $this->culture)
                ->orderBy('actor_i18n.authorized_form_of_name')
                ->select(['repository.id', 'actor_i18n.authorized_form_of_name as name'])
                ->get();

            $choices['repository'] = ['' => ''];
            foreach ($repos as $repo) {
                $choices['repository'][$repo->id] = $repo->name;
            }
        } catch (\Exception $e) {
            $choices['repository'] = ['' => ''];
        }

        try {
            // Entity types (taxonomy 32)
            $terms = DB::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', self::ENTITY_TYPE_TAXONOMY_ID)
                ->where('term_i18n.culture', $this->culture)
                ->orderBy('term_i18n.name')
                ->select(['term.id', 'term_i18n.name'])
                ->get();

            $choices['entityType'] = ['' => ''];
            foreach ($terms as $term) {
                $choices['entityType'][$term->id] = $term->name;
            }
        } catch (\Exception $e) {
            $choices['entityType'] = ['' => ''];
        }

        try {
            // Actor relation types (taxonomy 60)
            $terms = DB::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', self::ACTOR_RELATION_TYPE_TAXONOMY_ID)
                ->where('term_i18n.culture', $this->culture)
                ->orderBy('term_i18n.name')
                ->select(['term.id', 'term_i18n.name'])
                ->get();

            $choices['relatedType'] = ['' => ''];
            foreach ($terms as $term) {
                $choices['relatedType'][$term->id] = $term->name;
            }
        } catch (\Exception $e) {
            $choices['relatedType'] = ['' => ''];
        }

        return $choices;
    }

    /**
     * Get advanced search field options for the form.
     */
    public function getFieldOptions(): array
    {
        return [
            'authorizedFormOfName' => \__('Authorized form of name'),
            'parallelNames' => \__('Parallel form(s) of name'),
            'otherNames' => \__('Other name(s)'),
            'datesOfExistence' => \__('Dates of existence'),
            'history' => \__('History'),
            'places' => \__('Places'),
            'legalStatus' => \__('Legal status'),
            'generalContext' => \__('General context'),
            'descriptionIdentifier' => \__('Description identifier'),
            'institutionResponsibleIdentifier' => \__('Institution identifier'),
            'sources' => \__('Sources'),
            'occupation' => \__('Occupation'),
            'subject' => \__('Subject'),
            'place' => \__('Place'),
        ];
    }

    /**
     * Parse advanced search criteria from request params.
     */
    public function parseCriteria(array $params): array
    {
        $criteria = [];
        for ($i = 0; $i < 20; $i++) {
            $query = $params["sq{$i}"] ?? '';
            if ('' === $query && 0 !== $i) {
                continue;
            }
            $criteria[$i] = [
                'query' => $query,
                'field' => $params["sf{$i}"] ?? '',
                'operator' => $params["so{$i}"] ?? 'and',
            ];
            if (0 === $i && empty($criteria[$i]['query'])) {
                // Always have at least one empty criterion row
                break;
            }
        }

        if (empty($criteria)) {
            $criteria[0] = ['query' => '', 'field' => '', 'operator' => 'and'];
        }

        return $criteria;
    }

    /**
     * Extract i18n field from ES doc.
     */
    public function extractI18nField(array $doc, string $field): string
    {
        // Try current culture first, then fallback cultures
        $cultures = [$this->culture, 'en', 'fr', 'es'];
        foreach ($cultures as $c) {
            if (!empty($doc['i18n'][$c][$field])) {
                return $doc['i18n'][$c][$field];
            }
        }

        return '';
    }

    /**
     * Batch resolve entity type names for hit documents.
     */
    public function resolveHitEntityTypes(array $hits): array
    {
        $ids = [];
        foreach ($hits as $doc) {
            if (!empty($doc['entityTypeId'])) {
                $ids[] = (int) $doc['entityTypeId'];
            }
        }

        if (empty($ids)) {
            return [];
        }

        return $this->resolveTermNames(array_unique($ids));
    }

    /**
     * Direct curl request to Elasticsearch.
     */
    protected function esRequest(string $endpoint, array $body): ?array
    {
        $url = sprintf('http://%s:%d/%s%s', $this->esHost, $this->esPort, $this->indexName, $endpoint);

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
                'ahgActorBrowsePlugin ES error: HTTP %d, URL: %s, Response: %s',
                $httpCode,
                $url,
                substr($response ?: '', 0, 500)
            ));

            return null;
        }

        return json_decode($response, true);
    }
}
