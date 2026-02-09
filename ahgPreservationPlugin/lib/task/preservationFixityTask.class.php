<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to perform fixity checks on digital objects.
 */
class preservationFixityTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific digital object ID to check'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number of objects to check', 100),
            new sfCommandOption('stale-days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Check objects not verified in N days', 30),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Check all objects regardless of last check'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show fixity check statistics'),
            new sfCommandOption('failed-only', null, sfCommandOption::PARAMETER_NONE, 'Only show failed checks'),
            new sfCommandOption('auto-repair', null, sfCommandOption::PARAMETER_NONE, 'Enable self-healing auto-repair from backups'),
            new sfCommandOption('repair-stats', null, sfCommandOption::PARAMETER_NONE, 'Show self-healing repair statistics'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'fixity';
        $this->briefDescription = 'Verify file integrity using checksums';
        $this->detailedDescription = <<<EOF
Performs fixity checks on digital objects by verifying their checksums.

Examples:
  php symfony preservation:fixity                       # Check stale objects (>30 days)
  php symfony preservation:fixity --status              # Show fixity statistics
  php symfony preservation:fixity --repair-stats        # Show self-healing statistics
  php symfony preservation:fixity --object-id=123       # Check specific object
  php symfony preservation:fixity --stale-days=7        # Check objects not checked in 7 days
  php symfony preservation:fixity --all --limit=500     # Check all objects
  php symfony preservation:fixity --auto-repair         # Enable self-healing from backups
  php symfony preservation:fixity --object-id=123 --auto-repair  # Check and repair if needed

Fixity checks:
  - Recalculates SHA-256 checksum of file
  - Compares against stored checksum
  - Logs result as PREMIS event

Self-Healing (--auto-repair):
  - Automatically attempts repair when checksum fails
  - Searches configured replication targets for valid backup
  - Restores corrupted file from backup
  - Logs repair events with PREMIS provenance
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once dirname(__DIR__).'/PreservationService.php';

        $service = new PreservationService();

        // Repair statistics
        if ($options['repair-stats']) {
            $this->showRepairStats($service);

            return;
        }

        // Status check
        if ($options['status']) {
            $this->showStatus($options['failed-only'] ?? false);

            return;
        }

        $limit = (int) ($options['limit'] ?? 100);
        $staleDays = (int) ($options['stale-days'] ?? 30);
        $checkAll = isset($options['all']);
        $autoRepair = isset($options['auto-repair']);

        if ($autoRepair) {
            $this->logSection('fixity', 'Self-healing auto-repair is ENABLED');
        }

        // Single object check
        if (!empty($options['object-id'])) {
            $objectId = (int) $options['object-id'];
            $this->logSection('fixity', "Checking fixity for object ID: $objectId");

            if ($autoRepair) {
                $results = $service->verifyFixityWithRepair($objectId, 'sha256', 'cli-task', true);
            } else {
                $results = $service->verifyFixity($objectId, 'sha256', 'cli-task');
            }

            // Handle result array format
            foreach ($results as $algo => $result) {
                if ('pass' === $result['status']) {
                    $this->logSection('fixity', "PASSED ($algo) - Checksum verified", null, 'INFO');
                    $this->logSection('fixity', "  Checksum: {$result['expected']}");

                    if (!empty($result['repaired'])) {
                        $this->logSection('fixity', "  REPAIRED from: {$result['repair_source']}", null, 'INFO');
                    }
                } elseif ('fail' === $result['status']) {
                    $this->logSection('fixity', "FAILED ($algo) - Checksum mismatch!", null, 'ERROR');
                    $this->logSection('fixity', "  Expected: {$result['expected']}");
                    $this->logSection('fixity', "  Actual:   {$result['actual']}");

                    if (!empty($result['repair_failed'])) {
                        $this->logSection('fixity', "  Repair FAILED: {$result['repair_error']}", null, 'ERROR');
                    }
                } elseif ('missing' === $result['status']) {
                    $this->logSection('fixity', "MISSING ($algo) - File not found", null, 'ERROR');
                } else {
                    $this->logSection('fixity', "ERROR ($algo) - {$result['error']}", null, 'ERROR');
                }
            }

            return;
        }

        // Batch fixity check
        $this->logSection('fixity', 'Starting batch fixity check...');

        // Get objects needing check
        $query = DB::table('digital_object as do')
            ->leftJoin('preservation_fixity_check as pf', function ($join) {
                $join->on('do.id', '=', 'pf.digital_object_id')
                    ->where('pf.algorithm', '=', 'sha256');
            })
            ->where('do.usage_id', 140) // Masters only
            ->select('do.id', 'do.name', 'pf.checked_at', 'pf.status');

        if (!$checkAll) {
            // Only get stale or never-checked objects
            $staleDate = date('Y-m-d H:i:s', strtotime("-$staleDays days"));
            $query->where(function ($q) use ($staleDate) {
                $q->whereNull('pf.checked_at')
                  ->orWhere('pf.checked_at', '<', $staleDate);
            });
        }

        $objects = $query->orderBy('pf.checked_at', 'asc')
            ->limit($limit)
            ->get();

        if ($objects->isEmpty()) {
            $this->logSection('fixity', 'No objects need fixity checking');

            return;
        }

        $this->logSection('fixity', "Checking {$objects->count()} objects...");
        $this->logSection('fixity', '');

        $passed = 0;
        $failed = 0;
        $repaired = 0;
        $repairFailed = 0;
        $errors = 0;

        foreach ($objects as $obj) {
            if ($autoRepair) {
                $results = $service->verifyFixityWithRepair($obj->id, 'sha256', 'cli-task', true);
            } else {
                $results = $service->verifyFixity($obj->id, 'sha256', 'cli-task');
            }

            // Process results
            $objPassed = true;
            $objRepaired = false;
            $objRepairFailed = false;
            $objError = null;

            foreach ($results as $algo => $result) {
                if ('pass' !== $result['status']) {
                    $objPassed = false;
                }
                if (!empty($result['repaired'])) {
                    $objRepaired = true;
                    $objPassed = true; // File was repaired and is now valid
                }
                if (!empty($result['repair_failed'])) {
                    $objRepairFailed = true;
                }
                if (!empty($result['error'])) {
                    $objError = $result['error'];
                }
            }

            if ($objError) {
                $this->logSection('fixity', "Object {$obj->id}: ERROR - {$objError}", null, 'ERROR');
                ++$errors;
            } elseif ($objRepaired) {
                $source = $results['sha256']['repair_source'] ?? 'backup';
                $this->logSection('fixity', "Object {$obj->id}: REPAIRED from $source", null, 'INFO');
                ++$repaired;
            } elseif ($objRepairFailed) {
                $error = $results['sha256']['repair_error'] ?? 'Unknown';
                $this->logSection('fixity', "Object {$obj->id}: REPAIR FAILED - $error", null, 'ERROR');
                ++$repairFailed;
            } elseif ($objPassed) {
                $this->logSection('fixity', "Object {$obj->id}: PASSED", null, 'INFO');
                ++$passed;
            } else {
                $this->logSection('fixity', "Object {$obj->id}: FAILED - Checksum mismatch!", null, 'ERROR');
                ++$failed;
            }
        }

        $this->logSection('fixity', '');
        $this->logSection('fixity', 'Fixity Check Complete:');
        $this->logSection('fixity', "  Passed:        $passed");
        if ($autoRepair) {
            $this->logSection('fixity', "  Repaired:      $repaired");
            $this->logSection('fixity', "  Repair Failed: $repairFailed");
        }
        $this->logSection('fixity', "  Failed:        $failed");
        $this->logSection('fixity', "  Errors:        $errors");

        if ($failed > 0 || $repairFailed > 0) {
            $this->logSection('fixity', '');
            $this->logSection('fixity', 'WARNING: Some files have integrity issues!', null, 'ERROR');
            if (!$autoRepair) {
                $this->logSection('fixity', 'Run with --auto-repair to attempt restoration from backups.');
            } else {
                $this->logSection('fixity', 'Some files could not be repaired - check backup targets.');
            }
        }
    }

    protected function showStatus($failedOnly)
    {
        $this->logSection('fixity', 'Fixity Check Status');
        $this->logSection('fixity', '');

        // Overall statistics
        $stats = DB::table('preservation_fixity_check')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->logSection('fixity', 'Statistics:');
        $this->logSection('fixity', '  Passed: '.($stats['passed'] ?? 0));
        $this->logSection('fixity', '  Failed: '.($stats['failed'] ?? 0));
        $this->logSection('fixity', '  Pending: '.($stats['pending'] ?? 0));

        // Count objects never checked
        $neverChecked = DB::table('digital_object as do')
            ->leftJoin('preservation_fixity_check as pf', 'do.id', '=', 'pf.digital_object_id')
            ->where('do.usage_id', 140)
            ->whereNull('pf.id')
            ->count();

        $this->logSection('fixity', '  Never checked: '.$neverChecked);

        // Stale checks (>30 days)
        $staleDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        $stale = DB::table('preservation_fixity_check')
            ->where('checked_at', '<', $staleDate)
            ->count();

        $this->logSection('fixity', '  Stale (>30 days): '.$stale);

        // Recent failures
        $failures = DB::table('preservation_fixity_check as pf')
            ->join('digital_object as do', 'pf.digital_object_id', '=', 'do.id')
            ->where('pf.status', 'failed')
            ->orderBy('pf.checked_at', 'desc')
            ->select('do.id', 'do.name', 'pf.checked_at', 'pf.expected_value', 'pf.actual_value')
            ->limit(10)
            ->get();

        if (!$failures->isEmpty()) {
            $this->logSection('fixity', '');
            $this->logSection('fixity', 'Recent Failures:', null, 'ERROR');

            foreach ($failures as $f) {
                $this->logSection('fixity', "  [{$f->id}] {$f->name}");
                $this->logSection('fixity', "    Checked: {$f->checked_at}");
                $this->logSection('fixity', "    Expected: {$f->expected_value}");
                $this->logSection('fixity', "    Got: {$f->actual_value}");
            }
        }

        // Recent checks (unless showing failed only)
        if (!$failedOnly) {
            $recent = DB::table('preservation_fixity_check as pf')
                ->join('digital_object as do', 'pf.digital_object_id', '=', 'do.id')
                ->orderBy('pf.checked_at', 'desc')
                ->select('do.id', 'do.name', 'pf.status', 'pf.checked_at')
                ->limit(10)
                ->get();

            $this->logSection('fixity', '');
            $this->logSection('fixity', 'Recent Checks:');

            foreach ($recent as $r) {
                $status = strtoupper($r->status);
                $color = 'passed' === $r->status ? 'INFO' : 'ERROR';
                $this->logSection('fixity', "  [{$status}] {$r->id}: {$r->name} ({$r->checked_at})", null, $color);
            }
        }
    }

    /**
     * Show self-healing repair statistics
     */
    protected function showRepairStats(PreservationService $service)
    {
        $this->logSection('fixity', 'Self-Healing Repair Statistics');
        $this->logSection('fixity', '');

        $stats = $service->getSelfHealingStats(30);

        $this->logSection('fixity', "Period: Last {$stats['period_days']} days");
        $this->logSection('fixity', '');
        $this->logSection('fixity', 'Repair Summary:');
        $this->logSection('fixity', "  Successful Repairs: {$stats['successful_repairs']}");
        $this->logSection('fixity', "  Failed Repairs:     {$stats['failed_repairs']}");
        $this->logSection('fixity', "  Total Attempts:     {$stats['total_attempts']}");
        $this->logSection('fixity', "  Success Rate:       {$stats['success_rate']}%");

        if ($stats['bytes_restored'] > 0) {
            $bytesFormatted = $this->formatBytes($stats['bytes_restored']);
            $this->logSection('fixity', "  Data Restored:      {$bytesFormatted}");
        }

        if (!empty($stats['repairs_by_target'])) {
            $this->logSection('fixity', '');
            $this->logSection('fixity', 'Repairs by Backup Target:');
            foreach ($stats['repairs_by_target'] as $target => $count) {
                $this->logSection('fixity', "  {$target}: {$count}");
            }
        }

        // Show recent repair events
        $recentRepairs = DB::table('preservation_event as pe')
            ->join('digital_object as do', 'pe.digital_object_id', '=', 'do.id')
            ->whereIn('pe.event_type', ['repair', 'repair_attempt'])
            ->orderBy('pe.event_datetime', 'desc')
            ->select('do.id', 'do.name', 'pe.event_type', 'pe.event_outcome', 'pe.event_datetime', 'pe.event_detail')
            ->limit(10)
            ->get();

        if (!$recentRepairs->isEmpty()) {
            $this->logSection('fixity', '');
            $this->logSection('fixity', 'Recent Repair Activity:');

            foreach ($recentRepairs as $event) {
                $status = 'success' === $event->event_outcome ? 'OK' : 'FAIL';
                $color = 'success' === $event->event_outcome ? 'INFO' : 'ERROR';
                $type = 'repair' === $event->event_type ? 'REPAIR' : 'ATTEMPT';
                $this->logSection('fixity', "  [{$status}] [{$type}] Object {$event->id}: {$event->name}", null, $color);
                $this->logSection('fixity', "    Time: {$event->event_datetime}");
                if ($event->event_detail) {
                    $this->logSection('fixity', "    Detail: {$event->event_detail}");
                }
            }
        }

        // Show replication targets status
        $targets = DB::table('preservation_replication_target')
            ->where('is_active', 1)
            ->get();

        if (!$targets->isEmpty()) {
            $this->logSection('fixity', '');
            $this->logSection('fixity', 'Backup Targets Available for Self-Healing:');

            foreach ($targets as $target) {
                $lastSync = $target->last_sync_at ? date('Y-m-d H:i', strtotime($target->last_sync_at)) : 'Never';
                $status = $target->last_sync_status ?? 'N/A';
                $this->logSection('fixity', "  {$target->name} ({$target->target_type})");
                $this->logSection('fixity', "    Last Sync: {$lastSync} - Status: {$status}");
            }
        } else {
            $this->logSection('fixity', '');
            $this->logSection('fixity', 'No backup targets configured for self-healing.', null, 'ERROR');
            $this->logSection('fixity', 'Use preservation:replicate --add-target to configure backups.');
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            ++$i;
        }

        return number_format($bytes, 2).' '.$units[$i];
    }
}
