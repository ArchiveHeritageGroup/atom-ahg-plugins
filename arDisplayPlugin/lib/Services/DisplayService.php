<?php
/**
 * DisplayService - Main service for GLAM display functionality
 */

use Illuminate\Database\Capsule\Manager as DB;

require_once __DIR__ . '/DisplayTypeDetector.php';

class DisplayService
{
    /**
     * Get object data with auto-detected GLAM type and appropriate profile
     */
    public function getObjectDisplay(int $objectId): array
    {
        // Auto-detect type
        $type = DisplayTypeDetector::detect($objectId);
        $profile = DisplayTypeDetector::getProfile($objectId);

        // Get object data
        $object = $this->getObjectData($objectId);

        return [
            'object' => $object,
            'type' => $type,
            'profile' => $profile,
            'fields' => $this->getFieldsForProfile($profile),
        ];
    }

    public function getObjectData(int $objectId): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select('io.*', 'i18n.title', 'i18n.scope_and_content', 'i18n.extent_and_medium',
                'i18n.archival_history', 'i18n.acquisition', 'i18n.arrangement',
                'i18n.access_conditions', 'i18n.reproduction_conditions',
                'level.name as level_name')
            ->first();
    }

    public function getFieldsForProfile(?object $profile): array
    {
        if (!$profile) {
            return [];
        }

        $fieldCodes = array_merge(
            json_decode($profile->identity_fields ?? '[]', true) ?: [],
            json_decode($profile->description_fields ?? '[]', true) ?: [],
            json_decode($profile->context_fields ?? '[]', true) ?: [],
            json_decode($profile->access_fields ?? '[]', true) ?: []
        );

        if (empty($fieldCodes)) {
            return [];
        }

        return DB::table('display_field as df')
            ->leftJoin('display_field_i18n as dfi', function ($j) {
                $j->on('df.id', '=', 'dfi.id')->where('dfi.culture', '=', 'en');
            })
            ->whereIn('df.code', $fieldCodes)
            ->select('df.*', 'dfi.name', 'dfi.help_text')
            ->orderByRaw('FIELD(df.code, "' . implode('","', $fieldCodes) . '")')
            ->get()
            ->toArray();
    }

    public function getLevels(?string $domain = null): array
    {
        $query = DB::table('display_level as dl')
            ->leftJoin('display_level_i18n as dli', function ($j) {
                $j->on('dl.id', '=', 'dli.id')->where('dli.culture', '=', 'en');
            })
            ->select('dl.*', 'dli.name', 'dli.description')
            ->orderBy('dl.sort_order');

        if ($domain) {
            $query->where('dl.domain', $domain);
        }

        return $query->get()->toArray();
    }

    public function getCollectionTypes(): array
    {
        return DB::table('display_collection_type as dct')
            ->leftJoin('display_collection_type_i18n as dcti', function ($j) {
                $j->on('dct.id', '=', 'dcti.id')->where('dcti.culture', '=', 'en');
            })
            ->select('dct.*', 'dcti.name', 'dcti.description')
            ->orderBy('dct.sort_order')
            ->get()
            ->toArray();
    }

    public function setObjectType(int $objectId, string $type): void
    {
        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => $type, 'updated_at' => now()]
        );
    }

    public function setObjectTypeRecursive(int $parentId, string $type): int
    {
        $children = DB::table('information_object')
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->toArray();

        $count = 0;
        foreach ($children as $childId) {
            $this->setObjectType($childId, $type);
            $count++;
            $count += $this->setObjectTypeRecursive($childId, $type);
        }

        return $count;
    }

    public function assignProfile(int $objectId, int $profileId, string $context = 'default', bool $primary = false): void
    {
        DB::table('display_object_profile')->updateOrInsert(
            ['object_id' => $objectId, 'profile_id' => $profileId, 'context' => $context],
            ['is_primary' => $primary ? 1 : 0]
        );
    }
}
