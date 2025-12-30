<?php
use Illuminate\Database\Capsule\Manager as DB;

class ahgDisplayActions extends sfActions
{
    protected $service;

    public function preExecute()
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayTypeDetector.php';
        $this->service = new DisplayService();
    }

    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }

        $this->profiles = DB::table('display_profile as dp')
            ->leftJoin('display_profile_i18n as dpi', function($j) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', 'en');
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

    public function executeBrowse(sfWebRequest $request)
    {
        // Get all filter parameters
        $this->typeFilter = $request->getParameter('type');
        $this->parentId = $request->getParameter('parent');
        $this->topLevelOnly = $request->getParameter('topLevel', '0');
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $this->limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10; if ($this->limit < 10) $this->limit = 10; if ($this->limit > 100) $this->limit = 100; 
        $this->sort = $request->getParameter('sort', 'title');
        $this->sortDir = $request->getParameter('dir', 'asc');
        $this->viewMode = $request->getParameter('view', 'card');
        $this->hasDigital = $request->getParameter('hasDigital');
        
        // New facet filters
        $this->creatorFilter = $request->getParameter('creator');
        $this->subjectFilter = $request->getParameter('subject');
        $this->placeFilter = $request->getParameter('place');
        $this->genreFilter = $request->getParameter('genre');
        $this->levelFilter = $request->getParameter('level');
        $this->mediaFilter = $request->getParameter('media');
        $this->repoFilter = $request->getParameter('repo');

        // GLAM Type facet
        $this->types = DB::table('display_object_config')
            ->select('object_type', DB::raw('COUNT(*) as count'))
            ->groupBy('object_type')
            ->orderBy('count', 'desc')
            ->get()->toArray();

        // Level of description facet
        $this->levels = DB::table('information_object as io')
            ->join('term as t', 'io.level_of_description_id', '=', 't.id')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('io.id', '>', 1)
            ->select('t.id', 'ti.name', DB::raw('COUNT(*) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Repository facet
        $this->repositories = DB::table('information_object as io')
            ->join('repository as r', 'io.repository_id', '=', 'r.id')
            ->join('actor_i18n as ai', function($j) {
                $j->on('r.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('io.id', '>', 1)
            ->select('r.id', 'ai.authorized_form_of_name as name', DB::raw('COUNT(*) as count'))
            ->groupBy('r.id', 'ai.authorized_form_of_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Creator facet
        $this->creators = DB::table('event as e')
            ->join('actor as a', 'e.actor_id', '=', 'a.id')
            ->join('actor_i18n as ai', function($j) {
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->whereNotNull('e.actor_id')
            ->select('a.id', 'ai.authorized_form_of_name as name', DB::raw('COUNT(DISTINCT e.object_id) as count'))
            ->groupBy('a.id', 'ai.authorized_form_of_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Subject facet (taxonomy_id = 35)
        $this->subjects = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', 35)
            ->select('t.id', 'ti.name', DB::raw('COUNT(*) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Place facet (taxonomy_id = 42)
        $this->places = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', 42)
            ->select('t.id', 'ti.name', DB::raw('COUNT(*) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Genre facet (taxonomy_id = 78)
        $this->genres = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', 78)
            ->select('t.id', 'ti.name', DB::raw('COUNT(*) as count'))
            ->groupBy('t.id', 'ti.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Media type facet
        $this->mediaTypes = DB::table('digital_object as do')
            ->whereNull('do.parent_id')
            ->whereNotNull('do.mime_type')
            ->select(DB::raw('SUBSTRING_INDEX(mime_type, "/", 1) as media_type'), DB::raw('COUNT(*) as count'))
            ->groupBy('media_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()->toArray();

        // Build count query
        $countQuery = DB::table('information_object as io')
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->where('io.id', '>', 1);

        // Apply all filters to count query
        $this->applyFilters($countQuery);

        $this->total = $countQuery->count();
        $this->totalPages = (int) ceil($this->total / $this->limit);

        // Build main query
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', 'en');
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
        $this->applyFilters($query);

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
        $sortColumn = match($this->sort) {
            'identifier' => 'io.identifier',
            'refcode' => 'io.identifier',
            default => 'i18n.title'
        };
        $query->orderBy($sortColumn, $this->sortDir === 'desc' ? 'desc' : 'asc');

        // Paginate
        $this->objects = $query
            ->offset(($this->page - 1) * $this->limit)
            ->limit($this->limit)
            ->get()
            ->toArray();

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
            if (!$obj->thumbnail) {
                $libraryItem = DB::table('library_item')
                    ->where('information_object_id', $obj->id)
                    ->select('cover_url')
                    ->first();
                if ($libraryItem && $libraryItem->cover_url) {
                    $obj->thumbnail = $libraryItem->cover_url;
                    $obj->has_digital = true;
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
    }

    public function executePrint(sfWebRequest $request)
    {
        $this->typeFilter = $request->getParameter('type');
        $this->parentId = $request->getParameter('parent');
        $this->topLevelOnly = $request->getParameter('topLevel', '0');
        $this->sort = $request->getParameter('sort', 'title');
        $this->sortDir = $request->getParameter('dir', 'asc');

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', 'en');
            })
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->where('io.id', '>', 1)
            ->select('io.id', 'io.identifier', 'i18n.title', 'i18n.scope_and_content', 'level.name as level_name', 'doc.object_type');

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

        $sortColumn = match($this->sort) {
            'identifier' => 'io.identifier',
            'refcode' => 'io.identifier',
            default => 'i18n.title'
        };
        $query->orderBy($sortColumn, $this->sortDir === 'desc' ? 'desc' : 'asc');

        $this->objects = $query->limit(500)->get()->toArray();
        $this->total = count($this->objects);

        $this->setLayout(false);
    }

    public function executeExportCsv(sfWebRequest $request)
    {
        $typeFilter = $request->getParameter('type');
        $parentId = $request->getParameter('parent');
        $topLevelOnly = $request->getParameter('topLevel', '0');
        $sort = $request->getParameter('sort', 'title');
        $sortDir = $request->getParameter('dir', 'asc');

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', 'en');
            })
            ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('repository as r', 'io.repository_id', '=', 'r.id')
            ->leftJoin('actor_i18n as repo_name', function($j) {
                $j->on('r.id', '=', 'repo_name.id')->where('repo_name.culture', '=', 'en');
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

    public function executeChangeType(sfWebRequest $request)
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

    public function executeSetType(sfWebRequest $request)
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

    public function executeAssignProfile(sfWebRequest $request)
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

    public function executeProfiles(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->profiles = DB::table('display_profile as dp')
            ->leftJoin('display_profile_i18n as dpi', function($j) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', 'en');
            })
            ->select('dp.*', 'dpi.name', 'dpi.description')
            ->orderBy('dp.domain')->orderBy('dp.sort_order')
            ->get()->toArray();
    }

    public function executeLevels(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $domain = $request->getParameter('domain');
        $this->levels = $domain ? $this->service->getLevels($domain) : $this->service->getLevels();
        $this->currentDomain = $domain;
        $this->domains = ['archive', 'museum', 'gallery', 'library', 'dam', 'universal'];
    }

    public function executeBulkSetType(sfWebRequest $request)
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

    public function executeFields(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->fields = DB::table('display_field as df')
            ->leftJoin('display_field_i18n as dfi', function($j) {
                $j->on('df.id', '=', 'dfi.id')->where('dfi.culture', '=', 'en');
            })
            ->select('df.*', 'dfi.name', 'dfi.help_text')
            ->orderBy('df.field_group')->orderBy('df.sort_order')
            ->get()->toArray();
        $this->fieldGroups = ['identity', 'description', 'context', 'access', 'technical', 'admin'];
    }
}
