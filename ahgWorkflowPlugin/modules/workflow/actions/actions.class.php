<?php

use AtomFramework\Http\Controllers\AhgController;
class workflowActions extends AhgController
{
    protected ?WorkflowService $service = null;

    protected function getService(): WorkflowService
    {
        if ($this->service === null) {
            require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowService.php';
            $this->service = new WorkflowService();
        }
        return $this->service;
    }

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Administrator access required');
        }
    }

    protected function getCurrentUserId(): int
    {
        return (int) $this->getUser()->getAttribute('user_id');
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function executeDashboard($request)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();

        $service = $this->getService();
        $this->stats = $service->getDashboardStats($userId);
        $this->myTasks = $service->getMyTasks($userId);
        $this->poolTasks = $service->getPoolTasks($userId);
        $this->recentActivity = $service->getRecentActivity(10);
        $this->isAdmin = $this->getUser()->hasCredential('administrator');
    }

    // =========================================================================
    // MY TASKS
    // =========================================================================

    public function executeMyTasks($request)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();

        $status = $request->getParameter('status');
        $this->tasks = $this->getService()->getMyTasks($userId, $status);
        $this->currentStatus = $status;
    }

    // =========================================================================
    // POOL
    // =========================================================================

    public function executePool($request)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();

        $this->tasks = $this->getService()->getPoolTasks($userId);
    }

    // =========================================================================
    // TASK ACTIONS
    // =========================================================================

    public function executeViewTask($request)
    {
        $this->requireAuth();
        $taskId = (int) $request->getParameter('id');

        $this->task = $this->getService()->getTask($taskId);
        if (!$this->task) {
            $this->forward404('Task not found');
        }

        $this->currentUserId = $this->getCurrentUserId();
        $this->canClaim = $this->task->status === 'pending' && $this->task->assigned_to === null;
        $this->canAct = $this->task->assigned_to === $this->currentUserId && in_array($this->task->status, ['claimed', 'in_progress']);
        $this->canResubmit = $this->task->submitted_by === $this->currentUserId && $this->task->status === 'returned';
    }

    public function executeClaimTask($request)
    {
        $this->requireAuth();
        $taskId = (int) $request->getParameter('id');

        try {
            $this->getService()->claimTask($taskId, $this->getCurrentUserId());
            $this->getUser()->setFlash('notice', 'Task claimed successfully');
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect("workflow/task/{$taskId}");
    }

    public function executeReleaseTask($request)
    {
        $this->requireAuth();
        $taskId = (int) $request->getParameter('id');

        $comment = $request->getParameter('comment');
        $this->getService()->releaseTask($taskId, $this->getCurrentUserId(), $comment);
        $this->getUser()->setFlash('notice', 'Task released to pool');

        $this->redirect('workflow/my-tasks');
    }

    public function executeApproveTask($request)
    {
        $this->requireAuth();

        if ($request->isMethod('post')) {
            $taskId = (int) $request->getParameter('id');
            $comment = $request->getParameter('comment');
            $checklist = $request->getParameter('checklist', []);

            try {
                $this->getService()->approveTask($taskId, $this->getCurrentUserId(), $comment, $checklist);
                // Spectrum Phase C2 — fire chain rules (best-effort, doesn't block approval)
                try {
                    require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
                    $chain = (new SpectrumComplianceService())->applyChainOnTaskApproved($taskId);
                    $msg = 'Task approved';
                    if (($chain['spawned'] ?? 0) > 0) {
                        $msg .= sprintf(' (Spectrum chain spawned %d downstream task(s))', $chain['spawned']);
                    }
                    $this->getUser()->setFlash('notice', $msg);
                } catch (\Throwable $chainErr) {
                    $this->getUser()->setFlash('notice', 'Task approved');
                }
                $this->redirect('workflow/my-tasks');
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
                $this->redirect("workflow/task/{$taskId}");
            }
        }

        $this->forward404();
    }

    public function executeRejectTask($request)
    {
        $this->requireAuth();

        if ($request->isMethod('post')) {
            $taskId = (int) $request->getParameter('id');
            $comment = $request->getParameter('comment');

            if (empty($comment)) {
                $this->getUser()->setFlash('error', 'A comment is required when rejecting');
                $this->redirect("workflow/task/{$taskId}");
            }

            try {
                $this->getService()->rejectTask($taskId, $this->getCurrentUserId(), $comment);
                $this->getUser()->setFlash('notice', 'Task rejected');
                $this->redirect('workflow/my-tasks');
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
                $this->redirect("workflow/task/{$taskId}");
            }
        }

        $this->forward404();
    }

    // =========================================================================
    // HISTORY
    // =========================================================================

    public function executeHistory($request)
    {
        $this->requireAuth();
        $this->activity = $this->getService()->getRecentActivity(100);
    }

    public function executeObjectHistory($request)
    {
        $this->requireAuth();
        $objectId = (int) $request->getParameter('object_id');

        $this->objectId = $objectId;
        $this->history = $this->getService()->getObjectHistory($objectId);

        // Get object info
        $this->object = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->first();
    }

    // =========================================================================
    // START WORKFLOW
    // =========================================================================

    public function executeStartWorkflow($request)
    {
        $this->requireAuth();
        $objectId = (int) $request->getParameter('object_id');

        try {
            $taskId = $this->getService()->startWorkflow($objectId, $this->getCurrentUserId());
            if ($taskId) {
                $this->getUser()->setFlash('notice', 'Workflow started successfully');
                $this->redirect("workflow/task/{$taskId}");
            } else {
                $this->getUser()->setFlash('error', 'No workflow configured for this item');
            }
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect($request->getReferer() ?? 'workflow/dashboard');
    }

    // =========================================================================
    // ADMIN: WORKFLOW MANAGEMENT
    // =========================================================================

    public function executeAdmin($request)
    {
        $this->requireAdmin();
        $this->showInactive = (bool) $request->getParameter('show_inactive', 0);
        $filters = $this->showInactive ? [] : ['is_active' => 1];

        // Spectrum#A — optional filter
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';
        $spectrumFilter = SpectrumProcedureCatalog::normalize($request->getParameter('spectrum'));
        if ($spectrumFilter !== null) {
            $filters['spectrum_procedure'] = $spectrumFilter;
        }

        $this->workflows = $this->getService()->getWorkflows($filters);
        $this->spectrumProcedures = SpectrumProcedureCatalog::all();
        $this->spectrumFilter = $spectrumFilter;
    }

    public function executeCreateWorkflow($request)
    {
        $this->requireAdmin();

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'scope_type' => $request->getParameter('scope_type', 'global'),
                'scope_id' => $request->getParameter('scope_id') ?: null,
                'trigger_event' => $request->getParameter('trigger_event', 'submit'),
                'applies_to' => $request->getParameter('applies_to', 'information_object'),
                'is_active' => $request->getParameter('is_active', 1),
                'is_default' => $request->getParameter('is_default', 0),
                'notification_enabled' => $request->getParameter('notification_enabled', 1),
                'spectrum_procedure' => $request->getParameter('spectrum_procedure'),
                'created_by' => $this->getCurrentUserId(),
            ];

            $workflowId = $this->getService()->createWorkflow($data);
            $this->getUser()->setFlash('notice', 'Workflow created');
            $this->redirect("workflow/admin/edit/{$workflowId}");
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';
        $this->repositories = $this->getRepositories();
        $this->collections = $this->getCollections();
        $this->spectrumProcedures = SpectrumProcedureCatalog::all();
    }

    public function executeEditWorkflow($request)
    {
        $this->requireAdmin();
        $workflowId = (int) $request->getParameter('id');

        $this->workflow = $this->getService()->getWorkflow($workflowId);
        if (!$this->workflow) {
            $this->forward404('Workflow not found');
        }

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'scope_type' => $request->getParameter('scope_type', 'global'),
                'scope_id' => $request->getParameter('scope_id') ?: null,
                'trigger_event' => $request->getParameter('trigger_event'),
                'is_active' => $request->getParameter('is_active', 0),
                'is_default' => $request->getParameter('is_default', 0),
                'notification_enabled' => $request->getParameter('notification_enabled', 0),
                'spectrum_procedure' => $request->getParameter('spectrum_procedure'),
            ];

            $this->getService()->updateWorkflow($workflowId, $data);
            $this->getUser()->setFlash('notice', 'Workflow updated');
            $this->redirect("workflow/admin/edit/{$workflowId}");
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';
        $this->repositories = $this->getRepositories();
        $this->collections = $this->getCollections();
        $this->roles = $this->getRoles();
        $this->clearanceLevels = $this->getClearanceLevels();
        $this->spectrumProcedures = SpectrumProcedureCatalog::all();
    }

    public function executeDeleteWorkflow($request)
    {
        $this->requireAdmin();
        $workflowId = (int) $request->getParameter('id');

        try {
            $this->getService()->deleteWorkflow($workflowId);
            $this->getUser()->setFlash('notice', 'Workflow deleted');
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect('workflow/admin');
    }

    // =========================================================================
    // ADMIN: STEP MANAGEMENT
    // =========================================================================

    public function executeAddStep($request)
    {
        $this->requireAdmin();
        $workflowId = (int) $request->getParameter('workflow_id');

        $this->workflow = $this->getService()->getWorkflow($workflowId);
        if (!$this->workflow) {
            $this->forward404('Workflow not found');
        }

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'step_type' => $request->getParameter('step_type', 'review'),
                'action_required' => $request->getParameter('action_required', 'approve_reject'),
                'required_role_id' => $request->getParameter('required_role_id') ?: null,
                'required_clearance_level' => $request->getParameter('required_clearance_level') ?: null,
                'pool_enabled' => $request->getParameter('pool_enabled', 1),
                'escalation_days' => $request->getParameter('escalation_days') ?: null,
                'auto_assign_user_id' => $request->getParameter('auto_assign_user_id') ?: null,
                'escalation_user_id' => $request->getParameter('escalation_user_id') ?: null,
                'instructions' => $request->getParameter('instructions'),
                'is_optional' => $request->getParameter('is_optional', 0),
            ];

            $this->getService()->addStep($workflowId, $data);
            $this->getUser()->setFlash('notice', 'Step added');
            $this->redirect("workflow/admin/edit/{$workflowId}");
        }

        $this->roles = $this->getRoles();
        $this->clearanceLevels = $this->getClearanceLevels();
        $this->users = $this->getUsers();
    }

    public function executeEditStep($request)
    {
        $this->requireAdmin();
        $stepId = (int) $request->getParameter('id');

        $this->step = $this->getService()->getStep($stepId);
        if (!$this->step) {
            $this->forward404('Step not found');
        }

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'step_type' => $request->getParameter('step_type'),
                'action_required' => $request->getParameter('action_required'),
                'required_role_id' => $request->getParameter('required_role_id') ?: null,
                'required_clearance_level' => $request->getParameter('required_clearance_level') ?: null,
                'pool_enabled' => $request->getParameter('pool_enabled', 0),
                'escalation_days' => $request->getParameter('escalation_days') ?: null,
                'auto_assign_user_id' => $request->getParameter('auto_assign_user_id') ?: null,
                'escalation_user_id' => $request->getParameter('escalation_user_id') ?: null,
                'instructions' => $request->getParameter('instructions'),
                'is_optional' => $request->getParameter('is_optional', 0),
                'is_active' => $request->getParameter('is_active', 1),
            ];

            $this->getService()->updateStep($stepId, $data);
            $this->getUser()->setFlash('notice', 'Step updated');
            $this->redirect("workflow/admin/edit/{$this->step->workflow_id}");
        }

        $this->roles = $this->getRoles();
        $this->clearanceLevels = $this->getClearanceLevels();
        $this->users = $this->getUsers();
    }

    public function executeDeleteStep($request)
    {
        $this->requireAdmin();
        $stepId = (int) $request->getParameter('id');

        $step = $this->getService()->getStep($stepId);
        if (!$step) {
            $this->forward404('Step not found');
        }

        try {
            $this->getService()->deleteStep($stepId);
            $this->getUser()->setFlash('notice', 'Step deleted');
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect("workflow/admin/edit/{$step->workflow_id}");
    }

    public function executeReorderSteps($request)
    {
        $this->requireAdmin();

        if ($request->isMethod('post')) {
            $workflowId = (int) $request->getParameter('workflow_id');
            $stepIds = $request->getParameter('steps', []);

            $this->getService()->reorderSteps($workflowId, $stepIds);
            return $this->renderText(json_encode(['success' => true]));
        }

        $this->forward404();
    }

    // =========================================================================
    // V2.0: TIMELINE (#172)
    // =========================================================================

    public function executeTimeline($request)
    {
        $this->requireAuth();
        $objectId = (int) $request->getParameter('object_id');

        if (!$objectId) {
            $this->forward404('Object ID required');
        }

        $filters = [];
        if ($request->getParameter('type')) {
            $filters['type'] = $request->getParameter('type');
        }
        if ($request->getParameter('date_from')) {
            $filters['date_from'] = $request->getParameter('date_from');
        }
        if ($request->getParameter('date_to')) {
            $filters['date_to'] = $request->getParameter('date_to');
        }
        if ($request->getParameter('action')) {
            $filters['action'] = $request->getParameter('action');
        }

        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowEventService.php';
        $eventService = new WorkflowEventService();

        // JSON response
        $format = $request->getParameter('format', 'html');

        if ($format === 'json') {
            $this->getResponse()->setContentType('application/json');
            $events = $eventService->getTimeline($objectId, $filters);
            return $this->renderText(json_encode(['events' => $events, 'object_id' => $objectId]));
        }

        if ($format === 'csv') {
            $this->getResponse()->setContentType('text/csv');
            $this->getResponse()->setHttpHeader('Content-Disposition', "attachment; filename=timeline_{$objectId}.csv");
            return $this->renderText($eventService->exportTimelineCsv($objectId, $filters));
        }

        // HTML view
        $this->events = $eventService->getTimeline($objectId, $filters);
        $this->objectId = $objectId;
        $this->actions = $eventService->getAllActions();
        $this->filters = $filters;

        // Get object info
        $this->object = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->first();
    }

    // =========================================================================
    // V2.0: QUEUES (#173)
    // =========================================================================

    public function executeQueues($request)
    {
        $this->requireAuth();

        $this->queues = $this->getService()->getQueueStats();
        $this->isAdmin = $this->getUser()->hasCredential('administrator');

        // SLA overview
        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowSlaService.php';
        $slaService = new WorkflowSlaService();
        $this->slaOverview = $slaService->getOverview();
    }

    // =========================================================================
    // V2.0: MY WORK / TEAM WORK (#173)
    // =========================================================================

    public function executeMyWork($request)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();

        $filters = [];
        if ($request->getParameter('queue_id')) {
            $filters['queue_id'] = (int) $request->getParameter('queue_id');
        }
        if ($request->getParameter('status')) {
            $filters['status'] = $request->getParameter('status');
        }
        if ($request->getParameter('priority')) {
            $filters['priority'] = $request->getParameter('priority');
        }

        $this->tasks = $this->getService()->getMyWork($userId, $filters);
        $this->queues = $this->getService()->getQueues();
        $this->filters = $filters;
    }

    public function executeTeamWork($request)
    {
        $this->requireAuth();

        // Get user's primary group
        $userId = $this->getCurrentUserId();
        $groupId = (int) $request->getParameter('group_id');

        if (!$groupId) {
            $groupId = \Illuminate\Database\Capsule\Manager::table('acl_user_group')
                ->where('user_id', $userId)
                ->where('group_id', '>', 1)
                ->value('group_id') ?: 0;
        }

        $filters = [];
        if ($request->getParameter('queue_id')) {
            $filters['queue_id'] = (int) $request->getParameter('queue_id');
        }

        $this->tasks = $groupId ? $this->getService()->getTeamWork($groupId, $filters) : [];
        $this->queues = $this->getService()->getQueues();
        $this->roles = $this->getRoles();
        $this->currentGroupId = $groupId;
        $this->filters = $filters;
    }

    // =========================================================================
    // V2.0: OVERDUE DASHBOARD (#174)
    // =========================================================================

    public function executeOverdue($request)
    {
        $this->requireAuth();

        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowSlaService.php';
        $slaService = new WorkflowSlaService();

        $filters = [];
        if ($request->getParameter('user_id')) {
            $filters['user_id'] = (int) $request->getParameter('user_id');
        }
        if ($request->getParameter('queue_id')) {
            $filters['queue_id'] = (int) $request->getParameter('queue_id');
        }
        if ($request->getParameter('priority')) {
            $filters['priority'] = $request->getParameter('priority');
        }

        // CSV export
        if ($request->getParameter('format') === 'csv') {
            $this->getResponse()->setContentType('text/csv');
            $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename=overdue_report.csv');
            return $this->renderText($slaService->exportOverdueCsv($filters));
        }

        $this->tasks = $slaService->getOverdueTasks($filters);
        $this->slaOverview = $slaService->getOverview();
        $this->statsByQueue = $slaService->getStatsByQueue();
        $this->queues = $this->getService()->getQueues();
        $this->users = $this->getUsers();
        $this->filters = $filters;
        $this->isAdmin = $this->getUser()->hasCredential('administrator');
    }

    // =========================================================================
    // V2.0: BULK OPERATIONS (#175)
    // =========================================================================

    public function executeBulkPreview($request)
    {
        $this->requireAuth();
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        $taskIds = $request->getParameter('task_ids', []);
        $action = $request->getParameter('bulk_action', '');

        if (empty($taskIds) || empty($action)) {
            return $this->renderText(json_encode(['error' => 'task_ids and bulk_action required']));
        }

        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowEventService.php';
        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowBulkService.php';

        $eventService = new WorkflowEventService();
        $bulkService = new WorkflowBulkService($this->getService(), $eventService);

        if ($action === 'assign') {
            $targetUserId = (int) $request->getParameter('target_user_id');
            $preview = $bulkService->previewBulkAssign($taskIds, $targetUserId);
        } else {
            $preview = $bulkService->previewBulkTransition($taskIds, $action, $this->getCurrentUserId());
        }

        return $this->renderText(json_encode(['preview' => $preview, 'action' => $action]));
    }

    public function executeBulkExecute($request)
    {
        $this->requireAuth();
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        $taskIds = $request->getParameter('task_ids', []);
        $action = $request->getParameter('bulk_action', '');
        $comment = $request->getParameter('comment');
        $userId = $this->getCurrentUserId();

        if (empty($taskIds) || empty($action)) {
            return $this->renderText(json_encode(['error' => 'task_ids and bulk_action required']));
        }

        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowEventService.php';
        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowBulkService.php';

        $eventService = new WorkflowEventService();
        $bulkService = new WorkflowBulkService($this->getService(), $eventService);

        $result = match ($action) {
            'assign' => $bulkService->bulkAssign($taskIds, (int) $request->getParameter('target_user_id'), $userId),
            'approve', 'reject', 'return', 'cancel' => $bulkService->bulkTransition($taskIds, $action, $userId, $comment),
            'note' => $bulkService->bulkAddNote($taskIds, $comment ?: '', $userId),
            'priority' => $bulkService->bulkChangePriority($taskIds, $request->getParameter('new_priority', 'normal'), $userId),
            'queue' => $bulkService->bulkMoveToQueue($taskIds, (int) $request->getParameter('queue_id'), $userId),
            default => ['error' => "Unknown action: {$action}"],
        };

        return $this->renderText(json_encode($result));
    }

    // =========================================================================
    // PUBLISH GATES (#176)
    // =========================================================================

    protected function getGateService(): PublishGateService
    {
        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/PublishGateService.php';
        return new PublishGateService();
    }

    public function executePublishReadiness($request)
    {
        $this->requireAuth();
        $objectId = (int) $request->getParameter('object_id');

        $gateService = $this->getGateService();
        $this->results = $gateService->evaluate($objectId, $this->getCurrentUserId());
        $this->canPublish = !in_array(true, array_map(
            fn($r) => $r['severity'] === 'blocker' && $r['status'] === 'failed',
            $this->results
        ));
        $this->objectId = $objectId;
        $this->isAdmin = $this->getUser()->hasCredential('administrator');

        // Stats
        $this->passedCount = count(array_filter($this->results, fn($r) => $r['status'] === 'passed'));
        $this->failedCount = count(array_filter($this->results, fn($r) => $r['status'] === 'failed'));
        $this->warningCount = count(array_filter($this->results, fn($r) => $r['status'] === 'warning'));
        $this->skippedCount = count(array_filter($this->results, fn($r) => $r['status'] === 'skipped'));

        // Object info
        $this->object = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->join('information_object as io', 'information_object_i18n.id', '=', 'io.id')
            ->where('information_object_i18n.id', $objectId)
            ->where('information_object_i18n.culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->select('information_object_i18n.title', 'io.identifier')
            ->first();
    }

    public function executePublishSimulate($request)
    {
        $this->requireAuth();
        $objectId = (int) $request->getParameter('object_id');

        $this->objectId = $objectId;
        $this->preview = $this->getGateService()->simulatePublish(
            $objectId,
            \AtomExtensions\Helpers\CultureHelper::getCulture()
        );
    }

    public function executePublishExecute($request)
    {
        $this->requireAuth();
        $objectId = (int) $request->getParameter('object_id');
        $force = (bool) $request->getParameter('force', 0);

        if ($force) {
            $this->requireAdmin();
        }

        $result = $this->getGateService()->executePublish(
            $objectId,
            $this->getCurrentUserId(),
            $force
        );

        if ($result['published']) {
            $this->getUser()->setFlash('notice', 'Record published successfully');
        } else {
            $blockerMessages = array_map(fn($b) => $b['error_message'], $result['blockers']);
            $this->getUser()->setFlash('error', 'Cannot publish: ' . implode('; ', $blockerMessages));
        }

        $this->redirect("workflow/publish-readiness/{$objectId}");
    }

    public function executeGateAdmin($request)
    {
        $this->requireAdmin();
        $this->rules = $this->getGateService()->getRules();
    }

    public function executeGateRuleEdit($request)
    {
        $this->requireAdmin();
        $ruleId = (int) $request->getParameter('id');

        $gateService = $this->getGateService();

        // Load existing rule (0 = new rule)
        $this->rule = $ruleId > 0
            ? \Illuminate\Database\Capsule\Manager::table('ahg_publish_gate_rule')->where('id', $ruleId)->first()
            : null;

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'rule_type' => $request->getParameter('rule_type'),
                'entity_type' => $request->getParameter('entity_type', 'information_object'),
                'level_of_description_id' => $request->getParameter('level_of_description_id') ?: null,
                'material_type' => $request->getParameter('material_type') ?: null,
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'field_name' => $request->getParameter('field_name') ?: null,
                'rule_config' => $request->getParameter('rule_config') ?: null,
                'error_message' => $request->getParameter('error_message'),
                'severity' => $request->getParameter('severity', 'blocker'),
                'is_active' => $request->getParameter('is_active') ? 1 : 0,
                'sort_order' => (int) $request->getParameter('sort_order', 0),
            ];

            if ($this->rule) {
                $gateService->updateRule($ruleId, $data);
                $this->getUser()->setFlash('notice', 'Rule updated');
            } else {
                $gateService->createRule($data);
                $this->getUser()->setFlash('notice', 'Rule created');
            }

            $this->redirect('workflow/admin/gates');
        }

        // Load dropdown data for form
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $this->ruleTypes = \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->where('taxonomy', 'publish_gate_rule_type')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $this->levels = \Illuminate\Database\Capsule\Manager::table('term_i18n')
            ->join('term', 'term_i18n.id', '=', 'term.id')
            ->where('term.taxonomy_id', 34) // Level of description taxonomy
            ->where('term_i18n.culture', $culture)
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->get()
            ->toArray();

        $this->repositories = $this->getRepositories();
    }

    public function executeGateRuleDelete($request)
    {
        $this->requireAdmin();
        $ruleId = (int) $request->getParameter('id');

        $this->getGateService()->deleteRule($ruleId);
        $this->getUser()->setFlash('notice', 'Rule deleted');
        $this->redirect('workflow/admin/gates');
    }

    // =========================================================================
    // CHANGE SUMMARY (#177)
    // =========================================================================

    public function executeChangeSummary($request)
    {
        $this->requireAuth();
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        $objectId = (int) $request->getParameter('object_id');
        if (!$objectId) {
            return $this->renderText(json_encode(['error' => 'object_id required']));
        }

        // Collect new values from POST
        $newValues = [];
        $fields = ['title', 'scope_and_content', 'extent_and_medium', 'archival_history',
                    'acquisition', 'arrangement', 'access_conditions', 'reproduction_conditions',
                    'physical_characteristics', 'finding_aids', 'location_of_originals',
                    'location_of_copies', 'related_units_of_description', 'rules'];
        foreach ($fields as $f) {
            $val = $request->getParameter($f);
            if ($val !== null) {
                $newValues[$f] = $val;
            }
        }

        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/ChangeSummaryService.php';
        $summaryService = new ChangeSummaryService();

        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $diffs = $summaryService->computeDiff($objectId, $newValues, $culture);
        $summary = $summaryService->formatSummary($diffs);

        return $this->renderText(json_encode([
            'diffs' => $diffs,
            'summary' => $summary,
            'change_count' => count($diffs),
        ]));
    }

    // =========================================================================
    // API ENDPOINTS
    // =========================================================================

    public function executeApiStats($request)
    {
        $this->requireAuth();
        $this->getResponse()->setContentType('application/json');
        $stats = $this->getService()->getDashboardStats($this->getCurrentUserId());
        return $this->renderText(json_encode($stats));
    }

    public function executeApiTasks($request)
    {
        $this->requireAuth();
        $this->getResponse()->setContentType('application/json');
        $type = $request->getParameter('type', 'my');

        $tasks = $type === 'pool'
            ? $this->getService()->getPoolTasks($this->getCurrentUserId())
            : $this->getService()->getMyTasks($this->getCurrentUserId());

        return $this->renderText(json_encode($tasks));
    }

    public function executeApiSlaStatus($request)
    {
        $this->requireAuth();
        $this->getResponse()->setContentType('application/json');

        $taskId = (int) $request->getParameter('task_id');
        if ($taskId) {
            $task = \Illuminate\Database\Capsule\Manager::table('ahg_workflow_task')->where('id', $taskId)->first();
            if (!$task) {
                return $this->renderText(json_encode(['error' => 'Task not found']));
            }
            require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowSlaService.php';
            $sla = (new WorkflowSlaService())->computeForTask($task);
            return $this->renderText(json_encode($sla));
        }

        // Overview
        require_once $this->config('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowSlaService.php';
        return $this->renderText(json_encode((new WorkflowSlaService())->getOverview()));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function getRepositories(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('repository as r')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereNotNull('ai.authorized_form_of_name')
            ->select('r.id', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();
    }

    protected function getRoles(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('acl_group as g')
            ->join('acl_group_i18n as gi', function ($join) {
                $join->on('g.id', '=', 'gi.id')->where('gi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereNotNull('gi.name')
            ->where('g.id', '>', 1)
            ->select('g.id', 'gi.name')
            ->orderBy('gi.name')
            ->get()
            ->toArray();
    }

    protected function getCollections(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.parent_id', 1)
            ->whereNotNull('ioi.title')
            ->select('io.id', 'ioi.title as name')
            ->orderBy('ioi.title')
            ->get()
            ->toArray();
    }

    protected function getClearanceLevels(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('security_classification')
            ->orderBy('level')
            ->get()
            ->toArray();
    }

    protected function getUsers(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('user')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('user.id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('user.active', 1)
            ->selectRaw('user.id, user.username, COALESCE(ai.authorized_form_of_name, user.username) as name')
            ->orderByRaw('COALESCE(ai.authorized_form_of_name, user.username)')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // heratio#143 Phase 1 — read-only diagram
    // =========================================================================

    public function executeDiagram($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id', 0);
        if ($id <= 0) {
            $this->forward404('Workflow id required');
        }
        $workflow = $this->getService()->getWorkflow($id);
        if (!$workflow) {
            $this->forward404('Workflow not found');
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/WorkflowEdgeService.php';
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/WorkflowDiagramService.php';
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';

        $svc = new WorkflowDiagramService();
        $this->workflow = $workflow;
        $this->svg = $svc->render($id);
        $this->fallback = $svc->textFallback($id);
        $this->spectrumLabel = SpectrumProcedureCatalog::label($workflow->spectrum_procedure ?? null);
    }

    // =========================================================================
    // heratio#143 Phase 2 — task progress overlay
    // =========================================================================

    public function executeTaskDiagram($request)
    {
        $this->requireAuth();
        $taskId = (int) $request->getParameter('id', 0);
        if ($taskId <= 0) {
            $this->forward404('Task id required');
        }
        $task = $this->getService()->getTask($taskId);
        if (!$task) {
            $this->forward404('Task not found');
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/WorkflowEdgeService.php';
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/WorkflowDiagramService.php';
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';

        $svc = new WorkflowDiagramService();
        $payload = $svc->renderForTask($taskId);
        $workflow = $this->getService()->getWorkflow((int) $task->workflow_id);

        $this->task = $task;
        $this->workflow = $workflow;
        $this->svg = $payload['svg'];
        $this->statusMap = $payload['statusMap'];
        $this->fallback = $svc->textFallback((int) $task->workflow_id);
        $this->spectrumLabel = SpectrumProcedureCatalog::label($workflow->spectrum_procedure ?? null);
    }

    // =========================================================================
    // heratio#143 Phase 3 — drag-drop designer
    // =========================================================================

    public function executeDesigner($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id', 0);
        $workflow = $this->getService()->getWorkflow($id);
        if (!$workflow) {
            $this->forward404('Workflow not found');
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/WorkflowEdgeService.php';

        $this->workflow = $workflow;
        $this->steps = \Illuminate\Database\Capsule\Manager::table('ahg_workflow_step')
            ->where('workflow_id', $id)
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['id', 'name', 'step_order', 'step_type', 'is_optional']);
        $this->edges = (new WorkflowEdgeService())->getEdges($id);
    }

    /**
     * POST /workflow/designerSave?id=N — AJAX save endpoint.
     * Request body: { "edges": [{"from_step_id":1, "to_step_id":2}, ...] }
     */
    public function executeDesignerSave($request)
    {
        $this->requireAdmin();
        if (!$request->isMethod('post')) {
            return $this->renderJson(['ok' => false, 'errors' => ['POST required']], 405);
        }
        $id = (int) $request->getParameter('id', 0);
        $workflow = $this->getService()->getWorkflow($id);
        if (!$workflow) {
            return $this->renderJson(['ok' => false, 'errors' => ['Workflow not found']], 404);
        }

        // Accept JSON body OR form-encoded `edges` field
        $body = $request->getContent();
        $payload = null;
        if (!empty($body)) {
            $payload = json_decode($body, true);
        }
        $raw = is_array($payload['edges'] ?? null) ? $payload['edges'] : (array) $request->getParameter('edges', []);

        $edges = [];
        foreach ($raw as $e) {
            if (!is_array($e)) continue;
            $edges[] = [
                'from_step_id'   => (int) ($e['from_step_id'] ?? 0),
                'to_step_id'     => (int) ($e['to_step_id'] ?? 0),
                'condition_expr' => isset($e['condition_expr']) ? (string) $e['condition_expr'] : null,
            ];
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/WorkflowEdgeService.php';
        $result = (new WorkflowEdgeService())->replaceEdges($id, $edges);

        return $this->renderJson($result, $result['ok'] ? 200 : 422);
    }

    protected function renderJson(array $body, int $status = 200)
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($body));
    }

    // =========================================================================
    // Spectrum#B — install seed pack
    // =========================================================================

    // =========================================================================
    // Spectrum Phase C — compliance dashboard, chain rules, CSV export
    // =========================================================================

    public function executeSpectrumDashboard($request)
    {
        $this->requireAdmin();
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
        $overdueDays = (int) $request->getParameter('overdue_days', 30);
        $svc = new SpectrumComplianceService();
        $this->heatmap = $svc->heatmap('information_object', $overdueDays);
        $this->overdueDays = $overdueDays;
        $this->statuses = SpectrumComplianceService::STATUSES;
    }

    public function executeSpectrumExportCsv($request)
    {
        $this->requireAdmin();
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
        $overdueDays = (int) $request->getParameter('overdue_days', 30);
        $svc = new SpectrumComplianceService();
        $heatmap = $svc->heatmap('information_object', $overdueDays);

        $filename = 'spectrum_compliance_'.date('Y-m-d').'.csv';
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');

        $body = '';
        $body .= "procedure_code,procedure,total_objects,not_started,in_progress,completed,overdue,rejected,percent_completed\n";
        foreach ($heatmap as $code => $row) {
            $body .= sprintf("%s,\"%s\",%d,%d,%d,%d,%d,%d,%.1f\n",
                $code, str_replace('"', '""', $row['label']),
                $row['total_objects'],
                $row['totals']['not_started'], $row['totals']['in_progress'], $row['totals']['completed'],
                $row['totals']['overdue'], $row['totals']['rejected'],
                $row['percent_completed']
            );
        }
        return $this->renderText($body);
    }

    public function executeSpectrumChain($request)
    {
        $this->requireAdmin();
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';
        $svc = new SpectrumComplianceService();
        $this->rules = $svc->getChainRules();
        $this->procedures = SpectrumProcedureCatalog::all();
    }

    public function executeSpectrumChainSave($request)
    {
        $this->requireAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect('workflow/spectrumChain');
        }
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
        $svc = new SpectrumComplianceService();
        try {
            $svc->saveChainRule([
                'id'             => $request->getParameter('id'),
                'from_procedure' => $request->getParameter('from_procedure'),
                'to_procedure'   => $request->getParameter('to_procedure'),
                'trigger_event'  => $request->getParameter('trigger_event', 'on_complete'),
                'is_active'      => $request->getParameter('is_active'),
                'notes'          => $request->getParameter('notes'),
            ]);
            $this->getUser()->setFlash('notice', 'Chain rule saved');
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }
        $this->redirect('workflow/spectrumChain');
    }

    public function executeSpectrumChainDelete($request)
    {
        $this->requireAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect('workflow/spectrumChain');
        }
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
        $id = (int) $request->getParameter('id', 0);
        if ($id > 0) {
            (new SpectrumComplianceService())->deleteChainRule($id);
            $this->getUser()->setFlash('notice', 'Chain rule deleted');
        }
        $this->redirect('workflow/spectrumChain');
    }

    public function executeInstallSpectrumPack($request)
    {
        $this->requireAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect('workflow/admin');
        }

        $overwrite = (bool) $request->getParameter('overwrite');
        $cmd = sprintf(
            'cd %s && %s symfony workflow:seed-spectrum %s 2>&1',
            escapeshellarg(sfConfig::get('sf_root_dir')),
            escapeshellcmd(PHP_BINARY),
            $overwrite ? '--overwrite' : ''
        );
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $tail = trim(implode("\n", array_slice($output, -3)));
        if ($returnCode === 0) {
            $this->getUser()->setFlash('notice', 'Spectrum procedure pack installed. '.$tail);
        } else {
            $this->getUser()->setFlash('error', 'Install failed (exit '.$returnCode.'): '.$tail);
        }
        $this->redirect('workflow/admin');
    }
}
