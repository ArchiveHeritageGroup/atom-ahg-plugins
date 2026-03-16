<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class DropdownService
{
    protected static array $cache = [];

    /**
     * Get all active dropdown values for a group, ordered by sort_order.
     *
     * @return array Array of objects with value, label, badge_color, sort_order
     */
    public static function getGroup(string $group): array
    {
        if (isset(self::$cache[$group])) {
            return self::$cache[$group];
        }

        $rows = DB::table('registry_dropdown')
            ->where('dropdown_group', $group)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->all();

        self::$cache[$group] = $rows;

        return $rows;
    }

    /**
     * Get dropdown values as a simple value => label associative array.
     */
    public static function getOptions(string $group): array
    {
        $options = [];
        foreach (self::getGroup($group) as $row) {
            $options[$row->value] = $row->label;
        }

        return $options;
    }

    /**
     * Get dropdown values as value => badge_color associative array.
     */
    public static function getBadgeColors(string $group): array
    {
        $colors = [];
        foreach (self::getGroup($group) as $row) {
            if ($row->badge_color) {
                $colors[$row->value] = $row->badge_color;
            }
        }

        return $colors;
    }

    /**
     * Get label for a specific value within a group.
     */
    public static function getLabel(string $group, string $value): string
    {
        $options = self::getOptions($group);

        return $options[$value] ?? ucfirst(str_replace('_', ' ', $value));
    }

    /**
     * Clear the in-memory cache (useful after admin edits).
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
