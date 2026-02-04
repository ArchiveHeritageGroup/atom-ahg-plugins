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
        $routing = $event->getSubject();

        // Central Dashboard
        $routing->prependRoute('admin_dashboard', new sfRoute('/admin/dashboard', [
            'module' => 'reports',
            'action' => 'index',
        ]));

        // Legacy URL redirect
        $routing->prependRoute('reports_index', new sfRoute('/reports', [
            'module' => 'reports',
            'action' => 'index',
        ]));

        // Centralized Report System
        $routing->prependRoute('report_view', new sfRoute('/reports/view/:code', [
            'module' => 'reports',
            'action' => 'report',
        ], [
            'code' => '[a-z_]+',
        ]));

        // Report type routes
        $routing->prependRoute('reports_descriptions', new sfRoute('/reports/descriptions', [
            'module' => 'reports',
            'action' => 'descriptions',
        ]));

        $routing->prependRoute('reports_authorities', new sfRoute('/reports/authorities', [
            'module' => 'reports',
            'action' => 'archival',
        ]));

        $routing->prependRoute('reports_repositories', new sfRoute('/reports/repositories', [
            'module' => 'reports',
            'action' => 'repositories',
        ]));

        $routing->prependRoute('reports_accessions', new sfRoute('/reports/accessions', [
            'module' => 'reports',
            'action' => 'accessions',
        ]));

        $routing->prependRoute('reports_storage', new sfRoute('/reports/storage', [
            'module' => 'reports',
            'action' => 'storage',
        ]));

        $routing->prependRoute('reports_recent', new sfRoute('/reports/recent', [
            'module' => 'reports',
            'action' => 'recent',
        ]));

        $routing->prependRoute('reports_activity', new sfRoute('/reports/activity', [
            'module' => 'reports',
            'action' => 'activity',
        ]));

        // Spatial Analysis Export
        $routing->prependRoute('reports_spatial_analysis', new sfRoute('/reports/spatial-analysis', [
            'module' => 'reports',
            'action' => 'reportSpatialAnalysis',
        ]));
    }
}
