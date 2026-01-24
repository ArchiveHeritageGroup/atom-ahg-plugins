<?php

declare(strict_types=1);

namespace AhgDisplay\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Repository for managing global display mode settings.
 */
class GlobalDisplaySettingsRepository
{
    protected string $table = 'display_mode_global';
    protected string $auditTable = 'display_mode_audit';
    protected array $allModes = ['tree', 'grid', 'gallery', 'list', 'timeline'];

    public function getGlobalSettings(string $module): ?array
    {
        $settings = DB::table($this->table)
            ->where('module', $module)
            ->where('is_active', 1)
            ->first();

        if (!$settings) {
            return null;
        }

        $result = (array) $settings;
        $result['available_modes'] = json_decode($result['available_modes'] ?? '[]', true);
        return $result;
    }

    public function getAllGlobalSettings(bool $activeOnly = true): Collection
    {
        $query = DB::table($this->table);
        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('module')->get()->map(function ($row) {
            $arr = (array) $row;
            $arr['available_modes'] = json_decode($arr['available_modes'] ?? '[]', true);
            return $arr;
        });
    }

    public function saveGlobalSettings(string $module, array $settings, ?int $changedBy = null): bool
    {
        $existing = $this->getGlobalSettings($module);
        $data = ['module' => $module, 'updated_at' => date('Y-m-d H:i:s')];

        if (isset($settings['display_mode']) && in_array($settings['display_mode'], $this->allModes, true)) {
            $data['display_mode'] = $settings['display_mode'];
        }
        if (isset($settings['items_per_page'])) {
            $data['items_per_page'] = max(10, min(100, (int) $settings['items_per_page']));
        }
        if (isset($settings['sort_field'])) {
            $data['sort_field'] = $settings['sort_field'];
        }
        if (isset($settings['sort_direction']) && in_array($settings['sort_direction'], ['asc', 'desc'], true)) {
            $data['sort_direction'] = $settings['sort_direction'];
        }
        if (isset($settings['show_thumbnails'])) {
            $data['show_thumbnails'] = (int) (bool) $settings['show_thumbnails'];
        }
        if (isset($settings['show_descriptions'])) {
            $data['show_descriptions'] = (int) (bool) $settings['show_descriptions'];
        }
        if (isset($settings['card_size']) && in_array($settings['card_size'], ['small', 'medium', 'large'], true)) {
            $data['card_size'] = $settings['card_size'];
        }
        if (isset($settings['available_modes']) && is_array($settings['available_modes'])) {
            $validModes = array_intersect($settings['available_modes'], $this->allModes);
            $data['available_modes'] = json_encode(array_values($validModes));
        }
        if (isset($settings['allow_user_override'])) {
            $data['allow_user_override'] = (int) (bool) $settings['allow_user_override'];
        }
        if (isset($settings['is_active'])) {
            $data['is_active'] = (int) (bool) $settings['is_active'];
        }

        if ($existing) {
            $success = DB::table($this->table)->where('module', $module)->update($data) >= 0;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $success = DB::table($this->table)->insert($data);
        }

        if ($success) {
            $this->logAudit(null, $module, $existing ? 'update' : 'create', $existing, $data, 'global', $changedBy);
        }
        return $success;
    }

    public function getAvailableModes(string $module): array
    {
        $settings = $this->getGlobalSettings($module);
        if ($settings && !empty($settings['available_modes'])) {
            return $settings['available_modes'];
        }

        $defaults = [
            'informationobject' => ['tree', 'grid', 'list', 'timeline'],
            'actor' => ['grid', 'list'],
            'repository' => ['grid', 'list'],
            'digitalobject' => ['grid', 'gallery', 'list'],
            'library' => ['grid', 'list'],
            'gallery' => ['grid', 'gallery', 'list'],
            'dam' => ['grid', 'gallery', 'list'],
            'search' => ['grid', 'list'],
        ];
        return $defaults[$module] ?? ['grid', 'list'];
    }

    public function isUserOverrideAllowed(string $module): bool
    {
        $settings = $this->getGlobalSettings($module);
        return $settings ? (bool) $settings['allow_user_override'] : true;
    }

    public function resetToDefaults(string $module, ?int $changedBy = null): bool
    {
        $existing = $this->getGlobalSettings($module);
        $defaults = [
            'informationobject' => ['display_mode' => 'list', 'items_per_page' => 30],
            'actor' => ['display_mode' => 'list', 'items_per_page' => 30],
            'repository' => ['display_mode' => 'grid', 'items_per_page' => 20],
            'digitalobject' => ['display_mode' => 'grid', 'items_per_page' => 24],
            'library' => ['display_mode' => 'list', 'items_per_page' => 30],
            'gallery' => ['display_mode' => 'gallery', 'items_per_page' => 12],
            'dam' => ['display_mode' => 'grid', 'items_per_page' => 24],
            'search' => ['display_mode' => 'list', 'items_per_page' => 30],
        ];

        $default = $defaults[$module] ?? ['display_mode' => 'list', 'items_per_page' => 30];
        $default['allow_user_override'] = 1;
        $default['show_thumbnails'] = 1;
        $default['show_descriptions'] = 1;
        $default['card_size'] = 'medium';

        return $this->saveGlobalSettings($module, $default, $changedBy);
    }

    protected function logAudit(?int $userId, string $module, string $action, ?array $oldValue, ?array $newValue, string $scope, ?int $changedBy): void
    {
        try {
            DB::table($this->auditTable)->insert([
                'user_id' => $userId,
                'module' => $module,
                'action' => $action,
                'old_value' => $oldValue ? json_encode($oldValue) : null,
                'new_value' => $newValue ? json_encode($newValue) : null,
                'scope' => $scope,
                'changed_by' => $changedBy,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log('Display mode audit log failed: ' . $e->getMessage());
        }
    }

    public function getAuditLog(array $filters = [], int $limit = 100): Collection
    {
        $query = DB::table($this->auditTable)->orderBy('created_at', 'desc')->limit($limit);

        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (!empty($filters['scope'])) {
            $query->where('scope', $filters['scope']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        return $query->get();
    }
}
