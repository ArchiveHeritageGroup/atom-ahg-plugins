<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;
use AhgDisplay\Services\DisplayService;
use AhgDisplay\Services\DisplayModeService;

class displayActions extends AhgController
{
    protected $service;
    protected $modeService;

    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayTypeDetector.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayModeService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/FuzzySearchService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DynamicFacetService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Repositories/DisplayPreferenceRepository.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Repositories/GlobalDisplaySettingsRepository.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Repositories/UserBrowseSettingsRepository.php';
        $this->service = new DisplayService();
        $this->modeService = new DisplayModeService();
    }

    public function executeIndex($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }

        $this->profiles = DB::table('display_profile as dp')
            ->leftJoin('display_profile_i18n as dpi', function($j) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('dp.*', 'dpi.name')
            ->orderBy('dp.domain')->orderBy('dp.sort_order')
            ->get()->toArray();

        $this->levels = $this->service->getLevels();
        $this->collectionTypes = $this->service->getCollectionTypes();

        $this->stats = [
            'total_objects' => DB::table('information_object')->where('id', '>', 1)->count(),
            'configured_objects' => DB::table('display_object_config')->count(),
            'by_type' => DB::table('display_object_config')
                ->select('object_type', DB::raw('COUNT(*) as count'))
                ->groupBy('object_type')
                ->get()->toArray(),
        ];
    }

    public function executeBrowse($request)
    {
        // Check if user is authenticated (can see drafts)
        $this->isAuthenticated = $this->getContext()->getUser()->isAuthenticated();

        // Get all filter parameters
        $this->typeFilter = $request->getParameter('type');
        $this->parentId = $request->getParameter('parent');
        $this->topLevelOnly = $request->getParameter('topLevel', '1');
        $this->page = max(1, (int) $request->getParameter('page', 1));
        // Read limit from raw GET: QubitLimitResults filter caps $request->limit
        // to app_hits_per_page, but display browse has its own 10-100 range control.
        $this->limit = isset($_GET['limit']) ? (int) $_GET['limit'] : (int) $this->config('app_hits_per_page', 30);
        if ($this->limit < 10) $this->limit = 10;
        if ($this->limit > 100) $this->limit = 100;
        $this->sort = $request->getParameter('sort', 'date');
        $this->sortDir = $request->getParameter('dir', 'desc');
        $this->viewMode = $request->getParameter('view', 'card');
        $this->hasDigital = $request->getParameter('hasDigital');

        // Map standard AtoM parameters to display plugin native names
        if (!$this->hasDigital && $request->getParameter('onlyMedia')) {
            $this->hasDigital = '1';
        }
        if ($request->getParameter('topLod') !== null) {
            $this->topLevelOnly = filter_var($request->getParameter('topLod'), FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if (!$this->parentId && $request->getParameter('ancestor')) {
            $this->parentId = $request->getParameter('ancestor');
        }

        // New facet filters
        $this->creatorFilter = $request->getParameter('creator');
        $this->subjectFilter = $request->getParameter('subject');
        $this->placeFilter = $request->getParameter('place');
        $this->genreFilter = $request->getParameter('genre');
        $this->levelFilter = $request->getParameter('level');
        $this->mediaFilter = $request->getParameter('media');
        // Text search filters
        $this->queryFilter = $request->getParameter("query");
        $this->semanticEnabled = $request->getParameter("semantic") == "1";

        // Fuzzy search correction
        $this->didYouMean = null;
        $this->correctedQuery = null;
        $this->originalQuery = $this->queryFilter;
        $this->esAssistedSearch = false;

        $noCorrect = $request->getParameter('noCorrect');
        if ($this->queryFilter && !$noCorrect) {
            try {
                $fuzzyService = new \AhgDisplay\Services\FuzzySearchService();
                $correction = $fuzzyService->correctQuery($this->queryFilter);
                if ($correction['corrected'] !== null) {
                    if ($correction['confidence'] >= 0.9) {
                        // High confidence: auto-correct
                        $this->correctedQuery = $correction['corrected'];
                        $this->queryFilter = $correction['corrected'];
                    } else {
                        // Lower confidence: suggest only
                        $this->didYouMean = $correction['corrected'];
                    }
                }
            } catch (\Exception $e) {
                error_log('FuzzySearch: ' . $e->getMessage());
            }
        }

        // Expand query with synonyms if semantic search is enabled
        // Store as array for OR-based search
        if ($this->queryFilter && $this->semanticEnabled) {
            $this->queryFilterTerms = $this->expandQueryWithSynonyms($this->queryFilter);
        } else {
            $this->queryFilterTerms = null;
        }

        $this->titleFilter = $request->getParameter("title");
        $this->identifierFilter = $request->getParameter("identifier");
        $this->referenceCodeFilter = $request->getParameter("referenceCode");
        $this->scopeAndContentFilter = $request->getParameter("scopeAndContent");
        $this->extentAndMediumFilter = $request->getParameter("extentAndMedium");
        $this->archivalHistoryFilter = $request->getParameter("archivalHistory");
        $this->acquisitionFilter = $request->getParameter("acquisition");
        $this->creatorSearchFilter = $request->getParameter("creatorSearch");
        $this->subjectSearchFilter = $request->getParameter("subjectSearch");
        $this->placeSearchFilter = $request->getParameter("placeSearch");
        $this->genreSearchFilter = $request->getParameter("genreSearch");
        $this->repoFilter = $request->getParameter('repo');
        $this->startDateFilter = $request->getParameter('startDate');
        $this->endDateFilter = $request->getParameter('endDate');
        $this->rangeTypeFilter = $request->getParameter('rangeType', 'inclusive');

        // Load facets: use dynamic counts when filters are active, cached counts otherwise
        $sfx = $this->isAuthenticated ? '_all' : '';
        $facetService = new \AhgDisplay\Services\DynamicFacetService([
            'isAuthenticated' => $this->isAuthenticated,
            'typeFilter' => $this->typeFilter,
            'parentId' => $this->parentId,
            'topLevelOnly' => $this->topLevelOnly,
            'hasDigital' => $this->hasDigital,
            'creatorFilter' => $this->creatorFilter,
            'subjectFilter' => $this->subjectFilter,
            'placeFilter' => $this->placeFilter,
            'genreFilter' => $this->genreFilter,
            'levelFilter' => $this->levelFilter,
            'mediaFilter' => $this->mediaFilter,
            'repoFilter' => $this->repoFilter,
            'queryFilter' => $this->queryFilter,
            'queryFilterTerms' => $this->queryFilterTerms,
            'titleFilter' => $this->titleFilter,
            'identifierFilter' => $this->identifierFilter,
            'referenceCodeFilter' => $this->referenceCodeFilter,
            'scopeAndContentFilter' => $this->scopeAndContentFilter,
            'extentAndMediumFilter' => $this->extentAndMediumFilter,
            'archivalHistoryFilter' => $this->archivalHistoryFilter,
            'acquisitionFilter' => $this->acquisitionFilter,
            'creatorSearchFilter' => $this->creatorSearchFilter,
            'subjectSearchFilter' => $this->subjectSearchFilter,
            'placeSearchFilter' => $this->placeSearchFilter,
            'genreSearchFilter' => $this->genreSearchFilter,
            'startDateFilter' => $this->startDateFilter,
            'endDateFilter' => $this->endDateFilter,
            'rangeTypeFilter' => $this->rangeTypeFilter,
        ]);

        if ($facetService->hasActiveFacetFilters()) {
            // Dynamic disjunctive faceting: each facet excludes its own filter
            $this->types = $facetService->getFacetCounts('glam_type');
            $this->levels = $facetService->getFacetCounts('level');
            $this->repositories = $facetService->getFacetCounts('repository');
            $this->creators = $facetService->getFacetCounts('creator');
            $this->subjects = $facetService->getFacetCounts('subject');
            $this->places = $facetService->getFacetCounts('place');
            $this->genres = $facetService->getFacetCounts('genre');
            $this->mediaTypes = $facetService->getFacetCounts('media_type');
        } else {
            // No facet filters active: use pre-computed cached counts (zero overhead)
            $this->types = $this->getCachedFacet('glam_type' . $sfx, 'object_type');
            $this->levels = $this->getCachedFacet('level' . $sfx);
            $this->repositories = $this->getCachedFacet('repository' . $sfx);
            $this->creators = $this->getCachedFacet('creator' . $sfx);
            $this->subjects = $this->getCachedFacet('subject' . $sfx);
            $this->places = $this->getCachedFacet('place' . $sfx);
            $this->genres = $this->getCachedFacet('genre' . $sfx);
            $this->mediaTypes = $this->getCachedFacet('media_type' . $sfx, 'media_type');
        }

        // ── Discovery integration ──────────────────────────────────────
        // When a text query is present and ahgDiscoveryPlugin is enabled,
        // route through the 4-strategy Discovery pipeline for relevance-
        // ranked results instead of SQL FULLTEXT/LIKE.
        $this->discoveryMode = false;
        $this->discoveryExpanded = null;
        $this->discoveryMeta = [];
        $this->esIds = null;

        $useDiscovery = $this->queryFilter
            && $request->getParameter('discovery', '1') !== '0'
            && file_exists(\sfConfig::get('sf_plugins_dir') . '/ahgDiscoveryPlugin/lib/Services/QueryExpander.php');

        if ($useDiscovery) {
            try {
                $discoveryIds = $this->runDiscoveryPipeline($this->queryFilter);
                if (!empty($discoveryIds)) {
                    $this->discoveryMode = true;

                    // Apply facet filters to narrow the Discovery ID set
                    $filteredIds = $this->applyFacetFiltersToIds($discoveryIds);

                    $this->total = count($filteredIds);
                    $this->totalPages = (int) ceil($this->total / $this->limit);

                    // Paginate the ID list, then pass slice to main query via esIds path
                    $pageIds = array_slice($filteredIds, ($this->page - 1) * $this->limit, $this->limit);
                    $this->esIds = $pageIds;

                    // Recompute facets scoped to full Discovery result set
                    $this->recomputeDiscoveryFacets($discoveryIds);
                }
            } catch (\Exception $e) {
                error_log('Discovery integration error: ' . $e->getMessage());
                // Fall through to classic search
            }
        }

        if (!$this->discoveryMode) {
            // Classic path: SQL count + optional ES fuzzy fallback
            $countQuery = DB::table('information_object as io')
                ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
                ->where('io.id', '>', 1);

            $this->applyFilters($countQuery);
            $this->total = $countQuery->count();

            if ($this->total === 0 && $this->queryFilter) {
                try {
                    $esIds = $this->tryElasticsearchFuzzy($this->queryFilter);
                    if (!empty($esIds)) {
                        $this->esIds = $esIds;
                        $this->esAssistedSearch = true;
                        $this->total = count($esIds);
                    }
                } catch (\Exception $e) {
                    error_log('FuzzySearch ES fallback: ' . $e->getMessage());
                }
            }
        }

        $this->totalPages = (int) ceil($this->total / $this->limit);

        // Build main query
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', '>', 1)
            ->select(
                'io.id', 'io.identifier', 'io.parent_id',
                'i18n.title', 'i18n.scope_and_content',
                'level.name as level_name',
                'doc.object_type', 'slug.slug'
            );

        // Apply all filters to main query
        if ($this->esIds) {
            // ES fallback: override filters with ES-matched IDs
            $query->whereIn('io.id', $this->esIds);
        } else {
            $this->applyFilters($query);
        }

        // Handle parent/breadcrumb
        if ($this->parentId) {
            $this->parent = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($j) {
                    $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
                ->where('io.id', $this->parentId)
                ->select('io.id', 'io.parent_id', 'i18n.title', 'slug.slug')
                ->first();
            $this->breadcrumb = $this->buildBreadcrumb($this->parentId);
        } else {
            $this->parent = null;
            $this->breadcrumb = [];
        }

        $this->digitalObjectCount = DB::table('information_object as io')
            ->join('digital_object as do', function($j) {
                $j->on('io.id', '=', 'do.object_id')->whereNull('do.parent_id');
            })
            ->where('io.id', '>', 1)
            ->count();

        // Sort
        $sortDir = $this->sortDir === 'desc' ? 'desc' : 'asc';
        if ($this->discoveryMode && ($this->sort === 'relevance' || $this->sort === 'date')) {
            // Discovery mode default: preserve relevance ranking via ORDER BY FIELD
            if (!empty($this->esIds)) {
                $idList = implode(',', array_map('intval', $this->esIds));
                $query->orderByRaw("FIELD(io.id, $idList)");
            }
        } else {
            switch ($this->sort) {
                case 'identifier':
                case 'refcode':
                    $query->orderBy('io.identifier', $sortDir);
                    break;
                case 'date':
                    $query->orderBy('io.id', $sortDir);
                    break;
                case 'startdate':
                    $query->leftJoin('event as evt_sort', 'io.id', '=', 'evt_sort.object_id');
                    $query->orderByRaw("MIN(evt_sort.start_date) $sortDir");
                    $query->groupBy('io.id', 'io.identifier', 'io.parent_id', 'i18n.title', 'i18n.scope_and_content', 'level.name', 'doc.object_type', 'slug.slug');
                    break;
                case 'enddate':
                    $query->leftJoin('event as evt_sort', 'io.id', '=', 'evt_sort.object_id');
                    $query->orderByRaw("MAX(evt_sort.end_date) $sortDir");
                    $query->groupBy('io.id', 'io.identifier', 'io.parent_id', 'i18n.title', 'i18n.scope_and_content', 'level.name', 'doc.object_type', 'slug.slug');
                    break;
                default:
                    $query->orderBy('i18n.title', $sortDir);
            }
        }

        // Paginate — Discovery mode already sliced IDs, so fetch all matched
        if ($this->discoveryMode && !empty($this->esIds)) {
            $this->objects = $query->limit($this->limit)->get()->toArray();
        } else {
            $this->objects = $query
                ->offset(($this->page - 1) * $this->limit)
                ->limit($this->limit)
                ->get()
                ->toArray();
        }

        // Enrich results
        foreach ($this->objects as &$obj) {
            $obj->child_count = DB::table('information_object')->where('parent_id', $obj->id)->count();
            
            if (!$obj->object_type) {
                $obj->object_type = DisplayTypeDetector::detect($obj->id);
            }
            
            $obj->thumbnail = null;
            
            $digitalObject = DB::table('digital_object')
                ->where('object_id', $obj->id)
                ->whereNull('parent_id')
                ->select('id')
                ->first();
            
            $obj->has_digital = !empty($digitalObject);
            
            if ($digitalObject) {
                $thumb = DB::table('digital_object')
                    ->where('parent_id', $digitalObject->id)
                    ->where('usage_id', 142)
                    ->select('path', 'name')
                    ->first();
                
                if ($thumb && $thumb->path && $thumb->name) {
                    $obj->thumbnail = rtrim($thumb->path, '/') . '/' . $thumb->name;
                } else {
                    $ref = DB::table('digital_object')
                        ->where('parent_id', $digitalObject->id)
                        ->where('usage_id', 141)
                        ->select('path', 'name')
                        ->first();
                    
                    if ($ref && $ref->path && $ref->name) {
                        $obj->thumbnail = rtrim($ref->path, '/') . '/' . $ref->name;
                    }
				}
            }

            // Fallback to library_item cover_url for library items
            // Wrapped in try/catch: library_item table only exists when ahgLibraryPlugin is installed
            if (!$obj->thumbnail) {
                try {
                    $libraryItem = DB::table('library_item')
                        ->where('information_object_id', $obj->id)
                        ->select('cover_url')
                        ->first();
                    if ($libraryItem && $libraryItem->cover_url) {
                        $obj->thumbnail = $libraryItem->cover_url;
                        $obj->has_digital = true;
                    }
                } catch (\Exception $e) {
                    // table does not exist - ahgLibraryPlugin not installed
                }
            }
        }
        
        // Build filter params for template
        $this->filterParams = [
            'type' => $this->typeFilter,
            'parent' => $this->parentId,
            'topLevel' => $this->topLevelOnly,
            'creator' => $this->creatorFilter,
            'subject' => $this->subjectFilter,
            'place' => $this->placeFilter,
            'genre' => $this->genreFilter,
            'level' => $this->levelFilter,
            'media' => $this->mediaFilter,
            'repo' => $this->repoFilter,
            'hasDigital' => $this->hasDigital,
            'view' => $this->viewMode,
            'limit' => $this->limit,
            'sort' => $this->sort,
            'dir' => $this->sortDir,
        ];
    }

    protected function applyFilters($query)
    {
        // Filter by publication status - only show Published items (status_id = 160) for guests
        // Authenticated users (editors/admins) can see all items
        if (!$this->getContext()->getUser()->isAuthenticated()) {
            $query->join('status as pub_st', function($j) {
                $j->on('pub_st.object_id', '=', 'io.id')
                  ->where('pub_st.type_id', '=', 158)  // publication status type
                  ->where('pub_st.status_id', '=', 160); // Published
            });
        }

        if ($this->parentId) {
            $query->where('io.parent_id', $this->parentId);
        } elseif ($this->topLevelOnly === '1') {
            $query->where('io.parent_id', 1);
        }

        if ($this->typeFilter) {
            $query->where('doc.object_type', $this->typeFilter);
        }

        if ($this->hasDigital) {
            $query->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('digital_object')
                  ->whereRaw('digital_object.object_id = io.id')
                  ->whereNull('digital_object.parent_id');
            });
        }

        if ($this->creatorFilter) {
            $query->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('event')
                  ->whereRaw('event.object_id = io.id')
                  ->where('event.actor_id', $this->creatorFilter);
            });
        }

        if ($this->subjectFilter) {
            $query->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('object_term_relation')
                  ->whereRaw('object_term_relation.object_id = io.id')
                  ->where('object_term_relation.term_id', $this->subjectFilter);
            });
        }

        if ($this->placeFilter) {
            $query->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('object_term_relation')
                  ->whereRaw('object_term_relation.object_id = io.id')
                  ->where('object_term_relation.term_id', $this->placeFilter);
            });
        }

        if ($this->genreFilter) {
            $query->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('object_term_relation')
                  ->whereRaw('object_term_relation.object_id = io.id')
                  ->where('object_term_relation.term_id', $this->genreFilter);
            });
        }

        if ($this->levelFilter) {
            $query->where('io.level_of_description_id', $this->levelFilter);
        }

        if ($this->mediaFilter) {
            $query->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('digital_object')
                  ->whereRaw('digital_object.object_id = io.id')
                  ->whereNull('digital_object.parent_id')
                  ->whereRaw("digital_object.mime_type LIKE ?", [$this->mediaFilter . '/%']);
            });
        }

        if ($this->repoFilter) {
            $query->where('io.repository_id', $this->repoFilter);
        }

        // Text search filters - use OR logic for semantic search
        if ($this->queryFilter) {
            if ($this->queryFilterTerms) {
                // Semantic search: search for ANY of the expanded terms (OR logic)
                $this->applyTextSearchFilter($query, $this->queryFilterTerms);
            } else {
                // Normal search: single term
                $this->applyTextSearchFilter($query, $this->queryFilter);
            }
        }

        if ($this->titleFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("information_object_i18n as ioi")
                    ->whereRaw("ioi.id = io.id")
                    ->where("ioi.title", "like", "%".$this->titleFilter."%");
            });
        }

        if ($this->identifierFilter) {
            $query->where("io.identifier", "like", "%".$this->identifierFilter."%");
        }

        if ($this->scopeAndContentFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("information_object_i18n as ioi")
                    ->whereRaw("ioi.id = io.id")
                    ->where("ioi.scope_and_content", "like", "%".$this->scopeAndContentFilter."%");
            });
        }

        if ($this->creatorSearchFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("event as e")
                    ->join("actor_i18n as ai", function($j) {
                        $j->on("e.actor_id", "=", "ai.id")->where("ai.culture", "=", "en");
                    })
                    ->whereRaw("e.object_id = io.id")
                    ->where("ai.authorized_form_of_name", "like", "%".$this->creatorSearchFilter."%");
            });
        }

        if ($this->subjectSearchFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("object_term_relation as otr")
                    ->join("term as t", "otr.term_id", "=", "t.id")
                    ->join("term_i18n as ti", function($j) {
                        $j->on("t.id", "=", "ti.id")->where("ti.culture", "=", "en");
                    })
                    ->whereRaw("otr.object_id = io.id")
                    ->where("t.taxonomy_id", 35)
                    ->where("ti.name", "like", "%".$this->subjectSearchFilter."%");
            });
        }

        if ($this->placeSearchFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("object_term_relation as otr")
                    ->join("term as t", "otr.term_id", "=", "t.id")
                    ->join("term_i18n as ti", function($j) {
                        $j->on("t.id", "=", "ti.id")->where("ti.culture", "=", "en");
                    })
                    ->whereRaw("otr.object_id = io.id")
                    ->where("t.taxonomy_id", 42)
                    ->where("ti.name", "like", "%".$this->placeSearchFilter."%");
            });
        }

        if ($this->genreSearchFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("object_term_relation as otr")
                    ->join("term as t", "otr.term_id", "=", "t.id")
                    ->join("term_i18n as ti", function($j) {
                        $j->on("t.id", "=", "ti.id")->where("ti.culture", "=", "en");
                    })
                    ->whereRaw("otr.object_id = io.id")
                    ->where("t.taxonomy_id", 78)
                    ->where("ti.name", "like", "%".$this->genreSearchFilter."%");
            });
        }

        if ($this->referenceCodeFilter) {
            $query->where("io.identifier", "like", "%".$this->referenceCodeFilter."%");
        }

        if ($this->extentAndMediumFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("information_object_i18n as ioi")
                    ->whereRaw("ioi.id = io.id")
                    ->where("ioi.extent_and_medium", "like", "%".$this->extentAndMediumFilter."%");
            });
        }

        if ($this->archivalHistoryFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("information_object_i18n as ioi")
                    ->whereRaw("ioi.id = io.id")
                    ->where("ioi.archival_history", "like", "%".$this->archivalHistoryFilter."%");
            });
        }

        if ($this->acquisitionFilter) {
            $query->whereExists(function($sub) {
                $sub->select(DB::raw(1))
                    ->from("information_object_i18n as ioi")
                    ->whereRaw("ioi.id = io.id")
                    ->where("ioi.acquisition", "like", "%".$this->acquisitionFilter."%");
            });
        }

        if ($this->startDateFilter || $this->endDateFilter) {
            $startDate = $this->startDateFilter;
            $endDate = $this->endDateFilter;
            $rangeType = $this->rangeTypeFilter ?? 'inclusive';

            $query->whereExists(function($sub) use ($startDate, $endDate, $rangeType) {
                $sub->select(DB::raw(1))
                    ->from("event as evt_date")
                    ->whereRaw("evt_date.object_id = io.id");

                if ($rangeType === 'exact') {
                    if ($startDate) {
                        $sub->where("evt_date.start_date", ">=", $startDate);
                    }
                    if ($endDate) {
                        $sub->where("evt_date.end_date", "<=", $endDate);
                    }
                } else {
                    // Inclusive/overlapping: event overlaps with search range
                    if ($startDate) {
                        $sub->where(function($q) use ($startDate) {
                            $q->where("evt_date.end_date", ">=", $startDate)
                              ->orWhereNull("evt_date.end_date");
                        });
                    }
                    if ($endDate) {
                        $sub->where(function($q) use ($endDate) {
                            $q->where("evt_date.start_date", "<=", $endDate)
                              ->orWhereNull("evt_date.start_date");
                        });
                    }
                }
            });
        }
    }

    public function executePrint($request)
    {
        $this->typeFilter = $request->getParameter('type');
        $this->parentId = $request->getParameter('parent');
        $this->topLevelOnly = $request->getParameter('topLevel', '1');
        $this->sort = $request->getParameter('sort', 'date');
        $this->sortDir = $request->getParameter('dir', 'desc');

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', '>', 1)
            ->select('io.id', 'io.identifier', 'i18n.title', 'i18n.scope_and_content', 'level.name as level_name', 'doc.object_type', 'slug.slug');

        if ($this->parentId) {
            $query->where('io.parent_id', $this->parentId);
            $this->parent = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($j) {
                    $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->where('io.id', $this->parentId)
                ->select('io.id', 'i18n.title')
                ->first();
        } elseif ($this->topLevelOnly === '1') {
            $query->where('io.parent_id', 1);
            $this->parent = null;
        } else {
            $this->parent = null;
        }

        if ($this->typeFilter) {
            $query->where('doc.object_type', $this->typeFilter);
        }

        // Handle sorting
        $sortDir = $this->sortDir === 'desc' ? 'desc' : 'asc';
        switch ($this->sort) {
            case 'identifier':
            case 'refcode':
                $query->orderBy('io.identifier', $sortDir);
                break;
            case 'date':
                // Sort by object id as proxy for creation order
                $query->orderBy('io.id', $sortDir);
                break;
            case 'startdate':
                $query->leftJoin('event as evt_sort', 'io.id', '=', 'evt_sort.object_id');
                $query->orderByRaw("MIN(evt_sort.start_date) $sortDir");
                $query->groupBy('io.id', 'io.identifier', 'io.parent_id', 'i18n.title', 'i18n.scope_and_content', 'level.name', 'doc.object_type', 'slug.slug');
                break;
            case 'enddate':
                $query->leftJoin('event as evt_sort', 'io.id', '=', 'evt_sort.object_id');
                $query->orderByRaw("MAX(evt_sort.end_date) $sortDir");
                $query->groupBy('io.id', 'io.identifier', 'io.parent_id', 'i18n.title', 'i18n.scope_and_content', 'level.name', 'doc.object_type', 'slug.slug');
                break;
            default:
                $query->orderBy('i18n.title', $sortDir);
        }

        $this->objects = $query->limit(500)->get()->toArray();
        $this->total = count($this->objects);

        $this->setLayout(false);
    }

    public function executeExportCsv($request)
    {
        $typeFilter = $request->getParameter('type');
        $parentId = $request->getParameter('parent');
        $topLevelOnly = $request->getParameter('topLevel', '1');
        $sort = $request->getParameter('sort', 'date');
        $sortDir = $request->getParameter('dir', 'desc');

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('repository as r', 'io.repository_id', '=', 'r.id')
            ->leftJoin('actor_i18n as repo_name', function($j) {
                $j->on('r.id', '=', 'repo_name.id')->where('repo_name.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', '>', 1)
            ->select(
                'io.id', 
                'io.identifier', 
                'i18n.title', 
                'i18n.scope_and_content',
                'i18n.extent_and_medium',
                'level.name as level_name', 
                'doc.object_type',
                'repo_name.authorized_form_of_name as repository'
            );

        if ($parentId) {
            $query->where('io.parent_id', $parentId);
        } elseif ($topLevelOnly === '1') {
            $query->where('io.parent_id', 1);
        }

        if ($typeFilter) {
            $query->where('doc.object_type', $typeFilter);
        }

        $sortColumn = match($sort) {
            'identifier' => 'io.identifier',
            'refcode' => 'io.identifier',
            'date' => 'io.id',  // Use ID as proxy for date (newer records have higher IDs)
            'startdate' => 'io.id',
            'enddate' => 'io.id',
            default => 'i18n.title'
        };
        $query->orderBy($sortColumn, $sortDir === 'desc' ? 'desc' : 'asc');

        $objects = $query->limit(5000)->get()->toArray();
        $filename = 'glam_export_' . date('Y-m-d_His') . '.csv';

        while (ob_get_level()) { ob_end_clean(); }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['ID', 'Identifier', 'Title', 'Level', 'GLAM Type', 'Repository', 'Scope and Content', 'Extent']);
        
        foreach ($objects as $obj) {
            fputcsv($output, [
                $obj->id,
                $obj->identifier,
                $obj->title,
                $obj->level_name,
                $obj->object_type,
                $obj->repository,
                $obj->scope_and_content,
                $obj->extent_and_medium
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function executeChangeType($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(403);
            return sfView::NONE;
        }

        $objectId = (int) $request->getParameter('id');
        $newType = $request->getParameter('type');
        $recursive = $request->getParameter('recursive');

        $validTypes = ['archive', 'museum', 'gallery', 'library', 'dam', 'universal'];
        if (!in_array($newType, $validTypes)) {
            $this->getUser()->setFlash('error', 'Invalid type');
            $this->redirect($request->getReferer() ?: 'display/browse');
        }

        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => $newType, 'updated_at' => date('Y-m-d H:i:s')]
        );

        $count = 1;
        if ($recursive) {
            $count += $this->applyTypeRecursive($objectId, $newType);
        }

        $this->getUser()->setFlash('success', "Type changed to '$newType' for $count object(s)");
        $this->redirect($request->getReferer() ?: 'display/browse');
    }

    protected function applyTypeRecursive(int $parentId, string $type): int
    {
        $children = DB::table('information_object')->where('parent_id', $parentId)->pluck('id')->toArray();
        $count = 0;
        foreach ($children as $childId) {
            DB::table('display_object_config')->updateOrInsert(
                ['object_id' => $childId],
                ['object_type' => $type, 'updated_at' => date('Y-m-d H:i:s')]
            );
            $count++;
            $count += $this->applyTypeRecursive($childId, $type);
        }
        return $count;
    }

    protected function buildBreadcrumb(int $objectId): array
    {
        $breadcrumb = [];
        $currentId = $objectId;
        $maxDepth = 20;

        while ($currentId > 1 && $maxDepth-- > 0) {
            $item = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($j) {
                    $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
                ->where('io.id', $currentId)
                ->select('io.id', 'io.parent_id', 'i18n.title', 'slug.slug')
                ->first();
            if (!$item) break;
            array_unshift($breadcrumb, $item);
            $currentId = $item->parent_id;
        }
        return $breadcrumb;
    }

    public function executeSetType($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $objectId = (int) $request->getParameter('object_id');
        $type = $request->getParameter('type');
        $recursive = $request->getParameter('recursive');
        $this->service->setObjectType($objectId, $type);
        if ($recursive) {
            $count = $this->service->setObjectTypeRecursive($objectId, $type);
            $this->getUser()->setFlash('success', 'Set type for ' . ($count + 1) . ' objects');
        } else {
            $this->getUser()->setFlash('success', 'Object type set');
        }
        $this->redirect($request->getReferer() ?: 'display/index');
    }

    public function executeAssignProfile($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $objectId = (int) $request->getParameter('object_id');
        $profileId = (int) $request->getParameter('profile_id');
        $context = $request->getParameter('context') ?: 'default';
        $primary = $request->getParameter('primary') ? true : false;
        $this->service->assignProfile($objectId, $profileId, $context, $primary);
        $this->getUser()->setFlash('success', 'Profile assigned');
        $this->redirect($request->getReferer() ?: 'display/index');
    }

    public function executeProfiles($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->profiles = DB::table('display_profile as dp')
            ->leftJoin('display_profile_i18n as dpi', function($j) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('dp.*', 'dpi.name', 'dpi.description')
            ->orderBy('dp.domain')->orderBy('dp.sort_order')
            ->get()->toArray();
    }

    public function executeLevels($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $domain = $request->getParameter('domain');
        $this->levels = $domain ? $this->service->getLevels($domain) : $this->service->getLevels();
        $this->currentDomain = $domain;
        $this->domains = ['archive', 'museum', 'gallery', 'library', 'dam', 'universal'];
    }

    public function executeBulkSetType($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        if ($request->isMethod('post')) {
            $parentId = (int) $request->getParameter('parent_id');
            $type = $request->getParameter('type');
            $this->service->setObjectType($parentId, $type);
            $count = $this->service->setObjectTypeRecursive($parentId, $type);
            $this->getUser()->setFlash('success', 'Updated ' . ($count + 1) . ' objects to type: ' . $type);
            $this->redirect('display/index');
        }
        $this->collections = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->where('io.parent_id', 1)
            ->select('io.id', 'io.identifier', 'i18n.title')
            ->orderBy('i18n.title')
            ->get()->toArray();
        $this->collectionTypes = $this->service->getCollectionTypes();
    }

    public function executeFields($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->fields = DB::table('display_field as df')
            ->leftJoin('display_field_i18n as dfi', function($j) {
                $j->on('df.id', '=', 'dfi.id')->where('dfi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('df.*', 'dfi.name', 'dfi.help_text')
            ->orderBy('df.field_group')->orderBy('df.sort_order')
            ->get()->toArray();
        $this->fieldGroups = ['identity', 'description', 'context', 'access', 'technical', 'admin'];
    }

    /**
     * Expand query with synonyms from thesaurus
     *
     * @param string $query Original search query
     * @return array Array of all terms to search (original + synonyms)
     */
    protected function expandQueryWithSynonyms(string $query): array
    {
        try {
            require_once $this->config('sf_plugins_dir') . '/ahgSemanticSearchPlugin/lib/Services/ThesaurusService.php';
            $thesaurus = new \AtomFramework\Services\SemanticSearch\ThesaurusService();

            $expansions = $thesaurus->expandQuery($query);

            // Start with original query terms
            $allTerms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

            // Add all synonyms
            foreach ($expansions as $term => $synonyms) {
                foreach ($synonyms as $synonym) {
                    $allTerms[] = $synonym;
                }
            }

            // Return unique terms
            return array_unique($allTerms);
        } catch (\Exception $e) {
            // If thesaurus fails, return original terms
            return preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        }
    }

    /**
     * Apply text search filter with FULLTEXT (if available) or LIKE fallback.
     * Supports both single-term and semantic (multi-term OR) search.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array|string $searchTerms Search terms (array for semantic, string for normal)
     */
    protected function applyTextSearchFilter($query, $searchTerms): void
    {
        $useFulltext = $this->isFulltextAvailable();

        if (is_array($searchTerms)) {
            // Semantic search: OR between all terms
            $query->where(function($qb) use ($searchTerms, $useFulltext) {
                foreach ($searchTerms as $term) {
                    $q = "%" . $term . "%";
                    $qb->orWhere(function($inner) use ($q, $term, $useFulltext) {
                        if ($useFulltext) {
                            $inner->whereExists(function($sub) use ($term, $q) {
                                $sub->select(DB::raw(1))
                                    ->from("information_object_i18n as ioi")
                                    ->whereRaw("ioi.id = io.id")
                                    ->where(function($w) use ($term, $q) {
                                        $w->whereRaw("MATCH(ioi.title) AGAINST(? IN NATURAL LANGUAGE MODE)", [$term])
                                          ->orWhereRaw("MATCH(ioi.scope_and_content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$term])
                                          ->orWhere("ioi.title", "like", $q)
                                          ->orWhere("ioi.scope_and_content", "like", $q);
                                    });
                            })->orWhere("io.identifier", "like", $q);
                        } else {
                            $inner->whereExists(function($sub) use ($q) {
                                $sub->select(DB::raw(1))
                                    ->from("information_object_i18n as ioi")
                                    ->whereRaw("ioi.id = io.id")
                                    ->where(function($w) use ($q) {
                                        $w->where("ioi.title", "like", $q)
                                          ->orWhere("ioi.scope_and_content", "like", $q);
                                    });
                            })->orWhere("io.identifier", "like", $q);
                        }
                    });
                }
            });
        } else {
            // Normal search: single term
            $q = "%" . $searchTerms . "%";
            if ($useFulltext) {
                $query->where(function($qb) use ($q, $searchTerms) {
                    $qb->whereExists(function($sub) use ($searchTerms, $q) {
                        $sub->select(DB::raw(1))
                            ->from("information_object_i18n as ioi")
                            ->whereRaw("ioi.id = io.id")
                            ->where(function($w) use ($searchTerms, $q) {
                                $w->whereRaw("MATCH(ioi.title) AGAINST(? IN NATURAL LANGUAGE MODE)", [$searchTerms])
                                  ->orWhereRaw("MATCH(ioi.scope_and_content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$searchTerms])
                                  ->orWhere("ioi.title", "like", $q)
                                  ->orWhere("ioi.scope_and_content", "like", $q);
                            });
                    })->orWhere("io.identifier", "like", $q);
                });
            } else {
                $query->where(function($qb) use ($q) {
                    $qb->whereExists(function($sub) use ($q) {
                        $sub->select(DB::raw(1))
                            ->from("information_object_i18n as ioi")
                            ->whereRaw("ioi.id = io.id")
                            ->where(function($w) use ($q) {
                                $w->where("ioi.title", "like", $q)
                                  ->orWhere("ioi.scope_and_content", "like", $q);
                            });
                    })->orWhere("io.identifier", "like", $q);
                });
            }
        }
    }

    /**
     * Check if FULLTEXT indexes are available on information_object_i18n.
     * Cached per request (static property).
     */
    protected static $fulltextAvailable = null;

    protected function isFulltextAvailable(): bool
    {
        if (self::$fulltextAvailable !== null) {
            return self::$fulltextAvailable;
        }

        try {
            $result = DB::select("SHOW INDEX FROM information_object_i18n WHERE Key_name = 'ft_ioi_title'");
            self::$fulltextAvailable = !empty($result);
        } catch (\Exception $e) {
            self::$fulltextAvailable = false;
        }

        return self::$fulltextAvailable;
    }

    /**
     * Try Elasticsearch/OpenSearch fuzzy search as last-resort fallback.
     * Returns matching information_object IDs or empty array.
     *
     * @param string $query Search query
     * @return array Array of matching IDs (max 200)
     */
    protected function tryElasticsearchFuzzy(string $query): array
    {
        // Guard: verify ES is reachable with a quick check
        $esHost = $this->config('app_opensearch_host', 'localhost');
        $esPort = (int) $this->config('app_opensearch_port', 9200);
        $fp = @fsockopen($esHost, $esPort, $errno, $errstr, 1);
        if (!$fp) {
            return [];
        }
        fclose($fp);

        // Determine index name from sfConfig or default pattern
        $indexName = $this->config('app_opensearch_index_name', '');
        if (empty($indexName)) {
            // Convention: {database}_qubitinformationobject
            try {
                $dbName = DB::connection()->getDatabaseName();
                $indexName = $dbName . '_qubitinformationobject';
            } catch (\Exception $e) {
                $indexName = 'atom_qubitinformationobject';
            }
        }

        // Build multi_match fuzzy query via direct curl (avoids client dependencies)
        $esHost = $this->config('app_opensearch_host', 'localhost');
        $esPort = (int) $this->config('app_opensearch_port', 9200);

        $body = [
            'size' => 200,
            '_source' => false,
            'query' => [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'i18n.en.title^3',
                        'i18n.en.scopeAndContent',
                        'creators.i18n.en.authorizedFormOfName^2',
                        'i18n.en.alternateTitle^2',
                    ],
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 1,
                    'max_expansions' => 50,
                ],
            ],
        ];

        $url = sprintf('http://%s:%d/%s/_search', $esHost, $esPort, $indexName);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);
        if (!isset($data['hits']['hits'])) {
            return [];
        }

        $ids = [];
        foreach ($data['hits']['hits'] as $hit) {
            $ids[] = (int) $hit['_id'];
        }

        return $ids;
    }

    // ── Discovery pipeline ─────────────────────────────────────────

    /**
     * Run the 4-strategy Discovery pipeline and return relevance-ranked IDs.
     *
     * @return int[] Object IDs ordered by relevance score (highest first)
     */
    protected function runDiscoveryPipeline(string $query): array
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgDiscoveryPlugin/lib/Services';
        require_once $pluginDir . '/QueryExpander.php';
        require_once $pluginDir . '/KeywordSearchStrategy.php';
        require_once $pluginDir . '/EntitySearchStrategy.php';
        require_once $pluginDir . '/HierarchicalStrategy.php';
        require_once $pluginDir . '/VectorSearchStrategy.php';
        require_once $pluginDir . '/ResultMerger.php';

        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        // Step 1: Query Expansion
        $expander = new \AhgDiscovery\Services\QueryExpander();
        $expanded = $expander->expand($query);

        $this->discoveryExpanded = [
            'keywords'    => $expanded['keywords'] ?? [],
            'phrases'     => $expanded['phrases'] ?? [],
            'synonyms'    => $expanded['synonyms'] ?? [],
            'dateRange'   => $expanded['dateRange'] ?? null,
            'entityTerms' => array_column($expanded['entityTerms'] ?? [], 'value'),
        ];

        // Step 2: Four-strategy search
        $keywordSearch = new \AhgDiscovery\Services\KeywordSearchStrategy($culture);
        $entitySearch  = new \AhgDiscovery\Services\EntitySearchStrategy();

        $keywordResults = $keywordSearch->search($expanded, 100);
        $entityResults  = $entitySearch->search($expanded, 200);

        // Vector (semantic) search — gracefully skipped if Qdrant unavailable
        $vectorResults = [];
        if (\AhgDiscovery\Services\VectorSearchStrategy::isAvailable()) {
            $vectorSearch = new \AhgDiscovery\Services\VectorSearchStrategy();
            $vectorResults = $vectorSearch->search($expanded, 50);
        }

        // Hierarchical walk on top keyword + entity results
        $hierarchicalSearch = new \AhgDiscovery\Services\HierarchicalStrategy();
        $topResults = array_merge(
            array_slice($keywordResults, 0, 10),
            array_slice($entityResults, 0, 10)
        );
        $allFoundIds = array_unique(array_merge(
            array_column($keywordResults, 'object_id'),
            array_column($entityResults, 'object_id'),
            array_column($vectorResults, 'object_id')
        ));
        $hierarchicalResults = $hierarchicalSearch->search($topResults, $allFoundIds, 20);

        // Step 3: Merge & Rank
        $merger = new \AhgDiscovery\Services\ResultMerger();
        $merged = $merger->merge($keywordResults, $entityResults, $hierarchicalResults, $vectorResults);

        $flatResults = $merged['flat_results'] ?? [];
        if (empty($flatResults)) {
            return [];
        }

        // Filter out junk records (barcodes, empty/placeholder titles)
        $allIds = array_column($flatResults, 'object_id');
        if (!empty($allIds)) {
            $junkIds = DB::table('information_object_i18n')
                ->whereIn('id', $allIds)
                ->where(function ($q) {
                    $q->where('title', 'LIKE', 'Barcode%')
                      ->orWhere('scope_and_content', '=', 'barcode')
                      ->orWhereRaw("LENGTH(TRIM(COALESCE(title, ''))) < 3");
                })
                ->pluck('id')
                ->toArray();

            if (!empty($junkIds)) {
                $junkSet = array_flip($junkIds);
                $flatResults = array_values(array_filter($flatResults, fn($r) => !isset($junkSet[$r['object_id']])));
            }
        }

        if (empty($flatResults)) {
            return [];
        }

        // Build auxiliary maps from raw strategy results for richer context
        $entityMap = [];
        foreach ($entityResults as $r) {
            $entityMap[$r['object_id']] = [
                'matched_values' => $r['matched_values'] ?? [],
                'entity_types'   => $r['entity_types'] ?? '',
            ];
        }
        $hierarchicalMap = [];
        foreach ($hierarchicalResults as $r) {
            $hierarchicalMap[$r['object_id']] = [
                'relationship_type' => $r['relationship_type'] ?? '',
                'via_object_id'     => $r['via_object_id'] ?? 0,
            ];
        }

        // Store match metadata for template display.
        // Normalize scores for display: raw scores are weighted sums (max ~0.25 for
        // single-strategy matches) which look misleadingly low to users. Scale so the
        // top result displays ~95% and others are relative to it.
        $maxRawScore = 0;
        foreach ($flatResults as $r) {
            $s = $r['score'] ?? 0;
            if ($s > $maxRawScore) {
                $maxRawScore = $s;
            }
        }
        $scoreScale = $maxRawScore > 0 ? (0.95 / $maxRawScore) : 1;

        $this->discoveryMeta = [];
        foreach ($flatResults as $r) {
            $id = $r['object_id'];
            $this->discoveryMeta[$id] = [
                'score'           => round(min(1, ($r['score'] ?? 0) * $scoreScale), 3),
                'match_reasons'   => $r['match_reasons'] ?? [],
                'highlights'      => $r['highlights'] ?? [],
                'matched_entities' => $entityMap[$id]['matched_values'] ?? [],
                'entity_types'    => $entityMap[$id]['entity_types'] ?? '',
                'relationship'    => $hierarchicalMap[$id] ?? null,
            ];
        }

        return array_column($flatResults, 'object_id');
    }

    /**
     * Apply active facet filters (type, creator, subject, etc.) to a set of Discovery IDs.
     * Returns the filtered subset preserving original order.
     *
     * @param  int[] $ids Discovery-ranked object IDs
     * @return int[] Filtered IDs in original order
     */
    protected function applyFacetFiltersToIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Check if any facet filters are active
        $hasFilters = $this->typeFilter || $this->creatorFilter || $this->subjectFilter
            || $this->placeFilter || $this->genreFilter || $this->levelFilter
            || $this->mediaFilter || $this->repoFilter || $this->hasDigital
            || $this->parentId;

        if (!$hasFilters) {
            // Publication status filter for guests
            if (!$this->isAuthenticated) {
                $published = DB::table('information_object as io')
                    ->join('status as pub_st', function ($j) {
                        $j->on('pub_st.object_id', '=', 'io.id')
                          ->where('pub_st.type_id', '=', 158)
                          ->where('pub_st.status_id', '=', 160);
                    })
                    ->whereIn('io.id', $ids)
                    ->pluck('io.id')
                    ->toArray();
                // Preserve Discovery order
                return array_values(array_intersect($ids, $published));
            }
            return $ids;
        }

        // Build a filter query on the Discovery ID set
        $q = DB::table('information_object as io')
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->whereIn('io.id', $ids)
            ->where('io.id', '>', 1);

        if (!$this->isAuthenticated) {
            $q->join('status as pub_st', function ($j) {
                $j->on('pub_st.object_id', '=', 'io.id')
                  ->where('pub_st.type_id', '=', 158)
                  ->where('pub_st.status_id', '=', 160);
            });
        }

        if ($this->parentId) {
            $q->where('io.parent_id', $this->parentId);
        }
        if ($this->typeFilter) {
            $q->where('doc.object_type', $this->typeFilter);
        }
        if ($this->hasDigital) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))->from('digital_object')
                    ->whereRaw('digital_object.object_id = io.id')
                    ->whereNull('digital_object.parent_id');
            });
        }
        if ($this->creatorFilter) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))->from('event')
                    ->whereRaw('event.object_id = io.id')
                    ->where('event.actor_id', $this->creatorFilter);
            });
        }
        if ($this->subjectFilter) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))->from('object_term_relation')
                    ->whereRaw('object_term_relation.object_id = io.id')
                    ->where('object_term_relation.term_id', $this->subjectFilter);
            });
        }
        if ($this->placeFilter) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))->from('object_term_relation')
                    ->whereRaw('object_term_relation.object_id = io.id')
                    ->where('object_term_relation.term_id', $this->placeFilter);
            });
        }
        if ($this->genreFilter) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))->from('object_term_relation')
                    ->whereRaw('object_term_relation.object_id = io.id')
                    ->where('object_term_relation.term_id', $this->genreFilter);
            });
        }
        if ($this->levelFilter) {
            $q->where('io.level_of_description_id', $this->levelFilter);
        }
        if ($this->mediaFilter) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))->from('digital_object')
                    ->whereRaw('digital_object.object_id = io.id')
                    ->whereNull('digital_object.parent_id')
                    ->whereRaw('digital_object.mime_type LIKE ?', [$this->mediaFilter . '/%']);
            });
        }
        if ($this->repoFilter) {
            $q->where('io.repository_id', $this->repoFilter);
        }

        $filteredIds = $q->pluck('io.id')->toArray();

        // Preserve Discovery relevance order
        return array_values(array_intersect($ids, $filteredIds));
    }

    /**
     * Recompute facet counts scoped to the Discovery result set.
     * Overrides the facet arrays already loaded by DynamicFacetService
     * so the sidebar shows counts relevant to the search results.
     *
     * @param int[] $ids Full Discovery ID set (unfiltered by facets)
     */
    protected function recomputeDiscoveryFacets(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        // GLAM type counts
        $this->types = DB::table('information_object as io')
            ->join('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->whereIn('io.id', $ids)
            ->whereNotNull('doc.object_type')
            ->select('doc.object_type', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('doc.object_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => (object) ['id' => $r->object_type, 'name' => ucfirst($r->object_type), 'count' => (int) $r->count])
            ->toArray();

        // Level counts
        $this->levels = DB::table('information_object as io')
            ->join('term_i18n as ti', function ($j) use ($culture) {
                $j->on('io.level_of_description_id', '=', 'ti.id')->where('ti.culture', '=', $culture);
            })
            ->whereIn('io.id', $ids)
            ->select('io.level_of_description_id as id', 'ti.name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('io.level_of_description_id', 'ti.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()->toArray();

        // Creator counts
        $this->creators = DB::table('information_object as io')
            ->join('event as e', 'io.id', '=', 'e.object_id')
            ->join('actor_i18n as ai', function ($j) use ($culture) {
                $j->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', $culture);
            })
            ->whereIn('io.id', $ids)
            ->select('e.actor_id as id', 'ai.authorized_form_of_name as name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('e.actor_id', 'ai.authorized_form_of_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()->toArray();

        // Subject counts (taxonomy_id = 35)
        $this->subjects = DB::table('information_object as io')
            ->join('object_term_relation as otr', 'io.id', '=', 'otr.object_id')
            ->join('term as t', function ($j) {
                $j->on('otr.term_id', '=', 't.id')->where('t.taxonomy_id', '=', 35);
            })
            ->join('term_i18n as ti', function ($j) use ($culture) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $culture);
            })
            ->whereIn('io.id', $ids)
            ->select('t.id', 'ti.name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()->toArray();

        // Place counts (taxonomy_id = 42)
        $this->places = DB::table('information_object as io')
            ->join('object_term_relation as otr', 'io.id', '=', 'otr.object_id')
            ->join('term as t', function ($j) {
                $j->on('otr.term_id', '=', 't.id')->where('t.taxonomy_id', '=', 42);
            })
            ->join('term_i18n as ti', function ($j) use ($culture) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $culture);
            })
            ->whereIn('io.id', $ids)
            ->select('t.id', 'ti.name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()->toArray();

        // Genre counts (taxonomy_id = 78)
        $this->genres = DB::table('information_object as io')
            ->join('object_term_relation as otr', 'io.id', '=', 'otr.object_id')
            ->join('term as t', function ($j) {
                $j->on('otr.term_id', '=', 't.id')->where('t.taxonomy_id', '=', 78);
            })
            ->join('term_i18n as ti', function ($j) use ($culture) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $culture);
            })
            ->whereIn('io.id', $ids)
            ->select('t.id', 'ti.name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()->toArray();

        // Repository counts
        $this->repositories = DB::table('information_object as io')
            ->join('actor_i18n as ai', function ($j) use ($culture) {
                $j->on('io.repository_id', '=', 'ai.id')->where('ai.culture', '=', $culture);
            })
            ->whereIn('io.id', $ids)
            ->whereNotNull('io.repository_id')
            ->select('io.repository_id as id', 'ai.authorized_form_of_name as name', DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy('io.repository_id', 'ai.authorized_form_of_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()->toArray();

        // Media type counts
        $this->mediaTypes = DB::table('information_object as io')
            ->join('digital_object as dobj', function ($j) {
                $j->on('io.id', '=', 'dobj.object_id')->whereNull('dobj.parent_id');
            })
            ->whereIn('io.id', $ids)
            ->select(DB::raw("SUBSTRING_INDEX(dobj.mime_type, '/', 1) as media_type"), DB::raw('COUNT(DISTINCT io.id) as count'))
            ->groupBy(DB::raw("SUBSTRING_INDEX(dobj.mime_type, '/', 1)"))
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => (object) ['id' => $r->media_type, 'name' => ucfirst($r->media_type), 'count' => (int) $r->count])
            ->toArray();
    }

    /**
     * AJAX endpoint for embedded GLAM browser (landing page block)
     * Returns just the browse content without full page layout
     */
    public function executeBrowseAjax($request)
    {
        // Reuse browse logic
        $this->executeBrowse($request);

        // Check if sidebar should be shown
        $this->showSidebar = $request->getParameter('showSidebar', '1') === '1';
        $this->embedded = true;

        // Disable layout - return partial only
        $this->setLayout(false);

        // Use embedded template
        $this->setTemplate('browseEmbedded');
    }

    /**
     * Get cached facet data.
     *
     * @param string $facetType The facet type, e.g. 'subject', 'subject_all', 'glam_type', 'glam_type_all'
     * @param string|null $nameField Override for the name field in returned objects (default: 'name')
     * @return array Array of facet objects with id, name, and count
     */
    protected function getCachedFacet(string $facetType, ?string $nameField = null): array
    {
        $results = DB::table('display_facet_cache')
            ->where('facet_type', $facetType)
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        if ($results->isEmpty()) {
            return [];
        }

        // Strip _all suffix to determine base type for field mapping
        $baseType = str_replace('_all', '', $facetType);
        $nameField = $nameField ?? 'name';
        return $results->map(function($row) use ($nameField, $baseType) {
            $obj = new \stdClass();
            // For glam_type and media_type, use term_name as the primary field (no id)
            if (in_array($baseType, ['glam_type', 'media_type'])) {
                $obj->$nameField = $row->term_name;
            } else {
                $obj->id = $row->term_id;
                $obj->$nameField = $row->term_name;
            }
            $obj->count = $row->count;
            return $obj;
        })->toArray();
    }

    // =========================================================================
    // USER BROWSE SETTINGS
    // =========================================================================

    /**
     * User browse settings page
     */
    public function executeBrowseSettings($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->settings = $this->modeService->getBrowseSettings();

        if ($request->isMethod('post')) {
            $data = [
                'use_glam_browse' => $request->getParameter('use_glam_browse') ? 1 : 0,
                'default_sort_field' => $request->getParameter('default_sort_field', 'updated_at'),
                'default_sort_direction' => $request->getParameter('default_sort_direction', 'desc'),
                'default_view' => $request->getParameter('default_view', 'list'),
                'items_per_page' => max(10, min(100, (int) $request->getParameter('items_per_page', 30))),
                'show_facets' => $request->getParameter('show_facets') ? 1 : 0,
                'remember_filters' => $request->getParameter('remember_filters') ? 1 : 0,
            ];

            if ($this->modeService->saveBrowseSettings($data)) {
                $this->getUser()->setFlash('success', 'Browse settings saved');
            } else {
                $this->getUser()->setFlash('error', 'Failed to save settings');
            }

            $this->redirect('display/browseSettings');
        }
    }

    /**
     * Toggle GLAM browse via AJAX
     */
    public function executeToggleGlamBrowse($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $enabled = $request->getParameter('enabled') === '1';
        $success = $this->modeService->setGlamBrowse($enabled);

        return $this->renderText(json_encode([
            'success' => $success,
            'enabled' => $enabled,
        ]));
    }

    /**
     * Save browse settings via AJAX
     */
    public function executeSaveBrowseSettings($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $data = json_decode($request->getContent(), true) ?: $request->getParameterHolder()->getAll();

        $success = $this->modeService->saveBrowseSettings($data);

        return $this->renderText(json_encode(['success' => $success]));
    }

    /**
     * Get browse settings via AJAX
     */
    public function executeGetBrowseSettings($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $settings = $this->modeService->getBrowseSettings();

        return $this->renderText(json_encode([
            'success' => true,
            'settings' => $settings,
        ]));
    }

    /**
     * Reset browse settings to defaults
     */
    public function executeResetBrowseSettings($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $success = $this->modeService->resetBrowseSettings();

        return $this->renderText(json_encode(['success' => $success]));
    }
}
