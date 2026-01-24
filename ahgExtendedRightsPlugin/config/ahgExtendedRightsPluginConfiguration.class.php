<?php

class ahgExtendedRightsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Extended Rights: RightsStatements.org, Creative Commons, Embargo, TK Labels';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgExtendedRightsPlugin/web/css/extended-rights.css', 'last');
    }

    public function initialize()
    {
        // Autoload EmbargoHelper
        require_once dirname(__FILE__) . '/../lib/EmbargoHelper.php';
        
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'extendedRights';
        $enabledModules[] = 'embargo';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Dashboard/Index
        $routing->prependRoute('extendedRights_dashboard', new sfRoute(
            '/extendedRights/dashboard',
            ['module' => 'extendedRights', 'action' => 'index']
        ));
        
        $routing->prependRoute('extendedRights_index', new sfRoute(
            '/extendedRights',
            ['module' => 'extendedRights', 'action' => 'index']
        ));

        // Edit
        $routing->prependRoute('extendedRights_edit', new sfRoute(
            '/extendedRights/edit/:slug',
            ['module' => 'extendedRights', 'action' => 'edit']
        ));

        // Batch
        $routing->prependRoute('extendedRights_batch', new sfRoute(
            '/extendedRights/batch',
            ['module' => 'extendedRights', 'action' => 'batch']
        ));

        // Browse
        $routing->prependRoute('extendedRights_browse', new sfRoute(
            '/extendedRights/browse',
            ['module' => 'extendedRights', 'action' => 'browse']
        ));

        // Embargoes
        $routing->prependRoute('extendedRights_embargoes', new sfRoute(
            '/extendedRights/embargoes',
            ['module' => 'extendedRights', 'action' => 'embargoes']
        ));

        $routing->prependRoute('extendedRights_liftEmbargo', new sfRoute(
            '/extendedRights/liftEmbargo/:id',
            ['module' => 'extendedRights', 'action' => 'liftEmbargo'],
            ['id' => '\d+']
        ));

        // Admin routes
        $routing->prependRoute('extendedRights_admin', new sfRoute(
            '/admin/rights',
            ['module' => 'extendedRights', 'action' => 'index']
        ));

        $routing->prependRoute('extendedRights_admin_batch', new sfRoute(
            '/admin/rights/batch',
            ['module' => 'extendedRights', 'action' => 'batch']
        ));

        // Embargo module routes
        $routing->prependRoute('ahg_rights_embargo_index', new sfRoute(
            '/ahg/rights/embargo',
            ['module' => 'embargo', 'action' => 'index']
        ));

        $routing->prependRoute('ahg_rights_embargo_add', new sfRoute(
            '/ahg/rights/embargo/add',
            ['module' => 'embargo', 'action' => 'add']
        ));

        $routing->prependRoute('ahg_rights_embargo_edit', new sfRoute(
            '/ahg/rights/embargo/edit',
            ['module' => 'embargo', 'action' => 'edit']
        ));

        $routing->prependRoute('ahg_rights_embargo_view', new sfRoute(
            '/ahg/rights/embargo/view/:id',
            ['module' => 'embargo', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_rights_embargo_lift', new sfRoute(
            '/ahg/rights/embargo/lift/:id',
            ['module' => 'embargo', 'action' => 'lift'],
            ['id' => '\d+']
        ));
    }
}
