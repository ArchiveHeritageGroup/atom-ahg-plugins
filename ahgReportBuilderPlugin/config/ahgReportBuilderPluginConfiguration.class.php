<?php

class ahgReportBuilderPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Custom report builder with drag-drop designer, rich text sections, charts, scheduling, and export';
    public static $version = '2.0.0';

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

        // ========================================
        // Attachment API routes
        // ========================================
        $router->any('report_builder_api_attachment_delete', '/api/report-builder/attachment/:id/delete', 'apiAttachmentDelete', ['id' => '\d+']);
        $router->any('report_builder_api_attachment_upload', '/api/report-builder/attachment/upload', 'apiAttachmentUpload');
        $router->any('report_builder_api_attachments', '/api/report-builder/attachments', 'apiAttachments');

        // ========================================
        // Share API routes
        // ========================================
        $router->any('report_builder_api_share_deactivate', '/api/report-builder/share/:id/deactivate', 'apiShareDeactivate', ['id' => '\d+']);
        $router->any('report_builder_api_share_create', '/api/report-builder/share/create', 'apiShareCreate');

        // ========================================
        // Snapshot/Data Binding API routes
        // ========================================
        $router->any('report_builder_api_snapshot', '/api/report-builder/snapshot', 'apiSnapshot');

        // ========================================
        // Query API routes
        // ========================================
        $router->any('report_builder_api_query_relationships', '/api/report-builder/query/relationships/:table', 'apiQueryRelationships');
        $router->any('report_builder_api_query_columns', '/api/report-builder/query/columns/:table', 'apiQueryColumns');
        $router->any('report_builder_api_query_tables', '/api/report-builder/query/tables', 'apiQueryTables');
        $router->any('report_builder_api_query_validate', '/api/report-builder/query/validate', 'apiQueryValidate');
        $router->any('report_builder_api_query_save', '/api/report-builder/query/save', 'apiQuerySave');
        $router->any('report_builder_api_query_execute', '/api/report-builder/query/execute', 'apiQueryExecute');

        // ========================================
        // Version API routes
        // ========================================
        $router->any('report_builder_api_version_restore', '/api/report-builder/version/restore', 'apiVersionRestore');
        $router->any('report_builder_api_version_create', '/api/report-builder/version/create', 'apiVersionCreate');
        $router->any('report_builder_api_versions', '/api/report-builder/versions/:id', 'apiVersions', ['id' => '\d+']);

        // ========================================
        // Comment API routes
        // ========================================
        $router->any('report_builder_api_comment', '/api/report-builder/comment', 'apiComment');

        // ========================================
        // Workflow API routes
        // ========================================
        $router->any('report_builder_api_status_change', '/api/report-builder/status', 'apiStatusChange');

        // ========================================
        // Template API routes
        // ========================================
        $router->any('report_builder_api_template_delete', '/api/report-builder/template/:id/delete', 'apiTemplateDelete', ['id' => '\d+']);
        $router->any('report_builder_api_template_apply', '/api/report-builder/template/apply', 'apiTemplateApply');
        $router->any('report_builder_api_template_save', '/api/report-builder/template/save', 'apiTemplateSave');

        // ========================================
        // Link API routes
        // ========================================
        $router->any('report_builder_api_entity_search', '/api/report-builder/entity-search', 'apiEntitySearch');
        $router->any('report_builder_api_og_fetch', '/api/report-builder/og-fetch', 'apiOgFetch');
        $router->any('report_builder_api_link_delete', '/api/report-builder/link/:id/delete', 'apiLinkDelete', ['id' => '\d+']);
        $router->any('report_builder_api_link_save', '/api/report-builder/link/save', 'apiLinkSave');

        // ========================================
        // Section API routes
        // ========================================
        $router->any('report_builder_api_section_reorder', '/api/report-builder/section/reorder', 'apiSectionReorder');
        $router->any('report_builder_api_section_delete', '/api/report-builder/section/:id/delete', 'apiSectionDelete', ['id' => '\d+']);
        $router->any('report_builder_api_section_save', '/api/report-builder/section/save', 'apiSectionSave');

        // ========================================
        // Widget API routes (existing)
        // ========================================
        $router->any('report_builder_api_widget_delete', '/api/report-builder/widget/:id/delete', 'apiWidgetDelete', ['id' => '\d+']);
        $router->any('report_builder_api_widget_save', '/api/report-builder/widget/save', 'apiWidgetSave');
        $router->any('report_builder_api_widgets', '/api/report-builder/widgets', 'apiWidgets');

        // Widget embed route
        $router->any('report_builder_widget', '/report-widget/:id', 'widget', ['id' => '\d+']);

        // ========================================
        // Core API routes (existing)
        // ========================================
        $router->any('report_builder_api_columns', '/api/report-builder/columns/:source', 'apiColumns');
        $router->any('report_builder_api_data', '/api/report-builder/data', 'apiData');
        $router->any('report_builder_api_chart_data', '/api/report-builder/chart-data', 'apiChartData');
        $router->any('report_builder_api_save', '/api/report-builder/save', 'apiSave');
        $router->any('report_builder_api_delete', '/api/report-builder/delete/:id', 'apiDelete', ['id' => '\d+']);

        // ========================================
        // Public routes
        // ========================================
        $router->any('report_builder_shared_view', '/reports/shared/:token', 'sharedView');
        $router->any('report_builder_view', '/reports/custom/:id', 'view', ['id' => '\d+']);

        // ========================================
        // Admin page routes
        // ========================================
        // Schedule management
        $router->any('report_builder_schedule_delete', '/admin/report-builder/:id/schedule/:scheduleId/delete', 'scheduleDelete', ['id' => '\d+', 'scheduleId' => '\d+']);
        $router->any('report_builder_schedule', '/admin/report-builder/:id/schedule', 'schedule', ['id' => '\d+']);

        // Export (now includes docx)
        $router->any('report_builder_export', '/admin/report-builder/:id/export/:format', 'export', ['id' => '\d+', 'format' => 'pdf|xlsx|csv|docx']);

        // Query builder
        $router->any('report_builder_query', '/admin/report-builder/:id/query', 'query', ['id' => '\d+']);

        // Version history
        $router->any('report_builder_history', '/admin/report-builder/:id/history', 'history', ['id' => '\d+']);

        // Preview
        $router->any('report_builder_preview', '/admin/report-builder/:id/preview', 'preview', ['id' => '\d+']);

        // Clone
        $router->any('report_builder_clone', '/admin/report-builder/:id/clone', 'cloneReport', ['id' => '\d+']);

        // Edit
        $router->any('report_builder_edit', '/admin/report-builder/:id/edit', 'edit', ['id' => '\d+']);

        // Delete
        $router->any('report_builder_delete', '/admin/report-builder/:id/delete', 'delete', ['id' => '\d+']);

        // Templates library
        $router->any('report_builder_templates', '/admin/report-builder/templates', 'templates');

        // Create
        $router->any('report_builder_create', '/admin/report-builder/create', 'create');

        // Archive
        $router->any('report_builder_archive', '/admin/report-builder/archive', 'archive');

        // Index (must be last of admin routes)
        $router->any('report_builder_index', '/admin/report-builder', 'index');

        $router->register($event->getSubject());
    }
}
