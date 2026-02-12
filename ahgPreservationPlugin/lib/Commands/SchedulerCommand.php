<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class SchedulerCommand extends BaseCommand
{
    protected string $name = 'preservation:scheduler';
    protected string $description = 'Run scheduled preservation workflows';
    protected string $detailedDescription = <<<'EOF'
Executes scheduled preservation workflows based on their configured schedules.

This task should be run via cron every minute:
  * * * * * cd /usr/share/nginx/archive && php bin/atom preservation:scheduler

Examples:
  php bin/atom preservation:scheduler              # Run due workflows
  php bin/atom preservation:scheduler --status     # Show scheduler status
  php bin/atom preservation:scheduler --list       # List all schedules
  php bin/atom preservation:scheduler --run-id=1   # Run specific schedule
  php bin/atom preservation:scheduler --dry-run    # Preview without running
EOF;

    protected function configure(): void
    {
        $this->addOption('status', 's', 'Show scheduler status');
        $this->addOption('list', null, 'List all configured schedules');
        $this->addOption('run-id', null, 'Run a specific schedule by ID');
        $this->addOption('dry-run', null, 'Show what would be run without executing');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();

        // Status check
        if ($this->hasOption('status')) {
            $this->showStatus($service);

            return 0;
        }

        // List schedules
        if ($this->hasOption('list')) {
            $this->listSchedules($service);

            return 0;
        }

        $dryRun = $this->hasOption('dry-run');

        // Run specific schedule
        if ($this->hasOption('run-id')) {
            $scheduleId = (int) $this->option('run-id');

            return $this->runSchedule($service, $scheduleId, $dryRun);
        }

        // Run all due schedules
        return $this->runDueSchedules($service, $dryRun);
    }

    private function showStatus(\PreservationService $service): void
    {
        $stats = $service->getSchedulerStatistics();

        $this->bold('Workflow Scheduler Status');
        $this->newline();
        $this->info('Schedules:');
        $this->line("  Total: {$stats['total_schedules']}");
        $this->line("  Enabled: {$stats['enabled_schedules']}");
        $this->line("  Disabled: {$stats['disabled_schedules']}");
        $this->newline();
        $this->info('Last 24 Hours:');
        $this->line("  Runs: {$stats['last_24h_runs']}");
        $this->line("  Successful: {$stats['last_24h_success']}");
        $this->line("  Failed: {$stats['last_24h_failed']}");

        if (!empty($stats['by_type'])) {
            $this->newline();
            $this->info('By Type:');
            foreach ($stats['by_type'] as $type => $counts) {
                $typeInfo = $service->getWorkflowTypeInfo($type);
                $this->line("  {$typeInfo['label']}: {$counts['enabled']}/{$counts['total']} enabled");
            }
        }

        if (!empty($stats['upcoming'])) {
            $this->newline();
            $this->info('Upcoming Runs:');
            foreach ($stats['upcoming'] as $schedule) {
                $this->line("  {$schedule->name}: {$schedule->next_run_at}");
            }
        }
    }

    private function listSchedules(\PreservationService $service): void
    {
        $schedules = $service->getWorkflowSchedules();

        $this->bold('Configured Schedules');
        $this->newline();

        if (empty($schedules)) {
            $this->info('No schedules configured.');

            return;
        }

        foreach ($schedules as $schedule) {
            $status = $schedule->is_enabled ? 'ENABLED' : 'DISABLED';

            if ($schedule->is_enabled) {
                $this->info("[{$schedule->id}] {$schedule->name}");
            } else {
                $this->comment("[{$schedule->id}] {$schedule->name}");
            }

            $this->line("     Type: {$schedule->workflow_type}");
            $this->line("     Cron: {$schedule->cron_expression}");
            $this->line("     Limit: {$schedule->batch_limit} objects");
            $this->line("     Status: {$status}");

            if ($schedule->next_run_at) {
                $this->line("     Next Run: {$schedule->next_run_at}");
            }

            if ($schedule->last_run_at) {
                $this->line("     Last Run: {$schedule->last_run_at} ({$schedule->last_run_status})");
            }

            $this->newline();
        }
    }

    private function runSchedule(\PreservationService $service, int $scheduleId, bool $dryRun): int
    {
        $schedule = $service->getWorkflowSchedule($scheduleId);

        if (!$schedule) {
            $this->error("Schedule not found: {$scheduleId}");

            return 1;
        }

        $typeInfo = $service->getWorkflowTypeInfo($schedule->workflow_type);
        $this->info("Running: {$schedule->name} ({$typeInfo['label']})");

        if ($dryRun) {
            $this->comment("[DRY RUN] Would execute {$schedule->workflow_type} with limit={$schedule->batch_limit}");

            return 0;
        }

        $this->line("  Workflow: {$schedule->workflow_type}");
        $this->line("  Limit: {$schedule->batch_limit}");

        try {
            $result = $service->executeWorkflow($scheduleId, 'scheduler');

            if ($result['success']) {
                $this->success('COMPLETED');
                $this->line("  Processed: {$result['results']['processed']}");
                $this->line("  Succeeded: {$result['results']['succeeded']}");
                $this->line("  Failed: {$result['results']['failed']}");
            } else {
                $this->error("FAILED: {$result['results']['error']}");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("ERROR: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function runDueSchedules(\PreservationService $service, bool $dryRun): int
    {
        $dueSchedules = $service->getDueSchedules();

        if (empty($dueSchedules)) {
            $this->info('No workflows due to run.');

            return 0;
        }

        $this->info('Found ' . count($dueSchedules) . ' workflow(s) due to run' . ($dryRun ? ' [DRY RUN]' : ''));
        $this->newline();

        $succeeded = 0;
        $failed = 0;

        foreach ($dueSchedules as $schedule) {
            $typeInfo = $service->getWorkflowTypeInfo($schedule->workflow_type);
            $this->line("[{$schedule->id}] {$schedule->name} ({$typeInfo['label']})");

            if ($dryRun) {
                $this->comment("  [WOULD RUN] limit={$schedule->batch_limit}");

                continue;
            }

            try {
                $startTime = microtime(true);
                $result = $service->executeWorkflow($schedule->id, 'scheduler');
                $duration = round(microtime(true) - $startTime, 2);

                if ($result['success']) {
                    $this->success("  SUCCESS: {$result['results']['processed']} processed, {$result['results']['succeeded']} succeeded, {$result['results']['failed']} failed ({$duration}s)");
                    ++$succeeded;
                } else {
                    $this->error("  FAILED: {$result['results']['error']}");
                    ++$failed;
                }
            } catch (\Exception $e) {
                $this->error("  ERROR: {$e->getMessage()}");
                ++$failed;
            }

            $this->newline();
        }

        if (!$dryRun) {
            $this->newline();
            $this->line("Completed: {$succeeded} succeeded, {$failed} failed");
        }

        return 0;
    }
}
