<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * OrcidService - ORCID OAuth 2.0 Integration Service
 *
 * Handles ORCID authentication and profile integration for researchers.
 * Supports both production and sandbox ORCID environments.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class OrcidService
{
    // ORCID API endpoints
    private const ORCID_PRODUCTION_URL = 'https://orcid.org';
    private const ORCID_SANDBOX_URL = 'https://sandbox.orcid.org';
    private const ORCID_API_PRODUCTION = 'https://pub.orcid.org/v3.0';
    private const ORCID_API_SANDBOX = 'https://pub.sandbox.orcid.org/v3.0';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private bool $useSandbox;

    public function __construct()
    {
        // Load ORCID configuration from settings
        $this->clientId = sfConfig::get('app_orcid_client_id', '');
        $this->clientSecret = sfConfig::get('app_orcid_client_secret', '');
        $this->redirectUri = sfConfig::get('app_orcid_redirect_uri', '');
        $this->useSandbox = (bool) sfConfig::get('app_orcid_sandbox', true);
    }

    /**
     * Check if ORCID integration is configured and available.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }

    /**
     * Get the base ORCID URL (production or sandbox).
     */
    private function getBaseUrl(): string
    {
        return $this->useSandbox ? self::ORCID_SANDBOX_URL : self::ORCID_PRODUCTION_URL;
    }

    /**
     * Get the ORCID API URL (production or sandbox).
     */
    private function getApiUrl(): string
    {
        return $this->useSandbox ? self::ORCID_API_SANDBOX : self::ORCID_API_PRODUCTION;
    }

    /**
     * Generate the ORCID OAuth authorization URL.
     *
     * @param string $state CSRF protection state token
     * @return string The authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('ORCID integration is not configured');
        }

        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => '/authenticate /read-limited',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ];

        return $this->getBaseUrl() . '/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access and refresh tokens.
     *
     * @param string $code The authorization code from ORCID
     * @return array Token response containing access_token, refresh_token, orcid, name, etc.
     * @throws RuntimeException On API failure
     */
    public function exchangeCodeForToken(string $code): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('ORCID integration is not configured');
        }

        $tokenUrl = $this->getBaseUrl() . '/oauth/token';

        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tokenUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException('ORCID API request failed: ' . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || isset($data['error'])) {
            $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            throw new RuntimeException('ORCID token exchange failed: ' . $errorMsg);
        }

        return $data;
    }

    /**
     * Refresh an expired access token.
     *
     * @param string $refreshToken The refresh token
     * @return array New token response
     * @throws RuntimeException On API failure
     */
    public function refreshToken(string $refreshToken): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('ORCID integration is not configured');
        }

        $tokenUrl = $this->getBaseUrl() . '/oauth/token';

        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tokenUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException('ORCID API request failed: ' . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || isset($data['error'])) {
            $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            throw new RuntimeException('ORCID token refresh failed: ' . $errorMsg);
        }

        return $data;
    }

    /**
     * Verify ORCID and link to researcher profile.
     *
     * @param int $researcherId The researcher ID
     * @param string $code The authorization code from ORCID callback
     * @return array Result with success status and ORCID profile data
     */
    public function verifyOrcid(int $researcherId, string $code): array
    {
        try {
            // Exchange code for tokens
            $tokenData = $this->exchangeCodeForToken($code);

            $orcidId = $tokenData['orcid'] ?? null;
            if (!$orcidId) {
                return ['success' => false, 'error' => 'ORCID ID not returned'];
            }

            // Check if this ORCID is already linked to another researcher
            $existing = DB::table('research_researcher')
                ->where('orcid_id', $orcidId)
                ->where('id', '!=', $researcherId)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'error' => 'This ORCID is already linked to another researcher account',
                ];
            }

            // Calculate token expiry
            $expiresIn = $tokenData['expires_in'] ?? 3600;
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

            // Update researcher record
            DB::table('research_researcher')
                ->where('id', $researcherId)
                ->update([
                    'orcid_id' => $orcidId,
                    'orcid_verified' => 1,
                    'orcid_access_token' => $tokenData['access_token'] ?? null,
                    'orcid_refresh_token' => $tokenData['refresh_token'] ?? null,
                    'orcid_token_expires_at' => $expiresAt,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Create verification record
            DB::table('research_verification')->insert([
                'researcher_id' => $researcherId,
                'verification_type' => 'orcid',
                'document_reference' => $orcidId,
                'verification_data' => json_encode([
                    'name' => $tokenData['name'] ?? null,
                    'verified_at' => date('Y-m-d H:i:s'),
                ]),
                'status' => 'verified',
                'verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Fetch additional profile data
            $profile = $this->getOrcidProfile($orcidId, $tokenData['access_token']);

            return [
                'success' => true,
                'orcid_id' => $orcidId,
                'name' => $tokenData['name'] ?? null,
                'profile' => $profile,
            ];
        } catch (Exception $e) {
            error_log('OrcidService::verifyOrcid error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch ORCID profile data from public API.
     *
     * @param string $orcidId The ORCID iD
     * @param string|null $accessToken Optional access token for authenticated requests
     * @return array Profile data
     */
    public function getOrcidProfile(string $orcidId, ?string $accessToken = null): array
    {
        $url = $this->getApiUrl() . '/' . $orcidId . '/record';

        $headers = [
            'Accept: application/json',
        ];

        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return ['error' => 'Failed to fetch ORCID profile'];
        }

        $data = json_decode($response, true);

        // Extract relevant profile information
        $profile = [
            'orcid_id' => $orcidId,
            'orcid_url' => $this->getBaseUrl() . '/' . $orcidId,
        ];

        // Name
        if (isset($data['person']['name'])) {
            $name = $data['person']['name'];
            $profile['given_name'] = $name['given-names']['value'] ?? null;
            $profile['family_name'] = $name['family-name']['value'] ?? null;
            $profile['credit_name'] = $name['credit-name']['value'] ?? null;
        }

        // Biography
        if (isset($data['person']['biography']['content'])) {
            $profile['biography'] = $data['person']['biography']['content'];
        }

        // External identifiers
        if (isset($data['person']['external-identifiers']['external-identifier'])) {
            $profile['external_ids'] = [];
            foreach ($data['person']['external-identifiers']['external-identifier'] as $ext) {
                $profile['external_ids'][] = [
                    'type' => $ext['external-id-type'] ?? null,
                    'value' => $ext['external-id-value'] ?? null,
                    'url' => $ext['external-id-url']['value'] ?? null,
                ];
            }
        }

        // Research keywords
        if (isset($data['person']['keywords']['keyword'])) {
            $profile['keywords'] = array_map(
                fn($k) => $k['content'] ?? '',
                $data['person']['keywords']['keyword']
            );
        }

        // Affiliations (employments)
        if (isset($data['activities-summary']['employments']['affiliation-group'])) {
            $profile['affiliations'] = [];
            foreach ($data['activities-summary']['employments']['affiliation-group'] as $group) {
                foreach ($group['summaries'] ?? [] as $summary) {
                    $emp = $summary['employment-summary'] ?? [];
                    $profile['affiliations'][] = [
                        'organization' => $emp['organization']['name'] ?? null,
                        'role' => $emp['role-title'] ?? null,
                        'department' => $emp['department-name'] ?? null,
                        'start_date' => $this->formatOrcidDate($emp['start-date'] ?? null),
                        'end_date' => $this->formatOrcidDate($emp['end-date'] ?? null),
                    ];
                }
            }
        }

        // Education
        if (isset($data['activities-summary']['educations']['affiliation-group'])) {
            $profile['education'] = [];
            foreach ($data['activities-summary']['educations']['affiliation-group'] as $group) {
                foreach ($group['summaries'] ?? [] as $summary) {
                    $edu = $summary['education-summary'] ?? [];
                    $profile['education'][] = [
                        'organization' => $edu['organization']['name'] ?? null,
                        'role' => $edu['role-title'] ?? null,
                        'department' => $edu['department-name'] ?? null,
                        'start_date' => $this->formatOrcidDate($edu['start-date'] ?? null),
                        'end_date' => $this->formatOrcidDate($edu['end-date'] ?? null),
                    ];
                }
            }
        }

        // Works count
        if (isset($data['activities-summary']['works']['group'])) {
            $profile['works_count'] = count($data['activities-summary']['works']['group']);
        }

        return $profile;
    }

    /**
     * Format ORCID date structure to string.
     */
    private function formatOrcidDate(?array $date): ?string
    {
        if (!$date) {
            return null;
        }

        $year = $date['year']['value'] ?? null;
        $month = $date['month']['value'] ?? null;
        $day = $date['day']['value'] ?? null;

        if (!$year) {
            return null;
        }

        if ($month && $day) {
            return sprintf('%s-%02d-%02d', $year, $month, $day);
        } elseif ($month) {
            return sprintf('%s-%02d', $year, $month);
        }

        return $year;
    }

    /**
     * Disconnect ORCID from researcher profile.
     *
     * @param int $researcherId The researcher ID
     * @return bool Success status
     */
    public function disconnectOrcid(int $researcherId): bool
    {
        $updated = DB::table('research_researcher')
            ->where('id', $researcherId)
            ->update([
                'orcid_id' => null,
                'orcid_verified' => 0,
                'orcid_access_token' => null,
                'orcid_refresh_token' => null,
                'orcid_token_expires_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Mark verification as expired
        DB::table('research_verification')
            ->where('researcher_id', $researcherId)
            ->where('verification_type', 'orcid')
            ->where('status', 'verified')
            ->update([
                'status' => 'expired',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $updated > 0;
    }

    /**
     * Check if researcher's ORCID token needs refresh.
     *
     * @param int $researcherId The researcher ID
     * @return bool True if token is expired or expiring soon
     */
    public function needsTokenRefresh(int $researcherId): bool
    {
        $researcher = DB::table('research_researcher')
            ->where('id', $researcherId)
            ->select('orcid_token_expires_at', 'orcid_refresh_token')
            ->first();

        if (!$researcher || !$researcher->orcid_refresh_token) {
            return false;
        }

        if (!$researcher->orcid_token_expires_at) {
            return true;
        }

        // Refresh if token expires within 1 hour
        $expiresAt = strtotime($researcher->orcid_token_expires_at);
        return $expiresAt < (time() + 3600);
    }

    /**
     * Refresh researcher's ORCID tokens if needed.
     *
     * @param int $researcherId The researcher ID
     * @return bool Success status
     */
    public function refreshResearcherToken(int $researcherId): bool
    {
        $researcher = DB::table('research_researcher')
            ->where('id', $researcherId)
            ->select('orcid_refresh_token')
            ->first();

        if (!$researcher || !$researcher->orcid_refresh_token) {
            return false;
        }

        try {
            $tokenData = $this->refreshToken($researcher->orcid_refresh_token);

            $expiresIn = $tokenData['expires_in'] ?? 3600;
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

            DB::table('research_researcher')
                ->where('id', $researcherId)
                ->update([
                    'orcid_access_token' => $tokenData['access_token'] ?? null,
                    'orcid_refresh_token' => $tokenData['refresh_token'] ?? $researcher->orcid_refresh_token,
                    'orcid_token_expires_at' => $expiresAt,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return true;
        } catch (Exception $e) {
            error_log('OrcidService::refreshResearcherToken error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get researcher's valid ORCID access token, refreshing if needed.
     *
     * @param int $researcherId The researcher ID
     * @return string|null The access token or null if unavailable
     */
    public function getValidAccessToken(int $researcherId): ?string
    {
        if ($this->needsTokenRefresh($researcherId)) {
            $this->refreshResearcherToken($researcherId);
        }

        $researcher = DB::table('research_researcher')
            ->where('id', $researcherId)
            ->select('orcid_access_token')
            ->first();

        return $researcher->orcid_access_token ?? null;
    }

    /**
     * Generate CSRF state token for OAuth flow.
     *
     * @return string Random state token
     */
    public function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate ORCID iD format.
     *
     * @param string $orcidId The ORCID iD to validate
     * @return bool True if valid format
     */
    public static function validateOrcidFormat(string $orcidId): bool
    {
        // ORCID format: 0000-0001-2345-6789 (with checksum)
        $pattern = '/^(\d{4}-){3}\d{3}[\dX]$/';
        if (!preg_match($pattern, $orcidId)) {
            return false;
        }

        // Validate checksum (ISO 7064 Mod 11,2)
        $digits = str_replace('-', '', $orcidId);
        $total = 0;
        for ($i = 0; $i < 15; $i++) {
            $digit = ($digits[$i] === 'X') ? 10 : (int) $digits[$i];
            $total = ($total + $digit) * 2;
        }
        $remainder = $total % 11;
        $checkDigit = (12 - $remainder) % 11;
        $expectedCheck = ($checkDigit === 10) ? 'X' : (string) $checkDigit;

        return $digits[15] === $expectedCheck;
    }

    /**
     * Format ORCID iD as URL.
     *
     * @param string $orcidId The ORCID iD
     * @return string The ORCID profile URL
     */
    public function formatOrcidUrl(string $orcidId): string
    {
        return $this->getBaseUrl() . '/' . $orcidId;
    }
}
