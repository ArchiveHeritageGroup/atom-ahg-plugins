<?php

class ahgDataMigrationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Universal data migration tool for importing from external systems';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        
        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'dataMigration';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();
        
        $routing->prependRoute('dataMigration_index', new sfRoute(
            '/admin/data-migration',
            ['module' => 'dataMigration', 'action' => 'index']
        ));
        $routing->prependRoute('dataMigration_upload', new sfRoute(
            '/admin/data-migration/upload',
            ['module' => 'dataMigration', 'action' => 'upload']
        ));
        $routing->prependRoute('dataMigration_map', new sfRoute(
            '/admin/data-migration/map',
            ['module' => 'dataMigration', 'action' => 'map']
        ));
        $routing->prependRoute('dataMigration_preview', new sfRoute(
            '/admin/data-migration/preview',
            ['module' => 'dataMigration', 'action' => 'preview']
        ));
        $routing->prependRoute('dataMigration_execute', new sfRoute(
            '/admin/data-migration/execute',
            ['module' => 'dataMigration', 'action' => 'execute']
        ));
        $routing->prependRoute('dataMigration_saveMapping', new sfRoute(
            '/admin/data-migration/save-mapping',
            ['module' => 'dataMigration', 'action' => 'saveMapping']
        ));
        $routing->prependRoute('dataMigration_loadMapping', new sfRoute(
            '/admin/data-migration/load-mapping/:id',
            ['module' => 'dataMigration', 'action' => 'loadMapping']
        ));
        $routing->prependRoute('dataMigration_getPreview', new sfRoute(
            '/admin/data-migration/get-preview',
            ['module' => 'dataMigration', 'action' => 'getPreview']
        ));
    }
}
