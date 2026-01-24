<?php
class ahgSecurityClearancePluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        // Register plugin as enabled
        sfConfig::set('app_plugins_ahgSecurityClearancePlugin', true);

        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgSecurityClearance';
        $enabledModules[] = 'ahgSecurityAudit';
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
            ['module' => 'ahgSecurityClearance', 'action' => 'securityCompliance']
        ));
        $routing->prependRoute('security_clearances', new sfRoute(
            '/security/clearances',
            ['module' => 'ahgSecurityClearance', 'action' => 'index']
        ));
        $routing->prependRoute('security_clearance_view', new sfRoute(
            '/security/clearance/:id',
            ['module' => 'ahgSecurityClearance', 'action' => 'view'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('security_clearance_grant', new sfRoute(
            '/security/clearance/grant',
            ['module' => 'ahgSecurityClearance', 'action' => 'grant']
        ));
        $routing->prependRoute('security_clearance_revoke', new sfRoute(
            '/security/clearance/:id/revoke',
            ['module' => 'ahgSecurityClearance', 'action' => 'revoke'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('security_clearance_bulk_grant', new sfRoute(
            '/security/clearance/bulk-grant',
            ['module' => 'ahgSecurityClearance', 'action' => 'bulkGrant']
        ));
        $routing->prependRoute('security_access_revoke', new sfRoute(
            '/security/access/:id/revoke',
            ['module' => 'ahgSecurityClearance', 'action' => 'revokeAccess'],
            ['id' => '\d+']
        ));

        // User clearance management (slug-based)
        $routing->prependRoute('security_clearance_user', new sfRoute(
            '/security/clearance/user/:slug',
            ['module' => 'ahgSecurityClearance', 'action' => 'user'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
    }
}
