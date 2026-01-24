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
        $routing = $event->getSubject();
        
        // Admin clearance management routes
        $routing->prependRoute('security_compliance', new sfRoute(
            '/admin/security/compliance',
            ['module' => 'securityClearance', 'action' => 'securityCompliance']
        ));
        $routing->prependRoute('security_clearances', new sfRoute(
            '/security/clearances',
            ['module' => 'securityClearance', 'action' => 'index']
        ));
        $routing->prependRoute('security_clearance_view', new sfRoute(
            '/security/clearance/:id',
            ['module' => 'securityClearance', 'action' => 'view'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('security_clearance_grant', new sfRoute(
            '/security/clearance/grant',
            ['module' => 'securityClearance', 'action' => 'grant']
        ));
        $routing->prependRoute('security_clearance_revoke', new sfRoute(
            '/security/clearance/:id/revoke',
            ['module' => 'securityClearance', 'action' => 'revoke'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('security_clearance_bulk_grant', new sfRoute(
            '/security/clearance/bulk-grant',
            ['module' => 'securityClearance', 'action' => 'bulkGrant']
        ));
        $routing->prependRoute('security_access_revoke', new sfRoute(
            '/security/access/:id/revoke',
            ['module' => 'securityClearance', 'action' => 'revokeAccess'],
            ['id' => '\d+']
        ));

        // User clearance management (slug-based)
        $routing->prependRoute('security_clearance_user', new sfRoute(
            '/security/clearance/user/:slug',
            ['module' => 'securityClearance', 'action' => 'user'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
    }
}
