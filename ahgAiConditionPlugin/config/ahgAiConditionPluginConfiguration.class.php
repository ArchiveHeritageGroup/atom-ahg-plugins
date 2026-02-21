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

        // AJAX endpoints
        $router->any('ai_condition_api_test', '/ai-condition/api/test', 'apiTest');
        $router->any('ai_condition_api_submit', '/ai-condition/api/submit', 'apiSubmit');
        $router->any('ai_condition_api_confirm', '/ai-condition/api/confirm', 'apiConfirm');
        $router->any('ai_condition_api_history_data', '/ai-condition/api/history-data', 'apiHistoryData');
        $router->any('ai_condition_api_bulk_status', '/ai-condition/api/bulk-status', 'apiBulkStatus');
        $router->any('ai_condition_api_client_save', '/ai-condition/api/client-save', 'apiClientSave');
        $router->any('ai_condition_api_client_revoke', '/ai-condition/api/client-revoke', 'apiClientRevoke');

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
