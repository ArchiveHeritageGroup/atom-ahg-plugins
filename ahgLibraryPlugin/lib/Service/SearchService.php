<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SearchService — advanced library catalogue search.
 *
 * Supports:
 *   - Free-text query with Lucene-style syntax (AND, OR, NOT, phrase, field:, range:)
 *   - Facet filtering (material_type, language, date range)
 *   - Relevance and date sorting
 *
 * @package ahgLibraryPlugin\Service
 */
class SearchService
{
    protected static ?SearchService $instance = null;
    protected string $culture;

    public const FIELDS = [
        'title'       => 'ioi.title',
        'author'      => 'lic.name',
        'creator'     => 'lic.name',
        'subject'     => 'sub_ti.name',
        'keyword'     => 'ioi.scope_and_content',
        'isbn'        => 'li.isbn',
        'issn'        => 'li.issn',
        'doi'         => 'li.doi',
        'lccn'        => 'li.lccn',
        'oclc'        => 'li.oclc_number',
        'publisher'   => 'li.publisher',
        'year'        => 'li.publication_date',
        'material'    => 'li.material_type',
        'mat_type'    => 'li.material_type',
        'language'    => 'li.language',
        'call_number' => 'li.call_number',
        'dewey'       => 'li.dewey_decimal',
    ];

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? (\sfContext::hasInstance()
            ? \sfContext::getInstance()->getUser()->getCulture()
            : 'en');
    }

    public static function getInstance(?string $culture = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($culture);
        }
        return self::$instance;
    }

    // ========================================================================
    // Public API
    // ========================================================================

    /**
     * Execute an advanced search.
     *
     * @param array $params
     *   - query:        string  Lucene-style query string
     *   - material_type: string  filter by material type
     *   - language:      string  filter by language
     *   - date_from:     string  earliest publication year/date
     *   - date_to:       string  latest publication year/date
     *   - publisher:     string  publisher name filter
     *   - sort:          string  relevance | year_desc | year_asc | title_asc
     *   - page:          int
     *   - limit:         int
     * @return array{results: array, total: int, query: string, facets: array}
     */
    public function advancedSearch(array $params): array
    {
        $query   = trim($params['query'] ?? '');
        $limit   = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $offset  = max(0, ((int) ($params['page'] ?? 1) - 1) * $limit);
        $sort    = $params['sort'] ?? 'relevance';

        $db = DB::connection();

        // Base query with all needed joins
        $q = $db->table('library_item as li')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('library_item_creator as lic', function ($j) {
                $j->on('li.id', '=', 'lic.library_item_id')
                  ->where('lic.is_primary', '=', 1);
            })
            ->leftJoin('object_term_relation as otl_sub', 'io.id', '=', 'otl_sub.object_id')
            ->leftJoin('term as sub_term', 'otl_sub.term_id', '=', 'sub_term.id')
            ->leftJoin('term_i18n as sub_ti', 'sub_term.id', '=', 'sub_ti.id')
            ->where('io.source_standard', 'library')
            ->select([
                'ioi.title',
                'li.id as library_item_id',
                'io.id as io_id',
                'li.isbn',
                'li.issn',
                'li.doi',
                'li.publisher',
                'li.publication_date',
                'li.material_type',
                'li.call_number',
                'li.language',
                'lic.name as primary_creator',
                DB::raw('GROUP_CONCAT(DISTINCT sub_ti.name ORDER BY sub_ti.name SEPARATOR "; ") as subjects'),
                's.slug',
            ])
            ->groupBy('li.id');

        // Apply parsed query
        if (!empty($query)) {
            $this->applyLuceneQuery($q, $query);
        }

        // Apply filter params
        if (!empty($params['material_type'])) {
            $q->where('li.material_type', $params['material_type']);
        }
        if (!empty($params['language'])) {
            $q->where('li.language', $params['language']);
        }
        if (!empty($params['date_from'])) {
            $q->where('li.publication_date', '>=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $q->where('li.publication_date', '<=', $params['date_to']);
        }
        if (!empty($params['publisher'])) {
            $q->where('li.publisher', 'LIKE', '%' . $params['publisher'] . '%');
        }

        // Sorting
        $this->applySort($q, $sort);

        // Count total (before LIMIT/OFFSET)
        $totalQ = clone $q;
        $total = $totalQ->count();

        // Paginate
        $rows = $q->limit($limit)->offset($offset)->get();

        // Build results
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->mapRow($row);
        }

        // Build facets (counts by material_type, language)
        $facets = $this->buildFacets($params);

        return [
            'results' => $results,
            'total'    => (int) $total,
            'query'    => $query,
            'facets'   => $facets,
        ];
    }

    // ========================================================================
    // Lucene-style query parser
    // ========================================================================

    /**
     * Parse and apply a Lucene-style query to the Eloquent builder.
     *
     * Supports:
     *   term1 AND term2
     *   term1 OR term2
     *   term1 NOT term2
     *   "exact phrase"
     *   field:value
     *   field:[start TO end]
     */
    protected function applyLuceneQuery($q, string $query): void
    {
        // Normalise whitespace
        $query = preg_replace('/\s+/', ' ', trim($query));

        // Strip surrounding parentheses if the whole query is wrapped
        if (preg_match('/^\((.+)\)$/', $query, $m)) {
            $query = $m[1];
        }

        // Check for field:value
        if (preg_match('/^(\w+):("?)([^()"\s]+)\2(\s+(AND|OR|NOT)\s+)?(.+)?$/i', $query, $m)) {
            $field  = $m[1];
            $value  = $m[3];
            $op     = strtoupper($m[5] ?? 'AND');
            $rest   = trim($m[6] ?? '');

            $this->applyFieldCondition($q, $field, $value, null, 'AND');

            if ($rest) {
                if ($op === 'OR') {
                    $q->orWhere(function ($qq) use ($rest) {
                        $this->applyLuceneQuery($qq, $rest);
                    });
                } elseif ($op === 'NOT') {
                    $this->applyLuceneQuery($q, $rest); // recursive for NOT
                } else {
                    $this->applyLuceneQuery($q, $rest);
                }
            }
            return;
        }

        // Range queries: field:[start TO end]
        if (preg_match('/^(\w+):\[(.+?)\s+TO\s+(.+)\]$/', $query, $m)) {
            $field = $m[1];
            $start = trim($m[2]);
            $end   = trim($m[3]);
            $this->applyFieldCondition($q, $field, null, ['start' => $start, 'end' => $end], 'AND');
            return;
        }

        // Boolean operators
        $orParts  = preg_split('/\s+OR\s+(?=(?:[^"]*"[^"]*")*[^"]*$)/i', $query);
        if (count($orParts) > 1) {
            $q->where(function ($qq) use ($orParts) {
                foreach ($orParts as $i => $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    // Remove leading NOT if present
                    if (preg_match('/^NOT\s+(.+)$/i', $part, $m)) {
                        $subPart = trim($m[1]);
                        if ($i === 0) {
                            $qq->whereNot(function ($q2) use ($subPart) {
                                $this->applyTermConditions($q2, $subPart, 'AND', true);
                            });
                        } else {
                            $qq->orWhereNot(function ($q2) use ($subPart) {
                                $this->applyTermConditions($q2, $subPart, 'AND', true);
                            });
                        }
                    } else {
                        if ($i === 0) {
                            $this->applyTermConditions($qq, $part, 'OR');
                        } else {
                            $qq->orWhere(function ($q2) use ($part) {
                                $this->applyTermConditions($q2, $part, 'OR');
                            });
                        }
                    }
                }
            });
            return;
        }

        $andParts = preg_split('/\s+AND\s+(?=(?:[^"]*"[^"]*")*[^"]*$)/i', $query);
        if (count($andParts) > 1) {
            foreach ($andParts as $part) {
                $part = trim($part);
                if (empty($part)) continue;
                if (preg_match('/^NOT\s+(.+)$/i', $part, $m)) {
                    $this->applyTermConditions($q, trim($m[1]), 'AND', true);
                } else {
                    $this->applyTermConditions($q, $part, 'AND');
                }
            }
            return;
        }

        // Single term / phrase
        $this->applyTermConditions($q, $query, 'AND');
    }

    /**
     * Apply a single term (or phrase in quotes) as WHERE conditions.
     *
     * @param bool $negate  If true, applies NOT LIKE (exclude term)
     */
    protected function applyTermConditions($q, string $term, string $conj = 'AND', bool $negate = false): void
    {
        // Phrase search: "exact phrase"
        if (preg_match('/^"(.+)"$/', $term, $m)) {
            $phrase = $m[1];
            $cond = ['ioi.title', 'LIKE', '%' . $phrase . '%'];
            $subjCond = ['sub_ti.name', 'LIKE', '%' . $phrase . '%'];

            if ($negate) {
                if ($conj === 'AND') {
                    $q->where(function ($qq) use ($cond, $subjCond) {
                        $qq->whereNot($cond[0], $cond[1], $cond[2])
                           ->whereNot($subjCond[0], $subjCond[1], $subjCond[2]);
                    });
                }
            } else {
                $q->where(function ($qq) use ($cond, $subjCond) {
                    $qq->where($cond[0], $cond[1], $cond[2])
                       ->orWhere('lic.name', 'LIKE', '%' . $phrase . '%')
                       ->orWhere($subjCond[0], $subjCond[1], $subjCond[2])
                       ->orWhere('li.isbn', 'LIKE', '%' . $phrase . '%');
                });
            }
            return;
        }

        // Negation: NOT term
        if ($negate) {
            $term = preg_replace('/^NOT\s+/i', '', $term);
            $q->where(function ($qq) use ($term) {
                $qq->whereNot('ioi.title', 'LIKE', '%' . $term . '%')
                   ->whereNot('lic.name', 'LIKE', '%' . $term . '%')
                   ->whereNot('sub_ti.name', 'LIKE', '%' . $term . '%');
            });
            return;
        }

        // Plain term search
        $like = '%' . $term . '%';
        $q->where(function ($qq) use ($like) {
            $qq->where('ioi.title', 'LIKE', $like)
               ->orWhere('ioi.scope_and_content', 'LIKE', $like)
               ->orWhere('lic.name', 'LIKE', $like)
               ->orWhere('li.isbn', 'LIKE', $like)
               ->orWhere('li.issn', 'LIKE', $like)
               ->orWhere('li.publisher', 'LIKE', $like)
               ->orWhere('sub_ti.name', 'LIKE', $like);
        });
    }

    /**
     * Apply a field-specific condition.
     */
    protected function applyFieldCondition($q, string $field, ?string $value, ?array $range, string $conj): void
    {
        $column = self::FIELDS[$field] ?? null;
        if ($column === null) {
            // Fallback: search all text fields for the field name
            if ($value !== null) {
                $this->applyTermConditions($q, $value, $conj);
            }
            return;
        }

        if ($range !== null) {
            // Range query: field:[start TO end]
            if ($conj === 'AND') {
                $q->where($column, '>=', $range['start'])
                  ->where($column, '<=', $range['end']);
            } else {
                $q->orWhere($column, '>=', $range['start'])
                  ->orWhere($column, '<=', $range['end']);
            }
            return;
        }

        if ($value === null) return;

        // Exact match for identifier fields, LIKE for others
        $exactFields = ['isbn', 'issn', 'doi', 'lccn', 'oclc'];
        if (in_array($field, $exactFields, true)) {
            if ($conj === 'AND') {
                $q->where($column, $value);
            } else {
                $q->orWhere($column, $value);
            }
        } else {
            if ($conj === 'AND') {
                $q->where($column, 'LIKE', '%' . $value . '%');
            } else {
                $q->orWhere($column, 'LIKE', '%' . $value . '%');
            }
        }
    }

    // ========================================================================
    // Sort
    // ========================================================================

    protected function applySort($q, string $sort): void
    {
        switch ($sort) {
            case 'year_desc':
                $q->orderByRaw("CAST(li.publication_date AS SIGNED) DESC NULLS LAST");
                break;
            case 'year_asc':
                $q->orderByRaw("CAST(li.publication_date AS SIGNED) ASC NULLS LAST");
                break;
            case 'title_asc':
                $q->orderBy('ioi.title', 'asc');
                break;
            case 'relevance':
            default:
                $q->orderBy('ioi.title', 'asc');
                break;
        }
    }

    // ========================================================================
    // Facets
    // ========================================================================

    protected function buildFacets(array $params): array
    {
        $db = DB::connection();

        $base = $db->table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->where('io.source_standard', 'library');

        $matTypeCounts = (clone $base)
            ->select('li.material_type', DB::raw('COUNT(*) as count'))
            ->groupBy('li.material_type')
            ->orderBy('count', 'desc')
            ->get();

        $langCounts = (clone $base)
            ->select('li.language', DB::raw('COUNT(*) as count'))
            ->whereNotNull('li.language')
            ->where('li.language', '!=', '')
            ->groupBy('li.language')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();

        return [
            'material_type' => $matTypeCounts->map(fn($r) => [
                'value' => $r->material_type,
                'label' => $r->material_type,
                'count' => (int) $r->count,
            ])->all(),
            'language' => $langCounts->map(fn($r) => [
                'value' => $r->language,
                'label' => $r->language,
                'count' => (int) $r->count,
            ])->all(),
        ];
    }

    // ========================================================================
    // Row mapping
    // ========================================================================

    protected function mapRow(object $row): array
    {
        $slug = $row->slug ?? '';
        $url = $slug
            ? (\sfConfig::get('app_relative_url_root', '') . '/library/' . $slug)
            : '';

        return [
            'title'           => $row->title ?? '',
            'url'             => $url,
            'isbn'           => $row->isbn ?? '',
            'issn'           => $row->issn ?? '',
            'doi'            => $row->doi ?? '',
            'creator'        => $row->primary_creator ?? '',
            'publisher'      => $row->publisher ?? '',
            'publication_date' => $row->publication_date ?? '',
            'material_type'   => $row->material_type ?? '',
            'language'       => $row->language ?? '',
            'call_number'    => $row->call_number ?? '',
            'subjects'       => $row->subjects ?? '',
        ];
    }
}
