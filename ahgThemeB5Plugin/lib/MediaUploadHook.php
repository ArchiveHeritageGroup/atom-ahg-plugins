<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AtoM Digital Object Upload Hook
 * 
 * Hook into AtoM's digital object upload workflow to automatically:
 * - Generate thumbnails and previews
 * - Extract metadata
 * - Create waveforms
 * 
 * Pure Laravel Query Builder implementation.
 * 
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 2.0.0
 */

class MediaUploadHook
{
    private static bool $initialized = false;
    
    // Audio formats to process
    private const AUDIO_FORMATS = ['wav', 'mp3', 'flac', 'ogg', 'oga', 'm4a', 'aac', 'wma', 'aiff', 'aif'];
    
    // Video formats to process
    private const VIDEO_FORMATS = ['mov', 'mp4', 'avi', 'mkv', 'webm', 'wmv', 'flv', 'm4v', 'mpeg', 'mpg', '3gp'];
    
    /**
     * Initialize framework if needed
     */
    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Load atom-framework autoloader if available
        $frameworkAutoload = sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
        if (file_exists($frameworkAutoload)) {
            require_once $frameworkAutoload;
        }
        
        self::$initialized = true;
    }
    
    /**
     * Process a digital object after upload
     * Call this after saving a new digital object
     * 
     * @param object $digitalObject The digital object
     * @param array $options Processing options
     * @return array Processing results
     */
    public static function processDigitalObject($digitalObject, array $options = []): array
    {
        self::init();
        
        $id = is_object($digitalObject) ? $digitalObject->id : (int) $digitalObject;
        
        // Get digital object info if ID passed
        if (!is_object($digitalObject)) {
            $digitalObject = DB::table('digital_object')
                ->where('id', $id)
                ->first();
            
            if (!$digitalObject) {
                return ['processed' => false, 'reason' => 'Digital object not found'];
            }
        }
        
        $filename = $digitalObject->name;
        
        // Check if media file
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $isMedia = in_array($ext, self::AUDIO_FORMATS) || in_array($ext, self::VIDEO_FORMATS);
        
        if (!$isMedia) {
            return ['processed' => false, 'reason' => 'Not a media file'];
        }
        
        // Use processor service
        try {
            $processor = self::getProcessor();
            return $processor->processUpload($id);
        } catch (Exception $e) {
            // Log error but don't break upload
            error_log('MediaUploadHook: ' . $e->getMessage());
            
            return [
                'processed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Queue processing for async execution
     * Use this for large files or when you don't want to block the upload
     */
    public static function queueProcessing(int $digitalObjectId, array $options = []): bool
    {
        self::init();
        
        try {
            // Get object info
            $do = DB::table('digital_object')
                ->where('id', $digitalObjectId)
                ->first();
            
            if (!$do) {
                return false;
            }
            
            // Check if media file
            $ext = strtolower(pathinfo($do->name, PATHINFO_EXTENSION));
            $isMedia = in_array($ext, self::AUDIO_FORMATS) || in_array($ext, self::VIDEO_FORMATS);
            
            if (!$isMedia) {
                return false;
            }
            
            // Insert into processing queue
            DB::table('media_processing_queue')->insert([
                'digital_object_id' => $digitalObjectId,
                'object_id' => $do->object_id,
                'task_type' => 'full_processing',
                'task_options' => json_encode($options),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('MediaUploadHook queue error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if processing is enabled
     */
    public static function isEnabled(): bool
    {
        try {
            $value = DB::table('media_processor_settings')
                ->where('setting_key', 'auto_process_enabled')
                ->value('setting_value');
            
            return $value && filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } catch (Exception $e) {
            // Default to enabled
            return true;
        }
    }
    
    /**
     * Get processor instance
     */
    private static function getProcessor()
    {
        // Load settings from database
        $settings = self::loadSettings();
        
        // Framework path
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Extensions/IiifViewer';
        
        // Include service class
        require_once $frameworkPath . '/Services/MediaUploadProcessor.php';
        require_once $frameworkPath . '/Services/MediaMetadataService.php';
        
        return new \AtomFramework\Extensions\IiifViewer\Services\MediaUploadProcessor($settings);
    }
    
    /**
     * Load settings from database
     */
    private static function loadSettings(): array
    {
        // Use sfConfig for paths - no hardcoded paths
        $uploadDir = sfConfig::get('sf_upload_dir');
        
        $settings = [
            'uploads_dir' => $uploadDir,
            'derivatives_dir' => $uploadDir . '/derivatives',
        ];
        
        try {
            $rows = DB::table('media_processor_settings')
                ->select('setting_key', 'setting_value', 'setting_type')
                ->get();
            
            foreach ($rows as $row) {
                $value = $row->setting_value;
                
                switch ($row->setting_type) {
                    case 'boolean':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $settings[$row->setting_key] = $value;
            }
        } catch (Exception $e) {
            // Use defaults
            error_log('MediaUploadHook: Could not load settings: ' . $e->getMessage());
        }
        
        return $settings;
    }
    
    /**
     * Run pending queue items (call from cron)
     */
    public static function processQueue(int $limit = 10): array
    {
        self::init();
        
        $results = [];
        
        try {
            // Get pending items
            $items = DB::table('media_processing_queue')
                ->where('status', 'pending')
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();
            
            $processor = self::getProcessor();
            
            foreach ($items as $item) {
                // Mark as processing
                DB::table('media_processing_queue')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'processing',
                        'started_at' => date('Y-m-d H:i:s'),
                    ]);
                
                try {
                    $result = $processor->processUpload($item->digital_object_id);
                    
                    $status = ($result['success'] ?? false) ? 'completed' : 'failed';
                    $error = $result['error'] ?? null;
                    
                    DB::table('media_processing_queue')
                        ->where('id', $item->id)
                        ->update([
                            'status' => $status,
                            'error_message' => $error,
                            'completed_at' => date('Y-m-d H:i:s'),
                        ]);
                    
                    $results[$item->id] = $result;
                    
                } catch (Exception $e) {
                    // Mark as failed
                    DB::table('media_processing_queue')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'retry_count' => DB::raw('retry_count + 1'),
                            'completed_at' => date('Y-m-d H:i:s'),
                        ]);
                    
                    $results[$item->id] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
            
        } catch (Exception $e) {
            error_log('MediaUploadHook queue processing error: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Get queue status summary
     */
    public static function getQueueStatus(): array
    {
        try {
            $counts = DB::table('media_processing_queue')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            return [
                'pending' => $counts['pending'] ?? 0,
                'processing' => $counts['processing'] ?? 0,
                'completed' => $counts['completed'] ?? 0,
                'failed' => $counts['failed'] ?? 0,
            ];
        } catch (Exception $e) {
            return [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Retry failed queue items
     */
    public static function retryFailed(int $maxRetries = 3): int
    {
        try {
            return DB::table('media_processing_queue')
                ->where('status', 'failed')
                ->where('retry_count', '<', $maxRetries)
                ->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'started_at' => null,
                    'completed_at' => null,
                ]);
        } catch (Exception $e) {
            error_log('MediaUploadHook retry error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear completed queue items older than specified days
     */
    public static function cleanQueue(int $daysOld = 7): int
    {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
            
            return DB::table('media_processing_queue')
                ->where('status', 'completed')
                ->where('completed_at', '<', $cutoff)
                ->delete();
        } catch (Exception $e) {
            error_log('MediaUploadHook clean error: ' . $e->getMessage());
            return 0;
        }
    }
}