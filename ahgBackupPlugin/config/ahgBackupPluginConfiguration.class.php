<?php

class ahgBackupPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Backup and restore functionality for AtoM';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkBootstrap) && !class_exists('AtomExtensions\Services\BackupService', false)) {
            require_once $frameworkBootstrap;
        }
    }

    public function initialize()
    {

        // Contribute this plugin's own navigation entry.
        //
        // Registered here rather than named by the theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'backup', [
                'url' => '/index.php/backup',
                'label' => 'Backup & Restore',
            'credentials' => ['administrator'],
                'weight' => 80,
            ]);
        }
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Contribute the Manage menu entry at runtime rather than as a `menu` row.
        // A database row outlives plugin enablement, so disabling the plugin used to
        // leave a dead entry pointing at a module that no longer loads.
        require_once __DIR__ . '/../lib/Listeners/MenuInjector.php';
        $this->dispatcher->connect('response.filter_content', ['\AhgBackup\Listeners\MenuInjector', 'filter']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'backup';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
