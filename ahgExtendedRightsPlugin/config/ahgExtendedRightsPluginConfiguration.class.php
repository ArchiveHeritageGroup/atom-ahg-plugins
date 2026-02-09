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
        // Autoload core classes
        require_once dirname(__FILE__) . '/../lib/EmbargoHelper.php';
        require_once dirname(__FILE__) . '/../lib/DigitalObjectEmbargoFilter.php';
        require_once dirname(__FILE__) . '/../lib/Services/EmbargoService.php';
        require_once dirname(__FILE__) . '/../lib/Services/EmbargoNotificationService.php';

        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'extendedRights';
        $enabledModules[] = 'embargo';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function loadRoutes(sfEvent $event)
    {
        // extendedRights module routes
        $rightsRouter = new \AtomFramework\Routing\RouteLoader('extendedRights');

        // Dashboard/Index
        $rightsRouter->any('extendedRights_dashboard', '/extendedRights/dashboard', 'index');
        $rightsRouter->any('extendedRights_index', '/extendedRights', 'index');

        // Edit
        $rightsRouter->any('extendedRights_edit', '/extendedRights/edit/:slug', 'edit');

        // Batch
        $rightsRouter->any('extendedRights_batch', '/extendedRights/batch', 'batch');

        // Browse
        $rightsRouter->any('extendedRights_browse', '/extendedRights/browse', 'browse');

        // Embargoes
        $rightsRouter->any('extendedRights_embargoes', '/extendedRights/embargoes', 'embargoes');
        $rightsRouter->any('extendedRights_liftEmbargo', '/extendedRights/liftEmbargo/:id', 'liftEmbargo', ['id' => '\d+']);

        // Admin routes
        $rightsRouter->any('extendedRights_admin', '/admin/rights', 'index');
        $rightsRouter->any('extendedRights_admin_batch', '/admin/rights/batch', 'batch');

        $rightsRouter->register($event->getSubject());

        // Embargo module routes
        $embargoRouter = new \AtomFramework\Routing\RouteLoader('embargo');

        $embargoRouter->any('ahg_rights_embargo_index', '/ahg/rights/embargo', 'index');
        $embargoRouter->any('ahg_rights_embargo_add', '/ahg/rights/embargo/add', 'add');
        $embargoRouter->any('ahg_rights_embargo_edit', '/ahg/rights/embargo/edit', 'edit');
        $embargoRouter->any('ahg_rights_embargo_view', '/ahg/rights/embargo/view/:id', 'view', ['id' => '\d+']);
        $embargoRouter->any('ahg_rights_embargo_lift', '/ahg/rights/embargo/lift/:id', 'lift', ['id' => '\d+']);

        $embargoRouter->register($event->getSubject());
    }
}
