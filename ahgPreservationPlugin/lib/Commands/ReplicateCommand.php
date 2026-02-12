<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ReplicateCommand extends BaseCommand
{
    protected string $name = 'preservation:replicate';
    protected string $description = 'Replicate files to backup targets';
    protected string $detailedDescription = <<<'EOF'
Replicates digital assets to configured backup targets using rsync, S3, or other methods.

Examples:
  php bin/atom preservation:replicate --list           # List targets
  php bin/atom preservation:replicate                  # Sync all active targets
  php bin/atom preservation:replicate --target=offsite # Sync specific target
  php bin/atom preservation:replicate --dry-run        # Preview what would sync
  php bin/atom preservation:replicate --full           # Force full resync

Supported target types:
  - local: Local directory (rsync)
  - remote: Remote server via SSH/rsync
  - s3: Amazon S3 or compatible (aws cli)
  - azure: Azure Blob Storage (az cli)
EOF;

    protected function configure(): void
    {
        $this->addOption('target', null, 'Specific target name to sync');
        $this->addOption('target-id', null, 'Specific target ID to sync');
        $this->addOption('dry-run', null, 'Show what would be synced without syncing');
        $this->addOption('full', null, 'Force full sync instead of incremental');
        $this->addOption('list', null, 'List configured replication targets');
        $this->addOption('add-target', null, 'Add a new replication target');
    }

    protected function handle(): int
    {
        // List targets
        if ($this->hasOption('list')) {
            $this->listTargets();

            return 0;
        }

        // Add new target
        if ($this->hasOption('add-target')) {
            $this->addTarget();

            return 0;
        }

        $dryRun = $this->hasOption('dry-run');
        $fullSync = $this->hasOption('full');

        // Get target(s) to sync
        $query = DB::table('preservation_replication_target')
            ->where('is_active', 1);

        if ($this->hasOption('target')) {
            $query->where('name', $this->option('target'));
        } elseif ($this->hasOption('target-id')) {
            $query->where('id', (int) $this->option('target-id'));
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            $this->info('No active replication targets found');
            $this->line('Use --add-target to configure a backup destination');

            return 0;
        }

        foreach ($targets as $target) {
            $this->syncTarget($target, $dryRun, $fullSync);
        }

        return 0;
    }

    private function listTargets(): void
    {
        $targets = DB::table('preservation_replication_target')->get();

        if ($targets->isEmpty()) {
            $this->info('No replication targets configured');
            $this->line('Use --add-target to add a backup destination');

            return;
        }

        $this->bold('Configured Replication Targets:');
        $this->newline();

        foreach ($targets as $target) {
            $status = $target->is_active ? 'ACTIVE' : 'INACTIVE';
            $config = $this->getConfig($target);

            if ($target->is_active) {
                $this->info("[{$target->id}] {$target->name} ({$target->target_type})");
            } else {
                $this->comment("[{$target->id}] {$target->name} ({$target->target_type})");
            }

            $this->line("    Status: $status");
            $this->line("    Path: " . ($config['path'] ?? $config['bucket'] ?? 'N/A'));

            if (!empty($config['host'])) {
                $this->line("    Host: {$config['host']}");
            }

            // Last sync info
            $lastSync = DB::table('preservation_replication_log')
                ->where('target_id', $target->id)
                ->orderBy('started_at', 'desc')
                ->first();

            if ($lastSync) {
                $this->line("    Last sync: {$lastSync->started_at} ({$lastSync->status})");
                if ('completed' === $lastSync->status) {
                    $this->line("    Files: {$lastSync->files_synced}, Size: " . number_format($lastSync->bytes_transferred) . ' bytes');
                }
            } else {
                $this->line('    Last sync: Never');
            }

            $this->newline();
        }
    }

    private function syncTarget(object $target, bool $dryRun, bool $fullSync): void
    {
        $this->info("Syncing to: {$target->name} ({$target->target_type})" . ($dryRun ? ' [DRY RUN]' : ''));

        $uploadPath = $this->getAtomRoot() . '/uploads';
        if (defined('SF_UPLOAD_DIR') && is_dir(SF_UPLOAD_DIR)) {
            $uploadPath = SF_UPLOAD_DIR;
        } elseif (class_exists('sfConfig')) {
            $sfUploadDir = \sfConfig::get('sf_upload_dir');
            if ($sfUploadDir && is_dir($sfUploadDir)) {
                $uploadPath = $sfUploadDir;
            }
        }

        $startTime = date('Y-m-d H:i:s');
        $logId = null;

        if (!$dryRun) {
            // Create log entry
            $logId = DB::table('preservation_replication_log')->insertGetId([
                'target_id' => $target->id,
                'operation' => 'sync',
                'status' => 'started',
                'started_at' => $startTime,
            ]);
        }

        $result = null;
        $success = false;
        $filesSynced = 0;
        $bytesTransferred = 0;
        $errorMessage = null;

        try {
            switch ($target->target_type) {
                case 'local':
                    $result = $this->syncLocal($uploadPath, $target, $dryRun, $fullSync);

                    break;

                case 'remote':
                    $result = $this->syncRemote($uploadPath, $target, $dryRun, $fullSync);

                    break;

                case 's3':
                    $result = $this->syncS3($uploadPath, $target, $dryRun, $fullSync);

                    break;

                default:
                    $this->error("Unknown target type: {$target->target_type}");

                    return;
            }

            $success = $result['success'];
            $filesSynced = $result['files'] ?? 0;
            $bytesTransferred = $result['bytes'] ?? 0;
            $errorMessage = $result['error'] ?? null;
        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();
            $this->error("Error: {$errorMessage}");
        }

        if (!$dryRun && $logId) {
            $endTime = date('Y-m-d H:i:s');
            $durationMs = (strtotime($endTime) - strtotime($startTime)) * 1000;

            DB::table('preservation_replication_log')
                ->where('id', $logId)
                ->update([
                    'status' => $success ? 'completed' : 'failed',
                    'completed_at' => $endTime,
                    'duration_ms' => $durationMs,
                    'files_synced' => $filesSynced,
                    'bytes_transferred' => $bytesTransferred,
                    'error_message' => $errorMessage,
                ]);

            // Update target's last sync info
            DB::table('preservation_replication_target')
                ->where('id', $target->id)
                ->update([
                    'last_sync_at' => $endTime,
                    'last_sync_status' => $success ? 'success' : 'failed',
                    'last_sync_files' => $filesSynced,
                    'last_sync_bytes' => $bytesTransferred,
                ]);
        }

        if ($success) {
            $this->success("Sync completed: $filesSynced files, " . number_format($bytesTransferred) . ' bytes');
        }
    }

    private function getConfig(object $target): array
    {
        if (empty($target->connection_config)) {
            return [];
        }

        return is_string($target->connection_config)
            ? json_decode($target->connection_config, true) ?? []
            : (array) $target->connection_config;
    }

    private function syncLocal(string $sourcePath, object $target, bool $dryRun, bool $fullSync): array
    {
        $config = $this->getConfig($target);
        $destPath = $config['path'] ?? null;

        if (!$destPath) {
            return ['success' => false, 'error' => 'No path configured'];
        }

        // Build rsync command
        $cmd = 'rsync -av';
        if ($dryRun) {
            $cmd .= ' --dry-run';
        }
        if (!$fullSync) {
            $cmd .= ' --update';
        }
        $cmd .= ' --stats';
        $cmd .= ' ' . escapeshellarg($sourcePath . '/');
        $cmd .= ' ' . escapeshellarg($destPath . '/');

        $this->comment("Running: $cmd");

        $output = [];
        $returnCode = 0;
        exec($cmd . ' 2>&1', $output, $returnCode);

        // Parse rsync stats
        $files = 0;
        $bytes = 0;

        foreach ($output as $line) {
            if (preg_match('/Number of files transferred: (\d+)/', $line, $m)) {
                $files = (int) $m[1];
            }
            if (preg_match('/Total transferred file size: ([\d,]+)/', $line, $m)) {
                $bytes = (int) str_replace(',', '', $m[1]);
            }
            if ($dryRun && str_starts_with($line, ' ')) {
                $this->line('  ' . trim($line));
            }
        }

        return [
            'success' => 0 === $returnCode,
            'files' => $files,
            'bytes' => $bytes,
            'error' => 0 !== $returnCode ? implode("\n", $output) : null,
        ];
    }

    private function syncRemote(string $sourcePath, object $target, bool $dryRun, bool $fullSync): array
    {
        $config = $this->getConfig($target);
        $destPath = $config['path'] ?? null;
        $host = $config['host'] ?? null;
        $port = $config['port'] ?? 22;

        if (!$destPath || !$host) {
            return ['success' => false, 'error' => 'Missing host or path in config'];
        }

        // Build rsync command for remote
        $cmd = "rsync -avz -e 'ssh -p $port'";
        if ($dryRun) {
            $cmd .= ' --dry-run';
        }
        if (!$fullSync) {
            $cmd .= ' --update';
        }
        $cmd .= ' --stats';
        $cmd .= ' ' . escapeshellarg($sourcePath . '/');
        $cmd .= ' ' . escapeshellarg("$host:$destPath/");

        $this->comment("Running: $cmd");

        $output = [];
        $returnCode = 0;
        exec($cmd . ' 2>&1', $output, $returnCode);

        $files = 0;
        $bytes = 0;

        foreach ($output as $line) {
            if (preg_match('/Number of files transferred: (\d+)/', $line, $m)) {
                $files = (int) $m[1];
            }
            if (preg_match('/Total transferred file size: ([\d,]+)/', $line, $m)) {
                $bytes = (int) str_replace(',', '', $m[1]);
            }
        }

        return [
            'success' => 0 === $returnCode,
            'files' => $files,
            'bytes' => $bytes,
            'error' => 0 !== $returnCode ? implode("\n", $output) : null,
        ];
    }

    private function syncS3(string $sourcePath, object $target, bool $dryRun, bool $fullSync): array
    {
        $config = $this->getConfig($target);
        $bucket = $config['bucket'] ?? $config['path'] ?? null;

        if (!$bucket) {
            return ['success' => false, 'error' => 'No bucket configured'];
        }

        // Check aws cli
        $output = [];
        $returnCode = 0;
        exec('which aws', $output, $returnCode);
        if (0 !== $returnCode) {
            return [
                'success' => false,
                'error' => 'AWS CLI not installed. Install with: sudo apt install awscli',
            ];
        }

        // Build aws s3 sync command
        $cmd = 'aws s3 sync';
        if ($dryRun) {
            $cmd .= ' --dryrun';
        }
        $cmd .= ' ' . escapeshellarg($sourcePath);
        $cmd .= ' ' . escapeshellarg("s3://$bucket/uploads");

        if (!$fullSync) {
            $cmd .= ' --size-only'; // Only sync if size differs
        }

        $this->comment("Running: $cmd");

        $output = [];
        $returnCode = 0;
        exec($cmd . ' 2>&1', $output, $returnCode);

        // Count files from output
        $files = 0;
        foreach ($output as $line) {
            if (str_starts_with($line, 'upload:') || str_starts_with($line, '(dryrun) upload:')) {
                ++$files;
                if ($dryRun) {
                    $this->line('  ' . $line);
                }
            }
        }

        return [
            'success' => 0 === $returnCode,
            'files' => $files,
            'bytes' => 0, // S3 sync doesn't report bytes easily
            'error' => 0 !== $returnCode ? implode("\n", $output) : null,
        ];
    }

    private function addTarget(): void
    {
        $this->info('Adding a new replication target is not yet supported via CLI');
        $this->line('Please add targets via SQL:');
        $this->newline();
        $this->line('INSERT INTO preservation_replication_target');
        $this->line("  (name, target_type, target_path, is_active)");
        $this->line('VALUES');
        $this->line("  ('offsite-backup', 'local', '/mnt/backup/atom', 1);");
    }
}
