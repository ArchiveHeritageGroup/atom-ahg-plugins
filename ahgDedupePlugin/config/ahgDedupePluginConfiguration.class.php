<?php

/**
 * ahgDedupePlugin Configuration
 *
 * Duplicate detection for archival records during creation and import.
 */
class ahgDedupePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Duplicate Detection: Identify and manage duplicate records using multiple matching algorithms';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgDedupePlugin/web/css/dedupe.css', 'last');
        $context->response->addJavascript('/plugins/ahgDedupePlugin/web/js/dedupe.js', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Hook into record save to check for duplicates
        $this->dispatcher->connect('QubitInformationObject.preSave', [$this, 'onRecordPreSave']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'dedupe';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('dedupe');

        // Dashboard
        $router->any('ahg_dedupe_index', '/admin/dedupe', 'index');

        // Browse detected duplicates
        $router->any('ahg_dedupe_browse', '/admin/dedupe/browse', 'browse');

        // View duplicate pair
        $router->any('ahg_dedupe_view', '/admin/dedupe/view/:id', 'view', ['id' => '\d+']);

        // Compare records side-by-side
        $router->any('ahg_dedupe_compare', '/admin/dedupe/compare/:id', 'compare', ['id' => '\d+']);

        // Dismiss false positive
        $router->any('ahg_dedupe_dismiss', '/admin/dedupe/dismiss/:id', 'dismiss', ['id' => '\d+']);

        // Merge records
        $router->any('ahg_dedupe_merge', '/admin/dedupe/merge/:id', 'merge', ['id' => '\d+']);

        // Scan for duplicates
        $router->any('ahg_dedupe_scan', '/admin/dedupe/scan', 'scan');

        // Rules configuration
        $router->any('ahg_dedupe_rules', '/admin/dedupe/rules', 'rules');
        $router->any('ahg_dedupe_rule_create', '/admin/dedupe/rule/create', 'ruleCreate');
        $router->any('ahg_dedupe_rule_edit', '/admin/dedupe/rule/:id/edit', 'ruleEdit', ['id' => '\d+']);
        $router->any('ahg_dedupe_rule_delete', '/admin/dedupe/rule/:id/delete', 'ruleDelete', ['id' => '\d+']);

        // Reports
        $router->any('ahg_dedupe_report', '/admin/dedupe/report', 'report');

        // API routes
        $router->any('ahg_dedupe_api_check', '/api/dedupe/check', 'apiCheck');
        $router->any('ahg_dedupe_api_realtime', '/api/dedupe/realtime', 'apiRealtime');

        $router->register($event->getSubject());
    }

    /**
     * Hook: Check for duplicates before saving a record.
     */
    public function onRecordPreSave(sfEvent $event)
    {
        // This hook is optional - actual enforcement happens in the controller
        // Here we just flag potential duplicates for review
    }
}
