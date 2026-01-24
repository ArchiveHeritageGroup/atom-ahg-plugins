<?php

/**
 * ahgDataMigrationPlugin configuration.
 * 
 * Provides data migration capabilities for importing/exporting
 * archival descriptions from various systems including Preservica.
 */
class ahgDataMigrationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Data migration tools for importing and exporting archival data';
    public static $version = '1.2.0';

    public function contextLoadFactories(sfEvent $event)
    {
        // Load framework for services
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'preservica';
        $enabledModules[] = 'dataMigration';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));

        // Connect to context.load_factories for framework loading
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        
        // Tasks are auto-discovered by Symfony from lib/task/ directory
        // No need to manually register them
    }

    /**
     * Get plugin info for display.
     */
    public static function getPluginInfo()
    {
        return [
            'name'        => 'Data Migration Plugin',
            'version'     => self::$version,
            'description' => self::$summary,
            'author'      => 'The Archive and Heritage Group',
            'features'    => [
                'Import from Preservica OPEX/PAX',
                'Export to Preservica OPEX/PAX',
                'Import from ArchivesSpace, Vernon, PastPerfect',
                'Custom field mapping',
                'Batch processing',
                'Digital object handling',
            ],
        ];
    }
}
