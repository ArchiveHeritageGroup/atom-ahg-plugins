<?php

namespace AhgCore;

/**
 * AHG Sector Profile - Manages sector-specific profiles (Museum, Library, Gallery, DAM).
 *
 * Sector plugins provide profiles that define:
 * - Field labels and translations
 * - Vocabulary mappings
 * - Default values
 * - Display templates
 *
 * Sector plugins do NOT provide:
 * - Module actions (use capability plugins instead)
 * - Feature implementations (use capability plugins)
 * - Database tables (use capability plugins)
 *
 * Usage:
 *   // Sector plugin registers its profile
 *   AhgSectorProfile::register('museum', [
 *       'name' => 'Museum',
 *       'standard' => 'Spectrum 5.0 / CCO',
 *       'labels' => [...],
 *       'vocabularies' => [...],
 *   ]);
 *
 *   // Get label for current sector
 *   $label = AhgSectorProfile::getLabel('extent', 'Dimensions');
 */
class AhgSectorProfile
{
    private static array $profiles = [];
    private static ?string $activeSector = null;

    /**
     * Register a sector profile.
     *
     * @param string $sector  Sector code (museum, library, gallery, dam, archive)
     * @param array  $profile Profile configuration
     */
    public static function register(string $sector, array $profile): void
    {
        self::$profiles[$sector] = array_merge([
            'name' => ucfirst($sector),
            'standard' => null,
            'labels' => [],
            'vocabularies' => [],
            'defaults' => [],
            'templates' => [],
            'capabilities' => [], // Required capabilities for this sector
        ], $profile);
    }

    /**
     * Set the active sector.
     */
    public static function setActive(string $sector): void
    {
        self::$activeSector = $sector;
    }

    /**
     * Get the active sector.
     */
    public static function getActive(): ?string
    {
        return self::$activeSector;
    }

    /**
     * Get a label for the current sector.
     *
     * @param string $field   Field name
     * @param string $default Default label if not found
     * @return string
     */
    public static function getLabel(string $field, string $default = ''): string
    {
        if (self::$activeSector && isset(self::$profiles[self::$activeSector]['labels'][$field])) {
            return self::$profiles[self::$activeSector]['labels'][$field];
        }

        return $default;
    }

    /**
     * Get vocabulary for a field in the current sector.
     */
    public static function getVocabulary(string $field): ?array
    {
        if (self::$activeSector && isset(self::$profiles[self::$activeSector]['vocabularies'][$field])) {
            return self::$profiles[self::$activeSector]['vocabularies'][$field];
        }

        return null;
    }

    /**
     * Get default value for a field in the current sector.
     */
    public static function getDefault(string $field, $default = null)
    {
        if (self::$activeSector && isset(self::$profiles[self::$activeSector]['defaults'][$field])) {
            return self::$profiles[self::$activeSector]['defaults'][$field];
        }

        return $default;
    }

    /**
     * Get all registered profiles.
     */
    public static function all(): array
    {
        return self::$profiles;
    }

    /**
     * Get a specific profile.
     */
    public static function get(string $sector): ?array
    {
        return self::$profiles[$sector] ?? null;
    }

    /**
     * Check if a sector is registered.
     */
    public static function has(string $sector): bool
    {
        return isset(self::$profiles[$sector]);
    }

    /**
     * Standard sector codes.
     */
    public const SECTOR_MUSEUM = 'museum';
    public const SECTOR_LIBRARY = 'library';
    public const SECTOR_GALLERY = 'gallery';
    public const SECTOR_DAM = 'dam';
    public const SECTOR_ARCHIVE = 'archive';
}
