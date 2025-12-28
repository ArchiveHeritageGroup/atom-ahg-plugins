<?php

namespace AtoM\Framework\Plugins\AuditTrail\Services;

use Illuminate\Database\Capsule\Manager as DB;
namespace AtoM\Framework\Plugins\AuditTrail\Services;

use AtoM\Framework\Plugins\AuditTrail\Models\AuditAccess;
use AtoM\Framework\Plugins\AuditTrail\Models\AuditAuthentication;
use AtoM\Framework\Plugins\AuditTrail\Models\AuditLog;
use AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAccessRepository;
use AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAuthenticationRepository;
use AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository;
use AtoM\Framework\Plugins\AuditTrail\Repositories\AuditSettingsRepository;

class AuditService
{
    protected AuditLogRepository $auditRepo;
    protected AuditAuthenticationRepository $authRepo;
    protected AuditAccessRepository $accessRepo;
    protected AuditSettingsRepository $settings;
    protected ?array $currentUser = null;
    protected ?string $currentIp = null;
    protected ?string $currentUserAgent = null;
    protected ?string $currentSessionId = null;
    protected array $sensitiveFields = ['password', 'password_hash', 'api_key', 'secret', 'token'];

    public function __construct()
    {
        $this->auditRepo = new AuditLogRepository();
        $this->authRepo = new AuditAuthenticationRepository();
        $this->accessRepo = new AuditAccessRepository();
        $this->settings = new AuditSettingsRepository();
    }

    public function initializeContext(): void
    {
        try {
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $context = \sfContext::getInstance();
                $user = $context->getUser();
                if ($user && $user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                    if ($userId) {
                        $qubitUser = DB::table("user as u")
                        ->join("actor as a", "u.id", "=", "a.id")
                        ->leftJoin("actor_i18n as ai", function($j) { $j->on("a.id", "=", "ai.id")->where("ai.culture", "=", "en"); })
                        ->where("u.id", $userId)
                        ->select("u.*", "ai.authorized_form_of_name as name")
                        ->first();
                        if ($qubitUser) {
                            $this->currentUser = ['id' => $qubitUser->id, 'username' => $qubitUser->username, 'email' => $qubitUser->email];
                        }
                    }
                }
                $request = $context->getRequest();
                if ($request) {
                    $this->currentIp = $this->getClientIp($request);
                    $this->currentUserAgent = $request->getHttpHeader('User-Agent');
                    $this->currentSessionId = session_id() ?: null;
                }
            }
        } catch (\Exception $e) {}
    }

    public function isEnabled(): bool
    {
        return $this->settings->isEnabled('audit_enabled');
    }

    public function log(string $action, string $entityType, ?int $entityId = null, array $options = []): ?AuditLog
    {
        if (!$this->isEnabled()) return null;
        if ($action === AuditLog::ACTION_VIEW && !$this->settings->isEnabled('audit_views')) return null;

        $this->initializeContext();

        $data = [
            'user_id' => $this->currentUser['id'] ?? null,
            'username' => $this->currentUser['username'] ?? null,
            'user_email' => $this->currentUser['email'] ?? null,
            'ip_address' => $this->maybeAnonymizeIp($this->currentIp),
            'user_agent' => $this->currentUserAgent,
            'session_id' => $this->currentSessionId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_slug' => $options['slug'] ?? null,
            'entity_title' => $options['title'] ?? null,
            'module' => $options['module'] ?? null,
            'action_name' => $options['action_name'] ?? null,
            'request_method' => $options['request_method'] ?? $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => $options['request_uri'] ?? $_SERVER['REQUEST_URI'] ?? null,
            'old_values' => $this->maskSensitiveData($options['old_values'] ?? null),
            'new_values' => $this->maskSensitiveData($options['new_values'] ?? null),
            'changed_fields' => $options['changed_fields'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'security_classification' => $options['security_classification'] ?? null,
            'status' => $options['status'] ?? AuditLog::STATUS_SUCCESS,
            'error_message' => $options['error_message'] ?? null,
        ];

        try {
            return $this->auditRepo->create($data);
        } catch (\Exception $e) {
            error_log('AuditService error: ' . $e->getMessage());
            return null;
        }
    }

    public function logCreate(object $entity, array $newValues = [], array $options = []): ?AuditLog
    {
        return $this->log(AuditLog::ACTION_CREATE, get_class($entity), $entity->id ?? null, array_merge($options, [
            'slug' => $entity->slug ?? null,
            'title' => $this->getEntityTitle($entity),
            'new_values' => $newValues ?: $this->extractEntityValues($entity),
        ]));
    }

    public function logUpdate(object $entity, array $oldValues = [], array $newValues = [], array $options = []): ?AuditLog
    {
        $changedFields = array_keys(array_diff_assoc($newValues ?: [], $oldValues ?: []));
        return $this->log(AuditLog::ACTION_UPDATE, get_class($entity), $entity->id ?? null, array_merge($options, [
            'slug' => $entity->slug ?? null,
            'title' => $this->getEntityTitle($entity),
            'old_values' => $oldValues,
            'new_values' => $newValues ?: $this->extractEntityValues($entity),
            'changed_fields' => $changedFields,
        ]));
    }

    public function logDelete(object $entity, array $options = []): ?AuditLog
    {
        return $this->log(AuditLog::ACTION_DELETE, get_class($entity), $entity->id ?? null, array_merge($options, [
            'slug' => $entity->slug ?? null,
            'title' => $this->getEntityTitle($entity),
            'old_values' => $this->extractEntityValues($entity),
        ]));
    }

    public function logAuth(string $eventType, ?int $userId = null, ?string $username = null, array $options = []): ?AuditAuthentication
    {
        if (!$this->settings->isEnabled('audit_authentication')) return null;
        $this->initializeContext();
        
        $data = [
            'event_type' => $eventType,
            'user_id' => $userId ?? $this->currentUser['id'] ?? null,
            'username' => $username ?? $this->currentUser['username'] ?? null,
            'ip_address' => $this->maybeAnonymizeIp($this->currentIp),
            'user_agent' => $this->currentUserAgent,
            'session_id' => $this->currentSessionId,
            'status' => $options['status'] ?? 'success',
            'failure_reason' => $options['failure_reason'] ?? null,
            'failed_attempts' => $options['failed_attempts'] ?? 0,
            'metadata' => $options['metadata'] ?? null,
        ];

        try {
            return $this->authRepo->create($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function logLogin(int $userId, string $username): ?AuditAuthentication
    {
        return $this->logAuth(AuditAuthentication::EVENT_LOGIN, $userId, $username);
    }

    public function logLogout(): ?AuditAuthentication
    {
        return $this->logAuth(AuditAuthentication::EVENT_LOGOUT);
    }

    public function logFailedLogin(string $username, string $reason = 'Invalid credentials'): ?AuditAuthentication
    {
        $failedAttempts = $this->authRepo->getFailedAttempts($this->currentIp, $username, now()->subHour());
        return $this->logAuth(AuditAuthentication::EVENT_FAILED_LOGIN, null, $username, [
            'status' => 'failure',
            'failure_reason' => $reason,
            'failed_attempts' => $failedAttempts + 1,
        ]);
    }

    public function logAccess(string $accessType, object $entity, array $options = []): ?AuditAccess
    {
        if (!$this->settings->isEnabled('audit_sensitive_access')) return null;
        $this->initializeContext();

        $data = [
            'user_id' => $this->currentUser['id'] ?? null,
            'username' => $this->currentUser['username'] ?? null,
            'ip_address' => $this->maybeAnonymizeIp($this->currentIp),
            'access_type' => $accessType,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id ?? null,
            'entity_slug' => $entity->slug ?? null,
            'entity_title' => $this->getEntityTitle($entity),
            'security_classification' => $options['security_classification'] ?? null,
            'security_clearance_level' => $options['clearance_level'] ?? null,
            'clearance_verified' => $options['clearance_verified'] ?? false,
            'file_path' => $options['file_path'] ?? null,
            'file_name' => $options['file_name'] ?? null,
            'file_mime_type' => $options['file_mime_type'] ?? null,
            'file_size' => $options['file_size'] ?? null,
            'status' => $options['status'] ?? 'success',
            'denial_reason' => $options['denial_reason'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ];

        try {
            return $this->accessRepo->create($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function logDownload(object $entity, string $filePath, string $fileName, ?string $mimeType = null, ?int $fileSize = null): ?AuditAccess
    {
        if (!$this->settings->isEnabled('audit_downloads')) return null;
        return $this->logAccess(AuditAccess::ACCESS_DOWNLOAD, $entity, [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);
    }

    public function logAccessDenied(object $entity, string $reason, array $options = []): ?AuditAccess
    {
        return $this->logAccess($options['access_type'] ?? AuditAccess::ACCESS_VIEW, $entity, array_merge($options, [
            'status' => 'denied',
            'denial_reason' => $reason,
        ]));
    }

    protected function getEntityTitle(object $entity): ?string
    {
        if (method_exists($entity, 'getTitle')) return $entity->getTitle();
        return $entity->title ?? $entity->name ?? $entity->slug ?? null;
    }

    protected function extractEntityValues(object $entity): array
    {
        if (method_exists($entity, 'toArray')) return $this->maskSensitiveData($entity->toArray());
        return [];
    }

    protected function maskSensitiveData(?array $data): ?array
    {
        if (!$data || !$this->settings->isEnabled('audit_mask_sensitive')) return $data;
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }
        return $data;
    }

    protected function maybeAnonymizeIp(?string $ip): ?string
    {
        if (!$ip || !$this->settings->isEnabled('audit_ip_anonymize')) return $ip;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        return $ip;
    }

    protected function getClientIp($request): ?string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            $ip = $_SERVER[$header] ?? null;
            if ($ip) return trim(explode(',', $ip)[0]);
        }
        return null;
    }

    public function getAuditRepository(): AuditLogRepository { return $this->auditRepo; }
    public function getAuthRepository(): AuditAuthenticationRepository { return $this->authRepo; }
    public function getAccessRepository(): AuditAccessRepository { return $this->accessRepo; }
    public function getSettingsRepository(): AuditSettingsRepository { return $this->settings; }
}
