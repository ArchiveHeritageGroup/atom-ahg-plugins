<?php

/**
 * IIIF Collection Management Actions
 */
class ahgIiifCollectionActions extends sfActions
{
    protected $collectionService;

    protected function initService()
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/IiifCollectionService.php';
        $this->collectionService = new \AtomFramework\Services\IiifCollectionService();
        
        // Initialize database
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }
    }

    /**
     * List all collections
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->initService();
        
        $parentId = $request->getParameter('parent_id');
        $this->parentId = $parentId ? (int)$parentId : null;
        $this->collections = $this->collectionService->getAllCollections($this->parentId);
        
        if ($this->parentId) {
            $this->parentCollection = $this->collectionService->getCollection($this->parentId);
        }
        
        $this->response->setTitle($this->context->i18n->__('IIIF Collections'));
    }

    /**
     * View a single collection
     */
    public function executeView(sfWebRequest $request)
    {
        $this->initService();
        
        $identifier = $request->getParameter('slug') ?: $request->getParameter('id');
        $this->collection = $this->collectionService->getCollection($identifier);
        
        if (!$this->collection) {
            $this->forward404();
        }
        
        // Get breadcrumb trail
        $this->breadcrumbs = $this->getBreadcrumbs($this->collection);
        
        $this->response->setTitle($this->collection->display_name);
    }

    /**
     * Create new collection form
     */
    public function executeNew(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        $this->initService();
        $this->parentId = $request->getParameter('parent_id');
        $this->allCollections = $this->collectionService->getAllCollections();
        
        $this->response->setTitle($this->context->i18n->__('Create Collection'));
    }

    /**
     * Create collection
     */
    public function executeCreate(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'new']);
        }
        
        $this->initService();
        
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
        
        $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $id]);
    }

    /**
     * Edit collection form
     */
    public function executeEdit(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        $this->initService();
        
        $id = $request->getParameter('id');
        $this->collection = $this->collectionService->getCollection($id);
        
        if (!$this->collection) {
            $this->forward404();
        }
        
        $this->allCollections = $this->collectionService->getAllCollections();
        
        $this->response->setTitle($this->context->i18n->__('Edit Collection: %1%', ['%1%' => $this->collection->display_name]));
    }

    /**
     * Update collection
     */
    public function executeUpdate(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'index']);
        }
        
        $this->initService();
        
        $id = $request->getParameter('id');
        
        $data = [
            'name' => $request->getParameter('name'),
            'description' => $request->getParameter('description'),
            'attribution' => $request->getParameter('attribution'),
            'viewing_hint' => $request->getParameter('viewing_hint'),
            'parent_id' => $request->getParameter('parent_id') ?: null,
            'is_public' => $request->getParameter('is_public', 0),
        ];
        
        $this->collectionService->updateCollection($id, $data);
        
        $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $id]);
    }

    /**
     * Delete collection
     */
    public function executeDelete(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        $this->initService();
        
        $id = $request->getParameter('id');
        $collection = $this->collectionService->getCollection($id);
        $parentId = $collection ? $collection->parent_id : null;
        
        $this->collectionService->deleteCollection($id);
        
        if ($parentId) {
            $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $parentId]);
        }
        $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'index']);
    }

    /**
     * Add items to collection
     */
    public function executeAddItems(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        $this->initService();
        
        $id = $request->getParameter('id');
        $this->collection = $this->collectionService->getCollection($id);
        
        if (!$this->collection) {
            $this->forward404();
        }
        
        if ($request->isMethod('post')) {
            $objectIds = $request->getParameter('object_ids', []);
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
                    $this->collectionService->addItem($id, [
                        'object_id' => (int)$objectId,
                    ]);
                }
            }
            
            $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $id]);
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
    public function executeRemoveItem(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('@login');
        }
        
        $this->initService();
        
        $itemId = $request->getParameter('item_id');
        $collectionId = $request->getParameter('collection_id');
        
        $this->collectionService->removeItem($itemId);
        
        $this->redirect(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $collectionId]);
    }

    /**
     * Reorder items (AJAX)
     */
    public function executeReorder(sfWebRequest $request)
    {
        $this->initService();
        
        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }
        
        $collectionId = $request->getParameter('collection_id');
        $itemIds = $request->getParameter('item_ids', []);
        
        $this->collectionService->reorderItems($collectionId, $itemIds);
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Output IIIF Collection JSON
     */
    public function executeManifest(sfWebRequest $request)
    {
        $this->initService();
        
        $identifier = $request->getParameter('slug') ?: $request->getParameter('id');
        
        try {
            $collection = $this->collectionService->getCollection($identifier);
            
            if (!$collection || (!$collection->is_public && !$this->getUser()->isAuthenticated())) {
                $this->getResponse()->setStatusCode(404);
                return $this->renderText(json_encode(['error' => 'Collection not found']));
            }
            
            $json = $this->collectionService->generateCollectionJson($collection->id);
            
            $this->getResponse()->setContentType('application/ld+json');
            $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
            
            return $this->renderText(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);
            return $this->renderText(json_encode(['error' => $e->getMessage()]));
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
    public function executeAutocomplete(sfWebRequest $request)
    {
        $this->initService();
        
        $query = $request->getParameter('q', '');
        $results = [];
        
        if (strlen($query) >= 2) {
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
                ->whereNotNull('do.id')
                ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug')
                ->limit(20)
                ->get();
            
            foreach ($objects as $obj) {
                $results[] = [
                    'id' => $obj->id,
                    'text' => ($obj->identifier ? "[{$obj->identifier}] " : '') . ($obj->title ?: 'Untitled'),
                    'identifier' => $obj->identifier,
                    'title' => $obj->title,
                    'slug' => $obj->slug,
                ];
            }
        }
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode(['results' => $results]));
    }
}
