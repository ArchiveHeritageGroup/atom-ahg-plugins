<?php

namespace AhgDisplay\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Computes dynamic facet counts using disjunctive faceting.
 *
 * When facet filters are active, each facet's count applies ALL other active
 * filters except its own. This ensures clicking a facet with "9 items" actually
 * returns 9 results, because the count already accounts for all other selections.
 */
class DynamicFacetService
{
    private bool $isAuthenticated;
    private ?string $typeFilter;
    private ?string $parentId;
    private string $topLevelOnly;
    private ?string $hasDigital;
    private ?string $creatorFilter;
    private ?string $subjectFilter;
    private ?string $placeFilter;
    private ?string $genreFilter;
    private ?string $levelFilter;
    private ?string $mediaFilter;
    private ?string $repoFilter;
    private ?string $queryFilter;
    private ?array $queryFilterTerms;
    private ?string $titleFilter;
    private ?string $identifierFilter;
    private ?string $referenceCodeFilter;
    private ?string $scopeAndContentFilter;
    private ?string $extentAndMediumFilter;
    private ?string $archivalHistoryFilter;
    private ?string $acquisitionFilter;
    private ?string $creatorSearchFilter;
    private ?string $subjectSearchFilter;
    private ?string $placeSearchFilter;
    private ?string $genreSearchFilter;
    private ?string $startDateFilter;
    private ?string $endDateFilter;
    private string $rangeTypeFilter;

    /** @var bool|null Cached fulltext availability */
    private static ?bool $fulltextAvailable = null;

    public function __construct(array $filters)
    {
        $this->isAuthenticated = $filters['isAuthenticated'] ?? false;
        $this->typeFilter = $filters['typeFilter'] ?? null;
        $this->parentId = $filters['parentId'] ?? null;
        $this->topLevelOnly = $filters['topLevelOnly'] ?? '1';
        $this->hasDigital = $filters['hasDigital'] ?? null;
        $this->creatorFilter = $filters['creatorFilter'] ?? null;
        $this->subjectFilter = $filters['subjectFilter'] ?? null;
        $this->placeFilter = $filters['placeFilter'] ?? null;
        $this->genreFilter = $filters['genreFilter'] ?? null;
        $this->levelFilter = $filters['levelFilter'] ?? null;
        $this->mediaFilter = $filters['mediaFilter'] ?? null;
        $this->repoFilter = $filters['repoFilter'] ?? null;
        $this->queryFilter = $filters['queryFilter'] ?? null;
        $this->queryFilterTerms = $filters['queryFilterTerms'] ?? null;
        $this->titleFilter = $filters['titleFilter'] ?? null;
        $this->identifierFilter = $filters['identifierFilter'] ?? null;
        $this->referenceCodeFilter = $filters['referenceCodeFilter'] ?? null;
        $this->scopeAndContentFilter = $filters['scopeAndContentFilter'] ?? null;
        $this->extentAndMediumFilter = $filters['extentAndMediumFilter'] ?? null;
        $this->archivalHistoryFilter = $filters['archivalHistoryFilter'] ?? null;
        $this->acquisitionFilter = $filters['acquisitionFilter'] ?? null;
        $this->creatorSearchFilter = $filters['creatorSearchFilter'] ?? null;
        $this->subjectSearchFilter = $filters['subjectSearchFilter'] ?? null;
        $this->placeSearchFilter = $filters['placeSearchFilter'] ?? null;
        $this->genreSearchFilter = $filters['genreSearchFilter'] ?? null;
        $this->startDateFilter = $filters['startDateFilter'] ?? null;
        $this->endDateFilter = $filters['endDateFilter'] ?? null;
        $this->rangeTypeFilter = $filters['rangeTypeFilter'] ?? 'inclusive';
    }

    /**
     * Returns true if any of the 8 facet ID filters are active.
     */
    public function hasActiveFacetFilters(): bool
    {
        return $this->creatorFilter
            || $this->subjectFilter
            || $this->placeFilter
            || $this->genreFilter
            || $this->levelFilter
            || $this->mediaFilter
            || $this->repoFilter
            || $this->typeFilter;
    }

    /**
     * Get dynamic facet counts for a specific facet type.
     * Uses disjunctive faceting: excludes the named facet's own filter.
     *
     * @param string $facetType One of: creator, subject, place, genre, level, repository, glam_type, media_type
     * @return array Array of stdClass objects with id/name/count (or object_type/media_type/count)
     */
    public function getFacetCounts(string $facetType): array
    {
        switch ($facetType) {
            case 'creator':
                return $this->getCreatorCounts();
            case 'subject':
                return $this->getSubjectCounts();
            case 'place':
                return $this->getPlaceCounts();
            case 'genre':
                return $this->getGenreCounts();
            case 'level':
                return $this->getLevelCounts();
            case 'repository':
                return $this->getRepositoryCounts();
            case 'glam_type':
                return $this->getGlamTypeCounts();
            case 'media_type':
                return $this->getMediaTypeCounts();
            default:
                return [];
        }
    }

    /**
     * Build a base query on information_object with all filters applied
     * EXCEPT the one named by $excludeFacet.
     *
     * @param string $excludeFacet Facet to exclude from filtering
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildBaseQuery(string $excludeFacet)
    {
        $query = DB::table('information_object as io')
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->where('io.id', '>', 1);

        // Publication status filter for guests
        if (!$this->isAuthenticated) {
            $query->join('status as pub_st', function ($j) {
                $j->on('pub_st.object_id', '=', 'io.id')
                  ->where('pub_st.type_id', '=', 158)
                  ->where('pub_st.status_id', '=', 160);
            });
        }

        // Structural filters (always applied)
        if ($this->parentId) {
            $query->where('io.parent_id', $this->parentId);
        } elseif ($this->topLevelOnly === '1') {
            $query->where('io.parent_id', 1);
        }

        if ($this->hasDigital) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('digital_object')
                  ->whereRaw('digital_object.object_id = io.id')
                  ->whereNull('digital_object.parent_id');
            });
        }

        // Facet ID filters — skip the excluded one
        if ($excludeFacet !== 'glam_type' && $this->typeFilter) {
            $query->where('doc.object_type', $this->typeFilter);
        }

        if ($excludeFacet !== 'creator' && $this->creatorFilter) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('event')
                  ->whereRaw('event.object_id = io.id')
                  ->where('event.actor_id', $this->creatorFilter);
            });
        }

        if ($excludeFacet !== 'subject' && $this->subjectFilter) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('object_term_relation')
                  ->whereRaw('object_term_relation.object_id = io.id')
                  ->where('object_term_relation.term_id', $this->subjectFilter);
            });
        }

        if ($excludeFacet !== 'place' && $this->placeFilter) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('object_term_relation')
                  ->whereRaw('object_term_relation.object_id = io.id')
                  ->where('object_term_relation.term_id', $this->placeFilter);
            });
        }

        if ($excludeFacet !== 'genre' && $this->genreFilter) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('object_term_relation')
                  ->whereRaw('object_term_relation.object_id = io.id')
                  ->where('object_term_relation.term_id', $this->genreFilter);
            });
        }

        if ($excludeFacet !== 'level' && $this->levelFilter) {
            $query->where('io.level_of_description_id', $this->levelFilter);
        }

        if ($excludeFacet !== 'media_type' && $this->mediaFilter) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('digital_object')
                  ->whereRaw('digital_object.object_id = io.id')
                  ->whereNull('digital_object.parent_id')
                  ->whereRaw("digital_object.mime_type LIKE ?", [$this->mediaFilter . '/%']);
            });
        }

        if ($excludeFacet !== 'repository' && $this->repoFilter) {
            $query->where('io.repository_id', $this->repoFilter);
        }

        // Text search filters (always applied — never excluded)
        $this->applyTextFilters($query);

        // Date range filters (always applied)
        $this->applyDateFilters($query);

        return $query;
    }

    /**
     * Apply all text search filters to a query.
     * These are structural filters applied to ALL facets (never excluded).
     */
    private function applyTextFilters($query): void
    {
        if ($this->queryFilter) {
            if ($this->queryFilterTerms) {
                $this->applyTextSearchFilter($query, $this->queryFilterTerms);
            } else {
                $this->applyTextSearchFilter($query, $this->queryFilter);
            }
        }

        if ($this->titleFilter) {
            $titleFilter = $this->titleFilter;
            $query->whereExists(function ($sub) use ($titleFilter) {
                $sub->select(DB::raw(1))
                    ->from('information_object_i18n as ioi')
                    ->whereRaw('ioi.id = io.id')
                    ->where('ioi.title', 'like', '%' . $titleFilter . '%');
            });
        }

        if ($this->identifierFilter) {
            $query->where('io.identifier', 'like', '%' . $this->identifierFilter . '%');
        }

        if ($this->referenceCodeFilter) {
            $query->where('io.identifier', 'like', '%' . $this->referenceCodeFilter . '%');
        }

        if ($this->scopeAndContentFilter) {
            $scopeFilter = $this->scopeAndContentFilter;
            $query->whereExists(function ($sub) use ($scopeFilter) {
                $sub->select(DB::raw(1))
                    ->from('information_object_i18n as ioi')
                    ->whereRaw('ioi.id = io.id')
                    ->where('ioi.scope_and_content', 'like', '%' . $scopeFilter . '%');
            });
        }

        if ($this->extentAndMediumFilter) {
            $extentFilter = $this->extentAndMediumFilter;
            $query->whereExists(function ($sub) use ($extentFilter) {
                $sub->select(DB::raw(1))
                    ->from('information_object_i18n as ioi')
                    ->whereRaw('ioi.id = io.id')
                    ->where('ioi.extent_and_medium', 'like', '%' . $extentFilter . '%');
            });
        }

        if ($this->archivalHistoryFilter) {
            $archivalFilter = $this->archivalHistoryFilter;
            $query->whereExists(function ($sub) use ($archivalFilter) {
                $sub->select(DB::raw(1))
                    ->from('information_object_i18n as ioi')
                    ->whereRaw('ioi.id = io.id')
                    ->where('ioi.archival_history', 'like', '%' . $archivalFilter . '%');
            });
        }

        if ($this->acquisitionFilter) {
            $acquisitionFilter = $this->acquisitionFilter;
            $query->whereExists(function ($sub) use ($acquisitionFilter) {
                $sub->select(DB::raw(1))
                    ->from('information_object_i18n as ioi')
                    ->whereRaw('ioi.id = io.id')
                    ->where('ioi.acquisition', 'like', '%' . $acquisitionFilter . '%');
            });
        }

        if ($this->creatorSearchFilter) {
            $creatorSearch = $this->creatorSearchFilter;
            $query->whereExists(function ($sub) use ($creatorSearch) {
                $sub->select(DB::raw(1))
                    ->from('event as e')
                    ->join('actor_i18n as ai', function ($j) {
                        $j->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                    })
                    ->whereRaw('e.object_id = io.id')
                    ->where('ai.authorized_form_of_name', 'like', '%' . $creatorSearch . '%');
            });
        }

        if ($this->subjectSearchFilter) {
            $subjectSearch = $this->subjectSearchFilter;
            $query->whereExists(function ($sub) use ($subjectSearch) {
                $sub->select(DB::raw(1))
                    ->from('object_term_relation as otr')
                    ->join('term as t', 'otr.term_id', '=', 't.id')
                    ->join('term_i18n as ti', function ($j) {
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
                    })
                    ->whereRaw('otr.object_id = io.id')
                    ->where('t.taxonomy_id', 35)
                    ->where('ti.name', 'like', '%' . $subjectSearch . '%');
            });
        }

        if ($this->placeSearchFilter) {
            $placeSearch = $this->placeSearchFilter;
            $query->whereExists(function ($sub) use ($placeSearch) {
                $sub->select(DB::raw(1))
                    ->from('object_term_relation as otr')
                    ->join('term as t', 'otr.term_id', '=', 't.id')
                    ->join('term_i18n as ti', function ($j) {
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
                    })
                    ->whereRaw('otr.object_id = io.id')
                    ->where('t.taxonomy_id', 42)
                    ->where('ti.name', 'like', '%' . $placeSearch . '%');
            });
        }

        if ($this->genreSearchFilter) {
            $genreSearch = $this->genreSearchFilter;
            $query->whereExists(function ($sub) use ($genreSearch) {
                $sub->select(DB::raw(1))
                    ->from('object_term_relation as otr')
                    ->join('term as t', 'otr.term_id', '=', 't.id')
                    ->join('term_i18n as ti', function ($j) {
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
                    })
                    ->whereRaw('otr.object_id = io.id')
                    ->where('t.taxonomy_id', 78)
                    ->where('ti.name', 'like', '%' . $genreSearch . '%');
            });
        }
    }

    /**
     * Apply date range filters to a query.
     */
    private function applyDateFilters($query): void
    {
        if (!$this->startDateFilter && !$this->endDateFilter) {
            return;
        }

        $startDate = $this->startDateFilter;
        $endDate = $this->endDateFilter;
        $rangeType = $this->rangeTypeFilter;

        $query->whereExists(function ($sub) use ($startDate, $endDate, $rangeType) {
            $sub->select(DB::raw(1))
                ->from('event as evt_date')
                ->whereRaw('evt_date.object_id = io.id');

            if ($rangeType === 'exact') {
                if ($startDate) {
                    $sub->where('evt_date.start_date', '>=', $startDate);
                }
                if ($endDate) {
                    $sub->where('evt_date.end_date', '<=', $endDate);
                }
            } else {
                if ($startDate) {
                    $sub->where(function ($q) use ($startDate) {
                        $q->where('evt_date.end_date', '>=', $startDate)
                          ->orWhereNull('evt_date.end_date');
                    });
                }
                if ($endDate) {
                    $sub->where(function ($q) use ($endDate) {
                        $q->where('evt_date.start_date', '<=', $endDate)
                          ->orWhereNull('evt_date.start_date');
                    });
                }
            }
        });
    }

    /**
     * Apply text search filter using LIKE (simplified for facet count queries).
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array|string $searchTerms
     */
    private function applyTextSearchFilter($query, $searchTerms): void
    {
        if (is_array($searchTerms)) {
            $query->where(function ($qb) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q = '%' . $term . '%';
                    $qb->orWhere(function ($inner) use ($q) {
                        $inner->whereExists(function ($sub) use ($q) {
                            $sub->select(DB::raw(1))
                                ->from('information_object_i18n as ioi')
                                ->whereRaw('ioi.id = io.id')
                                ->where(function ($w) use ($q) {
                                    $w->where('ioi.title', 'like', $q)
                                      ->orWhere('ioi.scope_and_content', 'like', $q);
                                });
                        })->orWhere('io.identifier', 'like', $q);
                    });
                }
            });
        } else {
            $q = '%' . $searchTerms . '%';
            $query->where(function ($qb) use ($q) {
                $qb->whereExists(function ($sub) use ($q) {
                    $sub->select(DB::raw(1))
                        ->from('information_object_i18n as ioi')
                        ->whereRaw('ioi.id = io.id')
                        ->where(function ($w) use ($q) {
                            $w->where('ioi.title', 'like', $q)
                              ->orWhere('ioi.scope_and_content', 'like', $q);
                        });
                })->orWhere('io.identifier', 'like', $q);
            });
        }
    }

    // =========================================================================
    // Per-facet count methods
    // =========================================================================

    private function getCreatorCounts(): array
    {
        $query = $this->buildBaseQuery('creator');

        return $query
            ->join('event as ev', 'io.id', '=', 'ev.object_id')
            ->join('actor_i18n as ai', function ($j) {
                $j->on('ev.actor_id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->whereNotNull('ev.actor_id')
            ->select('ev.actor_id as id', 'ai.authorized_form_of_name as name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('ev.actor_id', 'ai.authorized_form_of_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getSubjectCounts(): array
    {
        return $this->getTermFacetCounts('subject', 35);
    }

    private function getPlaceCounts(): array
    {
        return $this->getTermFacetCounts('place', 42);
    }

    private function getGenreCounts(): array
    {
        return $this->getTermFacetCounts('genre', 78);
    }

    /**
     * Generic method for term-based facets (subject, place, genre).
     */
    private function getTermFacetCounts(string $facetName, int $taxonomyId): array
    {
        $query = $this->buildBaseQuery($facetName);

        return $query
            ->join('object_term_relation as otr', 'io.id', '=', 'otr.object_id')
            ->join('term as t', function ($j) use ($taxonomyId) {
                $j->on('otr.term_id', '=', 't.id')->where('t.taxonomy_id', '=', $taxonomyId);
            })
            ->join('term_i18n as ti', function ($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->select('t.id', 'ti.name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getLevelCounts(): array
    {
        $query = $this->buildBaseQuery('level');

        return $query
            ->join('term_i18n as lvl', function ($j) {
                $j->on('io.level_of_description_id', '=', 'lvl.id')->where('lvl.culture', '=', 'en');
            })
            ->whereNotNull('io.level_of_description_id')
            ->select('io.level_of_description_id as id', 'lvl.name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('io.level_of_description_id', 'lvl.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRepositoryCounts(): array
    {
        $query = $this->buildBaseQuery('repository');

        return $query
            ->join('repository as r', 'io.repository_id', '=', 'r.id')
            ->join('actor_i18n as rai', function ($j) {
                $j->on('r.id', '=', 'rai.id')->where('rai.culture', '=', 'en');
            })
            ->whereNotNull('io.repository_id')
            ->select('r.id', 'rai.authorized_form_of_name as name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('r.id', 'rai.authorized_form_of_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getGlamTypeCounts(): array
    {
        $query = $this->buildBaseQuery('glam_type');

        return $query
            ->whereNotNull('doc.object_type')
            ->select('doc.object_type', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('doc.object_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getMediaTypeCounts(): array
    {
        $query = $this->buildBaseQuery('media_type');

        return $query
            ->join('digital_object as dobj', function ($j) {
                $j->on('io.id', '=', 'dobj.object_id')->whereNull('dobj.parent_id');
            })
            ->select(DB::raw("SUBSTRING_INDEX(dobj.mime_type, '/', 1) as media_type"), DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy(DB::raw("SUBSTRING_INDEX(dobj.mime_type, '/', 1)"))
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
