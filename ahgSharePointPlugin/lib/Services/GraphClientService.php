<?php

namespace AtomExtensions\SharePoint\Services;

use AtomExtensions\SharePoint\Repositories\SharePointTenantRepository;
use AtomFramework\Services\HttpClientService;

/**
 * GraphClientService — hand-rolled Microsoft Graph wrapper.
 *
 * Decision (locked 2026-05-10): no microsoft/microsoft-graph SDK.
 * Reuses framework HttpClientService for SSRF protection, timeouts, blocked hosts.
 *
 * @phase 1
 */
class GraphClientService
{
    private const TOKEN_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';
    private const GRAPH_DEFAULT_SCOPE = 'https://graph.microsoft.com/.default';
    private const DOWNLOAD_MAX_BYTES = 100 * 1024 * 1024; // 100 MB cap; streaming for larger files is Phase 2.B.1

    private SharePointTenantRepository $tenants;
    private GraphTokenCache $cache;

    public function __construct(
        ?SharePointTenantRepository $tenants = null,
        ?GraphTokenCache $cache = null,
    ) {
        $this->tenants = $tenants ?? new SharePointTenantRepository();
        $this->cache = $cache ?? new GraphTokenCache();
    }

    /**
     * Acquire (or return cached) app-only access token for a tenant.
     * Client-credentials flow. Stored token is short-lived.
     */
    public function acquireToken(int $tenantId): string
    {
        $cached = $this->cache->get($tenantId);
        if ($cached !== null) {
            return $cached;
        }

        $tenant = $this->tenants->find($tenantId);
        if ($tenant === null) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }

        $secret = $this->tenants->resolveSecret($tenantId);
        $url = sprintf(self::TOKEN_URL, $tenant->tenant_id);
        $body = http_build_query([
            'client_id' => $tenant->client_id,
            'client_secret' => $secret,
            'scope' => self::GRAPH_DEFAULT_SCOPE,
            'grant_type' => 'client_credentials',
        ]);

        $resp = HttpClientService::post(
            $url,
            $body,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            ['timeout' => 15],
        );

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $this->tenants->update($tenantId, [
                'last_error' => substr('token acquire failed: HTTP ' . $resp['status'] . ' ' . $resp['body'], 0, 65000),
                'status' => 'error',
            ]);
            throw new \RuntimeException(
                "Token acquisition failed for tenant {$tenantId}: HTTP {$resp['status']} {$resp['body']}",
            );
        }

        $payload = json_decode($resp['body'], true);
        if (!is_array($payload) || empty($payload['access_token'])) {
            throw new \RuntimeException('Token response malformed: ' . substr($resp['body'], 0, 500));
        }

        $token = (string) $payload['access_token'];
        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $this->cache->put($tenantId, $token, $expiresIn);

        $this->tenants->update($tenantId, [
            'last_token_at' => date('Y-m-d H:i:s'),
            'last_error' => null,
            'status' => 'active',
        ]);

        return $token;
    }

    /**
     * On-Behalf-Of flow — exchange a user's bearer token for a Graph token
     * impersonating that user. Used by the manual-push file fetch path.
     */
    public function acquireOboToken(int $tenantId, string $userToken, string $graphScope): string
    {
        $tenant = $this->tenants->find($tenantId);
        if ($tenant === null) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }
        $secret = $this->tenants->resolveSecret($tenantId);

        $url = sprintf(self::TOKEN_URL, $tenant->tenant_id);
        $body = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'client_id' => $tenant->client_id,
            'client_secret' => $secret,
            'assertion' => $userToken,
            'scope' => $graphScope,
            'requested_token_use' => 'on_behalf_of',
        ]);

        $resp = HttpClientService::post(
            $url,
            $body,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            ['timeout' => 15],
        );

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException(
                "OBO token exchange failed: HTTP {$resp['status']} {$resp['body']}",
            );
        }

        $payload = json_decode($resp['body'], true);
        if (!is_array($payload) || empty($payload['access_token'])) {
            throw new \RuntimeException('OBO token response malformed');
        }

        // OBO tokens are NOT cached at the tenant key — they're per-user.
        // Caller can re-acquire as needed; tokens are short-lived.
        return (string) $payload['access_token'];
    }

    /** GET request. */
    public function get(int $tenantId, string $path, array $headers = []): array
    {
        return $this->request($tenantId, 'GET', $path, null, $headers);
    }

    /** POST request with JSON body. */
    public function post(int $tenantId, string $path, array $body, array $headers = []): array
    {
        return $this->request($tenantId, 'POST', $path, $body, $headers);
    }

    /** PATCH request with JSON body. */
    public function patch(int $tenantId, string $path, array $body, array $headers = []): array
    {
        return $this->request($tenantId, 'PATCH', $path, $body, $headers);
    }

    /** DELETE request. */
    public function delete(int $tenantId, string $path, array $headers = []): void
    {
        $this->request($tenantId, 'DELETE', $path, null, $headers, expectJson: false);
    }

    /**
     * Stream a driveItem content URL to a local path.
     *
     * Phase 1 limit: 100MB cap (HttpClientService loads the body in memory).
     * Phase 2.B.1 enhancement: replace with curl streaming for files >100MB.
     */
    public function downloadDriveItem(int $tenantId, string $siteId, string $driveId, string $itemId, string $destinationPath): void
    {
        $token = $this->acquireToken($tenantId);
        $url = $this->resolveBase($tenantId)
            . "/sites/{$siteId}/drives/{$driveId}/items/{$itemId}/content";

        $resp = HttpClientService::get(
            $url,
            ['Authorization' => 'Bearer ' . $token],
            [
                'timeout' => 60,
                'maxSize' => self::DOWNLOAD_MAX_BYTES,
                'followRedirects' => true,
            ],
        );

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException(
                "downloadDriveItem failed: HTTP {$resp['status']} for {$url}",
            );
        }

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $written = file_put_contents($destinationPath, $resp['body']);
        if ($written === false) {
            throw new \RuntimeException("Cannot write driveItem to {$destinationPath}");
        }
    }

    /**
     * Stream a driveItem to a local path using the drive-scoped URL
     * (no siteId required). Used by SharePointBrowserService — the v2 ingest
     * flow keys on drive_id only (sites are resolved upfront when drives are
     * registered in sharepoint_drive).
     */
    public function downloadDriveItemByDriveId(int $tenantId, string $driveId, string $itemId, string $destinationPath): void
    {
        $token = $this->acquireToken($tenantId);
        $url = $this->resolveBase($tenantId)
            . "/drives/{$driveId}/items/{$itemId}/content";

        $resp = HttpClientService::get(
            $url,
            ['Authorization' => 'Bearer ' . $token],
            [
                'timeout' => 60,
                'maxSize' => self::DOWNLOAD_MAX_BYTES,
                'followRedirects' => true,
            ],
        );

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException(
                "downloadDriveItemByDriveId failed: HTTP {$resp['status']} for {$url}",
            );
        }

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $written = file_put_contents($destinationPath, $resp['body']);
        if ($written === false) {
            throw new \RuntimeException("Cannot write driveItem to {$destinationPath}");
        }
    }

    /**
     * Read listItem.fields for a driveItem. Phase 2 reads _ComplianceTag from here.
     *
     * @return array<string, mixed> Decoded listItem.fields object.
     */
    public function getListItemFields(int $tenantId, string $siteId, string $driveId, string $itemId): array
    {
        // Graph: GET /sites/{site}/drives/{drive}/items/{item}/listItem?$expand=fields
        $resp = $this->get(
            $tenantId,
            "/sites/{$siteId}/drives/{$driveId}/items/{$itemId}/listItem?\$expand=fields",
        );
        return is_array($resp['fields'] ?? null) ? $resp['fields'] : [];
    }

    // ---- private ----

    /**
     * @return array Decoded JSON response (or empty array for 204/no-body).
     */
    private function request(int $tenantId, string $method, string $path, ?array $body, array $headers, bool $expectJson = true): array
    {
        $token = $this->acquireToken($tenantId);
        $url = $this->resolveBase($tenantId) . $this->ensureLeadingSlash($path);

        $finalHeaders = array_merge([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ], $headers);

        $bodyString = null;
        if ($body !== null) {
            $bodyString = json_encode($body, JSON_UNESCAPED_SLASHES);
            $finalHeaders['Content-Type'] = 'application/json';
        }

        $resp = HttpClientService::request(
            $method,
            $url,
            $bodyString,
            $finalHeaders,
            ['timeout' => 30],
        );

        if ($resp['status'] === 401) {
            // Token may have just expired between cache read and call; invalidate and retry once.
            $this->cache->invalidate($tenantId);
            $token = $this->acquireToken($tenantId);
            $finalHeaders['Authorization'] = 'Bearer ' . $token;
            $resp = HttpClientService::request($method, $url, $bodyString, $finalHeaders, ['timeout' => 30]);
        }

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException(
                "Graph {$method} {$path} failed: HTTP {$resp['status']} " . substr($resp['body'], 0, 1000),
            );
        }

        if (!$expectJson || $resp['body'] === '') {
            return [];
        }

        $decoded = json_decode($resp['body'], true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveBase(int $tenantId): string
    {
        $tenant = $this->tenants->find($tenantId);
        $base = (string) ($tenant->graph_endpoint ?? 'https://graph.microsoft.com/v1.0');
        return rtrim($base, '/');
    }

    private function ensureLeadingSlash(string $path): string
    {
        return str_starts_with($path, '/') ? $path : '/' . $path;
    }
}
