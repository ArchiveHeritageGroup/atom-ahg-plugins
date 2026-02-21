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
        $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $pluginBase = $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib';
        require_once $pluginBase . '/Repositories/AiConditionRepository.php';
        require_once $pluginBase . '/Services/AiConditionService.php';
        require_once $pluginBase . '/Helpers/AiConditionHelper.php';

        $this->repository = new \ahgAiConditionPlugin\Repositories\AiConditionRepository();
        $this->service = new \ahgAiConditionPlugin\Services\AiConditionService();
    }

    /**
     * Settings + API Clients (main page)
     */
    public function executeIndex($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $settingsFile = $this->config('sf_root_dir') . '/atom-framework/src/Services/AhgSettingsService.php';
        require_once $settingsFile;

        // Handle settings save
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'save_settings') {
            $fields = ['ai_condition_service_url', 'ai_condition_api_key', 'ai_condition_auto_scan',
                        'ai_condition_min_confidence', 'ai_condition_overlay_enabled', 'ai_condition_notify_grade'];

            foreach ($fields as $field) {
                $value = $request->getParameter($field);
                if ($value !== null) {
                    \AtomExtensions\Services\AhgSettingsService::set($field, $value, 'ai_condition');
                }
            }

            // Handle checkbox unchecked (not sent in POST)
            foreach (['ai_condition_auto_scan', 'ai_condition_overlay_enabled'] as $cb) {
                if ($request->getParameter($cb) === null) {
                    \AtomExtensions\Services\AhgSettingsService::set($cb, '0', 'ai_condition');
                }
            }

            $this->getUser()->setFlash('notice', 'Settings saved.');
            $this->redirect(['module' => 'aiCondition', 'action' => 'index']);
        }

        // Load settings
        $this->settings = [
            'ai_condition_service_url'      => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_service_url', 'http://localhost:8100'),
            'ai_condition_api_key'          => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_api_key', ''),
            'ai_condition_auto_scan'        => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_auto_scan', '0'),
            'ai_condition_min_confidence'    => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_min_confidence', '0.25'),
            'ai_condition_overlay_enabled'   => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_overlay_enabled', '1'),
            'ai_condition_notify_grade'      => \AtomExtensions\Services\AhgSettingsService::get('ai_condition_notify_grade', 'poor'),
        ];

        // Load API clients
        $clients = $this->repository->getClients();
        foreach ($clients as &$client) {
            $usage = $this->repository->getClientUsage($client->id);
            $client->scans_used = $usage->scans_used ?? 0;
        }
        $this->clients = $clients;

        // Load stats for sidebar
        $this->stats = $this->repository->getStats();
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
     * Browse assessments list
     */
    public function executeBrowse($request)
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
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        $result = $this->service->healthCheck();
        return $this->renderJson($result);
    }

    /**
     * Submit image for assessment (AJAX)
     * Accepts: multipart file upload OR JSON body with base64 image
     */
    public function executeApiSubmit($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $imageData = null;
        $objectId = null;
        $confidence = 0.25;

        // Try JSON body first (from annotation studio or API calls)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);
            if ($json) {
                $imageData = $json['image'] ?? $json['image_base64'] ?? null;
                $objectId = $json['object_id'] ?? $json['information_object_id'] ?? null;
                $confidence = (float) ($json['confidence'] ?? 0.25);
            }
        }

        // Fall back to form params
        if (empty($imageData)) {
            $imageData = $request->getParameter('image') ?? $request->getParameter('image_base64');
            $objectId = $objectId ?: ($request->getParameter('information_object_id') ?? $request->getParameter('object_id'));
            $confidence = (float) $request->getParameter('confidence', $confidence);
        }

        // Fall back to file upload
        if (empty($imageData)) {
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $imageData = base64_encode(file_get_contents($_FILES['image_file']['tmp_name']));
            } else {
                return $this->renderJson(['success' => false, 'error' => 'No image provided']);
            }
        }

        $result = $this->service->assess($imageData, [
            'information_object_id' => $objectId,
            'confidence' => $confidence,
            'store' => true,
            'overlay' => true,
        ]);

        if (empty($result['success'])) {
            return $this->renderJson(['success' => false, 'error' => $result['error'] ?? 'Assessment failed']);
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

        return $this->renderJson($result);
    }

    /**
     * Confirm an assessment (AJAX)
     */
    public function executeApiConfirm($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->confirmAssessment($id, $this->getUser()->getUserID());

        return $this->renderJson(['success' => $result]);
    }

    /**
     * Get history data for chart (AJAX)
     */
    public function executeApiHistoryData($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $objectId = (int) $request->getParameter('object_id');
        $history = $this->repository->getHistory($objectId);

        return $this->renderJson(['success' => true, 'data' => $history]);
    }

    /**
     * Save/update SaaS client (AJAX)
     */
    public function executeApiClientSave($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
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
            return $this->renderJson(['success' => false, 'error' => 'Name and email required']);
        }

        $id = $this->repository->saveClient($data);
        return $this->renderJson(['success' => true, 'id' => $id]);
    }

    /**
     * Revoke SaaS client (AJAX)
     */
    public function executeApiClientRevoke($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->revokeClient($id);
        return $this->renderJson(['success' => $result]);
    }

    /**
     * Object autocomplete for assessment form (AJAX)
     */
    public function executeApiObjectSearch($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['results' => []]);
        }

        $query = trim($request->getParameter('query', ''));
        if (strlen($query) < 2) {
            return $this->renderJson(['results' => []]);
        }

        $results = \Illuminate\Database\Capsule\Manager::table('information_object_i18n as io')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.culture', 'en')
            ->where('io.id', '!=', 1)
            ->where('io.title', 'like', '%' . $query . '%')
            ->select('io.id', 'io.title', 'slug.slug')
            ->orderBy('io.title')
            ->limit(15)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'slug' => $row->slug,
                ];
            })
            ->all();

        return $this->renderJson(['results' => $results]);
    }

    // =========================================================================
    // Manual Assessment
    // =========================================================================

    /**
     * Manual assessment form (no AI)
     */
    public function executeManualAssess($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * Save manual assessment (AJAX)
     */
    public function executeApiManualSave($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $grade = $request->getParameter('condition_grade');
        $score = (float) $request->getParameter('overall_score', 50);
        $objectId = $request->getParameter('information_object_id') ?: null;
        $recommendations = $request->getParameter('recommendations');
        $damagesJson = $request->getParameter('damages_json');

        if (empty($grade)) {
            return $this->renderJson(['success' => false, 'error' => 'Condition grade is required']);
        }

        $damages = $damagesJson ? json_decode($damagesJson, true) : [];

        $assessmentId = $this->repository->saveAssessment([
            'information_object_id' => $objectId,
            'overall_score'         => $score,
            'condition_grade'       => $grade,
            'damage_count'          => count($damages),
            'recommendations'       => $recommendations,
            'confidence_threshold'  => 1.0,
            'source'                => 'manual_entry',
            'is_confirmed'          => 1,
            'confirmed_by'          => $this->getUser()->getUserID(),
            'confirmed_at'          => date('Y-m-d H:i:s'),
            'created_by'            => $this->getUser()->getUserID(),
        ]);

        if (!empty($damages)) {
            $this->repository->saveDamages($assessmentId, $damages);
        }

        if ($objectId && $score) {
            $this->repository->saveHistory(
                (int) $objectId,
                $assessmentId,
                $score,
                $grade,
                count($damages)
            );
        }

        return $this->renderJson(['success' => true, 'assessment_id' => $assessmentId]);
    }

    // =========================================================================
    // Model Training
    // =========================================================================

    /**
     * Training management page
     */
    public function executeTraining($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * Proxy: Get model info from Python service
     */
    public function executeApiTrainingModelInfo($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }
        return $this->renderJson($this->service->proxyGet('/api/v1/training/model-info'));
    }

    /**
     * Proxy: Get training status from Python service
     */
    public function executeApiTrainingStatus($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }
        return $this->renderJson($this->service->proxyGet('/api/v1/training/status'));
    }

    /**
     * Proxy: Upload training data ZIP to Python service
     */
    public function executeApiTrainingUpload($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        if (!isset($_FILES['training_file']) || $_FILES['training_file']['error'] !== UPLOAD_ERR_OK) {
            return $this->renderJson(['success' => false, 'error' => 'No file uploaded']);
        }

        return $this->renderJson($this->service->proxyFileUpload(
            '/api/v1/training/upload',
            $_FILES['training_file']['tmp_name'],
            $_FILES['training_file']['name'],
            'file'
        ));
    }

    /**
     * Proxy: List training datasets / delete a dataset
     */
    public function executeApiTrainingDatasets($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        if ($request->isMethod('delete') || strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'DELETE') {
            $datasetId = $request->getParameter('dataset_id');
            return $this->renderJson($this->service->proxyDelete('/api/v1/training/dataset/' . urlencode($datasetId)));
        }

        return $this->renderJson($this->service->proxyGet('/api/v1/training/datasets'));
    }

    /**
     * Proxy: Start model training
     */
    public function executeApiTrainingStart($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        $payload = [
            'dataset_id' => $request->getParameter('dataset_id'),
            'epochs'     => (int) $request->getParameter('epochs', 100),
            'batch_size' => (int) $request->getParameter('batch_size', 16),
        ];

        return $this->renderJson($this->service->proxyPost('/api/v1/training/start', $payload));
    }

    // =========================================================================
    // Training Contributions
    // =========================================================================

    /**
     * Submit a training contribution (from condition photos, annotation studio, or manual)
     */
    public function executeApiContribute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);
        } else {
            $json = [
                'image_base64' => $request->getParameter('image_base64'),
                'annotations'  => json_decode($request->getParameter('annotations_json', '[]'), true),
                'source'       => $request->getParameter('source', 'manual'),
                'object_id'    => $request->getParameter('object_id'),
            ];
        }

        if (empty($json['image_base64'])) {
            return $this->renderJson(['success' => false, 'error' => 'Image data required']);
        }
        if (empty($json['annotations']) || !is_array($json['annotations'])) {
            return $this->renderJson(['success' => false, 'error' => 'At least one annotation required']);
        }

        $json['contributor'] = $this->getUser()->getUserID() . '';

        return $this->renderJson($this->service->proxyPost('/api/v1/training/contribute', $json));
    }

    /**
     * List training contributions
     */
    public function executeApiContributions($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        $qs = http_build_query(array_filter([
            'source' => $request->getParameter('source'),
            'status' => $request->getParameter('status'),
            'page'   => $request->getParameter('page'),
        ]));

        return $this->renderJson($this->service->proxyGet('/api/v1/training/contributions' . ($qs ? '?' . $qs : '')));
    }

    // =========================================================================
    // Client Training Permission Toggle
    // =========================================================================

    /**
     * Toggle client training data contribution permission (AJAX)
     */
    public function executeApiClientTrainingToggle($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderJson(['success' => false, 'error' => 'Not authorized']);
        }

        $clientId = (int) $request->getParameter('id');
        $enabled = (int) $request->getParameter('enabled', 0);

        $result = \Illuminate\Database\Capsule\Manager::table('ahg_ai_service_client')
            ->where('id', $clientId)
            ->update(['can_contribute_training' => $enabled]);

        return $this->renderJson(['success' => $result !== false]);
    }
}
