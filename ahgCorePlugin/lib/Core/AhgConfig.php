<?php

namespace AhgCore\Core;

/**
 * AhgConfig - Configuration Resolver
 *
 * Centralized configuration access to eliminate hardcoded paths and URLs.
 *
 * Usage:
 *   use AhgCore\Core\AhgConfig;
 *   $baseUrl = AhgConfig::getSiteBaseUrl();
 *   $uploadPath = AhgConfig::getUploadPath();
 */
class AhgConfig
{
    private static ?array $cache = null;

    /**
     * Get the site base URL (no trailing slash)
     * Resolves from: sfConfig -> $_SERVER -> database setting
     */
    public static function getSiteBaseUrl(): string
    {
        // Try sfConfig first
        if (class_exists('sfConfig', false)) {
            $url = \sfConfig::get('app_siteBaseUrl');
            if ($url) {
                return rtrim($url, '/');
            }
        }

        // Try $_SERVER
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        // Try database setting
        try {
            $setting = AhgDb::table('setting')
                ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
                ->where('setting.name', 'siteBaseUrl')
                ->value('setting_i18n.value');
            if ($setting) {
                return rtrim($setting, '/');
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Fallback
        return 'http://localhost';
    }

    /**
     * Get the uploads directory path
     */
    public static function getUploadPath(string $subPath = ''): string
    {
        $rootPath = self::getRootPath();
        $uploadPath = $rootPath . '/uploads';

        if ($subPath) {
            return $uploadPath . '/' . ltrim($subPath, '/');
        }

        return $uploadPath;
    }

    /**
     * Get the framework root path
     */
    public static function getFrameworkRoot(): string
    {
        return self::getRootPath() . '/atom-framework';
    }

    /**
     * Get the AtoM root path
     */
    public static function getRootPath(): string
    {
        // Try sfConfig
        if (class_exists('sfConfig', false)) {
            $root = \sfConfig::get('sf_root_dir');
            if ($root && is_dir($root)) {
                return $root;
            }
        }

        // Try constant
        if (defined('ATOM_ROOT_PATH')) {
            return ATOM_ROOT_PATH;
        }

        // Fallback from file location
        $path = dirname(__DIR__, 4);
        if (is_dir($path)) {
            return $path;
        }

        return sfConfig::get('sf_root_dir');
    }

    /**
     * Get the plugins path
     */
    public static function getPluginsPath(): string
    {
        return self::getRootPath() . '/plugins';
    }

    /**
     * Get the AHG plugins path (atom-ahg-plugins)
     */
    public static function getAhgPluginsPath(): string
    {
        return self::getRootPath() . '/atom-ahg-plugins';
    }

    /**
     * Get the cache path
     */
    public static function getCachePath(): string
    {
        return self::getRootPath() . '/cache';
    }

    /**
     * Get a setting from sfConfig
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        if (class_exists('sfConfig', false)) {
            return \sfConfig::get($name, $default);
        }
        return $default;
    }

    /**
     * Get an app setting (shortcut for app_*)
     */
    public static function getApp(string $name, mixed $default = null): mixed
    {
        return self::get('app_' . $name, $default);
    }

    /**
     * Get a database setting from setting/setting_i18n tables
     */
    public static function getDbSetting(string $name, ?string $culture = 'en'): ?string
    {
        try {
            $query = AhgDb::table('setting')
                ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
                ->where('setting.name', $name);

            if ($culture) {
                $query->where('setting_i18n.culture', $culture);
            }

            return $query->value('setting_i18n.value');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the current culture/locale
     */
    public static function getCulture(): string
    {
        if (class_exists('sfContext', false) && \sfContext::hasInstance()) {
            try {
                return \sfContext::getInstance()->getUser()->getCulture();
            } catch (\Exception $e) {
                // Fall through
            }
        }
        return 'en';
    }

    /**
     * Get the upload URL base path (for web access)
     */
    public static function getUploadUrl(string $subPath = ''): string
    {
        $baseUrl = self::getSiteBaseUrl();
        $uploadUrl = $baseUrl . '/uploads';

        if ($subPath) {
            return $uploadUrl . '/' . ltrim($subPath, '/');
        }

        return $uploadUrl;
    }

    /**
     * Check if we're in development mode
     */
    public static function isDevelopment(): bool
    {
        $env = self::get('sf_environment', 'prod');
        return $env === 'dev' || $env === 'development';
    }

    /**
     * Check if we're in production mode
     */
    public static function isProduction(): bool
    {
        return !self::isDevelopment();
    }

    /**
     * Get PHP version
     */
    public static function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Get the current environment
     */
    public static function getEnvironment(): string
    {
        return self::get('sf_environment', 'prod');
    }

    /**
     * Get temp directory
     */
    public static function getTempPath(): string
    {
        $tempPath = self::getRootPath() . '/cache/tmp';
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0755, true);
        }
        return $tempPath;
    }

    /**
     * Get the log path
     */
    public static function getLogPath(): string
    {
        return self::getRootPath() . '/log';
    }

    /**
     * Get max upload size in bytes
     */
    public static function getMaxUploadSize(): int
    {
        $uploadMax = self::parseSize(ini_get('upload_max_filesize'));
        $postMax = self::parseSize(ini_get('post_max_size'));
        return min($uploadMax, $postMax);
    }

    /**
     * Parse PHP size notation (e.g., "8M" -> 8388608)
     */
    private static function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtolower(substr($size, -1));
        $value = (int) $size;

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
