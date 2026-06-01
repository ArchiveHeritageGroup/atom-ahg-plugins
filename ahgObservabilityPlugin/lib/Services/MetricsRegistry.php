<?php

/**
 * MetricsRegistry - central metrics facade for the AHG Observability plugin.
 *
 * Mirrors the Heratio ahg-observability MetricsRegistry: a single entry point
 * that picks a storage adapter automatically and exposes idempotent
 * counter()/gauge()/histogram() helpers under a fixed namespace, so callers
 * never see the underlying backend.
 *
 * Storage selection (config key `storage_driver`, default "auto"):
 *   - redis     when the phpredis extension is loaded AND reachable
 *   - apcu      when the APCu extension is loaded + enabled for this SAPI
 *   - inmemory  otherwise (process-local; tests / CLI / safe fallback)
 *
 * Resolution is fail-soft at every step: an unreachable Redis or a disabled
 * APCu never throws out of here, it degrades to InMemory so a metrics misconfig
 * cannot break request serving.
 *
 * Settings live in the `ahg_settings` table (group "observability") and are
 * read via AhgSettingsService when available, falling back to environment
 * variables and finally to safe defaults. There is intentionally NO autoload:
 * the plugin classes are pulled in via require_once by the boot helper, in
 * line with the Symfony 1.x plugin convention.
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */

namespace AtomExtensions\Observability;

class MetricsRegistry
{
    /** Metric name prefix in rendered output (e.g. "atom_http_requests_total"). */
    public const NAMESPACE = 'atom';

    /** HTTP latency histogram buckets (seconds). */
    public const HTTP_BUCKETS = [0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

    /** DB query histogram buckets (seconds). */
    public const DB_BUCKETS = [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1];

    private static ?self $instance = null;

    private MetricStorageInterface $storage;

    public function __construct(?MetricStorageInterface $storage = null)
    {
        $this->storage = $storage ?? self::resolveStorage();
    }

    /**
     * Process-wide singleton so every push within one request/CLI run shares
     * the same adapter instance (important for the InMemory fallback).
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function storage(): MetricStorageInterface
    {
        return $this->storage;
    }

    public function driverName(): string
    {
        return $this->storage->driverName();
    }

    // ─── Storage resolution ──────────────────────────────────────────────

    public static function resolveStorage(): MetricStorageInterface
    {
        $driver = (string) self::setting('storage_driver', 'auto');
        if ($driver === 'auto') {
            $driver = self::autoDetectDriver();
        }

        switch ($driver) {
            case 'redis':
                return self::buildRedisStorage();
            case 'apcu':
                return self::apcuUsable() ? new ApcuStorage() : new InMemoryStorage();
            case 'inmemory':
                return new InMemoryStorage();
            default:
                return new InMemoryStorage();
        }
    }

    private static function autoDetectDriver(): string
    {
        if (class_exists('\Redis')) {
            return 'redis';
        }
        if (self::apcuUsable()) {
            return 'apcu';
        }

        return 'inmemory';
    }

    /**
     * APCu is only usable when the extension is loaded AND enabled for the
     * current SAPI (apc.enable_cli defaults off), otherwise apcu_* calls warn.
     */
    private static function apcuUsable(): bool
    {
        return extension_loaded('apcu')
            && function_exists('apcu_enabled')
            && apcu_enabled();
    }

    private static function buildRedisStorage(): MetricStorageInterface
    {
        try {
            $host = (string) self::setting('redis_host', getenv('OBSERVABILITY_REDIS_HOST') ?: '127.0.0.1');
            $port = (int) self::setting('redis_port', (int) (getenv('OBSERVABILITY_REDIS_PORT') ?: 6379));
            $pass = (string) self::setting('redis_password', getenv('OBSERVABILITY_REDIS_PASSWORD') ?: '');
            $db = (int) self::setting('redis_database', (int) (getenv('OBSERVABILITY_REDIS_DB') ?: 0));

            return new RedisStorage($host, $port, $pass !== '' ? $pass : null, $db);
        } catch (\Throwable $e) {
            // Configured but unreachable - degrade rather than 500.
            return new InMemoryStorage();
        }
    }

    // ─── Metric helpers ──────────────────────────────────────────────────

    public function counter(string $name, string $help, array $labelNames, array $labelValues, float $value = 1.0): void
    {
        $this->storage->incCounter($name, $help, $labelNames, $labelValues, $value);
    }

    public function gauge(string $name, string $help, array $labelNames, array $labelValues, float $value): void
    {
        $this->storage->setGauge($name, $help, $labelNames, $labelValues, $value);
    }

    public function histogram(string $name, string $help, array $labelNames, array $labelValues, array $buckets, float $value): void
    {
        $this->storage->observeHistogram($name, $help, $labelNames, $labelValues, $buckets, $value);
    }

    /**
     * Render the entire registry to Prometheus text exposition format.
     */
    public function render(): string
    {
        $renderer = new PrometheusTextRenderer();

        return $renderer->render(self::NAMESPACE, $this->storage->collect());
    }

    // ─── Settings access ─────────────────────────────────────────────────

    /**
     * Read an observability setting from the ahg_settings table via
     * AhgSettingsService. Keys are flat and prefixed "obs_" by convention to
     * avoid collisions (the service caches by key, group is a separate
     * column). Falls back to the supplied default when the service is absent
     * or the key is unset.
     */
    public static function setting(string $key, $default = null)
    {
        $fqKey = 'obs_'.$key;
        if (class_exists('\AtomExtensions\Services\AhgSettingsService')) {
            try {
                $val = \AtomExtensions\Services\AhgSettingsService::get($fqKey, null);
                if ($val !== null && $val !== '') {
                    return $val;
                }
            } catch (\Throwable $e) {
                // fall through to default
            }
        }

        return $default;
    }
}
