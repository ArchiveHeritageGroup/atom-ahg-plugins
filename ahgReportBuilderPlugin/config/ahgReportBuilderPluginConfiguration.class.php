<?php

class ahgReportBuilderPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Custom report builder with drag-drop designer, charts, scheduling, and export';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'reportBuilder';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('reportBuilder');

        // Widget API routes
        $router->any('report_builder_api_widget_delete', '/api/report-builder/widget/:id/delete', 'apiWidgetDelete', ['id' => '\d+']);
        $router->any('report_builder_api_widget_save', '/api/report-builder/widget/save', 'apiWidgetSave');
        $router->any('report_builder_api_widgets', '/api/report-builder/widgets', 'apiWidgets');

        // Widget embed route
        $router->any('report_builder_widget', '/report-widget/:id', 'widget', ['id' => '\d+']);

        // API routes (must be first due to route priority)
        $router->any('report_builder_api_columns', '/api/report-builder/columns/:source', 'apiColumns');
        $router->any('report_builder_api_data', '/api/report-builder/data', 'apiData');
        $router->any('report_builder_api_chart_data', '/api/report-builder/chart-data', 'apiChartData');
        $router->any('report_builder_api_save', '/api/report-builder/save', 'apiSave');
        $router->any('report_builder_api_delete', '/api/report-builder/delete/:id', 'apiDelete', ['id' => '\d+']);

        // Public view (for shared/public reports)
        $router->any('report_builder_view', '/reports/custom/:id', 'view', ['id' => '\d+']);

        // Schedule management
        $router->any('report_builder_schedule_delete', '/admin/report-builder/:id/schedule/:scheduleId/delete', 'scheduleDelete', ['id' => '\d+', 'scheduleId' => '\d+']);
        $router->any('report_builder_schedule', '/admin/report-builder/:id/schedule', 'schedule', ['id' => '\d+']);

        // Export
        $router->any('report_builder_export', '/admin/report-builder/:id/export/:format', 'export', ['id' => '\d+', 'format' => 'pdf|xlsx|csv']);

        // Preview
        $router->any('report_builder_preview', '/admin/report-builder/:id/preview', 'preview', ['id' => '\d+']);

        // Clone
        $router->any('report_builder_clone', '/admin/report-builder/:id/clone', 'cloneReport', ['id' => '\d+']);

        // Edit
        $router->any('report_builder_edit', '/admin/report-builder/:id/edit', 'edit', ['id' => '\d+']);

        // Delete
        $router->any('report_builder_delete', '/admin/report-builder/:id/delete', 'delete', ['id' => '\d+']);

        // Create
        $router->any('report_builder_create', '/admin/report-builder/create', 'create');

        // Archive
        $router->any('report_builder_archive', '/admin/report-builder/archive', 'archive');

        // Index (must be last of admin routes)
        $router->any('report_builder_index', '/admin/report-builder', 'index');

        $router->register($event->getSubject());
    }
}
