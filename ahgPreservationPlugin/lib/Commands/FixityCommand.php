<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class FixityCommand extends BaseCommand
{
    protected string $name = 'preservation:fixity';
    protected string $description = 'Verify file integrity using checksums';
    protected string $detailedDescription = <<<'EOF'
Performs fixity checks on digital objects by verifying their checksums.

Examples:
  php bin/atom preservation:fixity                       # Check stale objects (>30 days)
  php bin/atom preservation:fixity --status              # Show fixity statistics
  php bin/atom preservation:fixity --repair-stats        # Show self-healing statistics
  php bin/atom preservation:fixity --object-id=123       # Check specific object
  php bin/atom preservation:fixity --stale-days=7        # Check objects not checked in 7 days
  php bin/atom preservation:fixity --all --limit=500     # Check all objects
  php bin/atom preservation:fixity --auto-repair         # Enable self-healing from backups
EOF;

    protected function configure(): void
    {
        $this->addOption('object-id', null, 'Specific digital object ID to check');
        $this->addOption('limit', 'l', 'Maximum number of objects to check', '100');
        $this->addOption('stale-days', null, 'Check objects not verified in N days', '30');
        $this->addOption('all', 'a', 'Check all objects regardless of last check');
        $this->addOption('status', 's', 'Show fixity check statistics');
        $this->addOption('failed-only', null, 'Only show failed checks');
        $this->addOption('auto-repair', null, 'Enable self-healing auto-repair from backups');
        $this->addOption('repair-stats', null, 'Show self-healing repair statistics');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();

        // Repair statistics
        if ($this->hasOption('repair-stats')) {
            $this->showRepairStats($service);

            return 0;
        }

        // Status check
        if ($this->hasOption('status')) {
            $this->showStatus($this->hasOption('failed-only'));

            return 0;
        }

        $limit = (int) $this->option('limit', '100');
        $staleDays = (int) $this->option('stale-days', '30');
        $checkAll = $this->hasOption('all');
        $autoRepair = $this->hasOption('auto-repair');

        if ($autoRepair) {
            $this->info('Self-healing auto-repair is ENABLED');
        }

        // Single object check
        if ($this->hasOption('object-id')) {
            $objectId = (int) $this->option('object-id');
            $this->info("Checking fixity for object ID: $objectId");

            if ($autoRepair) {
                $results = $service->verifyFixityWithRepair($objectId, 'sha256', 'cli-task', true);
            } else {
                $results = $service->verifyFixity($objectId, 'sha256', 'cli-task');
            }

            foreach ($results as $algo => $result) {
                if ('pass' === $result['status']) {
                    $this->success("PASSED ($algo) - Checksum verified");
                    $this->line("  Checksum: {$result['expected']}");
                    if (!empty($result['repaired'])) {
                        $this->success("  REPAIRED from: {$result['repair_source']}");
                    }
                } elseif ('fail' === $result['status']) {
                    $this->error("FAILED ($algo) - Checksum mismatch!");
                    $this->line("  Expected: {$result['expected']}");
                    $this->line("  Actual:   {$result['actual']}");
                    if (!empty($result['repair_failed'])) {
                        $this->error("  Repair FAILED: {$result['repair_error']}");
                    }
                } elseif ('missing' === $result['status']) {
                    $this->error("MISSING ($algo) - File not found");
                } else {
                    $this->error("ERROR ($algo) - {$result['error']}");
                }
            }

            return 0;
        }

        // Batch fixity check
        $this->info('Starting batch fixity check...');

        $query = DB::table('digital_object as do')
            ->leftJoin('preservation_fixity_check as pf', function ($join) {
                $join->on('do.id', '=', 'pf.digital_object_id')
                    ->where('pf.algorithm', '=', 'sha256');
            })
            ->where('do.usage_id', 140)
            ->select('do.id', 'do.name', 'pf.checked_at', 'pf.status');

        if (!$checkAll) {
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
            $this->info('No objects need fixity checking');

            return 0;
        }

        $this->info("Checking {$objects->count()} objects...");
        $this->newline();

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
                    $objPassed = true;
                }
                if (!empty($result['repair_failed'])) {
                    $objRepairFailed = true;
                }
                if (!empty($result['error'])) {
                    $objError = $result['error'];
                }
            }

            if ($objError) {
                $this->error("Object {$obj->id}: ERROR - {$objError}");
                ++$errors;
            } elseif ($objRepaired) {
                $source = $results['sha256']['repair_source'] ?? 'backup';
                $this->success("Object {$obj->id}: REPAIRED from $source");
                ++$repaired;
            } elseif ($objRepairFailed) {
                $error = $results['sha256']['repair_error'] ?? 'Unknown';
                $this->error("Object {$obj->id}: REPAIR FAILED - $error");
                ++$repairFailed;
            } elseif ($objPassed) {
                $this->success("Object {$obj->id}: PASSED");
                ++$passed;
            } else {
                $this->error("Object {$obj->id}: FAILED - Checksum mismatch!");
                ++$failed;
            }
        }

        $this->newline();
        $this->bold('Fixity Check Complete:');
        $this->line("  Passed:        $passed");
        if ($autoRepair) {
            $this->line("  Repaired:      $repaired");
            $this->line("  Repair Failed: $repairFailed");
        }
        $this->line("  Failed:        $failed");
        $this->line("  Errors:        $errors");

        if ($failed > 0 || $repairFailed > 0) {
            $this->newline();
            $this->warning('Some files have integrity issues!');
            if (!$autoRepair) {
                $this->info('Run with --auto-repair to attempt restoration from backups.');
            } else {
                $this->info('Some files could not be repaired - check backup targets.');
            }
        }

        return 0;
    }

    private function showStatus(bool $failedOnly): void
    {
        $this->bold('Fixity Check Status');
        $this->newline();

        $stats = DB::table('preservation_fixity_check')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->info('Statistics:');
        $this->line('  Passed: ' . ($stats['passed'] ?? 0));
        $this->line('  Failed: ' . ($stats['failed'] ?? 0));
        $this->line('  Pending: ' . ($stats['pending'] ?? 0));

        $neverChecked = DB::table('digital_object as do')
            ->leftJoin('preservation_fixity_check as pf', 'do.id', '=', 'pf.digital_object_id')
            ->where('do.usage_id', 140)
            ->whereNull('pf.id')
            ->count();

        $this->line('  Never checked: ' . $neverChecked);

        $staleDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        $stale = DB::table('preservation_fixity_check')
            ->where('checked_at', '<', $staleDate)
            ->count();

        $this->line('  Stale (>30 days): ' . $stale);

        $failures = DB::table('preservation_fixity_check as pf')
            ->join('digital_object as do', 'pf.digital_object_id', '=', 'do.id')
            ->where('pf.status', 'failed')
            ->orderBy('pf.checked_at', 'desc')
            ->select('do.id', 'do.name', 'pf.checked_at', 'pf.expected_value', 'pf.actual_value')
            ->limit(10)
            ->get();

        if (!$failures->isEmpty()) {
            $this->newline();
            $this->error('Recent Failures:');
            foreach ($failures as $f) {
                $this->line("  [{$f->id}] {$f->name}");
                $this->line("    Checked: {$f->checked_at}");
                $this->line("    Expected: {$f->expected_value}");
                $this->line("    Got: {$f->actual_value}");
            }
        }

        if (!$failedOnly) {
            $recent = DB::table('preservation_fixity_check as pf')
                ->join('digital_object as do', 'pf.digital_object_id', '=', 'do.id')
                ->orderBy('pf.checked_at', 'desc')
                ->select('do.id', 'do.name', 'pf.status', 'pf.checked_at')
                ->limit(10)
                ->get();

            $this->newline();
            $this->info('Recent Checks:');
            foreach ($recent as $r) {
                $status = strtoupper($r->status);
                $method = 'passed' === $r->status ? 'success' : 'error';
                $this->$method("  [{$status}] {$r->id}: {$r->name} ({$r->checked_at})");
            }
        }
    }

    private function showRepairStats(\PreservationService $service): void
    {
        $this->bold('Self-Healing Repair Statistics');
        $this->newline();

        $stats = $service->getSelfHealingStats(30);

        $this->line("Period: Last {$stats['period_days']} days");
        $this->newline();
        $this->info('Repair Summary:');
        $this->line("  Successful Repairs: {$stats['successful_repairs']}");
        $this->line("  Failed Repairs:     {$stats['failed_repairs']}");
        $this->line("  Total Attempts:     {$stats['total_attempts']}");
        $this->line("  Success Rate:       {$stats['success_rate']}%");

        if ($stats['bytes_restored'] > 0) {
            $bytesFormatted = $this->formatBytes($stats['bytes_restored']);
            $this->line("  Data Restored:      {$bytesFormatted}");
        }

        if (!empty($stats['repairs_by_target'])) {
            $this->newline();
            $this->info('Repairs by Backup Target:');
            foreach ($stats['repairs_by_target'] as $target => $count) {
                $this->line("  {$target}: {$count}");
            }
        }

        $recentRepairs = DB::table('preservation_event as pe')
            ->join('digital_object as do', 'pe.digital_object_id', '=', 'do.id')
            ->whereIn('pe.event_type', ['repair', 'repair_attempt'])
            ->orderBy('pe.event_datetime', 'desc')
            ->select('do.id', 'do.name', 'pe.event_type', 'pe.event_outcome', 'pe.event_datetime', 'pe.event_detail')
            ->limit(10)
            ->get();

        if (!$recentRepairs->isEmpty()) {
            $this->newline();
            $this->info('Recent Repair Activity:');
            foreach ($recentRepairs as $event) {
                $type = 'repair' === $event->event_type ? 'REPAIR' : 'ATTEMPT';
                $method = 'success' === $event->event_outcome ? 'success' : 'error';
                $status = 'success' === $event->event_outcome ? 'OK' : 'FAIL';
                $this->$method("  [{$status}] [{$type}] Object {$event->id}: {$event->name}");
                $this->line("    Time: {$event->event_datetime}");
                if ($event->event_detail) {
                    $this->line("    Detail: {$event->event_detail}");
                }
            }
        }

        $targets = DB::table('preservation_replication_target')
            ->where('is_active', 1)
            ->get();

        if (!$targets->isEmpty()) {
            $this->newline();
            $this->info('Backup Targets Available for Self-Healing:');
            foreach ($targets as $target) {
                $lastSync = $target->last_sync_at ? date('Y-m-d H:i', strtotime($target->last_sync_at)) : 'Never';
                $status = $target->last_sync_status ?? 'N/A';
                $this->line("  {$target->name} ({$target->target_type})");
                $this->line("    Last Sync: {$lastSync} - Status: {$status}");
            }
        } else {
            $this->newline();
            $this->warning('No backup targets configured for self-healing.');
            $this->info('Use preservation:replicate --add-target to configure backups.');
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            ++$i;
        }

        return number_format($bytes, 2) . ' ' . $units[$i];
    }
}
