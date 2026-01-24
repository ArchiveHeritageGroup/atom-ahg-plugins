<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to run scheduled preservation workflows.
 *
 * This task should be run via cron every minute to check for and execute
 * any workflows that are due to run.
 */
class preservationSchedulerTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show scheduler status'),
            new sfCommandOption('list', null, sfCommandOption::PARAMETER_NONE, 'List all configured schedules'),
            new sfCommandOption('run-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Run a specific schedule by ID'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be run without executing'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'scheduler';
        $this->briefDescription = 'Run scheduled preservation workflows';
        $this->detailedDescription = <<<EOF
Executes scheduled preservation workflows based on their configured schedules.

This task should be run via cron every minute:
  * * * * * cd ' . sfConfig::get('sf_root_dir') . ' && php symfony preservation:scheduler

Examples:
  php symfony preservation:scheduler              # Run due workflows
  php symfony preservation:scheduler --status     # Show scheduler status
  php symfony preservation:scheduler --list       # List all schedules
  php symfony preservation:scheduler --run-id=1   # Run specific schedule
  php symfony preservation:scheduler --dry-run    # Preview without running
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once dirname(__DIR__).'/PreservationService.php';

        $service = new PreservationService();

        // Status check
        if ($options['status']) {
            $this->showStatus($service);

            return;
        }

        // List schedules
        if ($options['list']) {
            $this->listSchedules($service);

            return;
        }

        $dryRun = !empty($options['dry-run']);

        // Run specific schedule
        if (!empty($options['run-id'])) {
            $scheduleId = (int) $options['run-id'];
            $this->runSchedule($service, $scheduleId, $dryRun);

            return;
        }

        // Run all due schedules
        $this->runDueSchedules($service, $dryRun);
    }

    protected function showStatus($service)
    {
        $stats = $service->getSchedulerStatistics();

        $this->logSection('scheduler', 'Workflow Scheduler Status');
        $this->logSection('scheduler', '');
        $this->logSection('scheduler', 'Schedules:');
        $this->logSection('scheduler', "  Total: {$stats['total_schedules']}");
        $this->logSection('scheduler', "  Enabled: {$stats['enabled_schedules']}");
        $this->logSection('scheduler', "  Disabled: {$stats['disabled_schedules']}");
        $this->logSection('scheduler', '');
        $this->logSection('scheduler', 'Last 24 Hours:');
        $this->logSection('scheduler', "  Runs: {$stats['last_24h_runs']}");
        $this->logSection('scheduler', "  Successful: {$stats['last_24h_success']}");
        $this->logSection('scheduler', "  Failed: {$stats['last_24h_failed']}");

        if (!empty($stats['by_type'])) {
            $this->logSection('scheduler', '');
            $this->logSection('scheduler', 'By Type:');
            foreach ($stats['by_type'] as $type => $counts) {
                $typeInfo = $service->getWorkflowTypeInfo($type);
                $this->logSection('scheduler', "  {$typeInfo['label']}: {$counts['enabled']}/{$counts['total']} enabled");
            }
        }

        if (!empty($stats['upcoming'])) {
            $this->logSection('scheduler', '');
            $this->logSection('scheduler', 'Upcoming Runs:');
            foreach ($stats['upcoming'] as $schedule) {
                $this->logSection('scheduler', "  {$schedule->name}: {$schedule->next_run_at}");
            }
        }
    }

    protected function listSchedules($service)
    {
        $schedules = $service->getWorkflowSchedules();

        $this->logSection('scheduler', 'Configured Schedules');
        $this->logSection('scheduler', '');

        if (empty($schedules)) {
            $this->logSection('scheduler', 'No schedules configured.');

            return;
        }

        foreach ($schedules as $schedule) {
            $status = $schedule->is_enabled ? 'ENABLED' : 'DISABLED';
            $statusStyle = $schedule->is_enabled ? 'INFO' : 'COMMENT';

            $this->logSection('scheduler', "[{$schedule->id}] {$schedule->name}", null, $statusStyle);
            $this->logSection('scheduler', "     Type: {$schedule->workflow_type}");
            $this->logSection('scheduler', "     Cron: {$schedule->cron_expression}");
            $this->logSection('scheduler', "     Limit: {$schedule->batch_limit} objects");
            $this->logSection('scheduler', "     Status: {$status}");

            if ($schedule->next_run_at) {
                $this->logSection('scheduler', "     Next Run: {$schedule->next_run_at}");
            }

            if ($schedule->last_run_at) {
                $this->logSection('scheduler', "     Last Run: {$schedule->last_run_at} ({$schedule->last_run_status})");
            }

            $this->logSection('scheduler', '');
        }
    }

    protected function runSchedule($service, $scheduleId, $dryRun)
    {
        $schedule = $service->getWorkflowSchedule($scheduleId);

        if (!$schedule) {
            $this->logSection('scheduler', "Schedule not found: {$scheduleId}", null, 'ERROR');

            return 1;
        }

        $typeInfo = $service->getWorkflowTypeInfo($schedule->workflow_type);
        $this->logSection('scheduler', "Running: {$schedule->name} ({$typeInfo['label']})");

        if ($dryRun) {
            $this->logSection('scheduler', "[DRY RUN] Would execute {$schedule->workflow_type} with limit={$schedule->batch_limit}", null, 'COMMENT');

            return;
        }

        $this->logSection('scheduler', "  Workflow: {$schedule->workflow_type}");
        $this->logSection('scheduler', "  Limit: {$schedule->batch_limit}");

        try {
            $result = $service->executeWorkflow($scheduleId, 'scheduler');

            if ($result['success']) {
                $this->logSection('scheduler', 'COMPLETED', null, 'INFO');
                $this->logSection('scheduler', "  Processed: {$result['results']['processed']}");
                $this->logSection('scheduler', "  Succeeded: {$result['results']['succeeded']}");
                $this->logSection('scheduler', "  Failed: {$result['results']['failed']}");
            } else {
                $this->logSection('scheduler', "FAILED: {$result['results']['error']}", null, 'ERROR');

                return 1;
            }
        } catch (Exception $e) {
            $this->logSection('scheduler', "ERROR: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }

    protected function runDueSchedules($service, $dryRun)
    {
        $dueSchedules = $service->getDueSchedules();

        if (empty($dueSchedules)) {
            $this->logSection('scheduler', 'No workflows due to run.');

            return;
        }

        $this->logSection('scheduler', 'Found '.count($dueSchedules).' workflow(s) due to run'.($dryRun ? ' [DRY RUN]' : ''));
        $this->logSection('scheduler', '');

        $succeeded = 0;
        $failed = 0;

        foreach ($dueSchedules as $schedule) {
            $typeInfo = $service->getWorkflowTypeInfo($schedule->workflow_type);
            $this->logSection('scheduler', "[{$schedule->id}] {$schedule->name} ({$typeInfo['label']})");

            if ($dryRun) {
                $this->logSection('scheduler', "  [WOULD RUN] limit={$schedule->batch_limit}", null, 'COMMENT');

                continue;
            }

            try {
                $startTime = microtime(true);
                $result = $service->executeWorkflow($schedule->id, 'scheduler');
                $duration = round(microtime(true) - $startTime, 2);

                if ($result['success']) {
                    $this->logSection('scheduler', "  SUCCESS: {$result['results']['processed']} processed, {$result['results']['succeeded']} succeeded, {$result['results']['failed']} failed ({$duration}s)", null, 'INFO');
                    ++$succeeded;
                } else {
                    $this->logSection('scheduler', "  FAILED: {$result['results']['error']}", null, 'ERROR');
                    ++$failed;
                }
            } catch (Exception $e) {
                $this->logSection('scheduler', "  ERROR: {$e->getMessage()}", null, 'ERROR');
                ++$failed;
            }

            $this->logSection('scheduler', '');
        }

        if (!$dryRun) {
            $this->logSection('scheduler', '');
            $this->logSection('scheduler', "Completed: {$succeeded} succeeded, {$failed} failed");
        }
    }
}
