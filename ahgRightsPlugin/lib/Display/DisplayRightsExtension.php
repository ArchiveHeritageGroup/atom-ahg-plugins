<?php

declare(strict_types=1);

use AhgCore\Contracts\DisplayActionProviderInterface;
use AhgCore\Core\AhgDb;

/**
 * DisplayRightsExtension - Display provider for Rights plugin
 *
 * Implements DisplayActionProviderInterface to provide rights-related
 * actions, panels, and badges to the display system.
 *
 * @package ahgDisplayPlugin
 */
class DisplayRightsExtension implements DisplayActionProviderInterface
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getProviderId(): string
    {
        return 'ahgRightsPlugin';
    }

    public function supportsEntity(string $entityType): bool
    {
        return in_array($entityType, [
            'QubitInformationObject',
            'stdClass', // For database results
        ]);
    }

    public function getConfig(): array
    {
        return [
            'provides' => ['actions', 'panels', 'badges'],
            'requires' => ['ahgRightsPlugin'],
        ];
    }

    public function getActions(object $entity, array $context = []): array
    {
        $objectId = $entity->id ?? null;
        if (!$objectId) {
            return [];
        }

        $canEdit = $context['can_edit'] ?? false;
        $rightsData = $this->getRightsData($objectId);

        $actions = [];

        // Add edit rights action for editors
        if ($canEdit) {
            $actions[] = [
                'id' => 'edit_rights',
                'label' => 'Rights',
                'url' => '/rights/' . $objectId . '/edit',
                'icon' => 'fa-gavel',
                'class' => 'btn btn-outline-secondary btn-sm',
                'order' => 200,
            ];
        }

        // Add view embargo action if embargoed
        if ($rightsData['embargo']) {
            $actions[] = [
                'id' => 'view_embargo',
                'label' => 'Embargo Details',
                'url' => '/embargo/' . $objectId,
                'icon' => 'fa-clock',
                'class' => 'btn btn-warning btn-sm',
                'order' => 201,
            ];
        }

        return $actions;
    }

    public function getPanels(object $entity, array $context = []): array
    {
        $objectId = $entity->id ?? null;
        if (!$objectId) {
            return [];
        }

        $rightsData = $this->getRightsData($objectId);

        // Only show panel if there are rights records
        if (empty($rightsData['records']) && !$rightsData['embargo'] && empty($rightsData['tk_labels'])) {
            return [];
        }

        return [
            [
                'id' => 'rights_panel',
                'title' => 'Rights Information',
                'template' => 'ahgRightsPlugin/partials/rights_panel',
                'position' => 'sidebar',
                'order' => 50,
                'collapsible' => true,
                'collapsed' => false,
                'data' => $rightsData,
            ],
        ];
    }

    public function getBadges(object $entity, array $context = []): array
    {
        $objectId = $entity->id ?? null;
        if (!$objectId) {
            return [];
        }

        $badges = [];
        $summary = $this->getRightsSummary($objectId);

        // Embargo badge
        if ($summary['is_embargoed']) {
            $badges[] = [
                'id' => 'embargo_badge',
                'label' => 'Embargoed',
                'class' => 'badge bg-warning text-dark',
                'icon' => 'fa-clock',
                'tooltip' => 'This record is under embargo restrictions',
                'order' => 10,
            ];
        }

        // Restricted access badge
        if ($summary['has_rights']) {
            $badges[] = [
                'id' => 'rights_badge',
                'label' => 'Rights Applied',
                'class' => 'badge bg-info',
                'icon' => 'fa-gavel',
                'tooltip' => 'This record has rights statements',
                'order' => 20,
            ];
        }

        // TK Labels badge
        if ($summary['has_tk_labels']) {
            $badges[] = [
                'id' => 'tk_labels_badge',
                'label' => 'TK Labels',
                'class' => 'badge bg-success',
                'icon' => 'fa-tags',
                'tooltip' => 'Traditional Knowledge labels applied',
                'order' => 30,
            ];
        }

        return $badges;
    }

    /**
     * Get all rights data for display
     */
    public function getRightsData(int $objectId, ?int $userId = null, bool $canEdit = false): array
    {
        // Check if RightsService exists
        if (!class_exists('RightsService')) {
            $rightsPluginPath = sfConfig::get('sf_plugins_dir') . '/ahgRightsPlugin/lib/Service/RightsService.php';
            if (file_exists($rightsPluginPath)) {
                require_once $rightsPluginPath;
            } else {
                return $this->getEmptyRightsData();
            }
        }

        try {
            $service = \RightsService::getInstance();

            return [
                'records' => $service->getRightsForObject($objectId),
                'embargo' => $service->getEmbargo($objectId),
                'tk_labels' => $service->getTkLabelsForObject($objectId),
                'orphan_work' => $service->getOrphanWork($objectId),
                'access_check' => $service->checkAccess($objectId, 'information_object', $userId),
                'can_edit' => $canEdit,
            ];
        } catch (\Exception $e) {
            error_log('DisplayRightsExtension error: ' . $e->getMessage());
            return $this->getEmptyRightsData();
        }
    }

    /**
     * Get empty rights data structure
     */
    public function getEmptyRightsData(): array
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
     * Get rights summary for search results
     */
    public function getRightsSummary(int $objectId): array
    {
        try {
            $hasRights = AhgDb::table('rights_record')
                ->where('object_id', $objectId)
                ->exists();

            $isEmbargoed = AhgDb::table('embargo')
                ->where('object_id', $objectId)
                ->where('end_date', '>', date('Y-m-d'))
                ->where('is_active', 1)
                ->exists();

            $hasTkLabels = AhgDb::table('rights_object_tk_label')
                ->where('object_id', $objectId)
                ->exists();

            return [
                'has_rights' => $hasRights,
                'is_embargoed' => $isEmbargoed,
                'has_tk_labels' => $hasTkLabels,
            ];
        } catch (\Exception $e) {
            return ['has_rights' => false, 'is_embargoed' => false, 'has_tk_labels' => false];
        }
    }

    /**
     * Check if object has any rights restrictions
     */
    public static function isRestricted(int $objectId): bool
    {
        $instance = self::getInstance();
        $data = $instance->getRightsData($objectId);
        return !$data['access_check']['allowed'] || $data['embargo'] !== null;
    }
}
