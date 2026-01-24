<?php

/*
 * Spectrum Photo Upload Service
 * 
 * Handles photo uploads, thumbnail generation, and file management
 * 
 * @package    ahgSpectrumPlugin
 * @subpackage lib/services
 */

class SpectrumPhotoService
{
    protected $settings;
    protected $uploadDir;
    protected $errors = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadSettings();
        $this->uploadDir = sfConfig::get('sf_upload_dir') . '/' . $this->getSetting('photo_storage_path');
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Load settings from database
     */
    protected function loadSettings()
    {
        $this->settings = [];

        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('spectrum_media_settings')
                ->select('setting_key', 'setting_value', 'setting_type')
                ->get();

            foreach ($rows as $row) {
                $value = $row->setting_value;

                switch ($row->setting_type) {
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'boolean':
                        $value = $value === 'true' || $value === '1';
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }

                $this->settings[$row->setting_key] = $value;
            }
        } catch (Exception $e) {
            // Use defaults if database not available
        }
    }
    
    /**
     * Get setting value
     */
    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Upload photo from $_FILES
     * 
     * @param array $file The uploaded file from $_FILES
     * @param int $conditionCheckId The condition check to attach to
     * @param array $metadata Additional metadata (caption, type, etc.)
     * @param int $userId User performing upload
     * @return SpectrumConditionPhoto|false
     */
    public function uploadPhoto($file, $conditionCheckId, $metadata = [], $userId = null)
    {
        $this->errors = [];
        
        // Validate upload
        if (!$this->validateUpload($file)) {
            return false;
        }
        
        // Generate unique filename
        $filename = $this->generateFilename($file['name']);
        $relativePath = $this->getSetting('photo_storage_path') . '/' . $filename;
        $fullPath = sfConfig::get('sf_upload_dir') . '/' . $relativePath;
        
        // Ensure subdirectory exists (organize by year/month)
        $subDir = date('Y/m');
        $relativePath = $this->getSetting('photo_storage_path') . '/' . $subDir . '/' . $filename;
        $fullPath = sfConfig::get('sf_upload_dir') . '/' . $relativePath;
        
        $photoDir = dirname($fullPath);
        if (!is_dir($photoDir)) {
            mkdir($photoDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            $this->errors[] = 'Failed to move uploaded file';
            return false;
        }
        
        // Get image dimensions
        $imageInfo = getimagesize($fullPath);
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;
        
        // Create thumbnails
        if ($this->getSetting('photo_create_thumbnails', true)) {
            $this->createThumbnails($fullPath);
        }
        
        // Extract EXIF data if available
        $cameraInfo = $this->extractExifData($fullPath);
        
        // Create photo record
        $photo = new SpectrumConditionPhoto();
        $photo->set('condition_check_id', $conditionCheckId);
        $photo->set('photo_type', $metadata['photo_type'] ?? SpectrumConditionPhoto::TYPE_DETAIL);
        $photo->set('caption', $metadata['caption'] ?? null);
        $photo->set('description', $metadata['description'] ?? null);
        $photo->set('location_on_object', $metadata['location_on_object'] ?? null);
        $photo->set('filename', $filename);
        $photo->set('original_filename', $file['name']);
        $photo->set('file_path', $relativePath);
        $photo->set('file_size', $file['size']);
        $photo->set('mime_type', $file['type']);
        $photo->set('width', $width);
        $photo->set('height', $height);
        $photo->set('photographer', $metadata['photographer'] ?? null);
        $photo->set('photo_date', $metadata['photo_date'] ?? date('Y-m-d'));
        $photo->set('camera_info', $cameraInfo);
        $photo->set('sort_order', $metadata['sort_order'] ?? 0);
        $photo->set('is_primary', $metadata['is_primary'] ?? false);
        
        $photo->save($userId);
        
        return $photo;
    }
    
    /**
     * Validate uploaded file
     */
    protected function validateUpload($file)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // Check file size
        $maxSize = $this->getSetting('photo_max_upload_size', 10485760);
        if ($file['size'] > $maxSize) {
            $this->errors[] = 'File size exceeds maximum allowed (' . AhgCentralHelpers::formatBytes($maxSize) . ')';
            return false;
        }
        
        // Check MIME type
        $allowedTypes = $this->getSetting('photo_allowed_types', ['image/jpeg', 'image/png']);
        if (!in_array($file['type'], $allowedTypes)) {
            $this->errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
            return false;
        }
        
        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            $this->errors[] = 'File is not a valid image';
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate unique filename
     */
    protected function generateFilename($originalName)
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return uniqid('cond_') . '_' . time() . '.' . $ext;
    }
    
    /**
     * Create thumbnails
     */
    protected function createThumbnails($sourcePath)
    {
        $sizes = $this->getSetting('photo_thumbnail_sizes', ['small' => 150, 'medium' => 300, 'large' => 600]);
        
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'];
        
        // Load source image
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);
        
        $basePath = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
        
        foreach ($sizes as $sizeName => $maxDimension) {
            // Calculate new dimensions maintaining aspect ratio
            if ($origWidth > $origHeight) {
                $newWidth = $maxDimension;
                $newHeight = (int) ($origHeight * ($maxDimension / $origWidth));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) ($origWidth * ($maxDimension / $origHeight));
            }
            
            // Create thumbnail
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                imagefill($thumb, 0, 0, $transparent);
            }
            
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            
            $thumbPath = $basePath . '/' . $filename . '_' . $sizeName . '.' . $ext;
            
            // Save thumbnail
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($thumb, $thumbPath, 85);
                    break;
                case 'image/png':
                    imagepng($thumb, $thumbPath, 8);
                    break;
                case 'image/webp':
                    imagewebp($thumb, $thumbPath, 85);
                    break;
            }
            
            imagedestroy($thumb);
        }
        
        imagedestroy($source);
        
        return true;
    }
    
    /**
     * Extract EXIF data from image
     */
    protected function extractExifData($filePath)
    {
        if (!function_exists('exif_read_data')) {
            return null;
        }
        
        $exif = @exif_read_data($filePath);
        
        if (!$exif) {
            return null;
        }
        
        $cameraInfo = [];
        
        if (isset($exif['Make'])) {
            $cameraInfo[] = $exif['Make'];
        }
        if (isset($exif['Model'])) {
            $cameraInfo[] = $exif['Model'];
        }
        if (isset($exif['ExposureTime'])) {
            $cameraInfo[] = 'Exp: ' . $exif['ExposureTime'];
        }
        if (isset($exif['FNumber'])) {
            $cameraInfo[] = 'f/' . $exif['FNumber'];
        }
        if (isset($exif['ISOSpeedRatings'])) {
            $cameraInfo[] = 'ISO ' . $exif['ISOSpeedRatings'];
        }
        
        return !empty($cameraInfo) ? implode(' | ', $cameraInfo) : null;
    }
    
    /**
     * Get upload error message
     */
    protected function getUploadErrorMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE in form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the upload';
            default:
                return 'Unknown upload error';
        }
    }
    /**
     * Get errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Delete photo and all thumbnails
     */
    public function deletePhoto(SpectrumConditionPhoto $photo)
    {
        return $photo->delete();
    }
    
    /**
     * Rotate photo
     */
    public function rotatePhoto(SpectrumConditionPhoto $photo, $degrees = 90)
    {
        $fullPath = sfConfig::get('sf_upload_dir') . '/' . $photo->get('file_path');
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $imageInfo = getimagesize($fullPath);
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($fullPath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($fullPath);
                break;
            default:
                return false;
        }
        
        $rotated = imagerotate($source, -$degrees, 0);
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                imagejpeg($rotated, $fullPath, 95);
                break;
            case 'image/png':
                imagepng($rotated, $fullPath, 8);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($rotated);
        
        // Update dimensions
        $newWidth = imagesy($source);
        $newHeight = imagesx($source);
        $photo->set('width', $newWidth);
        $photo->set('height', $newHeight);
        $photo->save();
        
        // Recreate thumbnails
        $this->createThumbnails($fullPath);
        
        return true;
    }
}
