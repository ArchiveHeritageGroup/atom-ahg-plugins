<?php

namespace AtomFramework\Console\Commands\Workflow;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * View workflow task status and statistics.
 */
class StatusCommand extends BaseCommand
{
    protected string $name = 'workflow:status';
    protected string $description = 'View workflow task status and statistics';
    protected string $detailedDescription = <<<'EOF'
    Display workflow status and statistics.

    Examples:
      php bin/atom workflow:status              Show summary statistics
      php bin/atom workflow:status --pending    List pending tasks
      php bin/atom workflow:status --overdue    List overdue tasks
      php bin/atom workflow:status --claimed    List claimed tasks
      php bin/atom workflow:status --user=5     Show tasks for user ID 5
    EOF;

    protected function configure(): void
    {
        $this->addOption('pending', null, 'Show pending tasks');
        $this->addOption('overdue', null, 'Show overdue tasks');
        $this->addOption('claimed', null, 'Show claimed tasks');
        $this->addOption('user', null, 'Filter by user ID');
        $this->addOption('workflow', null, 'Filter by workflow ID');
        $this->addOption('limit', 'l', 'Limit output', '50');
    }

    protected function handle(): int
    {
        $this->bold('  Workflow Status Report');
        $this->line('  ' . str_repeat('=', 50));

        // Show summary if no specific filter
        if (!$this->hasOption('pending') && !$this->hasOption('overdue') && !$this->hasOption('claimed')) {
            $this->showSummary();
        }

        // Show pending tasks
        if ($this->hasOption('pending')) {
            $this->showTasks('pending');
        }

        // Show overdue tasks
        if ($this->hasOption('overdue')) {
            $this->showOverdueTasks();
        }

        // Show claimed tasks
        if ($this->hasOption('claimed')) {
            $this->showTasks('claimed');
        }

        return 0;
    }

    protected function showSummary(): void
    {
        $this->newline();
        $this->info('Statistics');

        $stats = [
            'Active Workflows' => DB::table('ahg_workflow')->where('is_active', 1)->count(),
            'Total Tasks' => DB::table('ahg_workflow_task')->count(),
            'Pending' => DB::table('ahg_workflow_task')->where('status', 'pending')->count(),
            'Claimed' => DB::table('ahg_workflow_task')->where('status', 'claimed')->count(),
            'In Progress' => DB::table('ahg_workflow_task')->where('status', 'in_progress')->count(),
            'Approved Today' => DB::table('ahg_workflow_task')
                ->where('status', 'approved')
                ->whereDate('decision_at', date('Y-m-d'))
                ->count(),
            'Overdue' => DB::table('ahg_workflow_task')
                ->whereNotNull('due_date')
                ->where('due_date', '<', date('Y-m-d'))
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count(),
            'Pending Notifications' => DB::table('ahg_workflow_notification')
                ->where('status', 'pending')
                ->count(),
        ];

        foreach ($stats as $label => $value) {
            $this->line(sprintf("  %-25s %d", $label . ':', $value));
        }

        // Workflows breakdown
        $this->newline();
        $this->info('Active Workflows');
        $workflows = DB::table('ahg_workflow')
            ->where('is_active', 1)
            ->select('id', 'name', 'scope_type')
            ->get();

        foreach ($workflows as $wf) {
            $taskCount = DB::table('ahg_workflow_task')
                ->where('workflow_id', $wf->id)
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->count();
            $this->line(sprintf("  [%d] %-30s (%s) - %d active tasks", $wf->id, $wf->name, $wf->scope_type, $taskCount));
        }
    }

    protected function showTasks(string $status): void
    {
        $this->newline();
        $this->info(ucfirst($status) . ' Tasks');

        $query = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as u', 't.assigned_to', '=', 'u.id')
            ->where('t.status', $status);

        if ($this->hasOption('user')) {
            $query->where('t.assigned_to', (int) $this->option('user'));
        }
        if ($this->hasOption('workflow')) {
            $query->where('t.workflow_id', (int) $this->option('workflow'));
        }

        $tasks = $query
            ->select('t.id', 't.object_id', 't.priority', 't.due_date', 't.created_at', 'w.name as workflow', 's.name as step', 'ioi.title', 'u.username')
            ->orderBy('t.priority', 'desc')
            ->orderBy('t.created_at')
            ->limit((int) $this->option('limit', '50'))
            ->get();

        if ($tasks->isEmpty()) {
            $this->line("  No {$status} tasks found.");

            return;
        }

        $headers = ['ID', 'Object', 'Workflow', 'Step', 'Priority', 'Assigned'];
        $rows = [];

        foreach ($tasks as $task) {
            $title = mb_substr($task->title ?? "#{$task->object_id}", 0, 28);
            $rows[] = [
                $task->id,
                $title,
                mb_substr($task->workflow, 0, 13),
                mb_substr($task->step, 0, 13),
                $task->priority,
                $task->username ?? '-',
            ];
        }

        $this->table($headers, $rows);
    }

    protected function showOverdueTasks(): void
    {
        $this->newline();
        $this->info('Overdue Tasks');

        $query = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 't.workflow_id', '=', 'w.id')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('t.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as u', 't.assigned_to', '=', 'u.id')
            ->whereNotNull('t.due_date')
            ->where('t.due_date', '<', date('Y-m-d'))
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled']);

        if ($this->hasOption('user')) {
            $query->where('t.assigned_to', (int) $this->option('user'));
        }

        $tasks = $query
            ->select('t.id', 't.object_id', 't.status', 't.due_date', 'w.name as workflow', 's.name as step', 'ioi.title', 'u.username')
            ->orderBy('t.due_date')
            ->limit((int) $this->option('limit', '50'))
            ->get();

        if ($tasks->isEmpty()) {
            $this->line('  No overdue tasks found.');

            return;
        }

        $headers = ['ID', 'Object', 'Step', 'Status', 'Assigned', 'Due Date', 'Overdue'];
        $rows = [];

        foreach ($tasks as $task) {
            $title = mb_substr($task->title ?? "#{$task->object_id}", 0, 23);
            $daysOverdue = (int) ((time() - strtotime($task->due_date)) / 86400);

            $rows[] = [
                $task->id,
                $title,
                mb_substr($task->step, 0, 13),
                $task->status,
                $task->username ?? '-',
                $task->due_date,
                "{$daysOverdue} days",
            ];
        }

        $this->table($headers, $rows);
    }
}
