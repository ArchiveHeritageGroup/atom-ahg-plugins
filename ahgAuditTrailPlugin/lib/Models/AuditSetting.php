<?php

// plugins/ahgAuditTrailPlugin/lib/Models/AuditSetting.php

namespace AtoM\Framework\Plugins\AuditTrail\Models;

use Illuminate\Database\Eloquent\Model;

class AuditSetting extends Model
{
    protected $table = 'ahg_audit_settings';

    protected $fillable = ['setting_key', 'setting_value', 'setting_type', 'description'];

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->setting_type) {
            'boolean' => (bool) $this->setting_value,
            'integer' => (int) $this->setting_value,
            'float' => (float) $this->setting_value,
            'array', 'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }
}