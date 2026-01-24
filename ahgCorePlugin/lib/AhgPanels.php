<?php

namespace AhgCore;

/**
 * AHG Panels - Record-page panel registry.
 *
 * Allows plugins to add panels to record pages without duplicating
 * module actions. Panels are rendered via hooks in templates.
 *
 * Usage:
 *   // Register a panel
 *   AhgPanels::register('informationobject', 'iiif_viewer', [
 *       'title' => 'IIIF Viewer',
 *       'partial' => 'ahgIiifPlugin/iiifViewer',
 *       'position' => 'sidebar',
 *       'weight' => 10,
 *       'condition' => function($record) { return $record->hasDigitalObject(); }
 *   ]);
 *
 *   // In template: render panels for a position
 *   <?php foreach (AhgPanels::forPosition('informationobject', 'sidebar', $resource) as $panel): ?>
 *       <?php include_partial($panel['partial'], ['resource' => $resource]); ?>
 *   <?php endforeach; ?>
 */
class AhgPanels
{
    private static array $panels = [];

    /**
     * Register a panel for a record type.
     *
     * @param string $recordType Record type (informationobject, actor, repository, etc.)
     * @param string $id         Unique panel ID
     * @param array  $config     Panel configuration:
     *                           - title: Panel title
     *                           - partial: Partial template path
     *                           - position: 'sidebar', 'main', 'header', 'footer'
     *                           - weight: Sort order (lower = earlier)
     *                           - condition: Optional callback to check if panel should show
     *                           - provider: Plugin providing this panel
     */
    public static function register(string $recordType, string $id, array $config): void
    {
        if (!isset(self::$panels[$recordType])) {
            self::$panels[$recordType] = [];
        }

        $config['id'] = $id;
        $config['weight'] = $config['weight'] ?? 50;
        $config['position'] = $config['position'] ?? 'sidebar';

        self::$panels[$recordType][$id] = $config;
    }

    /**
     * Get panels for a record type and position.
     *
     * @param string $recordType Record type
     * @param string $position   Position (sidebar, main, header, footer)
     * @param mixed  $record     The record object (for condition checks)
     * @return array Panels sorted by weight
     */
    public static function forPosition(string $recordType, string $position, $record = null): array
    {
        if (!isset(self::$panels[$recordType])) {
            return [];
        }

        $panels = array_filter(self::$panels[$recordType], function ($panel) use ($position, $record) {
            if ($panel['position'] !== $position) {
                return false;
            }

            // Check condition if provided
            if (isset($panel['condition']) && is_callable($panel['condition'])) {
                return $panel['condition']($record);
            }

            return true;
        });

        // Sort by weight
        usort($panels, fn($a, $b) => ($a['weight'] ?? 50) <=> ($b['weight'] ?? 50));

        return $panels;
    }

    /**
     * Get all panels for a record type.
     */
    public static function forRecordType(string $recordType): array
    {
        return self::$panels[$recordType] ?? [];
    }

    /**
     * Check if any panels are registered for a position.
     */
    public static function hasForPosition(string $recordType, string $position): bool
    {
        return count(self::forPosition($recordType, $position)) > 0;
    }

    /**
     * Remove a panel.
     */
    public static function remove(string $recordType, string $id): void
    {
        unset(self::$panels[$recordType][$id]);
    }

    /**
     * Standard positions.
     */
    public const POSITION_SIDEBAR = 'sidebar';
    public const POSITION_MAIN = 'main';
    public const POSITION_HEADER = 'header';
    public const POSITION_FOOTER = 'footer';
    public const POSITION_ACTIONS = 'actions';
}
