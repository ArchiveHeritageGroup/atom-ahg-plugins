<?php

/**
 * ahgNAZPlugin - National Archives of Zimbabwe Act [Chapter 25:06]
 *
 * Implements compliance features for Zimbabwe's archival legislation:
 * - 25-year closure period for restricted records
 * - Research permit tracking (local/foreign)
 * - Records schedule classification
 * - Records transfer to NAZ workflow
 * - Protected records management
 *
 * @package    ahgNAZPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2025 The Archive and Heritage Group (Pty) Ltd
 */
class ahgNAZPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'National Archives of Zimbabwe Act [Chapter 25:06] compliance plugin';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event): void
    {
        // Load CSS and JS assets
        $context = $event->getSubject();
        $response = $context->getResponse();

        // Only load on NAZ module pages
        if ('naz' === $context->getModuleName()) {
            $response->addStylesheet('/plugins/ahgNAZPlugin/css/naz.css', 'last');
            $response->addJavascript('/plugins/ahgNAZPlugin/js/naz.js', 'last');
        }
    }

    public function initialize(): void
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable NAZ module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'naz';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function routingLoadConfiguration(sfEvent $event): void
    {
        $routing = $event->getSubject();

        // NAZ Dashboard
        $routing->prependRoute('naz_index', new sfRoute(
            '/admin/naz',
            ['module' => 'naz', 'action' => 'index']
        ));

        // Closure periods
        $routing->prependRoute('naz_closures', new sfRoute(
            '/admin/naz/closures',
            ['module' => 'naz', 'action' => 'closures']
        ));
        $routing->prependRoute('naz_closure_create', new sfRoute(
            '/admin/naz/closure/create',
            ['module' => 'naz', 'action' => 'closureCreate']
        ));
        $routing->prependRoute('naz_closure_edit', new sfRoute(
            '/admin/naz/closure/:id/edit',
            ['module' => 'naz', 'action' => 'closureEdit'],
            ['id' => '\d+']
        ));

        // Research permits
        $routing->prependRoute('naz_permits', new sfRoute(
            '/admin/naz/permits',
            ['module' => 'naz', 'action' => 'permits']
        ));
        $routing->prependRoute('naz_permit_create', new sfRoute(
            '/admin/naz/permit/create',
            ['module' => 'naz', 'action' => 'permitCreate']
        ));
        $routing->prependRoute('naz_permit_view', new sfRoute(
            '/admin/naz/permit/:id',
            ['module' => 'naz', 'action' => 'permitView'],
            ['id' => '\d+']
        ));

        // Researchers
        $routing->prependRoute('naz_researchers', new sfRoute(
            '/admin/naz/researchers',
            ['module' => 'naz', 'action' => 'researchers']
        ));
        $routing->prependRoute('naz_researcher_create', new sfRoute(
            '/admin/naz/researcher/create',
            ['module' => 'naz', 'action' => 'researcherCreate']
        ));
        $routing->prependRoute('naz_researcher_view', new sfRoute(
            '/admin/naz/researcher/:id',
            ['module' => 'naz', 'action' => 'researcherView'],
            ['id' => '\d+']
        ));

        // Records schedules
        $routing->prependRoute('naz_schedules', new sfRoute(
            '/admin/naz/schedules',
            ['module' => 'naz', 'action' => 'schedules']
        ));
        $routing->prependRoute('naz_schedule_create', new sfRoute(
            '/admin/naz/schedule/create',
            ['module' => 'naz', 'action' => 'scheduleCreate']
        ));
        $routing->prependRoute('naz_schedule_view', new sfRoute(
            '/admin/naz/schedule/:id',
            ['module' => 'naz', 'action' => 'scheduleView'],
            ['id' => '\d+']
        ));

        // Transfers
        $routing->prependRoute('naz_transfers', new sfRoute(
            '/admin/naz/transfers',
            ['module' => 'naz', 'action' => 'transfers']
        ));
        $routing->prependRoute('naz_transfer_create', new sfRoute(
            '/admin/naz/transfer/create',
            ['module' => 'naz', 'action' => 'transferCreate']
        ));
        $routing->prependRoute('naz_transfer_view', new sfRoute(
            '/admin/naz/transfer/:id',
            ['module' => 'naz', 'action' => 'transferView'],
            ['id' => '\d+']
        ));

        // Protected records
        $routing->prependRoute('naz_protected', new sfRoute(
            '/admin/naz/protected',
            ['module' => 'naz', 'action' => 'protectedRecords']
        ));

        // Reports
        $routing->prependRoute('naz_reports', new sfRoute(
            '/admin/naz/reports',
            ['module' => 'naz', 'action' => 'reports']
        ));

        // Config
        $routing->prependRoute('naz_config', new sfRoute(
            '/admin/naz/config',
            ['module' => 'naz', 'action' => 'config']
        ));
    }
}
