<?php

declare(strict_types=1);

/**
 * DisplayRightsExtension - Bridge between DisplayService and RightsService
 * 
 * Adds rights data to DisplayService::prepareForDisplay() output
 * 
 * Usage in DisplayService::prepareForDisplay():
 *   $data['rights'] = DisplayRightsExtension::getRightsData($objectId, $userId);
 * 
 * @package ahgRightsPlugin
 */
class DisplayRightsExtension
{
    /**
     * Get all rights data for display
     *
     * @param int      $objectId Object ID
     * @param int|null $userId   Current user ID (for access checks)
     * @param bool     $canEdit  Whether user can edit rights
     *
     * @return array Rights data for display
     */
    public static function getRightsData(int $objectId, ?int $userId = null, bool $canEdit = false): array
    {
        // Load RightsService if not already loaded
        if (!class_exists('RightsService')) {
            $rightsPluginPath = sfConfig::get('sf_plugins_dir') . '/ahgRightsPlugin/lib/Service/RightsService.php';
            if (file_exists($rightsPluginPath)) {
                require_once $rightsPluginPath;
            } else {
                // Rights plugin not installed - return empty data
                return self::getEmptyRightsData();
            }
        }

        try {
            $service = RightsService::getInstance();

            return [
                'records' => $service->getRightsForObject($objectId),
                'embargo' => $service->getEmbargo($objectId),
                'tk_labels' => $service->getTkLabelsForObject($objectId),
                'orphan_work' => $service->getOrphanWork($objectId),
                'access_check' => $service->checkAccess($objectId, 'information_object', $userId),
                'can_edit' => $canEdit,
            ];
        } catch (Exception $e) {
            // Log error but don't break display
            error_log('DisplayRightsExtension error: ' . $e->getMessage());
            return self::getEmptyRightsData();
        }
    }

    /**
     * Get empty rights data structure
     *
     * @return array Empty rights data
     */
    public static function getEmptyRightsData(): array
    {
        return [
            'records' => [],
            'embargo' => null,
            'tk_labels' => [],
            'orphan_work' => null,
            'access_check' => ['allowed' => true, 'restrictions' => []],
            'can_edit' => false,
        ];
    }

    /**
     * Check if object has any rights restrictions
     *
     * @param int $objectId Object ID
     *
     * @return bool True if restricted
     */
    public static function isRestricted(int $objectId): bool
    {
        $data = self::getRightsData($objectId);
        return !$data['access_check']['allowed'] || $data['embargo'] !== null;
    }

    /**
     * Get rights summary for search results (minimal data)
     *
     * @param int $objectId Object ID
     *
     * @return array Minimal rights summary
     */
    public static function getRightsSummary(int $objectId): array
    {
        if (!class_exists('RightsService')) {
            return ['has_rights' => false, 'is_embargoed' => false, 'has_tk_labels' => false];
        }

        try {
            $service = RightsService::getInstance();

            // Just check existence, don't load full data
            $hasRights = DB::table('rights_record')
                ->where('object_id', $objectId)
                ->exists();

            $isEmbargoed = $service->isEmbargoed($objectId);

            $hasTkLabels = DB::table('rights_object_tk_label')
                ->where('object_id', $objectId)
                ->exists();

            return [
                'has_rights' => $hasRights,
                'is_embargoed' => $isEmbargoed,
                'has_tk_labels' => $hasTkLabels,
            ];
        } catch (Exception $e) {
            return ['has_rights' => false, 'is_embargoed' => false, 'has_tk_labels' => false];
        }
    }

    /**
     * Add rights action to available actions if appropriate
     *
     * @param array  $actions    Current actions array
     * @param int    $objectId   Object ID
     * @param bool   $canEdit    Can user edit
     *
     * @return array Updated actions array
     */
    public static function addRightsAction(array $actions, int $objectId, bool $canEdit = false): array
    {
        // Always add rights action for editors, or if rights exist
        if ($canEdit || self::isRestricted($objectId)) {
            // Add after 'view' if present, otherwise at beginning
            $viewIndex = array_search('view', $actions);
            if ($viewIndex !== false) {
                array_splice($actions, $viewIndex + 1, 0, ['rights']);
            } else {
                array_unshift($actions, 'rights');
            }
        }

        return $actions;
    }
}
