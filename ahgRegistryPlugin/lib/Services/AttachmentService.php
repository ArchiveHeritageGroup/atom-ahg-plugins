<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class AttachmentService
{
    protected string $culture;
    protected string $table = 'registry_attachment';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Queries
    // =========================================================================

    /**
     * Get attachments for an entity.
     */
    public function findByEntity(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    // =========================================================================
    // Upload & Delete
    // =========================================================================

    /**
     * Upload an attachment for an entity.
     *
     * @param string $entityType  discussion|reply|blog_post|institution|vendor|software
     * @param int    $entityId    ID of the parent entity
     * @param array  $file        $_FILES entry (name, type, tmp_name, error, size)
     * @param string|null $uploaderEmail
     * @param int|null    $uploaderUserId
     */
    public function upload(string $entityType, int $entityId, array $file, ?string $uploaderEmail = null, ?int $uploaderUserId = null): array
    {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Determine upload path
        $uploadPath = $this->getUploadPath();
        $fullDir = \sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads') . '/' . $uploadPath;

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        // Sanitize filename
        $originalName = $file['name'];
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $safeName = time() . '_' . $safeName;
        $destPath = $fullDir . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        // Determine file type category
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileType = $this->categorizeFileType($ext);

        $id = DB::table($this->table)->insertGetId([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'file_path' => $uploadPath . '/' . $safeName,
            'file_name' => $originalName,
            'file_size_bytes' => filesize($destPath),
            'mime_type' => $file['type'] ?? mime_content_type($destPath),
            'file_type' => $fileType,
            'download_count' => 0,
            'uploaded_by_email' => $uploaderEmail,
            'uploaded_by_user_id' => $uploaderUserId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'id' => $id,
            'file_path' => $uploadPath . '/' . $safeName,
            'file_name' => $originalName,
        ];
    }

    /**
     * Delete an attachment: remove file from disk and database record.
     */
    public function delete(int $id): array
    {
        $attachment = DB::table($this->table)->where('id', $id)->first();
        if (!$attachment) {
            return ['success' => false, 'error' => 'Attachment not found'];
        }

        // Remove file from disk
        $fullPath = \sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads') . '/' . $attachment->file_path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    /**
     * Increment download count for an attachment.
     */
    public function incrementDownloadCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->increment('download_count');
    }

    // =========================================================================
    // Path & Validation
    // =========================================================================

    /**
     * Get upload path relative to uploads dir: registry/attachments/YYYY/MM/
     */
    public function getUploadPath(): string
    {
        return 'registry/attachments/' . date('Y') . '/' . date('m');
    }

    /**
     * Validate file: check extension, size, and MIME type against settings.
     */
    public function validateFile(array $file): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No valid file uploaded'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload error code: ' . $file['error']];
        }

        // Check extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = $this->getAllowedTypes();
        if (!in_array($ext, $allowedTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed: .' . $ext . '. Allowed: ' . implode(', ', $allowedTypes)];
        }

        // Check size
        $maxSize = $this->getMaxSize();
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / (1024 * 1024), 1);

            return ['valid' => false, 'error' => 'File too large. Maximum size: ' . $maxMB . ' MB'];
        }

        return ['valid' => true];
    }

    /**
     * Get allowed file types from registry settings.
     */
    public function getAllowedTypes(): array
    {
        $setting = DB::table('registry_settings')
            ->where('setting_key', 'allowed_attachment_types')
            ->value('setting_value');

        if ($setting) {
            return array_map('trim', explode(',', $setting));
        }

        // Defaults
        return ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xlsx', 'csv', 'txt', 'log', 'zip'];
    }

    /**
     * Get maximum upload size in bytes from registry settings.
     */
    public function getMaxSize(): int
    {
        $settingMB = DB::table('registry_settings')
            ->where('setting_key', 'max_attachment_size_mb')
            ->value('setting_value');

        $mb = $settingMB ? (int) $settingMB : 10;

        return $mb * 1024 * 1024;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Categorize file extension into a file_type enum value.
     */
    private function categorizeFileType(string $ext): string
    {
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'tif'];
        $docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'rtf'];
        $logExts = ['log'];
        $archiveExts = ['zip', 'tar', 'gz', 'rar', '7z', 'tgz'];
        $screenshotExts = []; // set via caption/context, not extension

        if (in_array($ext, $imageExts)) {
            return 'image';
        }
        if (in_array($ext, $docExts)) {
            return 'document';
        }
        if (in_array($ext, $logExts)) {
            return 'log';
        }
        if (in_array($ext, $archiveExts)) {
            return 'archive';
        }

        return 'other';
    }
}
