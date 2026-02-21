<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * AI Condition Assessment Actions
 *
 * Companion to ahgConditionPlugin — adds AI-powered damage detection.
 */
class aiConditionActions extends AhgController
{
    private $repository;
    private $service;

    public function boot(): void
    {
        $ajaxActions = ['apiTest', 'apiSubmit', 'apiConfirm', 'apiHistoryData', 'apiBulkStatus', 'apiClientSave', 'apiClientRevoke'];
        if (in_array($this->getActionName(), $ajaxActions)) {
            ob_start();
        }

        $ahgDbFile = $this->config('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        if (file_exists($ahgDbFile)) {
            require_once $ahgDbFile;
        }

        $pluginBase = $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib';
        require_once $pluginBase . '/Repositories/AiConditionRepository.php';
        require_once $pluginBase . '/Services/AiConditionService.php';
        require_once $pluginBase . '/Helpers/AiConditionHelper.php';

        $this->repository = new \ahgAiConditionPlugin\Repositories\AiConditionRepository();
        $this->service = new \ahgAiConditionPlugin\Services\AiConditionService();
    }

    /**
     * List assessments
     */
    public function executeIndex($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $filters = [
            'condition_grade' => $request->getParameter('grade'),
            'source'          => $request->getParameter('source'),
            'is_confirmed'    => $request->getParameter('confirmed'),
            'search'          => $request->getParameter('q'),
        ];
        $page = max(1, (int) $request->getParameter('page', 1));

        $result = $this->repository->listAssessments(array_filter($filters), $page);
        $stats = $this->repository->getStats();

        $this->assessments = $result['items'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->pages = $result['pages'];
        $this->stats = $stats;
        $this->filters = $filters;
    }

    /**
     * New assessment form + processing
     */
    public function executeAssess($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $this->result = null;
        $this->objectId = $request->getParameter('object_id');
    }

    /**
     * View a single assessment with damage overlay
     */
    public function executeView($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');
        $assessment = $this->repository->getAssessment($id);

        if (!$assessment) {
            $this->forward404();
        }

        $this->assessment = $assessment;
    }

    /**
     * Condition history for an object (score chart over time)
     */
    public function executeHistory($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $slug = $request->getParameter('slug');

        $obj = \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$obj) {
            $this->forward404();
        }

        $objectId = $obj->object_id;

        $title = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->value('title');

        $history = $this->repository->getHistory($objectId);

        $this->objectId = $objectId;
        $this->objectTitle = $title ?? $slug;
        $this->slug = $slug;
        $this->history = $history;
    }

    /**
     * Settings page
     */
    public function executeSettings($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $settingsFile = $this->config('sf_root_dir') . '/atom-framework/src/Services/AhgSettingsService.php';
        require_once $settingsFile;

        if ($request->isMethod('post')) {
            $fields = ['ai_condition_service_url', 'ai_condition_api_key', 'ai_condition_auto_scan',
                        'ai_condition_min_confidence', 'ai_condition_overlay_enabled', 'ai_condition_notify_grade'];

            foreach ($fields as $field) {
                $value = $request->getParameter($field);
                if ($value !== null) {
                    \AtomExtensions\Services\AhgSettingsService::set($field, $value, 'ai_condition');
                }
            }

            $this->getUser()->setFlash('notice', 'Settings saved.');
            $this->redirect(['module' => 'aiCondition', 'action' => 'settings']);
        }

        $this->settings = [
            'ai_condition_service_url'      => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_service_url', 'http://localhost:8100'),
            'ai_condition_api_key'          => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_api_key', ''),
            'ai_condition_auto_scan'        => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_auto_scan', '0'),
            'ai_condition_min_confidence'    => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_min_confidence', '0.25'),
            'ai_condition_overlay_enabled'   => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_overlay_enabled', '1'),
            'ai_condition_notify_grade'      => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_notify_grade', 'poor'),
        ];
    }

    /**
     * Bulk scan management
     */
    public function executeBulk($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Get repositories for dropdown
        $this->repositories = \Illuminate\Database\Capsule\Manager::table('repository')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('repository.id', '=', 'ai.id')
                     ->where('ai.culture', '=', 'en');
            })
            ->select('repository.id', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->all();
    }

    /**
     * SaaS client management
     */
    public function executeClients($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $clients = $this->repository->getClients();

        // Attach usage data
        foreach ($clients as &$client) {
            $usage = $this->repository->getClientUsage($client->id);
            $client->scans_used = $usage->scans_used ?? 0;
        }

        $this->clients = $clients;
    }

    // =========================================================================
    // AJAX Endpoints
    // =========================================================================

    /**
     * Test connection to AI service
     */
    public function executeApiTest($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authorized']);
        }

        $result = $this->service->healthCheck();
        return $this->jsonResponse($result);
    }

    /**
     * Submit image for assessment (AJAX)
     */
    public function executeApiSubmit($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
        }

        $imageData = $request->getParameter('image');
        $objectId = $request->getParameter('information_object_id');
        $confidence = (float) $request->getParameter('confidence', 0.25);

        if (empty($imageData)) {
            // Check for file upload
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $imageData = base64_encode(file_get_contents($_FILES['image_file']['tmp_name']));
            } else {
                return $this->jsonResponse(['success' => false, 'error' => 'No image provided']);
            }
        }

        $result = $this->service->assess($imageData, [
            'information_object_id' => $objectId,
            'confidence' => $confidence,
            'store' => true,
            'overlay' => true,
        ]);

        if (empty($result['success'])) {
            return $this->jsonResponse(['success' => false, 'error' => $result['error'] ?? 'Assessment failed']);
        }

        // Store in local DB
        $assessmentId = $this->repository->saveAssessment([
            'information_object_id' => $objectId ?: null,
            'overall_score'         => $result['overall_score'] ?? null,
            'condition_grade'       => $result['condition_grade'] ?? null,
            'damage_count'          => count($result['damages'] ?? []),
            'recommendations'       => is_array($result['recommendations'] ?? null) ? implode("\n", $result['recommendations']) : ($result['recommendations'] ?? null),
            'model_version'         => $result['model_version'] ?? null,
            'processing_time_ms'    => $result['processing_time_ms'] ?? null,
            'confidence_threshold'  => $confidence,
            'source'                => 'manual',
            'created_by'            => $this->getUser()->getUserID(),
        ]);

        if (!empty($result['damages'])) {
            $this->repository->saveDamages($assessmentId, $result['damages']);
        }

        // Track history if linked to an object
        if ($objectId && !empty($result['overall_score'])) {
            $this->repository->saveHistory(
                (int) $objectId,
                $assessmentId,
                $result['overall_score'],
                $result['condition_grade'] ?? 'unknown',
                count($result['damages'] ?? [])
            );
        }

        $result['assessment_id'] = $assessmentId;

        return $this->jsonResponse($result);
    }

    /**
     * Confirm an assessment (AJAX)
     */
    public function executeApiConfirm($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->confirmAssessment($id, $this->getUser()->getUserID());

        return $this->jsonResponse(['success' => $result]);
    }

    /**
     * Get history data for chart (AJAX)
     */
    public function executeApiHistoryData($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
        }

        $objectId = (int) $request->getParameter('object_id');
        $history = $this->repository->getHistory($objectId);

        return $this->jsonResponse(['success' => true, 'data' => $history]);
    }

    /**
     * Save/update SaaS client (AJAX)
     */
    public function executeApiClientSave($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authorized']);
        }

        $data = [
            'id'            => $request->getParameter('id'),
            'name'          => $request->getParameter('name'),
            'organization'  => $request->getParameter('organization'),
            'email'         => $request->getParameter('email'),
            'tier'          => $request->getParameter('tier', 'free'),
            'monthly_limit' => (int) $request->getParameter('monthly_limit', 50),
        ];

        if (empty($data['name']) || empty($data['email'])) {
            return $this->jsonResponse(['success' => false, 'error' => 'Name and email required']);
        }

        $id = $this->repository->saveClient($data);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    /**
     * Revoke SaaS client (AJAX)
     */
    public function executeApiClientRevoke($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authorized']);
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->revokeClient($id);
        return $this->jsonResponse(['success' => $result]);
    }
}
