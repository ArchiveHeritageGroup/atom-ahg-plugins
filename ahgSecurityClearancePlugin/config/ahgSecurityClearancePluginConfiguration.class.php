<?php
class ahgSecurityClearancePluginConfiguration extends sfPluginConfiguration
{
    /** Per-request guard: the MFA gate evaluates only the first dispatched action. */
    private $mfaGateChecked = false;

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

        // #738 — session-wide per-role MFA enforcement. Fires after routing,
        // before the action runs; redirects MFA-required roles to /security/2fa
        // until they hold a valid 2FA session.
        $this->dispatcher->connect('controller.change_action', [$this, 'enforcePerRoleMfa']);
    }

    /**
     * Gate the request on MFA when the signed-in user belongs to a role the
     * admin has marked MFA-required (#738). Fail-open on any error so a policy
     * misconfiguration can never take the whole site down.
     */
    public function enforcePerRoleMfa(sfEvent $event)
    {
        if ($this->mfaGateChecked) {
            return; // controller.change_action also fires on internal forwards
        }
        $this->mfaGateChecked = true;

        $params = $event->getParameters();
        $module = $params['module'] ?? '';
        $action = $params['action'] ?? '';

        // Never gate the 2FA/clearance pages themselves (loop), the login/logout
        // flow, or the error module.
        if (in_array($module, ['securityClearance', 'securityAudit', 'accessFilter', 'default'], true)) {
            return;
        }
        if ('user' === $module && in_array($action, ['login', 'logout'], true)) {
            return;
        }

        try {
            $context = sfContext::getInstance();
            $user = $context->getUser();
            if (!$user->isAuthenticated()) {
                return;
            }

            // Skip API / AJAX — a 302 would break them; the next full page load
            // catches the user.
            $request = $context->getRequest();
            $fmt = $request->getRequestFormat();
            if ($request->isXmlHttpRequest() || (null !== $fmt && 'html' !== $fmt)) {
                return;
            }

            $userId = (int) $user->getAttribute('user_id');
            if ($userId <= 0) {
                return;
            }

            require_once __DIR__ . '/../lib/Services/SecurityClearanceService.php';
            if (!SecurityClearanceService::roleRequiresMfa($userId)) {
                return;
            }
            if (SecurityClearanceService::has2FASession($userId, session_id())) {
                return;
            }

            $context->getController()->redirect('/security/2fa?return=' . urlencode($request->getUri()));

            throw new sfStopException();
        } catch (sfStopException $e) {
            throw $e; // intended redirect control-flow, not an error
        } catch (\Throwable $e) {
            error_log('mfa.gate.error: ' . $e->getMessage());
        }
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

        // Per-role MFA policy admin (#738). any() matches GET for the page;
        // POST needs its own post() route (any() 404s on POST here).
        $router->any('security_mfa_policy', '/security/2fa/policy', 'mfaPolicy');
        $router->post('security_mfa_policy_save', '/security/2fa/policy/save', 'mfaPolicy');

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
