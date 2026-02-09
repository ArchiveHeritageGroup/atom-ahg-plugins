<?php

class ahgReportsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Central reporting dashboard for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'reports';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('reports');

        // Central Dashboard
        $router->any('admin_dashboard', '/admin/dashboard', 'index');

        // Legacy URL redirect
        $router->any('reports_index', '/reports', 'index');

        // Centralized Report System
        $router->any('report_view', '/reports/view/:code', 'report', ['code' => '[a-z_]+']);

        // Report type routes
        $router->any('reports_descriptions', '/reports/descriptions', 'descriptions');
        $router->any('reports_authorities', '/reports/authorities', 'archival');
        $router->any('reports_repositories', '/reports/repositories', 'repositories');
        $router->any('reports_accessions', '/reports/accessions', 'accessions');
        $router->any('reports_storage', '/reports/storage', 'storage');
        $router->any('reports_recent', '/reports/recent', 'recent');
        $router->any('reports_activity', '/reports/activity', 'activity');

        // Spatial Analysis Export
        $router->any('reports_spatial_analysis', '/reports/spatial-analysis', 'reportSpatialAnalysis');

        $router->register($event->getSubject());
    }
}
