<?php

class ahgExportPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Archival export functionality for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'export';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('export');

        $router->any('export_index', '/export', 'index');
        $router->any('export_archival', '/export/archival', 'archival');
        $router->any('export_authority', '/export/authority', 'authority');
        $router->any('export_repository', '/export/repository', 'repository');
        $router->any('export_csv', '/export/csv', 'archival');
        $router->any('export_ead', '/export/ead', 'archival');
        $router->any('export_grap', '/export/grap', 'archival');
        $router->any('export_authorities', '/export/authorities', 'authority');

        // Legacy route for object/export
        $router->any('object_export', '/object/export', 'index');

        $router->register($event->getSubject());
    }
}
