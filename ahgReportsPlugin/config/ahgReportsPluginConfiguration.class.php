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

        // Navigation entry, contributed by the plugin rather than named by the theme.
        //
        // "Central Dashboard" was hardcoded in ahgThemeB5Plugin's own menu templates,
        // so on an instance running the stock arDominion theme the plugin installed,
        // routed and worked with nothing anywhere to click - observed on RARI, 18
        // August 2026, where /reports answered 200 the whole time it looked absent.
        // Registering here means the entry follows the plugin and disappears with it.
        // Same shape as the ahgSecurityClearancePlugin fix, issue #292.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'reports_dashboard', [
                // A route NAME, not a module/action array: AhgNav::knownRoute() is
                // typed string and an array raises a TypeError from inside the layout,
                // which empties every page rather than just this entry.
                'route' => '@admin_dashboard',
                'label' => 'Central Dashboard',
                'credentials' => ['administrator', 'editor'],
                'weight' => 900,
            ]);
        }

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
        $router->any('reports_authorities', '/reports/authorities', 'authorities');
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
