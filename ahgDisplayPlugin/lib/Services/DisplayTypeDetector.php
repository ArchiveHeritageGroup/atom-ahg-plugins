<?php
/**
 * DisplayTypeDetector - Automatic GLAM type detection
 */

use Illuminate\Database\Capsule\Manager as DB;

class DisplayTypeDetector
{
    protected static $levelToDomain = [
        // Archive (ISAD)
        'fonds' => 'archive',
        'subfonds' => 'archive',
        'series' => 'archive',
        'subseries' => 'archive',
        'file' => 'archive',
        'item' => 'archive',
        'piece' => 'archive',
        
        // Museum (Spectrum)
        'object' => 'museum',
        'specimen' => 'museum',
        'artefact' => 'museum',
        'artifact' => 'museum',
        
        // Gallery
        'artwork' => 'gallery',
        'painting' => 'gallery',
        'sculpture' => 'gallery',
        'drawing' => 'gallery',
        
        // Library
        'book' => 'library',
        'periodical' => 'library',
        'volume' => 'library',
        'pamphlet' => 'library',
        'monograph' => 'library',
        
        // DAM
        'photograph' => 'dam',
        'photo' => 'dam',
        'image' => 'dam',
        'negative' => 'dam',
        'album' => 'dam',
        'slide' => 'dam',
        
        // Universal
        'collection' => 'universal',
    ];

    /**
     * Detect type - checks cache first
     */
    public static function detect(int $objectId): string
    {
        if ($objectId <= 1) {
            return 'archive';
        }

        // Check if already configured
        $existing = DB::table('display_object_config')
            ->where('object_id', $objectId)
            ->value('object_type');
        
        if ($existing) {
            return $existing;
        }

        return self::detectAndSave($objectId);
    }

    /**
     * Force detection and save (for create/update)
     */
    public static function detectAndSave(int $objectId, bool $force = false): string
    {
        if ($objectId <= 1) {
            return 'archive';
        }

        // If force, delete existing
        if ($force) {
            DB::table('display_object_config')->where('object_id', $objectId)->delete();
        }

        // Get object data
        $object = DB::table('information_object as io')
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', $objectId)
            ->select('io.*', 'level.name as level_name')
            ->first();

        if (!$object) {
            return 'archive';
        }

        // Detect by level first, then parent, then events
        $type = self::detectByLevel($object->level_name)
            ?? self::detectByParent($object->parent_id)
            ?? self::detectByEvents($objectId)
            ?? 'archive';

        // Save detected type
        self::saveType($objectId, $type);

        return $type;
    }

    protected static function detectByLevel(?string $levelName): ?string
    {
        if (!$levelName) {
            return null;
        }
        $level = strtolower(trim($levelName));
        return self::$levelToDomain[$level] ?? null;
    }

    protected static function detectByParent(?int $parentId): ?string
    {
        if (!$parentId || $parentId <= 1) {
            return null;
        }
        
        // Check parent's type
        $parentType = DB::table('display_object_config')
            ->where('object_id', $parentId)
            ->value('object_type');
            
        if ($parentType && $parentType !== 'universal') {
            return $parentType;
        }

        // Try grandparent
        $grandparentId = DB::table('information_object')
            ->where('id', $parentId)
            ->value('parent_id');
            
        if ($grandparentId && $grandparentId > 1) {
            return DB::table('display_object_config')
                ->where('object_id', $grandparentId)
                ->value('object_type');
        }

        return null;
    }

    protected static function detectByEvents(int $objectId): ?string
    {
        $events = DB::table('event as e')
            ->join('term_i18n as t', function ($j) {
                $j->on('e.type_id', '=', 't.id')->where('t.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('e.object_id', $objectId)
            ->pluck('t.name')
            ->map(function($n) { return strtolower($n); })
            ->toArray();

        if (in_array('photographer', $events) || in_array('photography', $events)) return 'dam';
        if (in_array('artist', $events) || in_array('painter', $events)) return 'gallery';
        if (in_array('author', $events) || in_array('writer', $events)) return 'library';
        if (in_array('production', $events) || in_array('manufacturer', $events)) return 'museum';

        return null;
    }

    protected static function saveType(int $objectId, string $type): void
    {
        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            [
                'object_type' => $type, 
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => DB::raw('COALESCE(created_at, NOW())')
            ]
        );
    }

    /**
     * Get appropriate profile for object
     */
    public static function getProfile(int $objectId): ?object
    {
        $type = self::detect($objectId);

        // Check for specific assignment
        $profile = DB::table('display_object_profile as dop')
            ->join('display_profile as dp', 'dop.profile_id', '=', 'dp.id')
            ->join('display_profile_i18n as dpi', function($j) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('dop.object_id', $objectId)
            ->select('dp.*', 'dpi.name', 'dpi.description')
            ->first();

        if (!$profile) {
            // Get default for domain
            $profile = DB::table('display_profile as dp')
                ->join('display_profile_i18n as dpi', function($j) {
                    $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('dp.domain', $type)
                ->where('dp.is_default', 1)
                ->select('dp.*', 'dpi.name', 'dpi.description')
                ->first();
        }

        return $profile;
    }

    /**
     * Get type for an object (public accessor)
     */
    public static function getType(int $objectId): string
    {
        return self::detect($objectId);
    }
}
