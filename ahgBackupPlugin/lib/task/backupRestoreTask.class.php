<?php

/**
 * CLI task: restore a backup.
 *
 * The restore pages start this in the background: restoring a real database
 * outlasts a web request (php-fpm stops a request after a few minutes, which
 * could leave a restore half-applied). Progress is written to
 * <backup_path>/restore-status.json, which the page reads (backup/restoreStatus).
 *
 * Usage:
 *   php symfony backup:restore --id=<backup id> --database
 *   php symfony backup:restore --upload=<upload id> --database --uploads
 */
class backupRestoreTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_REQUIRED, 'Backup id (a folder in the backup path)'),
            new sfCommandOption('upload', null, sfCommandOption::PARAMETER_REQUIRED, 'Uploaded backup id (a folder in <backup path>/staging)'),
            new sfCommandOption('database', null, sfCommandOption::PARAMETER_NONE, 'Restore the database'),
            new sfCommandOption('uploads', null, sfCommandOption::PARAMETER_NONE, 'Restore uploads'),
            new sfCommandOption('plugins', null, sfCommandOption::PARAMETER_NONE, 'Restore the AHG plugins'),
            new sfCommandOption('framework', null, sfCommandOption::PARAMETER_NONE, 'Restore the AHG framework'),
        ]);

        $this->namespace = 'backup';
        $this->name = 'restore';
        $this->briefDescription = 'Restore a backup';
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        \AhgCore\Core\AhgDb::init();

        $backupPath = (new \AtomExtensions\Services\BackupSettingsService())->get('backup_path', '/var/backups/atom');
        $id = (string) ($options['id'] ?? '');
        $upload = (string) ($options['upload'] ?? '');
        $source = $id ?: $upload;
        if ('' === $source || str_contains($source, '/') || str_contains($source, '..')) {
            $this->logSection('restore', 'Pass a valid --id or --upload', null, 'ERROR');

            return 1;
        }

        $restore = [];
        foreach (['database', 'uploads', 'plugins', 'framework'] as $c) {
            $restore['restore_'.$c] = !empty($options[$c]);
        }

        $status = function (string $state, string $message = '') use ($backupPath, $source, $restore) {
            file_put_contents($backupPath.'/restore-status.json', json_encode([
                'source' => $source, 'state' => $state, 'message' => $message,
                'components' => array_keys(array_filter($restore)), 'at' => date('Y-m-d H:i:s'),
            ]));
        };

        $status('running');
        $this->logSection('restore', "Restoring {$source}");
        try {
            // restoreFromPath() for both: restoreBackup() only handled the database,
            // so ticking uploads, plugins or framework on the restore page did nothing.
            $dir = $id ? $backupPath.'/'.$id : $backupPath.'/staging/'.$upload;
            $results = (new \AtomExtensions\Services\BackupService())->restoreFromPath($dir, $restore);
            foreach ($results as $component => $r) {
                if (in_array($r['status'] ?? '', ['skipped', 'error'], true)) {
                    throw new \RuntimeException(ucfirst($component).' not restored: '.($r['reason'] ?? $r['message'] ?? 'nothing to restore'));
                }
            }
            if ($upload) {
                $manifestFile = $backupPath.'/staging/'.$upload.'/upload_manifest.json';
                if (is_file($manifestFile)) {
                    $manifest = json_decode((string) file_get_contents($manifestFile), true) ?: [];
                    $manifest['status'] = 'restored';
                    $manifest['restored_at'] = date('Y-m-d H:i:s');
                    $manifest['restore_options'] = $restore;
                    file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
                }
            }
        } catch (\Throwable $e) {
            $status('failed', $e->getMessage());
            $this->logSection('restore', 'Failed: '.$e->getMessage(), null, 'ERROR');

            return 1;
        }

        $status('completed');
        $this->logSection('restore', 'Completed');

        return 0;
    }
}
