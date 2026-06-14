<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Label module - Barcode/label generation for all GLAM sectors.
 */
class labelActions extends AhgController
{
    public function executeIndex($request)
    {
        $slug = $request->getParameter('slug');

        // Dual-mode: EntityQueryService (standalone) or Propel (legacy)
        if (class_exists('\\AtomFramework\\Services\\EntityQueryService')) {
            $entity = \AtomFramework\Services\EntityQueryService::findBySlug($slug);
            if ($entity) {
                $this->resource = new \AtomFramework\Services\LightweightResource($entity);
            }
        } else {
            $this->resource = QubitInformationObject::getBySlug($slug);
        }

        if (!$this->resource) {
            $this->forward404();
        }
        
        $this->labelType = $request->getParameter('type', 'full');
        $this->labelSize = $request->getParameter('size', 'medium');
    }

    // =====================================================================
    // Label templates + batch printing
    // =====================================================================

    protected function labelService(): \AtomExtensions\Label\LabelService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgLabelPlugin/lib/Services/LabelService.php';

        return new \AtomExtensions\Label\LabelService();
    }

    protected function requireLabelAdmin(): void
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !$user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    /** Admin: list label templates (+ delete / set default via POST). */
    public function executeTemplates($request)
    {
        $this->requireLabelAdmin();
        $svc = $this->labelService();

        if ($request->isMethod('post') && 'delete' === $request->getParameter('form_action')) {
            $svc->deleteTemplate((int) $request->getParameter('id'));
            $this->redirect(['module' => 'label', 'action' => 'templates']);

            return;
        }

        $this->templates = $svc->listTemplates();

        return sfView::SUCCESS;
    }

    /** Admin: create / edit a template. */
    public function executeTemplateEdit($request)
    {
        $this->requireLabelAdmin();
        $svc = $this->labelService();

        if ($request->isMethod('post')) {
            $id = (int) $request->getParameter('id');
            $svc->saveTemplate($request->getParameterHolder()->getAll(), $id ?: null);
            $this->redirect(['module' => 'label', 'action' => 'templates']);

            return;
        }

        $id = (int) $request->getParameter('id');
        $this->template = $id ? $svc->getTemplate($id) : null;

        return sfView::SUCCESS;
    }

    /** Staff: batch label sheet. GET picker; with template+selection renders print sheet. */
    public function executeBatch($request)
    {
        $this->requireAuth();
        $svc = $this->labelService();
        $culture = $this->culture();

        $this->templates = $svc->listTemplates();
        $this->repositoryOptions = $svc->repositoryOptions($culture);

        $templateId = (int) $request->getParameter('template_id');
        $this->template = $templateId ? $svc->getTemplate($templateId) : $svc->getDefault();

        $records = [];
        $idsRaw = trim((string) $request->getParameter('ids'));
        $repositoryId = (int) $request->getParameter('repository_id');
        if ($idsRaw !== '') {
            $ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $idsRaw)));
            $records = $svc->resolveByIds($ids, $culture);
        } elseif ($repositoryId > 0) {
            $records = $svc->resolveByRepository($repositoryId, $culture);
        }

        $this->records = $records;
        $this->hasSelection = ($idsRaw !== '' || $repositoryId > 0);
        $this->idsRaw = $idsRaw;
        $this->repositoryId = $repositoryId;

        return sfView::SUCCESS;
    }
}
