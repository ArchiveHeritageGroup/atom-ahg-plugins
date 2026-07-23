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
        if (!$this->getUser()->isAuthenticated()
            || !$this->getUser()->hasCredential('editor')) {
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
