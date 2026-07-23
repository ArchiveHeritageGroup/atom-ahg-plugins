<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ahgRicManagePlugin - ricManage module.
 *
 * AJAX capture surface for a record's RiC metadata plus a per-record RiC-O
 * JSON-LD export. The RiC panel on the record view (see extension.json) reads
 * via GET and, for editors, writes via POST.
 */
class ricManageActions extends AhgController
{
    /**
     * Edit/create a Records in Context (RiC) description.
     *
     * Reached via forward() from ioManage::executeEdit when the RiC standard is
     * detected (IoFormHelper::MODULE_MAP['ric'] = 'ricManage'). Renders the RiC
     * edit form (archival fields + RiC-specific fields) and, on POST, persists
     * the RiC metadata (entity type + properties) alongside the shared IO save.
     */
    public function executeEdit($request)
    {
        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL - require editor/admin (forward() creates a fresh action instance).
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID) || $user->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID))
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        \IoFormHelper::loadIoData($this, $request, $culture);
        \IoFormHelper::loadDropdowns($this, $culture);

        // RiC-specific data for the form.
        $svc = new \AhgRicManage\Services\RicManageService();
        $objectId = !empty($this->io['id']) ? (int) $this->io['id'] : 0;
        $this->ricEntityTypes = \AhgRicManage\Services\RicManageService::ENTITY_TYPES;
        $this->ricPropFields = \AhgRicManage\Services\RicManageService::PROPERTY_FIELDS;
        $this->ricMeta = $objectId
            ? $svc->getRecordMeta($objectId)
            : ['entity_type' => 'Record', 'properties' => array_fill_keys(array_keys(\AhgRicManage\Services\RicManageService::PROPERTY_FIELDS), '')];

        if ($request->isMethod('post')) {
            // NB: RiC metadata is NOT saved here. Any ric_record_meta write in the
            // same request silently voids handlePost's information-object update
            // (a Propel/Illuminate shared-transaction interaction that persisted
            // even on a separate PDO). The RiC edit form instead saves the RiC
            // metadata via a prior AJAX POST to /ricManage/save (its own request)
            // before submitting the IO fields here. So this action only runs the
            // standard IO save.
            \IoFormHelper::handlePost($this, $request, $culture);
        }
    }

    /**
     * /ric/add - the clean "add a RiC record" entry point (Add menu). Forwards to
     * the shared IO add form with standard=ric preset, which dispatches to the RiC
     * add form. Accepts ?parent=<id> to create it under a given record.
     */
    public function executeAdd($request)
    {
        $request->setParameter('standard', 'ric');

        $this->forward('ioManage', 'edit');
    }

    /**
     * GET /ricManage/get/:objectId - return the record's RiC metadata + relations.
     */
    public function executeGet($request)
    {
        $objectId = (int) $request->getParameter('objectId');
        if ($objectId <= 0) {
            return $this->json(['success' => false, 'error' => 'Missing object id']);
        }

        $culture = $this->getUser()->getCulture() ?: 'en';
        $svc = new \AhgRicManage\Services\RicManageService();
        $meta = $svc->getRecordMeta($objectId);

        return $this->json([
            'success' => true,
            'entity_type' => $meta['entity_type'],
            'properties' => $meta['properties'],
            'relations' => $svc->getTypedRelations($objectId, $culture),
        ]);
    }

    /**
     * POST /ricManage/save - upsert a record's RiC entity type + properties.
     */
    public function executeSave($request)
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID) || $user->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID))) {
            return $this->json(['success' => false, 'error' => 'Not authorised'], 403);
        }

        $objectId = (int) $request->getPostParameter('object_id');
        if ($objectId <= 0) {
            return $this->json(['success' => false, 'error' => 'Missing object id']);
        }

        $entityType = (string) $request->getPostParameter('entity_type', 'Record');
        $properties = (array) $request->getPostParameter('properties', []);

        try {
            $svc = new \AhgRicManage\Services\RicManageService();
            $svc->saveRecordMeta($objectId, $entityType, $properties);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => 'Save failed: ' . $e->getMessage()]);
        }

        return $this->json(['success' => true]);
    }

    /**
     * GET /ricManage/export/:objectId - RiC-O JSON-LD for one record.
     */
    public function executeExport($request)
    {
        $objectId = (int) $request->getParameter('objectId');
        if ($objectId <= 0) {
            return $this->json(['success' => false, 'error' => 'Missing object id']);
        }

        $culture = $this->getUser()->getCulture() ?: 'en';
        $svc = new \AhgRicManage\Services\RicManageService();
        $doc = $svc->exportRicO($objectId, $culture);

        $this->getResponse()->setContentType('application/ld+json; charset=utf-8');

        return $this->renderText(json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Emit a JSON response with HTTP 200 (AtoM turns 4xx into a themed error
     * page, so status travels in the body - see the AtoM POST/JSON quirk).
     */
    private function json(array $data, int $logicalStatus = 200)
    {
        $data['_status'] = $logicalStatus;
        $this->getResponse()->setContentType('application/json; charset=utf-8');

        return $this->renderText(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
