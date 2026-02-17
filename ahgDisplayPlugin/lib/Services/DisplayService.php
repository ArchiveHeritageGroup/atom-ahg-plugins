<?php
/**
 * DisplayService - Main service for GLAM display functionality
 */

namespace AhgDisplay\Services;

use AhgCore\Core\AhgDb;

require_once __DIR__ . '/DisplayTypeDetector.php';
require_once __DIR__ . '/DisplayRegistry.php';

class DisplayService
{
    private static ?self $instance = null;
    private ?DisplayRegistry $registry = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the display registry
     */
    public function getRegistry(): DisplayRegistry
    {
        if ($this->registry === null) {
            $this->registry = DisplayRegistry::getInstance();
        }
        return $this->registry;
    }

    /**
     * Get object data with auto-detected GLAM type and appropriate profile
     */
    public function getObjectDisplay(int $objectId): array
    {
        // Auto-detect type
        $type = \DisplayTypeDetector::detect($objectId);
        $profile = \DisplayTypeDetector::getProfile($objectId);

        // Get object data
        $object = $this->getObjectData($objectId);

        // Get extensions from registry
        $extensions = [];
        if ($object) {
            $extensions = [
                'actions' => $this->getRegistry()->getActions($object),
                'panels' => $this->getRegistry()->getPanels($object),
                'badges' => $this->getRegistry()->getBadges($object),
            ];
        }

        return [
            'object' => $object,
            'type' => $type,
            'profile' => $profile,
            'fields' => $this->getFieldsForProfile($profile),
            'extensions' => $extensions,
        ];
    }

    /**
     * Get object data
     */
    public function getObjectData(int $objectId): ?object
    {
        return AhgDb::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($j) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', $objectId)
            ->select('io.*', 'i18n.title', 'i18n.scope_and_content', 'i18n.extent_and_medium',
                'i18n.archival_history', 'i18n.acquisition', 'i18n.arrangement',
                'i18n.access_conditions', 'i18n.reproduction_conditions',
                'level.name as level_name')
            ->first();
    }

    /**
     * Get fields for a profile
     */
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

        return AhgDb::table('display_field as df')
            ->leftJoin('display_field_i18n as dfi', function ($j) {
                $j->on('df.id', '=', 'dfi.id')->where('dfi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereIn('df.code', $fieldCodes)
            ->select('df.*', 'dfi.name', 'dfi.help_text')
            ->orderByRaw('FIELD(df.code, "' . implode('","', $fieldCodes) . '")')
            ->get()
            ->toArray();
    }

    /**
     * Get levels
     */
    public function getLevels(?string $domain = null): array
    {
        $query = AhgDb::table('display_level as dl')
            ->leftJoin('display_level_i18n as dli', function ($j) {
                $j->on('dl.id', '=', 'dli.id')->where('dli.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('dl.*', 'dli.name', 'dli.description')
            ->orderBy('dl.sort_order');

        if ($domain) {
            $query->where('dl.domain', $domain);
        }

        return $query->get()->toArray();
    }

    /**
     * Get collection types
     */
    public function getCollectionTypes(): array
    {
        return AhgDb::table('display_collection_type as dct')
            ->leftJoin('display_collection_type_i18n as dcti', function ($j) {
                $j->on('dct.id', '=', 'dcti.id')->where('dcti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('dct.*', 'dcti.name', 'dcti.description')
            ->orderBy('dct.sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Set object type
     */
    public function setObjectType(int $objectId, string $type): void
    {
        AhgDb::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => $type, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Set object type recursively for children
     */
    public function setObjectTypeRecursive(int $parentId, string $type): int
    {
        $children = AhgDb::table('information_object')
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

    /**
     * Assign profile to object
     */
    public function assignProfile(int $objectId, int $profileId, string $context = 'default', bool $primary = false): void
    {
        AhgDb::table('display_object_profile')->updateOrInsert(
            ['object_id' => $objectId, 'profile_id' => $profileId, 'context' => $context],
            ['is_primary' => $primary ? 1 : 0]
        );
    }

    /**
     * Get actions for an entity
     */
    public function getActionsForEntity(object $entity, array $context = []): array
    {
        return $this->getRegistry()->getActions($entity, $context);
    }

    /**
     * Get panels for an entity
     */
    public function getPanelsForEntity(object $entity, array $context = []): array
    {
        return $this->getRegistry()->getPanels($entity, $context);
    }

    /**
     * Get badges for an entity
     */
    public function getBadgesForEntity(object $entity, array $context = []): array
    {
        return $this->getRegistry()->getBadges($entity, $context);
    }

    /**
     * Render actions HTML
     */
    public function renderActions(object $entity, array $context = []): string
    {
        return $this->getRegistry()->renderActions($entity, $context);
    }

    /**
     * Render badges HTML
     */
    public function renderBadges(object $entity, array $context = []): string
    {
        return $this->getRegistry()->renderBadges($entity, $context);
    }
}
