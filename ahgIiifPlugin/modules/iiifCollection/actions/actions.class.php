<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * IIIF Collection Management Actions
 */
class iiifCollectionActions extends AhgController
{
    protected $collectionService;

    protected function initService()
    {
        // Both layouts: atom-framework/ from a checkout, plugins/ahgRuntimePlugin
        // from a bundle. Naming only the first made this page a fatal on any
        // packaged install - 200 with an empty body, nothing logged.
        //
        // Resolved inline rather than through a shared helper: helper files are
        // loaded into template scope, not action scope, so calling one here
        // swapped a missing file for a missing function and the page stayed
        // broken in exactly the same way.
        $root = $this->config('sf_root_dir');

        foreach ([
            $root.'/plugins/ahgRuntimePlugin/src/Services/IiifCollectionService.php',
            $root.'/atom-framework/src/Services/IiifCollectionService.php',
        ] as $candidate) {
            if (is_file($candidate)) {
                require_once $candidate;

                break;
            }
        }
        $this->collectionService = new \AtoM\Framework\Services\IiifCollectionService();
    }

    /** Editors and administrators may create collections and see private ones. */
    protected function isManager(): bool
    {
        $user = $this->getUser();

        return $user->isAuthenticated() && $user->hasCredential(['editor', 'administrator'], false);
    }

    /** Administrators manage every collection; editors only the ones they created. */
    protected function canManage($collection): bool
    {
        $user = $this->getUser();
        if (!$collection || !$user->isAuthenticated()) {
            return false;
        }
        if ($user->hasCredential('administrator')) {
            return true;
        }

        return $user->hasCredential('editor')
            && null !== $collection->created_by
            && (int) $collection->created_by === (int) $user->getAttribute('user_id');
    }

    /** Signed-out users go to the login page; everyone else not allowed is refused. */
    protected function requireManage($allowed): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$allowed) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * Descriptions among these ids that this user may not see: restricted ones,
     * and drafts for anyone who is not staff. Null when it cannot be worked out,
     * so callers hide every description rather than guess.
     *
     * @return array<int,bool>|null
     */
    protected function hiddenObjectIds(array $ids): ?array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ([] === $ids) {
            return [];
        }
        try {
            $user = $this->getUser();
            $userId = $user->isAuthenticated() ? (int) $user->getAttribute('user_id') : null;
            $hidden = array_flip(array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($userId ?: null)));
            if (!$this->isManager()) {
                $drafts = \Illuminate\Database\Capsule\Manager::table('status')
                    ->whereIn('object_id', $ids)->where('type_id', 158)->where('status_id', '<>', 160)
                    ->pluck('object_id');
                foreach ($drafts as $d) {
                    $hidden[(int) $d] = true;
                }
            }

            return $hidden;
        } catch (\Throwable $e) {
            error_log('iiifCollection.visibility_failed: '.$e->getMessage());

            return null;
        }
    }

    /** Leaves out items and private subcollections this user may not see. */
    protected function visibleCollection($collection): void
    {
        $items = (array) ($collection->items ?? []);
        $hidden = $this->hiddenObjectIds(array_map(static fn ($i) => $i->object_id ?? 0, $items));
        $collection->items = array_values(array_filter($items, static function ($i) use ($hidden) {
            return empty($i->object_id) || (null !== $hidden && !isset($hidden[(int) $i->object_id]));
        }));
        if (isset($collection->subcollections) && !$this->isManager()) {
            $collection->subcollections = array_values(array_filter((array) $collection->subcollections, static fn ($c) => !empty($c->is_public)));
        }
    }

    /** Changes only arrive as a POST carrying this site's token. */
    protected function requirePostToken($request): void
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        $token = \AtomFramework\Services\CsrfService::getTokenFromRequest();
        if (null === $token || !\AtomFramework\Services\CsrfService::validateToken($token)) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * List all collections
     */
    public function executeIndex($request)
    {
        $this->initService();
        
        $parentId = $request->getParameter('parent_id');
        $this->parentId = $parentId ? (int)$parentId : null;
        // Private collections are listed for editors and administrators only.
        $this->isManager = $this->isManager();
        $this->collections = $this->collectionService->getAllCollections($this->parentId, !$this->isManager);
        $this->manageIds = [];
        foreach ($this->collections as $c) {
            if ($this->canManage($c)) {
                $this->manageIds[] = (int) $c->id;
            }
        }

        if ($this->parentId) {
            $this->parentCollection = $this->collectionService->getCollection($this->parentId);
            if ($this->parentCollection && !$this->parentCollection->is_public && !$this->isManager) {
                $this->forward404();
            }
        }
        
        $this->response->setTitle($this->context->i18n->__('IIIF Collections'));
    }

    /**
     * View a single collection
     */
    public function executeView($request)
    {
        $this->initService();
        
        $identifier = $request->getParameter('slug') ?: $request->getParameter('id');
        $this->collection = $this->collectionService->getCollection($identifier);

        // A private collection answers like a missing one, except to staff.
        if (!$this->collection || (!$this->collection->is_public && !$this->isManager())) {
            $this->forward404();
        }
        $this->canManage = $this->canManage($this->collection);
        $this->isManager = $this->isManager();
        $this->visibleCollection($this->collection);

        // Get breadcrumb trail
        $this->breadcrumbs = $this->getBreadcrumbs($this->collection);
        
        $this->response->setTitle($this->collection->display_name);
    }

    /**
     * Create new collection form
     */
    public function executeNew($request)
    {
        $this->requireManage($this->isManager());
        
        $this->initService();
        $this->parentId = $request->getParameter('parent_id');
        $this->allCollections = $this->collectionService->getAllCollections();
        
        $this->response->setTitle($this->context->i18n->__('Create Collection'));
    }

    /**
     * Create collection
     */
    public function executeCreate($request)
    {
        $this->requireManage($this->isManager());
        $this->requirePostToken($request);

        $this->initService();

        // A subcollection goes only under a collection this user manages.
        $parent = $request->getParameter('parent_id');
        if ($parent && !$this->canManage($this->collectionService->getCollection((int) $parent))) {
            $this->forward('admin', 'secure');
        }

        $data = [
            'name' => $request->getParameter('name'),
            'description' => $request->getParameter('description'),
            'attribution' => $request->getParameter('attribution'),
            'viewing_hint' => $request->getParameter('viewing_hint', 'individuals'),
            'parent_id' => $request->getParameter('parent_id') ?: null,
            'is_public' => $request->getParameter('is_public', 1),
            'created_by' => $this->getUser()->getAttribute('user_id'),
        ];

        $id = $this->collectionService->createCollection($data);

        $this->redirect('@iiif_collection_view?id=' . $id);
    }

    /**
     * Edit collection form
     */
    public function executeEdit($request)
    {
        $this->initService();

        $id = $request->getParameter('id');
        $this->collection = $this->collectionService->getCollection($id);

        if (!$this->collection) {
            $this->forward404();
        }
        $this->requireManage($this->canManage($this->collection));
        
        $this->allCollections = $this->collectionService->getAllCollections();
        
        $this->response->setTitle($this->context->i18n->__('Edit Collection: %1%', ['%1%' => $this->collection->display_name]));
    }

    /**
     * Update collection
     */
    public function executeUpdate($request)
    {
        $this->initService();

        $id = (int) $request->getParameter('id');
        $collection = $this->collectionService->getCollection($id);
        if (!$collection) {
            $this->forward404();
        }
        $this->requireManage($this->canManage($collection));
        $this->requirePostToken($request);

        $parent = $request->getParameter('parent_id');
        if ($parent && (int) $parent !== (int) $collection->parent_id
            && !$this->canManage($this->collectionService->getCollection((int) $parent))) {
            $this->forward('admin', 'secure');
        }
        
        $data = [
            'name' => $request->getParameter('name'),
            'description' => $request->getParameter('description'),
            'attribution' => $request->getParameter('attribution'),
            'viewing_hint' => $request->getParameter('viewing_hint'),
            'parent_id' => $request->getParameter('parent_id') ?: null,
            'is_public' => $request->getParameter('is_public', 0),
        ];
        
        $this->collectionService->updateCollection($id, $data);
        
        $this->redirect('@iiif_collection_view?id=' . $id);
    }

    /**
     * Delete collection
     */
    public function executeDelete($request)
    {
        $this->initService();

        $id = (int) $request->getParameter('id');
        $collection = $this->collectionService->getCollection($id);
        if (!$collection) {
            $this->forward404();
        }
        $this->requireManage($this->canManage($collection));
        $this->requirePostToken($request);
        $parentId = $collection->parent_id;
        
        $this->collectionService->deleteCollection($id);
        
        if ($parentId) {
            $this->redirect('@iiif_collection_view?id=' . $parentId);
        }
        $this->redirect('@iiif_collection_index');
    }

    /**
     * Add items to collection
     */
    public function executeAddItems($request)
    {
        $this->initService();

        $id = (int) $request->getParameter('id');
        $this->collection = $this->collectionService->getCollection($id);

        if (!$this->collection) {
            $this->forward404();
        }
        $this->requireManage($this->canManage($this->collection));

        if ($request->isMethod('post')) {
            $this->requirePostToken($request);
            $objectIds = $request->getParameter('object_ids', []);
            $includeChildren = $request->getParameter('include_children', []);
            $manifestUri = $request->getParameter('manifest_uri');

            if ($manifestUri) {
                $this->collectionService->addItem($id, [
                    'manifest_uri' => $manifestUri,
                    'label' => $request->getParameter('label'),
                    'item_type' => $request->getParameter('item_type', 'manifest'),
                ]);
            }

            if (is_array($objectIds)) {
                foreach ($objectIds as $objectId) {
                    $objectId = (int) $objectId;

                    // Add the main item
                    $this->collectionService->addItem($id, [
                        'object_id' => $objectId,
                    ]);

                    // If include_children is checked for this item, add all descendants
                    if (in_array($objectId, (array) $includeChildren)) {
                        $this->addChildrenToCollection($id, $objectId);
                    }
                }
            }

            $this->redirect('@iiif_collection_view?id=' . $id);
        }
        
        // Search for objects to add
        $this->searchQuery = $request->getParameter('q', '');
        $this->searchResults = [];
        
        if ($this->searchQuery) {
            $this->searchResults = $this->searchObjects($this->searchQuery);
        }
        
        $this->response->setTitle($this->context->i18n->__('Add Items to Collection'));
    }

    /**
     * Remove item from collection
     */
    public function executeRemoveItem($request)
    {
        $this->initService();

        $itemId = (int) $request->getParameter('item_id');
        $collectionId = (int) $request->getParameter('collection_id');
        $collection = $this->collectionService->getCollection($collectionId);
        if (!$collection) {
            $this->forward404();
        }
        $this->requireManage($this->canManage($collection));
        $this->requirePostToken($request);

        // The item must belong to the collection the user may manage.
        $belongs = \Illuminate\Database\Capsule\Manager::table('iiif_collection_item')
            ->where('id', $itemId)->where('collection_id', $collectionId)->exists();
        if (!$belongs) {
            $this->forward404();
        }

        $this->collectionService->removeItem($itemId);
        
        $this->redirect('@iiif_collection_view?id=' . $collectionId);
    }

    /**
     * Reorder items (AJAX)
     */
    public function executeReorder($request)
    {
        $this->initService();
        $this->getResponse()->setContentType('application/json');

        $collectionId = (int) $request->getParameter('collection_id');
        $token = \AtomFramework\Services\CsrfService::getTokenFromRequest();
        if (!$request->isMethod('post')
            || null === $token || !\AtomFramework\Services\CsrfService::validateToken($token)
            || !$this->canManage($this->collectionService->getCollection($collectionId))) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Forbidden']));
        }

        $itemIds = array_map('intval', (array) $request->getParameter('item_ids', []));
        
        $this->collectionService->reorderItems($collectionId, $itemIds);
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Output IIIF Collection JSON
     */
    public function executeManifest($request)
    {
        $this->initService();
        
        $identifier = $request->getParameter('slug') ?: $request->getParameter('id');
        
        try {
            $collection = $this->collectionService->getCollection($identifier);
            
            if (!$collection || (!$collection->is_public && !$this->isManager())) {
                $this->getResponse()->setStatusCode(404);
                return $this->renderText(json_encode(['error' => 'Collection not found']));
            }
            
            $hidden = $this->hiddenObjectIds(array_map(static fn ($i) => $i->object_id ?? 0, (array) ($collection->items ?? [])));
            $manager = $this->isManager();
            $json = $this->collectionService->generateCollectionJson(
                $collection->id,
                static fn ($i) => empty($i->object_id) || (null !== $hidden && !isset($hidden[(int) $i->object_id])),
                static fn ($c) => $manager || !empty($c->is_public)
            );
            
            $this->getResponse()->setContentType('application/ld+json');
            $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
            
            return $this->renderText(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    /**
     * Add all children/descendants of an object to a collection.
     */
    protected function addChildrenToCollection(int $collectionId, int $parentObjectId): void
    {
        // Get the parent's lft and rgt values for nested set query
        $parent = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->where('id', $parentObjectId)
            ->select('lft', 'rgt')
            ->first();

        if (!$parent) {
            return;
        }

        // Find all descendants with digital objects
        $children = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where('io.lft', '>', $parent->lft)
            ->where('io.rgt', '<', $parent->rgt)
            ->select('io.id')
            ->orderBy('io.lft')
            ->get();

        foreach ($children as $child) {
            $this->collectionService->addItem($collectionId, [
                'object_id' => $child->id,
            ]);
        }
    }

    /**
     * Search objects
     */
    protected function searchObjects(string $query): array
    {
        return \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where(function ($q) use ($query) {
                $q->where('i18n.title', 'LIKE', "%{$query}%")
                    ->orWhere('io.identifier', 'LIKE', "%{$query}%");
            })
            ->whereNotNull('do.id')
            ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug')
            ->limit(50)
            ->get()
            ->all();
    }

    /**
     * Get breadcrumb trail
     */
    protected function getBreadcrumbs($collection): array
    {
        $breadcrumbs = [];
        $current = $collection;
        
        while ($current) {
            array_unshift($breadcrumbs, $current);
            if ($current->parent_id) {
                $current = $this->collectionService->getCollection($current->parent_id);
            } else {
                break;
            }
        }
        
        return $breadcrumbs;
    }

    /**
     * AJAX autocomplete for objects
     */
    public function executeAutocomplete($request)
    {
        // It searches every description, drafts and restricted ones included,
        // so it serves only the staff who build collections.
        if (!$this->isManager()) {
            $this->getResponse()->setStatusCode(403);
            $this->getResponse()->setContentType('application/json');

            return $this->renderText(json_encode(['results' => []]));
        }

        $this->initService();

        $query = $request->getParameter('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            // Search for objects - include items without digital objects too (for parent selection)
            $objects = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
                ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
                ->where(function ($q) use ($query) {
                    $q->where('i18n.title', 'LIKE', "%{$query}%")
                        ->orWhere('io.identifier', 'LIKE', "%{$query}%");
                })
                ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug', 'io.lft', 'io.rgt', 'do.id as has_digital')
                ->orderByRaw('do.id IS NOT NULL DESC') // Prioritize items with digital objects
                ->limit(30)
                ->get();

            foreach ($objects as $obj) {
                // Calculate child count using nested set (children with digital objects)
                $childCount = 0;
                if ($obj->rgt - $obj->lft > 1) {
                    $childCount = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                        ->join('digital_object as do', 'io.id', '=', 'do.object_id')
                        ->where('io.lft', '>', $obj->lft)
                        ->where('io.rgt', '<', $obj->rgt)
                        ->count();
                }

                $results[] = [
                    'id' => $obj->id,
                    'text' => ($obj->identifier ? "[{$obj->identifier}] " : '') . ($obj->title ?: 'Untitled'),
                    'identifier' => $obj->identifier,
                    'title' => $obj->title,
                    'slug' => $obj->slug,
                    'hasDigital' => !empty($obj->has_digital),
                    'hasChildren' => $childCount > 0,
                    'childCount' => $childCount,
                ];
            }
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['results' => $results]));
    }
}
