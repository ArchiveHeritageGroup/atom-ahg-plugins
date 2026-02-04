<?php

namespace AhgMultiTenant\Services;

use AhgMultiTenant\Models\Tenant;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantResolver Service
 *
 * Resolves tenant from HTTP host (domain or subdomain).
 * Implements Issue #85: Subdomain and Custom Domain Routing
 *
 * Resolution Order:
 * 1. Custom domain exact match (e.g., archive.institution.org)
 * 2. Subdomain match (e.g., tenant.heritage.example.com)
 * 3. Fallback to session-based context
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantResolver
{
    /** @var string|null Base domain for subdomain detection */
    private static ?string $baseDomain = null;

    /** @var array Cached resolution results */
    private static array $cache = [];

    /** @var bool Whether domain routing is enabled */
    private static bool $enabled = true;

    /** @var array Domains to exclude from resolution (e.g., main site) */
    private static array $excludedDomains = [];

    /**
     * Initialize resolver with configuration
     *
     * @param array $config Configuration options
     */
    public static function initialize(array $config = []): void
    {
        self::$baseDomain = $config['base_domain'] ?? self::getConfiguredBaseDomain();
        self::$enabled = $config['enabled'] ?? true;
        self::$excludedDomains = $config['excluded_domains'] ?? self::getConfiguredExcludedDomains();
    }

    /**
     * Resolve tenant from HTTP host
     *
     * @param string|null $host HTTP host (defaults to current request)
     * @return Tenant|null Resolved tenant or null
     */
    public static function resolveFromHost(?string $host = null): ?Tenant
    {
        if (!self::$enabled) {
            return null;
        }

        $host = $host ?? self::getCurrentHost();

        if (empty($host)) {
            return null;
        }

        // Check cache
        if (isset(self::$cache[$host])) {
            return self::$cache[$host];
        }

        // Remove port if present
        $host = self::normalizeHost($host);

        // Check if excluded
        if (self::isExcludedDomain($host)) {
            self::$cache[$host] = null;
            return null;
        }

        // Try custom domain first (exact match)
        $tenant = self::resolveByCustomDomain($host);

        // Try subdomain if no custom domain match
        if (!$tenant) {
            $tenant = self::resolveBySubdomain($host);
        }

        // Cache result
        self::$cache[$host] = $tenant;

        return $tenant;
    }

    /**
     * Resolve tenant by custom domain
     *
     * @param string $domain Full domain (e.g., archive.institution.org)
     * @return Tenant|null
     */
    public static function resolveByCustomDomain(string $domain): ?Tenant
    {
        $tenant = Tenant::findByDomain($domain);

        if ($tenant && $tenant->canAccess()) {
            return $tenant;
        }

        return null;
    }

    /**
     * Resolve tenant by subdomain
     *
     * @param string $host Full host (e.g., tenant.heritage.example.com)
     * @return Tenant|null
     */
    public static function resolveBySubdomain(string $host): ?Tenant
    {
        $subdomain = self::extractSubdomain($host);

        if (empty($subdomain)) {
            return null;
        }

        $tenant = Tenant::findBySubdomain($subdomain);

        if ($tenant && $tenant->canAccess()) {
            return $tenant;
        }

        return null;
    }

    /**
     * Extract subdomain from host
     *
     * @param string $host Full host
     * @return string|null Subdomain or null
     */
    public static function extractSubdomain(string $host): ?string
    {
        $baseDomain = self::getBaseDomain();

        if (empty($baseDomain)) {
            return null;
        }

        // Normalize both
        $host = strtolower(trim($host));
        $baseDomain = strtolower(trim($baseDomain));

        // Check if host ends with base domain
        if (!str_ends_with($host, '.' . $baseDomain) && $host !== $baseDomain) {
            return null;
        }

        // Extract subdomain
        $subdomain = str_replace('.' . $baseDomain, '', $host);

        // Ignore if it's the base domain itself or www
        if (empty($subdomain) || $subdomain === $baseDomain || $subdomain === 'www') {
            return null;
        }

        return $subdomain;
    }

    /**
     * Get the base domain for subdomain detection
     *
     * @return string|null
     */
    public static function getBaseDomain(): ?string
    {
        if (self::$baseDomain === null) {
            self::$baseDomain = self::getConfiguredBaseDomain();
        }

        return self::$baseDomain;
    }

    /**
     * Set the base domain
     *
     * @param string $domain
     */
    public static function setBaseDomain(string $domain): void
    {
        self::$baseDomain = $domain;
        self::clearCache();
    }

    /**
     * Get configured base domain from settings
     *
     * @return string|null
     */
    private static function getConfiguredBaseDomain(): ?string
    {
        // Try sfConfig first
        $domain = \sfConfig::get('app_multi_tenant_base_domain');
        if ($domain) {
            return $domain;
        }

        // Try database setting
        try {
            $setting = DB::table('ahg_settings')
                ->where('setting_key', 'multi_tenant_base_domain')
                ->value('setting_value');

            if ($setting) {
                return $setting;
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Derive from current host (fallback)
        $host = self::getCurrentHost();
        if ($host) {
            // Extract root domain (last two parts)
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                return implode('.', array_slice($parts, -2));
            }
        }

        return null;
    }

    /**
     * Get configured excluded domains
     *
     * @return array
     */
    private static function getConfiguredExcludedDomains(): array
    {
        // Try sfConfig first
        $domains = \sfConfig::get('app_multi_tenant_excluded_domains');
        if (is_array($domains)) {
            return $domains;
        }

        // Try database setting
        try {
            $setting = DB::table('ahg_settings')
                ->where('setting_key', 'multi_tenant_excluded_domains')
                ->value('setting_value');

            if ($setting) {
                return array_filter(array_map('trim', explode(',', $setting)));
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Default exclusions
        return ['localhost', '127.0.0.1'];
    }

    /**
     * Check if domain is excluded from resolution
     *
     * @param string $host
     * @return bool
     */
    public static function isExcludedDomain(string $host): bool
    {
        $excluded = self::$excludedDomains ?: self::getConfiguredExcludedDomains();

        foreach ($excluded as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        // Also exclude the base domain itself (main site)
        $baseDomain = self::getBaseDomain();
        if ($baseDomain && ($host === $baseDomain || $host === 'www.' . $baseDomain)) {
            return true;
        }

        return false;
    }

    /**
     * Get current HTTP host
     *
     * @return string|null
     */
    public static function getCurrentHost(): ?string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
    }

    /**
     * Normalize host (remove port, lowercase)
     *
     * @param string $host
     * @return string
     */
    public static function normalizeHost(string $host): string
    {
        // Remove port
        if (strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }

        return strtolower(trim($host));
    }

    /**
     * Check if current request is for a tenant domain
     *
     * @return bool
     */
    public static function isTenantRequest(): bool
    {
        return self::resolveFromHost() !== null;
    }

    /**
     * Get resolution result with details
     *
     * @param string|null $host
     * @return array Resolution details
     */
    public static function getResolutionDetails(?string $host = null): array
    {
        $host = $host ?? self::getCurrentHost();
        $normalizedHost = self::normalizeHost($host ?? '');

        $result = [
            'host' => $host,
            'normalized_host' => $normalizedHost,
            'base_domain' => self::getBaseDomain(),
            'is_excluded' => self::isExcludedDomain($normalizedHost),
            'subdomain' => self::extractSubdomain($normalizedHost),
            'tenant' => null,
            'resolution_method' => null,
        ];

        if ($result['is_excluded']) {
            $result['resolution_method'] = 'excluded';
            return $result;
        }

        // Try custom domain
        $tenant = self::resolveByCustomDomain($normalizedHost);
        if ($tenant) {
            $result['tenant'] = [
                'id' => $tenant->id,
                'code' => $tenant->code,
                'name' => $tenant->name,
            ];
            $result['resolution_method'] = 'custom_domain';
            return $result;
        }

        // Try subdomain
        $tenant = self::resolveBySubdomain($normalizedHost);
        if ($tenant) {
            $result['tenant'] = [
                'id' => $tenant->id,
                'code' => $tenant->code,
                'name' => $tenant->name,
            ];
            $result['resolution_method'] = 'subdomain';
            return $result;
        }

        $result['resolution_method'] = 'none';
        return $result;
    }

    /**
     * Clear resolution cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Enable/disable domain routing
     *
     * @param bool $enabled
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Check if domain routing is enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Generate tenant URL
     *
     * @param Tenant $tenant
     * @param string $path Path to append
     * @param bool $preferCustomDomain Prefer custom domain over subdomain
     * @return string Full URL
     */
    public static function generateTenantUrl(Tenant $tenant, string $path = '', bool $preferCustomDomain = true): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // Use custom domain if available and preferred
        if ($preferCustomDomain && !empty($tenant->domain)) {
            return $scheme . '://' . $tenant->domain . '/' . ltrim($path, '/');
        }

        // Use subdomain if available
        if (!empty($tenant->subdomain) && self::getBaseDomain()) {
            $host = $tenant->subdomain . '.' . self::getBaseDomain();
            return $scheme . '://' . $host . '/' . ltrim($path, '/');
        }

        // Fallback to current host with tenant code in path
        $currentHost = self::getCurrentHost() ?? 'localhost';
        return $scheme . '://' . $currentHost . '/tenant/' . $tenant->code . '/' . ltrim($path, '/');
    }
}
