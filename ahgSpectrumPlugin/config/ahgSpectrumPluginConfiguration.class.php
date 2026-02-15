<?php
class ahgSpectrumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Spectrum 5.1 Museum Procedures Plugin';
    public static $version = '1.1.5';
    public static $dependencies = [];
    public static $dependents = [];

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'spectrum';
        $enabledModules[] = 'spectrumReports';
        $enabledModules[] = 'spectrumApi';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Spectrum core module
        $spectrum = new \AtomFramework\Routing\RouteLoader('spectrum');

        // Core spectrum routes
        $spectrum->any('spectrum_index', '/:slug/spectrum', 'index');
        $spectrum->any('spectrum_label', '/:slug/spectrum/label', 'label');
        $spectrum->any('spectrum_workflow', '/spectrum/:slug/workflow', 'workflow');
        $spectrum->any('spectrum_workflow_update', '/spectrum/:slug/workflow/update', 'workflowUpdate');
        $spectrum->any('spectrum_workflow_transition', '/spectrum/:slug/workflow/transition', 'workflowTransition');

        // Dashboard routes
        $spectrum->any('spectrum_dashboard', '/spectrum/dashboard', 'dashboard');
        $spectrum->any('spectrum_my_tasks', '/spectrum/my-tasks', 'myTasks');
        $spectrum->any('spectrum_grap_dashboard', '/:slug/spectrum/grap', 'grapDashboard');
        $spectrum->any('spectrum_loan_dashboard', '/spectrum/loans', 'loanDashboard');

        // General (institution-level) procedures
        $spectrum->any('spectrum_general', '/spectrum/general', 'general');
        $spectrum->any('spectrum_general_workflow', '/spectrum/general/workflow', 'generalWorkflow');
        $spectrum->any('spectrum_general_workflow_transition', '/spectrum/general/workflow/transition', 'generalWorkflowTransition');

        // Condition routes
        $spectrum->any('spectrum_condition_photos', '/:slug/spectrum/condition-photos', 'conditionPhotos');
        $spectrum->any('spectrum_condition_report', '/:slug/spectrum/condition-report', 'conditionReport');
        $spectrum->any('spectrum_condition_check', '/:slug/spectrum/conditionCheck', 'conditionCheck');

        // Compliance routes
        $spectrum->any('spectrum_security_compliance', '/:slug/spectrum/security', 'securityCompliance');
        $spectrum->any('spectrum_privacy_compliance', '/:slug/spectrum/privacy', 'privacyCompliance');
        $spectrum->any('spectrum_privacy_ropa', '/spectrum/ropa', 'ropa');

        // Annotation routes
        $spectrum->any('spectrum_annotation_save', '/spectrum/annotation/save', 'saveAnnotation');
        $spectrum->any('spectrum_annotation_get', '/spectrum/annotation/get/:photo_id', 'getAnnotation');

        // Photo management
        $spectrum->any('spectrum_photo_delete', '/spectrum/photo/delete/:photo_id', 'deletePhoto');
        $spectrum->any('spectrum_photo_primary', '/spectrum/photo/primary/:photo_id', 'setPrimaryPhoto');
        $spectrum->any('spectrum_photo_rotate', '/spectrum/photo/rotate/:photo_id', 'rotatePhoto');

        // Provenance
        $spectrum->any('spectrum_provenance_ajax', '/spectrum/provenance/ajax', 'provenanceAjax');

        // Admin routes
        $spectrum->any('spectrum_install', '/spectrum/install', 'install');
        $spectrum->any('spectrum_export', '/spectrum/export', 'export');
        $spectrum->any('spectrum_template_config', '/spectrum/config/templates', 'templateConfig');

        $spectrum->register($routing);

        // Spectrum API module
        $spectrumApi = new \AtomFramework\Routing\RouteLoader('spectrumApi');
        $spectrumApi->any('spectrum_api_events', '/api/spectrum/events', 'spectrumEvents');
        $spectrumApi->any('spectrum_api_object_events', '/api/spectrum/objects/:object_id/events', 'spectrumObjectEvents');
        $spectrumApi->any('spectrum_api_statistics', '/api/spectrum/statistics', 'spectrumStatistics');
        $spectrumApi->register($routing);

        // Spectrum Reports module
        $spectrumReports = new \AtomFramework\Routing\RouteLoader('spectrumReports');
        $spectrumReports->any('spectrum_reports_index', '/spectrumReports', 'index');
        $spectrumReports->any('spectrum_reports_loans', '/spectrumReports/loans', 'loans');
        $spectrumReports->any('spectrum_reports_conditions', '/spectrumReports/conditions', 'conditions');
        $spectrumReports->any('spectrum_reports_valuations', '/spectrumReports/valuations', 'valuations');
        $spectrumReports->any('spectrum_reports_movements', '/spectrumReports/movements', 'movements');
        $spectrumReports->any('spectrum_reports_acquisitions', '/spectrumReports/acquisitions', 'acquisitions');
        $spectrumReports->any('spectrum_reports_conservation', '/spectrumReports/conservation', 'conservation');
        $spectrumReports->any('spectrum_reports_object_entry', '/spectrumReports/objectEntry', 'objectEntry');
        $spectrumReports->register($routing);
    }
}
