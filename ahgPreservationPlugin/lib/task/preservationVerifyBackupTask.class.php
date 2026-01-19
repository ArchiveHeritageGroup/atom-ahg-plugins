<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to verify backup integrity and replication status.
 */
class preservationVerifyBackupTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('path', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific backup file or directory to verify'),
            new sfCommandOption('backup-dir', null, sfCommandOption::PARAMETER_OPTIONAL, 'Backup directory to scan', '/var/backups/atom'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Backup type (full, incremental, database, files)', 'full'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show backup verification statistics'),
            new sfCommandOption('failed-only', null, sfCommandOption::PARAMETER_NONE, 'Only show failed verifications'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'verify-backup';
        $this->briefDescription = 'Verify backup integrity and replication status';
        $this->detailedDescription = <<<EOF
Verifies backup files for integrity using checksums and archive validation.

Examples:
  php symfony preservation:verify-backup --status              # Show verification stats
  php symfony preservation:verify-backup --backup-dir=/backups # Verify all in directory
  php symfony preservation:verify-backup --path=/backup.tar.gz # Verify specific file
  php symfony preservation:verify-backup --failed-only         # Show failed backups

Verification checks:
  - File exists and is readable
  - SHA-256 checksum verification
  - Archive integrity (tar/zip/gz)
  - File size validation
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
            $this->showStatus($options['failed-only'] ?? false);

            return;
        }

        $backupDir = $options['backup-dir'] ?? '/var/backups/atom';
        $backupType = $options['type'] ?? 'full';

        // Single file verification
        if (!empty($options['path'])) {
            $path = $options['path'];
            $this->logSection('verify', "Verifying backup: $path");

            if (!file_exists($path)) {
                $this->logSection('verify', 'ERROR: File not found', null, 'ERROR');

                return 1;
            }

            $result = $service->verifyBackup($path, $backupType, null, 'cli-task');

            $this->displayResult($result);

            return;
        }

        // Directory verification
        if (!is_dir($backupDir)) {
            $this->logSection('verify', "ERROR: Backup directory not found: $backupDir", null, 'ERROR');
            $this->logSection('verify', 'Create it or specify --backup-dir=/path/to/backups');

            return 1;
        }

        $this->logSection('verify', "Verifying backups in: $backupDir");
        $this->logSection('verify', '');

        $results = $service->verifyAllBackups($backupDir, 'cli-task');

        $this->logSection('verify', '');
        $this->logSection('verify', 'Verification Summary:');
        $this->logSection('verify', "  Total verified: {$results['total']}");
        $this->logSection('verify', "  Passed: {$results['passed']}");
        $this->logSection('verify', "  Failed: {$results['failed']}");
        $this->logSection('verify', "  Warnings: {$results['warnings']}");

        if ($results['failed'] > 0) {
            $this->logSection('verify', '');
            $this->logSection('verify', 'FAILED BACKUPS:', null, 'ERROR');

            foreach ($results['details'] as $detail) {
                if ('failed' === $detail['status']) {
                    $this->logSection('verify', "  {$detail['path']}: {$detail['message']}", null, 'ERROR');
                }
            }
        }

        // Check replication targets
        $this->logSection('verify', '');
        $this->checkReplicationTargets($service);
    }

    protected function displayResult($result)
    {
        if ('passed' === $result['status']) {
            $this->logSection('verify', 'PASSED - Backup is valid', null, 'INFO');
        } elseif ('warning' === $result['status']) {
            $this->logSection('verify', "WARNING - {$result['message']}", null, 'COMMENT');
        } else {
            $this->logSection('verify', "FAILED - {$result['message']}", null, 'ERROR');
        }

        if (!empty($result['details'])) {
            $this->logSection('verify', '');
            $this->logSection('verify', 'Details:');
            foreach ($result['details'] as $key => $value) {
                $this->logSection('verify', "  $key: $value");
            }
        }
    }

    protected function showStatus($failedOnly)
    {
        $this->logSection('verify', 'Backup Verification Status');
        $this->logSection('verify', '');

        // Overall statistics
        $stats = DB::table('preservation_backup_verification')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->logSection('verify', 'Verification Statistics:');
        $this->logSection('verify', '  Passed: '.($stats['passed'] ?? 0));
        $this->logSection('verify', '  Failed: '.($stats['failed'] ?? 0));
        $this->logSection('verify', '  Warnings: '.($stats['warning'] ?? 0));

        // Recent verifications
        $query = DB::table('preservation_backup_verification')
            ->orderBy('verified_at', 'desc')
            ->limit(20);

        if ($failedOnly) {
            $query->where('status', 'failed');
        }

        $recent = $query->get();

        $this->logSection('verify', '');
        $this->logSection('verify', 'Recent Verifications:');

        foreach ($recent as $v) {
            $status = strtoupper($v->status);
            $color = 'passed' === $v->status ? 'INFO' : ('failed' === $v->status ? 'ERROR' : 'COMMENT');

            $path = basename($v->backup_path);
            $date = substr($v->verified_at, 0, 16);

            $this->logSection('verify', "  [$status] $date - $path", null, $color);
        }

        // Replication targets
        $this->logSection('verify', '');
        $this->checkReplicationTargets(null);
    }

    protected function checkReplicationTargets($service)
    {
        $targets = DB::table('preservation_replication_target')
            ->where('is_active', 1)
            ->get();

        if ($targets->isEmpty()) {
            $this->logSection('verify', 'No replication targets configured');

            return;
        }

        $this->logSection('verify', 'Replication Targets:');

        foreach ($targets as $target) {
            $lastSync = DB::table('preservation_replication_log')
                ->where('target_id', $target->id)
                ->orderBy('started_at', 'desc')
                ->first();

            $status = $lastSync ? strtoupper($lastSync->status) : 'NEVER';
            $color = (!$lastSync || 'failed' === $lastSync->status) ? 'ERROR' : 'INFO';

            $this->logSection('verify', "  {$target->name} ({$target->target_type}):", null, $color);
            $this->logSection('verify', "    Last sync: ".($lastSync ? $lastSync->started_at : 'Never'));

            if ($lastSync && $lastSync->status) {
                $this->logSection('verify', "    Status: $status");
                if ($lastSync->files_synced) {
                    $this->logSection('verify', "    Files: {$lastSync->files_synced}, Bytes: ".number_format($lastSync->bytes_transferred));
                }
            }
        }
    }
}
