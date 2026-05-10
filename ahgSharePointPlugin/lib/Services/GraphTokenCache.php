<?php

namespace AtomExtensions\SharePoint\Services;

/**
 * GraphTokenCache — in-memory + ahg_settings-backed cache for access tokens.
 *
 * Tokens are short-lived (~60 min); cache miss triggers re-acquisition via
 * client-credentials flow. Refresh tokens are not part of client-credentials.
 *
 * Storage: ahg_settings group=sharepoint_runtime, key=access_token_{tenant_id}.
 * Cleartext at rest is acceptable here — tokens expire fast, and the
 * client_secret that mints them is encrypted.
 *
 * @phase 1
 */
class GraphTokenCache
{
    /** @var array<int, array{token:string, expires_at:int}> */
    private array $memory = [];

    public function get(int $tenantId): ?string
    {
        // TODO: 1) check memory; 2) check ahg_settings; 3) return null if absent or expired.
        return null;
    }

    public function put(int $tenantId, string $token, int $expiresInSeconds): void
    {
        // TODO: write to memory + ahg_settings. Apply 60s safety margin on expiry.
    }

    public function invalidate(int $tenantId): void
    {
        // TODO
    }
}
