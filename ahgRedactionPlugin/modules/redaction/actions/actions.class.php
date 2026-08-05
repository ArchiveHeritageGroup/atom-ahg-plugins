<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Visual redaction editor.
 *
 * Ported from ahgPrivacyPlugin's privacyAdmin module, keeping only the manual
 * path: the NER-driven region suggestions, the PII scanner and the privacy
 * dashboard are not part of this plugin.
 */
class redactionActions extends AhgController
{
    protected function service(): \AhgRedactionPlugin\Service\VisualRedactionService
    {
        require_once sfConfig::get('sf_plugins_dir').'/ahgRedactionPlugin/lib/Service/VisualRedactionService.php';

        return new \AhgRedactionPlugin\Service\VisualRedactionService();
    }

    /**
     * Every mutating action is a POST carrying a CSRF token.
     *
     * The originals accepted a bare POST with no token, so any page on any site
     * could delete another institution's redactions with a form submission. They
     * also answered a plain JSON error on the wrong method rather than refusing.
     */
    protected function guardWrite($request): ?string
    {
        if (!$request->isMethod('post')) {
            return 'POST method required';
        }

        if (!class_exists('\AtomFramework\Services\CsrfService')) {
            return null;
        }

        $token = $request->getParameter(\AtomFramework\Services\CsrfService::FIELD_NAME)
            ?: $request->getHttpHeader(\AtomFramework\Services\CsrfService::HEADER_NAME);

        return \AtomFramework\Services\CsrfService::validateToken((string) $token)
            ? null
            : 'CSRF token validation failed';
    }

    protected function json(array $payload): string
    {
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($payload));
    }

    /**
     * The editor page.
     */
    public function executeEditor($request)
    {
        $objectId = (int) $request->getParameter('id');

        if (!$objectId) {
            $this->forward404('Object id required');

            return;
        }

        $this->object = QubitInformationObject::getById($objectId);

        if (!$this->object) {
            $this->forward404('Record not found');

            return;
        }

        $service = $this->service();
        $this->docInfo = $service->getDocumentInfo($objectId);

        if ($this->docInfo) {
            $this->docInfo['url'] = $this->displayUrl($objectId);
        }

        $this->regions = $service->getRegionsForObject($objectId);
        $this->csrfToken = class_exists('\AtomFramework\Services\CsrfService')
            ? \AtomFramework\Services\CsrfService::generateToken()
            : '';
    }

    /**
     * Regions for an object, optionally for one page.
     */
    public function executeGetRegions($request)
    {
        $objectId = (int) $request->getParameter('id');

        if (!$objectId) {
            return $this->json(['success' => false, 'error' => 'Object id required']);
        }

        try {
            $page = $request->getParameter('page');
            $regions = $this->service()->getRegionsForObject($objectId, $page ? (int) $page : null);

            return $this->json([
                'success' => true,
                'regions' => $this->decodeCoordinates($regions),
                'count' => $regions->count(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save the regions drawn on one page.
     */
    public function executeSave($request)
    {
        if (null !== $error = $this->guardWrite($request)) {
            return $this->json(['success' => false, 'error' => $error]);
        }

        // The annotator posts a JSON body, so the regions are not request parameters.
        $data = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($data)) {
            return $this->json(['success' => false, 'error' => 'Invalid JSON data']);
        }

        $objectId = (int) ($data['object_id'] ?? 0);
        $page = (int) ($data['page'] ?? 1);

        if (!$objectId) {
            return $this->json(['success' => false, 'error' => 'Object id required']);
        }

        try {
            $service = $this->service();
            $saved = $service->batchSaveRegions($objectId, $page, $data['regions'] ?? [], $this->userId());
            $regions = $service->getRegionsForObject($objectId, $page);

            return $this->json([
                'success' => true,
                'message' => 'Regions saved',
                'saved_count' => count($saved),
                'regions' => $this->decodeCoordinates($regions),
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Remove one region.
     */
    public function executeDelete($request)
    {
        if (null !== $error = $this->guardWrite($request)) {
            return $this->json(['success' => false, 'error' => $error]);
        }

        $regionId = (int) $request->getParameter('region_id');

        if (!$regionId) {
            return $this->json(['success' => false, 'error' => 'Region id required']);
        }

        try {
            $deleted = $this->service()->deleteRegion($regionId);

            return $this->json([
                'success' => $deleted,
                'message' => $deleted ? 'Region deleted' : 'Region not found',
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Burn the saved regions into a redacted copy.
     *
     * The original file is never touched - the output is written beside it and
     * recorded in redaction_cache, so removing a region and applying again gives
     * back what it covered.
     */
    public function executeApply($request)
    {
        if (null !== $error = $this->guardWrite($request)) {
            return $this->json(['success' => false, 'error' => $error]);
        }

        $objectId = (int) $request->getParameter('object_id');

        if (!$objectId) {
            return $this->json(['success' => false, 'error' => 'Object id required']);
        }

        try {
            return $this->json($this->service()->applyRedactions($objectId, $this->userId()));
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Document metadata the annotator needs before it can render: page count,
     * dimensions and the path to the file.
     */
    public function executeDocument($request)
    {
        $objectId = (int) $request->getParameter('id');

        if (!$objectId) {
            return $this->json(['success' => false, 'error' => 'Object id required']);
        }

        try {
            $info = $this->service()->getDocumentInfo($objectId);

            if (!$info) {
                return $this->json(['success' => false, 'error' => 'No digital object for this record']);
            }

            $info['url'] = $this->displayUrl($objectId);

            return $this->json(['success' => true, 'document' => $info]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }


    /**
     * The image the editor draws on.
     *
     * The reference derivative, not the master. Masters under /uploads/r/ are no
     * longer served statically - they go through an ACL check and answer 404 to
     * anyone without readMaster - so pointing the canvas at one left the editor
     * with an empty pane. The reference copy is served normally and is what the
     * viewer should be handling anyway.
     *
     * It costs nothing in accuracy: regions are stored as 0-1 normalised
     * coordinates, so they are resolution independent, and applying them happens
     * server side against the master. Drawing on the reference also keeps the
     * master out of the browser entirely.
     *
     * Falls back to whatever digital object exists if there is no reference.
     */
    protected function displayUrl(int $objectId): ?string
    {
        $master = DB::table('digital_object')->where('object_id', $objectId)->first();

        if (!$master) {
            return null;
        }

        // usage 141 is the reference copy, 142 the thumbnail.
        $reference = DB::table('digital_object')
            ->where('parent_id', $master->id)
            ->where('usage_id', 141)
            ->first();

        $chosen = $reference ?: $master;

        return $chosen->path.$chosen->name;
    }

    /**
     * Coordinates are stored as JSON and are of no use to the annotator as a string.
     */
    protected function decodeCoordinates($regions): array
    {
        return $regions->map(static function ($region) {
            $region->coordinates = json_decode($region->coordinates, true);

            return $region;
        })->values()->toArray();
    }
}
