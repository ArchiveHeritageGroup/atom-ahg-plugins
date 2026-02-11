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
                $this->getUser()->setFlash('notice', 'Task approved');
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
            ->where('culture', 'en')
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
        $this->workflows = $this->getService()->getWorkflows();
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
                'created_by' => $this->getCurrentUserId(),
            ];

            $workflowId = $this->getService()->createWorkflow($data);
            $this->getUser()->setFlash('notice', 'Workflow created');
            $this->redirect("workflow/admin/edit/{$workflowId}");
        }

        $this->repositories = $this->getRepositories();
        $this->collections = $this->getCollections();
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
            ];

            $this->getService()->updateWorkflow($workflowId, $data);
            $this->getUser()->setFlash('notice', 'Workflow updated');
            $this->redirect("workflow/admin/edit/{$workflowId}");
        }

        $this->repositories = $this->getRepositories();
        $this->collections = $this->getCollections();
        $this->roles = $this->getRoles();
        $this->clearanceLevels = $this->getClearanceLevels();
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
                'instructions' => $request->getParameter('instructions'),
                'is_optional' => $request->getParameter('is_optional', 0),
            ];

            $this->getService()->addStep($workflowId, $data);
            $this->getUser()->setFlash('notice', 'Step added');
            $this->redirect("workflow/admin/edit/{$workflowId}");
        }

        $this->roles = $this->getRoles();
        $this->clearanceLevels = $this->getClearanceLevels();
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
    // API ENDPOINTS
    // =========================================================================

    public function executeApiStats($request)
    {
        $this->requireAuth();
        $stats = $this->getService()->getDashboardStats($this->getCurrentUserId());
        return $this->renderText(json_encode($stats));
    }

    public function executeApiTasks($request)
    {
        $this->requireAuth();
        $type = $request->getParameter('type', 'my');

        $tasks = $type === 'pool'
            ? $this->getService()->getPoolTasks($this->getCurrentUserId())
            : $this->getService()->getMyTasks($this->getCurrentUserId());

        return $this->renderText(json_encode($tasks));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function getRepositories(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('repository as r')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
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
                $join->on('g.id', '=', 'gi.id')->where('gi.culture', '=', 'en');
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
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
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
}
