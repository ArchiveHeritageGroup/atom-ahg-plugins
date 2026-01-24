<?php

declare(strict_types=1);

namespace AhgDisplay\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Repository for managing user display preferences.
 *
 * Implements fallback chain: User Preference → Global Setting → Hardcoded Default
 */
class DisplayPreferenceRepository
{
    protected string $table = 'user_display_preference';
    protected string $globalTable = 'display_mode_global';
    protected string $auditTable = 'display_mode_audit';
    protected array $validModes = ['tree', 'grid', 'gallery', 'list', 'timeline'];
    protected array $validSizes = ['small', 'medium', 'large'];
    protected ?GlobalDisplaySettingsRepository $globalRepo = null;

    protected function getGlobalRepo(): GlobalDisplaySettingsRepository
    {
        if (null === $this->globalRepo) {
            $this->globalRepo = new GlobalDisplaySettingsRepository();
        }
        return $this->globalRepo;
    }

    public function getPreference(int $userId, string $module): array
    {
        $userPref = $this->getUserPreference($userId, $module);
        if ($userPref && $userPref['is_custom']) {
            if ($this->getGlobalRepo()->isUserOverrideAllowed($module)) {
                $userPref['_source'] = 'user';
                return $userPref;
            }
        }

        $globalSettings = $this->getGlobalRepo()->getGlobalSettings($module);
        if ($globalSettings) {
            $globalSettings['_source'] = 'global';
            return $globalSettings;
        }

        $defaults = $this->getDefaultPreference($module);
        $defaults['_source'] = 'default';
        return $defaults;
    }

    public function getUserPreference(int $userId, string $module): ?array
    {
        $preference = DB::table($this->table)
            ->where('user_id', $userId)
            ->where('module', $module)
            ->first();

        return $preference ? (array) $preference : null;
    }

    public function getDefaultPreference(string $module): array
    {
        $moduleDefaults = [
            'informationobject' => ['display_mode' => 'list', 'items_per_page' => 30],
            'actor' => ['display_mode' => 'list', 'items_per_page' => 30],
            'repository' => ['display_mode' => 'grid', 'items_per_page' => 20],
            'digitalobject' => ['display_mode' => 'grid', 'items_per_page' => 24],
            'library' => ['display_mode' => 'list', 'items_per_page' => 30],
            'gallery' => ['display_mode' => 'gallery', 'items_per_page' => 12],
            'dam' => ['display_mode' => 'grid', 'items_per_page' => 24],
            'search' => ['display_mode' => 'list', 'items_per_page' => 30],
        ];

        $defaults = $moduleDefaults[$module] ?? ['display_mode' => 'list', 'items_per_page' => 30];
        return array_merge([
            'id' => null,
            'user_id' => 0,
            'module' => $module,
            'sort_field' => 'updated_at',
            'sort_direction' => 'desc',
            'show_thumbnails' => true,
            'show_descriptions' => true,
            'card_size' => 'medium',
            'is_custom' => false,
            'created_at' => null,
            'updated_at' => null,
        ], $defaults);
    }

    public function savePreference(int $userId, string $module, array $data): bool
    {
        if (!$this->getGlobalRepo()->isUserOverrideAllowed($module)) {
            return false;
        }

        $existing = $this->getUserPreference($userId, $module);
        $availableModes = $this->getGlobalRepo()->getAvailableModes($module);

        if (isset($data['display_mode']) && !in_array($data['display_mode'], $availableModes, true)) {
            $data['display_mode'] = $availableModes[0] ?? 'list';
        }
        if (isset($data['card_size']) && !in_array($data['card_size'], $this->validSizes, true)) {
            $data['card_size'] = 'medium';
        }
        if (isset($data['items_per_page'])) {
            $data['items_per_page'] = max(10, min(100, (int) $data['items_per_page']));
        }

        $saveData = array_merge([
            'user_id' => $userId,
            'module' => $module,
            'is_custom' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        if ($existing) {
            unset($saveData['created_at']);
            $success = DB::table($this->table)->where('id', $existing['id'])->update($saveData) >= 0;
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $success = DB::table($this->table)->insert($saveData);
        }

        if ($success) {
            $this->logAudit($userId, $module, $existing ? 'update' : 'create', $existing, $saveData);
        }
        return $success;
    }

    public function setDisplayMode(int $userId, string $module, string $mode): bool
    {
        $availableModes = $this->getGlobalRepo()->getAvailableModes($module);
        if (!in_array($mode, $availableModes, true)) {
            return false;
        }
        return $this->savePreference($userId, $module, ['display_mode' => $mode]);
    }

    public function resetToGlobal(int $userId, string $module): bool
    {
        $existing = $this->getUserPreference($userId, $module);
        if (!$existing) {
            return true;
        }

        $deleted = DB::table($this->table)
            ->where('user_id', $userId)
            ->where('module', $module)
            ->delete();

        if ($deleted) {
            $this->logAudit($userId, $module, 'reset', $existing, null);
        }
        return $deleted > 0;
    }

    public function resetAllUserPreferences(int $userId): int
    {
        return DB::table($this->table)->where('user_id', $userId)->delete();
    }

    public function getAllUserPreferences(int $userId): Collection
    {
        return DB::table($this->table)->where('user_id', $userId)->get();
    }

    public function hasCustomPreference(int $userId, string $module): bool
    {
        $pref = $this->getUserPreference($userId, $module);
        return $pref && $pref['is_custom'];
    }

    public function getAvailableModes(): array
    {
        return [
            'tree' => [
                'name' => 'Hierarchy',
                'icon' => 'bi-diagram-3',
                'fa_icon' => 'fa-sitemap',
                'description' => 'Tree view showing parent-child relationships',
            ],
            'grid' => [
                'name' => 'Grid',
                'icon' => 'bi-grid-3x3-gap',
                'fa_icon' => 'fa-th',
                'description' => 'Thumbnail grid with cards',
            ],
            'gallery' => [
                'name' => 'Gallery',
                'icon' => 'bi-images',
                'fa_icon' => 'fa-image',
                'description' => 'Large images for visual browsing',
            ],
            'list' => [
                'name' => 'List',
                'icon' => 'bi-list-ul',
                'fa_icon' => 'fa-list',
                'description' => 'Compact table/list view',
            ],
            'timeline' => [
                'name' => 'Timeline',
                'icon' => 'bi-clock-history',
                'fa_icon' => 'fa-history',
                'description' => 'Chronological timeline view',
            ],
        ];
    }

    public function getModuleModes(string $module): array
    {
        return $this->getGlobalRepo()->getAvailableModes($module);
    }

    protected function logAudit(int $userId, string $module, string $action, ?array $oldValue, ?array $newValue): void
    {
        try {
            DB::table($this->auditTable)->insert([
                'user_id' => $userId,
                'module' => $module,
                'action' => $action,
                'old_value' => $oldValue ? json_encode($oldValue) : null,
                'new_value' => $newValue ? json_encode($newValue) : null,
                'scope' => 'user',
                'changed_by' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log('Display mode audit log failed: ' . $e->getMessage());
        }
    }
}
