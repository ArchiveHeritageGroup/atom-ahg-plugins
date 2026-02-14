<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Attachment Service for Report Builder.
 *
 * Manages file uploads, thumbnails, and digital object linking for report attachments.
 */
class AttachmentService
{
    /**
     * Maximum upload size in bytes (10 MB).
     */
    private const MAX_FILE_SIZE = 10485760;

    /**
     * Allowed MIME types for upload.
     *
     * @var array
     */
    private array $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    ];

    /**
     * Image MIME types that support thumbnail generation.
     *
     * @var array
     */
    private array $imageTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Get attachments for a report, optionally filtered by section.
     *
     * @param int      $reportId  The report ID
     * @param int|null $sectionId Optional section filter
     *
     * @return array The attachments
     */
    public function getAttachments(int $reportId, ?int $sectionId = null): array
    {
        $query = DB::table('report_attachment')
            ->where('report_id', $reportId)
            ->orderBy('position');

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        return $query->get()->toArray();
    }

    /**
     * Upload a file and create an attachment record.
     *
     * @param int      $reportId  The report ID
     * @param int|null $sectionId The section ID (optional)
     * @param array    $file      The uploaded file ($_FILES entry)
     *
     * @return int The new attachment ID
     *
     * @throws \InvalidArgumentException If the file fails validation
     * @throws \RuntimeException If file move fails
     */
    public function upload(int $reportId, ?int $sectionId, array $file): int
    {
        // Validate the upload
        $this->validateUpload($file);

        // Ensure upload directory exists
        $uploadPath = $this->getUploadPath($reportId);

        // Generate unique filename preserving extension
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid('rpt_', true) . '.' . strtolower($extension);
        $destPath = $uploadPath . '/' . $uniqueName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        // Generate thumbnail for images
        $thumbnailPath = null;
        $mimeType = $file['type'] ?? mime_content_type($destPath);
        if (in_array($mimeType, $this->imageTypes, true)) {
            $thumbName = 'thumb_' . $uniqueName;
            $thumbFullPath = $uploadPath . '/' . $thumbName;
            if ($this->generateThumbnail($destPath, $thumbFullPath, 200)) {
                $thumbnailPath = $thumbFullPath;
            }
        }

        // Get next position
        $maxPosition = DB::table('report_attachment')
            ->where('report_id', $reportId)
            ->max('position') ?? -1;

        // Insert attachment record
        return DB::table('report_attachment')->insertGetId([
            'report_id' => $reportId,
            'section_id' => $sectionId,
            'file_name' => $file['name'],
            'file_path' => $destPath,
            'file_type' => $mimeType,
            'file_size' => $file['size'],
            'thumbnail_path' => $thumbnailPath,
            'caption' => null,
            'position' => $maxPosition + 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete an attachment (file from disk and DB record).
     *
     * @param int $attachmentId The attachment ID
     *
     * @return bool True if deleted
     */
    public function delete(int $attachmentId): bool
    {
        $attachment = DB::table('report_attachment')
            ->where('id', $attachmentId)
            ->first();

        if (!$attachment) {
            return false;
        }

        // Delete file from disk
        if (!empty($attachment->file_path) && file_exists($attachment->file_path)) {
            @unlink($attachment->file_path);
        }

        // Delete thumbnail from disk
        if (!empty($attachment->thumbnail_path) && file_exists($attachment->thumbnail_path)) {
            @unlink($attachment->thumbnail_path);
        }

        // Delete DB record
        return DB::table('report_attachment')
            ->where('id', $attachmentId)
            ->delete() > 0;
    }

    /**
     * Link an attachment to an AtoM digital object.
     *
     * @param int $attachmentId    The attachment ID
     * @param int $digitalObjectId The digital object ID
     *
     * @return bool True if linked
     */
    public function linkDigitalObject(int $attachmentId, int $digitalObjectId): bool
    {
        return DB::table('report_attachment')
            ->where('id', $attachmentId)
            ->update([
                'digital_object_id' => $digitalObjectId,
            ]) > 0;
    }

    /**
     * Get the upload directory path for a report, creating it if needed.
     *
     * @param int $reportId The report ID
     *
     * @return string The upload directory path
     *
     * @throws \RuntimeException If the directory cannot be created
     */
    public function getUploadPath(int $reportId): string
    {
        $baseDir = \sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads');
        $path = $baseDir . '/reports/' . $reportId;

        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true)) {
                throw new \RuntimeException("Failed to create upload directory: {$path}");
            }
        }

        return $path;
    }

    /**
     * Generate a thumbnail for an image file using GD.
     *
     * @param string $sourcePath The source image path
     * @param string $thumbPath  The thumbnail destination path
     * @param int    $width      The thumbnail width in pixels
     *
     * @return bool True if thumbnail was created
     */
    private function generateThumbnail(string $sourcePath, string $thumbPath, int $width = 200): bool
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        if ($srcWidth <= 0 || $srcHeight <= 0) {
            return false;
        }

        // Calculate proportional height
        $ratio = $width / $srcWidth;
        $height = (int) round($srcHeight * $ratio);

        // Create source image
        switch ($mimeType) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = @imagecreatefromwebp($sourcePath);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        if (!$source) {
            return false;
        }

        // Create thumbnail canvas
        $thumb = imagecreatetruecolor($width, $height);
        if (!$thumb) {
            imagedestroy($source);

            return false;
        }

        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
        }

        // Resize
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);

        // Save thumbnail as JPEG
        $result = imagejpeg($thumb, $thumbPath, 85);

        // Cleanup
        imagedestroy($source);
        imagedestroy($thumb);

        return $result;
    }

    /**
     * Validate an uploaded file.
     *
     * @param array $file The uploaded file ($_FILES entry)
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateUpload(array $file): void
    {
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload',
            ];
            $code = $file['error'] ?? -1;
            $msg = $errorMessages[$code] ?? "Upload error code: {$code}";

            throw new \InvalidArgumentException($msg);
        }

        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / 1048576;

            throw new \InvalidArgumentException("File exceeds maximum size of {$maxMb}MB");
        }

        // Check MIME type
        $mimeType = $file['type'] ?? '';
        if (!empty($file['tmp_name']) && function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']) ?: $mimeType;
        }

        if (!in_array($mimeType, $this->allowedTypes, true)) {
            throw new \InvalidArgumentException(
                "File type '{$mimeType}' is not allowed. Accepted types: images, PDF, Word, Excel, CSV, TXT."
            );
        }

        // Check that temp file actually exists
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid uploaded file');
        }
    }
}
