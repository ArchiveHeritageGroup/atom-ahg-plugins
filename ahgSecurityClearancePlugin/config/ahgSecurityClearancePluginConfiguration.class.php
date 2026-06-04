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

        // Two-Factor Authentication (TOTP)
        $router->any('security_2fa', '/security/2fa', 'twoFactor');
        $router->post('security_2fa_verify', '/security/2fa/verify', 'verifyTwoFactor');
        $router->any('security_2fa_setup', '/security/2fa/setup', 'setupTwoFactor');
        $router->post('security_2fa_confirm', '/security/2fa/confirm', 'confirmTwoFactor');
        $router->post('security_2fa_email', '/security/2fa/send-email', 'sendEmailCode');
        $router->any('security_2fa_remove', '/security/2fa/remove/:id', 'removeTwoFactor', ['id' => '\d+']);

        // WebAuthn / FIDO2 passkey MFA (#126 / #721)
        $router->any('security_webauthn_manage', '/security/2fa/webauthn', 'webauthnManage');
        $router->post('security_webauthn_register_begin', '/security/2fa/webauthn/register/begin', 'webauthnRegisterBegin');
        $router->post('security_webauthn_register_complete', '/security/2fa/webauthn/register/complete', 'webauthnRegisterComplete');
        $router->post('security_webauthn_assert_begin', '/security/2fa/webauthn/assert/begin', 'webauthnAssertBegin');
        $router->post('security_webauthn_assert_complete', '/security/2fa/webauthn/assert/complete', 'webauthnAssertComplete');
        $router->any('security_webauthn_delete', '/security/2fa/webauthn/delete/:id', 'webauthnDelete', ['id' => '\d+']);

        $router->register($event->getSubject());
    }
}
