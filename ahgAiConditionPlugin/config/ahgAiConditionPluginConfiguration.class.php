<?php

/**
 * ahgAiConditionPlugin Configuration
 *
 * AI-powered condition assessment companion to ahgConditionPlugin.
 */
class ahgAiConditionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AI Condition Assessment - YOLOv8 damage detection for archival materials';
    public static $version = '1.0.0';
    public static $category = 'ai';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'aiCondition';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('response.filter_content', [$this, 'filterContent']);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('aiCondition');

        // Browse assessments
        $router->any('ai_condition_index', '/ai-condition', 'index');

        // Dashboard
        $router->any('ai_condition_dashboard', '/ai-condition/dashboard', 'dashboard');

        // Browse assessments
        $router->any('ai_condition_browse', '/ai-condition/browse', 'browse');

        // New assessment
        $router->any('ai_condition_assess', '/ai-condition/assess', 'assess');

        // View single assessment
        $router->any('ai_condition_view', '/ai-condition/view/:id', 'view', ['id' => '\d+']);

        // Condition history for an object
        $router->any('ai_condition_history', '/ai-condition/history/:slug', 'history');

        // Settings
        $router->any('ai_condition_settings', '/ai-condition/settings', 'settings');

        // Bulk scan
        $router->any('ai_condition_bulk', '/ai-condition/bulk', 'bulk');

        // SaaS client management
        $router->any('ai_condition_clients', '/ai-condition/clients', 'clients');

        // Manual assessment
        $router->any('ai_condition_manual_assess', '/ai-condition/manual-assess', 'manualAssess');

        // Model training
        $router->any('ai_condition_training', '/ai-condition/training', 'training');

        // AJAX endpoints
        $router->any('ai_condition_api_test', '/ai-condition/api/test', 'apiTest');
        $router->any('ai_condition_api_submit', '/ai-condition/api/submit', 'apiSubmit');
        $router->any('ai_condition_api_confirm', '/ai-condition/api/confirm', 'apiConfirm');
        $router->any('ai_condition_api_history_data', '/ai-condition/api/history-data', 'apiHistoryData');
        $router->any('ai_condition_api_bulk_status', '/ai-condition/api/bulk-status', 'apiBulkStatus');
        $router->any('ai_condition_api_client_save', '/ai-condition/api/client-save', 'apiClientSave');
        $router->any('ai_condition_api_client_revoke', '/ai-condition/api/client-revoke', 'apiClientRevoke');
        $router->any('ai_condition_api_object_search', '/ai-condition/api/object-search', 'apiObjectSearch');

        // Manual assessment save
        $router->any('ai_condition_api_manual_save', '/ai-condition/api/manual-save', 'apiManualSave');

        // Training proxy endpoints
        $router->any('ai_condition_api_training_model_info', '/ai-condition/api/training/model-info', 'apiTrainingModelInfo');
        $router->any('ai_condition_api_training_status', '/ai-condition/api/training/status', 'apiTrainingStatus');
        $router->any('ai_condition_api_training_upload', '/ai-condition/api/training/upload', 'apiTrainingUpload');
        $router->any('ai_condition_api_training_datasets', '/ai-condition/api/training/datasets', 'apiTrainingDatasets');
        $router->any('ai_condition_api_training_start', '/ai-condition/api/training/start', 'apiTrainingStart');

        // Training contribution endpoints
        $router->any('ai_condition_api_contribute', '/ai-condition/api/contribute', 'apiContribute');
        $router->any('ai_condition_api_contributions', '/ai-condition/api/contributions', 'apiContributions');

        // Client training permission toggle
        $router->any('ai_condition_api_client_training_toggle', '/ai-condition/api/client-training-toggle', 'apiClientTrainingToggle');

        // Client training approval workflow
        $router->any('ai_condition_api_client_approve_training', '/ai-condition/api/client-approve-training', 'apiClientApproveTraining');
        $router->any('ai_condition_api_client_upload_consent', '/ai-condition/api/client-upload-consent', 'apiClientUploadConsent');
        $router->any('ai_condition_api_client_contributions', '/ai-condition/api/client-contributions', 'apiClientContributions');
        $router->any('ai_condition_api_contribution_review', '/ai-condition/api/contribution-review', 'apiContributionReview');
        $router->any('ai_condition_api_push_training_data', '/ai-condition/api/push-training-data', 'apiPushTrainingData');

        $router->register($event->getSubject());
    }

    public function filterContent(sfEvent $event, $content)
    {
        $moduleName = sfContext::getInstance()->getModuleName();
        if ($moduleName !== 'aiCondition') {
            return $content;
        }

        $pluginWebPath = '/plugins/ahgAiConditionPlugin/web';
        $css = '<link rel="stylesheet" href="' . $pluginWebPath . '/css/ai-condition.css">';
        $content = str_replace('</head>', $css . "\n</head>", $content);

        return $content;
    }
}
