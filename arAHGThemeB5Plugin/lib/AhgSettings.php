<?php

/**
 * AHG Settings Helper
 * 
 * Simple helper for accessing AHG settings from anywhere in the codebase.
 * 
 * Usage:
 *   AhgSettings::get('spectrum_enabled')
 *   AhgSettings::getBool('meta_extract_on_upload')
 *   AhgSettings::isEnabled('spectrum')
 */
class AhgSettings
{
    private static ?array $cache = null;
    
    public static function get(string $key, $default = null)
    {
        self::loadCache();
        return self::$cache[$key] ?? $default;
    }
    
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return $value === 'true' || $value === '1' || $value === true;
    }
    
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }
    
    public static function isEnabled(string $feature): bool
    {
        $map = [
            'theme' => 'ahg_theme_enabled',
            'metadata' => 'meta_extract_on_upload',
            'spectrum' => 'spectrum_enabled',
            'iiif' => 'iiif_enabled',
            'data_protection' => 'dp_enabled',
            'privacy' => 'dp_enabled',
            'faces' => 'face_detect_enabled',
            'face_detection' => 'face_detect_enabled',
            'jobs' => 'jobs_enabled',
        ];
        $key = $map[$feature] ?? $feature . '_enabled';
        return self::getBool($key, false);
    }
    
    private static function loadCache(): void
    {
        if (self::$cache !== null) {
            return;
        }
        
        self::$cache = [];
        
        try {
            $conn = Propel::getConnection();
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM ahg_settings");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log('AhgSettings: ' . $e->getMessage());
        }
    }
    
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
