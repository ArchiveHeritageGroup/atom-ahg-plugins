<?php

namespace AtomExtensions\SharePoint\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * GraphTokenCache — in-memory + ahg_settings-backed cache for access tokens.
 *
 * Tokens are short-lived (~60 min); cache miss triggers re-acquisition via
 * client-credentials flow. Refresh tokens are not part of client-credentials.
 *
 * Storage: ahg_settings group=sharepoint_runtime, key=access_token_{tenant_id}.
 * Cleartext at rest is acceptable — tokens expire fast, the long-lived
 * client_secret that mints them IS encrypted.
 *
 * @phase 1
 */
class GraphTokenCache
{
    private const SAFETY_MARGIN_SECONDS = 60;

    /** @var array<int, array{token:string, expires_at:int}> */
    private array $memory = [];

    public function get(int $tenantId): ?string
    {
        $now = time();

        // Memory tier
        if (isset($this->memory[$tenantId])) {
            $entry = $this->memory[$tenantId];
            if ($entry['expires_at'] - self::SAFETY_MARGIN_SECONDS > $now) {
                return $entry['token'];
            }
            unset($this->memory[$tenantId]);
        }

        // DB tier
        $row = DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint_runtime')
            ->where('setting_key', "access_token_{$tenantId}")
            ->first();
        if ($row === null || empty($row->setting_value)) {
            return null;
        }
        $payload = json_decode($row->setting_value, true);
        if (!is_array($payload) || empty($payload['token']) || empty($payload['expires_at'])) {
            return null;
        }
        if ((int) $payload['expires_at'] - self::SAFETY_MARGIN_SECONDS <= $now) {
            return null;
        }
        $this->memory[$tenantId] = [
            'token' => (string) $payload['token'],
            'expires_at' => (int) $payload['expires_at'],
        ];
        return (string) $payload['token'];
    }

    public function put(int $tenantId, string $token, int $expiresInSeconds): void
    {
        $expiresAt = time() + max(0, $expiresInSeconds);
        $this->memory[$tenantId] = ['token' => $token, 'expires_at' => $expiresAt];

        DB::table('ahg_settings')->updateOrInsert(
            ['setting_group' => 'sharepoint_runtime', 'setting_key' => "access_token_{$tenantId}"],
            [
                'setting_value' => json_encode(['token' => $token, 'expires_at' => $expiresAt]),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        );
    }

    public function invalidate(int $tenantId): void
    {
        unset($this->memory[$tenantId]);
        DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint_runtime')
            ->where('setting_key', "access_token_{$tenantId}")
            ->delete();
    }
}
