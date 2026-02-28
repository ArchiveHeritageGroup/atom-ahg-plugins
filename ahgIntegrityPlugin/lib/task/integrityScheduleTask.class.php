<?php

use Illuminate\Database\Capsule\Manager as DB;

class integrityScheduleTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('list', null, sfCommandOption::PARAMETER_NONE, 'List all schedules'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show schedule status summary'),
            new sfCommandOption('run-due', null, sfCommandOption::PARAMETER_NONE, 'Run all due schedules (for cron)'),
            new sfCommandOption('run-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Run a specific schedule by ID'),
            new sfCommandOption('enable', null, sfCommandOption::PARAMETER_OPTIONAL, 'Enable a schedule by ID'),
            new sfCommandOption('disable', null, sfCommandOption::PARAMETER_OPTIONAL, 'Disable a schedule by ID'),
        ]);

        $this->namespace = 'integrity';
        $this->name = 'schedule';
        $this->briefDescription = 'Manage integrity verification schedules';
        $this->detailedDescription = <<<'EOF'
Manage and run integrity verification schedules. Use --run-due in a cron job
to automatically process all due schedules.

Examples:
  php symfony integrity:schedule --list
  php symfony integrity:schedule --status
  php symfony integrity:schedule --run-due
  php symfony integrity:schedule --run-id=1
  php symfony integrity:schedule --enable=1
  php symfony integrity:schedule --disable=2

Recommended cron entry:
  */15 * * * * cd {root} && php symfony integrity:schedule --run-due >> /tmp/integrity-scheduler.log 2>&1
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once dirname(__DIR__) . '/Services/IntegrityScheduler.php';
        require_once dirname(__DIR__) . '/Services/IntegrityService.php';

        // List schedules
        if (!empty($options['list'])) {
            $this->listSchedules();

            return;
        }

        // Status summary
        if (!empty($options['status'])) {
            $this->showStatus();

            return;
        }

        // Enable/disable
        if (!empty($options['enable'])) {
            $this->toggleScheduleState((int) $options['enable'], true);

            return;
        }

        if (!empty($options['disable'])) {
            $this->toggleScheduleState((int) $options['disable'], false);

            return;
        }

        // Run specific schedule
        if (!empty($options['run-id'])) {
            $scheduleId = (int) $options['run-id'];
            $this->logSection('schedule', "Running schedule #{$scheduleId}...");

            $service = new IntegrityService();

            try {
                $result = $service->executeBatchVerification($scheduleId, 'cli');
                $this->logSection('schedule', "Completed: run #{$result['run_id']} - {$result['status']}", null, 'INFO');
                $this->logSection('schedule', "  Scanned: {$result['counters']['objects_scanned']}, Passed: {$result['counters']['objects_passed']}, Failed: {$result['counters']['objects_failed']}");
            } catch (\Exception $e) {
                $this->logSection('schedule', 'ERROR: ' . $e->getMessage(), null, 'ERROR');
            }

            return;
        }

        // Run due schedules (cron mode)
        if (!empty($options['run-due'])) {
            $scheduler = new IntegrityScheduler();
            $due = $scheduler->getDueSchedules();

            if (empty($due)) {
                $this->logSection('schedule', 'No schedules due');

                return;
            }

            $this->logSection('schedule', count($due) . ' schedule(s) due');
            $results = $scheduler->runDueSchedules();

            foreach ($results as $r) {
                if ($r['success']) {
                    $this->logSection('schedule',
                        "#{$r['schedule_id']} '{$r['schedule_name']}': {$r['result']['status']} " .
                        "(scanned: {$r['result']['counters']['objects_scanned']}, " .
                        "passed: {$r['result']['counters']['objects_passed']}, " .
                        "failed: {$r['result']['counters']['objects_failed']})", null, 'INFO');
                } else {
                    $this->logSection('schedule',
                        "#{$r['schedule_id']} '{$r['schedule_name']}': FAILED - {$r['error']}", null, 'ERROR');
                }
            }

            return;
        }

        // Default: show help
        $this->logSection('schedule', 'Use --list, --status, --run-due, --run-id=N, --enable=N, or --disable=N');
    }

    protected function listSchedules(): void
    {
        $schedules = DB::table('integrity_schedule')
            ->orderBy('id')
            ->get();

        if ($schedules->isEmpty()) {
            $this->logSection('schedule', 'No schedules configured');

            return;
        }

        $this->logSection('schedule', 'Integrity Verification Schedules', null, 'INFO');
        $this->logSection('schedule', '');

        foreach ($schedules as $s) {
            $status = $s->is_enabled ? 'ENABLED' : 'DISABLED';
            $style = $s->is_enabled ? 'INFO' : 'COMMENT';

            $this->logSection('schedule', "#{$s->id} {$s->name} [{$status}]", null, $style);
            $this->logSection('schedule', "    Scope: {$s->scope_type}" .
                ($s->repository_id ? " (repo #{$s->repository_id})" : '') .
                ($s->information_object_id ? " (io #{$s->information_object_id})" : ''));
            $this->logSection('schedule', "    Frequency: {$s->frequency} | Algorithm: {$s->algorithm} | Batch: {$s->batch_size}");
            $this->logSection('schedule', "    Last run: " . ($s->last_run_at ?: 'Never') . " | Next: " . ($s->next_run_at ?: 'N/A'));
            $this->logSection('schedule', "    Total runs: {$s->total_runs}");
            $this->logSection('schedule', '');
        }
    }

    protected function showStatus(): void
    {
        $total = DB::table('integrity_schedule')->count();
        $enabled = DB::table('integrity_schedule')->where('is_enabled', 1)->count();

        $scheduler = new IntegrityScheduler();
        $due = $scheduler->getDueSchedules();

        $running = DB::table('integrity_run')->where('status', 'running')->count();

        $this->logSection('schedule', 'Schedule Status', null, 'INFO');
        $this->logSection('schedule', "  Total schedules:   {$total}");
        $this->logSection('schedule', "  Enabled:           {$enabled}");
        $this->logSection('schedule', "  Currently due:     " . count($due));
        $this->logSection('schedule', "  Active runs:       {$running}");
    }

    protected function toggleScheduleState(int $id, bool $enable): void
    {
        $schedule = DB::table('integrity_schedule')->where('id', $id)->first();
        if (!$schedule) {
            $this->logSection('schedule', "Schedule #{$id} not found", null, 'ERROR');

            return;
        }

        $data = ['is_enabled' => $enable ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')];

        if ($enable && !$schedule->next_run_at) {
            $scheduler = new IntegrityScheduler();
            $data['next_run_at'] = $scheduler->computeNextRun($schedule->frequency, $schedule->cron_expression);
        }

        DB::table('integrity_schedule')->where('id', $id)->update($data);

        $action = $enable ? 'ENABLED' : 'DISABLED';
        $this->logSection('schedule', "Schedule #{$id} '{$schedule->name}': {$action}", null, 'INFO');
    }
}
