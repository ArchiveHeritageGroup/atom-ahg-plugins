<?php

/**
 * ahgNMMZPlugin - National Museums and Monuments of Zimbabwe Act [Chapter 25:11]
 *
 * Implements compliance features for Zimbabwe's heritage protection legislation:
 * - National Monuments registration and protection
 * - Antiquities management (objects > 100 years old)
 * - Export permit tracking
 * - Archaeological site protection
 * - Heritage impact assessments
 *
 * @package    ahgNMMZPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2025 The Archive and Heritage Group (Pty) Ltd
 */
class ahgNMMZPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'National Museums and Monuments of Zimbabwe Act [Chapter 25:11] compliance';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event): void
    {
        $context = $event->getSubject();
        $response = $context->getResponse();

        if ('nmmz' === $context->getModuleName()) {
            $response->addStylesheet('/plugins/ahgNMMZPlugin/css/nmmz.css', 'last');
            $response->addJavascript('/plugins/ahgNMMZPlugin/js/nmmz.js', 'last');
        }
    }

    public function initialize(): void
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'nmmz';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function routingLoadConfiguration(sfEvent $event): void
    {
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('nmmz_index', new sfRoute(
            '/admin/nmmz',
            ['module' => 'nmmz', 'action' => 'index']
        ));

        // National Monuments
        $routing->prependRoute('nmmz_monuments', new sfRoute(
            '/admin/nmmz/monuments',
            ['module' => 'nmmz', 'action' => 'monuments']
        ));
        $routing->prependRoute('nmmz_monument_create', new sfRoute(
            '/admin/nmmz/monument/create',
            ['module' => 'nmmz', 'action' => 'monumentCreate']
        ));
        $routing->prependRoute('nmmz_monument_view', new sfRoute(
            '/admin/nmmz/monument/:id',
            ['module' => 'nmmz', 'action' => 'monumentView'],
            ['id' => '\d+']
        ));

        // Antiquities
        $routing->prependRoute('nmmz_antiquities', new sfRoute(
            '/admin/nmmz/antiquities',
            ['module' => 'nmmz', 'action' => 'antiquities']
        ));
        $routing->prependRoute('nmmz_antiquity_create', new sfRoute(
            '/admin/nmmz/antiquity/create',
            ['module' => 'nmmz', 'action' => 'antiquityCreate']
        ));
        $routing->prependRoute('nmmz_antiquity_view', new sfRoute(
            '/admin/nmmz/antiquity/:id',
            ['module' => 'nmmz', 'action' => 'antiquityView'],
            ['id' => '\d+']
        ));

        // Export Permits
        $routing->prependRoute('nmmz_permits', new sfRoute(
            '/admin/nmmz/permits',
            ['module' => 'nmmz', 'action' => 'permits']
        ));
        $routing->prependRoute('nmmz_permit_create', new sfRoute(
            '/admin/nmmz/permit/create',
            ['module' => 'nmmz', 'action' => 'permitCreate']
        ));
        $routing->prependRoute('nmmz_permit_view', new sfRoute(
            '/admin/nmmz/permit/:id',
            ['module' => 'nmmz', 'action' => 'permitView'],
            ['id' => '\d+']
        ));

        // Archaeological Sites
        $routing->prependRoute('nmmz_sites', new sfRoute(
            '/admin/nmmz/sites',
            ['module' => 'nmmz', 'action' => 'sites']
        ));
        $routing->prependRoute('nmmz_site_create', new sfRoute(
            '/admin/nmmz/site/create',
            ['module' => 'nmmz', 'action' => 'siteCreate']
        ));
        $routing->prependRoute('nmmz_site_view', new sfRoute(
            '/admin/nmmz/site/:id',
            ['module' => 'nmmz', 'action' => 'siteView'],
            ['id' => '\d+']
        ));

        // Heritage Impact Assessments
        $routing->prependRoute('nmmz_hia', new sfRoute(
            '/admin/nmmz/hia',
            ['module' => 'nmmz', 'action' => 'hia']
        ));
        $routing->prependRoute('nmmz_hia_create', new sfRoute(
            '/admin/nmmz/hia/create',
            ['module' => 'nmmz', 'action' => 'hiaCreate']
        ));

        // Reports
        $routing->prependRoute('nmmz_reports', new sfRoute(
            '/admin/nmmz/reports',
            ['module' => 'nmmz', 'action' => 'reports']
        ));

        // Config
        $routing->prependRoute('nmmz_config', new sfRoute(
            '/admin/nmmz/config',
            ['module' => 'nmmz', 'action' => 'config']
        ));
    }
}
