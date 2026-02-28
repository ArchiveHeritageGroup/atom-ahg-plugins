<?php

use Illuminate\Database\Capsule\Manager as DB;

class integrityVerifyTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Verify a specific digital object ID'),
            new sfCommandOption('schedule-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Run a specific schedule'),
            new sfCommandOption('repository-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Verify objects in a specific repository'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum objects to verify', 200),
            new sfCommandOption('stale-days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Only verify objects not checked in N days', 7),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Verify all master digital objects'),
            new sfCommandOption('throttle', null, sfCommandOption::PARAMETER_OPTIONAL, 'IO throttle in ms between objects', 10),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show current verification status'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be verified without verifying'),
        ]);

        $this->namespace = 'integrity';
        $this->name = 'verify';
        $this->briefDescription = 'Run fixity verification on digital objects';
        $this->detailedDescription = <<<'EOF'
Verifies the integrity of digital objects by comparing stored checksums
against computed hashes. Delegates hash operations to PreservationService.

Results are recorded in the append-only integrity_ledger table.
Objects that fail repeatedly are escalated to the dead-letter queue.

Examples:
  php symfony integrity:verify --status
  php symfony integrity:verify --object-id=123
  php symfony integrity:verify --schedule-id=1
  php symfony integrity:verify --repository-id=5 --limit=50
  php symfony integrity:verify --limit=100 --stale-days=3
  php symfony integrity:verify --all --throttle=20
  php symfony integrity:verify --dry-run --limit=10
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once dirname(__DIR__) . '/Services/IntegrityService.php';
        $service = new IntegrityService();

        // Status mode
        if (!empty($options['status'])) {
            $this->showStatus($service);

            return;
        }

        // Single object verification
        if (!empty($options['object-id'])) {
            $objectId = (int) $options['object-id'];
            $this->logSection('integrity', "Verifying digital object ID: {$objectId}");

            if (!empty($options['dry-run'])) {
                $this->logSection('integrity', '[DRY RUN] Would verify object #' . $objectId);

                return;
            }

            $result = $service->verifyByObjectId($objectId, 'sha256', 'cli');
            $this->logResult($result);

            return;
        }

        // Schedule-based verification
        if (!empty($options['schedule-id'])) {
            $scheduleId = (int) $options['schedule-id'];
            $this->logSection('integrity', "Running schedule #{$scheduleId}...");

            if (!empty($options['dry-run'])) {
                $schedule = DB::table('integrity_schedule')->where('id', $scheduleId)->first();
                if (!$schedule) {
                    $this->logSection('integrity', "Schedule #{$scheduleId} not found", null, 'ERROR');

                    return;
                }
                $count = $service->buildScopeQuery($schedule)->count();
                $this->logSection('integrity', "[DRY RUN] Would verify {$count} objects for schedule '{$schedule->name}'");

                return;
            }

            $result = $service->executeBatchVerification($scheduleId, 'cli');
            $this->logBatchResult($result);

            return;
        }

        // Ad-hoc batch verification
        $limit = !empty($options['all']) ? 0 : (int) ($options['limit'] ?? 200);
        $staleDays = (int) ($options['stale-days'] ?? 7);
        $throttle = (int) ($options['throttle'] ?? 10);
        $repositoryId = !empty($options['repository-id']) ? (int) $options['repository-id'] : null;

        // Build query for objects needing verification
        $query = DB::table('digital_object as do')
            ->where('do.usage_id', 140);

        if ($repositoryId) {
            $query->join('information_object as io', 'do.object_id', '=', 'io.id')
                ->where('io.repository_id', $repositoryId);
        }

        if ($staleDays > 0) {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$staleDays} days"));
            $query->leftJoin('integrity_ledger as il', function ($join) {
                $join->on('il.digital_object_id', '=', 'do.id')
                    ->where('il.outcome', '=', 'pass');
            })
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('il.verified_at')
                  ->orWhere('il.verified_at', '<', $cutoff);
            });
        }

        $query->select('do.id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        if (!empty($options['dry-run'])) {
            $count = (clone $query)->count();
            $this->logSection('integrity', "[DRY RUN] Would verify {$count} objects" .
                ($repositoryId ? " in repository #{$repositoryId}" : '') .
                ($staleDays > 0 ? " (stale > {$staleDays} days)" : ''));

            return;
        }

        $objects = $query->get();
        $total = count($objects);
        $this->logSection('integrity', "Starting verification of {$total} objects...");

        $now = date('Y-m-d H:i:s');
        $runId = DB::table('integrity_run')->insertGetId([
            'schedule_id' => null,
            'status' => 'running',
            'algorithm' => 'sha256',
            'triggered_by' => 'cli',
            'started_at' => $now,
            'created_at' => $now,
        ]);

        $passed = 0;
        $failed = 0;
        $errors = 0;
        $bytes = 0;

        foreach ($objects as $idx => $obj) {
            $result = $service->verifyObject($obj->id, $runId, 'sha256');
            $outcome = $result['outcome'] ?? 'error';

            if ($outcome === 'pass') {
                $passed++;
            } elseif ($outcome === 'mismatch') {
                $failed++;
                $this->logSection('integrity', "MISMATCH: object #{$obj->id}", null, 'ERROR');
            } else {
                $errors++;
                if ($outcome !== 'no_baseline') {
                    $this->logSection('integrity', strtoupper($outcome) . ": object #{$obj->id}", null, 'COMMENT');
                }
            }

            $bytes += (int) ($result['file_size'] ?? 0);

            if (($idx + 1) % 50 === 0) {
                $this->logSection('integrity', "Progress: " . ($idx + 1) . "/{$total}");
            }

            if ($throttle > 0) {
                usleep($throttle * 1000);
            }
        }

        DB::table('integrity_run')->where('id', $runId)->update([
            'status' => 'completed',
            'objects_scanned' => $total,
            'objects_passed' => $passed,
            'objects_failed' => $failed,
            'objects_error' => $errors,
            'bytes_scanned' => $bytes,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logSection('integrity', '');
        $this->logSection('integrity', "Verification complete (run #{$runId}):", null, 'INFO');
        $this->logSection('integrity', "  Scanned:  {$total}");
        $this->logSection('integrity', "  Passed:   {$passed}");
        $this->logSection('integrity', "  Failed:   {$failed}");
        $this->logSection('integrity', "  Errors:   {$errors}");
        $this->logSection('integrity', "  Bytes:    " . $this->formatBytes($bytes));
    }

    protected function showStatus(IntegrityService $service): void
    {
        $stats = $service->getDashboardStats();

        $this->logSection('integrity', 'Integrity Verification Status', null, 'INFO');
        $this->logSection('integrity', '');
        $this->logSection('integrity', "  Master objects:       {$stats['total_master_objects']}");
        $this->logSection('integrity', "  Total verifications:  {$stats['total_verifications']}");
        $this->logSection('integrity', "  Pass rate:            " . ($stats['pass_rate'] !== null ? $stats['pass_rate'] . '%' : 'N/A'));
        $this->logSection('integrity', "  Open dead letters:    {$stats['open_dead_letters']}");
        $this->logSection('integrity', "  Schedules:            {$stats['enabled_schedules']}/{$stats['schedule_count']} enabled");

        if ($stats['last_run']) {
            $this->logSection('integrity', "  Last run:             {$stats['last_run']->started_at} ({$stats['last_run']->status})");
        } else {
            $this->logSection('integrity', '  Last run:             Never');
        }

        if (!empty($stats['recent_outcomes'])) {
            $this->logSection('integrity', '');
            $this->logSection('integrity', '  Last 30 days:');
            foreach ($stats['recent_outcomes'] as $outcome => $count) {
                $this->logSection('integrity', "    {$outcome}: {$count}");
            }
        }
    }

    protected function logResult(array $result): void
    {
        $outcome = $result['outcome'] ?? 'unknown';
        $style = $outcome === 'pass' ? 'INFO' : 'ERROR';
        $this->logSection('integrity', strtoupper($outcome), null, $style);

        if (!empty($result['file_path'])) {
            $this->logSection('integrity', "  Path: {$result['file_path']}");
        }
        if (!empty($result['expected_hash'])) {
            $this->logSection('integrity', "  Expected: {$result['expected_hash']}");
        }
        if (!empty($result['computed_hash'])) {
            $this->logSection('integrity', "  Computed: {$result['computed_hash']}");
        }
        if (!empty($result['error_detail'])) {
            $this->logSection('integrity', "  Error: {$result['error_detail']}");
        }
    }

    protected function logBatchResult(array $result): void
    {
        $style = $result['status'] === 'completed' ? 'INFO' : 'ERROR';
        $this->logSection('integrity', "Run #{$result['run_id']}: {$result['status']}", null, $style);
        $this->logSection('integrity', "  Duration: {$result['duration_seconds']}s");

        $c = $result['counters'];
        $this->logSection('integrity', "  Scanned:  {$c['objects_scanned']}");
        $this->logSection('integrity', "  Passed:   {$c['objects_passed']}");
        $this->logSection('integrity', "  Failed:   {$c['objects_failed']}");
        $this->logSection('integrity', "  Missing:  {$c['objects_missing']}");
        $this->logSection('integrity', "  Errors:   {$c['objects_error']}");
        $this->logSection('integrity', "  Skipped:  {$c['objects_skipped']}");
        $this->logSection('integrity', "  Bytes:    " . $this->formatBytes($c['bytes_scanned']));

        if ($result['error']) {
            $this->logSection('integrity', "  Error: {$result['error']}", null, 'ERROR');
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB', 'TB'];
        $exp = min((int) floor(log($bytes, 1024)), count($units));

        return round($bytes / pow(1024, $exp), 2) . ' ' . $units[$exp - 1];
    }
}
