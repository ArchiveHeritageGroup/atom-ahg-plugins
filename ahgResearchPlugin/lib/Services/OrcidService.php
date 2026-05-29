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

    // =========================================================================
    // §2.4 Works push/pull (extension - 2026-05-16)
    //
    // Tokens persisted in the new research_orcid_link table; the existing
    // OAuth flow above lands tokens on research_researcher columns. We keep
    // both: the older columns remain authoritative for the connect/disconnect
    // page; this method mirrors any successful exchange onto the new table so
    // pull/push has a stable home with token-encryption fields.
    // =========================================================================

    /**
     * Persist tokens onto research_orcid_link (upsert).
     */
    public function linkResearcher(int $researcherId, array $tokenResponse): void
    {
        $orcidId = $tokenResponse['orcid']           ?? null;
        $access  = $tokenResponse['access_token']    ?? null;
        $refresh = $tokenResponse['refresh_token']   ?? null;
        $scope   = $tokenResponse['scope']           ?? null;
        $expires = isset($tokenResponse['expires_in']) ? date('Y-m-d H:i:s', time() + (int) $tokenResponse['expires_in']) : null;

        if (!$orcidId || !$access) {
            return;
        }

        $row = \Illuminate\Database\Capsule\Manager::table('research_orcid_link')
            ->where('researcher_id', $researcherId)
            ->first();

        $payload = [
            'orcid_id'                 => $orcidId,
            'access_token_encrypted'   => $this->encryptToken((string) $access),
            'refresh_token_encrypted'  => $refresh ? $this->encryptToken((string) $refresh) : null,
            'scope'                    => $scope,
            'expires_at'               => $expires,
            'updated_at'               => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            \Illuminate\Database\Capsule\Manager::table('research_orcid_link')
                ->where('id', $row->id)
                ->update($payload);
        } else {
            $payload['researcher_id'] = $researcherId;
            $payload['created_at']    = date('Y-m-d H:i:s');
            \Illuminate\Database\Capsule\Manager::table('research_orcid_link')->insert($payload);
        }
    }

    public function unlink(int $researcherId): void
    {
        \Illuminate\Database\Capsule\Manager::table('research_orcid_link')
            ->where('researcher_id', $researcherId)
            ->delete();
    }

    public function getLink(int $researcherId): ?object
    {
        return \Illuminate\Database\Capsule\Manager::table('research_orcid_link')
            ->where('researcher_id', $researcherId)
            ->first();
    }

    /**
     * Pull works from the researcher's ORCID record.
     *
     * @return array list of {put-code, title, year, journal, doi}
     */
    public function pullWorks(int $researcherId): array
    {
        $link = $this->getLink($researcherId);
        if (!$link) {
            throw new \RuntimeException('Researcher is not linked to ORCID');
        }
        $token = $this->decryptToken($link->access_token_encrypted);
        $url   = $this->memberApiBase() . '/' . $link->orcid_id . '/works';

        $resp = $this->orcidApiGet($url, $token);
        if (empty($resp['group'])) {
            $this->markSynced($link->id, 0);
            return [];
        }

        $works = [];
        foreach ($resp['group'] as $g) {
            foreach (($g['work-summary'] ?? []) as $w) {
                $works[] = [
                    'put_code' => $w['put-code']                                    ?? null,
                    'title'    => $w['title']['title']['value']                    ?? null,
                    'year'     => $w['publication-date']['year']['value']          ?? null,
                    'journal'  => $w['journal-title']['value']                     ?? null,
                    'doi'      => $this->findExternalId($w['external-ids'] ?? null, 'doi'),
                    'type'     => $w['type']                                       ?? null,
                ];
            }
        }
        $this->markSynced($link->id, count($works));
        return $works;
    }

    /**
     * Push a citation as an ORCID Work record. Returns the new put-code.
     *
     * @param array $citation Required keys: title. Optional: year, journal, doi, type
     */
    public function pushWork(int $researcherId, array $citation): ?string
    {
        $link = $this->getLink($researcherId);
        if (!$link) {
            throw new \RuntimeException('Researcher is not linked to ORCID');
        }
        $token = $this->decryptToken($link->access_token_encrypted);
        $url   = $this->memberApiBase() . '/' . $link->orcid_id . '/work';
        $xml   = $this->buildWorkXml($citation);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/vnd.orcid+xml',
                'Accept: application/vnd.orcid+xml',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HEADER         => true,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            throw new \RuntimeException("ORCID push failed: HTTP {$http}");
        }
        // The put-code is in the Location header
        if (is_string($resp) && preg_match('/Location:\s*.*\/work\/(\d+)/i', $resp, $m)) {
            return $m[1];
        }
        return null;
    }

    protected function memberApiBase(): string
    {
        // For pull, the public Pub API is sufficient. Push needs the Member
        // API. We default to the same host pattern but allow override via
        // app_orcid_api_base config.
        $override = sfConfig::get('app_orcid_api_base');
        if ($override) {
            return rtrim($override, '/') . '/v3.0';
        }
        return $this->useSandbox ? 'https://api.sandbox.orcid.org/v3.0' : 'https://api.orcid.org/v3.0';
    }

    protected function buildWorkXml(array $citation): string
    {
        $title   = htmlspecialchars((string) ($citation['title'] ?? 'Untitled'), ENT_XML1);
        $year    = isset($citation['year']) ? '<common:year>' . htmlspecialchars((string) $citation['year'], ENT_XML1) . '</common:year>' : '';
        $type    = htmlspecialchars((string) ($citation['type'] ?? 'other'), ENT_XML1);
        $journal = !empty($citation['journal'])
            ? '<work:journal-title>' . htmlspecialchars((string) $citation['journal'], ENT_XML1) . '</work:journal-title>'
            : '';
        $doiBlock = '';
        if (!empty($citation['doi'])) {
            $doi = htmlspecialchars((string) $citation['doi'], ENT_XML1);
            $doiBlock = <<<XML
  <common:external-ids>
    <common:external-id>
      <common:external-id-type>doi</common:external-id-type>
      <common:external-id-value>{$doi}</common:external-id-value>
      <common:external-id-relationship>self</common:external-id-relationship>
    </common:external-id>
  </common:external-ids>
XML;
        }
        $datePart = $year ? "<common:publication-date>{$year}</common:publication-date>" : '';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<work:work xmlns:common="http://www.orcid.org/ns/common" xmlns:work="http://www.orcid.org/ns/work">
  <work:title>
    <common:title>{$title}</common:title>
  </work:title>
  {$journal}
  <work:type>{$type}</work:type>
  {$datePart}
  {$doiBlock}
</work:work>
XML;
    }

    protected function orcidApiGet(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/vnd.orcid+json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http < 200 || $http >= 300 || !$resp) {
            throw new \RuntimeException("ORCID GET {$url} failed: HTTP {$http}");
        }
        $data = json_decode((string) $resp, true);
        return is_array($data) ? $data : [];
    }

    protected function findExternalId($externalIds, string $type): ?string
    {
        if (!is_array($externalIds) || empty($externalIds['external-id'])) {
            return null;
        }
        foreach ($externalIds['external-id'] as $id) {
            if (($id['external-id-type'] ?? null) === $type) {
                return $id['external-id-value']['value'] ?? ($id['external-id-value'] ?? null);
            }
        }
        return null;
    }

    protected function markSynced(int $linkId, int $count): void
    {
        \Illuminate\Database\Capsule\Manager::table('research_orcid_link')
            ->where('id', $linkId)
            ->update([
                'last_synced_at'   => date('Y-m-d H:i:s'),
                'last_works_count' => $count,
                'last_error'       => null,
            ]);
    }

    // ========================================================================
    // Per-researcher OAuth credentials (self-service — Heratio #102 parity)
    // ========================================================================

    /**
     * Resolve the effective ORCID client credentials for a researcher: their
     * own registered app if present, otherwise the global admin/.env config.
     *
     * @return array{client_id:string,client_secret:string,redirect_uri:string,api_base:string}
     */
    public function getCredentials(?int $researcherId): array
    {
        if ($researcherId) {
            try {
                $row = DB::table('researcher_orcid_credential')->where('researcher_id', $researcherId)->first();
            } catch (\Throwable $e) {
                $row = null;
            }
            if ($row && !empty($row->client_id)) {
                return [
                    'client_id'     => (string) $row->client_id,
                    'client_secret' => $this->decryptToken($row->client_secret_encrypted ?? ''),
                    'redirect_uri'  => $row->redirect_uri ?: $this->redirectUri,
                    'api_base'      => $row->api_base ? rtrim($row->api_base, '/') : $this->getApiUrl(),
                ];
            }
        }

        return [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'api_base'      => $this->getApiUrl(),
        ];
    }

    /** True when the researcher (or global config) has a usable OAuth client. */
    public function isConfiguredFor(?int $researcherId): bool
    {
        $c = $this->getCredentials($researcherId);

        return !empty($c['client_id']) && !empty($c['client_secret']) && !empty($c['redirect_uri']);
    }

    /** Save / update a researcher's own ORCID client credentials (secret encrypted). */
    public function saveCredentials(int $researcherId, string $clientId, string $clientSecret, ?string $redirectUri = null, ?string $apiBase = null): void
    {
        $now    = date('Y-m-d H:i:s');
        $values = [
            'client_id'    => trim($clientId),
            'redirect_uri' => $redirectUri ?: $this->redirectUri,
            'api_base'     => $apiBase ?: null,
            'updated_at'   => $now,
            'created_at'   => $now,
        ];
        // Only (re)write the secret when one was supplied — a blank secret on
        // edit preserves the stored value.
        if ($clientSecret !== '') {
            $values['client_secret_encrypted'] = $this->encryptToken(trim($clientSecret));
        }

        DB::table('researcher_orcid_credential')->updateOrInsert(
            ['researcher_id' => $researcherId],
            $values
        );
    }

    /** Remove a researcher's stored client credentials (reverts to global config). */
    public function clearCredentials(int $researcherId): void
    {
        DB::table('researcher_orcid_credential')->where('researcher_id', $researcherId)->delete();
    }

    /** Authorization URL using the researcher's own (or global) client. */
    public function getAuthorizationUrlFor(?int $researcherId, string $state): string
    {
        $c = $this->getCredentials($researcherId);
        if (empty($c['client_id']) || empty($c['redirect_uri'])) {
            throw new RuntimeException('ORCID client not configured for this researcher');
        }

        return $this->getBaseUrl() . '/oauth/authorize?' . http_build_query([
            'client_id'     => $c['client_id'],
            'response_type' => 'code',
            'scope'         => '/authenticate /read-limited',
            'redirect_uri'  => $c['redirect_uri'],
            'state'         => $state,
        ]);
    }

    /** Token exchange using the researcher's own (or global) client. */
    public function exchangeCodeForTokenFor(string $code, ?int $researcherId): array
    {
        $c = $this->getCredentials($researcherId);
        if (empty($c['client_id']) || empty($c['client_secret']) || empty($c['redirect_uri'])) {
            throw new RuntimeException('ORCID client not configured for this researcher');
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->getBaseUrl() . '/oauth/token',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $c['client_id'],
                'client_secret' => $c['client_secret'],
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $c['redirect_uri'],
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException('ORCID API request failed: ' . $error);
        }
        $data = json_decode($response, true);
        if ($httpCode !== 200 || isset($data['error'])) {
            throw new RuntimeException('ORCID token exchange failed: ' . ($data['error_description'] ?? $data['error'] ?? 'Unknown error'));
        }

        return $data;
    }

    /** Verify + link using the researcher's own (or global) client. */
    public function verifyOrcidFor(int $researcherId, string $code): array
    {
        try {
            $token = $this->exchangeCodeForTokenFor($code, $researcherId);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
        $this->linkResearcher($researcherId, $token);

        return ['orcid_id' => $token['orcid'] ?? null];
    }

    // ========================================================================
    // Tokenless public-record read (pub.orcid.org) — Fetch / Pull profile
    // ========================================================================

    /** Extract a bare ORCID iD (####-####-####-###X) from a raw string / URL. */
    public function normaliseOrcidId(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        if (preg_match('/(\d{4}-\d{4}-\d{4}-\d{3}[\dXx])/', $raw, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Fetch a public ORCID record (no credentials required). Returns a flat
     * profile array, or null on failure. Hard 12s timeout so a slow ORCID
     * cannot hang the request.
     *
     * @return array|null
     */
    public function fetchPublicRecord(string $orcidId): ?array
    {
        $orcidId = $this->normaliseOrcidId($orcidId);
        if (!$orcidId) {
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->getApiUrl() . '/' . $orcidId . '/record',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }
        $rec = json_decode($response, true);
        if (!is_array($rec)) {
            return null;
        }

        $person = $rec['person'] ?? [];
        $name   = $person['name'] ?? [];

        $keywords = [];
        foreach (($person['keywords']['keyword'] ?? []) as $kw) {
            if (!empty($kw['content'])) {
                $keywords[] = $kw['content'];
            }
        }

        // Most recent employment, if any.
        $institution = $department = $position = null;
        $groups = $rec['activities-summary']['employments']['affiliation-group'] ?? [];
        foreach ($groups as $g) {
            $emp = $g['summaries'][0]['employment-summary'] ?? null;
            if ($emp) {
                $institution = $emp['organization']['name'] ?? $institution;
                $department  = $emp['department-name'] ?? $department;
                $position    = $emp['role-title'] ?? $position;
                break;
            }
        }

        return [
            'first_name'         => $name['given-names']['value'] ?? null,
            'last_name'          => $name['family-name']['value'] ?? null,
            'institution'        => $institution,
            'department'         => $department,
            'position'           => $position,
            'research_interests' => $keywords ? implode(', ', $keywords) : null,
            'orcid_id'           => $orcidId,
        ];
    }

    /**
     * Pull a researcher's public ORCID profile and update non-empty columns on
     * research_researcher. Stamps research_orcid_link.last_profile_synced_at.
     *
     * @return array|null The fetched profile, or null when nothing was pulled.
     */
    public function pullProfile(int $researcherId): ?array
    {
        $link    = $this->getLink($researcherId);
        $orcidId = $link->orcid_id ?? DB::table('research_researcher')->where('id', $researcherId)->value('orcid_id');
        if (!$orcidId) {
            throw new RuntimeException('Researcher has no ORCID iD to pull from');
        }

        $record = $this->fetchPublicRecord($orcidId);
        if (!$record) {
            return null;
        }

        $schema = \Illuminate\Database\Capsule\Manager::schema();

        $update = [];
        foreach (['first_name', 'last_name', 'institution', 'department', 'position', 'research_interests', 'orcid_id'] as $col) {
            if (!empty($record[$col]) && $schema->hasColumn('research_researcher', $col)) {
                $update[$col] = $record[$col];
            }
        }
        if ($update) {
            try {
                DB::table('research_researcher')->where('id', $researcherId)->update($update);
            } catch (\Throwable $e) {
                // best-effort — leave existing data intact on failure
            }
        }

        if ($link) {
            try {
                if ($schema->hasColumn('research_orcid_link', 'last_profile_synced_at')) {
                    DB::table('research_orcid_link')->where('researcher_id', $researcherId)
                        ->update(['last_profile_synced_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $record;
    }

    /**
     * AES-256-CBC token encryption keyed off sf_app_secret.
     */
    protected function encryptToken(string $plain): string
    {
        $key = $this->encryptionKey();
        $iv  = random_bytes(16);
        $enc = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $enc);
    }

    protected function decryptToken(?string $encoded): string
    {
        if (!$encoded) {
            return '';
        }
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv  = substr($raw, 0, 16);
        $enc = substr($raw, 16);
        $key = $this->encryptionKey();
        $plain = openssl_decrypt($enc, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : (string) $plain;
    }

    protected function encryptionKey(): string
    {
        $secret = sfConfig::get('sf_app_secret') ?: sfConfig::get('sf_secret_key') ?: 'fallback-not-secure';
        return hash('sha256', (string) $secret, true);
    }
}
