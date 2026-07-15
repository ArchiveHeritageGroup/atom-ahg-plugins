<?php
/**
 * Elasticsearch 7 Search Service for ahgDisplayPlugin
 *
 * Handles indexing and searching with display-specific fields
 */

use Illuminate\Database\Capsule\Manager as DB;
use AhgDisplay\Services\DisplayService;

class DisplayElasticsearchService
{
    protected $index;
    protected $displayService;
    protected $culture;
    protected $esHost;
    protected $esPort;

    const INDEX_NAME = 'atom';

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? sfContext::getInstance()->getUser()->getCulture() ?? 'en';

        // Target the information-object OpenSearch index for this instance
        // (e.g. archive_qubitinformationobject). This stack is OpenSearch-over-curl;
        // the display fields live on the IO index, mirroring ActorBrowseService.
        $prefix = sfConfig::get('app_opensearch_index_name', '');
        if (empty($prefix)) {
            try {
                $prefix = DB::connection()->getDatabaseName();
            } catch (\Throwable $e) {
                $prefix = sfConfig::get('app_elasticsearch_index', self::INDEX_NAME);
            }
        }
        $this->index = $prefix . '_qubitinformationobject';

        $this->esHost = sfConfig::get('app_opensearch_host', 'localhost');
        $this->esPort = (int) sfConfig::get('app_opensearch_port', 9200);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayService.php';
        $this->displayService = new DisplayService();
    }

    /**
     * Direct curl request to OpenSearch. Returns decoded JSON or null on failure.
     * $body is JSON-encoded; for _bulk pass a pre-built ndjson string via esBulk().
     */
    protected function esRequest(string $method, string $path, ?array $body = null): ?array
    {
        $url = sprintf('http://%s:%d%s', $this->esHost, $this->esPort, $path);
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];
        if (null !== $body) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (false === $resp || $code < 200 || $code >= 300) {
            error_log(sprintf('DisplayES: %s %s -> HTTP %d: %s', $method, $url, $code, substr((string) $resp, 0, 300)));
            return null;
        }

        return json_decode($resp, true);
    }

    /**
     * Execute an OpenSearch _bulk request from a pre-built ndjson body.
     */
    protected function esBulk(string $ndjson): ?array
    {
        $url = sprintf('http://%s:%d/_bulk', $this->esHost, $this->esPort);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $ndjson,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-ndjson'],
            CURLOPT_TIMEOUT => 120,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (false === $resp || $code < 200 || $code >= 300) {
            error_log(sprintf('DisplayES: _bulk -> HTTP %d: %s', $code, substr((string) $resp, 0, 300)));
            return null;
        }

        return json_decode($resp, true);
    }
    
    // =========================================================================
    // MAPPING MANAGEMENT
    // =========================================================================
    
    /**
     * Add display fields to existing mapping
     * Call this after AtoM's base mapping is created
     */
    public function updateMapping(): bool
    {
        $displayMapping = require sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Elasticsearch/displayMapping.php';
        
        $response = $this->esRequest('PUT', '/' . $this->index . '/_mapping', [
            'properties' => $displayMapping,
        ]);

        return (bool) ($response['acknowledged'] ?? false);
    }
    
    /**
     * Check if display fields exist in mapping
     */
    public function hasDisplayMapping(): bool
    {
        $mapping = $this->esRequest('GET', '/' . $this->index . '/_mapping');
        $properties = $mapping[$this->index]['mappings']['properties'] ?? [];

        return isset($properties['display_object_type']);
    }
    
    // =========================================================================
    // INDEXING
    // =========================================================================
    
    /**
     * Get display data for indexing an information object
     */
    public function getIndexData(int $objectId): array
    {
        $data = [];
        
        // Get object type (archive/museum/gallery/library/dam) via the detector
        $objectType = \DisplayTypeDetector::detect($objectId);
        $data['display_object_type'] = $objectType;
        $data['display_domain'] = $objectType;

        // Get profile
        $profile = \DisplayTypeDetector::getProfile($objectId);
        $data['display_profile'] = $profile->code ?? null;
        
        // Get extended level
        $object = $this->getObjectData($objectId);
        if ($object) {
            $levelCode = $this->mapLevelToCode($object->level_name);
            $data['display_level_code'] = $levelCode;
        }
        
        // Build display nested object
        $data['display'] = $this->buildDisplayData($objectId, $object, $objectType);
        
        return $data;
    }
    
    /**
     * Build display nested data for ES
     */
    protected function buildDisplayData(int $objectId, ?object $object, string $objectType): array
    {
        if (!$object) return [];
        
        $display = [
            'identifier' => $object->identifier,
            'title' => $object->title,
            'level' => $object->level_name,
            'extent' => $object->extent_and_medium,
            'scope_content' => $object->scope_and_content,
            'description' => $object->scope_and_content,
        ];
        
        // Get creator/dates from events
        $events = $this->getEvents($objectId);
        foreach ($events as $event) {
            if ($event->type_id == QubitTerm::CREATION_ID || $event->type_id == 111) { // Creation event
                if ($event->actor_name) {
                    $display['creator'] = $event->actor_name;
                    $display['creator_keyword'] = $event->actor_name;
                    // Carry the creator's actor id so the read side can suppress
                    // draft/embargoed authority records from anonymous users.
                    $display['creator_id'] = (int) ($event->actor_id ?? 0);

                    // Map to domain-specific field
                    if ($objectType === 'gallery') {
                        $display['artist'] = $event->actor_name;
                        $display['artist_keyword'] = $event->actor_name;
                    } elseif ($objectType === 'library') {
                        $display['author'] = $event->actor_name;
                        $display['author_keyword'] = $event->actor_name;
                    } elseif ($objectType === 'dam') {
                        $display['photographer'] = $event->actor_name;
                        $display['photographer_keyword'] = $event->actor_name;
                    }
                }
                
                if ($event->date) {
                    $display['date_display'] = $event->date;
                }
                if ($event->start_date) {
                    $display['date_start'] = $event->start_date;
                }
                if ($event->end_date) {
                    $display['date_end'] = $event->end_date;
                }
            }
        }
        
        // Get digital object info
        $digitalObject = $this->getDigitalObject($objectId);
        if ($digitalObject) {
            $display['has_digital_object'] = true;
            $display['thumbnail_path'] = $this->getThumbnailPath($digitalObject);
            $display['master_path'] = $digitalObject->path;
            $display['mime_type'] = $digitalObject->mime_type;
            $display['media_type'] = $this->getMediaType($digitalObject->mime_type);
        } else {
            $display['has_digital_object'] = false;
        }
        
        // Get properties for domain-specific fields
        $properties = $this->getProperties($objectId);
        foreach ($properties as $prop) {
            $name = strtolower($prop->name);
            if (in_array($name, ['dimensions', 'medium', 'technique', 'call_number', 'isbn', 'publisher'])) {
                $display[$name] = $prop->value;
            }
        }
        
        // Get subjects/terms
        $terms = $this->getTerms($objectId);
        $display['subjects'] = [];
        $display['places'] = [];
        $display['genres'] = [];
        
        foreach ($terms as $term) {
            if ($term->taxonomy_id == QubitTaxonomy::SUBJECT_ID) {
                $display['subjects'][] = $term->name;
            } elseif ($term->taxonomy_id == QubitTaxonomy::PLACE_ID) {
                $display['places'][] = $term->name;
            } elseif ($term->taxonomy_id == QubitTaxonomy::GENRE_ID) {
                $display['genres'][] = $term->name;
            }
        }
        
        // Hierarchy info
        $display['child_count'] = $this->getChildCount($objectId);
        
        $ancestors = $this->getAncestorsData($objectId);
        if (!empty($ancestors)) {
            $display['ancestor_ids'] = array_column($ancestors, 'id');
            $display['ancestor_slugs'] = array_column($ancestors, 'slug');
            $display['parent_title'] = end($ancestors)->title ?? null;
        }
        
        // Museum-specific
        if ($objectType === 'museum') {
            $display['object_number'] = $object->identifier;
            $display['object_name'] = $object->title;
            $display['materials'] = $object->extent_and_medium;
        }
        
        // Library-specific
        if ($objectType === 'library') {
            $display['call_number'] = $properties['call_number'] ?? null;
            $display['isbn'] = $properties['isbn'] ?? null;
        }
        
        return array_filter($display, fn($v) => $v !== null && $v !== '' && $v !== []);
    }
    
    /**
     * Index a single document with display data
     */
    public function indexDocument(int $objectId, array $existingBody = []): array
    {
        $displayData = $this->getIndexData($objectId);
        return array_merge($existingBody, $displayData);
    }

    /**
     * Partial-update a single IO document's display fields via OpenSearch _update.
     */
    public function updateDocument(int $objectId, array $doc): bool
    {
        $resp = $this->esRequest('POST', '/' . $this->index . '/_update/' . $objectId, ['doc' => $doc]);

        return null !== $resp;
    }
    
    /**
     * Bulk update display data for existing index
     */
    public function reindexDisplayData(int $batchSize = 100, ?callable $progressCallback = null): int
    {
        $total = DB::table('information_object')->where('id', '>', 1)->count();
        $processed = 0;
        $offset = 0;
        
        while ($offset < $total) {
            $objects = DB::table('information_object')
                ->where('id', '>', 1)
                ->orderBy('id')
                ->offset($offset)
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();
            
            if (empty($objects)) break;
            
            $ndjson = '';
            foreach ($objects as $objectId) {
                $displayData = $this->getIndexData($objectId);
                // Partial update: adds the display.* fields to the existing IO doc,
                // leaving all base-AtoM fields untouched.
                $ndjson .= json_encode(['update' => ['_index' => $this->index, '_id' => (string) $objectId]]) . "\n";
                $ndjson .= json_encode(['doc' => $displayData, 'doc_as_upsert' => false]) . "\n";
            }

            $response = $this->esBulk($ndjson);
            if (null !== $response) {
                $processed += count($objects);
                if ($progressCallback) {
                    $progressCallback($processed, $total);
                }
            } else {
                error_log('DisplayES: Bulk update failed at offset ' . $offset);
            }
            
            $offset += $batchSize;
        }
        
        return $processed;
    }
    
    // =========================================================================
    // SEARCHING
    // =========================================================================
    
    /**
     * Search with display type filter
     */
    public function search(array $params): array
    {
        $query = $params['query'] ?? '*';
        $objectType = $params['object_type'] ?? null;
        $hasDigitalObject = $params['has_digital_object'] ?? null;
        $mediaType = $params['media_type'] ?? null;
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $subjects = $params['subjects'] ?? [];
        $places = $params['places'] ?? [];
        $sort = $params['sort'] ?? '_score';
        $from = $params['from'] ?? 0;
        $size = $params['size'] ?? 20;
        $aggregations = $params['aggregations'] ?? true;
        
        // Build query
        $must = [];
        $filter = [];
        
        // Main query
        if ($query !== '*') {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'i18n.*.title^3',
                        'display.title^3',
                        'display.creator^2',
                        'display.identifier^2',
                        'display.description',
                        'display.subjects',
                        'autocomplete',
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ];
        } else {
            $must[] = ['match_all' => new \stdClass()];
        }
        
        // Object type filter
        if ($objectType) {
            $filter[] = ['term' => ['display_object_type' => $objectType]];
        }
        
        // Digital object filter
        if ($hasDigitalObject !== null) {
            $filter[] = ['term' => ['display.has_digital_object' => $hasDigitalObject]];
        }
        
        // Media type filter
        if ($mediaType) {
            $filter[] = ['term' => ['display.media_type' => $mediaType]];
        }
        
        // Date range
        if ($dateFrom || $dateTo) {
            $dateRange = [];
            if ($dateFrom) $dateRange['gte'] = $dateFrom;
            if ($dateTo) $dateRange['lte'] = $dateTo;
            $filter[] = ['range' => ['display.date_start' => $dateRange]];
        }
        
        // Subject filter
        if (!empty($subjects)) {
            $filter[] = ['terms' => ['display.subjects' => $subjects]];
        }
        
        // Place filter
        if (!empty($places)) {
            $filter[] = ['terms' => ['display.places' => $places]];
        }
        
        // Publication status (only published)
        $filter[] = ['term' => ['publicationStatusId' => QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID]];
        
        // Build sort
        $sortArray = match($sort) {
            'title_asc' => [['display.title.keyword' => 'asc']],
            'title_desc' => [['display.title.keyword' => 'desc']],
            'date_asc' => [['display.date_start' => 'asc']],
            'date_desc' => [['display.date_start' => 'desc']],
            'identifier' => [['display.identifier' => 'asc']],
            'relevance', '_score' => [['_score' => 'desc']],
            default => [['_score' => 'desc']],
        };
        
        // Build request body
        $body = [
            'query' => [
                'bool' => [
                    'must' => $must,
                    'filter' => $filter,
                ],
            ],
            'sort' => $sortArray,
            'from' => $from,
            'size' => $size,
            '_source' => [
                'slug',
                'identifier',
                'levelOfDescriptionId',
                'publicationStatusId',
                'display_object_type',
                'display_profile',
                'display',
                'i18n',
            ],
        ];
        
        // Add aggregations
        if ($aggregations) {
            $body['aggs'] = [
                'object_types' => [
                    'terms' => ['field' => 'display_object_type', 'size' => 10],
                ],
                'media_types' => [
                    'terms' => ['field' => 'display.media_type', 'size' => 10],
                ],
                'levels' => [
                    'terms' => ['field' => 'display.level', 'size' => 20],
                ],
                'subjects' => [
                    'terms' => ['field' => 'display.subjects', 'size' => 30],
                ],
                'places' => [
                    'terms' => ['field' => 'display.places', 'size' => 30],
                ],
                'creators' => [
                    'terms' => ['field' => 'display.creator_keyword', 'size' => 20],
                ],
                'date_histogram' => [
                    'date_histogram' => [
                        'field' => 'display.date_start',
                        'calendar_interval' => 'year',
                        'format' => 'yyyy',
                        'min_doc_count' => 1,
                    ],
                ],
                'has_digital' => [
                    'terms' => ['field' => 'display.has_digital_object'],
                ],
            ];
        }
        
        $response = $this->esRequest('POST', '/' . $this->index . '/_search', $body);
        if (null === $response) {
            return [
                'total' => 0,
                'hits' => [],
                'aggregations' => [],
                'error' => 'search request failed',
            ];
        }

        return $this->formatSearchResults($response, $params);
    }
    
    /**
     * Format search results for display
     */
    protected function formatSearchResults(array $response, array $params): array
    {
        $hits = [];
        
        foreach ($response['hits']['hits'] as $hit) {
            $source = $hit['_source'];
            $display = $source['display'] ?? [];
            
            // Get title from i18n or display
            $title = $display['title'] ?? null;
            if (!$title && isset($source['i18n'])) {
                foreach ($source['i18n'] as $culture => $i18n) {
                    if (isset($i18n['title'])) {
                        $title = $i18n['title'];
                        break;
                    }
                }
            }
            
            $hits[] = [
                'id' => (int) $hit['_id'],
                'score' => $hit['_score'],
                'slug' => $source['slug'] ?? null,
                'identifier' => $source['identifier'] ?? $display['identifier'] ?? null,
                'title' => $title,
                'object_type' => $source['display_object_type'] ?? 'archive',
                'profile' => $source['display_profile'] ?? null,
                'level' => $display['level'] ?? null,
                'creator' => $display['creator'] ?? $display['artist'] ?? $display['author'] ?? null,
                'creator_id' => $display['creator_id'] ?? null,
                'date' => $display['date_display'] ?? null,
                'description' => isset($display['description']) 
                    ? substr($display['description'], 0, 300) . '...' 
                    : null,
                'has_digital_object' => $display['has_digital_object'] ?? false,
                'thumbnail_path' => $display['thumbnail_path'] ?? null,
                'media_type' => $display['media_type'] ?? null,
                'subjects' => $display['subjects'] ?? [],
                'child_count' => $display['child_count'] ?? 0,
            ];
        }
        
        // Format aggregations
        $aggregations = [];
        if (isset($response['aggregations'])) {
            foreach ($response['aggregations'] as $name => $agg) {
                if (isset($agg['buckets'])) {
                    $aggregations[$name] = array_map(fn($b) => [
                        'key' => $b['key'],
                        'count' => $b['doc_count'],
                    ], $agg['buckets']);
                }
            }
        }
        
        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'hits' => $hits,
            'aggregations' => $aggregations,
            'from' => $params['from'] ?? 0,
            'size' => $params['size'] ?? 20,
        ];
    }
    
    /**
     * Browse by object type
     */
    public function browseByType(string $objectType, array $params = []): array
    {
        $params['object_type'] = $objectType;
        $params['query'] = $params['query'] ?? '*';
        return $this->search($params);
    }
    
    /**
     * Get facet counts only
     */
    public function getFacets(array $filters = []): array
    {
        $params = array_merge($filters, [
            'size' => 0,
            'aggregations' => true,
        ]);
        
        $result = $this->search($params);
        return $result['aggregations'] ?? [];
    }
    
    /**
     * Autocomplete search
     */
    public function autocomplete(string $query, int $size = 10): array
    {
        $body = [
            'query' => [
                'bool' => [
                    'must' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'autocomplete^3',
                                'display.title^2',
                                'display.identifier',
                            ],
                            'type' => 'phrase_prefix',
                        ],
                    ],
                    'filter' => [
                        ['term' => ['publicationStatusId' => QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID]],
                    ],
                ],
            ],
            'size' => $size,
            '_source' => ['slug', 'display.title', 'display.identifier', 'display_object_type'],
        ];
        
        $response = $this->esRequest('POST', '/' . $this->index . '/_search', $body);
        if (null === $response || !isset($response['hits']['hits'])) {
            return [];
        }

        return array_map(fn($hit) => [
            'id' => $hit['_id'],
            'slug' => $hit['_source']['slug'] ?? null,
            'title' => $hit['_source']['display']['title'] ?? 'Untitled',
            'identifier' => $hit['_source']['display']['identifier'] ?? null,
            'type' => $hit['_source']['display_object_type'] ?? 'archive',
        ], $response['hits']['hits']);
    }
    
    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    protected function getObjectData(int $objectId): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n as lod', function($j) {
                $j->on('io.level_of_description_id', '=', 'lod.id')->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $objectId)
            ->select(
                'io.*',
                'i18n.title', 'i18n.extent_and_medium', 'i18n.scope_and_content',
                'lod.name as level_name',
                'slug.slug'
            )
            ->first();
    }
    
    protected function getEvents(int $objectId): array
    {
        return DB::table('event as e')
            ->leftJoin('event_i18n as ei', function($j) {
                $j->on('e.id', '=', 'ei.id')->where('ei.culture', '=', $this->culture);
            })
            ->leftJoin('actor_i18n as ai', function($j) {
                $j->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', $this->culture);
            })
            ->where('e.object_id', $objectId)
            ->select('e.*', 'ei.date', 'ai.authorized_form_of_name as actor_name')
            ->get()->toArray();
    }
    
    protected function getDigitalObject(int $objectId): ?object
    {
        return DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();
    }
    
    protected function getThumbnailPath(?object $digitalObject): ?string
    {
        if (!$digitalObject) return null;
        
        // Check for thumbnail derivative
        $thumb = DB::table('digital_object')
            ->where('parent_id', $digitalObject->id)
            ->where('usage_id', QubitTerm::THUMBNAIL_ID)
            ->first();
        
        return $thumb->path ?? $digitalObject->path;
    }
    
    protected function getMediaType(?string $mimeType): string
    {
        if (!$mimeType) return 'unknown';
        
        return match(true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            $mimeType === 'application/pdf' => 'document',
            str_contains($mimeType, 'document') || str_contains($mimeType, 'word') => 'document',
            str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel') => 'document',
            default => 'other',
        };
    }
    
    protected function getProperties(int $objectId): array
    {
        $result = [];
        $props = DB::table('property as p')
            ->leftJoin('property_i18n as pi', function($j) {
                $j->on('p.id', '=', 'pi.id')->where('pi.culture', '=', $this->culture);
            })
            ->where('p.object_id', $objectId)
            ->select('p.name', 'pi.value')
            ->get()->toArray();
        
        foreach ($props as $p) {
            $result[strtolower($p->name)] = $p->value;
        }
        return $result;
    }
    
    protected function getTerms(int $objectId): array
    {
        return DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', $objectId)
            ->select('t.taxonomy_id', 'ti.name')
            ->get()->toArray();
    }
    
    protected function getChildCount(int $objectId): int
    {
        return DB::table('information_object')
            ->where('parent_id', $objectId)
            ->count();
    }

    /**
     * Ancestor chain (root-first) for hierarchy facets: [{id, slug, title}, ...].
     * Walks parent_id up to the root; capped to avoid pathological loops.
     */
    protected function getAncestorsData(int $objectId): array
    {
        $ancestors = [];
        $guard = 0;
        $current = (int) (DB::table('information_object')->where('id', $objectId)->value('parent_id') ?? 0);

        while ($current > 1 && $guard++ < 50) {
            $row = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as i', function ($j) {
                    $j->on('io.id', '=', 'i.id')->where('i.culture', '=', $this->culture);
                })
                ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
                ->where('io.id', $current)
                ->select('io.id', 'io.parent_id', 's.slug', 'i.title')
                ->first();
            if (!$row) {
                break;
            }
            $ancestors[] = (object) ['id' => (int) $row->id, 'slug' => $row->slug, 'title' => $row->title];
            $current = (int) ($row->parent_id ?? 0);
        }

        return array_reverse($ancestors); // root-first
    }
    
    protected function mapLevelToCode(?string $levelName): ?string
    {
        if (!$levelName) return null;
        
        $levelName = strtolower($levelName);
        
        // Try to find in display_level table
        $level = DB::table('display_level_i18n')
            ->whereRaw('LOWER(name) = ?', [$levelName])
            ->first();
        
        if ($level) {
            $code = DB::table('display_level')
                ->where('id', $level->id)
                ->value('code');
            return $code;
        }
        
        // Fallback: convert name to code
        return str_replace(' ', '_', $levelName);
    }
}
