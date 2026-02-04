<?php

declare(strict_types=1);

namespace AhgDisplay\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Repository for managing user browse settings.
 *
 * Handles per-user preferences for browse interface including
 * GLAM browse toggle, sort preferences, and filter memory.
 */
class UserBrowseSettingsRepository
{
    protected string $table = 'user_browse_settings';

    /**
     * Get user's browse settings
     */
    public function getSettings(int $userId): array
    {
        $settings = DB::table($this->table)
            ->where('user_id', $userId)
            ->first();

        if ($settings) {
            $settings = (array) $settings;
            $settings['last_filters'] = $settings['last_filters']
                ? json_decode($settings['last_filters'], true)
                : [];
            return $settings;
        }

        return $this->getDefaultSettings($userId);
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(int $userId = 0): array
    {
        return [
            'id' => null,
            'user_id' => $userId,
            'use_glam_browse' => false,
            'default_sort_field' => 'updated_at',
            'default_sort_direction' => 'desc',
            'default_view' => 'list',
            'items_per_page' => 30,
            'show_facets' => true,
            'remember_filters' => true,
            'last_filters' => [],
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    /**
     * Check if user has GLAM browse enabled
     */
    public function useGlamBrowse(int $userId): bool
    {
        $settings = DB::table($this->table)
            ->where('user_id', $userId)
            ->first();

        return $settings ? (bool) $settings->use_glam_browse : false;
    }

    /**
     * Toggle GLAM browse for user
     */
    public function setGlamBrowse(int $userId, bool $enabled): bool
    {
        return $this->saveSettings($userId, ['use_glam_browse' => $enabled ? 1 : 0]);
    }

    /**
     * Save user browse settings
     */
    public function saveSettings(int $userId, array $data): bool
    {
        $existing = DB::table($this->table)
            ->where('user_id', $userId)
            ->first();

        // Prepare data
        $saveData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Map allowed fields
        $allowedFields = [
            'use_glam_browse', 'default_sort_field', 'default_sort_direction',
            'default_view', 'items_per_page', 'show_facets', 'remember_filters',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            }
        }

        // Handle last_filters specially
        if (isset($data['last_filters'])) {
            $saveData['last_filters'] = is_array($data['last_filters'])
                ? json_encode($data['last_filters'])
                : $data['last_filters'];
        }

        if ($existing) {
            return DB::table($this->table)
                ->where('id', $existing->id)
                ->update($saveData) >= 0;
        }

        $saveData['user_id'] = $userId;
        $saveData['created_at'] = date('Y-m-d H:i:s');
        return DB::table($this->table)->insert($saveData);
    }

    /**
     * Save last used filters for a user
     */
    public function saveLastFilters(int $userId, array $filters): bool
    {
        return $this->saveSettings($userId, ['last_filters' => $filters]);
    }

    /**
     * Get last used filters for a user
     */
    public function getLastFilters(int $userId): array
    {
        $settings = $this->getSettings($userId);
        return $settings['last_filters'] ?? [];
    }

    /**
     * Reset user settings to defaults
     */
    public function resetSettings(int $userId): bool
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->delete() > 0;
    }
}
