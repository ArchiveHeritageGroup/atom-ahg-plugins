<?php

class ahgSecurityClearancePluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Admin clearance management routes
        $routing->prependRoute('security_clearances', new sfRoute(
            '/security/clearances',
            ['module' => 'arSecurityClearance', 'action' => 'index']
        ));

        $routing->prependRoute('security_clearance_view', new sfRoute(
            '/security/clearance/:id',
            ['module' => 'arSecurityClearance', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('security_clearance_grant', new sfRoute(
            '/security/clearance/grant',
            ['module' => 'arSecurityClearance', 'action' => 'grant']
        ));

        $routing->prependRoute('security_clearance_revoke', new sfRoute(
            '/security/clearance/:id/revoke',
            ['module' => 'arSecurityClearance', 'action' => 'revoke'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('security_clearance_bulk_grant', new sfRoute(
            '/security/clearance/bulk-grant',
            ['module' => 'arSecurityClearance', 'action' => 'bulkGrant']
        ));

        $routing->prependRoute('security_access_revoke', new sfRoute(
            '/security/access/:id/revoke',
            ['module' => 'arSecurityClearance', 'action' => 'revokeAccess'],
            ['id' => '\d+']
        ));
    }
}
