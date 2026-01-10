<?php

namespace AhgAPIPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

class ApiKeyService
{
    public function authenticate(): ?array
    {
        $key = $this->getApiKeyFromHeader();
        if (!$key) {
            return null;
        }

        // Hash the provided key to compare with stored hash
        $hashedKey = hash('sha256', $key);

        $apiKey = DB::table('ahg_api_key')
            ->where('api_key', $hashedKey)
            ->where('is_active', 1)
            ->first();

        if (!$apiKey) {
            return null;
        }

        // Check expiry
        if ($apiKey->expires_at && $apiKey->expires_at < date('Y-m-d H:i:s')) {
            return null;
        }

        // Update last used
        DB::table('ahg_api_key')
            ->where('id', $apiKey->id)
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        return [
            'type' => 'ahg_api_key',
            'id' => $apiKey->id,
            'user_id' => $apiKey->user_id,
            'scopes' => json_decode($apiKey->scopes ?? '[]', true),
            'rate_limit' => $apiKey->rate_limit
        ];
    }

    protected function getApiKeyFromHeader(): ?string
    {
        // Check various header formats
        $headers = ['HTTP_X_API_KEY', 'HTTP_X_REST_API_KEY', 'HTTP_REST_API_KEY'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }

        // Check Authorization: Bearer header
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/^Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function checkRateLimit(int $apiKeyId, int $limit): bool
    {
        $windowStart = date('Y-m-d H:00:00');

        $current = DB::table('ahg_api_rate_limit')
            ->where('api_key_id', $apiKeyId)
            ->where('window_start', $windowStart)
            ->first();

        if (!$current) {
            DB::table('ahg_api_rate_limit')->insert([
                'api_key_id' => $apiKeyId,
                'window_start' => $windowStart,
                'request_count' => 1
            ]);
            return true;
        }

        if ($current->request_count >= $limit) {
            return false;
        }

        DB::table('ahg_api_rate_limit')
            ->where('id', $current->id)
            ->increment('request_count');

        return true;
    }

    public function logRequest(array $data): void
    {
        DB::table('ahg_api_log')->insert([
            'api_key_id' => $data['api_key_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'method' => $data['method'],
            'endpoint' => $data['endpoint'],
            'status_code' => $data['status_code'],
            'duration_ms' => $data['duration_ms'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function createApiKey(int $userId, string $name, array $scopes = ['read'], int $rateLimit = 1000): array
    {
        $rawKey = bin2hex(random_bytes(32));
        $hashedKey = hash('sha256', $rawKey);
        $now = date('Y-m-d H:i:s');

        $id = DB::table('ahg_api_key')->insertGetId([
            'user_id' => $userId,
            'name' => $name,
            'api_key' => $hashedKey,
            'api_key_prefix' => substr($rawKey, 0, 8),
            'scopes' => json_encode($scopes),
            'rate_limit' => $rateLimit,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // Return the raw key - it will only be shown once
        return [
            'id' => $id,
            'api_key' => $rawKey,
            'name' => $name,
            'scopes' => $scopes
        ];
    }

    public function deleteApiKey(int $keyId): bool
    {
        return DB::table('ahg_api_key')->where('id', $keyId)->delete() > 0;
    }

    public function toggleApiKey(int $keyId): bool
    {
        $key = DB::table('ahg_api_key')->where('id', $keyId)->first();
        if (!$key) {
            return false;
        }

        DB::table('ahg_api_key')
            ->where('id', $keyId)
            ->update(['is_active' => $key->is_active ? 0 : 1]);

        return true;
    }
}
