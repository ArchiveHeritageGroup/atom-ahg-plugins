<?php

use Illuminate\Database\Capsule\Manager as DB;

class WorkflowService
{
    // =========================================================================
    // WORKFLOW DEFINITIONS
    // =========================================================================

    /**
     * Get all workflows, optionally filtered.
     */
    public function getWorkflows(array $filters = []): array
    {
        $query = DB::table('ahg_workflow');

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['scope_type'])) {
            $query->where('scope_type', $filters['scope_type']);
        }
        if (!empty($filters['scope_id'])) {
            $query->where('scope_id', $filters['scope_id']);
        }

        return $query->orderBy('name')->get()->toArray();
    }

    /**
     * Get a single workflow by ID with its steps.
     */
    public function getWorkflow(int $id): ?object
    {
        $workflow = DB::table('ahg_workflow')->where('id', $id)->first();
        if ($workflow) {
            $workflow->steps = $this->getWorkflowSteps($id);
        }
        return $workflow;
    }

    /**
     * Get workflow applicable to an object based on scope hierarchy.
     * Checks: collection -> repository -> global (in that order)
     */
    public function getApplicableWorkflow(int $objectId, string $objectType = 'information_object'): ?object
    {
        // Get object info to determine scope
        $object = DB::table('information_object')
            ->where('id', $objectId)
            ->select('id', 'repository_id', 'parent_id', 'lft', 'rgt')
            ->first();

        if (!$object) {
            return null;
        }

        // Check for collection-level workflow (parent hierarchy)
        if ($object->parent_id) {
            $ancestors = DB::table('information_object')
                ->where('lft', '<', $object->lft)
                ->where('rgt', '>', $object->rgt)
                ->orderBy('lft', 'desc')
                ->pluck('id')
                ->toArray();

            foreach ($ancestors as $ancestorId) {
                $workflow = DB::table('ahg_workflow')
                    ->where('scope_type', 'collection')
                    ->where('scope_id', $ancestorId)
                    ->where('is_active', 1)
                    ->where('applies_to', $objectType)
                    ->first();
                if ($workflow) {
                    $workflow->steps = $this->getWorkflowSteps($workflow->id);
                    return $workflow;
                }
            }
        }

        // Check for repository-level workflow
        if ($object->repository_id) {
            $workflow = DB::table('ahg_workflow')
                ->where('scope_type', 'repository')
                ->where('scope_id', $object->repository_id)
                ->where('is_active', 1)
                ->where('applies_to', $objectType)
                ->first();
            if ($workflow) {
                $workflow->steps = $this->getWorkflowSteps($workflow->id);
                return $workflow;
            }
        }

        // Fall back to global default workflow
        $workflow = DB::table('ahg_workflow')
            ->where('scope_type', 'global')
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->where('applies_to', $objectType)
            ->first();

        if ($workflow) {
            $workflow->steps = $this->getWorkflowSteps($workflow->id);
        }

        return $workflow;
    }

    /**
     * Create a new workflow.
     */
    public function createWorkflow(array $data): int
    {
        $workflowId = DB::table('ahg_workflow')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'scope_type' => $data['scope_type'] ?? 'global',
            'scope_id' => $data['scope_id'] ?? null,
            'trigger_event' => $data['trigger_event'] ?? 'submit',
            'applies_to' => $data['applies_to'] ?? 'information_object',
            'is_active' => $data['is_active'] ?? 1,
            'is_default' => $data['is_default'] ?? 0,
            'require_all_steps' => $data['require_all_steps'] ?? 1,
            'allow_parallel' => $data['allow_parallel'] ?? 0,
            'auto_archive_days' => $data['auto_archive_days'] ?? null,
            'notification_enabled' => $data['notification_enabled'] ?? 1,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit('create', 'Workflow', $workflowId, [], $data, $data['name']);
        return $workflowId;
    }

    /**
     * Update a workflow.
     */
    public function updateWorkflow(int $id, array $data): bool
    {
        $oldValues = (array) (DB::table('ahg_workflow')->where('id', $id)->first() ?? []);
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = DB::table('ahg_workflow')->where('id', $id)->update($data) > 0;

        if ($result) {
            $newValues = (array) (DB::table('ahg_workflow')->where('id', $id)->first() ?? []);
            $this->logAudit('update', 'Workflow', $id, $oldValues, $newValues, $newValues['name'] ?? '');
        }

        return $result;
    }

    /**
     * Delete a workflow (soft delete by deactivating).
     */
    public function deleteWorkflow(int $id): bool
    {
        // Check for active tasks
        $activeTasks = DB::table('ahg_workflow_task')
            ->where('workflow_id', $id)
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
            ->count();

        if ($activeTasks > 0) {
            throw new Exception("Cannot delete workflow with {$activeTasks} active tasks");
        }

        return DB::table('ahg_workflow')->where('id', $id)->update([
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]) > 0;
    }

    // =========================================================================
    // WORKFLOW STEPS
    // =========================================================================

    /**
     * Get steps for a workflow.
     */
    public function getWorkflowSteps(int $workflowId): array
    {
        return DB::table('ahg_workflow_step')
            ->where('workflow_id', $workflowId)
            ->where('is_active', 1)
            ->orderBy('step_order')
            ->get()
            ->toArray();
    }

    /**
     * Get a single step.
     */
    public function getStep(int $id): ?object
    {
        return DB::table('ahg_workflow_step')->where('id', $id)->first();
    }

    /**
     * Add a step to a workflow.
     */
    public function addStep(int $workflowId, array $data): int
    {
        // Get next order
        $maxOrder = DB::table('ahg_workflow_step')
            ->where('workflow_id', $workflowId)
            ->max('step_order') ?? 0;

        return DB::table('ahg_workflow_step')->insertGetId([
            'workflow_id' => $workflowId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'step_order' => $data['step_order'] ?? ($maxOrder + 1),
            'step_type' => $data['step_type'] ?? 'review',
            'action_required' => $data['action_required'] ?? 'approve_reject',
            'required_role_id' => $data['required_role_id'] ?? null,
            'required_clearance_level' => $data['required_clearance_level'] ?? null,
            'allowed_group_ids' => isset($data['allowed_group_ids']) ? json_encode($data['allowed_group_ids']) : null,
            'allowed_user_ids' => isset($data['allowed_user_ids']) ? json_encode($data['allowed_user_ids']) : null,
            'pool_enabled' => $data['pool_enabled'] ?? 1,
            'auto_assign_user_id' => $data['auto_assign_user_id'] ?? null,
            'escalation_days' => $data['escalation_days'] ?? null,
            'escalation_user_id' => $data['escalation_user_id'] ?? null,
            'notification_template' => $data['notification_template'] ?? 'default',
            'instructions' => $data['instructions'] ?? null,
            'checklist' => isset($data['checklist']) ? json_encode($data['checklist']) : null,
            'is_optional' => $data['is_optional'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a step.
     */
    public function updateStep(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (isset($data['allowed_group_ids']) && is_array($data['allowed_group_ids'])) {
            $data['allowed_group_ids'] = json_encode($data['allowed_group_ids']);
        }
        if (isset($data['allowed_user_ids']) && is_array($data['allowed_user_ids'])) {
            $data['allowed_user_ids'] = json_encode($data['allowed_user_ids']);
        }
        if (isset($data['checklist']) && is_array($data['checklist'])) {
            $data['checklist'] = json_encode($data['checklist']);
        }
        return DB::table('ahg_workflow_step')->where('id', $id)->update($data) > 0;
    }

    /**
     * Delete a step.
     */
    public function deleteStep(int $id): bool
    {
        // Check for active tasks using this step
        $activeTasks = DB::table('ahg_workflow_task')
            ->where('workflow_step_id', $id)
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
            ->count();

        if ($activeTasks > 0) {
            throw new Exception("Cannot delete step with {$activeTasks} active tasks");
        }

        return DB::table('ahg_workflow_step')->where('id', $id)->delete() > 0;
    }

    /**
     * Reorder steps.
     */
    public function reorderSteps(int $workflowId, array $stepIds): bool
    {
        foreach ($stepIds as $order => $stepId) {
            DB::table('ahg_workflow_step')
                ->where('id', $stepId)
                ->where('workflow_id', $workflowId)
                ->update(['step_order' => $order + 1]);
        }
        return true;
    }

    // =========================================================================
    // WORKFLOW TASKS
    // =========================================================================

    /**
     * Start a workflow for an object.
     */
    public function startWorkflow(int $objectId, int $submittedBy, string $objectType = 'information_object', ?int $workflowId = null): ?int
    {
        // Get applicable workflow
        $workflow = $workflowId
            ? $this->getWorkflow($workflowId)
            : $this->getApplicableWorkflow($objectId, $objectType);

        if (!$workflow || empty($workflow->steps)) {
            return null;
        }

        // Check if object is already in workflow
        $existingTask = DB::table('ahg_workflow_task')
            ->where('object_id', $objectId)
            ->where('object_type', $objectType)
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
            ->first();

        if ($existingTask) {
            throw new Exception('Object is already in an active workflow');
        }

        // Get first step
        $firstStep = $workflow->steps[0];

        // Create task for first step
        $taskId = DB::table('ahg_workflow_task')->insertGetId([
            'workflow_id' => $workflow->id,
            'workflow_step_id' => $firstStep->id,
            'object_id' => $objectId,
            'object_type' => $objectType,
            'status' => $firstStep->auto_assign_user_id ? 'claimed' : 'pending',
            'priority' => 'normal',
            'submitted_by' => $submittedBy,
            'assigned_to' => $firstStep->auto_assign_user_id ?? null,
            'claimed_at' => $firstStep->auto_assign_user_id ? date('Y-m-d H:i:s') : null,
            'due_date' => $firstStep->escalation_days
                ? date('Y-m-d', strtotime("+{$firstStep->escalation_days} days"))
                : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Log history
        $this->addHistory($taskId, $workflow->id, $firstStep->id, $objectId, $objectType, 'started', null, 'pending', $submittedBy, 'Workflow started');

        // Send notification
        if ($workflow->notification_enabled) {
            $this->sendTaskNotification($taskId, 'task_assigned');
        }

        return $taskId;
    }

    /**
     * Get task by ID with full details.
     */
    public function getTask(int $id): ?object
    {
        $task = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->where('t.id', $id)
            ->select(
                't.*',
                'w.name as workflow_name',
                's.name as step_name',
                's.step_type',
                's.action_required',
                's.instructions',
                's.checklist'
            )
            ->first();

        if ($task) {
            // Get object info
            if ($task->object_type === 'information_object') {
                $task->object = DB::table('information_object as io')
                    ->leftJoin('information_object_i18n as ioi', function ($join) {
                        $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                    })
                    ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
                    ->where('io.id', $task->object_id)
                    ->select('io.id', 'io.identifier', 'ioi.title', 'slug.slug')
                    ->first();
            }

            // Get user info
            if ($task->assigned_to) {
                $task->assigned_user = DB::table('user')
                    ->where('id', $task->assigned_to)
                    ->select('id', 'username', 'email')
                    ->first();
            }

            $task->submitted_user = DB::table('user')
                ->where('id', $task->submitted_by)
                ->select('id', 'username', 'email')
                ->first();

            // Get history
            $task->history = $this->getTaskHistory($id);
        }

        return $task;
    }

    /**
     * Get tasks for pool (unclaimed, matching user's role/clearance).
     */
    public function getPoolTasks(int $userId): array
    {
        $userInfo = $this->getUserRoleAndClearance($userId);

        $query = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('t.status', 'pending')
            ->whereNull('t.assigned_to')
            ->where('s.pool_enabled', 1);

        // Filter by role if required
        $query->where(function ($q) use ($userInfo) {
            $q->whereNull('s.required_role_id')
              ->orWhereIn('s.required_role_id', $userInfo['roles']);
        });

        // Filter by clearance if required
        $query->where(function ($q) use ($userInfo) {
            $q->whereNull('s.required_clearance_level')
              ->orWhere('s.required_clearance_level', '<=', $userInfo['clearance_level']);
        });

        return $query->select(
            't.*',
            'w.name as workflow_name',
            's.name as step_name',
            's.step_type',
            'ioi.title as object_title'
        )
            ->orderBy('t.priority', 'desc')
            ->orderBy('t.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get tasks assigned to a user.
     */
    public function getMyTasks(int $userId, ?string $status = null): array
    {
        $query = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('t.assigned_to', $userId);

        if ($status) {
            $query->where('t.status', $status);
        } else {
            $query->whereNotIn('t.status', ['approved', 'rejected', 'cancelled']);
        }

        return $query->select(
            't.*',
            'w.name as workflow_name',
            's.name as step_name',
            's.step_type',
            'ioi.title as object_title'
        )
            ->orderBy('t.priority', 'desc')
            ->orderBy('t.due_date')
            ->orderBy('t.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Claim a task from the pool.
     */
    public function claimTask(int $taskId, int $userId): bool
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task || $task->status !== 'pending' || $task->assigned_to !== null) {
            return false;
        }

        // Verify user can claim this task
        if (!$this->canUserClaimTask($userId, $task->workflow_step_id)) {
            throw new Exception('You do not have permission to claim this task');
        }

        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
            'status' => 'claimed',
            'assigned_to' => $userId,
            'claimed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addHistory($taskId, $task->workflow_id, $task->workflow_step_id, $task->object_id, $task->object_type, 'claimed', 'pending', 'claimed', $userId);

        return true;
    }

    /**
     * Release a claimed task back to pool.
     */
    public function releaseTask(int $taskId, int $userId, ?string $comment = null): bool
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task || $task->assigned_to !== $userId) {
            return false;
        }

        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
            'status' => 'pending',
            'assigned_to' => null,
            'claimed_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addHistory($taskId, $task->workflow_id, $task->workflow_step_id, $task->object_id, $task->object_type, 'released', 'claimed', 'pending', $userId, $comment);

        return true;
    }

    /**
     * Approve a task (move to next step or complete workflow).
     */
    public function approveTask(int $taskId, int $userId, ?string $comment = null, ?array $checklistCompleted = null): bool
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task || $task->assigned_to !== $userId || !in_array($task->status, ['claimed', 'in_progress'])) {
            return false;
        }

        $workflow = $this->getWorkflow($task->workflow_id);
        $currentStep = $this->getStep($task->workflow_step_id);

        // Update current task
        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
            'status' => 'approved',
            'decision' => 'approved',
            'decision_comment' => $comment,
            'decision_at' => date('Y-m-d H:i:s'),
            'decision_by' => $userId,
            'checklist_completed' => $checklistCompleted ? json_encode($checklistCompleted) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addHistory($taskId, $task->workflow_id, $task->workflow_step_id, $task->object_id, $task->object_type, 'approved', $task->status, 'approved', $userId, $comment);

        // Find next step
        $nextStep = DB::table('ahg_workflow_step')
            ->where('workflow_id', $task->workflow_id)
            ->where('step_order', '>', $currentStep->step_order)
            ->where('is_active', 1)
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            // Create task for next step
            $newTaskId = DB::table('ahg_workflow_task')->insertGetId([
                'workflow_id' => $task->workflow_id,
                'workflow_step_id' => $nextStep->id,
                'object_id' => $task->object_id,
                'object_type' => $task->object_type,
                'status' => $nextStep->auto_assign_user_id ? 'claimed' : 'pending',
                'priority' => $task->priority,
                'submitted_by' => $task->submitted_by,
                'assigned_to' => $nextStep->auto_assign_user_id ?? null,
                'claimed_at' => $nextStep->auto_assign_user_id ? date('Y-m-d H:i:s') : null,
                'due_date' => $nextStep->escalation_days
                    ? date('Y-m-d', strtotime("+{$nextStep->escalation_days} days"))
                    : null,
                'previous_task_id' => $taskId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($workflow->notification_enabled) {
                $this->sendTaskNotification($newTaskId, 'task_assigned');
            }
        } else {
            // Workflow complete - mark object as published/approved
            $this->addHistory($taskId, $task->workflow_id, null, $task->object_id, $task->object_type, 'completed', 'approved', 'completed', $userId, 'Workflow completed');

            if ($workflow->notification_enabled) {
                $this->sendCompletionNotification($task->submitted_by, $task->object_id, $task->object_type);
            }
        }

        return true;
    }

    /**
     * Reject a task.
     */
    public function rejectTask(int $taskId, int $userId, string $comment): bool
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task || $task->assigned_to !== $userId || !in_array($task->status, ['claimed', 'in_progress'])) {
            return false;
        }

        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
            'status' => 'rejected',
            'decision' => 'rejected',
            'decision_comment' => $comment,
            'decision_at' => date('Y-m-d H:i:s'),
            'decision_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addHistory($taskId, $task->workflow_id, $task->workflow_step_id, $task->object_id, $task->object_type, 'rejected', $task->status, 'rejected', $userId, $comment);

        // Notify submitter
        $workflow = $this->getWorkflow($task->workflow_id);
        if ($workflow && $workflow->notification_enabled) {
            $this->sendTaskNotification($taskId, 'task_rejected');
        }

        return true;
    }

    /**
     * Return task to submitter for revision.
     */
    public function returnTask(int $taskId, int $userId, string $comment): bool
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task || $task->assigned_to !== $userId || !in_array($task->status, ['claimed', 'in_progress'])) {
            return false;
        }

        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
            'status' => 'returned',
            'decision' => 'returned',
            'decision_comment' => $comment,
            'decision_at' => date('Y-m-d H:i:s'),
            'decision_by' => $userId,
            'retry_count' => DB::raw('retry_count + 1'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addHistory($taskId, $task->workflow_id, $task->workflow_step_id, $task->object_id, $task->object_type, 'returned', $task->status, 'returned', $userId, $comment);

        // Notify submitter
        $workflow = $this->getWorkflow($task->workflow_id);
        if ($workflow && $workflow->notification_enabled) {
            $this->sendTaskNotification($taskId, 'task_returned');
        }

        return true;
    }

    /**
     * Resubmit a returned task.
     */
    public function resubmitTask(int $taskId, int $userId, ?string $comment = null): bool
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task || $task->submitted_by !== $userId || $task->status !== 'returned') {
            return false;
        }

        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
            'status' => 'pending',
            'assigned_to' => null,
            'claimed_at' => null,
            'decision' => 'pending',
            'decision_comment' => null,
            'decision_at' => null,
            'decision_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->addHistory($taskId, $task->workflow_id, $task->workflow_step_id, $task->object_id, $task->object_type, 'started', 'returned', 'pending', $userId, $comment ?? 'Resubmitted after revision');

        return true;
    }

    // =========================================================================
    // HISTORY & NOTIFICATIONS
    // =========================================================================

    /**
     * Get history for a task.
     */
    public function getTaskHistory(int $taskId): array
    {
        return DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->where('h.task_id', $taskId)
            ->select('h.*', 'u.username', 'u.email')
            ->orderBy('h.performed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get history for an object.
     */
    public function getObjectHistory(int $objectId, string $objectType = 'information_object'): array
    {
        return DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->leftJoin('ahg_workflow as w', 'h.workflow_id', '=', 'w.id')
            ->leftJoin('ahg_workflow_step as s', 'h.workflow_step_id', '=', 's.id')
            ->where('h.object_id', $objectId)
            ->where('h.object_type', $objectType)
            ->select('h.*', 'u.username', 'u.email', 'w.name as workflow_name', 's.name as step_name')
            ->orderBy('h.performed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Add history entry.
     */
    protected function addHistory(
        ?int $taskId,
        int $workflowId,
        ?int $stepId,
        int $objectId,
        string $objectType,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        int $performedBy,
        ?string $comment = null
    ): int {
        return DB::table('ahg_workflow_history')->insertGetId([
            'task_id' => $taskId,
            'workflow_id' => $workflowId,
            'workflow_step_id' => $stepId,
            'object_id' => $objectId,
            'object_type' => $objectType,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => $performedBy,
            'performed_at' => date('Y-m-d H:i:s'),
            'comment' => $comment,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
        ]);
    }

    /**
     * Queue a notification.
     */
    protected function queueNotification(int $userId, string $type, string $subject, string $body, ?int $taskId = null): int
    {
        return DB::table('ahg_workflow_notification')->insertGetId([
            'task_id' => $taskId,
            'user_id' => $userId,
            'notification_type' => $type,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Send task notification.
     */
    protected function sendTaskNotification(int $taskId, string $type): void
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            return;
        }

        $objectTitle = $task->object->title ?? "Object #{$task->object_id}";

        switch ($type) {
            case 'task_assigned':
                $users = $this->getEligibleUsersForStep($task->workflow_step_id);
                foreach ($users as $user) {
                    $this->queueNotification(
                        $user->id,
                        $type,
                        "New workflow task: {$task->step_name}",
                        "A new task is available for '{$objectTitle}' in the {$task->workflow_name} workflow.\n\nStep: {$task->step_name}\n\nPlease review and claim this task.",
                        $taskId
                    );
                }
                break;

            case 'task_rejected':
            case 'task_returned':
                $this->queueNotification(
                    $task->submitted_by,
                    $type,
                    "Workflow task {$type}: {$objectTitle}",
                    "Your submission '{$objectTitle}' has been {$type} in the {$task->workflow_name} workflow.\n\nStep: {$task->step_name}\n\nComment: {$task->decision_comment}",
                    $taskId
                );
                break;
        }
    }

    /**
     * Send workflow completion notification.
     */
    protected function sendCompletionNotification(int $userId, int $objectId, string $objectType): void
    {
        $objectTitle = "Object #{$objectId}";
        if ($objectType === 'information_object') {
            $obj = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->value('title');
            if ($obj) {
                $objectTitle = $obj;
            }
        }

        $this->queueNotification(
            $userId,
            'workflow_completed',
            "Workflow completed: {$objectTitle}",
            "Your submission '{$objectTitle}' has completed the approval workflow and is now approved.",
            null
        );
    }

    /**
     * Process pending notifications (call from cron).
     */
    public function processPendingNotifications(int $limit = 50): array
    {
        $notifications = DB::table('ahg_workflow_notification')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->toArray();

        $results = ['sent' => 0, 'failed' => 0];

        foreach ($notifications as $notification) {
            $user = DB::table('user')->where('id', $notification->user_id)->first();
            if (!$user || !$user->email) {
                DB::table('ahg_workflow_notification')
                    ->where('id', $notification->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'User not found or no email',
                    ]);
                $results['failed']++;
                continue;
            }

            try {
                // Use AtoM's email service if available
                $this->sendEmail($user->email, $notification->subject, $notification->body);

                DB::table('ahg_workflow_notification')
                    ->where('id', $notification->id)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                    ]);
                $results['sent']++;
            } catch (Exception $e) {
                DB::table('ahg_workflow_notification')
                    ->where('id', $notification->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'retry_count' => DB::raw('retry_count + 1'),
                    ]);
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send email using AtoM's mailer.
     */
    protected function sendEmail(string $to, string $subject, string $body): bool
    {
        try {
            $mailer = sfContext::getInstance()->getMailer();
            $message = $mailer->compose(
                sfConfig::get('app_mail_from', 'noreply@example.com'),
                $to,
                $subject,
                $body
            );
            return $mailer->send($message) > 0;
        } catch (Exception $e) {
            error_log("WorkflowService EMAIL ERROR: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // DASHBOARD & STATISTICS
    // =========================================================================

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(?int $userId = null): array
    {
        $stats = [
            'total_workflows' => DB::table('ahg_workflow')->where('is_active', 1)->count(),
            'pending_tasks' => DB::table('ahg_workflow_task')->where('status', 'pending')->count(),
            'claimed_tasks' => DB::table('ahg_workflow_task')->where('status', 'claimed')->count(),
            'completed_today' => DB::table('ahg_workflow_task')
                ->where('status', 'approved')
                ->whereDate('decision_at', date('Y-m-d'))
                ->count(),
            'overdue_tasks' => DB::table('ahg_workflow_task')
                ->whereNotNull('due_date')
                ->where('due_date', '<', date('Y-m-d'))
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count(),
        ];

        if ($userId) {
            $stats['my_tasks'] = DB::table('ahg_workflow_task')
                ->where('assigned_to', $userId)
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count();
            $stats['my_submissions'] = DB::table('ahg_workflow_task')
                ->where('submitted_by', $userId)
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count();
        }

        return $stats;
    }

    /**
     * Get recent activity.
     */
    public function getRecentActivity(int $limit = 20): array
    {
        return DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->leftJoin('ahg_workflow as w', 'h.workflow_id', '=', 'w.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('h.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('h.*', 'u.username', 'w.name as workflow_name', 'ioi.title as object_title')
            ->orderBy('h.performed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if user can claim a task based on step requirements.
     */
    protected function canUserClaimTask(int $userId, int $stepId): bool
    {
        $step = $this->getStep($stepId);
        if (!$step) {
            return false;
        }

        $userInfo = $this->getUserRoleAndClearance($userId);

        // Check role requirement
        if ($step->required_role_id && !in_array($step->required_role_id, $userInfo['roles'])) {
            return false;
        }

        // Check clearance requirement
        if ($step->required_clearance_level && $userInfo['clearance_level'] < $step->required_clearance_level) {
            return false;
        }

        // Check allowed users
        if ($step->allowed_user_ids) {
            $allowedUsers = json_decode($step->allowed_user_ids, true) ?: [];
            if (!empty($allowedUsers) && !in_array($userId, $allowedUsers)) {
                return false;
            }
        }

        // Check allowed groups
        if ($step->allowed_group_ids) {
            $allowedGroups = json_decode($step->allowed_group_ids, true) ?: [];
            if (!empty($allowedGroups)) {
                $userGroups = DB::table('acl_user_group')->where('user_id', $userId)->pluck('group_id')->toArray();
                if (empty(array_intersect($allowedGroups, $userGroups))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get user's role IDs and security clearance level.
     */
    protected function getUserRoleAndClearance(int $userId): array
    {
        // Get roles
        $roles = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        // Get clearance level from ahgSecurityClearancePlugin
        $clearance = DB::table('user_security_clearance')
            ->join('security_classification', 'security_classification.id', '=', 'user_security_clearance.classification_id')
            ->where('user_security_clearance.user_id', $userId)
            ->where('security_classification.active', 1)
            ->orderBy('security_classification.level', 'desc')
            ->select('security_classification.level as clearance_level')
            ->first();

        return [
            'roles' => $roles,
            'clearance_level' => $clearance->clearance_level ?? 0,
        ];
    }

    /**
     * Get users eligible for a step.
     */
    protected function getEligibleUsersForStep(int $stepId): array
    {
        $step = $this->getStep($stepId);
        if (!$step) {
            return [];
        }

        $query = DB::table('user')->where('active', 1);

        // Filter by allowed users if specified
        if ($step->allowed_user_ids) {
            $allowedUsers = json_decode($step->allowed_user_ids, true) ?: [];
            if (!empty($allowedUsers)) {
                $query->whereIn('id', $allowedUsers);
            }
        }

        // Filter by role if required
        if ($step->required_role_id) {
            $userIds = DB::table('acl_user_group')
                ->where('group_id', $step->required_role_id)
                ->pluck('user_id')
                ->toArray();
            $query->whereIn('id', $userIds);
        }

        // Filter by clearance if required
        if ($step->required_clearance_level) {
            $clearedUserIds = DB::table('user_security_clearance')
                ->join('security_classification', 'security_classification.id', '=', 'user_security_clearance.classification_id')
                ->where('security_classification.level', '>=', $step->required_clearance_level)
                ->where('security_classification.active', 1)
                ->pluck('user_security_clearance.user_id')
                ->toArray();
            $query->whereIn('id', $clearedUserIds);
        }

        return $query->select('id', 'email', 'username')->get()->toArray();
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues, ?string $title = null): void
    {
        try {
            $auditServicePath = sfConfig::get('sf_root_dir') . '/plugins/ahgAuditTrailPlugin/lib/Services/AhgAuditService.php';
            if (file_exists($auditServicePath)) {
                require_once $auditServicePath;
            }

            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $changedFields = [];
                foreach ($newValues as $key => $val) {
                    if (($oldValues[$key] ?? null) !== $val) {
                        $changedFields[] = $key;
                    }
                }

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    $entityType,
                    $entityId,
                    [
                        'title' => $title,
                        'module' => 'ahgWorkflowPlugin',
                        'action_name' => $action,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        } catch (Exception $e) {
            error_log("WorkflowService AUDIT ERROR: " . $e->getMessage());
        }
    }
}
