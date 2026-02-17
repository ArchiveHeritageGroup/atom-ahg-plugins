<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ahgResearcherPlugin actions — Researcher Collection Upload & Approval Workflow.
 *
 * 14 actions:
 *  1. dashboard         – Researcher home (stats + recent submissions)
 *  2. submissions       – List with status filter
 *  3. newSubmission     – Create submission form
 *  4. viewSubmission    – Detail view with items, files, timeline
 *  5. editSubmission    – Edit draft metadata
 *  6. addItem           – Add item (ISAD(G) form)
 *  7. editItem          – Edit item + manage files
 *  8. deleteItem        – Delete item (POST)
 *  9. submit            – Submit for review → workflow
 * 10. resubmit          – Resubmit after return
 * 11. importExchange    – Upload researcher-exchange.json
 * 12. publish           – Publish approved → AtoM records
 * 13. apiUpload         – AJAX file upload
 * 14. apiDeleteFile     – AJAX file delete
 */
class researcherActions extends AhgController
{
    // ─── SERVICE LOADING ────────────────────────────────────────

    protected function loadServices(): void
    {
        static $loaded = false;
        if (!$loaded) {
            $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgResearcherPlugin';
            require_once $pluginDir . '/lib/Services/SubmissionService.php';
            require_once $pluginDir . '/lib/Services/ExchangeImportService.php';
            require_once $pluginDir . '/lib/Services/PublishService.php';
            $loaded = true;
        }
    }

    protected function getSubmissionService(): \AhgResearcherPlugin\Services\SubmissionService
    {
        $this->loadServices();

        return new \AhgResearcherPlugin\Services\SubmissionService();
    }

    protected function getExchangeImportService(): \AhgResearcherPlugin\Services\ExchangeImportService
    {
        $this->loadServices();

        return new \AhgResearcherPlugin\Services\ExchangeImportService();
    }

    protected function getPublishService(): \AhgResearcherPlugin\Services\PublishService
    {
        $this->loadServices();

        return new \AhgResearcherPlugin\Services\PublishService();
    }

    // ─── HELPERS ────────────────────────────────────────────────

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
    }

    protected function userId(): int
    {
        return (int) $this->getUser()->getAttribute('user_id');
    }

    protected function isAdmin(): bool
    {
        return $this->getUser()->hasCredential('administrator');
    }

    protected function culture(): string
    {
        return $this->getUser()->getCulture() ?? 'en';
    }

    /**
     * Verify submission ownership (or admin).
     */
    protected function authorizeSubmission(object $submission): void
    {
        if (!$this->isAdmin() && (int) $submission->user_id !== $this->userId()) {
            $this->forward404('Submission not found.');
        }
    }

    // ─── 1. DASHBOARD ───────────────────────────────────────────

    public function executeDashboard($request)
    {
        $this->requireAuth();

        $service = $this->getSubmissionService();
        $userId = $this->userId();

        $this->stats = $service->getDashboardStats($this->isAdmin() ? null : $userId);
        $this->recent = $service->getSubmissions(
            $this->isAdmin() ? null : $userId
        );
        // Limit to 10 most recent
        $this->recent = array_slice($this->recent, 0, 10);
        $this->isAdmin = $this->isAdmin();

        // Research integration — load projects, collections, annotations
        $this->hasResearch = $service->hasResearchPlugin();
        $this->researcherProfile = null;
        $this->projects = [];
        $this->collections = [];
        $this->annotations = [];

        if ($this->hasResearch) {
            $this->researcherProfile = $service->getResearcherProfile($userId);
            if ($this->researcherProfile) {
                $rid = (int) $this->researcherProfile->id;
                $this->projects = $service->getResearchProjects($rid, 5);
                $this->collections = $service->getResearchCollections($rid, 5);
                $this->annotations = $service->getResearchAnnotations($rid, 5);
            }
        }
    }

    // ─── 2. SUBMISSIONS LIST ────────────────────────────────────

    public function executeSubmissions($request)
    {
        $this->requireAuth();

        $service = $this->getSubmissionService();
        $userId = $this->userId();

        $filters = [];
        if ($request->getParameter('status')) {
            $filters['status'] = $request->getParameter('status');
        }
        if ($request->getParameter('source_type')) {
            $filters['source_type'] = $request->getParameter('source_type');
        }

        $this->submissions = $service->getSubmissions(
            $this->isAdmin() ? null : $userId,
            $filters
        );
        $this->currentStatus = $request->getParameter('status', '');
        $this->isAdmin = $this->isAdmin();
    }

    // ─── 3. NEW SUBMISSION ──────────────────────────────────────

    public function executeNewSubmission($request)
    {
        $this->requireAuth();

        $service = $this->getSubmissionService();
        $this->repositories = $service->getRepositories($this->culture());

        // Load research projects for linking dropdown
        $this->projects = [];
        if ($service->hasResearchPlugin()) {
            $profile = $service->getResearcherProfile($this->userId());
            if ($profile) {
                $this->projects = $service->getResearchProjects((int) $profile->id, 50);
            }
        }

        if ($request->isMethod('post')) {
            $id = $service->createSubmission($this->userId(), [
                'title'           => $request->getParameter('title'),
                'description'     => $request->getParameter('description'),
                'repository_id'   => $request->getParameter('repository_id'),
                'parent_object_id' => $request->getParameter('parent_object_id'),
                'project_id'      => $request->getParameter('project_id'),
            ]);

            $this->getUser()->setFlash('notice', 'Submission created. Add items to your collection.');
            $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $id]);
        }
    }

    // ─── 4. VIEW SUBMISSION ─────────────────────────────────────

    public function executeViewSubmission($request)
    {
        $this->requireAuth();

        $id = (int) $request->getParameter('id');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($id);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        $this->submission = $data['submission'];
        $this->items = $data['items'];
        $this->files = $data['files'];
        $this->reviews = $data['reviews'];
        $this->isAdmin = $this->isAdmin();

        // Check workflow status for approved submissions
        $this->workflowComplete = false;
        if (in_array($this->submission->status, ['submitted', 'under_review'])) {
            if ($service->isWorkflowComplete($id)) {
                $service->markApproved($id);
                $this->submission->status = 'approved';
            }
        }
        $this->workflowComplete = $this->submission->status === 'approved';

        // Get repository name for display
        $this->repositoryName = null;
        if ($this->submission->repository_id) {
            $repo = \Illuminate\Database\Capsule\Manager::table('actor_i18n')
                ->where('id', $this->submission->repository_id)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('authorized_form_of_name');
            $this->repositoryName = $repo;
        }

        // Get linked project name
        $this->projectName = null;
        if ($this->submission->project_id) {
            $project = $service->getResearchProject((int) $this->submission->project_id);
            $this->projectName = $project ? $project->title : null;
        }
    }

    // ─── 5. EDIT SUBMISSION ─────────────────────────────────────

    public function executeEditSubmission($request)
    {
        $this->requireAuth();

        $id = (int) $request->getParameter('id');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($id);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        if ($data['submission']->status !== 'draft') {
            $this->getUser()->setFlash('error', 'Only draft submissions can be edited.');
            $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $id]);
        }

        $this->submission = $data['submission'];
        $this->repositories = $service->getRepositories($this->culture());

        // Load research projects for linking dropdown
        $this->projects = [];
        if ($service->hasResearchPlugin()) {
            $profile = $service->getResearcherProfile($this->userId());
            if ($profile) {
                $this->projects = $service->getResearchProjects((int) $profile->id, 50);
            }
        }

        if ($request->isMethod('post')) {
            $service->updateSubmission($id, [
                'title'           => $request->getParameter('title'),
                'description'     => $request->getParameter('description'),
                'repository_id'   => $request->getParameter('repository_id'),
                'parent_object_id' => $request->getParameter('parent_object_id'),
                'project_id'      => $request->getParameter('project_id'),
            ]);

            $this->getUser()->setFlash('notice', 'Submission updated.');
            $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $id]);
        }
    }

    // ─── 6. ADD ITEM ────────────────────────────────────────────

    public function executeAddItem($request)
    {
        $this->requireAuth();

        $submissionId = (int) $request->getParameter('id');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($submissionId);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        if (!in_array($data['submission']->status, ['draft', 'returned'])) {
            $this->getUser()->setFlash('error', 'Items can only be added to draft or returned submissions.');
            $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submissionId]);
        }

        $this->submission = $data['submission'];
        $this->submissionId = $submissionId;
        $this->items = $data['items']; // For parent selection
        $this->item = null; // New item
        $this->itemFiles = [];

        if ($request->isMethod('post')) {
            $itemId = $service->addItem($submissionId, [
                'item_type'             => $request->getParameter('item_type', 'description'),
                'parent_item_id'        => $request->getParameter('parent_item_id') ?: null,
                'title'                 => $request->getParameter('title'),
                'identifier'            => $request->getParameter('identifier'),
                'level_of_description'  => $request->getParameter('level_of_description', 'item'),
                'scope_and_content'     => $request->getParameter('scope_and_content'),
                'extent_and_medium'     => $request->getParameter('extent_and_medium'),
                'date_display'          => $request->getParameter('date_display'),
                'date_start'            => $request->getParameter('date_start'),
                'date_end'              => $request->getParameter('date_end'),
                'creators'              => $request->getParameter('creators'),
                'subjects'              => $request->getParameter('subjects'),
                'places'                => $request->getParameter('places'),
                'genres'                => $request->getParameter('genres'),
                'access_conditions'     => $request->getParameter('access_conditions'),
                'reproduction_conditions' => $request->getParameter('reproduction_conditions'),
                'notes'                 => $request->getParameter('notes'),
                'repository_name'       => $request->getParameter('repository_name'),
                'repository_address'    => $request->getParameter('repository_address'),
                'repository_contact'    => $request->getParameter('repository_contact'),
            ]);

            $this->getUser()->setFlash('notice', 'Item added. You can now upload files.');
            $this->redirect(['module' => 'researcher', 'action' => 'editItem', 'id' => $submissionId, 'itemId' => $itemId]);
        }
    }

    // ─── 7. EDIT ITEM ───────────────────────────────────────────

    public function executeEditItem($request)
    {
        $this->requireAuth();

        $submissionId = (int) $request->getParameter('id');
        $itemId = (int) $request->getParameter('itemId');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($submissionId);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        $item = $service->getItem($itemId);
        if (!$item || (int) $item->submission_id !== $submissionId) {
            $this->forward404('Item not found.');
        }

        $this->submission = $data['submission'];
        $this->submissionId = $submissionId;
        $this->item = $item;
        $this->items = $data['items']; // For parent selection
        $this->itemFiles = $data['files'][$itemId] ?? [];

        if ($request->isMethod('post')) {
            $service->updateItem($itemId, [
                'parent_item_id'        => $request->getParameter('parent_item_id') ?: null,
                'title'                 => $request->getParameter('title'),
                'identifier'            => $request->getParameter('identifier'),
                'level_of_description'  => $request->getParameter('level_of_description', 'item'),
                'scope_and_content'     => $request->getParameter('scope_and_content'),
                'extent_and_medium'     => $request->getParameter('extent_and_medium'),
                'date_display'          => $request->getParameter('date_display'),
                'date_start'            => $request->getParameter('date_start'),
                'date_end'              => $request->getParameter('date_end'),
                'creators'              => $request->getParameter('creators'),
                'subjects'              => $request->getParameter('subjects'),
                'places'                => $request->getParameter('places'),
                'genres'                => $request->getParameter('genres'),
                'access_conditions'     => $request->getParameter('access_conditions'),
                'reproduction_conditions' => $request->getParameter('reproduction_conditions'),
                'notes'                 => $request->getParameter('notes'),
                'repository_name'       => $request->getParameter('repository_name'),
                'repository_address'    => $request->getParameter('repository_address'),
                'repository_contact'    => $request->getParameter('repository_contact'),
            ]);

            $this->getUser()->setFlash('notice', 'Item updated.');
            $this->redirect(['module' => 'researcher', 'action' => 'editItem', 'id' => $submissionId, 'itemId' => $itemId]);
        }
    }

    // ─── 8. DELETE ITEM ─────────────────────────────────────────

    public function executeDeleteItem($request)
    {
        $this->requireAuth();

        $submissionId = (int) $request->getParameter('id');
        $itemId = (int) $request->getParameter('itemId');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($submissionId);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        if ($request->isMethod('post')) {
            $service->deleteItem($itemId);
            $this->getUser()->setFlash('notice', 'Item deleted.');
        }

        $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submissionId]);
    }

    // ─── 9. SUBMIT FOR REVIEW ───────────────────────────────────

    public function executeSubmit($request)
    {
        $this->requireAuth();

        $id = (int) $request->getParameter('id');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($id);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        if ($request->isMethod('post')) {
            $success = $service->submitForReview($id, $this->userId());

            if ($success) {
                $this->getUser()->setFlash('notice', 'Submission sent for review. You will be notified when reviewed.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot submit. Ensure you have at least one description item and the submission is in draft status.');
            }
        }

        $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $id]);
    }

    // ─── 10. RESUBMIT ──────────────────────────────────────────

    public function executeResubmit($request)
    {
        $this->requireAuth();

        $id = (int) $request->getParameter('id');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($id);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->authorizeSubmission($data['submission']);

        if ($request->isMethod('post')) {
            $success = $service->resubmit($id, $this->userId());

            if ($success) {
                $this->getUser()->setFlash('notice', 'Submission resubmitted for review.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot resubmit. Check submission status.');
            }
        }

        $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $id]);
    }

    // ─── 11. IMPORT EXCHANGE ────────────────────────────────────

    public function executeImportExchange($request)
    {
        $this->requireAuth();

        $service = $this->getSubmissionService();
        $this->repositories = $service->getRepositories($this->culture());
        $this->importResult = null;

        if ($request->isMethod('post')) {
            $file = $request->getFiles('exchange_file');

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->getUser()->setFlash('error', 'Please select a valid researcher-exchange.json file.');

                return;
            }

            $jsonString = file_get_contents($file['tmp_name']);
            $repositoryId = $request->getParameter('repository_id') ?: null;

            try {
                $importService = $this->getExchangeImportService();
                $result = $importService->import($this->userId(), $jsonString, $repositoryId);

                $this->importResult = $result;
                $this->getUser()->setFlash('notice',
                    'Import complete! Submission created as draft.'
                );
            } catch (\InvalidArgumentException $e) {
                $this->getUser()->setFlash('error', 'Invalid exchange file: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Import failed: ' . $e->getMessage());
            }
        }
    }

    // ─── 11b. CREATE FROM RESEARCH COLLECTION ──────────────────

    public function executeCreateFromCollection($request)
    {
        $this->requireAuth();

        $collectionId = (int) $request->getParameter('collectionId');
        $service = $this->getSubmissionService();

        if (!$service->hasResearchPlugin()) {
            $this->getUser()->setFlash('error', 'Research plugin is not installed.');
            $this->redirect(['module' => 'researcher', 'action' => 'dashboard']);
        }

        $projectId = $request->getParameter('project_id') ?: null;

        $submissionId = $service->createFromCollection($this->userId(), $collectionId, $projectId);

        if (!$submissionId) {
            $this->getUser()->setFlash('error', 'Could not create submission from collection. Check ownership and collection content.');
            $this->redirect(['module' => 'researcher', 'action' => 'dashboard']);
        }

        $this->getUser()->setFlash('notice', 'Submission created from research collection. Review the imported items and add files.');
        $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submissionId]);
    }

    // ─── 12. PUBLISH ────────────────────────────────────────────

    public function executePublish($request)
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            $this->getUser()->setFlash('error', 'Only administrators can publish submissions.');
            $this->redirect(['module' => 'researcher', 'action' => 'dashboard']);
        }

        $id = (int) $request->getParameter('id');
        $service = $this->getSubmissionService();
        $data = $service->getSubmission($id);

        if (!$data) {
            $this->forward404('Submission not found.');
        }

        $this->submission = $data['submission'];
        $this->publishResult = null;

        if ($this->submission->status !== 'approved') {
            $this->getUser()->setFlash('error', 'Only approved submissions can be published.');
            $this->redirect(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $id]);
        }

        if ($request->isMethod('post')) {
            $publishService = $this->getPublishService();
            $this->publishResult = $publishService->publish($id, $this->userId());
        }
    }

    // ─── 13. AJAX: FILE UPLOAD ──────────────────────────────────

    public function executeApiUpload($request)
    {
        $this->requireAuth();

        $this->getResponse()->setContentType('application/json');

        $itemId = (int) $request->getParameter('item_id');
        $file = $request->getFiles('file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => 'No file uploaded or upload error.',
            ]));
        }

        // Verify ownership
        $service = $this->getSubmissionService();
        $item = $service->getItem($itemId);
        if (!$item) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => 'Item not found.',
            ]));
        }

        $sub = \Illuminate\Database\Capsule\Manager::table('researcher_submission')
            ->where('id', $item->submission_id)->first();

        if (!$sub || (!$this->isAdmin() && (int) $sub->user_id !== $this->userId())) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => 'Not authorized.',
            ]));
        }

        $fileId = $service->addFile($itemId, $file);

        if ($fileId) {
            $fileRecord = $service->getFile($fileId);

            return $this->renderText(json_encode([
                'success' => true,
                'file'    => [
                    'id'            => $fileId,
                    'original_name' => $fileRecord->original_name,
                    'mime_type'     => $fileRecord->mime_type,
                    'file_size'     => $fileRecord->file_size,
                ],
            ]));
        }

        return $this->renderText(json_encode([
            'success' => false,
            'error'   => 'Failed to store file.',
        ]));
    }

    // ─── 14. AJAX: DELETE FILE ──────────────────────────────────

    public function executeApiDeleteFile($request)
    {
        $this->requireAuth();

        $this->getResponse()->setContentType('application/json');

        $fileId = (int) $request->getParameter('file_id');
        $service = $this->getSubmissionService();
        $file = $service->getFile($fileId);

        if (!$file) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => 'File not found.',
            ]));
        }

        // Verify ownership via item → submission
        $item = $service->getItem($file->item_id);
        if ($item) {
            $sub = \Illuminate\Database\Capsule\Manager::table('researcher_submission')
                ->where('id', $item->submission_id)->first();

            if ($sub && !$this->isAdmin() && (int) $sub->user_id !== $this->userId()) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error'   => 'Not authorized.',
                ]));
            }
        }

        $service->deleteFile($fileId);

        return $this->renderText(json_encode([
            'success' => true,
        ]));
    }

    // ─── 15. AJAX: AUTOCOMPLETE (terms + actors) ────────────────

    public function executeApiAutocomplete($request)
    {
        $this->requireAuth();

        $this->getResponse()->setContentType('application/json');

        $query = trim($request->getParameter('query', ''));
        $source = $request->getParameter('source', 'term'); // 'term' or 'actor'
        $taxonomyId = (int) $request->getParameter('taxonomy', 0);
        $limit = min((int) $request->getParameter('limit', 10), 25);

        if (strlen($query) < 1) {
            return $this->renderText(json_encode([]));
        }

        $DB = \Illuminate\Database\Capsule\Manager::class;

        if ($source === 'actor') {
            // Search actors by name
            $results = $DB::table('actor_i18n')
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->where('authorized_form_of_name', 'LIKE', '%' . $query . '%')
                ->whereNotNull('authorized_form_of_name')
                ->orderBy('authorized_form_of_name')
                ->limit($limit)
                ->select('id', 'authorized_form_of_name as name')
                ->get()
                ->toArray();

            return $this->renderText(json_encode(array_map(function ($r) {
                return ['id' => $r->id, 'name' => $r->name];
            }, $results)));
        }

        // Search terms by taxonomy
        if ($taxonomyId < 1) {
            return $this->renderText(json_encode([]));
        }

        $results = $DB::table('term')
            ->join('term_i18n', function ($j) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', 'en');
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', 'LIKE', '%' . $query . '%')
            ->orderBy('term_i18n.name')
            ->limit($limit)
            ->select('term.id', 'term_i18n.name')
            ->get()
            ->toArray();

        return $this->renderText(json_encode(array_map(function ($r) {
            return ['id' => $r->id, 'name' => $r->name];
        }, $results)));
    }
}
