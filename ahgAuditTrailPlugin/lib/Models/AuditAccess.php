<?php

// plugins/ahgAuditTrailPlugin/lib/Models/AuditAccess.php

namespace AtoM\Framework\Plugins\AuditTrail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditAccess extends Model
{
    protected $table = 'ahg_audit_access';
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'user_id', 'username', 'ip_address', 'access_type',
        'entity_type', 'entity_id', 'entity_slug', 'entity_title',
        'security_classification', 'security_clearance_level', 'clearance_verified',
        'file_path', 'file_name', 'file_mime_type', 'file_size',
        'status', 'denial_reason', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'clearance_verified' => 'boolean',
        'file_size' => 'integer',
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

    public const ACCESS_VIEW = 'view';
    public const ACCESS_DOWNLOAD = 'download';
    public const ACCESS_PRINT = 'print';
    public const ACCESS_EXPORT = 'export';
    public const ACCESS_API = 'api_access';
}