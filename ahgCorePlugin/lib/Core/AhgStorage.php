<?php

namespace AhgCore\Core;

/**
 * AhgStorage - File Storage Helper
 *
 * Centralized file storage utilities with proper permissions,
 * filename sanitization, and collision handling.
 *
 * Usage:
 *   use AhgCore\Core\AhgStorage;
 *
 *   // Create directory with proper permissions
 *   AhgStorage::mkdir('/path/to/dir');
 *
 *   // Sanitize a filename
 *   $safe = AhgStorage::sanitizeFilename('My File (1).pdf');
 *
 *   // Store an uploaded file
 *   $path = AhgStorage::store($uploadedFile, 'documents');
 */
class AhgStorage
{
    /**
     * Default directory permissions (775)
     */
    public const DIR_MODE = 0775;

    /**
     * Default file permissions (664)
     */
    public const FILE_MODE = 0664;

    /**
     * Maximum filename length
     */
    public const MAX_FILENAME_LENGTH = 200;

    /**
     * Dangerous file extensions that should be blocked
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'ps1',
        'js', 'htaccess', 'htpasswd',
    ];

    /**
     * Create directory with proper permissions
     *
     * @param string $path Directory path
     * @param int $mode Permission mode (default 0775)
     * @param bool $recursive Create parent directories
     * @return bool Success
     */
    public static function mkdir(string $path, int $mode = self::DIR_MODE, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $result = @mkdir($path, $mode, $recursive);

        if ($result) {
            // Ensure group write permissions
            @chmod($path, $mode);
        }

        return $result;
    }

    /**
     * Sanitize filename for safe storage
     *
     * @param string $filename Original filename
     * @param bool $allowUnicode Allow unicode characters
     * @return string Sanitized filename
     */
    public static function sanitizeFilename(string $filename, bool $allowUnicode = true): string
    {
        // Get extension and basename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        // Remove null bytes and other control characters
        $basename = preg_replace('/[\x00-\x1F\x7F]/', '', $basename);

        if ($allowUnicode) {
            // Keep unicode, remove dangerous characters
            $basename = preg_replace('/[\/\\\\:*?"<>|]/', '', $basename);
        } else {
            // ASCII only
            $basename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $basename);
        }

        // Normalize whitespace
        $basename = preg_replace('/\s+/', '_', trim($basename));

        // Remove leading/trailing dots and underscores
        $basename = trim($basename, '._');

        // Truncate if too long
        if (strlen($basename) > self::MAX_FILENAME_LENGTH) {
            $basename = substr($basename, 0, self::MAX_FILENAME_LENGTH);
        }

        // Default if empty
        if (empty($basename)) {
            $basename = 'file_' . date('YmdHis');
        }

        // Sanitize extension
        $extension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension));

        // Block dangerous extensions
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $extension = 'blocked';
        }

        return $extension ? "{$basename}.{$extension}" : $basename;
    }

    /**
     * Generate unique filename to avoid collisions
     *
     * @param string $directory Target directory
     * @param string $filename Original filename
     * @return string Unique filename
     */
    public static function uniqueFilename(string $directory, string $filename): string
    {
        $sanitized = self::sanitizeFilename($filename);
        $extension = pathinfo($sanitized, PATHINFO_EXTENSION);
        $basename = pathinfo($sanitized, PATHINFO_FILENAME);

        $finalName = $sanitized;
        $counter = 1;

        while (file_exists($directory . DIRECTORY_SEPARATOR . $finalName)) {
            $finalName = $extension
                ? "{$basename}_{$counter}.{$extension}"
                : "{$basename}_{$counter}";
            $counter++;

            // Safety limit
            if ($counter > 10000) {
                $finalName = uniqid($basename . '_') . ($extension ? ".{$extension}" : '');
                break;
            }
        }

        return $finalName;
    }

    /**
     * Store a file in the uploads directory
     *
     * @param array $uploadedFile $_FILES array element
     * @param string $subDirectory Subdirectory under uploads
     * @param string|null $customName Custom filename (optional)
     * @return array|null ['path' => full path, 'filename' => name, 'url' => web url] or null on failure
     */
    public static function store(array $uploadedFile, string $subDirectory = '', ?string $customName = null): ?array
    {
        // Validate uploaded file
        if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
            return null;
        }

        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Build target directory
        $uploadPath = AhgConfig::getUploadPath($subDirectory);
        if (!self::mkdir($uploadPath)) {
            return null;
        }

        // Determine filename
        $originalName = $customName ?? $uploadedFile['name'] ?? 'uploaded_file';
        $filename = self::uniqueFilename($uploadPath, $originalName);
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $filename;

        // Move file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $fullPath)) {
            return null;
        }

        // Set permissions
        @chmod($fullPath, self::FILE_MODE);

        return [
            'path' => $fullPath,
            'filename' => $filename,
            'url' => AhgConfig::getUploadUrl($subDirectory . '/' . $filename),
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath) ?: $uploadedFile['type'] ?? null,
        ];
    }

    /**
     * Store file content directly
     *
     * @param string $content File content
     * @param string $filename Desired filename
     * @param string $subDirectory Subdirectory under uploads
     * @return array|null Same as store()
     */
    public static function storeContent(string $content, string $filename, string $subDirectory = ''): ?array
    {
        $uploadPath = AhgConfig::getUploadPath($subDirectory);
        if (!self::mkdir($uploadPath)) {
            return null;
        }

        $safeFilename = self::uniqueFilename($uploadPath, $filename);
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $safeFilename;

        if (file_put_contents($fullPath, $content) === false) {
            return null;
        }

        @chmod($fullPath, self::FILE_MODE);

        return [
            'path' => $fullPath,
            'filename' => $safeFilename,
            'url' => AhgConfig::getUploadUrl($subDirectory . '/' . $safeFilename),
            'size' => strlen($content),
            'mime_type' => mime_content_type($fullPath) ?: null,
        ];
    }

    /**
     * Delete a file safely
     *
     * @param string $path File path
     * @return bool Success
     */
    public static function delete(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        // Security: don't allow deleting outside uploads
        $uploadsPath = AhgConfig::getUploadPath();
        if (strpos(realpath($path), realpath($uploadsPath)) !== 0) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * Delete a directory recursively
     *
     * @param string $path Directory path
     * @return bool Success
     */
    public static function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }

        // Security: don't allow deleting outside uploads
        $uploadsPath = AhgConfig::getUploadPath();
        if (strpos(realpath($path), realpath($uploadsPath)) !== 0) {
            return false;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            is_dir($filePath) ? self::deleteDirectory($filePath) : @unlink($filePath);
        }

        return @rmdir($path);
    }

    /**
     * Copy file to uploads
     *
     * @param string $sourcePath Source file path
     * @param string $subDirectory Target subdirectory
     * @param string|null $newName New filename (optional)
     * @return array|null Same as store()
     */
    public static function copy(string $sourcePath, string $subDirectory = '', ?string $newName = null): ?array
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return null;
        }

        $uploadPath = AhgConfig::getUploadPath($subDirectory);
        if (!self::mkdir($uploadPath)) {
            return null;
        }

        $filename = self::uniqueFilename($uploadPath, $newName ?? basename($sourcePath));
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $filename;

        if (!copy($sourcePath, $fullPath)) {
            return null;
        }

        @chmod($fullPath, self::FILE_MODE);

        return [
            'path' => $fullPath,
            'filename' => $filename,
            'url' => AhgConfig::getUploadUrl($subDirectory . '/' . $filename),
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath) ?: null,
        ];
    }

    /**
     * Get file info
     *
     * @param string $path File path
     * @return array|null File info or null if not exists
     */
    public static function getInfo(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        return [
            'path' => $path,
            'filename' => basename($path),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'size' => filesize($path),
            'mime_type' => mime_content_type($path) ?: null,
            'modified' => filemtime($path),
            'readable' => is_readable($path),
            'writable' => is_writable($path),
        ];
    }

    /**
     * Check if path is within uploads directory (security check)
     *
     * @param string $path Path to check
     * @return bool True if within uploads
     */
    public static function isWithinUploads(string $path): bool
    {
        $uploadsPath = realpath(AhgConfig::getUploadPath());
        $realPath = realpath($path);

        if (!$uploadsPath || !$realPath) {
            return false;
        }

        return strpos($realPath, $uploadsPath) === 0;
    }

    /**
     * Get human-readable file size
     *
     * @param int $bytes Size in bytes
     * @param int $precision Decimal precision
     * @return string Formatted size
     */
    public static function formatSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get MIME type for extension
     *
     * @param string $extension File extension
     * @return string MIME type
     */
    public static function getMimeType(string $extension): string
    {
        $mimes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'html' => 'text/html',
        ];

        return $mimes[strtolower($extension)] ?? 'application/octet-stream';
    }
}
