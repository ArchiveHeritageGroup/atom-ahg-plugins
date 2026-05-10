<?php

namespace AtomExtensions\SharePoint\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GraphTokenValidatorService — validates inbound AAD bearer tokens.
 *
 * Used by Phase 2.B push endpoints AND Phase 3 connector feed. Two
 * dependencies:
 *   1. firebase/php-jwt for signature/exp/iss/aud verification
 *   2. Cached AAD JWKS (kept in ahg_cache or ahg_settings)
 *
 * Returns an array of decoded claims; throws on invalid token.
 *
 * @phase 2.B / 3
 */
class GraphTokenValidatorService
{
    private const JWKS_TTL_SECONDS = 3600;

    public function __construct(
        private SharePointTenantRepository $tenants,
    ) {
    }

    /**
     * Validate an Authorization: Bearer header value (without the "Bearer " prefix).
     *
     * @return array{oid:string, upn?:?string, email?:?string, name?:?string, tid:string, scp?:?string, sub:string}
     * @throws \RuntimeException on validation failure
     */
    public function validate(string $bearerToken, int $expectedTenantId): array
    {
        $tenant = $this->tenants->find($expectedTenantId);
        if ($tenant === null) {
            throw new \RuntimeException("Tenant {$expectedTenantId} not found");
        }

        $jwks = $this->fetchJwks((string) $tenant->tenant_id);
        $keys = JWK::parseKeySet($jwks);

        try {
            $decoded = (array) JWT::decode($bearerToken, $keys);
        } catch (\Throwable $e) {
            throw new \RuntimeException('JWT decode failed: ' . $e->getMessage(), 0, $e);
        }

        // Strict claim checks
        $tid = (string) ($decoded['tid'] ?? '');
        if ($tid !== (string) $tenant->tenant_id) {
            throw new \RuntimeException('JWT tenant id mismatch');
        }

        $expectedAudience = $this->expectedAudience($tenant);
        $aud = $decoded['aud'] ?? '';
        if (!$this->audienceMatches($aud, $expectedAudience)) {
            throw new \RuntimeException('JWT audience mismatch (expected ' . $expectedAudience . ', got ' . (is_array($aud) ? implode(',', $aud) : (string) $aud) . ')');
        }

        $expectedIssuer = "https://login.microsoftonline.com/{$tid}/v2.0";
        if (!isset($decoded['iss']) || (string) $decoded['iss'] !== $expectedIssuer) {
            throw new \RuntimeException('JWT issuer mismatch (expected ' . $expectedIssuer . ')');
        }

        if (empty($decoded['oid'])) {
            throw new \RuntimeException('JWT missing oid claim — cannot identify user');
        }

        return [
            'oid' => (string) $decoded['oid'],
            'upn' => isset($decoded['upn']) ? (string) $decoded['upn'] : null,
            'email' => isset($decoded['email']) ? (string) $decoded['email'] : null,
            'name' => isset($decoded['name']) ? (string) $decoded['name'] : null,
            'tid' => $tid,
            'scp' => isset($decoded['scp']) ? (string) $decoded['scp'] : null,
            'sub' => (string) ($decoded['sub'] ?? ''),
            // Original token preserved so callers can re-use it for OBO flow.
            '_raw' => $bearerToken,
        ];
    }

    private function expectedAudience(\stdClass $tenant): string
    {
        $row = DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint')
            ->where('setting_key', 'expected_jwt_audience')
            ->first();
        if ($row !== null && !empty($row->setting_value)) {
            return (string) $row->setting_value;
        }
        // Sensible default: the AtoM API app id URI.
        return "api://{$tenant->client_id}";
    }

    private function audienceMatches(mixed $audClaim, string $expected): bool
    {
        if (is_array($audClaim)) {
            return in_array($expected, $audClaim, true);
        }
        return (string) $audClaim === $expected;
    }

    /**
     * Fetch and cache the AAD JWKS for a tenant.
     */
    private function fetchJwks(string $tenantId): array
    {
        $cacheKey = "sharepoint_jwks_{$tenantId}";
        $row = DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint_runtime')
            ->where('setting_key', $cacheKey)
            ->first();

        if ($row !== null && !empty($row->setting_value)) {
            $cached = json_decode($row->setting_value, true);
            if (is_array($cached) && !empty($cached['fetched_at'])) {
                if ((time() - (int) $cached['fetched_at']) < self::JWKS_TTL_SECONDS) {
                    return $cached['jwks'];
                }
            }
        }

        // TODO: replace with HttpClientService (no SSRF protection on direct file_get_contents).
        $url = "https://login.microsoftonline.com/{$tenantId}/discovery/v2.0/keys";
        $body = @file_get_contents($url);
        if ($body === false) {
            throw new \RuntimeException('Cannot fetch AAD JWKS at ' . $url);
        }
        $jwks = json_decode($body, true);
        if (!is_array($jwks) || empty($jwks['keys'])) {
            throw new \RuntimeException('AAD JWKS body malformed');
        }

        DB::table('ahg_settings')->updateOrInsert(
            ['setting_group' => 'sharepoint_runtime', 'setting_key' => $cacheKey],
            ['setting_value' => json_encode(['fetched_at' => time(), 'jwks' => $jwks])],
        );

        return $jwks;
    }
}
