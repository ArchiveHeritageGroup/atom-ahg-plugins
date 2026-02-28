<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Workflow SLA Service — Complete Implementation
 *
 * Pure-function SLA computation with config-first + DB-override approach.
 * Computes SLA status at render time from policy + timestamps — no stored derived status.
 *
 * Policy resolution order:
 *   1. DB override (ahg_workflow_sla_policy for specific queue+workflow)
 *   2. YAML queue-specific policy (config/sla_policies.yml → queues.{slug})
 *   3. YAML default policy
 *
 * SLA states: on_track, at_risk, overdue, breached
 *   - on_track:  now < at_risk_at
 *   - at_risk:   at_risk_at <= now < due_at
 *   - overdue:   due_at <= now < escalation_at
 *   - breached:  now >= escalation_at
 *
 * @version 2.0.0
 */
class WorkflowSlaService
{
    // SLA status constants
    public const STATUS_ON_TRACK = 'on_track';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_BREACHED = 'breached';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_POLICY = 'no_policy';

    // Badge colors per status
    public const STATUS_COLORS = [
        self::STATUS_ON_TRACK => '#28a745',
        self::STATUS_AT_RISK => '#ffc107',
        self::STATUS_OVERDUE => '#fd7e14',
        self::STATUS_BREACHED => '#dc3545',
        self::STATUS_COMPLETED => '#6c757d',
        self::STATUS_NO_POLICY => '#6c757d',
    ];

    // Badge icons per status
    public const STATUS_ICONS = [
        self::STATUS_ON_TRACK => 'fa-check-circle',
        self::STATUS_AT_RISK => 'fa-exclamation-triangle',
        self::STATUS_OVERDUE => 'fa-clock',
        self::STATUS_BREACHED => 'fa-exclamation-circle',
        self::STATUS_COMPLETED => 'fa-flag-checkered',
        self::STATUS_NO_POLICY => 'fa-minus-circle',
    ];

    private ?array $yamlConfig = null;
    private array $dbPolicyCache = [];

    // =========================================================================
    // POLICY RESOLUTION
    // =========================================================================

    /**
     * Resolve the SLA policy for a task.
     *
     * @param string|null $queueSlug   Queue slug (from ahg_workflow_queue)
     * @param int|null    $workflowId  Workflow ID
     * @param string      $priority    Task priority code
     * @return array Resolved policy with computed durations
     */
    public function resolvePolicy(?string $queueSlug = null, ?int $workflowId = null, string $priority = 'normal'): array
    {
        // 1. Try DB override first
        $dbPolicy = $this->getDbPolicy($queueSlug, $workflowId);
        if ($dbPolicy) {
            $policy = [
                'source' => 'database',
                'policy_id' => $dbPolicy->id,
                'name' => $dbPolicy->name,
                'due_days' => (int) $dbPolicy->due_days,
                'warning_days' => (int) $dbPolicy->warning_days,
                'escalation_days' => (int) $dbPolicy->escalation_days,
                'escalation_user_id' => $dbPolicy->escalation_user_id,
                'escalation_action' => $dbPolicy->escalation_action ?? 'notify_lead',
                'at_risk_threshold' => 0.20,
            ];
        } else {
            // 2. Try YAML queue-specific
            $yaml = $this->loadYamlConfig();
            if ($queueSlug && isset($yaml['queues'][$queueSlug])) {
                $queuePolicy = $yaml['queues'][$queueSlug];
                $policy = [
                    'source' => 'yaml_queue',
                    'policy_id' => null,
                    'name' => ucfirst($queueSlug) . ' Queue SLA',
                    'due_days' => (int) ($queuePolicy['due_days'] ?? 5),
                    'warning_days' => (int) ($queuePolicy['warning_days'] ?? 3),
                    'escalation_days' => (int) ($queuePolicy['escalation_days'] ?? 7),
                    'escalation_user_id' => null,
                    'escalation_action' => $queuePolicy['escalation_action'] ?? 'notify_lead',
                    'at_risk_threshold' => (float) ($yaml['default']['at_risk_threshold'] ?? 0.20),
                ];
            } else {
                // 3. Fall back to YAML default
                $default = $yaml['default'] ?? [];
                $policy = [
                    'source' => 'yaml_default',
                    'policy_id' => null,
                    'name' => 'Default SLA',
                    'due_days' => (int) ($default['due_days'] ?? 5),
                    'warning_days' => (int) ($default['warning_days'] ?? 3),
                    'escalation_days' => (int) ($default['escalation_days'] ?? 7),
                    'escalation_user_id' => null,
                    'escalation_action' => $default['escalation_action'] ?? 'notify_lead',
                    'at_risk_threshold' => (float) ($default['at_risk_threshold'] ?? 0.20),
                ];
            }
        }

        // Apply priority factor
        $factor = $this->getPriorityFactor($priority);
        if ($factor !== 1.0) {
            $policy['due_days'] = max(1, (int) ceil($policy['due_days'] * $factor));
            $policy['warning_days'] = max(1, (int) ceil($policy['warning_days'] * $factor));
            $policy['escalation_days'] = max(1, (int) ceil($policy['escalation_days'] * $factor));
        }

        $policy['priority'] = $priority;
        $policy['priority_factor'] = $factor;

        return $policy;
    }

    // =========================================================================
    // SLA COMPUTATION (PURE FUNCTIONS)
    // =========================================================================

    /**
     * Compute SLA timestamps and current status for a task.
     *
     * @param string      $createdAt    Task created_at (Y-m-d H:i:s)
     * @param array       $policy       Resolved policy from resolvePolicy()
     * @param string|null $manualDueAt  Manual due date override (Y-m-d)
     * @param string|null $completedAt  Task completion timestamp (null if still open)
     * @param string|null $now          Override current time for testing
     * @return array{due_at: string, at_risk_at: string, escalation_at: string, sla_status: string, ...}
     */
    public function compute(
        string $createdAt,
        array $policy,
        ?string $manualDueAt = null,
        ?string $completedAt = null,
        ?string $now = null
    ): array {
        $now = $now ? new \DateTimeImmutable($now) : new \DateTimeImmutable();
        $created = new \DateTimeImmutable($createdAt);
        $yaml = $this->loadYamlConfig();
        $useBusinessDays = !empty($yaml['calendar']['use_business_days']);

        // Compute due date
        if ($manualDueAt) {
            $dueAt = new \DateTimeImmutable($manualDueAt . ' 23:59:59');
        } else {
            $dueAt = $this->addDays($created, $policy['due_days'], $useBusinessDays);
        }

        // Compute at-risk threshold
        // at_risk_at = due_at - warning_days
        $atRiskAt = $this->subtractDays($dueAt, $policy['warning_days'], $useBusinessDays);

        // Escalation date
        $escalationAt = $this->addDays($created, $policy['escalation_days'], $useBusinessDays);

        // Determine SLA status
        if ($completedAt) {
            $completed = new \DateTimeImmutable($completedAt);
            $slaStatus = $completed <= $dueAt ? self::STATUS_COMPLETED : self::STATUS_BREACHED;
        } elseif ($now >= $escalationAt) {
            $slaStatus = self::STATUS_BREACHED;
        } elseif ($now >= $dueAt) {
            $slaStatus = self::STATUS_OVERDUE;
        } elseif ($now >= $atRiskAt) {
            $slaStatus = self::STATUS_AT_RISK;
        } else {
            $slaStatus = self::STATUS_ON_TRACK;
        }

        // Time remaining/elapsed calculations
        $totalSeconds = $dueAt->getTimestamp() - $created->getTimestamp();
        $elapsedSeconds = $now->getTimestamp() - $created->getTimestamp();
        $remainingSeconds = max(0, $dueAt->getTimestamp() - $now->getTimestamp());
        $progressPct = $totalSeconds > 0 ? min(100, round(($elapsedSeconds / $totalSeconds) * 100, 1)) : 100;

        // Days overdue (negative = days remaining)
        $daysUntilDue = (int) $now->diff($dueAt)->format('%r%a');

        return [
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
            'at_risk_at' => $atRiskAt->format('Y-m-d H:i:s'),
            'escalation_at' => $escalationAt->format('Y-m-d H:i:s'),
            'sla_status' => $slaStatus,
            'sla_color' => self::STATUS_COLORS[$slaStatus] ?? '#6c757d',
            'sla_icon' => self::STATUS_ICONS[$slaStatus] ?? 'fa-question',
            'sla_label' => $this->getStatusLabel($slaStatus),
            'days_until_due' => $daysUntilDue,
            'remaining_seconds' => $remainingSeconds,
            'remaining_human' => $this->humanizeDuration($remainingSeconds),
            'progress_pct' => $progressPct,
            'is_overdue' => in_array($slaStatus, [self::STATUS_OVERDUE, self::STATUS_BREACHED]),
            'is_at_risk' => $slaStatus === self::STATUS_AT_RISK,
            'policy_name' => $policy['name'] ?? 'Default',
            'policy_source' => $policy['source'] ?? 'unknown',
        ];
    }

    /**
     * Compute SLA for a task record directly (convenience method).
     *
     * @param object $task Task record from DB (needs: created_at, priority, queue_id, workflow_id, due_date, status, decision_at)
     * @return array SLA computation result
     */
    public function computeForTask(object $task): array
    {
        // Resolve queue slug
        $queueSlug = null;
        if (!empty($task->queue_id)) {
            $queueSlug = DB::table('ahg_workflow_queue')
                ->where('id', $task->queue_id)
                ->value('slug');
        }

        $policy = $this->resolvePolicy(
            $queueSlug,
            $task->workflow_id ?? null,
            $task->priority ?? 'normal'
        );

        // Determine completion time
        $completedAt = null;
        $terminalStatuses = ['approved', 'rejected', 'cancelled'];
        if (in_array($task->status ?? '', $terminalStatuses)) {
            $completedAt = $task->decision_at ?? $task->updated_at ?? null;
        }

        return $this->compute(
            $task->created_at,
            $policy,
            $task->due_date ?? null,
            $completedAt
        );
    }

    /**
     * Batch compute SLA for multiple tasks.
     *
     * @param array $tasks Array of task objects
     * @return array Keyed by task ID
     */
    public function computeBatch(array $tasks): array
    {
        $results = [];
        foreach ($tasks as $task) {
            $results[$task->id] = $this->computeForTask($task);
        }
        return $results;
    }

    // =========================================================================
    // DUE DATE MANAGEMENT
    // =========================================================================

    /**
     * Apply SLA policy to a task: compute and set due_date.
     * Emits sla_policy_applied event.
     *
     * @param int    $taskId
     * @param int    $userId  User triggering the action
     * @return array SLA computation result
     */
    public function applyPolicy(int $taskId, int $userId): array
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task) {
            throw new \InvalidArgumentException("Task {$taskId} not found");
        }

        $sla = $this->computeForTask($task);

        // Set due_date from computed value
        $dueDate = substr($sla['due_at'], 0, 10); // Y-m-d
        DB::table('ahg_workflow_task')
            ->where('id', $taskId)
            ->update([
                'due_date' => $dueDate,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Emit event
        $this->emitSlaEvent('sla_policy_applied', $task, $userId, [
            'policy_name' => $sla['policy_name'],
            'policy_source' => $sla['policy_source'],
            'computed_due_date' => $dueDate,
            'escalation_at' => $sla['escalation_at'],
        ]);

        return $sla;
    }

    /**
     * Override a task's due date manually (role-gated by caller).
     * Emits sla_due_overridden event with reason.
     *
     * @param int    $taskId
     * @param string $newDueDate  Y-m-d format
     * @param int    $userId
     * @param string $reason      Required reason for audit trail
     * @return array Updated SLA computation
     */
    public function overrideDueDate(int $taskId, string $newDueDate, int $userId, string $reason): array
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task) {
            throw new \InvalidArgumentException("Task {$taskId} not found");
        }

        if (empty($reason)) {
            throw new \InvalidArgumentException('Reason is required when overriding SLA due date');
        }

        $oldDueDate = $task->due_date;

        DB::table('ahg_workflow_task')
            ->where('id', $taskId)
            ->update([
                'due_date' => $newDueDate,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Emit override event
        $this->emitSlaEvent('sla_due_overridden', $task, $userId, [
            'old_due_date' => $oldDueDate,
            'new_due_date' => $newDueDate,
            'reason' => $reason,
        ], "Due date changed from {$oldDueDate} to {$newDueDate}: {$reason}");

        // Recompute with new date
        $task->due_date = $newDueDate;
        return $this->computeForTask($task);
    }

    // =========================================================================
    // BREACH DETECTION & ESCALATION
    // =========================================================================

    /**
     * Scan all open tasks and detect SLA breaches + at-risk items.
     * Called by daily cron job.
     *
     * @param int $limit Max tasks to process
     * @return array{breached: int, at_risk: int, escalated: int, notified: int, errors: int}
     */
    public function processSlaBreach(int $limit = 500): array
    {
        $results = [
            'breached' => 0,
            'at_risk' => 0,
            'escalated' => 0,
            'notified' => 0,
            'errors' => 0,
        ];

        // Get all open tasks
        $tasks = DB::table('ahg_workflow_task as t')
            ->leftJoin('ahg_workflow_queue as q', 't.queue_id', '=', 'q.id')
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled'])
            ->select('t.*', 'q.slug as queue_slug')
            ->limit($limit)
            ->get();

        foreach ($tasks as $task) {
            try {
                $sla = $this->computeForTask($task);

                if ($sla['sla_status'] === self::STATUS_BREACHED) {
                    $results['breached']++;
                    $this->handleBreach($task, $sla);
                    $results['escalated']++;
                } elseif ($sla['sla_status'] === self::STATUS_OVERDUE) {
                    $results['breached']++;
                    // Emit warning event (once — check metadata)
                    $this->emitSlaEventOnce('sla_breached', $task);
                } elseif ($sla['sla_status'] === self::STATUS_AT_RISK) {
                    $results['at_risk']++;
                    $this->emitSlaEventOnce('sla_warning', $task);
                }
            } catch (\Exception $e) {
                $results['errors']++;
                error_log("SLA breach processing error for task {$task->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Handle an SLA breach: escalate task according to policy.
     */
    private function handleBreach(object $task, array $sla): void
    {
        $queueSlug = null;
        if (!empty($task->queue_id)) {
            $queueSlug = DB::table('ahg_workflow_queue')
                ->where('id', $task->queue_id)
                ->value('slug');
        }

        $policy = $this->resolvePolicy($queueSlug, $task->workflow_id, $task->priority ?? 'normal');
        $escalationAction = $policy['escalation_action'] ?? 'notify_lead';
        $escalationUserId = $policy['escalation_user_id'] ?? null;

        // Resolve escalation target
        if (!$escalationUserId) {
            if ($escalationAction === 'notify_admin') {
                // Find first admin user
                $escalationUserId = DB::table('user')
                    ->join('acl_user_group', 'user.id', '=', 'acl_user_group.user_id')
                    ->where('acl_user_group.group_id', 100) // administrator group
                    ->where('user.active', 1)
                    ->value('user.id');
            }
            // Fall back to task submitter
            $escalationUserId = $escalationUserId ?: $task->submitted_by;
        }

        // Auto-reassign if configured
        if ($escalationAction === 'auto_reassign' && $escalationUserId) {
            DB::table('ahg_workflow_task')
                ->where('id', $task->id)
                ->update([
                    'assigned_to' => $escalationUserId,
                    'status' => 'claimed',
                    'claimed_at' => date('Y-m-d H:i:s'),
                    'escalated_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            // Mark as escalated
            DB::table('ahg_workflow_task')
                ->where('id', $task->id)
                ->update([
                    'escalated_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        // Emit breach event
        $this->emitSlaEvent('sla_breached', $task, 0, [
            'escalation_action' => $escalationAction,
            'escalation_user_id' => $escalationUserId,
            'policy_name' => $policy['name'],
            'days_overdue' => abs($sla['days_until_due']),
        ], "SLA breached — escalation: {$escalationAction}");

        // Queue notification to escalation target
        if ($escalationUserId) {
            $this->queueSlaNotification($task, $escalationUserId, 'sla_breached');
        }
    }

    /**
     * Emit an SLA event only once per task per action (idempotent).
     */
    private function emitSlaEventOnce(string $action, object $task): void
    {
        // Check if already emitted today
        $existing = DB::table('ahg_workflow_history')
            ->where('task_id', $task->id)
            ->where('action', $action)
            ->where('performed_at', '>=', date('Y-m-d 00:00:00'))
            ->exists();

        if (!$existing) {
            $this->emitSlaEvent($action, $task, 0, []);

            // Also queue notification for at-risk/overdue
            $notifyUserId = $task->assigned_to ?: $task->submitted_by;
            if ($notifyUserId) {
                $notificationType = ($action === 'sla_warning') ? 'task_due_soon' : 'task_overdue';
                $this->queueSlaNotification($task, $notifyUserId, $notificationType);
            }
        }
    }

    // =========================================================================
    // REPORTING & ANALYTICS
    // =========================================================================

    /**
     * Get SLA summary stats across all open tasks.
     */
    public function getOverview(): array
    {
        $tasks = DB::table('ahg_workflow_task as t')
            ->leftJoin('ahg_workflow_queue as q', 't.queue_id', '=', 'q.id')
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled'])
            ->select('t.*', 'q.slug as queue_slug')
            ->get();

        $counts = [
            self::STATUS_ON_TRACK => 0,
            self::STATUS_AT_RISK => 0,
            self::STATUS_OVERDUE => 0,
            self::STATUS_BREACHED => 0,
        ];

        foreach ($tasks as $task) {
            $sla = $this->computeForTask($task);
            if (isset($counts[$sla['sla_status']])) {
                $counts[$sla['sla_status']]++;
            }
        }

        return [
            'total_open' => count($tasks),
            'on_track' => $counts[self::STATUS_ON_TRACK],
            'at_risk' => $counts[self::STATUS_AT_RISK],
            'overdue' => $counts[self::STATUS_OVERDUE],
            'breached' => $counts[self::STATUS_BREACHED],
            'health_pct' => count($tasks) > 0
                ? round(($counts[self::STATUS_ON_TRACK] / count($tasks)) * 100, 1)
                : 100,
        ];
    }

    /**
     * Get SLA stats per queue.
     */
    public function getStatsByQueue(): array
    {
        $queues = DB::table('ahg_workflow_queue')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        $result = [];

        foreach ($queues as $queue) {
            $tasks = DB::table('ahg_workflow_task')
                ->where('queue_id', $queue->id)
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->get();

            $counts = [
                'total' => count($tasks),
                self::STATUS_ON_TRACK => 0,
                self::STATUS_AT_RISK => 0,
                self::STATUS_OVERDUE => 0,
                self::STATUS_BREACHED => 0,
            ];

            $totalAge = 0;
            foreach ($tasks as $task) {
                $sla = $this->computeForTask($task);
                if (isset($counts[$sla['sla_status']])) {
                    $counts[$sla['sla_status']]++;
                }
                $totalAge += (time() - strtotime($task->created_at));
            }

            $counts['avg_age_days'] = $counts['total'] > 0
                ? round($totalAge / $counts['total'] / 86400, 1)
                : 0;

            $result[$queue->slug] = [
                'queue' => $queue,
                'stats' => $counts,
            ];
        }

        return $result;
    }

    /**
     * Get SLA stats per user (team view).
     */
    public function getStatsByUser(?int $groupId = null): array
    {
        $query = DB::table('ahg_workflow_task as t')
            ->join('user as u', 't.assigned_to', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereNotNull('t.assigned_to')
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled']);

        if ($groupId) {
            $query->join('acl_user_group as aug', 'u.id', '=', 'aug.user_id')
                  ->where('aug.group_id', $groupId);
        }

        $tasks = $query->select('t.*', DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as user_name'))
            ->get();

        $byUser = [];
        foreach ($tasks as $task) {
            $userId = $task->assigned_to;
            if (!isset($byUser[$userId])) {
                $byUser[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $task->user_name,
                    'total' => 0,
                    self::STATUS_ON_TRACK => 0,
                    self::STATUS_AT_RISK => 0,
                    self::STATUS_OVERDUE => 0,
                    self::STATUS_BREACHED => 0,
                ];
            }

            $sla = $this->computeForTask($task);
            $byUser[$userId]['total']++;
            if (isset($byUser[$userId][$sla['sla_status']])) {
                $byUser[$userId][$sla['sla_status']]++;
            }
        }

        return array_values($byUser);
    }

    /**
     * Get overdue tasks with full SLA details, filterable.
     *
     * @param array $filters Keys: user_id, queue_id, priority, limit
     * @return array
     */
    public function getOverdueTasks(array $filters = []): array
    {
        $query = DB::table('ahg_workflow_task as t')
            ->leftJoin('ahg_workflow_queue as q', 't.queue_id', '=', 'q.id')
            ->leftJoin('user as u', 't.assigned_to', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled']);

        if (!empty($filters['user_id'])) {
            $query->where('t.assigned_to', $filters['user_id']);
        }
        if (!empty($filters['queue_id'])) {
            $query->where('t.queue_id', $filters['queue_id']);
        }
        if (!empty($filters['priority'])) {
            $query->where('t.priority', $filters['priority']);
        }

        $limit = (int) ($filters['limit'] ?? 100);

        $tasks = $query->select(
            't.*',
            'q.name as queue_name',
            'q.slug as queue_slug',
            'q.color as queue_color',
            'q.icon as queue_icon',
            'w.name as workflow_name',
            DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as assignee_name'),
            'ioi.title as object_title'
        )->limit($limit)->get();

        // Filter to only overdue/breached/at_risk and attach SLA data
        $results = [];
        foreach ($tasks as $task) {
            $sla = $this->computeForTask($task);
            if (in_array($sla['sla_status'], [self::STATUS_OVERDUE, self::STATUS_BREACHED, self::STATUS_AT_RISK])) {
                $task->sla = (object) $sla;
                $results[] = $task;
            }
        }

        // Sort: breached first, then overdue, then at_risk
        usort($results, function ($a, $b) {
            $order = [self::STATUS_BREACHED => 0, self::STATUS_OVERDUE => 1, self::STATUS_AT_RISK => 2];
            $aOrder = $order[$a->sla->sla_status] ?? 3;
            $bOrder = $order[$b->sla->sla_status] ?? 3;
            if ($aOrder !== $bOrder) {
                return $aOrder - $bOrder;
            }
            return ($a->sla->days_until_due ?? 0) - ($b->sla->days_until_due ?? 0);
        });

        return $results;
    }

    /**
     * Export overdue report as CSV.
     */
    public function exportOverdueCsv(array $filters = []): string
    {
        $tasks = $this->getOverdueTasks($filters);

        $csv = "task_id,object_title,queue,assignee,priority,status,sla_status,due_date,days_overdue,policy\n";
        foreach ($tasks as $task) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",%s,%s,%s,%s,%d,\"%s\"\n",
                $task->id,
                str_replace('"', '""', $task->object_title ?? "Object #{$task->object_id}"),
                str_replace('"', '""', $task->queue_name ?? 'Unassigned'),
                str_replace('"', '""', $task->assignee_name ?? 'Unassigned'),
                $task->priority,
                $task->status,
                $task->sla->sla_status,
                $task->sla->due_at,
                abs($task->sla->days_until_due),
                str_replace('"', '""', $task->sla->policy_name)
            );
        }

        return $csv;
    }

    // =========================================================================
    // POLICY ADMIN (DB)
    // =========================================================================

    /**
     * Get all SLA policies from DB.
     */
    public function getPolicies(): array
    {
        return DB::table('ahg_workflow_sla_policy')
            ->leftJoin('ahg_workflow_queue as q', 'ahg_workflow_sla_policy.queue_id', '=', 'q.id')
            ->leftJoin('ahg_workflow as w', 'ahg_workflow_sla_policy.workflow_id', '=', 'w.id')
            ->select(
                'ahg_workflow_sla_policy.*',
                'q.name as queue_name',
                'w.name as workflow_name'
            )
            ->orderBy('ahg_workflow_sla_policy.name')
            ->get()
            ->toArray();
    }

    /**
     * Create a DB SLA policy.
     */
    public function createPolicy(array $data): int
    {
        return DB::table('ahg_workflow_sla_policy')->insertGetId([
            'name' => $data['name'],
            'queue_id' => $data['queue_id'] ?? null,
            'workflow_id' => $data['workflow_id'] ?? null,
            'warning_days' => (int) ($data['warning_days'] ?? 3),
            'due_days' => (int) ($data['due_days'] ?? 5),
            'escalation_days' => (int) ($data['escalation_days'] ?? 7),
            'escalation_user_id' => $data['escalation_user_id'] ?? null,
            'escalation_action' => $data['escalation_action'] ?? 'notify_lead',
            'is_active' => (int) ($data['is_active'] ?? 1),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a DB SLA policy.
     */
    public function updatePolicy(int $id, array $data): bool
    {
        $this->dbPolicyCache = []; // Clear cache
        return DB::table('ahg_workflow_sla_policy')
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Delete a DB SLA policy.
     */
    public function deletePolicy(int $id): bool
    {
        $this->dbPolicyCache = [];
        return DB::table('ahg_workflow_sla_policy')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Seed DB policies from YAML config.
     */
    public function seedFromYaml(): int
    {
        $yaml = $this->loadYamlConfig();
        $queues = DB::table('ahg_workflow_queue')->pluck('id', 'slug')->toArray();
        $seeded = 0;

        foreach ($yaml['queues'] ?? [] as $slug => $queuePolicy) {
            $queueId = $queues[$slug] ?? null;
            if (!$queueId) {
                continue;
            }

            // Check if DB policy already exists for this queue
            $exists = DB::table('ahg_workflow_sla_policy')
                ->where('queue_id', $queueId)
                ->exists();

            if (!$exists) {
                $this->createPolicy([
                    'name' => ucfirst($slug) . ' Queue SLA',
                    'queue_id' => $queueId,
                    'warning_days' => $queuePolicy['warning_days'] ?? 3,
                    'due_days' => $queuePolicy['due_days'] ?? 5,
                    'escalation_days' => $queuePolicy['escalation_days'] ?? 7,
                    'escalation_action' => $queuePolicy['escalation_action'] ?? 'notify_lead',
                ]);
                $seeded++;
            }
        }

        return $seeded;
    }

    // =========================================================================
    // INTERNALS
    // =========================================================================

    /**
     * Get DB policy for a queue/workflow combination.
     */
    private function getDbPolicy(?string $queueSlug, ?int $workflowId): ?object
    {
        $cacheKey = ($queueSlug ?? 'null') . ':' . ($workflowId ?? 'null');
        if (isset($this->dbPolicyCache[$cacheKey])) {
            return $this->dbPolicyCache[$cacheKey];
        }

        $query = DB::table('ahg_workflow_sla_policy')
            ->where('is_active', 1);

        // Most specific: queue + workflow
        if ($queueSlug && $workflowId) {
            $queueId = DB::table('ahg_workflow_queue')->where('slug', $queueSlug)->value('id');
            if ($queueId) {
                $policy = (clone $query)
                    ->where('queue_id', $queueId)
                    ->where('workflow_id', $workflowId)
                    ->first();
                if ($policy) {
                    $this->dbPolicyCache[$cacheKey] = $policy;
                    return $policy;
                }
            }
        }

        // Queue-only
        if ($queueSlug) {
            $queueId = $queueId ?? DB::table('ahg_workflow_queue')->where('slug', $queueSlug)->value('id');
            if ($queueId) {
                $policy = (clone $query)
                    ->where('queue_id', $queueId)
                    ->whereNull('workflow_id')
                    ->first();
                if ($policy) {
                    $this->dbPolicyCache[$cacheKey] = $policy;
                    return $policy;
                }
            }
        }

        // No DB override
        $this->dbPolicyCache[$cacheKey] = null;
        return null;
    }

    /**
     * Load YAML config (cached per request).
     */
    private function loadYamlConfig(): array
    {
        if ($this->yamlConfig !== null) {
            return $this->yamlConfig;
        }

        $paths = [
            (class_exists('\sfConfig') ? \sfConfig::get('sf_root_dir', '') : '') . '/plugins/ahgWorkflowPlugin/config/sla_policies.yml',
            __DIR__ . '/../../config/sla_policies.yml',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $parsed = \sfYaml::parse($content);
                if (is_array($parsed)) {
                    $this->yamlConfig = $parsed;
                    return $this->yamlConfig;
                }
            }
        }

        // Fallback defaults
        $this->yamlConfig = [
            'calendar' => ['use_business_days' => false],
            'default' => [
                'due_days' => 5,
                'warning_days' => 3,
                'escalation_days' => 7,
                'escalation_action' => 'notify_lead',
                'at_risk_threshold' => 0.20,
            ],
            'queues' => [],
            'priority_factors' => [
                'urgent' => 0.4,
                'high' => 0.6,
                'normal' => 1.0,
                'low' => 1.5,
            ],
        ];

        return $this->yamlConfig;
    }

    /**
     * Get priority factor from YAML config.
     */
    private function getPriorityFactor(string $priority): float
    {
        $yaml = $this->loadYamlConfig();
        return (float) ($yaml['priority_factors'][$priority] ?? 1.0);
    }

    /**
     * Add calendar days to a date.
     */
    private function addDays(\DateTimeImmutable $date, int $days, bool $businessDays = false): \DateTimeImmutable
    {
        if (!$businessDays) {
            return $date->modify("+{$days} days");
        }

        // Business days: skip weekends
        $yaml = $this->loadYamlConfig();
        $weekends = $yaml['calendar']['weekends'] ?? ['saturday', 'sunday'];
        $holidays = $yaml['calendar']['holidays'] ?? [];

        $added = 0;
        $current = $date;
        while ($added < $days) {
            $current = $current->modify('+1 day');
            $dayName = strtolower($current->format('l'));
            $dateStr = $current->format('Y-m-d');

            if (!in_array($dayName, $weekends) && !in_array($dateStr, $holidays)) {
                $added++;
            }
        }

        return $current;
    }

    /**
     * Subtract calendar days from a date.
     */
    private function subtractDays(\DateTimeImmutable $date, int $days, bool $businessDays = false): \DateTimeImmutable
    {
        if (!$businessDays) {
            return $date->modify("-{$days} days");
        }

        $yaml = $this->loadYamlConfig();
        $weekends = $yaml['calendar']['weekends'] ?? ['saturday', 'sunday'];
        $holidays = $yaml['calendar']['holidays'] ?? [];

        $subtracted = 0;
        $current = $date;
        while ($subtracted < $days) {
            $current = $current->modify('-1 day');
            $dayName = strtolower($current->format('l'));
            $dateStr = $current->format('Y-m-d');

            if (!in_array($dayName, $weekends) && !in_array($dateStr, $holidays)) {
                $subtracted++;
            }
        }

        return $current;
    }

    /**
     * Human-readable duration string.
     */
    private function humanizeDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'overdue';
        }

        $days = (int) floor($seconds / 86400);
        $hours = (int) floor(($seconds % 86400) / 3600);

        if ($days > 0) {
            return $days === 1 ? '1 day' : "{$days} days";
        }
        if ($hours > 0) {
            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        $minutes = (int) floor($seconds / 60);
        return $minutes <= 1 ? '< 1 minute' : "{$minutes} minutes";
    }

    /**
     * Get human label for SLA status.
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_ON_TRACK => 'On Track',
            self::STATUS_AT_RISK => 'At Risk',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_BREACHED => 'Breached',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_NO_POLICY => 'No Policy',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Emit an SLA event via WorkflowEventService.
     */
    private function emitSlaEvent(string $action, object $task, int $userId, array $metadata, ?string $comment = null): void
    {
        try {
            require_once __DIR__ . '/WorkflowEventService.php';
            $eventService = new WorkflowEventService();
            $eventService->emit($action, [
                'task_id' => $task->id,
                'workflow_id' => $task->workflow_id,
                'step_id' => $task->workflow_step_id ?? null,
                'object_id' => $task->object_id,
                'object_type' => $task->object_type ?? 'information_object',
                'performed_by' => $userId,
                'comment' => $comment,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            error_log("SLA event emit error: " . $e->getMessage());
        }
    }

    /**
     * Queue an SLA notification.
     */
    private function queueSlaNotification(object $task, int $userId, string $type): void
    {
        try {
            $objectTitle = 'Object #' . $task->object_id;
            if ($task->object_type === 'information_object') {
                $title = DB::table('information_object_i18n')
                    ->where('id', $task->object_id)
                    ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->value('title');
                if ($title) {
                    $objectTitle = $title;
                }
            }

            $subject = match ($type) {
                'task_due_soon' => "SLA Warning: task due soon — {$objectTitle}",
                'task_overdue' => "SLA Overdue: task past due — {$objectTitle}",
                'sla_breached' => "SLA Breached: immediate action required — {$objectTitle}",
                default => "SLA Notification: {$objectTitle}",
            };

            DB::table('ahg_workflow_notification')->insert([
                'task_id' => $task->id,
                'user_id' => $userId,
                'notification_type' => $type,
                'subject' => $subject,
                'body' => "Task #{$task->id} for '{$objectTitle}' requires attention. Priority: {$task->priority}.",
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("SLA notification error: " . $e->getMessage());
        }
    }
}
