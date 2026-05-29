<?php

declare(strict_types=1);

/**
 * OpacService
 *
 * Online Public Access Catalog — search, availability, patron self-service.
 * Powers the public-facing OPAC interface for library patrons.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class OpacService
{
    protected static ?OpacService $instance = null;
    protected Logger $logger;

    /** True when the last search() used Elasticsearch relevance (vs MySQL LIKE). */
    public bool $esMode = false;

    public function __construct()
    {
        $this->initLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.opac');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // CATALOG SEARCH
    // ========================================================================

    /**
     * Search the library catalog (public-safe).
     *
     * Supports keyword, title, author, subject, ISBN, call number searches.
     * When $params['frbr_cluster'] is true, results are grouped by FRBR
     * work to show one representative card per work with all manifestations.
     *
     * @param array $params
     *   q, search_type, material_type, publication_year, sort, page, limit,
     *   frbr_cluster (bool, default true)
     * @return array
     *   items        : flat list of items (frbr_cluster=false)
     *   clusters     : work-clustered list (frbr_cluster=true)
     *   total        : result count
     *   total_works  : number of distinct works (frbr_cluster=true)
     *   page, pages  : pagination info
     */
    public function search(array $params = []): array
    {
        $useFrbr = ($params['frbr_cluster'] ?? true);

        $query = DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', function ($j) {
                $j->on('io.id', '=', 'slug.object_id');
            })
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')
                    ->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', '=', 160); // Published only

        $searchType = $params['search_type'] ?? 'keyword';
        $q = $params['q'] ?? '';

        // ES-backed relevance over AtoM's EXISTING information-object index
        // (reuses the library_* fields LibraryService::updateSearchIndex()
        // upserts). When ES is unavailable or errors, esRelevanceIds() returns
        // null and we fall straight through to the MySQL LIKE search below.
        $this->esMode = false;
        $esOrderIds = [];
        if (!empty($q) && in_array($searchType, ['keyword', 'title', 'author', 'subject'], true)) {
            $esIds = $this->esRelevanceIds((string) $q);
            // Only take the ES path when it actually matched something — an
            // empty/null result means ES is unavailable OR added no recall, so
            // we fall through to the MySQL LIKE search (better recall on small
            // catalogues). The library_item join below keeps results to books.
            if (is_array($esIds) && count($esIds) > 0) {
                $this->esMode = true;
                $esOrderIds = $esIds;
                $query->whereIn('io.id', $esIds);
            }
        }

        if (!empty($q) && !$this->esMode) {
            $like = '%' . $q . '%';

            switch ($searchType) {
                case 'title':
                    $query->where('ioi.title', 'LIKE', $like);
                    break;

                case 'author':
                    $query->where(function ($qb) use ($like) {
                        $qb->whereExists(function ($sub) use ($like) {
                            $sub->select(DB::raw(1))
                                ->from('library_item_creator')
                                ->whereColumn('library_item_creator.library_item_id', 'li.id')
                                ->where('library_item_creator.name', 'LIKE', $like);
                        });
                    });
                    break;

                case 'subject':
                    $query->where(function ($qb) use ($like) {
                        $qb->whereExists(function ($sub) use ($like) {
                            $sub->select(DB::raw(1))
                                ->from('library_item_subject')
                                ->whereColumn('library_item_subject.library_item_id', 'li.id')
                                ->where('library_item_subject.heading', 'LIKE', $like);
                        });
                    });
                    break;

                case 'isbn':
                    $cleaned = preg_replace('/[^0-9X]/', '', strtoupper($q));
                    $query->where('li.isbn', $cleaned);
                    break;

                case 'call_number':
                    $query->where('li.call_number', 'LIKE', $like);
                    break;

                case 'keyword':
                default:
                    $query->where(function ($qb) use ($like) {
                        $qb->where('ioi.title', 'LIKE', $like)
                            ->orWhere('li.isbn', 'LIKE', $like)
                            ->orWhere('li.issn', 'LIKE', $like)
                            ->orWhere('li.call_number', 'LIKE', $like)
                            ->orWhere('li.publisher', 'LIKE', $like)
                            ->orWhere('li.series_title', 'LIKE', $like)
                            ->orWhereExists(function ($sub) use ($like) {
                                $sub->select(DB::raw(1))
                                    ->from('library_item_creator')
                                    ->whereColumn('library_item_creator.library_item_id', 'li.id')
                                    ->where('library_item_creator.name', 'LIKE', $like);
                            })
                            ->orWhereExists(function ($sub) use ($like) {
                                $sub->select(DB::raw(1))
                                    ->from('library_item_subject')
                                    ->whereColumn('library_item_subject.library_item_id', 'li.id')
                                    ->where('library_item_subject.heading', 'LIKE', $like);
                            });
                    });
                    break;
            }
        }

        // Facet filters
        if (!empty($params['material_type'])) {
            $query->where('li.material_type', $params['material_type']);
        }

        if (!empty($params['classification_scheme'])) {
            $query->where('li.classification_scheme', $params['classification_scheme']);
        }

        if (!empty($params['publication_year'])) {
            $query->where('li.publication_date', 'LIKE', $params['publication_year'] . '%');
        }

        if (!empty($params['language'])) {
            $query->where('li.language', $params['language']);
        }

        if (!empty($params['publisher'])) {
            $query->where('li.publisher', $params['publisher']);
        }

        if (!empty($params['availability'])) {
            $query->where('li.circulation_status', $params['availability']);
        }

        if (!empty($params['creator'])) {
            $creatorName = $params['creator'];
            $query->whereExists(function ($sub) use ($creatorName) {
                $sub->select(DB::raw(1))
                    ->from('library_item_creator')
                    ->whereColumn('library_item_creator.library_item_id', 'li.id')
                    ->where('library_item_creator.name', $creatorName);
            });
        }

        // Count before pagination
        $total = $query->count();

        $page = max(1, (int) ($params['page'] ?? 1));
        $defaultLimit = (int) $this->getLibrarySetting('opac_results_per_page', '20');
        $limit = min(50, max(1, (int) ($params['limit'] ?? $defaultLimit)));

        // Sort
        $sort = $params['sort'] ?? 'relevance';
        switch ($sort) {
            case 'title':
                $query->orderBy('ioi.title');
                break;
            case 'date_desc':
                $query->orderBy('li.publication_date', 'desc');
                break;
            case 'date_asc':
                $query->orderBy('li.publication_date', 'asc');
                break;
            case 'call_number':
                $query->orderBy('li.call_number');
                break;
            default:
                // Relevance: preserve ES hit order when ES drove the search;
                // otherwise fall back to title sort.
                if ($this->esMode && !empty($esOrderIds)) {
                    $query->orderByRaw('FIELD(io.id, ' . implode(',', array_map('intval', $esOrderIds)) . ')');
                } else {
                    $query->orderBy('ioi.title');
                }
                break;
        }

        $rows = $query->select([
                'li.id',
                'li.information_object_id',
                'li.material_type',
                'li.frbr_work_key',
                'li.call_number',
                'li.isbn',
                'li.issn',
                'li.publisher',
                'li.publication_date',
                'li.edition',
                'li.total_copies',
                'li.available_copies',
                'li.circulation_status',
                'li.series_title',
                'li.cover_url',
                'ioi.title',
                'slug.slug',
            ])
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->all();

        // Enrich with primary creator
        foreach ($rows as &$row) {
            $creator = DB::table('library_item_creator')
                ->where('library_item_id', $row->id)
                ->where('is_primary', 1)
                ->value('name');
            $row->primary_creator = $creator;
        }

        // FRBR clustering
        if ($useFrbr) {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/FrbrService.php';

            $frbrSvc = FrbrService::getInstance();
            $clusters = $frbrSvc->clusterSearchResults($rows);

            return [
                'clusters'    => $clusters,
                'items'       => $rows, // Keep for non-FRBR callers
                'total'       => $total,
                'total_works' => count($clusters),
                'page'        => $page,
                'pages'       => (int) ceil($total / $limit),
                'es_mode'     => $this->esMode,
            ];
        }

        return [
            'items'   => $rows,
            'total'   => $total,
            'page'    => $page,
            'pages'   => (int) ceil($total / $limit),
            'es_mode' => $this->esMode,
        ];
    }

    /**
     * Query AtoM's existing information-object Elasticsearch index for the
     * free-text query and return the matched information_object ids in
     * relevance order. Restricted to library items via the library_material_type
     * field (upserted by LibraryService::updateSearchIndex()).
     *
     * Reuses AtoM's own ES client + index — no dedicated library index. Returns
     * null when ES is unavailable or errors, signalling the MySQL fallback.
     *
     * @return int[]|null
     */
    private function esRelevanceIds(string $q, int $cap = 500): ?array
    {
        if (!class_exists('arElasticSearchPlugin')) {
            return null;
        }

        try {
            $es     = \arElasticSearchPlugin::getInstance();
            $client = $es->client;
            $index  = $es->index->getName();

            $response = $client->search([
                'index' => $index,
                'body'  => [
                    'size'    => $cap,
                    '_source' => false,
                    // Relevance over the IO index. The library_* fields (upserted
                    // by LibraryService::updateSearchIndex) boost catalogue
                    // matches where present; i18n title carries the rest. We do
                    // NOT filter to library docs here — the caller's SQL join to
                    // library_item restricts results to books, so this stays
                    // correct even on instances where library_* aren't indexed.
                    'query'   => [
                        'multi_match' => [
                            'query'     => $q,
                            'fields'    => [
                                'i18n.en.title^4',
                                'i18n.*.title^2',
                                'library_isbn^3',
                                'library_issn^3',
                                'library_creators^3',
                                'library_primary_creator^2',
                                'library_publisher^2',
                                'library_series_title^2',
                                'library_call_number^2',
                                'library_subjects',
                            ],
                            'type'      => 'best_fields',
                            'fuzziness' => 'AUTO',
                            'lenient'   => true,
                        ],
                    ],
                ],
            ]);

            $hits = $response['hits']['hits'] ?? [];
            $ids  = [];
            foreach ($hits as $hit) {
                $id = (int) ($hit['_id'] ?? 0);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }

            return $ids;
        } catch (\Throwable $e) {
            $this->logger->warning('OPAC ES relevance failed; falling back to MySQL', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ========================================================================
    // ITEM DETAIL (PUBLIC)
    // ========================================================================

    /**
     * Get full catalog record for public display.
     */
    public function getItemDetail(int $libraryItemId): ?array
    {
        $item = DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', function ($j) {
                $j->on('io.id', '=', 'slug.object_id');
            })
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')
                    ->where('pub_st.type_id', '=', 158);
            })
            ->where('li.id', $libraryItemId)
            ->where('pub_st.status_id', 160)
            ->select(['li.*', 'ioi.title', 'ioi.scope_and_content', 'slug.slug'])
            ->first();

        if (!$item) {
            return null;
        }

        // Creators
        $creators = DB::table('library_item_creator')
            ->where('library_item_id', $libraryItemId)
            ->orderBy('sort_order')
            ->get()
            ->all();

        // Subjects
        $subjects = DB::table('library_item_subject')
            ->where('library_item_id', $libraryItemId)
            ->orderBy('sort_order')
            ->get()
            ->all();

        // Copies with availability
        $copies = DB::table('library_copy')
            ->where('library_item_id', $libraryItemId)
            ->whereNotIn('copy_status', ['withdrawn'])
            ->select(['id', 'barcode', 'copy_number', 'copy_status', 'location', 'condition_note'])
            ->orderBy('copy_number')
            ->get()
            ->all();

        // Pending holds count
        $holdCount = DB::table('library_hold')
            ->where('library_item_id', $libraryItemId)
            ->whereIn('hold_status', ['pending', 'ready'])
            ->count();

        return [
            'item'       => $item,
            'creators'   => $creators,
            'subjects'   => $subjects,
            'copies'     => $copies,
            'hold_count' => $holdCount,
        ];
    }

    // ========================================================================
    // FACETS
    // ========================================================================

    /**
     * Get facet counts for search refinement.
     */
    public function getFacets(): array
    {
        $materialTypes = DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')
                    ->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160)
            ->select('li.material_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('li.material_type')
            ->orderBy('cnt', 'desc')
            ->get()
            ->all();

        $years = DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')
                    ->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160)
            ->whereNotNull('li.publication_date')
            ->select(DB::raw('LEFT(li.publication_date, 4) as year'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->limit(20)
            ->get()
            ->all();

        // Published-only base query helper for the remaining facet dimensions.
        $base = function () {
            return DB::table('library_item as li')
                ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
                ->join('status as pub_st', function ($j) {
                    $j->on('io.id', '=', 'pub_st.object_id')->where('pub_st.type_id', '=', 158);
                })
                ->where('pub_st.status_id', 160);
        };

        $languages = $base()
            ->whereNotNull('li.language')->where('li.language', '!=', '')
            ->select('li.language', DB::raw('COUNT(*) as cnt'))
            ->groupBy('li.language')->orderBy('cnt', 'desc')->limit(20)
            ->get()->all();

        $publishers = $base()
            ->whereNotNull('li.publisher')->where('li.publisher', '!=', '')
            ->select('li.publisher', DB::raw('COUNT(*) as cnt'))
            ->groupBy('li.publisher')->orderBy('cnt', 'desc')->limit(20)
            ->get()->all();

        $availability = $base()
            ->whereNotNull('li.circulation_status')
            ->select('li.circulation_status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('li.circulation_status')->orderBy('cnt', 'desc')
            ->get()->all();

        $creators = $base()
            ->join('library_item_creator as lic', 'lic.library_item_id', '=', 'li.id')
            ->whereNotNull('lic.name')->where('lic.name', '!=', '')
            ->select('lic.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('lic.name')->orderBy('cnt', 'desc')->limit(20)
            ->get()->all();

        return [
            'material_types'    => $materialTypes,
            'publication_years' => $years,
            'languages'         => $languages,
            'publishers'        => $publishers,
            'availability'      => $availability,
            'creators'          => $creators,
        ];
    }

    // ========================================================================
    // PATRON SELF-SERVICE (requires authenticated patron)
    // ========================================================================

    /**
     * Get patron's account summary for OPAC "My Account" page.
     */
    public function getPatronAccount(int $patronId): array
    {
        $patronService = PatronService::getInstance();
        $fineService = FineService::getInstance();

        return [
            'patron'    => $patronService->find($patronId),
            'checkouts' => $patronService->getCheckouts($patronId),
            'holds'     => $patronService->getHolds($patronId),
            'fines'     => $fineService->getPatronFines($patronId),
            'balance'   => $fineService->getPatronBalance($patronId),
        ];
    }

    // ========================================================================
    // NEW ARRIVALS / POPULAR
    // ========================================================================

    /**
     * Get recently added items.
     */
    public function getNewArrivals(int $limit = 0): array
    {
        if ($limit <= 0) {
            $limit = (int) $this->getLibrarySetting('opac_new_arrivals_count', '10');
        }
        return DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', function ($j) {
                $j->on('io.id', '=', 'slug.object_id');
            })
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')
                    ->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160)
            ->select([
                'li.id', 'li.material_type', 'li.call_number', 'li.isbn',
                'li.publisher', 'li.publication_date', 'li.cover_url',
                'li.available_copies', 'li.total_copies',
                'ioi.title', 'slug.slug',
            ])
            ->orderBy('li.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Get most checked out items (popular).
     */
    public function getPopular(int $limit = 0, int $days = 0): array
    {
        if ($limit <= 0) {
            $limit = (int) $this->getLibrarySetting('opac_popular_days', '10');
        }
        if ($days <= 0) {
            $days = 90;
        }
        $since = date('Y-m-d', strtotime('-' . $days . ' days'));

        return DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', function ($j) {
                $j->on('io.id', '=', 'slug.object_id');
            })
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')
                    ->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160)
            ->where('c.checkout_date', '>=', $since)
            ->select([
                'li.id', 'li.material_type', 'li.call_number', 'li.isbn',
                'li.cover_url', 'li.available_copies',
                'ioi.title', 'slug.slug',
                DB::raw('COUNT(c.id) as checkout_count'),
            ])
            ->groupBy('li.id', 'li.material_type', 'li.call_number', 'li.isbn',
                'li.cover_url', 'li.available_copies',
                'ioi.title', 'slug.slug')
            ->orderBy('checkout_count', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    protected function getLibrarySetting(string $key, string $default = ''): string
    {
        try {
            $val = DB::table('library_settings')->where('setting_key', $key)->value('setting_value');
            return $val !== null ? $val : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}