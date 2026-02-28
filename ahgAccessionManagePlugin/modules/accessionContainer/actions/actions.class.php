<?php

class accessionContainerActions extends sfActions
{
    protected function getContainerService(): \AhgAccessionManage\Services\AccessionContainerService
    {
        $intakeService = new \AhgAccessionManage\Services\AccessionIntakeService();

        return new \AhgAccessionManage\Services\AccessionContainerService(null, $intakeService);
    }

    protected function requireAuth(): int
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        return (int) $this->context->user->getAttribute('user_id');
    }

    protected function requireEditor(): int
    {
        $userId = $this->requireAuth();
        if (!$this->context->user->hasCredential('editor') && !$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        return $userId;
    }

    // =========================================================================
    // CONTAINERS
    // =========================================================================

    public function executeContainers(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getContainerService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->containers = $service->getContainers($accessionId);
        $this->containerTypes = \AhgAccessionManage\Services\AccessionContainerService::CONTAINER_TYPES;
        $this->conditionStatuses = \AhgAccessionManage\Services\AccessionContainerService::CONDITIONS;

        // Load items for each container
        $this->containerItems = [];
        foreach ($this->containers as $c) {
            $this->containerItems[$c->id] = $service->getContainerItems($c->id);
        }

        // Use Success.php template (Symfony default)
    }

    public function executeApiContainerSave(sfWebRequest $request)
    {
        $this->requireEditor();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $service = $this->getContainerService();

        $data = [
            'container_type' => $request->getParameter('container_type', 'box'),
            'label' => $request->getParameter('label', ''),
            'barcode' => $request->getParameter('barcode') ?: null,
            'location_id' => $request->getParameter('location_id') ?: null,
            'location_detail' => $request->getParameter('location_detail', ''),
            'dimensions' => $request->getParameter('dimensions', ''),
            'item_count' => $request->getParameter('item_count') ?: null,
            'weight_kg' => $request->getParameter('weight_kg') ?: null,
            'condition_status' => $request->getParameter('condition_status') ?: null,
            'notes' => $request->getParameter('notes', ''),
            'sort_order' => (int) $request->getParameter('sort_order', 0),
        ];

        $containerId = (int) $request->getParameter('container_id', 0);
        $accessionId = (int) $request->getParameter('accession_id');

        if ($containerId) {
            $service->updateContainer($containerId, $data);
            $id = $containerId;
        } else {
            $id = $service->createContainer($accessionId, $data);
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true, 'id' => $id]));
    }

    public function executeApiContainerDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $containerId = (int) $request->getParameter('id');
        $service = $this->getContainerService();
        $result = $service->deleteContainer($containerId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => $result]));
    }

    // =========================================================================
    // CONTAINER ITEMS
    // =========================================================================

    public function executeApiContainerItemSave(sfWebRequest $request)
    {
        $this->requireEditor();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $service = $this->getContainerService();

        $data = [
            'title' => $request->getParameter('title', ''),
            'description' => $request->getParameter('description', ''),
            'quantity' => (int) $request->getParameter('quantity', 1),
            'format' => $request->getParameter('format', ''),
            'date_range' => $request->getParameter('date_range', ''),
            'sort_order' => (int) $request->getParameter('sort_order', 0),
            'information_object_id' => $request->getParameter('information_object_id') ?: null,
        ];

        $itemId = (int) $request->getParameter('item_id', 0);
        $containerId = (int) $request->getParameter('container_id');

        if ($itemId) {
            $service->updateContainerItem($itemId, $data);
            $id = $itemId;
        } else {
            $id = $service->addContainerItem($containerId, $data);
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true, 'id' => $id]));
    }

    public function executeApiContainerItemDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $itemId = (int) $request->getParameter('id');
        $service = $this->getContainerService();
        $service->deleteContainerItem($itemId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true]));
    }

    public function executeApiContainerItemLink(sfWebRequest $request)
    {
        $this->requireEditor();

        $itemId = (int) $request->getParameter('id');
        $ioId = (int) $request->getParameter('information_object_id');
        $service = $this->getContainerService();
        $result = $service->linkItemToIO($itemId, $ioId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => $result]));
    }

    public function executeApiBarcodeLookup(sfWebRequest $request)
    {
        $this->requireAuth();

        $barcode = $request->getParameter('barcode', '');
        $service = $this->getContainerService();
        $result = $service->lookupBarcode($barcode);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode([
            'success' => $result !== null,
            'container' => $result,
        ]));
    }

    // =========================================================================
    // RIGHTS
    // =========================================================================

    public function executeRights(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getContainerService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->rights = $service->getRights($accessionId);
        $this->rightsBasis = \AhgAccessionManage\Services\AccessionContainerService::RIGHTS_BASIS;
        $this->restrictionTypes = \AhgAccessionManage\Services\AccessionContainerService::RESTRICTION_TYPES;
        $this->grantActs = \AhgAccessionManage\Services\AccessionContainerService::GRANT_ACTS;
        $this->grantRestrictions = \AhgAccessionManage\Services\AccessionContainerService::GRANT_RESTRICTIONS;

        // Use Success.php template (Symfony default)
    }

    public function executeApiRightsSave(sfWebRequest $request)
    {
        $this->requireEditor();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $service = $this->getContainerService();

        $data = [
            'rights_basis' => $request->getParameter('rights_basis', 'copyright'),
            'rights_holder' => $request->getParameter('rights_holder', ''),
            'rights_holder_id' => $request->getParameter('rights_holder_id') ?: null,
            'start_date' => $request->getParameter('start_date') ?: null,
            'end_date' => $request->getParameter('end_date') ?: null,
            'restriction_type' => $request->getParameter('restriction_type', 'none'),
            'conditions' => $request->getParameter('conditions', ''),
            'grant_act' => $request->getParameter('grant_act') ?: null,
            'grant_restriction' => $request->getParameter('grant_restriction') ?: null,
            'notes' => $request->getParameter('notes', ''),
            'inherit_to_children' => $request->getParameter('inherit_to_children') ? 1 : 0,
        ];

        $rightId = (int) $request->getParameter('right_id', 0);
        $accessionId = (int) $request->getParameter('accession_id');

        if ($rightId) {
            $service->updateRight($rightId, $data);
            $id = $rightId;
        } else {
            $id = $service->createRight($accessionId, $data);
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true, 'id' => $id]));
    }

    public function executeApiRightsDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $rightId = (int) $request->getParameter('id');
        $service = $this->getContainerService();
        $result = $service->deleteRight($rightId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => $result]));
    }

    public function executeApiRightsInherit(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $rightId = (int) $request->getParameter('id');
        $service = $this->getContainerService();
        $count = $service->inheritRightsToChildren($rightId, $userId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true, 'count' => $count]));
    }
}
