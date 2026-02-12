<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class VerifyBackupCommand extends BaseCommand
{
    protected string $name = 'preservation:verify-backup';
    protected string $description = 'Verify backup integrity and replication status';
    protected string $detailedDescription = <<<'EOF'
Verifies backup files for integrity using checksums and archive validation.

Examples:
  php bin/atom preservation:verify-backup --status              # Show verification stats
  php bin/atom preservation:verify-backup --backup-dir=/backups # Verify all in directory
  php bin/atom preservation:verify-backup --path=/backup.tar.gz # Verify specific file
  php bin/atom preservation:verify-backup --failed-only         # Show failed backups

Verification checks:
  - File exists and is readable
  - SHA-256 checksum verification
  - Archive integrity (tar/zip/gz)
  - File size validation
EOF;

    protected function configure(): void
    {
        $this->addOption('path', null, 'Specific backup file or directory to verify');
        $this->addOption('backup-dir', null, 'Backup directory to scan', '/var/backups/atom');
        $this->addOption('type', null, 'Backup type (full, incremental, database, files)', 'full');
        $this->addOption('status', null, 'Show backup verification statistics');
        $this->addOption('failed-only', null, 'Only show failed verifications');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();

        // Status check
        if ($this->hasOption('status')) {
            $this->showStatus($this->hasOption('failed-only'));

            return 0;
        }

        $backupDir = $this->option('backup-dir', '/var/backups/atom');
        $backupType = $this->option('type', 'full');

        // Single file verification
        if ($this->hasOption('path')) {
            $path = $this->option('path');
            $this->info("Verifying backup: $path");

            if (!file_exists($path)) {
                $this->error('ERROR: File not found');

                return 1;
            }

            $result = $service->verifyBackup($path, $backupType, null, 'cli-task');

            $this->displayResult($result);

            return 0;
        }

        // Directory verification
        if (!is_dir($backupDir)) {
            $this->error("ERROR: Backup directory not found: $backupDir");
            $this->line('Create it or specify --backup-dir=/path/to/backups');

            return 1;
        }

        $this->info("Verifying backups in: $backupDir");
        $this->newline();

        $results = $service->verifyAllBackups($backupDir, 'cli-task');

        $this->newline();
        $this->bold('Verification Summary:');
        $this->line("  Total verified: {$results['total']}");
        $this->line("  Passed: {$results['passed']}");
        $this->line("  Failed: {$results['failed']}");
        $this->line("  Warnings: {$results['warnings']}");

        if ($results['failed'] > 0) {
            $this->newline();
            $this->error('FAILED BACKUPS:');

            foreach ($results['details'] as $detail) {
                if ('failed' === $detail['status']) {
                    $this->error("  {$detail['path']}: {$detail['message']}");
                }
            }
        }

        // Check replication targets
        $this->newline();
        $this->checkReplicationTargets($service);

        return 0;
    }

    /**
     * Display a single verification result.
     */
    private function displayResult(array $result): void
    {
        if ('passed' === $result['status']) {
            $this->success('PASSED - Backup is valid');
        } elseif ('warning' === $result['status']) {
            $this->warning("WARNING - {$result['message']}");
        } else {
            $this->error("FAILED - {$result['message']}");
        }

        if (!empty($result['details'])) {
            $this->newline();
            $this->info('Details:');
            foreach ($result['details'] as $key => $value) {
                $this->line("  $key: $value");
            }
        }
    }

    /**
     * Show backup verification status.
     */
    private function showStatus(bool $failedOnly): void
    {
        $this->bold('Backup Verification Status');
        $this->newline();

        // Overall statistics
        $stats = DB::table('preservation_backup_verification')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->info('Verification Statistics:');
        $this->line('  Passed: ' . ($stats['passed'] ?? 0));
        $this->line('  Failed: ' . ($stats['failed'] ?? 0));
        $this->line('  Warnings: ' . ($stats['warning'] ?? 0));

        // Recent verifications
        $query = DB::table('preservation_backup_verification')
            ->orderBy('verified_at', 'desc')
            ->limit(20);

        if ($failedOnly) {
            $query->where('status', 'failed');
        }

        $recent = $query->get();

        $this->newline();
        $this->info('Recent Verifications:');

        foreach ($recent as $v) {
            $status = strtoupper($v->status);
            $method = 'passed' === $v->status ? 'success' : ('failed' === $v->status ? 'error' : 'warning');

            $path = basename($v->backup_path);
            $date = substr($v->verified_at, 0, 16);

            $this->$method("  [$status] $date - $path");
        }

        // Replication targets
        $this->newline();
        $this->checkReplicationTargets(null);
    }

    /**
     * Check and display replication target status.
     */
    private function checkReplicationTargets(?\PreservationService $service): void
    {
        $targets = DB::table('preservation_replication_target')
            ->where('is_active', 1)
            ->get();

        if ($targets->isEmpty()) {
            $this->line('No replication targets configured');

            return;
        }

        $this->info('Replication Targets:');

        foreach ($targets as $target) {
            $lastSync = DB::table('preservation_replication_log')
                ->where('target_id', $target->id)
                ->orderBy('started_at', 'desc')
                ->first();

            $status = $lastSync ? strtoupper($lastSync->status) : 'NEVER';
            $method = (!$lastSync || 'failed' === $lastSync->status) ? 'error' : 'info';

            $this->$method("  {$target->name} ({$target->target_type}):");
            $this->line("    Last sync: " . ($lastSync ? $lastSync->started_at : 'Never'));

            if ($lastSync && $lastSync->status) {
                $this->line("    Status: $status");
                if ($lastSync->files_synced) {
                    $this->line("    Files: {$lastSync->files_synced}, Bytes: " . number_format($lastSync->bytes_transferred));
                }
            }
        }
    }
}
