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
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('ahg_dedupe_index', new sfRoute(
            '/admin/dedupe',
            ['module' => 'dedupe', 'action' => 'index']
        ));

        // Browse detected duplicates
        $routing->prependRoute('ahg_dedupe_browse', new sfRoute(
            '/admin/dedupe/browse',
            ['module' => 'dedupe', 'action' => 'browse']
        ));

        // View duplicate pair
        $routing->prependRoute('ahg_dedupe_view', new sfRoute(
            '/admin/dedupe/view/:id',
            ['module' => 'dedupe', 'action' => 'view'],
            ['id' => '\d+']
        ));

        // Compare records side-by-side
        $routing->prependRoute('ahg_dedupe_compare', new sfRoute(
            '/admin/dedupe/compare/:id',
            ['module' => 'dedupe', 'action' => 'compare'],
            ['id' => '\d+']
        ));

        // Dismiss false positive
        $routing->prependRoute('ahg_dedupe_dismiss', new sfRoute(
            '/admin/dedupe/dismiss/:id',
            ['module' => 'dedupe', 'action' => 'dismiss'],
            ['id' => '\d+']
        ));

        // Merge records
        $routing->prependRoute('ahg_dedupe_merge', new sfRoute(
            '/admin/dedupe/merge/:id',
            ['module' => 'dedupe', 'action' => 'merge'],
            ['id' => '\d+']
        ));

        // Scan for duplicates
        $routing->prependRoute('ahg_dedupe_scan', new sfRoute(
            '/admin/dedupe/scan',
            ['module' => 'dedupe', 'action' => 'scan']
        ));

        // Rules configuration
        $routing->prependRoute('ahg_dedupe_rules', new sfRoute(
            '/admin/dedupe/rules',
            ['module' => 'dedupe', 'action' => 'rules']
        ));

        $routing->prependRoute('ahg_dedupe_rule_create', new sfRoute(
            '/admin/dedupe/rule/create',
            ['module' => 'dedupe', 'action' => 'ruleCreate']
        ));

        $routing->prependRoute('ahg_dedupe_rule_edit', new sfRoute(
            '/admin/dedupe/rule/:id/edit',
            ['module' => 'dedupe', 'action' => 'ruleEdit'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_dedupe_rule_delete', new sfRoute(
            '/admin/dedupe/rule/:id/delete',
            ['module' => 'dedupe', 'action' => 'ruleDelete'],
            ['id' => '\d+']
        ));

        // Reports
        $routing->prependRoute('ahg_dedupe_report', new sfRoute(
            '/admin/dedupe/report',
            ['module' => 'dedupe', 'action' => 'report']
        ));

        // API routes
        $routing->prependRoute('ahg_dedupe_api_check', new sfRoute(
            '/api/dedupe/check',
            ['module' => 'dedupe', 'action' => 'apiCheck']
        ));

        $routing->prependRoute('ahg_dedupe_api_realtime', new sfRoute(
            '/api/dedupe/realtime',
            ['module' => 'dedupe', 'action' => 'apiRealtime']
        ));
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
