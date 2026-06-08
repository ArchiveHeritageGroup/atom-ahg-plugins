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

    // Library-only facets (only meaningful when result set includes library_item rows).
    private ?string $materialTypeFilter = null;
    private ?string $conditionGradeFilter = null;
    private ?string $acquisitionMethodFilter = null;
    private ?string $circulationStatusFilter = null;

    // Museum-only facets. Data lives in property rows where name='ccoData'
    // (a JSON blob authored by the CCO template), NOT in museum_metadata which
    // is currently dormant on Heratio sites. We JSON_EXTRACT into the value.
    private ?string $workTypeFilter = null;        // ccoData.work_type
    private ?string $materialsFilter = null;       // ccoData.materials_display
    private ?string $creationPlaceFilter = null;   // ccoData.creation_place
    private ?string $museumRepositoryFilter = null; // ccoData.repository

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

        $this->materialTypeFilter = $filters['materialTypeFilter'] ?? null;
        $this->conditionGradeFilter = $filters['conditionGradeFilter'] ?? null;
        $this->acquisitionMethodFilter = $filters['acquisitionMethodFilter'] ?? null;
        $this->circulationStatusFilter = $filters['circulationStatusFilter'] ?? null;

        $this->workTypeFilter = $filters['workTypeFilter'] ?? null;
        $this->materialsFilter = $filters['materialsFilter'] ?? null;
        $this->creationPlaceFilter = $filters['creationPlaceFilter'] ?? null;
        $this->museumRepositoryFilter = $filters['museumRepositoryFilter'] ?? null;
        $this->languageFilter = $filters['languageFilter'] ?? null;
    }

    /**
     * Returns true if any of the facet ID filters are active.
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
            || $this->typeFilter
            || $this->materialTypeFilter
            || $this->conditionGradeFilter
            || $this->acquisitionMethodFilter
            || $this->circulationStatusFilter
            || $this->workTypeFilter
            || $this->materialsFilter
            || $this->creationPlaceFilter
            || $this->museumRepositoryFilter
            || ($this->languageFilter !== null && $this->languageFilter !== '');
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
            case 'language':
                return $this->getLanguageCounts();
            case 'material_type':
            case 'condition_grade':
            case 'acquisition_method':
            case 'circulation_status':
                return $this->getLibraryColumnCounts($facetType);
            case 'work_type':
            case 'materials':
            case 'creation_place':
            case 'museum_repository':
                return $this->getMuseumCcoCounts($facetType);
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

        // Facet ID filters — skip the excluded one. The type filter is also
        // skipped when computing a library-only dimension so the per-library
        // facet doesn't vanish if default_sector is non-library: clicking a
        // library facet must always be allowed to pivot the user into the
        // library sector.
        $libraryDimensions = ['material_type', 'condition_grade', 'acquisition_method', 'circulation_status'];
        $museumDimensions = ['work_type', 'materials', 'creation_place', 'museum_repository'];
        $isLibraryFacet = in_array($excludeFacet, $libraryDimensions, true);
        $isMuseumFacet = in_array($excludeFacet, $museumDimensions, true);
        if ($excludeFacet !== 'glam_type' && !$isLibraryFacet && !$isMuseumFacet && $this->typeFilter) {
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

        if ($excludeFacet !== 'language' && $this->languageFilter !== null && $this->languageFilter !== '') {
            $this->applyLanguageFilter($query);
        }

        // Library-only ID filters. Each scopes the result set to IOs whose
        // paired library_item row matches the value (and thus excludes
        // non-library records entirely when any of these is set).
        $libFilterCols = [
            'material_type'      => $this->materialTypeFilter,
            'condition_grade'    => $this->conditionGradeFilter,
            'acquisition_method' => $this->acquisitionMethodFilter,
            'circulation_status' => $this->circulationStatusFilter,
        ];
        foreach ($libFilterCols as $col => $val) {
            if ($excludeFacet === $col || $val === null || $val === '') {
                continue;
            }
            $query->whereExists(function ($q) use ($col, $val) {
                $q->select(DB::raw(1))
                    ->from('library_item as li_w')
                    ->whereRaw('li_w.information_object_id = io.id')
                    ->where("li_w.{$col}", $val);
            });
        }

        // Museum-only ID filters. Data lives in property rows where name='ccoData'
        // with the museum-specific values inside a JSON blob; JSON_EXTRACT
        // pulls out a single key. Keys are the four CCO fields actually
        // populated on Heratio sites (work_type, materials_display,
        // creation_place, repository) — see getMuseumCcoCounts for source.
        $museumFilterMap = [
            'work_type'         => $this->workTypeFilter,
            'materials_display' => $this->materialsFilter,
            'creation_place'    => $this->creationPlaceFilter,
            'repository'        => $this->museumRepositoryFilter,
        ];
        // Map facet name → CCO JSON key (so $excludeFacet='materials' excludes the
        // materials_display filter from its own facet count).
        $facetToCcoKey = [
            'work_type'         => 'work_type',
            'materials'         => 'materials_display',
            'creation_place'    => 'creation_place',
            'museum_repository' => 'repository',
        ];
        $excludeCcoKey = $facetToCcoKey[$excludeFacet] ?? null;
        foreach ($museumFilterMap as $jsonKey => $val) {
            if ($jsonKey === $excludeCcoKey || $val === null || $val === '') {
                continue;
            }
            $query->whereExists(function ($q) use ($jsonKey, $val) {
                $q->select(DB::raw(1))
                    ->from('property as p_mw')
                    ->join('property_i18n as pi_mw', function ($j) {
                        $j->on('pi_mw.id', '=', 'p_mw.id')
                          ->where('pi_mw.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->whereRaw('p_mw.object_id = io.id')
                    ->where('p_mw.name', '=', 'ccoData')
                    // Guard: some ccoData property_i18n.value rows contain non-JSON
                    // text; JSON_EXTRACT throws SQLSTATE 22032 on invalid JSON and
                    // 500s the whole browse. Fall back to '{}' (→ NULL, no match).
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(pi_mw.value), pi_mw.value, '{}'), ?)) = ?", ['$.' . $jsonKey, $val]);
            });
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
                        $j->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
        $sectorTables = $this->getSectorSearchTables();

        if (is_array($searchTerms)) {
            $query->where(function ($qb) use ($searchTerms, $sectorTables) {
                foreach ($searchTerms as $term) {
                    $q = '%' . $term . '%';
                    $qb->orWhere(function ($inner) use ($q, $sectorTables) {
                        $inner->whereExists(function ($sub) use ($q) {
                            $sub->select(DB::raw(1))
                                ->from('information_object_i18n as ioi')
                                ->whereRaw('ioi.id = io.id')
                                ->where(function ($w) use ($q) {
                                    $w->where('ioi.title', 'like', $q)
                                      ->orWhere('ioi.scope_and_content', 'like', $q);
                                });
                        })->orWhere('io.identifier', 'like', $q);
                        $this->applySectorSearchClauses($inner, $q, $sectorTables);
                    });
                }
            });
        } else {
            $q = '%' . $searchTerms . '%';
            $query->where(function ($qb) use ($q, $sectorTables) {
                $qb->whereExists(function ($sub) use ($q) {
                    $sub->select(DB::raw(1))
                        ->from('information_object_i18n as ioi')
                        ->whereRaw('ioi.id = io.id')
                        ->where(function ($w) use ($q) {
                            $w->where('ioi.title', 'like', $q)
                              ->orWhere('ioi.scope_and_content', 'like', $q);
                        });
                })->orWhere('io.identifier', 'like', $q);
                $this->applySectorSearchClauses($qb, $q, $sectorTables);
            });
        }
    }

    /**
     * Add sector-specific search clauses (DAM, Museum, Gallery).
     */
    private function applySectorSearchClauses($qb, string $likePattern, array $sectorTables): void
    {
        if (in_array('dam_iptc_metadata', $sectorTables)) {
            $qb->orWhereExists(function ($sub) use ($likePattern) {
                $sub->select(DB::raw(1))
                    ->from('dam_iptc_metadata as dim')
                    ->whereRaw('dim.object_id = io.id')
                    ->where(function ($w) use ($likePattern) {
                        $w->where('dim.creator', 'like', $likePattern)
                          ->orWhere('dim.headline', 'like', $likePattern)
                          ->orWhere('dim.caption', 'like', $likePattern)
                          ->orWhere('dim.keywords', 'like', $likePattern);
                    });
            });
        }

        if (in_array('museum_metadata', $sectorTables)) {
            $qb->orWhereExists(function ($sub) use ($likePattern) {
                $sub->select(DB::raw(1))
                    ->from('museum_metadata as mm')
                    ->whereRaw('mm.object_id = io.id')
                    ->where(function ($w) use ($likePattern) {
                        $w->where('mm.creator_identity', 'like', $likePattern)
                          ->orWhere('mm.materials', 'like', $likePattern)
                          ->orWhere('mm.techniques', 'like', $likePattern)
                          ->orWhere('mm.classification', 'like', $likePattern)
                          ->orWhere('mm.inscription', 'like', $likePattern);
                    });
            });
        }

        // Museum CCO JSON: object_number, work_type, materials_display,
        // creator_display, creation_place, condition_summary, subject_display,
        // inscription_transcription, etc — all live as keys inside a single JSON
        // blob in property_i18n.value where property.name='ccoData'. A LIKE on
        // the raw JSON catches any of them without parsing.
        $qb->orWhereExists(function ($sub) use ($likePattern) {
            $sub->select(DB::raw(1))
                ->from('property as p_cco')
                ->join('property_i18n as pi_cco', function ($j) {
                    $j->on('pi_cco.id', '=', 'p_cco.id')
                      ->where('pi_cco.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->whereRaw('p_cco.object_id = io.id')
                ->where('p_cco.name', '=', 'ccoData')
                ->where('pi_cco.value', 'like', $likePattern);
        });

        if (in_array('gallery_artist', $sectorTables)) {
            $qb->orWhereExists(function ($sub) use ($likePattern) {
                $sub->select(DB::raw(1))
                    ->from('event as ev_ga')
                    ->join('gallery_artist as ga', 'ga.actor_id', '=', 'ev_ga.actor_id')
                    ->whereRaw('ev_ga.object_id = io.id')
                    ->where(function ($w) use ($likePattern) {
                        $w->where('ga.display_name', 'like', $likePattern)
                          ->orWhere('ga.medium_specialty', 'like', $likePattern)
                          ->orWhere('ga.movement_style', 'like', $likePattern);
                    });
            });
        }

        // Library: search ISBN, call_number, series_title, summary, contents_note
        // on the per-IO library_item row.
        if (in_array('library_item', $sectorTables)) {
            $qb->orWhereExists(function ($sub) use ($likePattern) {
                $sub->select(DB::raw(1))
                    ->from('library_item as li')
                    ->whereRaw('li.information_object_id = io.id')
                    ->where(function ($w) use ($likePattern) {
                        $w->where('li.isbn', 'like', $likePattern)
                          ->orWhere('li.call_number', 'like', $likePattern)
                          ->orWhere('li.series_title', 'like', $likePattern)
                          ->orWhere('li.summary', 'like', $likePattern)
                          ->orWhere('li.contents_note', 'like', $likePattern);
                    });
            });
        }

        // Library creators: search the raw author name (covers rows where
        // resolveOrCreateActor has not run yet so actor_id is still NULL).
        if (in_array('library_item_creator', $sectorTables)) {
            $qb->orWhereExists(function ($sub) use ($likePattern) {
                $sub->select(DB::raw(1))
                    ->from('library_item_creator as lic')
                    ->join('library_item as li2', 'li2.id', '=', 'lic.library_item_id')
                    ->whereRaw('li2.information_object_id = io.id')
                    ->where('lic.name', 'like', $likePattern);
            });
        }

        // Authority records: search actor_i18n.authorized_form_of_name for any
        // IO whose event row links to a matching actor. Catches author/creator
        // searches across all sectors (not just library), so "Nelson Mandela"
        // finds the autobiography even though no library_item.title contains it.
        $qb->orWhereExists(function ($sub) use ($likePattern) {
            $sub->select(DB::raw(1))
                ->from('event as ev_act')
                ->join('actor_i18n as ai_act', 'ai_act.id', '=', 'ev_act.actor_id')
                ->whereRaw('ev_act.object_id = io.id')
                ->where('ai_act.authorized_form_of_name', 'like', $likePattern);
        });
    }

    /**
     * Get list of sector-specific tables that exist in the database.
     * Cached per request.
     */
    private static $sectorSearchTables = null;

    private function getSectorSearchTables(): array
    {
        if (self::$sectorSearchTables !== null) {
            return self::$sectorSearchTables;
        }

        self::$sectorSearchTables = [];
        $candidates = ['dam_iptc_metadata', 'museum_metadata', 'gallery_artist', 'library_item', 'library_item_creator'];

        foreach ($candidates as $table) {
            try {
                DB::select("SELECT 1 FROM `{$table}` LIMIT 1");
                self::$sectorSearchTables[] = $table;
            } catch (\Exception $e) {
                // Table doesn't exist — skip
            }
        }

        return self::$sectorSearchTables;
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
                $j->on('ev.actor_id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                $j->on('io.level_of_description_id', '=', 'lvl.id')->where('lvl.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
                $j->on('r.id', '=', 'rai.id')->where('rai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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

    /**
     * Library-only facet counts (material_type, condition_grade,
     * acquisition_method, circulation_status). The column on library_item
     * doubles as both the bucket id and the bucket name since these are free-
     * text columns rather than FK-to-term.
     */
    private function getLibraryColumnCounts(string $libCol): array
    {
        try {
            $query = $this->buildBaseQuery($libCol);

            return $query
                ->join('library_item as li_f', 'li_f.information_object_id', '=', 'io.id')
                ->whereNotNull("li_f.{$libCol}")
                ->where("li_f.{$libCol}", '!=', '')
                ->select(
                    DB::raw("li_f.{$libCol} as id"),
                    DB::raw("li_f.{$libCol} as name"),
                    DB::raw('COUNT(DISTINCT io.id) as count')
                )
                ->groupBy("li_f.{$libCol}")
                ->orderByDesc('count')
                ->limit(30)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // library_item table only exists when ahgLibraryPlugin is installed.
            return [];
        }
    }

    /**
     * Museum-only facet counts (work_type, materials, creation_place,
     * museum_repository). Reads CCO data from property.name='ccoData' rows
     * where the value is a JSON blob; JSON_EXTRACT pulls out one key.
     */
    private function getMuseumCcoCounts(string $facetName): array
    {
        $facetToCcoKey = [
            'work_type'         => 'work_type',
            'materials'         => 'materials_display',
            'creation_place'    => 'creation_place',
            'museum_repository' => 'repository',
        ];
        $jsonKey = $facetToCcoKey[$facetName] ?? null;
        if ($jsonKey === null) {
            return [];
        }
        $jsonPath = '$.' . $jsonKey;

        try {
            $query = $this->buildBaseQuery($facetName);

            return $query
                ->join('property as p_mf', 'p_mf.object_id', '=', 'io.id')
                ->join('property_i18n as pi_mf', function ($j) {
                    $j->on('pi_mf.id', '=', 'p_mf.id')
                      ->where('pi_mf.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('p_mf.name', '=', 'ccoData')
                ->whereRaw("JSON_EXTRACT(pi_mf.value, ?) IS NOT NULL", [$jsonPath])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(pi_mf.value, ?)) != ''", [$jsonPath])
                ->select(
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pi_mf.value, " . DB::connection()->getPdo()->quote($jsonPath) . ")) as id"),
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pi_mf.value, " . DB::connection()->getPdo()->quote($jsonPath) . ")) as name"),
                    DB::raw('COUNT(DISTINCT io.id) as count')
                )
                ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pi_mf.value, " . DB::connection()->getPdo()->quote($jsonPath) . "))"))
                ->orderByDesc('count')
                ->limit(30)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // property/property_i18n always exist in base AtoM but ccoData may
            // not be populated; return empty so the template hides the card.
            return [];
        }
    }

    /**
     * Language facet counts from information_object_i18n.languages (JSON array).
     * Each distinct ISO 639-1 code in the result set gets its own bucket.
     */
    private function getLanguageCounts(): array
    {
        try {
            $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
            $pdo = DB::connection()->getPdo();
            $jsonPath = $pdo->quote('$.*');
            $query = $this->buildBaseQuery('language');
            $query->leftJoin('information_object_i18n as io_l18n', function ($j) use ($culture) {
                $j->on('io.id', '=', 'io_l18n.id')->where('io_l18n.culture', '=', $culture);
            })
            ->whereNotNull('io_l18n.languages')
            ->where('io_l18n.languages', '!=', '')
            ->select(
                DB::raw("lang_codes.code as id"),
                DB::raw("lang_codes.code as name"),
                DB::raw('COUNT(DISTINCT io.id) as count')
            )
            ->crossJoin(DB::raw("JSON_TABLE(io_l18n.languages, " . $pdo->quote('$[*]') . ", " . $pdo->quote("$.code") . " columns (code varchar(5) path " . $pdo->quote('$') . ")) as lang_codes"))
            ->groupBy('lang_codes.code')
            ->orderByDesc('count')
            ->limit(30);
            return $query->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Apply a language filter to a query.
     * Uses REGEXP pattern-matching on the raw JSON array to avoid JSON_TABLE
     * version requirements in the fallback path.
     */
    private function applyLanguageFilter($query): void
    {
        $code = $this->languageFilter;
        $query->whereExists(function ($sub) use ($code) {
            $sub->select(DB::raw(1))
                ->from('information_object_i18n as io_lf')
                ->whereRaw('io_lf.id = io.id')
                ->where('io_lf.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->whereNotNull('io_lf.languages')
                ->where('io_lf.languages', '!=', '')
                // REGEXP with $ bound — matches "en" as a word within the JSON array
                // but not as a substring of another code like "afr" or "ben".
                ->whereRaw("io_lf.languages REGEXP ?", ['"(^|[^a-zA-Z])' . preg_quote($code, '/') . '($|[^a-zA-Z])']);
        });
    }

}
