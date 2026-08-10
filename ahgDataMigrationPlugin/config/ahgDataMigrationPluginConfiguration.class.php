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

        // Navigation entry, contributed by the plugin rather than named by the
        // theme.
        //
        // These modules were reachable only because ahgThemeB5Plugin hardcodes
        // them in its own admin menu, so on a stock AtoM install - which is what
        // the published bundles target - the plugin installed, worked, and had
        // nowhere to click from. ahgCorePlugin renders these into AtoM's own
        // quick-links menu when the theme is absent, so the entry now follows
        // the plugin and disappears with it. Issue #292.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'dataMigration_index', [
                'route' => ['module' => 'dataMigration', 'action' => 'index'],
                'label' => 'Data migration',
                'credentials' => ['administrator'],
                'weight' => 310,
            ]);
        }
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
