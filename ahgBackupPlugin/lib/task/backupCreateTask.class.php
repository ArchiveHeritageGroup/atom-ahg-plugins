<?php

/**
 * CLI task: create one backup now.
 *
 * The Backup page starts this in the background ("Create Backup"): a real
 * database dump outlasts a web request (php-fpm stops a request after a few
 * minutes), so the page only launches it and follows backup_history.
 *
 * Usage:
 *   php symfony backup:create --database
 *   php symfony backup:create --database --uploads --plugins --framework
 */
class backupCreateTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('database', null, sfCommandOption::PARAMETER_NONE, 'Include the database'),
            new sfCommandOption('uploads', null, sfCommandOption::PARAMETER_NONE, 'Include uploads (digital objects)'),
            new sfCommandOption('plugins', null, sfCommandOption::PARAMETER_NONE, 'Include the AHG plugins'),
            new sfCommandOption('framework', null, sfCommandOption::PARAMETER_NONE, 'Include the AHG framework'),
            new sfCommandOption('fuseki', null, sfCommandOption::PARAMETER_NONE, 'Include the Fuseki (RiC) triplestore'),
        ]);

        $this->namespace = 'backup';
        $this->name = 'create';
        $this->briefDescription = 'Create a backup now';
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        \AhgCore\Core\AhgDb::init();

        $components = [];
        foreach (['database', 'uploads', 'plugins', 'framework', 'fuseki'] as $c) {
            $components[$c] = !empty($options[$c]);
        }
        if (!in_array(true, $components, true)) {
            $this->logSection('backup', 'Nothing to back up: pass --database, --uploads, --plugins or --framework', null, 'ERROR');

            return 1;
        }

        $this->logSection('backup', 'Starting: '.implode(', ', array_keys(array_filter($components))));
        $result = (new \AtomExtensions\Services\BackupService())->createBackup($components + ['type' => 'manual']);

        if ('completed' === ($result['status'] ?? '')) {
            $this->logSection('backup', "Completed: {$result['id']}");

            return 0;
        }
        $this->logSection('backup', 'Failed: '.($result['error'] ?? 'unknown error'), null, 'ERROR');

        return 1;
    }
}
