<?php

class ingestActions extends sfActions
{
    protected function loadServices(): void
    {
        static $loaded = false;
        if (!$loaded) {
            $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin';
            require_once $pluginDir . '/lib/Services/IngestService.php';
            require_once $pluginDir . '/lib/Services/IngestCommitService.php';
            $loaded = true;
        }
    }

    protected function getIngestService(): \AhgIngestPlugin\Services\IngestService
    {
        $this->loadServices();

        return new \AhgIngestPlugin\Services\IngestService();
    }

    protected function getCommitService(): \AhgIngestPlugin\Services\IngestCommitService
    {
        $this->loadServices();

        return new \AhgIngestPlugin\Services\IngestCommitService();
    }

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
    }

    protected function requireSessionOwner(object $session): void
    {
        if ((int) $session->user_id !== (int) $this->getUser()->getAttribute('user_id')) {
            if (!$this->getUser()->isAdministrator()) {
                $this->forward('admin', 'secure');
            }
        }
    }

    // ─── Dashboard ──────────────────────────────────────────────────────

    public function executeIndex(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $userId = $this->getUser()->getAttribute('user_id');

        // Admins see all sessions, others see only their own
        if ($this->getUser()->isAdministrator()) {
            $this->sessions = \Illuminate\Database\Capsule\Manager::table('ingest_session')
                ->leftJoin('user', 'ingest_session.user_id', '=', 'user.id')
                ->leftJoin('actor_i18n', 'user.id', '=', 'actor_i18n.id')
                ->select('ingest_session.*', 'actor_i18n.authorized_form_of_name as user_name')
                ->orderByDesc('ingest_session.updated_at')
                ->get()
                ->toArray();
        } else {
            $this->sessions = $svc->getSessions($userId);
        }
    }

    // ─── Step 1: Configure ──────────────────────────────────────────────

    public function executeConfigure(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = $request->getParameter('id');

        if ($id) {
            $this->session = $svc->getSession((int) $id);
            if (!$this->session) {
                $this->forward404();
            }
            $this->requireSessionOwner($this->session);
        }

        if ($request->isMethod('post')) {
            $config = [
                'title' => $request->getParameter('title', ''),
                'sector' => $request->getParameter('sector', 'archive'),
                'standard' => $request->getParameter('standard', 'isadg'),
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'parent_id' => $request->getParameter('parent_id') ?: null,
                'parent_placement' => $request->getParameter('parent_placement', 'top_level'),
                'new_parent_title' => $request->getParameter('new_parent_title', ''),
                'new_parent_level' => $request->getParameter('new_parent_level', ''),
                'output_create_records' => $request->getParameter('output_create_records', 1),
                'output_generate_sip' => $request->getParameter('output_generate_sip', 0),
                'output_generate_aip' => $request->getParameter('output_generate_aip', 0),
                'output_generate_dip' => $request->getParameter('output_generate_dip', 0),
                'output_sip_path' => $request->getParameter('output_sip_path', ''),
                'output_aip_path' => $request->getParameter('output_aip_path', ''),
                'output_dip_path' => $request->getParameter('output_dip_path', ''),
                'derivative_thumbnails' => $request->getParameter('derivative_thumbnails', 1),
                'derivative_reference' => $request->getParameter('derivative_reference', 1),
                'derivative_normalize_format' => $request->getParameter('derivative_normalize_format', ''),
                'security_classification_id' => $request->getParameter('security_classification_id') ?: null,
                'process_ner' => $request->getParameter('process_ner', 0),
                'process_ocr' => $request->getParameter('process_ocr', 0),
                'process_virus_scan' => $request->getParameter('process_virus_scan', 1),
                'process_summarize' => $request->getParameter('process_summarize', 0),
                'process_spellcheck' => $request->getParameter('process_spellcheck', 0),
                'process_translate' => $request->getParameter('process_translate', 0),
                'process_translate_lang' => $request->getParameter('process_translate_lang') ?: null,
                'process_format_id' => $request->getParameter('process_format_id', 0),
                'process_face_detect' => $request->getParameter('process_face_detect', 0),
            ];

            $userId = $this->getUser()->getAttribute('user_id');

            if ($id) {
                $svc->updateSession((int) $id, $config);
                $svc->updateSessionStatus((int) $id, 'upload');
                $sessionId = (int) $id;
            } else {
                $sessionId = $svc->createSession($userId, $config);
                $svc->updateSessionStatus($sessionId, 'upload');
            }

            $this->redirect(['module' => 'ingest', 'action' => 'upload', 'id' => $sessionId]);
        }

        // Load data for dropdowns
        $this->repositories = \Illuminate\Database\Capsule\Manager::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->toArray();

        $this->classifications = [];
        if (class_exists('\AtomExtensions\Services\SecurityClearanceService')) {
            try {
                $this->classifications = \AtomExtensions\Services\SecurityClearanceService::getAllClassifications();
            } catch (\Exception $e) {
                // Plugin not installed
            }
        }

        // Load ingest defaults from ahg_settings (if no existing session)
        $this->defaults = [];
        if (!$id) {
            try {
                $rows = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                    ->where('setting_group', 'ingest')
                    ->get(['setting_key', 'setting_value']);
                foreach ($rows as $row) {
                    $this->defaults[$row->setting_key] = $row->setting_value;
                }
            } catch (\Exception $e) {
                // Table may not exist
            }
        }
    }

    // ─── Step 2: Upload ─────────────────────────────────────────────────

    public function executeUpload(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $this->session = $svc->getSession($id);
        if (!$this->session) {
            $this->forward404();
        }
        $this->requireSessionOwner($this->session);

        if ($request->isMethod('post')) {
            $uploadDir = sfConfig::get('sf_upload_dir') . '/ingest/' . $id;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $request->getFiles('ingest_file');
            $dirPath = $request->getParameter('directory_path');

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $storedName = uniqid('ingest_') . '.' . $ext;
                $storedPath = $uploadDir . '/' . $storedName;

                if (move_uploaded_file($file['tmp_name'], $storedPath)) {
                    $svc->processUpload($id, [
                        'original_name' => $file['name'],
                        'stored_path' => $storedPath,
                        'file_size' => $file['size'],
                        'mime_type' => $file['type'],
                    ]);
                } else {
                    $this->getUser()->setFlash('error', 'Failed to save uploaded file');
                    return;
                }
            } elseif (!empty($dirPath) && is_dir($dirPath)) {
                // Server directory path — register as directory type
                $svc->processUpload($id, [
                    'original_name' => basename($dirPath),
                    'stored_path' => $dirPath,
                    'file_size' => 0,
                    'mime_type' => 'directory',
                ]);
            } else {
                $this->getUser()->setFlash('error', 'Please select a file or enter a directory path');
                return;
            }

            // Parse rows from CSV
            $svc->parseRows($id);
            $svc->updateSessionStatus($id, 'map');

            $this->redirect(['module' => 'ingest', 'action' => 'map', 'id' => $id]);
        }

        $this->files = $svc->getFiles($id);
    }

    // ─── Step 3: Map & Enrich ───────────────────────────────────────────

    public function executeMap(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $this->session = $svc->getSession($id);
        if (!$this->session) {
            $this->forward404();
        }
        $this->requireSessionOwner($this->session);

        // Check if auto-map needed (no mappings exist yet)
        $existingMappings = $svc->getMappings($id);
        if (empty($existingMappings)) {
            $svc->autoMapColumns($id, $this->session->standard);
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action', 'save');

            if ($action === 'load_profile') {
                $profileId = (int) $request->getParameter('mapping_profile_id');
                if ($profileId) {
                    $svc->loadMappingProfile($id, $profileId);
                }
                $this->redirect(['module' => 'ingest', 'action' => 'map', 'id' => $id]);
            }

            // Save manual mapping changes
            $targets = $request->getParameter('target_field', []);
            $ignored = $request->getParameter('is_ignored', []);
            $defaults = $request->getParameter('default_value', []);
            $transforms = $request->getParameter('transform', []);

            $mappings = [];
            foreach ($targets as $mapId => $target) {
                $mappings[] = [
                    'id' => (int) $mapId,
                    'target_field' => $target ?: null,
                    'is_ignored' => isset($ignored[$mapId]) ? 1 : 0,
                    'default_value' => $defaults[$mapId] ?? null,
                    'transform' => $transforms[$mapId] ?? null,
                ];
            }
            $svc->saveMappings($id, $mappings);

            // Enrich rows with new mappings
            $svc->enrichRows($id);

            // Extract file metadata
            $svc->extractFileMetadata($id);

            // Match digital objects if ZIP was uploaded
            $doStrategy = $request->getParameter('do_match_strategy', 'filename');
            $svc->matchDigitalObjects($id, $doStrategy);

            $svc->updateSessionStatus($id, 'validate');
            $this->redirect(['module' => 'ingest', 'action' => 'validate', 'id' => $id]);
        }

        $this->mappings = $svc->getMappings($id);
        $this->targetFields = \AhgIngestPlugin\Services\IngestService::getTargetFields($this->session->standard);
        $this->savedProfiles = $svc->getSavedMappingProfiles();

        // Get sample data for preview
        $this->sampleRows = \Illuminate\Database\Capsule\Manager::table('ingest_row')
            ->where('session_id', $id)
            ->orderBy('row_number')
            ->limit(5)
            ->get()
            ->toArray();
    }

    // ─── Step 4: Validate ───────────────────────────────────────────────

    public function executeValidate(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $this->session = $svc->getSession($id);
        if (!$this->session) {
            $this->forward404();
        }
        $this->requireSessionOwner($this->session);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action', 'validate');

            if ($action === 'exclude') {
                $rowNum = (int) $request->getParameter('row_number');
                $svc->excludeRow($id, $rowNum, true);
                $this->redirect(['module' => 'ingest', 'action' => 'validate', 'id' => $id]);
            }

            if ($action === 'include') {
                $rowNum = (int) $request->getParameter('row_number');
                $svc->excludeRow($id, $rowNum, false);
                $this->redirect(['module' => 'ingest', 'action' => 'validate', 'id' => $id]);
            }

            if ($action === 'fix') {
                $rowNum = (int) $request->getParameter('row_number');
                $field = $request->getParameter('field_name');
                $value = $request->getParameter('field_value');
                $svc->fixRow($id, $rowNum, $field, $value);
                $this->redirect(['module' => 'ingest', 'action' => 'validate', 'id' => $id]);
            }

            if ($action === 'proceed') {
                $svc->updateSessionStatus($id, 'preview');
                $this->redirect(['module' => 'ingest', 'action' => 'preview', 'id' => $id]);
            }
        }

        // Run validation
        $this->stats = $svc->validateSession($id);
        $this->errors = $svc->getValidationErrors($id);
        $this->rowCount = $svc->getRowCount($id);
    }

    // ─── Step 5: Preview ────────────────────────────────────────────────

    public function executePreview(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $this->session = $svc->getSession($id);
        if (!$this->session) {
            $this->forward404();
        }
        $this->requireSessionOwner($this->session);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'approve') {
                $svc->updateSessionStatus($id, 'commit');
                $this->redirect(['module' => 'ingest', 'action' => 'commit', 'id' => $id]);
            }

            if ($action === 'back') {
                $svc->updateSessionStatus($id, 'validate');
                $this->redirect(['module' => 'ingest', 'action' => 'validate', 'id' => $id]);
            }
        }

        $this->tree = $svc->buildHierarchyTree($id);
        $this->rowCount = $svc->getRowCount($id);

        $doCount = \Illuminate\Database\Capsule\Manager::table('ingest_row')
            ->where('session_id', $id)
            ->where('is_excluded', 0)
            ->where('digital_object_matched', 1)
            ->count();
        $this->doCount = $doCount;
    }

    // ─── Step 6: Commit ─────────────────────────────────────────────────

    public function executeCommit(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();
        $commitSvc = $this->getCommitService();

        $id = (int) $request->getParameter('id');
        $this->session = $svc->getSession($id);
        if (!$this->session) {
            $this->forward404();
        }
        $this->requireSessionOwner($this->session);

        // Check if job already exists
        $this->job = $commitSvc->getJobBySession($id);

        if ($request->isMethod('post') && !$this->job) {
            // Start the commit job
            $jobId = $commitSvc->startJob($id);

            // Launch background task (non-blocking)
            $atomRoot = sfConfig::get('sf_root_dir');
            $logFile = sfConfig::get('sf_upload_dir') . '/ingest/job_' . $jobId . '.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            $cmd = sprintf(
                'nohup php %s/symfony ingest:commit --job-id=%d > %s 2>&1 &',
                escapeshellarg($atomRoot),
                $jobId,
                escapeshellarg($logFile)
            );
            exec($cmd);

            $this->job = $commitSvc->getJobStatus($jobId);
        }
    }

    // ─── AJAX Endpoints ─────────────────────────────────────────────────

    public function executeSearchParent(sfWebRequest $request)
    {
        $this->requireAuth();

        $query = $request->getParameter('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            $results = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->join('slug', 'information_object.id', '=', 'slug.object_id')
                ->where('information_object_i18n.title', 'LIKE', '%' . $query . '%')
                ->where('information_object.id', '!=', 1)
                ->select(
                    'information_object.id',
                    'information_object_i18n.title',
                    'slug.slug',
                    'information_object.identifier'
                )
                ->limit(20)
                ->get()
                ->toArray();
        }

        $this->getResponse()->setContentType('application/json');
        echo json_encode($results);

        return sfView::NONE;
    }

    public function executeAutoMap(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('session_id');
        $session = $svc->getSession($id);
        if (!$session) {
            echo json_encode(['error' => 'Session not found']);
            return sfView::NONE;
        }

        $mappings = $svc->autoMapColumns($id, $session->standard);

        $this->getResponse()->setContentType('application/json');
        echo json_encode(['mappings' => $mappings]);

        return sfView::NONE;
    }

    public function executeExtractMetadata(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('session_id');
        $svc->extractFileMetadata($id);

        $this->getResponse()->setContentType('application/json');
        echo json_encode(['success' => true]);

        return sfView::NONE;
    }

    public function executeJobStatus(sfWebRequest $request)
    {
        $this->requireAuth();
        $commitSvc = $this->getCommitService();

        $jobId = (int) $request->getParameter('job_id');
        $job = $commitSvc->getJobStatus($jobId);

        $this->getResponse()->setContentType('application/json');
        echo json_encode($job ?: ['error' => 'Job not found']);

        return sfView::NONE;
    }

    public function executePreviewTree(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('session_id');
        $tree = $svc->buildHierarchyTree($id);

        $this->getResponse()->setContentType('application/json');
        echo json_encode($tree);

        return sfView::NONE;
    }

    // ─── Management ─────────────────────────────────────────────────────

    public function executeCancel(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $session = $svc->getSession($id);
        if (!$session) {
            $this->forward404();
        }
        $this->requireSessionOwner($session);

        $svc->updateSessionStatus($id, 'cancelled');
        $this->getUser()->setFlash('notice', 'Ingest session cancelled');
        $this->redirect(['module' => 'ingest', 'action' => 'index']);
    }

    public function executeRollback(sfWebRequest $request)
    {
        $this->requireAuth();
        $commitSvc = $this->getCommitService();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $session = $svc->getSession($id);
        if (!$session) {
            $this->forward404();
        }
        $this->requireSessionOwner($session);

        $job = $commitSvc->getJobBySession($id);
        if ($job) {
            $count = $commitSvc->rollback($job->id);
            $this->getUser()->setFlash('notice', "Rollback complete: {$count} records deleted");
        }

        $this->redirect(['module' => 'ingest', 'action' => 'index']);
    }

    public function executeDownloadManifest(sfWebRequest $request)
    {
        $this->requireAuth();
        $commitSvc = $this->getCommitService();
        $svc = $this->getIngestService();

        $id = (int) $request->getParameter('id');
        $session = $svc->getSession($id);
        if (!$session) {
            $this->forward404();
        }

        $job = $commitSvc->getJobBySession($id);
        if (!$job || !$job->manifest_path || !file_exists($job->manifest_path)) {
            $this->forward404();
        }

        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType('text/csv');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="ingest_manifest_' . $id . '.csv"');
        $response->setContent(file_get_contents($job->manifest_path));
        $response->send();

        return sfView::NONE;
    }

    public function executeDownloadTemplate(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getIngestService();

        $sector = $request->getParameter('sector', 'archive');
        $standard = $request->getParameter('standard', 'isadg');

        $csv = $svc->generateCsvTemplate($sector, $standard);

        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType('text/csv');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="ingest_template_' . $sector . '.csv"');
        $response->setContent($csv);
        $response->send();

        return sfView::NONE;
    }
}
