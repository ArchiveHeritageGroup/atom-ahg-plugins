<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * IIIF Authentication Service
 *
 * Implements IIIF Authentication API 1.0 for access control.
 * Supports login, clickthrough, kiosk, and external authentication profiles.
 *
 * @see https://iiif.io/api/auth/1.0/
 */
class IiifAuthService
{
    public const PROFILE_LOGIN = 'login';
    public const PROFILE_CLICKTHROUGH = 'clickthrough';
    public const PROFILE_KIOSK = 'kiosk';
    public const PROFILE_EXTERNAL = 'external';
    public const TOKEN_COOKIE = 'iiif_auth_token';

    protected string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? sfConfig::get('app_iiif_base_url', $this->detectBaseUrl());
    }

    protected function detectBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Check if access is allowed for an object.
     */
    public function checkAccess(int $objectId, ?int $userId = null): array
    {
        $authResource = $this->getAuthResourceForObject($objectId);

        if (!$authResource) {
            return ['allowed' => true, 'degraded' => false, 'service' => null];
        }

        $service = $this->getAuthService($authResource->service_id);
        if (!$service || !$service->is_active) {
            return ['allowed' => true, 'degraded' => false, 'service' => null];
        }

        $token = $this->validateCurrentToken($service->id);

        if ($token) {
            $this->logAccess($objectId, $userId, $token->id, 'view');
            return [
                'allowed' => true,
                'degraded' => false,
                'service' => $this->formatServiceDescription($service),
            ];
        }

        $this->logAccess($objectId, $userId, null, 'token_deny', [
            'reason' => 'no_valid_token',
            'service' => $service->name,
        ]);

        return [
            'allowed' => false,
            'degraded' => (bool)$authResource->degraded_access,
            'degraded_width' => $authResource->degraded_width ?? 200,
            'service' => $this->formatServiceDescription($service),
        ];
    }

    protected function getAuthResourceForObject(int $objectId): ?object
    {
        $direct = DB::table('iiif_auth_resource')
            ->where('object_id', $objectId)
            ->first();

        if ($direct) {
            return $direct;
        }

        $repoId = DB::table('information_object')
            ->where('id', $objectId)
            ->value('repository_id');

        if ($repoId) {
            $repoAuth = DB::table('iiif_auth_repository')
                ->where('repository_id', $repoId)
                ->first();

            if ($repoAuth) {
                return (object)[
                    'object_id' => $objectId,
                    'service_id' => $repoAuth->service_id,
                    'degraded_access' => $repoAuth->degraded_access,
                    'degraded_width' => $repoAuth->degraded_width,
                ];
            }
        }

        $parentIds = $this->getAncestorIds($objectId);

        if (!empty($parentIds)) {
            $inherited = DB::table('iiif_auth_resource')
                ->whereIn('object_id', $parentIds)
                ->where('apply_to_children', 1)
                ->orderByRaw('FIELD(object_id, ' . implode(',', $parentIds) . ')')
                ->first();

            if ($inherited) {
                return $inherited;
            }
        }

        return null;
    }

    protected function getAncestorIds(int $objectId): array
    {
        $ancestors = [];
        $current = $objectId;

        for ($i = 0; $i < 20; $i++) {
            $parent = DB::table('information_object')
                ->where('id', $current)
                ->value('parent_id');

            if (!$parent || $parent === 1) {
                break;
            }

            $ancestors[] = $parent;
            $current = $parent;
        }

        return $ancestors;
    }

    /**
     * Request access token.
     */
    public function requestToken(string $serviceName, ?int $userId = null, ?string $messageId = null): array
    {
        $service = DB::table('iiif_auth_service')
            ->where('name', $serviceName)
            ->where('is_active', 1)
            ->first();

        if (!$service) {
            return $this->tokenError('invalidRequest', 'Unknown service', $messageId);
        }

        switch ($service->profile) {
            case self::PROFILE_CLICKTHROUGH:
                return $this->issueToken($service, $userId, $messageId);

            case self::PROFILE_KIOSK:
                if ($this->isKioskLocation()) {
                    return $this->issueToken($service, $userId, $messageId);
                }
                return $this->tokenError('missingCredentials', 'Access from authorized location required', $messageId);

            case self::PROFILE_LOGIN:
            case self::PROFILE_EXTERNAL:
                if (!$userId) {
                    return $this->tokenError('missingCredentials', 'Authentication required', $messageId);
                }
                if (!$this->userHasAccess($userId, $service->id)) {
                    return $this->tokenError('invalidCredentials', 'Access denied', $messageId);
                }
                return $this->issueToken($service, $userId, $messageId);

            default:
                return $this->tokenError('invalidRequest', 'Unknown auth profile', $messageId);
        }
    }

    protected function issueToken(object $service, ?int $userId, ?string $messageId): array
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $ttl = $service->token_ttl ?? 3600;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

        DB::table('iiif_auth_token')->insert([
            'token_hash' => $tokenHash,
            'user_id' => $userId,
            'service_id' => $service->id,
            'session_id' => session_id() ?: null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'expires_at' => $expiresAt,
        ]);

        $tokenId = DB::getPdo()->lastInsertId();
        $this->logAccess(null, $userId, (int)$tokenId, 'token_grant', ['service' => $service->name]);
        $this->setTokenCookie($token, $ttl);

        $response = ['accessToken' => $token, 'expiresIn' => $ttl];
        if ($messageId) {
            $response['messageId'] = $messageId;
        }
        return $response;
    }

    protected function tokenError(string $error, string $description, ?string $messageId): array
    {
        $response = ['error' => $error, 'description' => $description];
        if ($messageId) {
            $response['messageId'] = $messageId;
        }
        return $response;
    }

    public function validateCurrentToken(?int $serviceId = null): ?object
    {
        $token = $_COOKIE[self::TOKEN_COOKIE] ?? null;

        if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        $query = DB::table('iiif_auth_token')
            ->where('token_hash', $tokenHash)
            ->where('is_revoked', 0)
            ->where('expires_at', '>', now());

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $tokenRecord = $query->first();

        if ($tokenRecord) {
            DB::table('iiif_auth_token')
                ->where('id', $tokenRecord->id)
                ->update(['last_used_at' => now()]);
        }

        return $tokenRecord;
    }

    protected function setTokenCookie(string $token, int $ttl): void
    {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        setcookie(self::TOKEN_COOKIE, $token, [
            'expires' => time() + $ttl,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'None',
        ]);
    }

    public function logout(): bool
    {
        $token = $_COOKIE[self::TOKEN_COOKIE] ?? null;

        if ($token) {
            $tokenHash = hash('sha256', $token);

            $tokenRecord = DB::table('iiif_auth_token')
                ->where('token_hash', $tokenHash)
                ->first();

            if ($tokenRecord) {
                DB::table('iiif_auth_token')
                    ->where('id', $tokenRecord->id)
                    ->update(['is_revoked' => 1]);

                $this->logAccess(null, $tokenRecord->user_id, $tokenRecord->id, 'logout');
            }

            setcookie(self::TOKEN_COOKIE, '', time() - 3600, '/');
        }

        return true;
    }

    public function getServiceDescription(int $serviceId): ?array
    {
        $service = $this->getAuthService($serviceId);
        return $service ? $this->formatServiceDescription($service) : null;
    }

    protected function formatServiceDescription(object $service): array
    {
        $description = [
            '@context' => 'http://iiif.io/api/auth/1/context.json',
            '@id' => $this->baseUrl . '/iiif/auth/login/' . $service->name,
            'profile' => 'http://iiif.io/api/auth/1/' . $service->profile,
            'label' => $service->label,
            'header' => $service->failure_header,
            'description' => $service->description,
            'confirmLabel' => $service->confirm_label,
            'failureHeader' => $service->failure_header,
            'failureDescription' => $service->failure_description,
            'service' => [[
                '@id' => $this->baseUrl . '/iiif/auth/token/' . $service->name,
                'profile' => 'http://iiif.io/api/auth/1/token',
            ]],
        ];

        if ($service->profile === self::PROFILE_LOGIN || $service->profile === self::PROFILE_EXTERNAL) {
            $description['service'][] = [
                '@id' => $this->baseUrl . '/iiif/auth/logout/' . $service->name,
                'profile' => 'http://iiif.io/api/auth/1/logout',
                'label' => 'Logout',
            ];
        }

        return $description;
    }

    protected function getAuthService(int $serviceId): ?object
    {
        return DB::table('iiif_auth_service')->where('id', $serviceId)->first();
    }

    public function getAuthServiceByName(string $name): ?object
    {
        return DB::table('iiif_auth_service')
            ->where('name', $name)
            ->where('is_active', 1)
            ->first();
    }

    protected function userHasAccess(int $userId, int $serviceId): bool
    {
        $user = DB::table('user')
            ->where('id', $userId)
            ->where('active', 1)
            ->first();

        return (bool)$user;
    }

    protected function isKioskLocation(): bool
    {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $kioskRanges = ['192.168.0.0/24', '10.0.0.0/8', '127.0.0.1'];

        foreach ($kioskRanges as $range) {
            if ($this->ipInRange($clientIp, $range)) {
                return true;
            }
        }

        return false;
    }

    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);

        return ($ip & $mask) === ($subnet & $mask);
    }

    protected function logAccess(?int $objectId, ?int $userId, ?int $tokenId, string $action, ?array $details = null): void
    {
        try {
            DB::table('iiif_auth_access_log')->insert([
                'object_id' => $objectId,
                'user_id' => $userId,
                'token_id' => $tokenId,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'details' => $details ? json_encode($details) : null,
            ]);
        } catch (\Exception $e) {
            error_log('IIIF Auth logging error: ' . $e->getMessage());
        }
    }

    public function setObjectAuth(int $objectId, string $serviceName, array $options = []): bool
    {
        $service = $this->getAuthServiceByName($serviceName);
        if (!$service) {
            return false;
        }

        DB::table('iiif_auth_resource')->updateOrInsert(
            ['object_id' => $objectId, 'service_id' => $service->id],
            [
                'apply_to_children' => $options['apply_to_children'] ?? true,
                'degraded_access' => $options['degraded_access'] ?? false,
                'degraded_width' => $options['degraded_width'] ?? 200,
                'notes' => $options['notes'] ?? null,
                'updated_at' => now(),
            ]
        );

        return true;
    }

    public function removeObjectAuth(int $objectId, ?string $serviceName = null): bool
    {
        $query = DB::table('iiif_auth_resource')->where('object_id', $objectId);

        if ($serviceName) {
            $service = $this->getAuthServiceByName($serviceName);
            if ($service) {
                $query->where('service_id', $service->id);
            }
        }

        return $query->delete() > 0;
    }

    public function cleanupExpiredTokens(): int
    {
        return DB::table('iiif_auth_token')
            ->where('expires_at', '<', now())
            ->orWhere(function ($q) {
                $q->where('is_revoked', 1)
                  ->where('expires_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')));
            })
            ->delete();
    }

    /**
     * Get all auth services.
     */
    public function getAllServices(): array
    {
        return DB::table('iiif_auth_service')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get protected resources.
     */
    public function getProtectedResources(int $limit = 50, int $offset = 0): array
    {
        return DB::table('iiif_auth_resource as ar')
            ->join('iiif_auth_service as s', 'ar.service_id', '=', 's.id')
            ->join('information_object as io', 'ar.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->select([
                'ar.*',
                's.name as service_name',
                's.profile as service_profile',
                'ioi.title',
                'slug.slug',
            ])
            ->orderBy('ar.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }
}
