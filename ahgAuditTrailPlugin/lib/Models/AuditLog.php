<?php

// plugins/ahgAuditTrailPlugin/lib/Models/AuditLog.php

namespace AtoM\Framework\Plugins\AuditTrail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $table = 'ahg_audit_log';
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'user_id', 'username', 'user_email', 'ip_address', 'user_agent',
        'session_id', 'action', 'entity_type', 'entity_id', 'entity_slug',
        'entity_title', 'module', 'action_name', 'request_method', 'request_uri',
        'old_values', 'new_values', 'changed_fields', 'metadata',
        'security_classification', 'status', 'error_message',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
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

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';
    public const ACTION_PUBLISH = 'publish';
    public const ACTION_UNPUBLISH = 'unpublish';
    public const ACTION_MOVE = 'move';
    public const ACTION_PERMISSION_CHANGE = 'permission_change';
    public const ACTION_SETTINGS_CHANGE = 'settings_change';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_DENIED = 'denied';

    public function getActionLabelAttribute(): string
    {
        $labels = [
            self::ACTION_CREATE => 'Created',
            self::ACTION_UPDATE => 'Updated',
            self::ACTION_DELETE => 'Deleted',
            self::ACTION_VIEW => 'Viewed',
            self::ACTION_DOWNLOAD => 'Downloaded',
            self::ACTION_EXPORT => 'Exported',
            self::ACTION_IMPORT => 'Imported',
            self::ACTION_PUBLISH => 'Published',
            self::ACTION_UNPUBLISH => 'Unpublished',
            self::ACTION_MOVE => 'Moved',
            self::ACTION_PERMISSION_CHANGE => 'Permission Changed',
            self::ACTION_SETTINGS_CHANGE => 'Settings Changed',
        ];
        return $labels[$this->action] ?? ucfirst($this->action);
    }

    public function getEntityTypeLabelAttribute(): string
    {
        $labels = [
            'QubitInformationObject' => 'Archival Description',
            'QubitActor' => 'Authority Record',
            'QubitRepository' => 'Repository',
            'QubitTerm' => 'Term',
            'QubitTaxonomy' => 'Taxonomy',
            'QubitUser' => 'User',
            'QubitAccession' => 'Accession',
            'QubitDeaccession' => 'Deaccession',
            'QubitDonor' => 'Donor',
            'QubitDigitalObject' => 'Digital Object',
            'QubitPhysicalObject' => 'Physical Storage',
            'QubitStaticPage' => 'Static Page',
        ];
        return $labels[$this->entity_type] ?? $this->entity_type;
    }
}