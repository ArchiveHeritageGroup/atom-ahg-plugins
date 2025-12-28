<?php

// plugins/ahgAuditTrailPlugin/lib/Repositories/AuditSettingsRepository.php

namespace AtoM\Framework\Plugins\AuditTrail\Repositories;

use AtoM\Framework\Plugins\AuditTrail\Models\AuditSetting;
use Illuminate\Support\Collection;

class AuditSettingsRepository
{
    protected static array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $setting = AuditSetting::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        self::$cache[$key] = $setting->typed_value;
        return self::$cache[$key];
    }

    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $type = $type ?? (is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'));
        $stringValue = is_array($value) ? json_encode($value) : ($value ? '1' : '0');
        
        AuditSetting::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $stringValue, 'setting_type' => $type]
        );
        
        self::$cache[$key] = $value;
    }

    public function all(): Collection
    {
        return AuditSetting::all();
    }

    public function isEnabled(string $feature): bool
    {
        return (bool) $this->get($feature, false);
    }
}