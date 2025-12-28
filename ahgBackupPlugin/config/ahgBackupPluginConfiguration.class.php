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
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'backup';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
