<?php

use AhgStorageManage\Services\StorageLocationService;
use AhgStorageManage\Services\StorageMovementService;
use AtomFramework\Http\Controllers\AhgController;

/**
 * Hierarchical storage locations: browse, view, create, edit, delete.
 *
 * The service is built in preExecute(), not in a constructor. sfComponent's
 * constructor takes three required arguments and Symfony calls it itself, so a
 * zero-argument parent::__construct() here is an ArgumentCountError on every
 * request. strongroom/actions does the same thing the same way.
 */
class storageLocationActions extends AhgController
{
    /** Rows per page, matching the SimplePager the rest of this plugin uses. */
    public const PER_PAGE = 30;

    protected StorageLocationService $locationService;
    protected StorageMovementService $movementService;

    public function preExecute()
    {
        parent::preExecute();

        $this->locationService = new StorageLocationService($this->culture());
        $this->movementService = new StorageMovementService($this->culture());
    }

    public function executeBrowse($request)
    {
        $label = $this->config('app_ui_label_storage_location', 'Storage Locations');
        $this->response->setTitle(__('Browse %1%', ['%1%' => $label]).' - '.$this->response->getTitle());

        $search = trim((string) $request->getParameter('search', ''));
        $type = trim((string) $request->getParameter('type', ''));
        $parentId = $request->getParameter('parent_id', null);

        $params = [];

        if ('' !== $search) {
            $params['search'] = $search;
        }

        if ('' !== $type) {
            $params['type'] = $type;
        }

        if (null !== $parentId && '' !== $parentId) {
            $params['parent_id'] = (int) $parentId;
        }

        $all = $this->locationService->getLocations($params);

        // Page in the action, so the pager reflects what is actually shown. The
        // template used to render page links over the whole table, and every page
        // returned the same rows.
        $page = max(1, (int) $request->getParameter('page', 1));
        $pages = max(1, (int) ceil(count($all) / self::PER_PAGE));
        $page = min($page, $pages);

        $this->locations = array_slice($all, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        $this->total = count($all);
        $this->page = $page;
        $this->pages = $pages;
        $this->tree = $this->locationService->getLocationTree();
        $this->types = StorageLocationService::TYPES;
        $this->search = $search;
        $this->type = $type;
        $this->parentId = $parentId;
    }

    public function executeView($request)
    {
        $this->location = $this->findLocation($request);

        $id = (int) $this->location['id'];
        $this->path = $this->locationService->getLocationPath($id);
        $this->children = $this->locationService->getChildren($id);
        $this->descendants = $this->locationService->getDescendants($id);
        $this->objects = $this->movementService->objectsIn($id);
        $this->movements = $this->movementService->historyForLocation($id, 20);
        // Somewhere to move things to: anywhere but here.
        $this->destinations = array_values(array_filter(
            $this->locationService->getLocations(),
            static function ($candidate) use ($id) { return (int) $candidate['id'] !== $id; }
        ));
    }

    /**
     * Move the selected objects to another location, in one batch.
     *
     * The whole move is one transaction and one batch id, so a relocation that
     * half succeeded cannot leave the shelf list disagreeing with the history.
     */
    public function executeMoveObjects($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $fromId = (int) $request->getParameter('id');

        if (!$fromId) {
            $this->forward404();
        }

        $objectIds = array_filter(array_map('intval', (array) $request->getParameter('objects', [])));
        $toRaw = $request->getParameter('to_location_id');
        $toId = (null === $toRaw || '' === $toRaw) ? null : (int) $toRaw;

        if (!$objectIds) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('Select at least one object to move.'));
            $this->redirect(['module' => 'storageLocation', 'action' => 'view', 'id' => $fromId]);
        }

        try {
            $moved = $this->movementService->moveObjects($objectIds, $toId, [
                'note' => $request->getParameter('note'),
            ]);

            $this->getUser()->setFlash('notice', $this->context->i18n->__(
                '%1% object(s) moved.',
                ['%1%' => count($moved)]
            ));
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__(
                'Error moving objects: %1%',
                ['%1%' => $e->getMessage()]
            ));
        }

        $this->redirect(['module' => 'storageLocation', 'action' => 'view', 'id' => $fromId]);
    }

    public function executeCreate($request)
    {
        $label = $this->config('app_ui_label_storage_location', 'Storage Location');
        $this->response->setTitle(__('Create %1%', ['%1%' => $label]).' - '.$this->response->getTitle());

        $parentId = $request->getParameter('parent_id');
        $parentLocation = null;

        if (!empty($parentId)) {
            $parentLocation = $this->locationService->getLocationById((int) $parentId);

            if (null === $parentLocation) {
                $parentId = null;   // a parent that does not exist is no parent
            }
        }

        $this->parentId = $parentId;
        $this->parentLocation = $parentLocation;
        $this->path = null === $parentLocation ? [] : $this->locationService->getLocationPath((int) $parentLocation['id']);
        // Flat, so every level can be chosen as a parent. The hierarchical form
        // returns roots only, which made rooms impossible to nest under a floor.
        $this->allLocations = $this->locationService->getLocations();
        $this->types = StorageLocationService::TYPES;
        $this->location = null;
    }

    public function executeSave($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        try {
            $location = $this->locationService->createLocation($this->formData($request));

            $this->getUser()->setFlash('notice', $this->context->i18n->__('Storage location created.'));
            $this->redirect(['module' => 'storageLocation', 'action' => 'view', 'id' => $location['id']]);
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__(
                'Error creating storage location: %1%',
                ['%1%' => $e->getMessage()]
            ));
            $this->redirect(['module' => 'storageLocation', 'action' => 'create']);
        }
    }

    public function executeEdit($request)
    {
        $this->location = $this->findLocation($request);

        $label = $this->config('app_ui_label_storage_location', 'Storage Location');
        $this->response->setTitle(__('Edit %1%', ['%1%' => $label]).' - '.$this->response->getTitle());

        $id = (int) $this->location['id'];
        $this->path = $this->locationService->getLocationPath($id);
        $this->types = StorageLocationService::TYPES;

        // A location cannot be its own parent, and cannot move inside its own
        // subtree - so neither belongs in the list.
        $exclude = [$id];

        foreach ($this->locationService->getDescendants($id) as $descendant) {
            $exclude[] = (int) $descendant['id'];
        }

        $this->allLocations = array_values(array_filter(
            $this->locationService->getLocations(),
            static function ($candidate) use ($exclude) { return !in_array((int) $candidate['id'], $exclude, true); }
        ));
    }

    public function executeUpdate($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404();
        }

        try {
            $location = $this->locationService->updateLocation($id, $this->formData($request));

            $this->getUser()->setFlash('notice', $this->context->i18n->__('Storage location updated.'));
            $this->redirect(['module' => 'storageLocation', 'action' => 'view', 'id' => $location['id']]);
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__(
                'Error updating storage location: %1%',
                ['%1%' => $e->getMessage()]
            ));
            $this->redirect(['module' => 'storageLocation', 'action' => 'edit', 'id' => $id]);
        }
    }

    public function executeDelete($request)
    {
        $location = $this->findLocation($request);
        $id = (int) $location['id'];

        // GET shows the confirmation page; only POST deletes.
        if (!$request->isMethod('post')) {
            $this->location = $location;
            $this->path = $this->locationService->getLocationPath($id);
            $this->children = $this->locationService->getChildren($id);
            $this->descendants = $this->locationService->getDescendants($id);

            return sfView::SUCCESS;
        }

        try {
            if ($this->locationService->deleteLocation($id)) {
                $this->getUser()->setFlash('notice', $this->context->i18n->__('Storage location deleted.'));
            } else {
                $this->getUser()->setFlash('error', $this->context->i18n->__('Storage location could not be deleted.'));
            }

            $this->redirect(['module' => 'storageLocation', 'action' => 'browse']);
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__(
                'Error deleting storage location: %1%',
                ['%1%' => $e->getMessage()]
            ));
            $this->redirect(['module' => 'storageLocation', 'action' => 'view', 'id' => $id]);
        }
    }

    public function executeApiLocations($request)
    {
        $params = [];

        foreach (['search', 'type'] as $key) {
            $value = trim((string) $request->getParameter($key, ''));

            if ('' !== $value) {
                $params[$key] = $value;
            }
        }

        $parentId = $request->getParameter('parent_id', null);

        if (null !== $parentId && '' !== $parentId) {
            $params['parent_id'] = (int) $parentId;
        }

        return $this->json($this->locationService->getLocations($params));
    }

    public function executeApiTree($request)
    {
        $parentId = $request->getParameter('parent_id', null);

        return $this->json($this->locationService->getLocationTree(
            null === $parentId || '' === $parentId ? null : (int) $parentId
        ));
    }

    public function executeApiSearch($request)
    {
        $query = trim((string) $request->getParameter('q', ''));

        if ('' === $query) {
            return $this->json([], false, 'Search query is required');
        }

        return $this->json($this->locationService->searchLocations($query));
    }

    /** The location named by the request, or a 404. */
    protected function findLocation($request): array
    {
        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404();
        }

        $location = $this->locationService->getLocationById($id);

        if (null === $location) {
            $this->forward404();
        }

        return $location;
    }

    /** The editable fields, taken from the submitted form. */
    protected function formData($request): array
    {
        $data = [];

        foreach (['name', 'description', 'location_type', 'parent_id', 'capacity_value', 'capacity_unit', 'notes'] as $field) {
            $data[$field] = $request->getParameter($field);
        }

        return $data;
    }

    /**
     * Send a JSON response through the response object.
     *
     * echo followed by exit skips sfResponse::sendHttpHeaders(), so the content
     * type that was just set never reaches the client and the body goes out as
     * text/html. It also skips the filter chain.
     */
    protected function json(array $data, bool $success = true, ?string $message = null)
    {
        $payload = ['success' => $success, 'data' => $data, 'count' => count($data)];

        if (null !== $message) {
            $payload['message'] = $message;
        }

        $this->response->setContentType('application/json');
        $this->response->setContent(json_encode($payload));

        return sfView::NONE;
    }
}
