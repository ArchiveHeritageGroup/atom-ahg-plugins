<?php

namespace AhgCore;

/**
 * AHG Capabilities - Registry of capabilities provided by enabled plugins.
 *
 * Plugins register their capabilities here so other plugins can check
 * what features are available without hard dependencies.
 *
 * Usage:
 *   // Plugin registers its capability
 *   AhgCapabilities::register('iiif', 'ahgIiifPlugin', [
 *       'version' => '2.0',
 *       'features' => ['manifest', 'annotations', 'ocr']
 *   ]);
 *
 *   // Check if capability is available
 *   if (AhgCapabilities::has('iiif')) {
 *       // Use IIIF features
 *   }
 */
class AhgCapabilities
{
    private static array $capabilities = [];

    /**
     * Register a capability provided by a plugin.
     *
     * @param string $capability Capability name (e.g., 'iiif', '3d', 'ai', 'pii')
     * @param string $provider   Plugin providing the capability
     * @param array  $metadata   Additional metadata (version, features, etc.)
     */
    public static function register(string $capability, string $provider, array $metadata = []): void
    {
        self::$capabilities[$capability] = [
            'provider' => $provider,
            'metadata' => $metadata,
            'registered_at' => time(),
        ];
    }

    /**
     * Check if a capability is available.
     */
    public static function has(string $capability): bool
    {
        return isset(self::$capabilities[$capability]);
    }

    /**
     * Get capability info.
     */
    public static function get(string $capability): ?array
    {
        return self::$capabilities[$capability] ?? null;
    }

    /**
     * Get provider plugin for a capability.
     */
    public static function getProvider(string $capability): ?string
    {
        return self::$capabilities[$capability]['provider'] ?? null;
    }

    /**
     * Get all registered capabilities.
     */
    public static function all(): array
    {
        return self::$capabilities;
    }

    /**
     * Get capabilities provided by a specific plugin.
     */
    public static function forPlugin(string $plugin): array
    {
        return array_filter(self::$capabilities, function ($info) use ($plugin) {
            return $info['provider'] === $plugin;
        });
    }

    /**
     * Check if capability has a specific feature.
     */
    public static function hasFeature(string $capability, string $feature): bool
    {
        $info = self::get($capability);
        if (!$info) {
            return false;
        }

        $features = $info['metadata']['features'] ?? [];
        return in_array($feature, $features, true);
    }

    /**
     * Standard capability names for consistency.
     */
    public const IIIF = 'iiif';
    public const MODEL_3D = '3d';
    public const AI = 'ai';
    public const PII = 'pii';
    public const RIGHTS = 'rights';
    public const LOANS = 'loans';
    public const CART = 'cart';
    public const FAVORITES = 'favorites';
    public const BACKUP = 'backup';
    public const AUDIT = 'audit';
    public const SPECTRUM = 'spectrum';
    public const PRIVACY = 'privacy';
    public const SECURITY = 'security';
}
