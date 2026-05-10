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
     * On-Behalf-Of flow — exchange a user's bearer token for a Graph token
     * impersonating that user. Used by the manual-push file fetch path so
     * AtoM never bypasses the user's SharePoint permissions.
     *
     * Requires the AAD app to have the requested delegated permission and
     * the user (or admin) to have consented.
     *
     * @param int    $tenantId
     * @param string $userToken      The bearer token AtoM received in Authorization header.
     * @param string $graphScope     e.g. "https://graph.microsoft.com/Files.Read.All"
     * @return string Graph access token impersonating the user.
     */
    public function acquireOboToken(int $tenantId, string $userToken, string $graphScope): string
    {
        // TODO (Phase 2.B):
        //   1. POST to https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token
        //      grant_type   = urn:ietf:params:oauth:grant-type:jwt-bearer
        //      client_id    = <AtoM API app id>
        //      client_secret= decrypted from tenant
        //      assertion    = $userToken
        //      requested_token_use = on_behalf_of
        //      scope        = $graphScope
        //   2. Cache result keyed on (tenantId, oid claim, scope, hash($userToken)) — short TTL.
        //   3. Return the returned access_token.
        throw new \RuntimeException('GraphClientService::acquireOboToken not implemented yet');
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
     * PATCH request against the Graph endpoint. Used for subscription renewal.
     */
    public function patch(int $tenantId, string $path, array $body, array $headers = []): array
    {
        throw new \RuntimeException('GraphClientService::patch not implemented yet');
    }

    /**
     * DELETE request against the Graph endpoint. Used for subscription teardown.
     */
    public function delete(int $tenantId, string $path, array $headers = []): void
    {
        throw new \RuntimeException('GraphClientService::delete not implemented yet');
    }

    /**
     * Stream a driveItem content URL to a local path.
     */
    public function downloadDriveItem(int $tenantId, string $siteId, string $driveId, string $itemId, string $destinationPath): void
    {
        throw new \RuntimeException('GraphClientService::downloadDriveItem not implemented yet');
    }

    /**
     * Read listItem.fields for a driveItem. Phase 2 reads _ComplianceTag from here.
     *
     * @return array<string, mixed> Decoded listItem.fields object.
     */
    public function getListItemFields(int $tenantId, string $siteId, string $driveId, string $itemId): array
    {
        throw new \RuntimeException('GraphClientService::getListItemFields not implemented yet');
    }
}
