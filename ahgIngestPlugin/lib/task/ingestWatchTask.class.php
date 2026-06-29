<?php

/**
 * Hot-folder watcher for unattended auto-ingest.
 *
 * Scans every enabled row in `ingest_watch_folder`. For each folder, any new
 * top-level files are moved into a per-run batch dir, registered as a
 * directory-type ingest source using the folder's snapshotted template config,
 * committed through the normal pipeline, then moved to a .processed/<ts>/
 * subfolder so they are not ingested again.
 *
 * Intended to run from cron (as www-data):
 *   php symfony ingest:watch
 *   php symfony ingest:watch --id=3        # one folder only
 *   php symfony ingest:watch --dry-run     # report what would happen
 */
class ingestWatchTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process only this watch-folder id'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report new files without ingesting'),
        ]);

        $this->namespace = 'ingest';
        $this->name = 'watch';
        $this->briefDescription = 'Auto-ingest new files dropped in watched (hot) folders';
        $this->detailedDescription = <<<'EOF'
The [ingest:watch|INFO] task scans the watched folders registered in
ingest_watch_folder and auto-ingests any new files using each folder's
template config.

  [php symfony ingest:watch|INFO]
  [php symfony ingest:watch --id=3|INFO]
  [php symfony ingest:watch --dry-run|INFO]

Run it from cron (as www-data), e.g. every 15 minutes.
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin';
        require_once $pluginDir . '/lib/Services/IngestService.php';
        require_once $pluginDir . '/lib/Services/IngestCommitService.php';

        $db = '\Illuminate\Database\Capsule\Manager';
        if (!$db::schema()->hasTable('ingest_watch_folder')) {
            $this->logSection('ingest', 'ERROR: ingest_watch_folder table not found - run migration_watch_folder.sql', null, 'ERROR');

            return 1;
        }

        $dryRun = !empty($options['dry-run']);
        $svc = new \AhgIngestPlugin\Services\IngestService();
        $commitSvc = new \AhgIngestPlugin\Services\IngestCommitService();

        $q = $db::table('ingest_watch_folder')->where('is_enabled', 1);
        if (!empty($options['id'])) {
            $q->where('id', (int) $options['id']);
        }
        $folders = $q->get();

        if ($folders->isEmpty()) {
            $this->logSection('ingest', 'No enabled watched folders.');

            return 0;
        }

        foreach ($folders as $wf) {
            try {
                $this->processFolder($wf, $svc, $commitSvc, $dryRun);
            } catch (\Throwable $e) {
                $this->logSection('ingest', "Watch folder #{$wf->id} ERROR: " . $e->getMessage(), null, 'ERROR');
                if (!$dryRun) {
                    $db::table('ingest_watch_folder')->where('id', $wf->id)->update([
                        'last_scan_at' => date('Y-m-d H:i:s'),
                        'last_status' => 'ERROR: ' . substr($e->getMessage(), 0, 200),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        return 0;
    }

    private function processFolder(object $wf, $svc, $commitSvc, bool $dryRun): void
    {
        $db = '\Illuminate\Database\Capsule\Manager';
        $path = rtrim((string) $wf->watch_path, '/');

        if ($path === '' || !is_dir($path)) {
            $this->logSection('ingest', "Watch folder #{$wf->id}: path not found ({$wf->watch_path})", null, 'ERROR');
            $db::table('ingest_watch_folder')->where('id', $wf->id)->update([
                'last_scan_at' => date('Y-m-d H:i:s'),
                'last_status' => 'Path not found',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        // Collect new top-level files (skip dotfiles + the .processed/.processing dirs).
        $newFiles = [];
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $name = $item->getFilename();
            if ($name === '' || $name[0] === '.') {
                continue;
            }
            $newFiles[] = $item->getPathname();
        }

        if (empty($newFiles)) {
            $this->logSection('ingest', "Watch folder #{$wf->id} ({$path}): no new files.");
            $db::table('ingest_watch_folder')->where('id', $wf->id)->update([
                'last_scan_at' => date('Y-m-d H:i:s'),
                'last_status' => 'No new files',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $count = count($newFiles);
        if ($dryRun) {
            $this->logSection('ingest', "Watch folder #{$wf->id} ({$path}): would ingest {$count} file(s).");

            return;
        }

        $ts = date('YmdHis');
        $batchDir = $path . '/.processing_' . $ts;
        if (!is_dir($batchDir) && !mkdir($batchDir, 0755, true) && !is_dir($batchDir)) {
            throw new \RuntimeException("Could not create batch dir {$batchDir}");
        }

        $moved = 0;
        foreach ($newFiles as $src) {
            $dest = $batchDir . '/' . basename($src);
            if (@rename($src, $dest)) {
                ++$moved;
            }
        }
        if ($moved === 0) {
            throw new \RuntimeException('Could not move any files into the batch dir');
        }

        // Build template config from the stored JSON snapshot.
        $config = json_decode((string) $wf->config, true) ?: [];
        $config['title'] = 'Watch: ' . ($wf->label ?: basename($path)) . ' ' . $ts;
        $userId = (int) ($wf->user_id ?? 0) ?: 1;

        $sessionId = $svc->createSession($userId, $config);
        $svc->processUpload($sessionId, [
            'original_name' => 'Watched folder batch (' . $moved . ' files)',
            'stored_path' => $batchDir,
            'file_size' => 0,
            'mime_type' => 'directory',
        ]);
        $svc->parseRows($sessionId);
        $svc->updateSessionStatus($sessionId, 'commit');

        $jobId = $commitSvc->startJob($sessionId);
        $commitSvc->executeJob($jobId);

        // Archive the batch so it is not re-ingested.
        $processedDir = $path . '/.processed';
        if (!is_dir($processedDir)) {
            @mkdir($processedDir, 0755, true);
        }
        @rename($batchDir, $processedDir . '/' . $ts);

        $status = "Ingested {$moved} file(s) - session {$sessionId}, job {$jobId}";
        $this->logSection('ingest', "Watch folder #{$wf->id} ({$path}): {$status}");
        $db::table('ingest_watch_folder')->where('id', $wf->id)->update([
            'last_scan_at' => date('Y-m-d H:i:s'),
            'last_status' => $status,
            'files_ingested' => (int) $wf->files_ingested + $moved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
