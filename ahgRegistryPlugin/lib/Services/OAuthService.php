<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * OAuth / Social Login Service
 *
 * Handles social login via Google, Facebook, GitHub, LinkedIn, Microsoft.
 * Stores linked accounts in registry_oauth_account.
 */
class OAuthService
{
    /**
     * Supported providers and their configuration keys.
     */
    private static $providers = [
        'google' => [
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'scope' => 'openid email profile',
        ],
        'facebook' => [
            'auth_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'userinfo_url' => 'https://graph.facebook.com/me?fields=id,name,email,picture',
            'scope' => 'email,public_profile',
        ],
        'github' => [
            'auth_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'userinfo_url' => 'https://api.github.com/user',
            'scope' => 'user:email',
        ],
        'linkedin' => [
            'auth_url' => 'https://www.linkedin.com/oauth/v2/authorization',
            'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'userinfo_url' => 'https://api.linkedin.com/v2/userinfo',
            'scope' => 'openid profile email',
        ],
        'microsoft' => [
            'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'userinfo_url' => 'https://graph.microsoft.com/v1.0/me',
            'scope' => 'openid email profile',
        ],
    ];

    /**
     * Get the authorization URL for a provider.
     *
     * @param string $provider
     * @param string $redirectUri
     * @return string|null
     */
    public static function getAuthUrl($provider, $redirectUri)
    {
        if (!isset(self::$providers[$provider])) {
            return null;
        }

        $config = self::getProviderConfig($provider);
        if (!$config) {
            return null;
        }

        $providerInfo = self::$providers[$provider];
        $state = bin2hex(random_bytes(16));

        // Store state in session for CSRF protection
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['oauth_state'] = $state;
            $_SESSION['oauth_provider'] = $provider;
        }

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $providerInfo['scope'],
            'state' => $state,
        ];

        // Provider-specific parameters
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        }

        return $providerInfo['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens and user info.
     *
     * @param string $provider
     * @param string $code
     * @param string $redirectUri
     * @return array|null ['provider_user_id', 'email', 'name', 'avatar_url', 'access_token', 'refresh_token']
     */
    public static function handleCallback($provider, $code, $redirectUri)
    {
        if (!isset(self::$providers[$provider])) {
            return null;
        }

        $config = self::getProviderConfig($provider);
        if (!$config) {
            return null;
        }

        $providerInfo = self::$providers[$provider];

        // Exchange code for token
        $tokenData = self::exchangeCode($provider, $code, $redirectUri, $config, $providerInfo);
        if (!$tokenData || empty($tokenData['access_token'])) {
            return null;
        }

        // Fetch user info
        $userInfo = self::fetchUserInfo($provider, $tokenData['access_token'], $providerInfo);
        if (!$userInfo) {
            return null;
        }

        return [
            'provider_user_id' => $userInfo['id'],
            'email' => $userInfo['email'] ?? null,
            'name' => $userInfo['name'] ?? null,
            'avatar_url' => $userInfo['avatar_url'] ?? null,
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'token_expires_at' => !empty($tokenData['expires_in'])
                ? date('Y-m-d H:i:s', time() + (int) $tokenData['expires_in'])
                : null,
        ];
    }

    /**
     * Link an OAuth account to an AtoM user.
     *
     * @param int    $userId
     * @param string $provider
     * @param array  $oauthData
     * @return int Insert ID
     */
    public static function linkAccount($userId, $provider, array $oauthData)
    {
        // Check if already linked
        $existing = DB::table('registry_oauth_account')
            ->where('user_id', $userId)
            ->where('provider', $provider)
            ->first();

        $data = [
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $oauthData['provider_user_id'],
            'email' => $oauthData['email'] ?? null,
            'name' => $oauthData['name'] ?? null,
            'avatar_url' => $oauthData['avatar_url'] ?? null,
            'access_token' => $oauthData['access_token'] ?? null,
            'refresh_token' => $oauthData['refresh_token'] ?? null,
            'token_expires_at' => $oauthData['token_expires_at'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            DB::table('registry_oauth_account')
                ->where('id', $existing->id)
                ->update($data);
            return $existing->id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return DB::table('registry_oauth_account')->insertGetId($data);
    }

    /**
     * Unlink an OAuth account.
     *
     * @param int    $userId
     * @param string $provider
     * @return bool
     */
    public static function unlinkAccount($userId, $provider)
    {
        return DB::table('registry_oauth_account')
            ->where('user_id', $userId)
            ->where('provider', $provider)
            ->delete() > 0;
    }

    /**
     * Find an AtoM user by OAuth provider + provider_user_id.
     *
     * @param string $provider
     * @param string $providerUserId
     * @return object|null
     */
    public static function findByProviderAccount($provider, $providerUserId)
    {
        return DB::table('registry_oauth_account')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    /**
     * Find an AtoM user by OAuth email.
     *
     * @param string $email
     * @return object|null
     */
    public static function findByEmail($email)
    {
        return DB::table('registry_oauth_account')
            ->where('email', $email)
            ->first();
    }

    /**
     * Get all linked accounts for a user.
     *
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public static function getLinkedAccounts($userId)
    {
        return DB::table('registry_oauth_account')
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Get the list of supported provider names.
     *
     * @return array
     */
    public static function getSupportedProviders()
    {
        return array_keys(self::$providers);
    }

    /**
     * Check if a provider is enabled (has client_id configured).
     *
     * @param string $provider
     * @return bool
     */
    public static function isProviderEnabled($provider)
    {
        $config = self::getProviderConfig($provider);
        return $config && !empty($config['client_id']);
    }

    /**
     * Get enabled providers.
     *
     * @return array
     */
    public static function getEnabledProviders()
    {
        $enabled = [];
        foreach (array_keys(self::$providers) as $provider) {
            if (self::isProviderEnabled($provider)) {
                $enabled[] = $provider;
            }
        }
        return $enabled;
    }

    /**
     * Validate the OAuth state parameter (CSRF protection).
     *
     * @param string $state
     * @return bool
     */
    public static function validateState($state)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $expected = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        return $expected && hash_equals($expected, $state);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Get provider client_id + client_secret from registry_settings.
     */
    private static function getProviderConfig($provider)
    {
        $clientId = DB::table('registry_settings')
            ->where('setting_key', 'oauth_' . $provider . '_client_id')
            ->value('setting_value');

        $clientSecret = DB::table('registry_settings')
            ->where('setting_key', 'oauth_' . $provider . '_client_secret')
            ->value('setting_value');

        if (empty($clientId) || empty($clientSecret)) {
            return null;
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
    }

    /**
     * Exchange authorization code for access token.
     */
    private static function exchangeCode($provider, $code, $redirectUri, $config, $providerInfo)
    {
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $headers = ['Accept: application/json'];

        $ch = curl_init($providerInfo['token_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Fetch user info from provider API.
     */
    private static function fetchUserInfo($provider, $accessToken, $providerInfo)
    {
        $ch = curl_init($providerInfo['userinfo_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'User-Agent: AtoM-Heratio-Registry/1.0',
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!$data) {
            return null;
        }

        // Normalize across providers
        return self::normalizeUserInfo($provider, $data, $accessToken);
    }

    /**
     * Normalize user info response across different providers.
     */
    private static function normalizeUserInfo($provider, array $data, $accessToken = null)
    {
        switch ($provider) {
            case 'google':
                return [
                    'id' => $data['sub'] ?? null,
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? null,
                    'avatar_url' => $data['picture'] ?? null,
                ];

            case 'facebook':
                return [
                    'id' => $data['id'] ?? null,
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? null,
                    'avatar_url' => isset($data['picture']['data']['url']) ? $data['picture']['data']['url'] : null,
                ];

            case 'github':
                $email = $data['email'] ?? null;
                // GitHub may not return email in profile — fetch from emails API
                if (!$email && $accessToken) {
                    $email = self::fetchGitHubEmail($accessToken);
                }
                return [
                    'id' => (string) ($data['id'] ?? ''),
                    'email' => $email,
                    'name' => $data['name'] ?? $data['login'] ?? null,
                    'avatar_url' => $data['avatar_url'] ?? null,
                ];

            case 'linkedin':
                return [
                    'id' => $data['sub'] ?? null,
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? null,
                    'avatar_url' => $data['picture'] ?? null,
                ];

            case 'microsoft':
                return [
                    'id' => $data['id'] ?? null,
                    'email' => $data['mail'] ?? $data['userPrincipalName'] ?? null,
                    'name' => $data['displayName'] ?? null,
                    'avatar_url' => null, // MS Graph photo requires separate endpoint
                ];

            default:
                return [
                    'id' => $data['id'] ?? $data['sub'] ?? null,
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? null,
                    'avatar_url' => null,
                ];
        }
    }

    /**
     * GitHub-specific: fetch primary email from /user/emails endpoint.
     */
    private static function fetchGitHubEmail($accessToken)
    {
        $ch = curl_init('https://api.github.com/user/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'User-Agent: AtoM-Heratio-Registry/1.0',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $emails = json_decode($response, true);
        if (!is_array($emails)) {
            return null;
        }

        // Find primary verified email
        foreach ($emails as $entry) {
            if (!empty($entry['primary']) && !empty($entry['verified'])) {
                return $entry['email'];
            }
        }

        // Fallback: first verified email
        foreach ($emails as $entry) {
            if (!empty($entry['verified'])) {
                return $entry['email'];
            }
        }

        return null;
    }
}
