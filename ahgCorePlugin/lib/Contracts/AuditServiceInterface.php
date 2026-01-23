<?php

namespace AhgCore\Contracts;

/**
 * AuditServiceInterface
 *
 * Interface for audit logging services. Plugins should use this interface
 * to log actions for the audit trail.
 *
 * Implementation: ahgAuditTrailPlugin provides AhgAuditService
 *
 * Usage:
 *   use AhgCore\Contracts\AuditServiceInterface;
 *
 *   $audit = AhgCore::getService(AuditServiceInterface::class);
 *   $audit->log('create', 'QubitInformationObject', $id, ['title' => 'New record']);
 */
interface AuditServiceInterface
{
    /**
     * Standard action types
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_PUBLISH = 'publish';
    public const ACTION_UNPUBLISH = 'unpublish';

    /**
     * Status types
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_DENIED = 'denied';

    /**
     * Check if audit logging is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Log an action
     *
     * @param string $action Action type (use ACTION_* constants)
     * @param string $entityType Entity class or type name
     * @param int|null $entityId Entity ID (optional)
     * @param array $options Additional options:
     *   - slug: Entity slug
     *   - title: Entity title
     *   - module: Symfony module name
     *   - action_name: Symfony action name
     *   - old_values: Previous values (for updates)
     *   - new_values: New values
     *   - changed_fields: Array of changed field names
     *   - metadata: Additional metadata array
     *   - status: success/failure/denied
     *   - error_message: Error message if failed
     * @return mixed Audit log entry or null
     */
    public function log(string $action, string $entityType, ?int $entityId = null, array $options = []): mixed;

    /**
     * Log a create action
     *
     * @param object $entity Entity object
     * @param array $newValues New values
     * @param array $options Additional options
     * @return mixed
     */
    public function logCreate(object $entity, array $newValues = [], array $options = []): mixed;

    /**
     * Log an update action
     *
     * @param object $entity Entity object
     * @param array $oldValues Previous values
     * @param array $newValues New values
     * @param array $options Additional options
     * @return mixed
     */
    public function logUpdate(object $entity, array $oldValues = [], array $newValues = [], array $options = []): mixed;

    /**
     * Log a delete action
     *
     * @param object $entity Entity object
     * @param array $options Additional options
     * @return mixed
     */
    public function logDelete(object $entity, array $options = []): mixed;

    /**
     * Log a file download
     *
     * @param object $entity Entity being downloaded from
     * @param string $filePath File path
     * @param string $fileName File name
     * @param string|null $mimeType MIME type
     * @param int|null $fileSize File size in bytes
     * @return mixed
     */
    public function logDownload(object $entity, string $filePath, string $fileName, ?string $mimeType = null, ?int $fileSize = null): mixed;

    /**
     * Log an access denied event
     *
     * @param object $entity Entity access was denied to
     * @param string $reason Denial reason
     * @param array $options Additional options
     * @return mixed
     */
    public function logAccessDenied(object $entity, string $reason, array $options = []): mixed;
}
