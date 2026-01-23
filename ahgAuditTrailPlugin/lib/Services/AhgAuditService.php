<?php

namespace AhgAuditTrail\Services;

use AhgCore\Contracts\AuditServiceInterface;
use AhgCore\Core\AhgDb;

/**
 * AhgAuditService - Centralized Audit Logging Service
 *
 * This is the primary service for audit logging. All plugins should use this
 * instead of writing directly to ahg_audit_log.
 *
 * Usage:
 *   use AhgAuditTrail\Services\AhgAuditService;
 *
 *   // Static usage (recommended)
 *   AhgAuditService::log('create', 'QubitInformationObject', $id, [
 *       'title' => 'New Record',
 *       'module' => 'informationobject',
 *   ]);
 *
 *   // Or via AhgCore
 *   AhgCore::audit()->log('create', 'QubitInformationObject', $id);
 */
class AhgAuditService implements AuditServiceInterface
{
    private static ?self $instance = null;
    private static bool $initialized = false;
    private ?array $currentUser = null;
    private ?string $currentIp = null;
    private ?string $currentUserAgent = null;
    private ?string $currentSessionId = null;

    private const SENSITIVE_FIELDS = ['password', 'password_hash', 'api_key', 'secret', 'token', 'salt'];

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize and register with AhgCore
     */
    public static function register(): void
    {
        if (self::$initialized) {
            return;
        }

        // Register with AhgCore if available
        if (class_exists('\AhgCore\AhgCore')) {
            \AhgCore\AhgCore::registerService(
                AuditServiceInterface::class,
                self::getInstance()
            );
        }

        self::$initialized = true;
    }

    /**
     * Check if audit logging is enabled
     */
    public function isEnabled(): bool
    {
        try {
            $value = AhgDb::table('ahg_audit_settings')
                ->where('setting_key', 'audit_enabled')
                ->value('setting_value');
            return $value === '1';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a specific audit type is enabled
     */
    public function isTypeEnabled(string $type): bool
    {
        try {
            $value = AhgDb::table('ahg_audit_settings')
                ->where('setting_key', 'audit_' . $type)
                ->value('setting_value');
            return $value === '1';
        } catch (\Exception $e) {
            return true; // Default to enabled
        }
    }

    /**
     * Log an action - static convenience method
     */
    public static function logAction(
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $options = []
    ): mixed {
        return self::getInstance()->log($action, $entityType, $entityId, $options);
    }

    /**
     * Log an action
     */
    public function log(string $action, string $entityType, ?int $entityId = null, array $options = []): mixed
    {
        if (!$this->isEnabled()) {
            return null;
        }

        // Check type-specific settings
        $actionTypeMap = [
            self::ACTION_CREATE => 'creates',
            self::ACTION_UPDATE => 'updates',
            self::ACTION_DELETE => 'deletes',
            self::ACTION_VIEW => 'views',
            self::ACTION_DOWNLOAD => 'downloads',
            self::ACTION_EXPORT => 'exports',
            self::ACTION_IMPORT => 'imports',
            self::ACTION_LOGIN => 'authentication',
            self::ACTION_LOGOUT => 'authentication',
        ];

        $settingKey = $actionTypeMap[$action] ?? null;
        if ($settingKey && !$this->isTypeEnabled($settingKey)) {
            return null;
        }

        $this->initializeContext();

        $data = [
            'uuid' => $this->generateUuid(),
            'user_id' => $options['user_id'] ?? $this->currentUser['id'] ?? null,
            'username' => $options['username'] ?? $this->currentUser['username'] ?? 'anonymous',
            'user_email' => $options['user_email'] ?? $this->currentUser['email'] ?? null,
            'ip_address' => $this->maybeAnonymizeIp($this->currentIp),
            'user_agent' => substr($this->currentUserAgent ?? '', 0, 500),
            'session_id' => $this->currentSessionId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_slug' => $options['slug'] ?? null,
            'entity_title' => isset($options['title']) ? substr($options['title'], 0, 255) : null,
            'module' => $options['module'] ?? null,
            'action_name' => $options['action_name'] ?? null,
            'request_method' => $options['request_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? null),
            'request_uri' => isset($options['request_uri'])
                ? substr($options['request_uri'], 0, 2000)
                : (isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 2000) : null),
            'old_values' => $this->encodeValues($options['old_values'] ?? null),
            'new_values' => $this->encodeValues($options['new_values'] ?? null),
            'changed_fields' => isset($options['changed_fields']) ? json_encode($options['changed_fields']) : null,
            'metadata' => isset($options['metadata']) ? json_encode($options['metadata']) : null,
            'security_classification' => $options['security_classification'] ?? null,
            'status' => $options['status'] ?? self::STATUS_SUCCESS,
            'error_message' => $options['error_message'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $id = AhgDb::table('ahg_audit_log')->insertGetId($data);
            return $id;
        } catch (\Exception $e) {
            error_log('AhgAuditService error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log a create action
     */
    public function logCreate(object $entity, array $newValues = [], array $options = []): mixed
    {
        return $this->log(
            self::ACTION_CREATE,
            $this->getEntityType($entity),
            $entity->id ?? null,
            array_merge($options, [
                'slug' => $entity->slug ?? null,
                'title' => $this->getEntityTitle($entity),
                'new_values' => $newValues ?: $this->extractEntityValues($entity),
            ])
        );
    }

    /**
     * Log an update action
     */
    public function logUpdate(object $entity, array $oldValues = [], array $newValues = [], array $options = []): mixed
    {
        $changedFields = array_keys(array_diff_assoc($newValues ?: [], $oldValues ?: []));

        return $this->log(
            self::ACTION_UPDATE,
            $this->getEntityType($entity),
            $entity->id ?? null,
            array_merge($options, [
                'slug' => $entity->slug ?? null,
                'title' => $this->getEntityTitle($entity),
                'old_values' => $oldValues,
                'new_values' => $newValues ?: $this->extractEntityValues($entity),
                'changed_fields' => $changedFields,
            ])
        );
    }

    /**
     * Log a delete action
     */
    public function logDelete(object $entity, array $options = []): mixed
    {
        return $this->log(
            self::ACTION_DELETE,
            $this->getEntityType($entity),
            $entity->id ?? null,
            array_merge($options, [
                'slug' => $entity->slug ?? null,
                'title' => $this->getEntityTitle($entity),
                'old_values' => $this->extractEntityValues($entity),
            ])
        );
    }

    /**
     * Log a file download
     */
    public function logDownload(
        object $entity,
        string $filePath,
        string $fileName,
        ?string $mimeType = null,
        ?int $fileSize = null
    ): mixed {
        if (!$this->isTypeEnabled('downloads')) {
            return null;
        }

        return $this->log(
            self::ACTION_DOWNLOAD,
            $this->getEntityType($entity),
            $entity->id ?? null,
            [
                'title' => $this->getEntityTitle($entity),
                'metadata' => [
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                ],
            ]
        );
    }

    /**
     * Log an access denied event
     */
    public function logAccessDenied(object $entity, string $reason, array $options = []): mixed
    {
        return $this->log(
            $options['action'] ?? self::ACTION_VIEW,
            $this->getEntityType($entity),
            $entity->id ?? null,
            array_merge($options, [
                'title' => $this->getEntityTitle($entity),
                'status' => self::STATUS_DENIED,
                'error_message' => $reason,
            ])
        );
    }

    /**
     * Log authentication event
     */
    public function logAuth(string $eventType, ?int $userId = null, ?string $username = null, array $options = []): mixed
    {
        if (!$this->isTypeEnabled('authentication')) {
            return null;
        }

        $this->initializeContext();

        $data = [
            'uuid' => $this->generateUuid(),
            'event_type' => $eventType,
            'user_id' => $userId ?? $this->currentUser['id'] ?? null,
            'username' => $username ?? $this->currentUser['username'] ?? null,
            'ip_address' => $this->maybeAnonymizeIp($this->currentIp),
            'user_agent' => substr($this->currentUserAgent ?? '', 0, 500),
            'session_id' => $this->currentSessionId,
            'status' => $options['status'] ?? 'success',
            'failure_reason' => $options['failure_reason'] ?? null,
            'failed_attempts' => $options['failed_attempts'] ?? 0,
            'metadata' => isset($options['metadata']) ? json_encode($options['metadata']) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            return AhgDb::table('ahg_audit_authentication')->insertGetId($data);
        } catch (\Exception $e) {
            error_log('AhgAuditService auth error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initialize context from current request
     */
    protected function initializeContext(): void
    {
        if ($this->currentUser !== null) {
            return;
        }

        try {
            if (class_exists('sfContext', false) && \sfContext::hasInstance()) {
                $context = \sfContext::getInstance();
                $user = $context->getUser();

                if ($user && $user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                    if ($userId) {
                        $userRecord = AhgDb::table('user')
                            ->where('id', $userId)
                            ->first();
                        if ($userRecord) {
                            $this->currentUser = [
                                'id' => $userRecord->id,
                                'username' => $userRecord->username,
                                'email' => $userRecord->email ?? null,
                            ];
                        }
                    }
                }

                $request = $context->getRequest();
                if ($request) {
                    $this->currentIp = $this->getClientIp();
                    $this->currentUserAgent = $request->getHttpHeader('User-Agent');
                    $this->currentSessionId = session_id() ?: null;
                }
            }
        } catch (\Exception $e) {
            // Ignore initialization errors
        }

        // Fallback defaults
        $this->currentUser = $this->currentUser ?? ['id' => null, 'username' => 'anonymous', 'email' => null];
        $this->currentIp = $this->currentIp ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $this->currentUserAgent = $this->currentUserAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
        $this->currentSessionId = $this->currentSessionId ?? (session_id() ?: null);
    }

    /**
     * Get client IP from various headers
     */
    protected function getClientIp(): ?string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            $ip = $_SERVER[$header] ?? null;
            if ($ip) {
                return trim(explode(',', $ip)[0]);
            }
        }
        return null;
    }

    /**
     * Maybe anonymize IP address
     */
    protected function maybeAnonymizeIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        try {
            $anonymize = AhgDb::table('ahg_audit_settings')
                ->where('setting_key', 'audit_ip_anonymize')
                ->value('setting_value');

            if ($anonymize === '1') {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return preg_replace('/\.\d+$/', '.0', $ip);
                } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    return preg_replace('/:[^:]+$/', ':0', $ip);
                }
            }
        } catch (\Exception $e) {
            // Keep original
        }

        return $ip;
    }

    /**
     * Get entity type string
     */
    protected function getEntityType(object $entity): string
    {
        return get_class($entity);
    }

    /**
     * Get entity title
     */
    protected function getEntityTitle(object $entity): ?string
    {
        if (method_exists($entity, 'getTitle')) {
            return $entity->getTitle();
        }
        return $entity->title ?? $entity->name ?? $entity->slug ?? null;
    }

    /**
     * Extract values from entity
     */
    protected function extractEntityValues(object $entity): array
    {
        if (method_exists($entity, 'toArray')) {
            return $this->maskSensitive($entity->toArray());
        }
        return [];
    }

    /**
     * Encode values to JSON, masking sensitive fields
     */
    protected function encodeValues(?array $values): ?string
    {
        if (!$values) {
            return null;
        }
        return json_encode($this->maskSensitive($values));
    }

    /**
     * Mask sensitive fields in data
     */
    protected function maskSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::SENSITIVE_FIELDS)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
            }
        }
        return $data;
    }

    /**
     * Generate UUID v4
     */
    protected function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Reset context (for testing)
     */
    public function resetContext(): void
    {
        $this->currentUser = null;
        $this->currentIp = null;
        $this->currentUserAgent = null;
        $this->currentSessionId = null;
    }
}
