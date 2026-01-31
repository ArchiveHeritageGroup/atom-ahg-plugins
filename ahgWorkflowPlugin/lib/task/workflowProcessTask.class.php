<?php

/**
 * CLI task for processing workflow operations.
 *
 * Usage:
 *   php symfony workflow:process                    # Process all pending operations
 *   php symfony workflow:process --notifications    # Send pending notifications only
 *   php symfony workflow:process --escalate         # Check and escalate overdue tasks
 *   php symfony workflow:process --cleanup          # Archive old completed tasks
 */
class workflowProcessTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('notifications', null, sfCommandOption::PARAMETER_NONE, 'Send pending notifications'),
            new sfCommandOption('escalate', null, sfCommandOption::PARAMETER_NONE, 'Escalate overdue tasks'),
            new sfCommandOption('cleanup', null, sfCommandOption::PARAMETER_NONE, 'Archive old completed tasks'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Days for cleanup (default 90)', 90),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Limit operations per run', 100),
        ]);

        $this->namespace = 'workflow';
        $this->name = 'process';
        $this->briefDescription = 'Process workflow operations (notifications, escalation, cleanup)';
        $this->detailedDescription = <<<EOF
The [workflow:process|INFO] task processes pending workflow operations.

This command should be run periodically via cron (e.g., every 5-15 minutes).

Examples:
  [php symfony workflow:process|INFO]                    # Run all processing
  [php symfony workflow:process --notifications|INFO]   # Send pending emails only
  [php symfony workflow:process --escalate|INFO]        # Escalate overdue tasks
  [php symfony workflow:process --cleanup --days=60|INFO] # Archive tasks older than 60 days
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowService.php';

        $service = new WorkflowService();
        $runAll = !$options['notifications'] && !$options['escalate'] && !$options['cleanup'];

        $this->logSection('workflow', 'Starting workflow processing...');

        // Send notifications
        if ($runAll || $options['notifications']) {
            $this->processNotifications($service, (int) $options['limit']);
        }

        // Escalate overdue tasks
        if ($runAll || $options['escalate']) {
            $this->escalateOverdueTasks($service);
        }

        // Cleanup old tasks
        if ($options['cleanup']) {
            $this->cleanupOldTasks($service, (int) $options['days']);
        }

        $this->logSection('workflow', 'Processing complete.');
    }

    protected function processNotifications(WorkflowService $service, int $limit): void
    {
        $this->logSection('notify', "Processing pending notifications (limit: {$limit})...");

        $results = $service->processPendingNotifications($limit);

        $this->logSection('notify', "Sent: {$results['sent']}, Failed: {$results['failed']}");
    }

    protected function escalateOverdueTasks(WorkflowService $service): void
    {
        $this->logSection('escalate', 'Checking for overdue tasks...');

        $overdueTasks = \Illuminate\Database\Capsule\Manager::table('ahg_workflow_task as t')
            ->join('ahg_workflow_step as s', 't.workflow_step_id', '=', 's.id')
            ->whereNotNull('t.due_date')
            ->where('t.due_date', '<', date('Y-m-d'))
            ->whereNotIn('t.status', ['approved', 'rejected', 'cancelled', 'escalated'])
            ->whereNotNull('s.escalation_user_id')
            ->select('t.*', 's.escalation_user_id')
            ->get();

        $escalated = 0;
        foreach ($overdueTasks as $task) {
            \Illuminate\Database\Capsule\Manager::table('ahg_workflow_task')
                ->where('id', $task->id)
                ->update([
                    'status' => 'escalated',
                    'assigned_to' => $task->escalation_user_id,
                    'escalated_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Log history
            \Illuminate\Database\Capsule\Manager::table('ahg_workflow_history')->insert([
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

            $escalated++;
        }

        $this->logSection('escalate', "Escalated {$escalated} overdue tasks");
    }

    protected function cleanupOldTasks(WorkflowService $service, int $days): void
    {
        $this->logSection('cleanup', "Archiving tasks completed more than {$days} days ago...");

        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));

        // Get workflows with auto_archive_days set
        $workflows = \Illuminate\Database\Capsule\Manager::table('ahg_workflow')
            ->whereNotNull('auto_archive_days')
            ->get();

        $archived = 0;
        foreach ($workflows as $workflow) {
            $workflowCutoff = date('Y-m-d', strtotime("-{$workflow->auto_archive_days} days"));

            $count = \Illuminate\Database\Capsule\Manager::table('ahg_workflow_task')
                ->where('workflow_id', $workflow->id)
                ->whereIn('status', ['approved', 'rejected', 'cancelled'])
                ->where('updated_at', '<', $workflowCutoff)
                ->delete();

            $archived += $count;
        }

        // Also clean up tasks based on command parameter
        $count = \Illuminate\Database\Capsule\Manager::table('ahg_workflow_task')
            ->whereIn('status', ['approved', 'rejected', 'cancelled'])
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        $archived += $count;

        $this->logSection('cleanup', "Archived {$archived} old tasks");
    }
}
