<?php

/*
 * Spectrum Condition Photo Model
 * 
 * Handles condition report photos for museum objects
 * 
 * @package    ahgSpectrumPlugin
 * @subpackage lib/model
 */

class SpectrumConditionPhoto
{
    protected $data = [];
    protected $conn;
    
    // Photo type constants
    const TYPE_BEFORE = 'before';
    const TYPE_AFTER = 'after';
    const TYPE_DETAIL = 'detail';
    const TYPE_DAMAGE = 'damage';
    const TYPE_OVERALL = 'overall';
    const TYPE_OTHER = 'other';
    
    /**
     * Constructor
     */
    public function __construct($id = null)
    {
        $this->conn = Propel::getConnection();
        
        if ($id) {
            $this->load($id);
        }
    }
    
    /**
     * Load photo by ID
     */
    public function load($id)
    {
        $sql = "SELECT * FROM spectrum_condition_photo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $this->data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        return !empty($this->data);
    }
    
    /**
     * Get photo data
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Set photo data
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Get all data
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Save photo
     */
    public function save($userId = null)
    {
        if (empty($this->data['id'])) {
            return $this->insert($userId);
        } else {
            return $this->update($userId);
        }
    }
    
    /**
     * Insert new photo
     */
    protected function insert($userId)
    {
        $sql = "INSERT INTO spectrum_condition_photo (
                    condition_check_id, digital_object_id, photo_type, caption,
                    description, location_on_object, filename, original_filename,
                    file_path, file_size, mime_type, width, height,
                    photographer, photo_date, camera_info, sort_order, is_primary,
                    annotations, created_by, created_at
                ) VALUES (
                    :condition_check_id, :digital_object_id, :photo_type, :caption,
                    :description, :location_on_object, :filename, :original_filename,
                    :file_path, :file_size, :mime_type, :width, :height,
                    :photographer, :photo_date, :camera_info, :sort_order, :is_primary,
                    :annotations, :created_by, NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $this->bindParams($stmt, $userId);
        $stmt->execute();
        
        $this->data['id'] = $this->conn->lastInsertId();
        
        return $this->data['id'];
    }
    
    /**
     * Update existing photo
     */
    protected function update($userId)
    {
        $sql = "UPDATE spectrum_condition_photo SET
                    condition_check_id = :condition_check_id,
                    digital_object_id = :digital_object_id,
                    photo_type = :photo_type,
                    caption = :caption,
                    description = :description,
                    location_on_object = :location_on_object,
                    filename = :filename,
                    original_filename = :original_filename,
                    file_path = :file_path,
                    file_size = :file_size,
                    mime_type = :mime_type,
                    width = :width,
                    height = :height,
                    photographer = :photographer,
                    photo_date = :photo_date,
                    camera_info = :camera_info,
                    sort_order = :sort_order,
                    is_primary = :is_primary,
                    annotations = :annotations,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $this->bindParams($stmt, $userId, true);
        $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $this->data['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        return $this->data['id'];
    }
    
    /**
     * Bind parameters to statement
     */
    protected function bindParams($stmt, $userId, $isUpdate = false)
    {
        $stmt->bindValue(':condition_check_id', $this->get('condition_check_id'), PDO::PARAM_INT);
        $stmt->bindValue(':digital_object_id', $this->get('digital_object_id'), PDO::PARAM_INT);
        $stmt->bindValue(':photo_type', $this->get('photo_type', self::TYPE_DETAIL));
        $stmt->bindValue(':caption', $this->get('caption'));
        $stmt->bindValue(':description', $this->get('description'));
        $stmt->bindValue(':location_on_object', $this->get('location_on_object'));
        $stmt->bindValue(':filename', $this->get('filename'));
        $stmt->bindValue(':original_filename', $this->get('original_filename'));
        $stmt->bindValue(':file_path', $this->get('file_path'));
        $stmt->bindValue(':file_size', $this->get('file_size'), PDO::PARAM_INT);
        $stmt->bindValue(':mime_type', $this->get('mime_type'));
        $stmt->bindValue(':width', $this->get('width'), PDO::PARAM_INT);
        $stmt->bindValue(':height', $this->get('height'), PDO::PARAM_INT);
        $stmt->bindValue(':photographer', $this->get('photographer'));
        $stmt->bindValue(':photo_date', $this->get('photo_date'));
        $stmt->bindValue(':camera_info', $this->get('camera_info'));
        $stmt->bindValue(':sort_order', $this->get('sort_order', 0), PDO::PARAM_INT);
        $stmt->bindValue(':is_primary', $this->get('is_primary', false), PDO::PARAM_BOOL);
        $stmt->bindValue(':annotations', $this->get('annotations'));
        
        if (!$isUpdate) {
            $stmt->bindValue(':created_by', $userId, PDO::PARAM_INT);
        }
    }
    
    /**
     * Delete photo
     */
    public function delete()
    {
        if (empty($this->data['id'])) {
            return false;
        }
        
        // Delete file from disk
        $this->deleteFile();
        
        // Delete database record
        $sql = "DELETE FROM spectrum_condition_photo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $this->data['id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Delete file from disk
     */
    protected function deleteFile()
    {
        $filePath = $this->get('file_path');
        
        if ($filePath && file_exists(sfConfig::get('sf_upload_dir') . '/' . $filePath)) {
            unlink(sfConfig::get('sf_upload_dir') . '/' . $filePath);
            
            // Delete thumbnails
            $basePath = dirname(sfConfig::get('sf_upload_dir') . '/' . $filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            
            foreach (['small', 'medium', 'large'] as $size) {
                $thumbPath = $basePath . '/' . $filename . '_' . $size . '.' . $ext;
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
        }
    }
    
    /**
     * Get photos for a condition check
     */
    public static function getByConditionCheck($conditionCheckId, $photoType = null)
    {
        $conn = Propel::getConnection();
        
        $sql = "SELECT cp.*, u.username as photographer_username
                FROM spectrum_condition_photo cp
                LEFT JOIN user u ON cp.created_by = u.id
                WHERE cp.condition_check_id = :condition_check_id";
        
        if ($photoType) {
            $sql .= " AND cp.photo_type = :photo_type";
        }
        
        $sql .= " ORDER BY cp.sort_order ASC, cp.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':condition_check_id', $conditionCheckId, PDO::PARAM_INT);
        
        if ($photoType) {
            $stmt->bindValue(':photo_type', $photoType);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get primary photo for condition check
     */
    public static function getPrimaryPhoto($conditionCheckId)
    {
        $conn = Propel::getConnection();
        
        $sql = "SELECT * FROM spectrum_condition_photo 
                WHERE condition_check_id = :condition_check_id 
                AND is_primary = 1 
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':condition_check_id', $conditionCheckId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get before/after pairs
     */
    public static function getComparisons($conditionCheckId)
    {
        $conn = Propel::getConnection();
        
        $sql = "SELECT 
                    c.*,
                    bp.filename as before_filename,
                    bp.file_path as before_file_path,
                    bp.caption as before_caption,
                    ap.filename as after_filename,
                    ap.file_path as after_file_path,
                    ap.caption as after_caption
                FROM spectrum_condition_photo_comparison c
                JOIN spectrum_condition_photo bp ON c.before_photo_id = bp.id
                JOIN spectrum_condition_photo ap ON c.after_photo_id = ap.id
                WHERE c.condition_check_id = :condition_check_id
                ORDER BY c.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':condition_check_id', $conditionCheckId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create comparison pair
     */
    public static function createComparison($conditionCheckId, $beforePhotoId, $afterPhotoId, $title = null, $notes = null, $userId = null)
    {
        $conn = Propel::getConnection();
        
        $sql = "INSERT INTO spectrum_condition_photo_comparison 
                (condition_check_id, before_photo_id, after_photo_id, comparison_title, comparison_notes, created_by, created_at)
                VALUES (:condition_check_id, :before_photo_id, :after_photo_id, :title, :notes, :user_id, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':condition_check_id', $conditionCheckId, PDO::PARAM_INT);
        $stmt->bindValue(':before_photo_id', $beforePhotoId, PDO::PARAM_INT);
        $stmt->bindValue(':after_photo_id', $afterPhotoId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':notes', $notes);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $conn->lastInsertId();
    }
    
    /**
     * Get photo types for dropdown
     */
    public static function getPhotoTypes()
    {
        return [
            self::TYPE_BEFORE => 'Before Treatment',
            self::TYPE_AFTER => 'After Treatment',
            self::TYPE_DETAIL => 'Detail View',
            self::TYPE_DAMAGE => 'Damage Documentation',
            self::TYPE_OVERALL => 'Overall View',
            self::TYPE_OTHER => 'Other'
        ];
    }
    
    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl($size = 'medium')
    {
        $filePath = $this->get('file_path');
        
        if (!$filePath) {
            return null;
        }
        
        $basePath = dirname($filePath);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        
        $thumbPath = $basePath . '/' . $filename . '_' . $size . '.' . $ext;
        
        if (file_exists(sfConfig::get('sf_upload_dir') . '/' . $thumbPath)) {
            return '/uploads/' . $thumbPath;
        }
        
        // Return original if thumbnail doesn't exist
        return '/uploads/' . $filePath;
    }
    
    /**
     * Get full URL
     */
    public function getFullUrl()
    {
        $filePath = $this->get('file_path');
        
        if (!$filePath) {
            return null;
        }
        
        return '/uploads/' . $filePath;
    }
}
