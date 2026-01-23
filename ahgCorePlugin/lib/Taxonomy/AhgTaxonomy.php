<?php

namespace AhgCore\Taxonomy;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AhgTaxonomy - Centralized taxonomy ID lookup
 *
 * Provides dynamic lookup of AtoM taxonomy IDs by name, replacing hardcoded IDs.
 * Caches results for performance.
 *
 * Usage:
 *   $subjectsId = AhgTaxonomy::getId('subjects');
 *   $placesId = AhgTaxonomy::getId('places');
 */
class AhgTaxonomy
{
    private static array $cache = [];

    /**
     * Standard AtoM taxonomy names mapped to their typical IDs (fallback only)
     */
    private const FALLBACK_IDS = [
        'subjects' => 35,
        'places' => 42,
        'level_of_description' => 34,
        'actor_entity_type' => 32,
        'thematic_area' => 72,
        'geographic_subregion' => 73,
        'media_type' => 46,
        'digital_object_usage' => 47,
        'physical_object_type' => 52,
        'relation_type' => 53,
        'actor_relation_type' => 54,
        'material_type' => 57,
        'rad_title_note_type' => 61,
        'rad_note_type' => 62,
        'mods_resource_type' => 64,
        'dc_type' => 65,
        'accession_acquisition_type' => 66,
        'accession_resource_type' => 67,
        'accession_processing_priority' => 68,
        'accession_processing_status' => 69,
        'deaccession_scope' => 70,
        'rights_act' => 75,
        'rights_basis' => 76,
        'copyright_status' => 77,
        'status_type' => 79,
    ];

    /**
     * Get taxonomy ID by name
     *
     * @param string $name Taxonomy name (e.g., 'subjects', 'places')
     * @return int|null The taxonomy ID or null if not found
     */
    public static function getId(string $name): ?int
    {
        $name = strtolower(trim($name));

        // Check cache first
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        // Initialize database if needed
        \AhgCore\Core\AhgDb::init();

        // Try to find by name in taxonomy_i18n
        $taxonomy = DB::table('taxonomy_i18n')
            ->whereRaw('LOWER(name) = ?', [$name])
            ->value('id');

        if ($taxonomy) {
            self::$cache[$name] = (int) $taxonomy;
            return self::$cache[$name];
        }

        // Try with underscores replaced by spaces
        $nameWithSpaces = str_replace('_', ' ', $name);
        $taxonomy = DB::table('taxonomy_i18n')
            ->whereRaw('LOWER(name) = ?', [$nameWithSpaces])
            ->value('id');

        if ($taxonomy) {
            self::$cache[$name] = (int) $taxonomy;
            return self::$cache[$name];
        }

        // Fall back to hardcoded IDs if lookup fails
        if (isset(self::FALLBACK_IDS[$name])) {
            self::$cache[$name] = self::FALLBACK_IDS[$name];
            return self::$cache[$name];
        }

        return null;
    }

    /**
     * Get multiple taxonomy IDs at once
     *
     * @param array $names Array of taxonomy names
     * @return array Associative array of name => id
     */
    public static function getIds(array $names): array
    {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = self::getId($name);
        }
        return $result;
    }

    /**
     * Clear the cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
