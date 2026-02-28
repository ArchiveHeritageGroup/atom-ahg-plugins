<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Workflow Event Service
 *
 * Centralized event emitter for all workflow actions.
 * Writes to ahg_workflow_history with optional correlation IDs for bulk operations.
 * Optionally logs to ahgAuditTrailPlugin for compliance.
 *
 * @version 2.0.0
 */
class WorkflowEventService
{
    // Extended action types for V2.0
    public const ACTION_STARTED = 'started';
    public const ACTION_CLAIMED = 'claimed';
    public const ACTION_RELEASED = 'released';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_RETURNED = 'returned';
    public const ACTION_ESCALATED = 'escalated';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_COMMENT = 'comment';
    public const ACTION_REASSIGNED = 'reassigned';

    // V2.0 new action types
    public const ACTION_NOTE_ADDED = 'note_added';
    public const ACTION_ATTACHMENT_ADDED = 'attachment_added';
    public const ACTION_ATTACHMENT_REMOVED = 'attachment_removed';
    public const ACTION_RIGHTS_DECISION = 'rights_decision';
    public const ACTION_PUBLISH = 'publish';
    public const ACTION_UNPUBLISH = 'unpublish';
    public const ACTION_PRIORITY_CHANGED = 'priority_changed';
    public const ACTION_DUE_DATE_CHANGED = 'due_date_changed';
    public const ACTION_QUEUE_CHANGED = 'queue_changed';
    public const ACTION_SLA_WARNING = 'sla_warning';
    public const ACTION_SLA_BREACHED = 'sla_breached';

    /** Current correlation ID for bulk operations */
    private ?string $correlationId = null;

    /**
     * Start a bulk operation — generates a shared correlation ID.
     */
    public function startBulkOperation(): string
    {
        $this->correlationId = $this->generateCorrelationId();
        return $this->correlationId;
    }

    /**
     * End the current bulk operation.
     */
    public function endBulkOperation(): void
    {
        $this->correlationId = null;
    }

    /**
     * Get or set the current correlation ID.
     */
    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function setCorrelationId(?string $id): void
    {
        $this->correlationId = $id;
    }

    /**
     * Validate that an action code exists in ahg_dropdown.
     * Caches valid codes for the request lifecycle.
     */
    private ?array $validActions = null;

    public function isValidAction(string $action): bool
    {
        if ($this->validActions === null) {
            $this->validActions = DB::table('ahg_dropdown')
                ->where('taxonomy', 'workflow_history_action')
                ->where('is_active', 1)
                ->pluck('code')
                ->toArray();
        }
        return in_array($action, $this->validActions);
    }

    /**
     * Get action metadata (color, icon, label) from ahg_dropdown.
     */
    public function getActionMeta(string $action): ?object
    {
        return DB::table('ahg_dropdown')
            ->where('taxonomy', 'workflow_history_action')
            ->where('code', $action)
            ->select('label', 'color', 'icon')
            ->first();
    }

    /**
     * Get all valid action codes with metadata.
     */
    public function getAllActions(): array
    {
        return DB::table('ahg_dropdown')
            ->where('taxonomy', 'workflow_history_action')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Emit a workflow event.
     *
     * Action codes are validated against ahg_dropdown taxonomy 'workflow_history_action'.
     *
     * @param string      $action      Event action type (must be a valid code in ahg_dropdown)
     * @param array       $context     Event context data
     * @return int History record ID
     */
    public function emit(string $action, array $context = []): int
    {
        $data = [
            'task_id' => $context['task_id'] ?? null,
            'workflow_id' => $context['workflow_id'] ?? 0,
            'workflow_step_id' => $context['step_id'] ?? null,
            'object_id' => $context['object_id'] ?? 0,
            'object_type' => $context['object_type'] ?? 'information_object',
            'action' => $action,
            'from_status' => $context['from_status'] ?? null,
            'to_status' => $context['to_status'] ?? null,
            'performed_by' => $context['performed_by'] ?? 0,
            'performed_at' => date('Y-m-d H:i:s'),
            'comment' => $context['comment'] ?? null,
            'metadata' => isset($context['metadata']) ? json_encode($context['metadata']) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            'correlation_id' => $this->correlationId ?? ($context['correlation_id'] ?? null),
        ];

        $historyId = DB::table('ahg_workflow_history')->insertGetId($data);

        // Optionally log to audit trail
        if (!empty($context['audit']) || $this->isAuditableAction($action)) {
            $this->logToAuditTrail($action, $context);
        }

        return $historyId;
    }

    /**
     * Emit a note-added event.
     */
    public function emitNote(int $taskId, int $objectId, int $userId, string $note, array $extra = []): int
    {
        return $this->emit(self::ACTION_NOTE_ADDED, array_merge([
            'task_id' => $taskId,
            'object_id' => $objectId,
            'performed_by' => $userId,
            'comment' => $note,
        ], $extra));
    }

    /**
     * Emit a priority-changed event.
     */
    public function emitPriorityChange(int $taskId, int $objectId, int $userId, string $oldPriority, string $newPriority, array $extra = []): int
    {
        return $this->emit(self::ACTION_PRIORITY_CHANGED, array_merge([
            'task_id' => $taskId,
            'object_id' => $objectId,
            'performed_by' => $userId,
            'metadata' => ['old_priority' => $oldPriority, 'new_priority' => $newPriority],
            'comment' => "Priority changed from {$oldPriority} to {$newPriority}",
        ], $extra));
    }

    /**
     * Emit a reassignment event.
     */
    public function emitReassignment(int $taskId, int $objectId, int $performedBy, ?int $fromUserId, int $toUserId, array $extra = []): int
    {
        return $this->emit(self::ACTION_REASSIGNED, array_merge([
            'task_id' => $taskId,
            'object_id' => $objectId,
            'performed_by' => $performedBy,
            'metadata' => ['from_user_id' => $fromUserId, 'to_user_id' => $toUserId],
        ], $extra));
    }

    /**
     * Emit a due-date-changed event.
     */
    public function emitDueDateChange(int $taskId, int $objectId, int $userId, ?string $oldDate, ?string $newDate, array $extra = []): int
    {
        return $this->emit(self::ACTION_DUE_DATE_CHANGED, array_merge([
            'task_id' => $taskId,
            'object_id' => $objectId,
            'performed_by' => $userId,
            'metadata' => ['old_due_date' => $oldDate, 'new_due_date' => $newDate],
            'comment' => "Due date changed to {$newDate}",
        ], $extra));
    }

    /**
     * Get timeline events for an object (ordered, with optional filters).
     *
     * @param int         $objectId
     * @param array       $filters   type, date_from, date_to, action
     * @param int         $limit
     * @return array
     */
    public function getTimeline(int $objectId, array $filters = [], int $limit = 200): array
    {
        $query = DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('h.object_id', $objectId)
            ->select(
                'h.id',
                'h.task_id',
                'h.workflow_id',
                'h.workflow_step_id',
                'h.action',
                'h.from_status',
                'h.to_status',
                'h.performed_by',
                'h.performed_at',
                'h.comment',
                'h.metadata',
                'h.correlation_id',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as performer_name')
            )
            ->orderByDesc('h.performed_at')
            ->limit($limit);

        // Filters
        if (!empty($filters['action'])) {
            $query->where('h.action', $filters['action']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('h.performed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('h.performed_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['type'])) {
            $typeActions = $this->getActionsByCategory($filters['type']);
            if (!empty($typeActions)) {
                $query->whereIn('h.action', $typeActions);
            }
        }

        return $query->get()->toArray();
    }

    /**
     * Get events by correlation ID (all events in a bulk operation).
     */
    public function getByCorrelation(string $correlationId): array
    {
        return DB::table('ahg_workflow_history')
            ->where('correlation_id', $correlationId)
            ->orderBy('performed_at')
            ->get()
            ->toArray();
    }

    /**
     * Export timeline as CSV.
     */
    public function exportTimelineCsv(int $objectId, array $filters = []): string
    {
        $events = $this->getTimeline($objectId, $filters, 10000);

        $csv = "id,action,from_status,to_status,performer,performed_at,comment,correlation_id\n";
        foreach ($events as $event) {
            $csv .= sprintf(
                "%d,%s,%s,%s,\"%s\",%s,\"%s\",%s\n",
                $event->id,
                $event->action,
                $event->from_status ?? '',
                $event->to_status ?? '',
                str_replace('"', '""', $event->performer_name ?? ''),
                $event->performed_at,
                str_replace('"', '""', $event->comment ?? ''),
                $event->correlation_id ?? ''
            );
        }

        return $csv;
    }

    /**
     * Map category names to action types.
     */
    private function getActionsByCategory(string $category): array
    {
        return match ($category) {
            'workflow' => [
                self::ACTION_STARTED, self::ACTION_CLAIMED, self::ACTION_RELEASED,
                self::ACTION_APPROVED, self::ACTION_REJECTED, self::ACTION_RETURNED,
                self::ACTION_ESCALATED, self::ACTION_CANCELLED, self::ACTION_COMPLETED,
                self::ACTION_REASSIGNED, self::ACTION_QUEUE_CHANGED,
            ],
            'notes' => [
                self::ACTION_NOTE_ADDED, self::ACTION_COMMENT,
            ],
            'system' => [
                self::ACTION_SLA_WARNING, self::ACTION_SLA_BREACHED,
                self::ACTION_PUBLISH, self::ACTION_UNPUBLISH,
            ],
            'rights' => [
                self::ACTION_RIGHTS_DECISION,
            ],
            'attachments' => [
                self::ACTION_ATTACHMENT_ADDED, self::ACTION_ATTACHMENT_REMOVED,
            ],
            default => [],
        };
    }

    /**
     * Check if an action should always be audited.
     */
    private function isAuditableAction(string $action): bool
    {
        return in_array($action, [
            self::ACTION_APPROVED,
            self::ACTION_REJECTED,
            self::ACTION_PUBLISH,
            self::ACTION_UNPUBLISH,
            self::ACTION_RIGHTS_DECISION,
            self::ACTION_SLA_BREACHED,
        ]);
    }

    /**
     * Log to ahgAuditTrailPlugin if available.
     */
    private function logToAuditTrail(string $action, array $context): void
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_audit_log'");
            if (empty($exists)) {
                return;
            }

            DB::table('ahg_audit_log')->insert([
                'action' => "workflow:{$action}",
                'entity_type' => 'workflow_task',
                'entity_id' => $context['task_id'] ?? $context['object_id'] ?? 0,
                'user_id' => $context['performed_by'] ?? 0,
                'description' => $context['comment'] ?? "Workflow event: {$action}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Audit trail not available — silently ignore
        }
    }

    /**
     * Generate a UUID v4 correlation ID.
     */
    private function generateCorrelationId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
