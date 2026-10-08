<?php

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

/**
 * OpenID Connect sign-in (#200), Google first; any standard issuer works.
 *
 * Authorisation code flow with PKCE, state and nonce. The ID token is verified
 * locally against the issuer's published keys (JWKS) with firebase/php-jwt,
 * which the framework already ships, and its issuer, audience, expiry, nonce
 * and verified e-mail are checked. Nothing in the token is trusted before that.
 */
class OidcClient
{
    private const SESSION = 'ahg_sso_oidc';
    private const CACHE_TTL = 3600;

    /** Where to send the browser to start sign-in. */
    public static function authorizeUrl(sfUser $user, string $redirectUri, string $next): string
    {
        $config = self::discovery();
        $verifier = self::b64url(random_bytes(48));
        $flow = [
            'state' => bin2hex(random_bytes(16)),
            'nonce' => bin2hex(random_bytes(16)),
            'verifier' => $verifier,
            'next' => $next,
            'started' => time(),
        ];
        $user->setAttribute(self::SESSION, $flow);

        $params = [
            'response_type' => 'code',
            'client_id' => SsoSettings::get('sso_oidc_client_id'),
            'redirect_uri' => $redirectUri,
            'scope' => 'openid email profile',
            'state' => $flow['state'],
            'nonce' => $flow['nonce'],
            'code_challenge' => self::b64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ];
        $domains = self::allowedDomains();
        if (1 === count($domains)) {
            $params['hd'] = $domains[0]; // Google: preselect the institution's domain
        }

        return $config['authorization_endpoint'].'?'.http_build_query($params);
    }

    /**
     * Finish sign-in on the callback.
     *
     * @return array{email: string, name: string, groups: string[], next: string}
     */
    public static function finish(sfUser $user, sfWebRequest $request, string $redirectUri): array
    {
        $flow = $user->getAttribute(self::SESSION);
        $user->getAttributeHolder()->remove(self::SESSION);

        if ($request->getParameter('error')) {
            throw new RuntimeException('Sign-in was cancelled or refused by the provider.');
        }
        if (!is_array($flow) || time() - (int) $flow['started'] > 600
            || !hash_equals((string) $flow['state'], (string) $request->getParameter('state'))) {
            throw new RuntimeException('This sign-in link has expired. Please start again.');
        }

        $config = self::discovery();
        $tokens = self::post($config['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'code' => (string) $request->getParameter('code'),
            'redirect_uri' => $redirectUri,
            'client_id' => SsoSettings::get('sso_oidc_client_id'),
            'client_secret' => SsoSettings::get('sso_oidc_client_secret'),
            'code_verifier' => $flow['verifier'],
        ]);
        if (empty($tokens['id_token'])) {
            throw new RuntimeException('The provider did not return an identity token.');
        }

        $claims = self::verifyIdToken($tokens['id_token'], self::jwks($config['jwks_uri']), (string) $flow['nonce']);
        $groupsClaim = SsoSettings::get('sso_oidc_groups_claim');

        return [
            'email' => $claims['email'],
            'name' => trim((string) ($claims['name'] ?? '')),
            'groups' => array_map('strval', (array) ($claims[$groupsClaim] ?? [])),
            'next' => (string) $flow['next'],
        ];
    }

    /**
     * Verify an ID token's signature against the key set, then its issuer,
     * audience, nonce, verified e-mail and allowed domain. Returns the claims,
     * with 'email' lower-cased.
     */
    public static function verifyIdToken(string $idToken, array $jwks, string $nonce): array
    {
        JWT::$leeway = 60;
        $claims = (array) JWT::decode($idToken, JWK::parseKeySet($jwks));

        $issuer = rtrim(SsoSettings::get('sso_oidc_issuer'), '/');
        $issuers = [$issuer, preg_replace('#^https://#', '', $issuer)];
        $audience = (array) ($claims['aud'] ?? []);
        if (!in_array($claims['iss'] ?? '', $issuers, true)
            || !in_array(SsoSettings::get('sso_oidc_client_id'), $audience, true)
            || !hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
            throw new RuntimeException('The identity token did not check out.');
        }
        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        if ('' === $email || true !== ($claims['email_verified'] ?? false)) {
            throw new RuntimeException('The provider did not confirm an e-mail address for this account.');
        }
        $domains = self::allowedDomains();
        if ($domains && !in_array(substr(strrchr($email, '@'), 1), $domains, true)) {
            throw new RuntimeException('Accounts from this e-mail domain may not sign in here.');
        }
        $claims['email'] = $email;

        return $claims;
    }

    /** @return string[] lower-case domains, empty for any */
    public static function allowedDomains(): array
    {
        return array_values(array_filter(array_map(
            static fn ($d) => strtolower(trim($d, " \t@")),
            preg_split('/[\s,]+/', SsoSettings::get('sso_oidc_allowed_domains'))
        )));
    }

    private static function discovery(): array
    {
        $issuer = rtrim(SsoSettings::get('sso_oidc_issuer'), '/');
        $config = self::cachedJson($issuer.'/.well-known/openid-configuration');
        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException('The identity provider could not be reached. Try again later.');
            }
        }

        return $config;
    }

    private static function jwks(string $uri): array
    {
        $keys = self::cachedJson($uri);
        if (empty($keys['keys'])) {
            throw new RuntimeException('The identity provider keys could not be loaded.');
        }

        return $keys;
    }

    /** GET a JSON document, cached for an hour in the system temp directory. */
    private static function cachedJson(string $url): array
    {
        if (0 !== strpos($url, 'https://')) {
            throw new RuntimeException('The identity provider must use https.');
        }
        $file = sys_get_temp_dir().'/ahg-sso-'.sha1($url).'.json';
        if (is_file($file) && time() - filemtime($file) < self::CACHE_TTL) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                return $data;
            }
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $data = 200 === $status ? json_decode((string) $body, true) : null;
        if (!is_array($data)) {
            return [];
        }
        @file_put_contents($file, $body);

        return $data;
    }

    private static function post(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $data = json_decode((string) $body, true);
        if (200 !== $status || !is_array($data)) {
            error_log('sso.oidc_token_failed: HTTP '.$status);

            throw new RuntimeException('The provider would not complete the sign-in.');
        }

        return $data;
    }

    private static function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
