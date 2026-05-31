<?php

use AtomExtensions\Services\SettingService;
use AtomFramework\Http\Controllers\AhgController;

/**
 * Test LDAP Connection (#29).
 *
 * Connectivity/bind probe against the saved LDAP settings. Supplying a test
 * user also attempts an authenticated bind. Read-only — no settings or auth
 * state are changed. Result is surfaced via a flash message on the LDAP
 * settings page (CSP-safe; no inline JS).
 */
class AhgSettingsLdapTestAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $back = ['module' => 'ahgSettings', 'action' => 'ldap'];

        if (!extension_loaded('ldap')) {
            $this->getUser()->setFlash('error', 'LDAP test failed: the php-ldap extension is not installed on this server.');
            $this->redirect($back);
        }

        $host = $this->settingValue('ldapHost');
        $port = (int) ($this->settingValue('ldapPort') ?: 389);
        $baseDn = $this->settingValue('ldapBaseDn');
        $bindAttr = $this->settingValue('ldapBindAttribute') ?: 'uid';

        if ('' === (string) $host) {
            $this->getUser()->setFlash('error', 'LDAP test failed: set and save the Host first.');
            $this->redirect($back);
        }

        $testUser = trim((string) $request->getParameter('test_user'));
        $testPass = (string) $request->getParameter('test_pass');

        $conn = @ldap_connect($host, $port);
        if (false === $conn) {
            $this->getUser()->setFlash('error', sprintf('LDAP test failed: could not initialise a connection to %s:%d.', $host, $port));
            $this->redirect($back);
        }
        @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

        // Match ldapUser's TLS behaviour.
        if (\sfConfig::get('app_ldap_enable_tls_encryption', true)) {
            if (!@ldap_start_tls($conn)) {
                $this->getUser()->setFlash('error', sprintf('LDAP test failed: TLS (ldap_start_tls) could not be started on %s:%d. Check the server certificate or disable app_ldap_enable_tls_encryption.', $host, $port));
                $this->redirect($back);
            }
        }

        if ('' !== $testUser) {
            $dn = $bindAttr . '=' . $testUser . ',' . $baseDn;
            if (@ldap_bind($conn, $dn, $testPass)) {
                $this->getUser()->setFlash('notice', sprintf('LDAP test succeeded: bound as %s on %s:%d.', $dn, $host, $port));
            } else {
                $this->getUser()->setFlash('error', sprintf('LDAP test failed: bind as %s was rejected (%s).', $dn, ldap_error($conn)));
            }
        } else {
            // No credentials: connectivity probe via anonymous bind.
            if (@ldap_bind($conn)) {
                $this->getUser()->setFlash('notice', sprintf('LDAP connectivity OK: reached %s:%d (anonymous bind). Supply a test user to verify authentication.', $host, $port));
            } else {
                $this->getUser()->setFlash('notice', sprintf('LDAP reachable at %s:%d, but anonymous bind was refused (%s) — normal for many directories. Supply a test user to verify authentication.', $host, $port, ldap_error($conn)));
            }
        }

        @ldap_unbind($conn);
        $this->redirect($back);
    }

    private function settingValue(string $name): ?string
    {
        $setting = SettingService::getByName($name);

        return $setting ? (string) $setting->getValue(['sourceCulture' => true]) : null;
    }
}
