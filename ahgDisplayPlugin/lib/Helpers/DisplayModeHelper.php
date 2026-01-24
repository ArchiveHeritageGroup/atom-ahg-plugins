<?php

declare(strict_types=1);

namespace AhgDisplay\Helpers;

use AhgDisplay\Services\DisplayModeService;

/**
 * Helper functions for display mode switching.
 *
 * Usage in templates:
 * use AhgDisplay\Helpers\DisplayModeHelper;
 * echo DisplayModeHelper::renderToolbar('informationobject', $totalCount);
 */
class DisplayModeHelper
{
    private static ?DisplayModeService $service = null;

    private static function getService(): DisplayModeService
    {
        if (null === self::$service) {
            self::$service = new DisplayModeService();
        }
        return self::$service;
    }

    /**
     * Render the display mode toolbar.
     * Returns HTML for the toolbar buttons.
     */
    public static function renderToolbar(string $module, int $totalCount = 0, array $options = []): string
    {
        $service = self::getService();
        $modes = $service->getModeMetas($module);

        if (count($modes) < 2) {
            return '';
        }

        $baseUrl = $options['base_url'] ?? '';
        return $service->renderToggleButtons($module, $baseUrl, $options['ajax'] ?? true);
    }

    /**
     * Get current display mode for module.
     */
    public static function getCurrentMode(string $module): string
    {
        return self::getService()->getCurrentMode($module);
    }

    /**
     * Get container CSS class for current mode.
     */
    public static function getContainerClass(string $module): string
    {
        $mode = self::getCurrentMode($module);
        return self::getService()->getContainerClass($mode);
    }

    /**
     * Get items per page for module.
     */
    public static function getItemsPerPage(string $module): int
    {
        return self::getService()->getItemsPerPage($module);
    }

    /**
     * Get mode metadata for templates to render their own UI.
     */
    public static function getModeMetas(string $module): array
    {
        return self::getService()->getModeMetas($module);
    }

    /**
     * Check if user can override display settings.
     */
    public static function canOverride(string $module): bool
    {
        return self::getService()->canOverride($module);
    }

    /**
     * Check if user has custom preference.
     */
    public static function hasCustomPreference(string $module): bool
    {
        return self::getService()->hasCustomPreference($module);
    }

    /**
     * Transform search/browse results for display.
     */
    public static function transformResults(array $results, string $module): array
    {
        $items = [];

        foreach ($results as $result) {
            $get = function ($key) use ($result) {
                if (is_array($result)) {
                    return $result[$key] ?? null;
                }
                if (is_object($result)) {
                    return $result->$key ?? null;
                }
                return null;
            };

            $items[] = [
                'id' => $get('id'),
                'slug' => $get('slug'),
                'title' => $get('title') ?? $get('authorized_form_of_name') ?? $get('name'),
                'reference_code' => $get('reference_code') ?? $get('identifier'),
                'dates' => $get('dates') ?? $get('date'),
                'level_of_description' => $get('level_of_description'),
                'scope_and_content' => $get('scope_and_content') ?? $get('description'),
                'thumbnail' => $get('thumbnail_path') ?? $get('thumbnail'),
                'thumbnail_large' => $get('reference_path') ?? $get('thumbnail_large'),
                'start_date' => $get('start_date'),
                'end_date' => $get('end_date'),
                'repository' => $get('repository'),
                'creator' => $get('creator'),
                'parent_id' => $get('parent_id'),
                'lft' => $get('lft'),
                'rgt' => $get('rgt'),
                'children' => $get('children') ?? [],
            ];
        }

        return $items;
    }

    /**
     * Build hierarchical tree from flat results.
     */
    public static function buildTree(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item['id']] = $item;
        }

        $tree = [];
        foreach ($indexed as $id => &$item) {
            if (!empty($item['parent_id']) && isset($indexed[$item['parent_id']])) {
                $indexed[$item['parent_id']]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }

        return $tree;
    }
}
