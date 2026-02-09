<?php
class ahgSecurityClearancePluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        // Register plugin as enabled
        sfConfig::set('app_plugins_ahgSecurityClearancePlugin', true);

        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'securityClearance';
        $enabledModules[] = 'securityAudit';
        $enabledModules[] = 'accessFilter';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }
    
    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('securityClearance');

        // Admin clearance management routes
        $router->any('security_compliance', '/admin/security/compliance', 'securityCompliance');
        $router->any('security_clearances', '/security/clearances', 'index');
        $router->any('security_clearance_view', '/security/clearance/:id', 'view', ['id' => '\d+']);
        $router->any('security_clearance_grant', '/security/clearance/grant', 'grant');
        $router->any('security_clearance_revoke', '/security/clearance/:id/revoke', 'revoke', ['id' => '\d+']);
        $router->any('security_clearance_bulk_grant', '/security/clearance/bulk-grant', 'bulkGrant');
        $router->any('security_access_revoke', '/security/access/:id/revoke', 'revokeAccess', ['id' => '\d+']);

        // User clearance management (slug-based)
        $router->any('security_clearance_user', '/security/clearance/user/:slug', 'user', ['slug' => '[a-zA-Z0-9_-]+']);

        $router->register($event->getSubject());
    }
}
