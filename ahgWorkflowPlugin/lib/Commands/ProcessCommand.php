<?php

namespace AtomFramework\Console\Commands\Workflow;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Process workflow operations (notifications, escalation, cleanup).
 */
class ProcessCommand extends BaseCommand
{
    protected string $name = 'workflow:process';
    protected string $description = 'Process workflow operations (notifications, escalation, cleanup)';
    protected string $detailedDescription = <<<'EOF'
    Process pending workflow operations.
    This command should be run periodically via cron (e.g., every 5-15 minutes).

    Examples:
      php bin/atom workflow:process                    Run all processing
      php bin/atom workflow:process --notifications   Send pending emails only
      php bin/atom workflow:process --escalate        Escalate overdue tasks
      php bin/atom workflow:process --cleanup --days=60 Archive tasks older than 60 days
    EOF;

    protected function configure(): void
    {
        $this->addOption('notifications', null, 'Send pending notifications');
        $this->addOption('escalate', null, 'Escalate overdue tasks');
        $this->addOption('cleanup', null, 'Archive old completed tasks');
        $this->addOption('days', null, 'Days for cleanup (default 90)', '90');
        $this->addOption('limit', 'l', 'Limit operations per run', '100');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowService.php';
        if (!file_exists($serviceFile)) {
            $this->error("WorkflowService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \WorkflowService();
        $runAll = !$this->hasOption('notifications') && !$this->hasOption('escalate') && !$this->hasOption('cleanup');

        $this->info('Starting workflow processing...');

        // Send notifications
        if ($runAll || $this->hasOption('notifications')) {
            $this->processNotifications($service, (int) $this->option('limit', '100'));
        }

        // Escalate overdue tasks
        if ($runAll || $this->hasOption('escalate')) {
            $this->escalateOverdueTasks();
        }

        // Cleanup old tasks
        if ($this->hasOption('cleanup')) {
            $this->cleanupOldTasks((int) $this->option('days', '90'));
        }

        $this->success('Processing complete.');

        return 0;
    }

    protected function processNotifications($service, int $limit): void
    {
        $this->info("Processing pending notifications (limit: {$limit})...");

        $results = $service->processPendingNotifications($limit);

        $this->line("  Sent: {$results['sent']}, Failed: {$results['failed']}");
    }

    protected function escalateOverdueTasks(): void
    {
        $this->info('Checking for overdue tasks...');

        $overdueTasks = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->whereNotNull('t.due_date')
            ->where('t.due_date', '<', date('Y-m-d'))
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled', 'escalated'])
            ->whereNotNull('s.escalation_user_id')
            ->select('t.*', 's.escalation_user_id')
            ->get();

        $escalated = 0;
        foreach ($overdueTasks as $task) {
            DB::table('ahg_workflow_task')
                ->where('id', $task->id)
                ->update([
                    'status' => 'escalated',
                    'assigned_to' => $task->escalation_user_id,
                    'escalated_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Log history
            DB::table('ahg_workflow_history')->insert([
                'task_id' => $task->id,
                'workflow_id' => $task->workflow_id,
                'workflow_step_id' => $task->workflow_step_id,
                'object_id' => $task->object_id,
                'object_type' => $task->object_type,
                'action' => 'escalated',
                'from_status' => $task->status,
                'to_status' => 'escalated',
                'performed_by' => 1, // System user
                'performed_at' => date('Y-m-d H:i:s'),
                'comment' => 'Automatically escalated due to overdue deadline',
            ]);

            ++$escalated;
        }

        $this->line("  Escalated {$escalated} overdue tasks");
    }

    protected function cleanupOldTasks(int $days): void
    {
        $this->info("Archiving tasks completed more than {$days} days ago...");

        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));

        // Get workflows with auto_archive_days set
        $workflows = DB::table('ahg_workflow')
            ->whereNotNull('auto_archive_days')
            ->get();

        $archived = 0;
        foreach ($workflows as $workflow) {
            $workflowCutoff = date('Y-m-d', strtotime("-{$workflow->auto_archive_days} days"));

            $count = DB::table('ahg_workflow_task')
                ->where('workflow_id', $workflow->id)
                ->whereIn('status', ['approved', 'rejected', 'cancelled'])
                ->where('updated_at', '<', $workflowCutoff)
                ->delete();

            $archived += $count;
        }

        // Also clean up tasks based on command parameter
        $count = DB::table('ahg_workflow_task')
            ->whereIn('status', ['approved', 'rejected', 'cancelled'])
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        $archived += $count;

        $this->line("  Archived {$archived} old tasks");
    }
}
