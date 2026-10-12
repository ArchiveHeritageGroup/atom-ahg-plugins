<?php
/**
 * PSIS / AtoM-AHG - staff content-credentials / provenance pages.
 *
 * PSIS twin of Heratio's ProvenanceController + CoverageController
 * (/admin/c2pa/...). Routes are registered in ahgC2paPluginConfiguration:
 *
 *   GET       /admin/c2pa/coverage                        executeCoverage
 *   GET       /admin/c2pa/object/:id                      executeIndex     provenance records for a record
 *   GET|POST  /admin/c2pa/object/:id/record               executeRecord    record a digitisation (form / save)
 *   GET       /admin/c2pa/object/:id/record/:pid          executeShow      verify one record
 *   GET       /admin/c2pa/object/:id/record/:pid/manifest executeManifest  its signed manifest JSON
 *
 * Access: editor or administrator (config/security.yml).
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use AhgC2pa\Services\C2paService;
use AhgC2pa\Services\CoverageReportService;
use AhgC2pa\Services\DigitalObjectProvenanceService;
use AhgC2pa\Services\ProvenanceRecordService;
use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class c2paAdminActions extends AhgController
{
    public function boot(): void
    {
        require_once dirname(__DIR__, 3) . '/lib/c2pa_bootstrap.php';
        \C2paBootstrap::load();
    }

    public function executeCoverage($request)
    {
        $this->report = (new CoverageReportService(\C2paBootstrap::provenanceService()))->report();
    }

    public function executeIndex($request)
    {
        $ioId = $this->requireObject($request);
        $service = \C2paBootstrap::provenanceService();

        $this->informationObjectId = $ioId;
        $this->records = $service->listForObject($ioId);
        $this->capability = $service->capability();
        $this->manifests = $this->manifestsForObject($ioId);
        $this->notice = $this->getUser()->getFlash('c2pa_notice');
    }

    /**
     * GET shows the form; POST records the digitisation and signs it.
     */
    public function executeRecord($request)
    {
        $ioId = $this->requireObject($request);
        $service = \C2paBootstrap::provenanceService();

        $this->informationObjectId = $ioId;
        $this->capability = $service->capability();
        $this->digitalObjects = DB::table('digital_object')
            ->where('object_id', $ioId)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get(['id', 'name', 'path', 'mime_type'])
            ->all();
        $this->errors = [];
        $this->old = [];

        if (!$request->isMethod('post')) {
            return sfView::SUCCESS;
        }

        $fields = ['digital_object_id', 'captured_by', 'captured_at', 'capture_device', 'capture_software', 'notes'];
        $input = [];
        foreach ($fields as $f) {
            $v = $request->getParameter($f);
            $input[$f] = is_string($v) ? trim($v) : null;
        }
        $this->old = $input;

        foreach (['captured_by', 'capture_device', 'capture_software'] as $f) {
            if ($input[$f] !== null && mb_strlen($input[$f]) > 255) {
                $this->errors[] = $this->context->getI18N()->__('%1% must be at most 255 characters.', ['%1%' => $f]);
            }
        }
        if ($input['captured_at'] !== null && $input['captured_at'] !== '' && strtotime($input['captured_at']) === false) {
            $this->errors[] = $this->context->getI18N()->__('Captured at is not a valid date.');
        }

        $assetPath = null;
        $doId = null;
        if ($input['digital_object_id'] !== null && $input['digital_object_id'] !== '') {
            $doId = ctype_digit($input['digital_object_id']) ? (int) $input['digital_object_id'] : 0;
            $do = $doId > 0 ? DigitalObjectProvenanceService::loadRow($doId) : null;
            if ($do === null || (int) $do->object_id !== $ioId) {
                $this->errors[] = $this->context->getI18N()->__('The chosen digital object does not belong to this record.');
            } else {
                $assetPath = DigitalObjectProvenanceService::resolveAssetPath($do);
            }
        }

        if ($this->errors !== []) {
            return sfView::SUCCESS;
        }

        try {
            $id = $service->record($ioId, [
                'digital_object_id' => $doId,
                'captured_by'       => $input['captured_by'],
                'captured_at'       => $input['captured_at'],
                'capture_device'    => $input['capture_device'],
                'capture_software'  => $input['capture_software'],
                'notes'             => $input['notes'],
                'asset_path'        => $assetPath,
            ]);
        } catch (\Throwable $e) {
            error_log('[c2pa] admin provenance record failed for IO ' . $ioId . ': ' . $e->getMessage());
            $this->errors[] = $this->context->getI18N()->__('The provenance record could not be saved.');

            return sfView::SUCCESS;
        }

        $this->getUser()->setFlash('c2pa_notice', $this->context->getI18N()->__('Provenance record created.'));

        try {
            $this->redirect('@ahg_c2pa_admin_show?id=' . $ioId . '&pid=' . $id);
        } catch (\sfStopException $e) {
            throw $e;
        }
    }

    public function executeShow($request)
    {
        $ioId = $this->requireObject($request);
        $pid = (int) $request->getParameter('pid');
        $service = \C2paBootstrap::provenanceService();

        $record = $service->find($pid);
        if ($record === null || (int) $record->information_object_id !== $ioId) {
            $this->forward404();
        }

        $this->informationObjectId = $ioId;
        $this->record = $record;
        $this->verification = $service->verifyRecord($pid);
        $this->inferenceSteps = ProvenanceRecordService::decodeSteps($record);
        $this->capability = $service->capability();
        $this->notice = $this->getUser()->getFlash('c2pa_notice');
    }

    public function executeManifest($request)
    {
        $ioId = (int) $request->getParameter('id');
        $pid = (int) $request->getParameter('pid');
        $record = \C2paBootstrap::provenanceService()->find($pid);
        if ($record === null || (int) $record->information_object_id !== $ioId || $record->manifest_id === null) {
            $this->forward404();
        }
        $json = DB::table('ahg_c2pa_manifest')->where('id', (int) $record->manifest_id)->value('manifest_json');
        if ($json === null) {
            $this->forward404();
        }

        $response = $this->getResponse();
        $response->setContentType('application/json');
        $response->setHttpHeader('Content-Disposition', 'inline; filename="c2pa-manifest-' . $pid . '.json"');

        return $this->renderText((string) $json);
    }

    /**
     * Load the record into $this->object, 404 when it does not exist.
     */
    private function requireObject($request): int
    {
        $ioId = (int) $request->getParameter('id');
        $this->object = $ioId > 0 ? ProvenanceRecordService::loadInformationObject($ioId) : null;
        if ($this->object === null) {
            $this->forward404();
        }

        return $ioId;
    }

    /**
     * Every manifest stored for the record (provenance, AI outputs and
     * metadata-export issuances), newest first.
     *
     * @return list<object>
     */
    private function manifestsForObject(int $ioId): array
    {
        $columns = C2paService::manifestColumns();
        if ($columns === []) {
            return [];
        }
        $select = array_values(array_intersect(['id', 'action', 'model_id', 'model_version', 'manifest_label', 'kid', 'digital_object_id', 'created_at'], $columns));

        return DB::table('ahg_c2pa_manifest')
            ->where('information_object_id', $ioId)
            ->orderByDesc('id')
            ->limit(100)
            ->get($select)
            ->all();
    }
}
