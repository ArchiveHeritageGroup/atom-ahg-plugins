<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to replicate files to configured backup targets.
 */
class preservationReplicateTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('target', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific target name to sync'),
            new sfCommandOption('target-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific target ID to sync'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be synced without syncing'),
            new sfCommandOption('full', null, sfCommandOption::PARAMETER_NONE, 'Force full sync instead of incremental'),
            new sfCommandOption('list', null, sfCommandOption::PARAMETER_NONE, 'List configured replication targets'),
            new sfCommandOption('add-target', null, sfCommandOption::PARAMETER_NONE, 'Add a new replication target (interactive)'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'replicate';
        $this->briefDescription = 'Replicate files to backup targets';
        $this->detailedDescription = <<<EOF
Replicates digital assets to configured backup targets using rsync, S3, or other methods.

Examples:
  php symfony preservation:replicate --list           # List targets
  php symfony preservation:replicate                  # Sync all active targets
  php symfony preservation:replicate --target=offsite # Sync specific target
  php symfony preservation:replicate --dry-run        # Preview what would sync
  php symfony preservation:replicate --full           # Force full resync

Supported target types:
  - local: Local directory (rsync)
  - remote: Remote server via SSH/rsync
  - s3: Amazon S3 or compatible (aws cli)
  - azure: Azure Blob Storage (az cli)
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        // List targets
        if ($options['list']) {
            $this->listTargets();

            return;
        }

        // Add new target (interactive)
        if ($options['add-target']) {
            $this->addTarget();

            return;
        }

        $dryRun = !empty($options['dry-run']);
        $fullSync = !empty($options['full']);

        // Get target(s) to sync
        $query = DB::table('preservation_replication_target')
            ->where('is_active', 1);

        if (!empty($options['target'])) {
            $query->where('name', $options['target']);
        } elseif (!empty($options['target-id'])) {
            $query->where('id', (int) $options['target-id']);
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            $this->logSection('replicate', 'No active replication targets found');
            $this->logSection('replicate', 'Use --add-target to configure a backup destination');

            return;
        }

        foreach ($targets as $target) {
            $this->syncTarget($target, $dryRun, $fullSync);
        }
    }

    protected function listTargets()
    {
        $targets = DB::table('preservation_replication_target')->get();

        if ($targets->isEmpty()) {
            $this->logSection('replicate', 'No replication targets configured');
            $this->logSection('replicate', 'Use --add-target to add a backup destination');

            return;
        }

        $this->logSection('replicate', 'Configured Replication Targets:');
        $this->logSection('replicate', '');

        foreach ($targets as $target) {
            $status = $target->is_active ? 'ACTIVE' : 'INACTIVE';
            $color = $target->is_active ? 'INFO' : 'COMMENT';
            $config = $this->getConfig($target);

            $this->logSection('replicate', "[{$target->id}] {$target->name} ({$target->target_type})", null, $color);
            $this->logSection('replicate', "    Status: $status");
            $this->logSection('replicate', "    Path: ".($config['path'] ?? $config['bucket'] ?? 'N/A'));

            if (!empty($config['host'])) {
                $this->logSection('replicate', "    Host: {$config['host']}");
            }

            // Last sync info
            $lastSync = DB::table('preservation_replication_log')
                ->where('target_id', $target->id)
                ->orderBy('started_at', 'desc')
                ->first();

            if ($lastSync) {
                $this->logSection('replicate', "    Last sync: {$lastSync->started_at} ({$lastSync->status})");
                if ('completed' === $lastSync->status) {
                    $this->logSection('replicate', "    Files: {$lastSync->files_synced}, Size: ".number_format($lastSync->bytes_transferred).' bytes');
                }
            } else {
                $this->logSection('replicate', '    Last sync: Never');
            }

            $this->logSection('replicate', '');
        }
    }

    protected function syncTarget($target, $dryRun, $fullSync)
    {
        $this->logSection('replicate', "Syncing to: {$target->name} ({$target->target_type})".($dryRun ? ' [DRY RUN]' : ''));

        $uploadPath = sfConfig::get('sf_upload_dir');
        if (!$uploadPath || !is_dir($uploadPath)) {
            $uploadPath = sfConfig::get('sf_root_dir').'/uploads';
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
                    $this->logSection('replicate', "Unknown target type: {$target->target_type}", null, 'ERROR');

                    return;
            }

            $success = $result['success'];
            $filesSynced = $result['files'] ?? 0;
            $bytesTransferred = $result['bytes'] ?? 0;
            $errorMessage = $result['error'] ?? null;
        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();
            $this->logSection('replicate', "Error: {$errorMessage}", null, 'ERROR');
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
            $this->logSection('replicate', "Sync completed: $filesSynced files, ".number_format($bytesTransferred).' bytes', null, 'INFO');
        }
    }

    protected function getConfig($target): array
    {
        if (empty($target->connection_config)) {
            return [];
        }

        return is_string($target->connection_config)
            ? json_decode($target->connection_config, true) ?? []
            : (array) $target->connection_config;
    }

    protected function syncLocal($sourcePath, $target, $dryRun, $fullSync)
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
        $cmd .= ' '.escapeshellarg($sourcePath.'/');
        $cmd .= ' '.escapeshellarg($destPath.'/');

        $this->logSection('replicate', "Running: $cmd", null, 'COMMENT');

        $output = [];
        $returnCode = 0;
        exec($cmd.' 2>&1', $output, $returnCode);

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
                $this->logSection('replicate', '  '.trim($line));
            }
        }

        return [
            'success' => 0 === $returnCode,
            'files' => $files,
            'bytes' => $bytes,
            'error' => 0 !== $returnCode ? implode("\n", $output) : null,
        ];
    }

    protected function syncRemote($sourcePath, $target, $dryRun, $fullSync)
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
        $cmd .= ' '.escapeshellarg($sourcePath.'/');
        $cmd .= ' '.escapeshellarg("$host:$destPath/");

        $this->logSection('replicate', "Running: $cmd", null, 'COMMENT');

        $output = [];
        $returnCode = 0;
        exec($cmd.' 2>&1', $output, $returnCode);

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

    protected function syncS3($sourcePath, $target, $dryRun, $fullSync)
    {
        $config = $this->getConfig($target);
        $bucket = $config['bucket'] ?? $config['path'] ?? null;

        if (!$bucket) {
            return ['success' => false, 'error' => 'No bucket configured'];
        }

        // Check aws cli
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
        $cmd .= ' '.escapeshellarg($sourcePath);
        $cmd .= ' '.escapeshellarg("s3://$bucket/uploads");

        if (!$fullSync) {
            $cmd .= ' --size-only'; // Only sync if size differs
        }

        $this->logSection('replicate', "Running: $cmd", null, 'COMMENT');

        $output = [];
        $returnCode = 0;
        exec($cmd.' 2>&1', $output, $returnCode);

        // Count files from output
        $files = 0;
        foreach ($output as $line) {
            if (str_starts_with($line, 'upload:') || str_starts_with($line, '(dryrun) upload:')) {
                ++$files;
                if ($dryRun) {
                    $this->logSection('replicate', '  '.$line);
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

    protected function addTarget()
    {
        $this->logSection('replicate', 'Adding a new replication target is not yet supported via CLI');
        $this->logSection('replicate', 'Please add targets via SQL:');
        $this->logSection('replicate', '');
        $this->logSection('replicate', "INSERT INTO preservation_replication_target");
        $this->logSection('replicate', "  (name, target_type, target_path, is_active)");
        $this->logSection('replicate', "VALUES");
        $this->logSection('replicate', "  ('offsite-backup', 'local', '/mnt/backup/atom', 1);");
    }
}
