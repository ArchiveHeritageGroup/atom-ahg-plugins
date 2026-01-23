<?php

namespace AhgCore\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

/**
 * AhgDb - Centralized Database Bootstrap
 *
 * Provides a single point of initialization for the Laravel Query Builder.
 * Plugins should use AhgDb::connection() instead of requiring bootstrap.php.
 *
 * Usage:
 *   use AhgCore\Core\AhgDb;
 *   $db = AhgDb::connection();
 *   $results = $db->table('information_object')->where('id', 1)->first();
 *
 * Or use the static table helper:
 *   $results = AhgDb::table('information_object')->where('id', 1)->first();
 */
class AhgDb
{
    private static ?Capsule $capsule = null;
    private static bool $initialized = false;

    /**
     * Initialize the database connection (idempotent)
     */
    public static function init(): bool
    {
        if (self::$initialized) {
            return true;
        }

        // Check if framework already initialized Capsule
        if (defined('ATOM_FRAMEWORK_LOADED') && ATOM_FRAMEWORK_LOADED) {
            self::$initialized = true;
            return true;
        }

        // Get root path
        $rootPath = self::getRootPath();
        if (!$rootPath) {
            return false;
        }

        // Load framework autoloader if available
        $frameworkAutoload = $rootPath . '/atom-framework/vendor/autoload.php';
        if (file_exists($frameworkAutoload) && !class_exists(Capsule::class)) {
            require_once $frameworkAutoload;
        }

        // Load database config
        $configFile = $rootPath . '/config/config.php';
        if (!file_exists($configFile)) {
            return false;
        }

        $config = require $configFile;
        if (!isset($config['all']['propel']['param'])) {
            return false;
        }

        $dbConfig = $config['all']['propel']['param'];
        $dsn = $dbConfig['dsn'] ?? '';

        // Parse DSN
        $database = 'atom';
        $host = 'localhost';
        $port = 3306;

        if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
            $database = $matches[1];
        }
        if (preg_match('/host=([^;]+)/', $dsn, $matches)) {
            $host = $matches[1];
        }
        if (preg_match('/port=([^;]+)/', $dsn, $matches)) {
            $port = (int) $matches[1];
        }

        try {
            self::$capsule = new Capsule();
            self::$capsule->addConnection([
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $dbConfig['username'] ?? 'root',
                'password' => $dbConfig['password'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
            self::$initialized = true;
            return true;
        } catch (\Exception $e) {
            error_log('AhgDb: Failed to initialize database: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the database connection
     */
    public static function connection(): ?Connection
    {
        if (!self::init()) {
            return null;
        }

        // If framework initialized Capsule, use global instance
        if (defined('ATOM_FRAMEWORK_LOADED')) {
            return Capsule::connection();
        }

        return self::$capsule?->getConnection();
    }

    /**
     * Static table helper - shortcut for AhgDb::connection()->table()
     */
    public static function table(string $tableName): \Illuminate\Database\Query\Builder
    {
        $conn = self::connection();
        if (!$conn) {
            throw new \RuntimeException('Database not initialized');
        }
        return $conn->table($tableName);
    }

    /**
     * Run a raw query
     */
    public static function select(string $query, array $bindings = []): array
    {
        $conn = self::connection();
        if (!$conn) {
            throw new \RuntimeException('Database not initialized');
        }
        return $conn->select($query, $bindings);
    }

    /**
     * Run an insert/update/delete statement
     */
    public static function statement(string $query, array $bindings = []): bool
    {
        $conn = self::connection();
        if (!$conn) {
            throw new \RuntimeException('Database not initialized');
        }
        return $conn->statement($query, $bindings);
    }

    /**
     * Begin a database transaction
     */
    public static function beginTransaction(): void
    {
        $conn = self::connection();
        if ($conn) {
            $conn->beginTransaction();
        }
    }

    /**
     * Commit a database transaction
     */
    public static function commit(): void
    {
        $conn = self::connection();
        if ($conn) {
            $conn->commit();
        }
    }

    /**
     * Rollback a database transaction
     */
    public static function rollBack(): void
    {
        $conn = self::connection();
        if ($conn) {
            $conn->rollBack();
        }
    }

    /**
     * Run a closure within a transaction
     */
    public static function transaction(callable $callback, int $attempts = 1): mixed
    {
        $conn = self::connection();
        if (!$conn) {
            throw new \RuntimeException('Database not initialized');
        }
        return $conn->transaction($callback, $attempts);
    }

    /**
     * Check if database is connected
     */
    public static function isConnected(): bool
    {
        try {
            $conn = self::connection();
            if (!$conn) {
                return false;
            }
            $conn->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get AtoM root path
     */
    private static function getRootPath(): ?string
    {
        // Try sfConfig first (Symfony context)
        if (class_exists('sfConfig', false)) {
            $root = \sfConfig::get('sf_root_dir');
            if ($root && is_dir($root)) {
                return $root;
            }
        }

        // Try ATOM_ROOT_PATH constant
        if (defined('ATOM_ROOT_PATH')) {
            return ATOM_ROOT_PATH;
        }

        // Fallback: navigate from this file's location
        // ahgCorePlugin/lib/Core/AhgDb.php -> atom-ahg-plugins -> archive
        $path = dirname(__DIR__, 4);
        if (is_dir($path) && file_exists($path . '/config/config.php')) {
            return $path;
        }

        return null;
    }

    /**
     * Get the Capsule manager instance (for advanced use)
     */
    public static function getCapsule(): ?Capsule
    {
        self::init();
        return self::$capsule;
    }
}
