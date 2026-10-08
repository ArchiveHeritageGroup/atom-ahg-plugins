<?php

$ssoLib = dirname(__DIR__, 3).'/lib/Sso/';
require_once $ssoLib.'SsoSettings.php';
require_once $ssoLib.'OidcClient.php';
require_once $ssoLib.'SamlClient.php';
require_once $ssoLib.'SsoUserProvisioner.php';

/**
 * Single sign-on (#200): OpenID Connect (Google first) and SAML 2.0.
 *
 * /sso/oidc/login -> provider -> /sso/oidc/callback
 * /sso/saml/login -> provider -> POST /sso/saml/acs ; /sso/saml/metadata for the provider
 * /admin/sso      settings (administrators)
 */
class ssoActions extends sfActions
{
    public function executeOidcLogin(sfWebRequest $request)
    {
        $this->forward404Unless(SsoSettings::oidcReady());

        return $this->redirect(OidcClient::authorizeUrl($this->getUser(), $this->oidcCallbackUrl($request), $this->safeNext($request)));
    }

    public function executeOidcCallback(sfWebRequest $request)
    {
        $this->forward404Unless(SsoSettings::oidcReady());

        try {
            $identity = OidcClient::finish($this->getUser(), $request, $this->oidcCallbackUrl($request));
        } catch (\Throwable $e) {
            return $this->failed($e);
        }

        return $this->signInAndGo($identity, 'oidc');
    }

    public function executeSamlLogin(sfWebRequest $request)
    {
        $this->forward404Unless(SsoSettings::samlReady());

        return $this->redirect(SamlClient::loginUrl($this->getUser(), $request->getUriPrefix(), $this->safeNext($request)));
    }

    public function executeSamlAcs(sfWebRequest $request)
    {
        $this->forward404Unless(SsoSettings::samlReady() && $request->isMethod('post'));

        try {
            $identity = SamlClient::finish($this->getUser(), $request->getUriPrefix());
        } catch (\Throwable $e) {
            return $this->failed($e);
        }

        return $this->signInAndGo($identity, 'saml');
    }

    public function executeSamlMetadata(sfWebRequest $request)
    {
        $this->forward404Unless(class_exists('\\OneLogin\\Saml2\\Auth') && SsoSettings::on('sso_saml_enabled'));
        $this->getResponse()->setContentType('application/samlmetadata+xml');

        return $this->renderText(SamlClient::metadata($request->getUriPrefix()));
    }

    public function executeAdmin(sfWebRequest $request)
    {
        if (!$this->getUser()->isAdministrator()) {
            QubitAcl::forwardUnauthorized();
        }

        if ($request->isMethod('post')) {
            SsoSettings::save((array) $request->getParameter('sso', []));
            $this->getUser()->setFlash('notice', $this->context->i18n->__('Single sign-on settings saved.'));
            $this->redirect('@sso_admin');
        }

        $this->settings = SsoSettings::all();
        $this->samlLibrary = class_exists('\\OneLogin\\Saml2\\Auth');
        $this->oidcCallback = $this->oidcCallbackUrl($request);
        $this->samlAcs = $request->getUriPrefix().'/sso/saml/acs';
        $this->samlMetadata = $request->getUriPrefix().'/sso/saml/metadata';
    }

    private function signInAndGo(array $identity, string $via)
    {
        try {
            $user = SsoUserProvisioner::resolve($identity);
        } catch (\Throwable $e) {
            return $this->failed($e);
        }

        // A fresh session id on sign-in, so a session cannot be planted beforehand.
        $this->getContext()->getStorage()->regenerate(true);
        $this->getUser()->signIn($user);
        error_log(sprintf('sso.signed_in: %s via %s', $user->username, $via));

        $next = $identity['next'] ?? '';

        return $this->redirect('' !== $next ? $next : '@homepage');
    }

    private function failed(\Throwable $e)
    {
        error_log('sso.failed: '.$e->getMessage());
        $message = $e instanceof RuntimeException ? $e->getMessage() : $this->context->i18n->__('Single sign-on failed. Please try again.');
        $this->getUser()->setFlash('error', $message);

        return $this->redirect(['module' => 'user', 'action' => 'login']);
    }

    private function oidcCallbackUrl(sfWebRequest $request): string
    {
        return $request->getUriPrefix().'/sso/oidc/callback';
    }

    /** Only a path on this site, never another host. */
    private function safeNext(sfWebRequest $request): string
    {
        $next = (string) $request->getParameter('next', '');

        return preg_match('#^/(?!/)[^\s\\\\]*$#', $next) ? $next : '';
    }
}
