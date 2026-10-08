<?php

/**
 * SAML 2.0 sign-in (#200): Shibboleth, SAFIRE and other institutional
 * identity providers.
 *
 * Signature checking, audience, destination, time conditions and replay
 * (InResponseTo) are left to onelogin/php-saml, a maintained library; this
 * class only builds its settings and reads the attributes. Without the library
 * installed, SsoSettings::samlReady() is false and SAML stays switched off.
 */
class SamlClient
{
    private const SESSION = 'ahg_sso_saml';

    public static function auth(string $baseUrl): \OneLogin\Saml2\Auth
    {
        return new \OneLogin\Saml2\Auth(self::settings($baseUrl));
    }

    public static function settings(string $baseUrl): array
    {
        return [
            'strict' => true,
            'sp' => [
                'entityId' => $baseUrl.'/sso/saml/metadata',
                'assertionConsumerService' => [
                    'url' => $baseUrl.'/sso/saml/acs',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
            ],
            'idp' => [
                'entityId' => SsoSettings::get('sso_saml_idp_entity_id'),
                'singleSignOnService' => [
                    'url' => SsoSettings::get('sso_saml_idp_sso_url'),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => SsoSettings::get('sso_saml_idp_x509cert'),
            ],
            'security' => [
                'wantAssertionsSigned' => true,
                'wantMessagesSigned' => false,
                'requestedAuthnContext' => false,
            ],
        ];
    }

    public static function loginUrl(sfUser $user, string $baseUrl, string $next): string
    {
        // login(..., stay=true) returns the URL instead of redirecting; the same
        // object then knows the request id, which the response must answer.
        $auth = self::auth($baseUrl);
        $url = $auth->login(null, [], false, false, true);
        $user->setAttribute(self::SESSION, ['request_id' => $auth->getLastRequestID(), 'next' => $next, 'started' => time()]);

        return $url;
    }

    /**
     * Validate the posted response.
     *
     * @return array{email: string, name: string, groups: string[], next: string}
     */
    public static function finish(sfUser $user, string $baseUrl): array
    {
        $flow = $user->getAttribute(self::SESSION);
        $user->getAttributeHolder()->remove(self::SESSION);
        $requestId = is_array($flow) ? ($flow['request_id'] ?? null) : null;

        $auth = self::auth($baseUrl);
        $auth->processResponse($requestId);
        if ($auth->getErrors() || !$auth->isAuthenticated()) {
            error_log('sso.saml_failed: '.implode(', ', $auth->getErrors()).' '.$auth->getLastErrorReason());

            throw new RuntimeException('The identity provider response could not be verified.');
        }

        $attrs = $auth->getAttributes();
        $first = static fn ($name) => trim((string) (($attrs[$name] ?? [])[0] ?? ''));
        $email = strtolower($first(SsoSettings::get('sso_saml_email_attribute')));
        if ('' === $email && filter_var($auth->getNameId(), FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($auth->getNameId());
        }
        if ('' === $email) {
            throw new RuntimeException('The identity provider did not send an e-mail address.');
        }

        return [
            'email' => $email,
            'name' => $first(SsoSettings::get('sso_saml_name_attribute')),
            'groups' => array_map('strval', $attrs[SsoSettings::get('sso_saml_groups_attribute')] ?? []),
            'next' => is_array($flow) ? (string) ($flow['next'] ?? '') : '',
        ];
    }

    public static function metadata(string $baseUrl): string
    {
        $settings = self::auth($baseUrl)->getSettings();
        $xml = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($xml);
        if ($errors) {
            throw new RuntimeException('Invalid service provider metadata: '.implode(', ', $errors));
        }

        return $xml;
    }
}
