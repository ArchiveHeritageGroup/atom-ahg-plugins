<?php

/*
 * Spectrum Condition Photo Model
 *
 * Handles condition report photos for museum objects
 * Uses Laravel Query Builder for database operations.
 *
 * @package    ahgSpectrumPlugin
 * @subpackage lib/model
 */

use Illuminate\Database\Capsule\Manager as DB;

class SpectrumConditionPhoto
{
    protected $data = [];

    // Photo type constants
    public const TYPE_BEFORE = 'before';
    public const TYPE_AFTER = 'after';
    public const TYPE_DETAIL = 'detail';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_OVERALL = 'overall';
    public const TYPE_OTHER = 'other';

    /**
     * Constructor
     */
    public function __construct($id = null)
    {
        if ($id) {
            $this->load($id);
        }
    }

    /**
     * Load photo by ID
     */
    public function load($id)
    {
        $result = DB::table('spectrum_condition_photo')
            ->where('id', $id)
            ->first();

        $this->data = $result ? (array) $result : [];

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
        $id = DB::table('spectrum_condition_photo')->insertGetId([
            'condition_check_id' => $this->get('condition_check_id'),
            'digital_object_id' => $this->get('digital_object_id'),
            'photo_type' => $this->get('photo_type', self::TYPE_DETAIL),
            'caption' => $this->get('caption'),
            'description' => $this->get('description'),
            'location_on_object' => $this->get('location_on_object'),
            'filename' => $this->get('filename'),
            'original_filename' => $this->get('original_filename'),
            'file_path' => $this->get('file_path'),
            'file_size' => $this->get('file_size'),
            'mime_type' => $this->get('mime_type'),
            'width' => $this->get('width'),
            'height' => $this->get('height'),
            'photographer' => $this->get('photographer'),
            'photo_date' => $this->get('photo_date'),
            'camera_info' => $this->get('camera_info'),
            'sort_order' => $this->get('sort_order', 0),
            'is_primary' => $this->get('is_primary', false) ? 1 : 0,
            'annotations' => $this->get('annotations'),
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->data['id'] = $id;

        return $id;
    }

    /**
     * Update existing photo
     */
    protected function update($userId)
    {
        DB::table('spectrum_condition_photo')
            ->where('id', $this->data['id'])
            ->update([
                'condition_check_id' => $this->get('condition_check_id'),
                'digital_object_id' => $this->get('digital_object_id'),
                'photo_type' => $this->get('photo_type', self::TYPE_DETAIL),
                'caption' => $this->get('caption'),
                'description' => $this->get('description'),
                'location_on_object' => $this->get('location_on_object'),
                'filename' => $this->get('filename'),
                'original_filename' => $this->get('original_filename'),
                'file_path' => $this->get('file_path'),
                'file_size' => $this->get('file_size'),
                'mime_type' => $this->get('mime_type'),
                'width' => $this->get('width'),
                'height' => $this->get('height'),
                'photographer' => $this->get('photographer'),
                'photo_date' => $this->get('photo_date'),
                'camera_info' => $this->get('camera_info'),
                'sort_order' => $this->get('sort_order', 0),
                'is_primary' => $this->get('is_primary', false) ? 1 : 0,
                'annotations' => $this->get('annotations'),
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->data['id'];
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
        return DB::table('spectrum_condition_photo')
            ->where('id', $this->data['id'])
            ->delete() > 0;
    }

    /**
     * Delete file from disk
     */
    protected function deleteFile()
    {
        $filePath = $this->get('file_path');

        if ($filePath && file_exists(sfConfig::get('sf_upload_dir').'/'.$filePath)) {
            unlink(sfConfig::get('sf_upload_dir').'/'.$filePath);

            // Delete thumbnails
            $basePath = dirname(sfConfig::get('sf_upload_dir').'/'.$filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);

            foreach (['small', 'medium', 'large'] as $size) {
                $thumbPath = $basePath.'/'.$filename.'_'.$size.'.'.$ext;
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
        $query = DB::table('spectrum_condition_photo as cp')
            ->leftJoin('user as u', 'cp.created_by', '=', 'u.id')
            ->where('cp.condition_check_id', $conditionCheckId)
            ->select('cp.*', 'u.username as photographer_username');

        if ($photoType) {
            $query->where('cp.photo_type', $photoType);
        }

        return $query->orderBy('cp.sort_order')
            ->orderBy('cp.created_at')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get primary photo for condition check
     */
    public static function getPrimaryPhoto($conditionCheckId)
    {
        $result = DB::table('spectrum_condition_photo')
            ->where('condition_check_id', $conditionCheckId)
            ->where('is_primary', 1)
            ->first();

        return $result ? (array) $result : null;
    }

    /**
     * Get before/after pairs
     */
    public static function getComparisons($conditionCheckId)
    {
        return DB::table('spectrum_condition_photo_comparison as c')
            ->join('spectrum_condition_photo as bp', 'c.before_photo_id', '=', 'bp.id')
            ->join('spectrum_condition_photo as ap', 'c.after_photo_id', '=', 'ap.id')
            ->where('c.condition_check_id', $conditionCheckId)
            ->select(
                'c.*',
                'bp.filename as before_filename',
                'bp.file_path as before_file_path',
                'bp.caption as before_caption',
                'ap.filename as after_filename',
                'ap.file_path as after_file_path',
                'ap.caption as after_caption'
            )
            ->orderBy('c.created_at')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Create comparison pair
     */
    public static function createComparison($conditionCheckId, $beforePhotoId, $afterPhotoId, $title = null, $notes = null, $userId = null)
    {
        return DB::table('spectrum_condition_photo_comparison')->insertGetId([
            'condition_check_id' => $conditionCheckId,
            'before_photo_id' => $beforePhotoId,
            'after_photo_id' => $afterPhotoId,
            'comparison_title' => $title,
            'comparison_notes' => $notes,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
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
            self::TYPE_OTHER => 'Other',
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

        $thumbPath = $basePath.'/'.$filename.'_'.$size.'.'.$ext;

        if (file_exists(sfConfig::get('sf_upload_dir').'/'.$thumbPath)) {
            return '/uploads/'.$thumbPath;
        }

        // Return original if thumbnail doesn't exist
        return '/uploads/'.$filePath;
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

        return '/uploads/'.$filePath;
    }
}
