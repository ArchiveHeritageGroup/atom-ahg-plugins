<?php

namespace AtomExtensions\SharePoint\Services;

/**
 * GraphClientService — hand-rolled Microsoft Graph wrapper.
 *
 * Decision (locked 2026-05-10): no microsoft/microsoft-graph SDK.
 * Reuses framework HttpClientService for SSRF protection, timeouts, blocked hosts.
 *
 * Endpoints used (Phase 1):
 *   POST  https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token
 *   GET   /sites
 *   GET   /sites/{site}/drives
 *   GET   /sites/{site}/drives/{drive}/root/delta
 *   GET   /sites/{site}/drives/{drive}/items/{item}
 *
 * Phase 2 adds: POST/PATCH/DELETE /subscriptions, listItem fields.
 * Phase 3 adds: POST /search/query.
 *
 * @phase 1
 */
class GraphClientService
{
    /**
     * Acquire (or return cached) access token for a tenant.
     */
    public function acquireToken(int $tenantId): string
    {
        // TODO (Phase 1):
        //   1. Look up tenant row + decrypted client_secret via TenantRepository.
        //   2. Check GraphTokenCache; return if non-expired.
        //   3. POST to /oauth2/v2.0/token with grant_type=client_credentials, scope=...default.
        //   4. Persist token + expiry in GraphTokenCache.
        //   5. Return access_token string.
        throw new \RuntimeException('GraphClientService::acquireToken not implemented yet');
    }

    /**
     * GET request against the Graph endpoint with bearer auth.
     *
     * @param int                  $tenantId
     * @param string               $path     e.g. "/sites?search=archive"
     * @param array<string,string> $headers
     * @return array Decoded JSON response
     */
    public function get(int $tenantId, string $path, array $headers = []): array
    {
        throw new \RuntimeException('GraphClientService::get not implemented yet');
    }

    /**
     * POST request against the Graph endpoint with bearer auth.
     *
     * @param int                  $tenantId
     * @param string               $path
     * @param array                $body
     * @param array<string,string> $headers
     * @return array
     */
    public function post(int $tenantId, string $path, array $body, array $headers = []): array
    {
        throw new \RuntimeException('GraphClientService::post not implemented yet');
    }

    /**
     * Stream a driveItem content URL to a local path.
     */
    public function downloadDriveItem(int $tenantId, string $siteId, string $driveId, string $itemId, string $destinationPath): void
    {
        throw new \RuntimeException('GraphClientService::downloadDriveItem not implemented yet');
    }
}
