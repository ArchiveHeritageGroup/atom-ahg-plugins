<?php

namespace AhgCore\Core;

/**
 * AhgTaxonomy - Taxonomy and Term ID Resolver
 *
 * Eliminates hardcoded taxonomy and term IDs throughout plugins.
 * Uses database lookups with caching for performance.
 *
 * Usage:
 *   use AhgCore\Core\AhgTaxonomy;
 *
 *   // Get taxonomy ID by name
 *   $subjectTaxonomyId = AhgTaxonomy::getTaxonomyId('SUBJECT');
 *
 *   // Get term ID by taxonomy and term name
 *   $creationTermId = AhgTaxonomy::getTermId('EVENT_TYPE', 'Creation');
 *
 *   // Or use constants
 *   $id = AhgTaxonomy::TAXONOMY_SUBJECT;
 */
class AhgTaxonomy
{
    /**
     * Standard AtoM Taxonomy IDs (from QubitTaxonomy)
     * These are the default IDs in a fresh AtoM installation
     */
    public const TAXONOMY_ROOT = 30;
    public const TAXONOMY_PLACE = 42;
    public const TAXONOMY_SUBJECT = 35;
    public const TAXONOMY_NAME = 36;
    public const TAXONOMY_GENRE = 78;
    public const TAXONOMY_FUNCTION = 73;
    public const TAXONOMY_LEVEL_OF_DESCRIPTION = 34;
    public const TAXONOMY_ACTOR_ENTITY_TYPE = 32;
    public const TAXONOMY_EVENT_TYPE = 40;
    public const TAXONOMY_NOTE_TYPE = 52;
    public const TAXONOMY_MATERIAL_TYPE = 50;
    public const TAXONOMY_MEDIA_TYPE = 46;
    public const TAXONOMY_DIGITAL_OBJECT_USAGE = 47;
    public const TAXONOMY_PHYSICAL_OBJECT_TYPE = 57;
    public const TAXONOMY_RELATION_TYPE = 66;
    public const TAXONOMY_DESCRIPTION_STATUS = 55;
    public const TAXONOMY_DESCRIPTION_DETAIL_LEVEL = 56;
    public const TAXONOMY_ACTOR_RELATION_TYPE = 68;
    public const TAXONOMY_TERM_RELATION_TYPE = 67;
    public const TAXONOMY_RIGHTS_ACT = 75;
    public const TAXONOMY_RIGHTS_BASIS = 74;
    public const TAXONOMY_ACCESSION_ACQUISITION_TYPE = 84;
    public const TAXONOMY_ACCESSION_PROCESSING_STATUS = 88;
    public const TAXONOMY_ACCESSION_PROCESSING_PRIORITY = 87;
    public const TAXONOMY_ACCESSION_RESOURCE_TYPE = 83;
    public const TAXONOMY_DEACCESSION_SCOPE = 89;
    public const TAXONOMY_RAD_TITLE_NOTE_TYPE = 53;
    public const TAXONOMY_RAD_OTHER_NOTE_TYPE = 54;
    public const TAXONOMY_ISDF_RELATION_TYPE = 81;
    public const TAXONOMY_THEMATIC_AREA = 72;
    public const TAXONOMY_GEOGRAPHIC_SUBREGION = 71;

    /**
     * Standard AtoM Term IDs (from QubitTerm)
     */
    public const TERM_ROOT = 110;
    public const TERM_CREATION = 111;
    public const TERM_SUBJECT = 112;
    public const TERM_CUSTODY = 113;
    public const TERM_PUBLICATION = 114;
    public const TERM_CONTRIBUTION = 115;
    public const TERM_COLLECTION = 116;
    public const TERM_ACCUMULATION = 117;
    public const TERM_ACQUISITION = 118;
    public const TERM_REPRODUCTION = 119;
    public const TERM_BROADCASTING = 120;
    public const TERM_DISTRIBUTION = 121;
    public const TERM_MANUFACTURING = 122;
    public const TERM_AUTHORING = 123;
    public const TERM_EDITING = 124;
    public const TERM_PERSON = 131;
    public const TERM_CORPORATE_BODY = 132;
    public const TERM_FAMILY = 133;
    public const TERM_ITEM = 140;
    public const TERM_FONDS = 141;
    public const TERM_SUBFONDS = 142;
    public const TERM_COLLECTION_LEVEL = 143;
    public const TERM_SERIES = 144;
    public const TERM_SUBSERIES = 145;
    public const TERM_FILE = 146;
    public const TERM_MASTER = 147;
    public const TERM_REFERENCE = 148;
    public const TERM_THUMBNAIL = 149;
    public const TERM_COMPOUND_REPRESENTATION = 150;
    public const TERM_NAME_ACCESS_POINT = 161;
    public const TERM_PLACE_ACCESS_POINT = 162;
    public const TERM_SUBJECT_ACCESS_POINT = 163;
    public const TERM_GENRE_ACCESS_POINT = 164;
    public const TERM_FUNCTION_ACCESS_POINT = 165;
    public const TERM_DONOR = 170;
    public const TERM_STATUS_DRAFT = 159;
    public const TERM_STATUS_PUBLISHED = 160;

    private static array $taxonomyCache = [];
    private static array $termCache = [];
    private static bool $cacheLoaded = false;

    /**
     * Get taxonomy ID by name/code
     *
     * @param string $name Taxonomy name (e.g., 'SUBJECT', 'PLACES', 'EVENT_TYPE')
     * @return int|null Taxonomy ID or null if not found
     */
    public static function getTaxonomyId(string $name): ?int
    {
        self::loadCache();

        // Normalize name
        $normalizedName = self::normalizeName($name);

        // Check cache
        if (isset(self::$taxonomyCache[$normalizedName])) {
            return self::$taxonomyCache[$normalizedName];
        }

        // Try constant lookup
        $constName = 'TAXONOMY_' . strtoupper(str_replace([' ', '-'], '_', $name));
        if (defined('self::' . $constName)) {
            return constant('self::' . $constName);
        }

        // Database lookup
        try {
            $taxonomy = AhgDb::table('taxonomy')
                ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
                ->whereRaw('UPPER(REPLACE(taxonomy_i18n.name, " ", "_")) = ?', [$normalizedName])
                ->orWhere('taxonomy_i18n.name', 'like', '%' . $name . '%')
                ->select('taxonomy.id')
                ->first();

            if ($taxonomy) {
                self::$taxonomyCache[$normalizedName] = $taxonomy->id;
                return $taxonomy->id;
            }
        } catch (\Exception $e) {
            error_log('AhgTaxonomy: Error looking up taxonomy: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get term ID by taxonomy and term name
     *
     * @param string|int $taxonomy Taxonomy name or ID
     * @param string $termName Term name
     * @param string $culture Culture code (default 'en')
     * @return int|null Term ID or null if not found
     */
    public static function getTermId(string|int $taxonomy, string $termName, string $culture = 'en'): ?int
    {
        self::loadCache();

        // Resolve taxonomy ID
        $taxonomyId = is_int($taxonomy) ? $taxonomy : self::getTaxonomyId($taxonomy);
        if (!$taxonomyId) {
            return null;
        }

        // Create cache key
        $cacheKey = $taxonomyId . '_' . self::normalizeName($termName);
        if (isset(self::$termCache[$cacheKey])) {
            return self::$termCache[$cacheKey];
        }

        // Database lookup
        try {
            $term = AhgDb::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', $taxonomyId)
                ->where(function ($q) use ($termName, $culture) {
                    $q->where('term_i18n.name', $termName)
                      ->orWhereRaw('LOWER(term_i18n.name) = LOWER(?)', [$termName]);
                })
                ->where('term_i18n.culture', $culture)
                ->select('term.id')
                ->first();

            if ($term) {
                self::$termCache[$cacheKey] = $term->id;
                return $term->id;
            }
        } catch (\Exception $e) {
            error_log('AhgTaxonomy: Error looking up term: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get term name by ID
     *
     * @param int $termId Term ID
     * @param string $culture Culture code
     * @return string|null Term name or null if not found
     */
    public static function getTermName(int $termId, string $culture = 'en'): ?string
    {
        try {
            return AhgDb::table('term_i18n')
                ->where('id', $termId)
                ->where('culture', $culture)
                ->value('name');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all terms for a taxonomy
     *
     * @param string|int $taxonomy Taxonomy name or ID
     * @param string $culture Culture code
     * @return array Array of ['id' => ..., 'name' => ...]
     */
    public static function getTerms(string|int $taxonomy, string $culture = 'en'): array
    {
        $taxonomyId = is_int($taxonomy) ? $taxonomy : self::getTaxonomyId($taxonomy);
        if (!$taxonomyId) {
            return [];
        }

        try {
            return AhgDb::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', $taxonomyId)
                ->where('term_i18n.culture', $culture)
                ->orderBy('term_i18n.name')
                ->select('term.id', 'term_i18n.name')
                ->get()
                ->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get or create a term
     *
     * @param string|int $taxonomy Taxonomy name or ID
     * @param string $termName Term name to find or create
     * @param string $culture Culture code
     * @return int|null Term ID
     */
    public static function getOrCreateTerm(string|int $taxonomy, string $termName, string $culture = 'en'): ?int
    {
        // Try to find existing
        $termId = self::getTermId($taxonomy, $termName, $culture);
        if ($termId) {
            return $termId;
        }

        $taxonomyId = is_int($taxonomy) ? $taxonomy : self::getTaxonomyId($taxonomy);
        if (!$taxonomyId) {
            return null;
        }

        try {
            // Get root term for this taxonomy
            $rootId = AhgDb::table('term')
                ->where('taxonomy_id', $taxonomyId)
                ->whereNull('parent_id')
                ->value('id') ?? self::TERM_ROOT;

            // Create object record first
            $objectId = AhgDb::table('object')->insertGetId([
                'class_name' => 'QubitTerm',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Create term
            AhgDb::table('term')->insert([
                'id' => $objectId,
                'taxonomy_id' => $taxonomyId,
                'parent_id' => $rootId,
            ]);

            // Create term_i18n
            AhgDb::table('term_i18n')->insert([
                'id' => $objectId,
                'culture' => $culture,
                'name' => $termName,
            ]);

            // Create slug
            $slug = self::generateSlug($termName);
            AhgDb::table('slug')->insert([
                'object_id' => $objectId,
                'slug' => $slug,
            ]);

            // Update cache
            $cacheKey = $taxonomyId . '_' . self::normalizeName($termName);
            self::$termCache[$cacheKey] = $objectId;

            return $objectId;
        } catch (\Exception $e) {
            error_log('AhgTaxonomy: Error creating term: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a term exists
     */
    public static function termExists(string|int $taxonomy, string $termName, string $culture = 'en'): bool
    {
        return self::getTermId($taxonomy, $termName, $culture) !== null;
    }

    /**
     * Get taxonomy name by ID
     */
    public static function getTaxonomyName(int $taxonomyId, string $culture = 'en'): ?string
    {
        try {
            return AhgDb::table('taxonomy_i18n')
                ->where('id', $taxonomyId)
                ->where('culture', $culture)
                ->value('name');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Pre-load commonly used taxonomy and term IDs into cache
     */
    private static function loadCache(): void
    {
        if (self::$cacheLoaded) {
            return;
        }

        try {
            // Load taxonomy IDs
            $taxonomies = AhgDb::table('taxonomy')
                ->join('taxonomy_i18n', 'taxonomy.id', '=', 'taxonomy_i18n.id')
                ->where('taxonomy_i18n.culture', 'en')
                ->select('taxonomy.id', 'taxonomy_i18n.name')
                ->get();

            foreach ($taxonomies as $tax) {
                $normalized = self::normalizeName($tax->name);
                self::$taxonomyCache[$normalized] = $tax->id;
            }

            self::$cacheLoaded = true;
        } catch (\Exception $e) {
            // Ignore errors, will fall back to direct lookups
        }
    }

    /**
     * Normalize name for cache lookup
     */
    private static function normalizeName(string $name): string
    {
        return strtoupper(str_replace([' ', '-', '.'], '_', trim($name)));
    }

    /**
     * Generate a URL-safe slug
     */
    private static function generateSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (AhgDb::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Clear the cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$taxonomyCache = [];
        self::$termCache = [];
        self::$cacheLoaded = false;
    }
}
