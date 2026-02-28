<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Workflow Bulk Operations Service
 *
 * Handles bulk assign, transition, note, and priority changes
 * with correlation IDs for audit grouping, dry-run preview,
 * and per-task validation.
 *
 * @version 2.0.0
 */
class WorkflowBulkService
{
    private WorkflowService $workflowService;
    private WorkflowEventService $eventService;

    public function __construct(WorkflowService $workflowService, WorkflowEventService $eventService)
    {
        $this->workflowService = $workflowService;
        $this->eventService = $eventService;
    }

    // =========================================================================
    // PREVIEW (DRY RUN)
    // =========================================================================

    /**
     * Preview a bulk transition — validates each task without committing.
     *
     * @param int[]  $taskIds
     * @param string $action   approve|reject|return|cancel
     * @param int    $userId
     * @return array Per-task preview results
     */
    public function previewBulkTransition(array $taskIds, string $action, int $userId): array
    {
        $results = [];

        foreach ($taskIds as $taskId) {
            $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();

            if (!$task) {
                $results[] = [
                    'task_id' => $taskId,
                    'can_transition' => false,
                    'reason' => 'Task not found',
                    'current_status' => null,
                    'would_become' => null,
                ];
                continue;
            }

            $validation = $this->validateTransition($task, $action, $userId);

            $results[] = [
                'task_id' => $taskId,
                'object_id' => $task->object_id,
                'object_title' => $this->getObjectTitle($task->object_id, $task->object_type),
                'current_status' => $task->status,
                'priority' => $task->priority,
                'assigned_to' => $task->assigned_to,
                'can_transition' => $validation['valid'],
                'reason' => $validation['reason'],
                'would_become' => $validation['valid'] ? $this->getTargetStatus($action) : null,
            ];
        }

        return $results;
    }

    /**
     * Preview a bulk assignment.
     */
    public function previewBulkAssign(array $taskIds, int $targetUserId): array
    {
        $results = [];

        foreach ($taskIds as $taskId) {
            $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();

            if (!$task) {
                $results[] = [
                    'task_id' => $taskId,
                    'can_assign' => false,
                    'reason' => 'Task not found',
                ];
                continue;
            }

            $canAssign = in_array($task->status, ['pending', 'claimed', 'in_progress', 'returned']);
            $reason = $canAssign ? 'OK' : "Cannot assign task in status '{$task->status}'";

            $results[] = [
                'task_id' => $taskId,
                'object_id' => $task->object_id,
                'current_status' => $task->status,
                'current_assignee' => $task->assigned_to,
                'can_assign' => $canAssign,
                'reason' => $reason,
            ];
        }

        return $results;
    }

    // =========================================================================
    // BULK EXECUTE
    // =========================================================================

    /**
     * Bulk assign tasks to a user.
     *
     * @return array{success: array, failed: array, correlation_id: string}
     */
    public function bulkAssign(array $taskIds, int $targetUserId, int $performedBy): array
    {
        $correlationId = $this->eventService->startBulkOperation();

        $result = ['success' => [], 'failed' => [], 'correlation_id' => $correlationId];

        foreach ($taskIds as $taskId) {
            try {
                $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();

                if (!$task) {
                    $result['failed'][] = ['task_id' => $taskId, 'reason' => 'Task not found'];
                    continue;
                }

                if (!in_array($task->status, ['pending', 'claimed', 'in_progress', 'returned'])) {
                    $result['failed'][] = ['task_id' => $taskId, 'reason' => "Cannot assign in status '{$task->status}'"];
                    continue;
                }

                $this->workflowService->assignToUser($taskId, $targetUserId, $performedBy);
                $result['success'][] = ['task_id' => $taskId, 'status' => 'assigned'];

            } catch (\Exception $e) {
                $result['failed'][] = ['task_id' => $taskId, 'reason' => $e->getMessage()];
            }
        }

        $this->eventService->endBulkOperation();

        return $result;
    }

    /**
     * Bulk transition tasks (approve/reject/return/cancel).
     *
     * @return array{success: array, failed: array, correlation_id: string}
     */
    public function bulkTransition(array $taskIds, string $action, int $userId, ?string $comment = null): array
    {
        $correlationId = $this->eventService->startBulkOperation();

        $result = ['success' => [], 'failed' => [], 'correlation_id' => $correlationId];

        foreach ($taskIds as $taskId) {
            try {
                $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();

                if (!$task) {
                    $result['failed'][] = ['task_id' => $taskId, 'reason' => 'Task not found'];
                    continue;
                }

                $validation = $this->validateTransition($task, $action, $userId);
                if (!$validation['valid']) {
                    $result['failed'][] = ['task_id' => $taskId, 'reason' => $validation['reason']];
                    continue;
                }

                switch ($action) {
                    case 'approve':
                        $this->workflowService->approveTask($taskId, $userId, $comment);
                        break;
                    case 'reject':
                        $this->workflowService->rejectTask($taskId, $userId, $comment ?: 'Bulk rejected');
                        break;
                    case 'return':
                        $this->workflowService->returnTask($taskId, $userId, $comment ?: 'Bulk returned');
                        break;
                    case 'cancel':
                        DB::table('ahg_workflow_task')->where('id', $taskId)->update([
                            'status' => 'cancelled',
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $this->eventService->emit('cancelled', [
                            'task_id' => $taskId,
                            'workflow_id' => $task->workflow_id,
                            'step_id' => $task->workflow_step_id,
                            'object_id' => $task->object_id,
                            'object_type' => $task->object_type,
                            'performed_by' => $userId,
                            'from_status' => $task->status,
                            'to_status' => 'cancelled',
                            'comment' => $comment ?: 'Bulk cancelled',
                        ]);
                        break;
                    default:
                        $result['failed'][] = ['task_id' => $taskId, 'reason' => "Unknown action: {$action}"];
                        continue 2;
                }

                $result['success'][] = [
                    'task_id' => $taskId,
                    'from_status' => $task->status,
                    'to_status' => $this->getTargetStatus($action),
                ];

            } catch (\Exception $e) {
                $result['failed'][] = ['task_id' => $taskId, 'reason' => $e->getMessage()];
            }
        }

        $this->eventService->endBulkOperation();

        return $result;
    }

    /**
     * Bulk add note to tasks.
     *
     * @return array{success: array, failed: array, correlation_id: string}
     */
    public function bulkAddNote(array $taskIds, string $note, int $userId): array
    {
        $correlationId = $this->eventService->startBulkOperation();

        $result = ['success' => [], 'failed' => [], 'correlation_id' => $correlationId];

        foreach ($taskIds as $taskId) {
            try {
                $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();

                if (!$task) {
                    $result['failed'][] = ['task_id' => $taskId, 'reason' => 'Task not found'];
                    continue;
                }

                $this->eventService->emitNote($taskId, $task->object_id, $userId, $note, [
                    'workflow_id' => $task->workflow_id,
                    'step_id' => $task->workflow_step_id,
                    'object_type' => $task->object_type,
                ]);

                $result['success'][] = ['task_id' => $taskId, 'status' => 'note_added'];

            } catch (\Exception $e) {
                $result['failed'][] = ['task_id' => $taskId, 'reason' => $e->getMessage()];
            }
        }

        $this->eventService->endBulkOperation();

        return $result;
    }

    /**
     * Bulk change priority.
     *
     * @return array{success: array, failed: array, correlation_id: string}
     */
    public function bulkChangePriority(array $taskIds, string $newPriority, int $userId): array
    {
        $correlationId = $this->eventService->startBulkOperation();

        $result = ['success' => [], 'failed' => [], 'correlation_id' => $correlationId];

        foreach ($taskIds as $taskId) {
            try {
                $this->workflowService->changePriority($taskId, $newPriority, $userId);
                $result['success'][] = ['task_id' => $taskId, 'new_priority' => $newPriority];
            } catch (\Exception $e) {
                $result['failed'][] = ['task_id' => $taskId, 'reason' => $e->getMessage()];
            }
        }

        $this->eventService->endBulkOperation();

        return $result;
    }

    /**
     * Bulk move to queue.
     *
     * @return array{success: array, failed: array, correlation_id: string}
     */
    public function bulkMoveToQueue(array $taskIds, int $queueId, int $userId): array
    {
        $correlationId = $this->eventService->startBulkOperation();

        $result = ['success' => [], 'failed' => [], 'correlation_id' => $correlationId];

        foreach ($taskIds as $taskId) {
            try {
                $this->workflowService->moveToQueue($taskId, $queueId, $userId);
                $result['success'][] = ['task_id' => $taskId, 'new_queue_id' => $queueId];
            } catch (\Exception $e) {
                $result['failed'][] = ['task_id' => $taskId, 'reason' => $e->getMessage()];
            }
        }

        $this->eventService->endBulkOperation();

        return $result;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Validate whether a transition is allowed for a task.
     */
    private function validateTransition(object $task, string $action, int $userId): array
    {
        // Terminal statuses
        if (in_array($task->status, ['approved', 'rejected', 'cancelled'])) {
            return ['valid' => false, 'reason' => "Task already in terminal status '{$task->status}'"];
        }

        // Check valid transitions per action
        $validStatuses = match ($action) {
            'approve' => ['claimed', 'in_progress'],
            'reject' => ['claimed', 'in_progress'],
            'return' => ['claimed', 'in_progress'],
            'cancel' => ['pending', 'claimed', 'in_progress', 'returned', 'escalated'],
            default => [],
        };

        if (empty($validStatuses)) {
            return ['valid' => false, 'reason' => "Unknown action '{$action}'"];
        }

        if (!in_array($task->status, $validStatuses)) {
            return ['valid' => false, 'reason' => "Cannot {$action} task in status '{$task->status}'"];
        }

        // For approve/reject/return: task must be assigned to user (or user is admin)
        if (in_array($action, ['approve', 'reject', 'return'])) {
            if ($task->assigned_to !== $userId) {
                // Check if user is admin
                $isAdmin = DB::table('acl_user_group')
                    ->where('user_id', $userId)
                    ->where('group_id', 100)
                    ->exists();

                if (!$isAdmin) {
                    return ['valid' => false, 'reason' => 'Task not assigned to you'];
                }
            }
        }

        // Check if checklist is complete (if required)
        if ($action === 'approve') {
            $step = DB::table('ahg_workflow_step')->where('id', $task->workflow_step_id)->first();
            if ($step && $step->checklist) {
                $checklistItems = json_decode($step->checklist, true) ?: [];
                $completed = json_decode($task->checklist_completed ?? '{}', true) ?: [];

                $allComplete = true;
                foreach ($checklistItems as $item) {
                    $itemKey = is_array($item) ? ($item['key'] ?? $item['label'] ?? '') : (string) $item;
                    if (empty($completed[$itemKey])) {
                        $allComplete = false;
                        break;
                    }
                }

                if (!$allComplete) {
                    return ['valid' => false, 'reason' => 'Checklist items not all completed'];
                }
            }
        }

        return ['valid' => true, 'reason' => 'OK'];
    }

    /**
     * Get target status for a transition action.
     */
    private function getTargetStatus(string $action): string
    {
        return match ($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'return' => 'returned',
            'cancel' => 'cancelled',
            default => 'unknown',
        };
    }

    /**
     * Get object title helper.
     */
    private function getObjectTitle(int $objectId, string $objectType): string
    {
        if ($objectType === 'information_object') {
            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('title');
            if ($title) {
                return $title;
            }
        }
        return "Object #{$objectId}";
    }
}
