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
        $routing = $event->getSubject();

        // Widget API routes
        $routing->prependRoute('report_builder_api_widget_delete', new sfRoute(
            '/api/report-builder/widget/:id/delete',
            ['module' => 'reportBuilder', 'action' => 'apiWidgetDelete'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('report_builder_api_widget_save', new sfRoute(
            '/api/report-builder/widget/save',
            ['module' => 'reportBuilder', 'action' => 'apiWidgetSave']
        ));
        $routing->prependRoute('report_builder_api_widgets', new sfRoute(
            '/api/report-builder/widgets',
            ['module' => 'reportBuilder', 'action' => 'apiWidgets']
        ));

        // Widget embed route
        $routing->prependRoute('report_builder_widget', new sfRoute(
            '/report-widget/:id',
            ['module' => 'reportBuilder', 'action' => 'widget'],
            ['id' => '\d+']
        ));

        // API routes (must be first due to route priority)
        $routing->prependRoute('report_builder_api_columns', new sfRoute(
            '/api/report-builder/columns/:source',
            ['module' => 'reportBuilder', 'action' => 'apiColumns']
        ));
        $routing->prependRoute('report_builder_api_data', new sfRoute(
            '/api/report-builder/data',
            ['module' => 'reportBuilder', 'action' => 'apiData']
        ));
        $routing->prependRoute('report_builder_api_chart_data', new sfRoute(
            '/api/report-builder/chart-data',
            ['module' => 'reportBuilder', 'action' => 'apiChartData']
        ));
        $routing->prependRoute('report_builder_api_save', new sfRoute(
            '/api/report-builder/save',
            ['module' => 'reportBuilder', 'action' => 'apiSave']
        ));
        $routing->prependRoute('report_builder_api_delete', new sfRoute(
            '/api/report-builder/delete/:id',
            ['module' => 'reportBuilder', 'action' => 'apiDelete'],
            ['id' => '\d+']
        ));

        // Public view (for shared/public reports)
        $routing->prependRoute('report_builder_view', new sfRoute(
            '/reports/custom/:id',
            ['module' => 'reportBuilder', 'action' => 'view'],
            ['id' => '\d+']
        ));

        // Schedule management
        $routing->prependRoute('report_builder_schedule_delete', new sfRoute(
            '/admin/report-builder/:id/schedule/:scheduleId/delete',
            ['module' => 'reportBuilder', 'action' => 'scheduleDelete'],
            ['id' => '\d+', 'scheduleId' => '\d+']
        ));
        $routing->prependRoute('report_builder_schedule', new sfRoute(
            '/admin/report-builder/:id/schedule',
            ['module' => 'reportBuilder', 'action' => 'schedule'],
            ['id' => '\d+']
        ));

        // Export
        $routing->prependRoute('report_builder_export', new sfRoute(
            '/admin/report-builder/:id/export/:format',
            ['module' => 'reportBuilder', 'action' => 'export'],
            ['id' => '\d+', 'format' => 'pdf|xlsx|csv']
        ));

        // Preview
        $routing->prependRoute('report_builder_preview', new sfRoute(
            '/admin/report-builder/:id/preview',
            ['module' => 'reportBuilder', 'action' => 'preview'],
            ['id' => '\d+']
        ));

        // Clone
        $routing->prependRoute('report_builder_clone', new sfRoute(
            '/admin/report-builder/:id/clone',
            ['module' => 'reportBuilder', 'action' => 'cloneReport'],
            ['id' => '\d+']
        ));

        // Edit
        $routing->prependRoute('report_builder_edit', new sfRoute(
            '/admin/report-builder/:id/edit',
            ['module' => 'reportBuilder', 'action' => 'edit'],
            ['id' => '\d+']
        ));

        // Delete
        $routing->prependRoute('report_builder_delete', new sfRoute(
            '/admin/report-builder/:id/delete',
            ['module' => 'reportBuilder', 'action' => 'delete'],
            ['id' => '\d+']
        ));

        // Create
        $routing->prependRoute('report_builder_create', new sfRoute(
            '/admin/report-builder/create',
            ['module' => 'reportBuilder', 'action' => 'create']
        ));

        // Archive
        $routing->prependRoute('report_builder_archive', new sfRoute(
            '/admin/report-builder/archive',
            ['module' => 'reportBuilder', 'action' => 'archive']
        ));

        // Index (must be last of admin routes)
        $routing->prependRoute('report_builder_index', new sfRoute(
            '/admin/report-builder',
            ['module' => 'reportBuilder', 'action' => 'index']
        ));
    }
}
