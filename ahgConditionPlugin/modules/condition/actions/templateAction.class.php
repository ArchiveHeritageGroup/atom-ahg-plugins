<?php

use AtomFramework\Http\Controllers\AhgController;
require_once dirname(__FILE__)."/../../../lib/ConditionConstants.php";

/**
 * Condition Template Action Controller
 */
class conditionTemplateAction extends AhgController
{
    protected $templateService;

    public function boot(): void
    {
        // Load AhgDb class for Laravel Query Builder
        $ahgDbFile = $this->config('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        if (file_exists($ahgDbFile)) {
            require_once $ahgDbFile;
            \AhgCore\Core\AhgDb::init();
        }
    }

    protected function initService()
    {
        require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionTemplateService.php';
        $this->templateService = new \ahgConditionPlugin\Service\ConditionTemplateService();
    }

    public function execute($request)
    {
        $this->initService();
        $action = $request->getParameter('template_action', 'list');

        switch ($action) {
            case 'form':
                return $this->executeForm($request);
            case 'save':
                return $this->executeSave($request);
            case 'list':
                return $this->executeList($request);
            case 'view':
                return $this->executeView($request);
            case 'export':
                return $this->executeExport($request);
            default:
                return $this->executeList($request);
        }
    }

    public function executeForm($request)
    {
        $templateId = $request->getParameter('id');
        $checkId = $request->getParameter('check_id');

        if (!$templateId) {
            return $this->renderText('<div class="alert alert-warning">No template selected.</div>');
        }

        $template = $this->templateService->getTemplate((int) $templateId);
        if (!$template) {
            return $this->renderText('<div class="alert alert-danger">Template not found.</div>');
        }

        $existingData = [];
        if ($checkId) {
            $existingData = $this->templateService->getCheckData((int) $checkId);
        }

        // Allow editing if user is authenticated
        $canEdit = $this->getUser()->isAuthenticated();

        return $this->renderText($this->templateService->renderForm($template, $existingData, !$canEdit));
    }

    public function executeSave($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post') || !$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid request']));
        }

        $checkId = $request->getParameter('condition_check_id');
        $templateId = $request->getParameter('template_id');
        $fieldData = $request->getParameter('template_field', []);

        if (!$checkId || !$templateId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing fields']));
        }

        $result = $this->templateService->saveCheckData((int) $checkId, (int) $templateId, $fieldData);
        return $this->renderText(json_encode(['success' => $result]));
    }

    public function executeList($request)
    {
        $this->templates = $this->templateService->getAllTemplates(false);
        $this->materialTypes = $this->templateService->getMaterialTypes();
        $this->canManage = $this->getUser()->isAuthenticated() &&
                          $this->getUser()->hasGroup(ConditionConstants::ADMINISTRATOR_GROUP_ID);
        $this->setTemplate('templateList');
    }

    public function executeView($request)
    {
        $templateId = $request->getParameter('id');
        $this->template = $this->templateService->getTemplate((int) $templateId);

        if (!$this->template) {
            $this->forward404('Template not found');
        }
        $this->setTemplate('templateView');
    }

    public function executeExport($request)
    {
        $templateId = $request->getParameter('id');
        $data = $this->templateService->exportTemplate((int) $templateId);

        if (!$data) {
            $this->forward404('Template not found');
        }

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Content-Disposition', 
            'attachment; filename="template_' . $data['code'] . '.json"');
        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT));
    }
}
