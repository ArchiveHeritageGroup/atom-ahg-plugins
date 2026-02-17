<?php

/**
 * CLI task for viewing workflow status.
 *
 * Usage:
 *   php symfony workflow:status              # Show summary
 *   php symfony workflow:status --pending    # Show pending tasks
 *   php symfony workflow:status --overdue    # Show overdue tasks
 *   php symfony workflow:status --user=ID    # Show tasks for specific user
 */
class workflowStatusTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('pending', null, sfCommandOption::PARAMETER_NONE, 'Show pending tasks'),
            new sfCommandOption('overdue', null, sfCommandOption::PARAMETER_NONE, 'Show overdue tasks'),
            new sfCommandOption('claimed', null, sfCommandOption::PARAMETER_NONE, 'Show claimed tasks'),
            new sfCommandOption('user', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by user ID'),
            new sfCommandOption('workflow', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by workflow ID'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Limit output', 50),
        ]);

        $this->namespace = 'workflow';
        $this->name = 'status';
        $this->briefDescription = 'View workflow task status and statistics';
        $this->detailedDescription = <<<EOF
The [workflow:status|INFO] task displays workflow status and statistics.

Examples:
  [php symfony workflow:status|INFO]              # Show summary statistics
  [php symfony workflow:status --pending|INFO]   # List pending tasks
  [php symfony workflow:status --overdue|INFO]   # List overdue tasks
  [php symfony workflow:status --user=5|INFO]    # Show tasks for user ID 5
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->logSection('workflow', 'Workflow Status Report');
        $this->logSection('workflow', str_repeat('=', 50));

        // Show summary if no specific filter
        if (!$options['pending'] && !$options['overdue'] && !$options['claimed']) {
            $this->showSummary($db);
        }

        // Show pending tasks
        if ($options['pending']) {
            $this->showTasks($db, 'pending', $options);
        }

        // Show overdue tasks
        if ($options['overdue']) {
            $this->showOverdueTasks($db, $options);
        }

        // Show claimed tasks
        if ($options['claimed']) {
            $this->showTasks($db, 'claimed', $options);
        }
    }

    protected function showSummary($db): void
    {
        $this->logSection('summary', 'Statistics');

        $stats = [
            'Active Workflows' => $db::table('ahg_workflow')->where('is_active', 1)->count(),
            'Total Tasks' => $db::table('ahg_workflow_task')->count(),
            'Pending' => $db::table('ahg_workflow_task')->where('status', 'pending')->count(),
            'Claimed' => $db::table('ahg_workflow_task')->where('status', 'claimed')->count(),
            'In Progress' => $db::table('ahg_workflow_task')->where('status', 'in_progress')->count(),
            'Approved Today' => $db::table('ahg_workflow_task')
                ->where('status', 'approved')
                ->whereDate('decision_at', date('Y-m-d'))
                ->count(),
            'Overdue' => $db::table('ahg_workflow_task')
                ->whereNotNull('due_date')
                ->where('due_date', '<', date('Y-m-d'))
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count(),
            'Pending Notifications' => $db::table('ahg_workflow_notification')
                ->where('status', 'pending')
                ->count(),
        ];

        foreach ($stats as $label => $value) {
            $this->log(sprintf("  %-25s %d", $label . ':', $value));
        }

        // Workflows breakdown
        $this->logSection('workflows', 'Active Workflows');
        $workflows = $db::table('ahg_workflow')
            ->where('is_active', 1)
            ->select('id', 'name', 'scope_type')
            ->get();

        foreach ($workflows as $wf) {
            $taskCount = $db::table('ahg_workflow_task')
                ->where('workflow_id', $wf->id)
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count();
            $this->log(sprintf("  [%d] %-30s (%s) - %d active tasks", $wf->id, $wf->name, $wf->scope_type, $taskCount));
        }
    }

    protected function showTasks($db, string $status, array $options): void
    {
        $this->logSection($status, ucfirst($status) . ' Tasks');

        $query = $db::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as u', 't.assigned_to', '=', 'u.id')
            ->where('t.status', $status);

        if (!empty($options['user'])) {
            $query->where('t.assigned_to', (int) $options['user']);
        }
        if (!empty($options['workflow'])) {
            $query->where('t.workflow_id', (int) $options['workflow']);
        }

        $tasks = $query
            ->select('t.id', 't.object_id', 't.priority', 't.due_date', 't.created_at', 'w.name as workflow', 's.name as step', 'ioi.title', 'u.username')
            ->orderBy('t.priority', 'desc')
            ->orderBy('t.created_at')
            ->limit((int) ($options['limit'] ?? 50))
            ->get();

        if ($tasks->isEmpty()) {
            $this->log("  No {$status} tasks found.");
            return;
        }

        $this->log(sprintf("  %-6s %-30s %-15s %-15s %-10s %-12s", 'ID', 'Object', 'Workflow', 'Step', 'Priority', 'Assigned'));
        $this->log('  ' . str_repeat('-', 100));

        foreach ($tasks as $task) {
            $title = mb_substr($task->title ?? "#{$task->object_id}", 0, 28);
            $this->log(sprintf(
                "  %-6d %-30s %-15s %-15s %-10s %-12s",
                $task->id,
                $title,
                mb_substr($task->workflow, 0, 13),
                mb_substr($task->step, 0, 13),
                $task->priority,
                $task->username ?? '-'
            ));
        }
    }

    protected function showOverdueTasks($db, array $options): void
    {
        $this->logSection('overdue', 'Overdue Tasks');

        $query = $db::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as u', 't.assigned_to', '=', 'u.id')
            ->whereNotNull('t.due_date')
            ->where('t.due_date', '<', date('Y-m-d'))
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled']);

        if (!empty($options['user'])) {
            $query->where('t.assigned_to', (int) $options['user']);
        }

        $tasks = $query
            ->select('t.id', 't.object_id', 't.status', 't.due_date', 'w.name as workflow', 's.name as step', 'ioi.title', 'u.username')
            ->orderBy('t.due_date')
            ->limit((int) ($options['limit'] ?? 50))
            ->get();

        if ($tasks->isEmpty()) {
            $this->log("  No overdue tasks found.");
            return;
        }

        $this->log(sprintf("  %-6s %-25s %-15s %-10s %-12s %-10s", 'ID', 'Object', 'Step', 'Status', 'Assigned', 'Due Date'));
        $this->log('  ' . str_repeat('-', 90));

        foreach ($tasks as $task) {
            $title = mb_substr($task->title ?? "#{$task->object_id}", 0, 23);
            $daysOverdue = (int) ((time() - strtotime($task->due_date)) / 86400);

            $this->log(sprintf(
                "  %-6d %-25s %-15s %-10s %-12s %-10s (%d days)",
                $task->id,
                $title,
                mb_substr($task->step, 0, 13),
                $task->status,
                $task->username ?? '-',
                $task->due_date,
                $daysOverdue
            ));
        }
    }
}
