<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * RiC SHACL Validation Actions
 *
 * Admin UI for SHACL validation of the generated RiC-O graph against the
 * RiC-O shapes, with persisted reports. Symfony 1.4 / Bootstrap 5 / PHP 8.3.
 *
 * @package    ahgRicExplorerPlugin
 */
class ricShaclActions extends AhgController
{
    /** @var \AhgRicExplorer\Services\ShaclValidationService|null */
    protected $shacl;

    public function boot(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        try {
            $bootstrapFile = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrapFile)) {
                require_once $bootstrapFile;
            }
            $serviceFile = $this->config('sf_root_dir')
                . '/atom-ahg-plugins/ahgRicExplorerPlugin/lib/Services/ShaclValidationService.class.php';
            if (file_exists($serviceFile)) {
                require_once $serviceFile;
                $this->shacl = new \AhgRicExplorer\Services\ShaclValidationService();
            }
        } catch (\Throwable $e) {
            $this->shacl = null;
        }
    }

    /**
     * Report listing + engine status.
     */
    public function executeIndex($request)
    {
        if (null === $this->shacl) {
            $this->engineStatus = ['available' => false, 'reason' => 'SHACL service not available'];
            $this->reports = [];

            return;
        }

        $this->engineStatus = $this->shacl->engineStatus();
        $this->reports = $this->shacl->recentReports(50);
    }

    /**
     * Run a full-graph validation and redirect to the resulting report.
     */
    public function executeRun($request)
    {
        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'ricShacl', 'action' => 'index']);
        }
        if (null === $this->shacl) {
            $this->getUser()->setFlash('error', 'SHACL service not available.');
            $this->redirect(['module' => 'ricShacl', 'action' => 'index']);
        }

        $graph = $request->getParameter('graph');
        $graph = (is_string($graph) && '' !== trim($graph)) ? trim($graph) : null;

        $report = $this->shacl->validateGraph($graph);
        $id = (int) ($report['report_id'] ?? 0);

        if ($id > 0) {
            $this->redirect(['module' => 'ricShacl', 'action' => 'report', 'id' => $id]);
        }

        $this->getUser()->setFlash('notice', 'Validation ran but no report id was stored (engine: ' . $report['engine'] . ').');
        $this->redirect(['module' => 'ricShacl', 'action' => 'index']);
    }

    /**
     * View a single persisted report.
     */
    public function executeReport($request)
    {
        $id = (int) $request->getParameter('id');
        if (null === $this->shacl || $id <= 0) {
            $this->forward404('Report not found');
        }

        $report = $this->shacl->getReport($id);
        if (null === $report) {
            $this->forward404('Report not found');
        }

        $this->report = $report;
    }

    /**
     * AJAX: validate a single RiC-O entity (JSON-LD) supplied as POST 'entity'.
     * Returns JSON {valid, errors, warnings, engine}.
     */
    public function executeAjaxValidateEntity($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (null === $this->shacl) {
            return $this->renderText(json_encode([
                'valid' => true,
                'errors' => [],
                'warnings' => [],
                'engine' => 'none',
                'note' => 'SHACL service not available',
            ]));
        }

        $raw = $request->getParameter('entity');
        $entityType = (string) $request->getParameter('entity_type', 'Record');

        $entity = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($entity)) {
            return $this->renderText(json_encode([
                'valid' => false,
                'errors' => ['Invalid or missing JSON-LD entity payload'],
                'warnings' => [],
                'engine' => 'none',
            ]));
        }

        $result = $this->shacl->validateBeforeSave($entity, $entityType);

        return $this->renderText(json_encode($result));
    }
}
