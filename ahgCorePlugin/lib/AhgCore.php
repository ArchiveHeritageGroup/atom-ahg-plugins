<?php

namespace AhgCore;

use AhgCore\Core\AhgDb;
use AhgCore\Core\AhgConfig;
use AhgCore\Core\AhgTaxonomy;
use AhgCore\Core\AhgStorage;
use AhgCore\Contracts\AuditServiceInterface;

/**
 * AhgCore - Main Facade
 *
 * Provides a unified entry point to all ahgCorePlugin services.
 * Use this class for quick access to core utilities.
 *
 * Usage:
 *   use AhgCore\AhgCore;
 *
 *   // Database access
 *   $db = AhgCore::db();
 *   $results = $db->table('information_object')->get();
 *
 *   // Configuration
 *   $baseUrl = AhgCore::config()->getSiteBaseUrl();
 *
 *   // Taxonomy resolution
 *   $termId = AhgCore::taxonomy()->getTermId('EVENT_TYPE', 'Creation');
 *
 *   // File storage
 *   $result = AhgCore::storage()->store($uploadedFile, 'documents');
 *
 *   // Get registered service
 *   $audit = AhgCore::getService(AuditServiceInterface::class);
 */
class AhgCore
{
    /**
     * Registered services
     */
    private static array $services = [];

    /**
     * Get database connection/helper
     */
    public static function db(): AhgDb
    {
        return new class extends AhgDb {};
    }

    /**
     * Get configuration helper
     */
    public static function config(): AhgConfig
    {
        return new class extends AhgConfig {};
    }

    /**
     * Get taxonomy helper
     */
    public static function taxonomy(): AhgTaxonomy
    {
        return new class extends AhgTaxonomy {};
    }

    /**
     * Get storage helper
     */
    public static function storage(): AhgStorage
    {
        return new class extends AhgStorage {};
    }

    /**
     * Quick access to database table
     */
    public static function table(string $tableName): \Illuminate\Database\Query\Builder
    {
        return AhgDb::table($tableName);
    }

    /**
     * Register a service implementation
     *
     * @param string $interface Interface class name
     * @param object|callable $implementation Service instance or factory
     */
    public static function registerService(string $interface, object|callable $implementation): void
    {
        self::$services[$interface] = $implementation;
    }

    /**
     * Get a registered service
     *
     * @param string $interface Interface class name
     * @return object|null Service instance or null if not registered
     */
    public static function getService(string $interface): ?object
    {
        if (!isset(self::$services[$interface])) {
            return null;
        }

        $service = self::$services[$interface];

        // If it's a callable factory, invoke it
        if (is_callable($service) && !is_object($service)) {
            self::$services[$interface] = $service();
            return self::$services[$interface];
        }

        return $service;
    }

    /**
     * Check if a service is registered
     */
    public static function hasService(string $interface): bool
    {
        return isset(self::$services[$interface]);
    }

    /**
     * Get the audit service (convenience method)
     */
    public static function audit(): ?AuditServiceInterface
    {
        return self::getService(AuditServiceInterface::class);
    }

    /**
     * Log an audit event (convenience method)
     *
     * @param string $action Action type
     * @param string $entityType Entity type
     * @param int|null $entityId Entity ID
     * @param array $options Additional options
     * @return mixed
     */
    public static function logAudit(string $action, string $entityType, ?int $entityId = null, array $options = []): mixed
    {
        $audit = self::audit();
        if (!$audit) {
            return null;
        }
        return $audit->log($action, $entityType, $entityId, $options);
    }

    /**
     * Get plugin version
     */
    public static function getVersion(): string
    {
        $extensionFile = dirname(__DIR__) . '/extension.json';
        if (file_exists($extensionFile)) {
            $data = json_decode(file_get_contents($extensionFile), true);
            return $data['version'] ?? '1.0.0';
        }
        return '1.0.0';
    }

    /**
     * Check if running in CLI mode
     */
    public static function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Get current user ID (if authenticated)
     */
    public static function getCurrentUserId(): ?int
    {
        if (class_exists('sfContext', false) && \sfContext::hasInstance()) {
            try {
                $user = \sfContext::getInstance()->getUser();
                if ($user && $user->isAuthenticated()) {
                    return $user->getAttribute('user_id');
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        return null;
    }

    /**
     * Get current username (if authenticated)
     */
    public static function getCurrentUsername(): ?string
    {
        if (class_exists('sfContext', false) && \sfContext::hasInstance()) {
            try {
                $user = \sfContext::getInstance()->getUser();
                if ($user && $user->isAuthenticated()) {
                    return $user->getAttribute('username') ?? $user->getUsername();
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        return null;
    }

    /**
     * Clear all caches
     */
    public static function clearCaches(): void
    {
        AhgTaxonomy::clearCache();
    }
}
