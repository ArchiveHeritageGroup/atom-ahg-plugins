<?php
class ahgSpectrumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Spectrum 5.0 Museum Procedures Plugin';
    public static $version = '1.1.5';
    public static $dependencies = [];
    public static $dependents = [];

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'spectrum';
        $enabledModules[] = 'api';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Core spectrum routes
        $routing->prependRoute('spectrum_index', new sfRoute(
            '/:slug/spectrum',
            ['module' => 'spectrum', 'action' => 'index']
        ));

        $routing->prependRoute('spectrum_label', new sfRoute(
            '/:slug/spectrum/label',
            ['module' => 'spectrum', 'action' => 'label']
        ));

        $routing->prependRoute('spectrum_workflow', new sfRoute(
            '/spectrum/:slug/workflow',
            ['module' => 'spectrum', 'action' => 'workflow']
        ));

        $routing->prependRoute('spectrum_workflow_update', new sfRoute(
            '/spectrum/:slug/workflow/update',
            ['module' => 'spectrum', 'action' => 'workflowUpdate']
        ));

        $routing->prependRoute('spectrum_workflow_transition', new sfRoute(
            '/spectrum/:slug/workflow/transition',
            ['module' => 'spectrum', 'action' => 'workflowTransition']
        ));

        // Dashboard routes
        $routing->prependRoute('spectrum_dashboard', new sfRoute(
            '/spectrum/dashboard',
            ['module' => 'spectrum', 'action' => 'dashboard']
        ));

        $routing->prependRoute('spectrum_grap_dashboard', new sfRoute(
            '/spectrum/grap',
            ['module' => 'spectrum', 'action' => 'grapDashboard']
        ));

        $routing->prependRoute('spectrum_loan_dashboard', new sfRoute(
            '/spectrum/loans',
            ['module' => 'spectrum', 'action' => 'loanDashboard']
        ));

        // Condition routes
        $routing->prependRoute('spectrum_condition_photos', new sfRoute(
            '/:slug/spectrum/condition-photos',
            ['module' => 'spectrum', 'action' => 'conditionPhotos']
        ));

        $routing->prependRoute('spectrum_condition_report', new sfRoute(
            '/:slug/spectrum/condition-report',
            ['module' => 'spectrum', 'action' => 'conditionReport']
        ));

        $routing->prependRoute('spectrum_condition_check', new sfRoute(
            '/:slug/spectrum/conditionCheck',
            ['module' => 'spectrum', 'action' => 'conditionCheck']
        ));

        // Compliance routes
        $routing->prependRoute('spectrum_security_compliance', new sfRoute(
            '/:slug/spectrum/security',
            ['module' => 'spectrum', 'action' => 'securityCompliance']
        ));

        $routing->prependRoute('spectrum_privacy_compliance', new sfRoute(
            '/:slug/spectrum/privacy',
            ['module' => 'spectrum', 'action' => 'privacyCompliance']
        ));

        $routing->prependRoute('spectrum_privacy_ropa', new sfRoute(
            '/spectrum/ropa',
            ['module' => 'spectrum', 'action' => 'ropa']
        ));

        // Annotation routes
        $routing->prependRoute('spectrum_annotation_save', new sfRoute(
            '/spectrum/annotation/save',
            ['module' => 'spectrum', 'action' => 'saveAnnotation']
        ));

        $routing->prependRoute('spectrum_annotation_get', new sfRoute(
            '/spectrum/annotation/get/:photo_id',
            ['module' => 'spectrum', 'action' => 'getAnnotation']
        ));

        // Photo management
        $routing->prependRoute('spectrum_photo_delete', new sfRoute(
            '/spectrum/photo/delete/:photo_id',
            ['module' => 'spectrum', 'action' => 'deletePhoto']
        ));

        $routing->prependRoute('spectrum_photo_primary', new sfRoute(
            '/spectrum/photo/primary/:photo_id',
            ['module' => 'spectrum', 'action' => 'setPrimaryPhoto']
        ));

        $routing->prependRoute('spectrum_photo_rotate', new sfRoute(
            '/spectrum/photo/rotate/:photo_id',
            ['module' => 'spectrum', 'action' => 'rotatePhoto']
        ));

        // Provenance
        $routing->prependRoute('spectrum_provenance_ajax', new sfRoute(
            '/spectrum/provenance/ajax',
            ['module' => 'spectrum', 'action' => 'provenanceAjax']
        ));

        // Admin routes
        $routing->prependRoute('spectrum_install', new sfRoute(
            '/spectrum/install',
            ['module' => 'spectrum', 'action' => 'install']
        ));

        $routing->prependRoute('spectrum_export', new sfRoute(
            '/spectrum/export',
            ['module' => 'spectrum', 'action' => 'export']
        ));

        $routing->prependRoute('spectrum_template_config', new sfRoute(
            '/spectrum/config/templates',
            ['module' => 'spectrum', 'action' => 'templateConfig']
        ));

        // API routes
        $routing->prependRoute('spectrum_api_events', new sfRoute(
            '/api/spectrum/events',
            ['module' => 'api', 'action' => 'spectrumEvents']
        ));

        $routing->prependRoute('spectrum_api_object_events', new sfRoute(
            '/api/spectrum/objects/:object_id/events',
            ['module' => 'api', 'action' => 'spectrumObjectEvents']
        ));

        $routing->prependRoute('spectrum_api_statistics', new sfRoute(
            '/api/spectrum/statistics',
            ['module' => 'api', 'action' => 'spectrumStatistics']
        ));

        // Reports routes
        $routing->prependRoute('spectrum_reports_index', new sfRoute(
            '/spectrumReports/index',
            ['module' => 'settings', 'action' => 'spectrumReports']
        ));

        $routing->prependRoute('spectrum_reports_conditions', new sfRoute(
            '/spectrumReports/conditions',
            ['module' => 'settings', 'action' => 'conditionReports']
        ));
    }
}
