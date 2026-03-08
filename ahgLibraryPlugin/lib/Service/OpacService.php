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
     * Returns results with availability info.
     */
    public function search(array $params = []): array
    {
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

        if (!empty($q)) {
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

        // Count before pagination
        $total = $query->count();

        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(50, max(1, (int) ($params['limit'] ?? 20)));

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
                $query->orderBy('ioi.title');
                break;
        }

        $rows = $query->select([
                'li.id',
                'li.information_object_id',
                'li.material_type',
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

        return [
            'items' => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ];
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

        return [
            'material_types' => $materialTypes,
            'publication_years' => $years,
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
    public function getNewArrivals(int $limit = 10): array
    {
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
    public function getPopular(int $limit = 10, int $days = 90): array
    {
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
}
