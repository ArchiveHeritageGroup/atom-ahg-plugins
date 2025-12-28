<?php

// plugins/ahgAuditTrailPlugin/lib/Models/AuditAuthentication.php

namespace AtoM\Framework\Plugins\AuditTrail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditAuthentication extends Model
{
    protected $table = 'ahg_audit_authentication';
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'event_type', 'user_id', 'username', 'ip_address',
        'user_agent', 'session_id', 'status', 'failure_reason',
        'failed_attempts', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'failed_attempts' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public const EVENT_LOGIN = 'login';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_FAILED_LOGIN = 'failed_login';
    public const EVENT_PASSWORD_CHANGE = 'password_change';
    public const EVENT_PASSWORD_RESET = 'password_reset';
    public const EVENT_ACCOUNT_LOCKED = 'account_locked';
}