<?php

/**
 * Media Processor Settings Actions
 * 
 * Admin module for configuring media processing settings:
 * - Thumbnail generation settings
 * - Preview clip settings
 * - Waveform settings
 * - Transcription settings
 * 
 * @package ahgThemeB5Plugin
 * @subpackage arMediaSettings
 * @author Johan Pieterse - The Archive and Heritage Group
 */

class arMediaSettingsActions extends sfActions
{
    /**
     * Check admin access
     */
    public function preExecute()
    {
        parent::preExecute();
        
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }
    
    /**
     * Index - Main settings page
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->settings = $this->loadSettings();
        $this->grouped = $this->groupSettings($this->settings);
        
        // Check tool availability
        $this->tools = [
            'ffmpeg' => $this->checkTool('/usr/bin/ffmpeg'),
            'ffprobe' => $this->checkTool('/usr/bin/ffprobe'),
            'mediainfo' => $this->checkTool('/usr/bin/mediainfo'),
            'exiftool' => $this->checkTool('/usr/bin/exiftool'),
            'whisper' => $this->checkTool('/usr/local/bin/whisper') || $this->checkTool('/usr/bin/whisper'),
        ];
    }
    
    /**
     * Save settings
     */
    public function executeSave(sfWebRequest $request)
    {
        if ($request->isMethod('POST')) {
            $settings = $request->getParameter('settings', []);
            
            $pdo = Propel::getConnection();
            
            foreach ($settings as $key => $value) {
                // Get setting type
                $stmt = $pdo->prepare("SELECT setting_type FROM media_processor_settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $row = $stmt->fetch(PDO::FETCH_OBJ);
                
                $type = $row ? $row->setting_type : 'string';
                
                // Convert checkbox values
                if ($type === 'boolean') {
                    $value = $value ? '1' : '0';
                } elseif ($type === 'json' && is_array($value)) {
                    $value = json_encode($value);
                }
                
                // Update or insert
                $stmt = $pdo->prepare("
                    INSERT INTO media_processor_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }
            
            // Handle unchecked checkboxes (they won't be in POST data)
            $booleanKeys = [
                'thumbnail_enabled', 'preview_enabled', 'waveform_enabled',
                'poster_enabled', 'audio_preview_enabled', 'transcription_enabled',
                'auto_detect_language'
            ];
            
            foreach ($booleanKeys as $key) {
                if (!isset($settings[$key])) {
                    $stmt = $pdo->prepare("
                        UPDATE media_processor_settings SET setting_value = '0', updated_at = NOW()
                        WHERE setting_key = ?
                    ");
                    $stmt->execute([$key]);
                }
            }
            
            $this->getUser()->setFlash('notice', 'Media processing settings saved successfully.');
        }
        
        $this->redirect(['module' => 'arMediaSettings', 'action' => 'index']);
    }
    
    /**
     * Test processing on a specific file
     */
    public function executeTest(sfWebRequest $request)
    {
        $digitalObjectId = $request->getParameter('id');
        
        if (!$digitalObjectId) {
            $this->forward404('Digital object ID required');
        }
        
        // Load processor
        require_once sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/lib/MediaUploadHook.php';
        
        $result = MediaUploadHook::processDigitalObject(
            QubitDigitalObject::getById($digitalObjectId)
        );
        
        $this->result = $result;
        $this->digitalObjectId = $digitalObjectId;
    }
    
    /**
     * View processing queue
     */
    public function executeQueue(sfWebRequest $request)
    {
        $pdo = Propel::getConnection();
        
        // Get queue statistics
        $stmt = $pdo->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM media_processing_queue
            GROUP BY status
        ");
        $this->stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $this->stats[$row->status] = $row->count;
        }
        
        // Get recent items
        $stmt = $pdo->query("
            SELECT q.*, d.name as filename
            FROM media_processing_queue q
            LEFT JOIN digital_object d ON q.digital_object_id = d.id
            ORDER BY q.created_at DESC
            LIMIT 50
        ");
        $this->items = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Process queue items manually
     */
    public function executeProcessQueue(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/lib/MediaUploadHook.php';
        
        $limit = $request->getParameter('limit', 5);
        $results = MediaUploadHook::processQueue($limit);
        
        $this->getUser()->setFlash('notice', 'Processed ' . count($results) . ' queue items.');
        $this->redirect(['module' => 'arMediaSettings', 'action' => 'queue']);
    }
    
    /**
     * Clear completed queue items
     */
    public function executeClearQueue(sfWebRequest $request)
    {
        $pdo = Propel::getConnection();
        $stmt = $pdo->exec("DELETE FROM media_processing_queue WHERE status IN ('completed', 'failed')");
        
        $this->getUser()->setFlash('notice', 'Queue cleared.');
        $this->redirect(['module' => 'arMediaSettings', 'action' => 'queue']);
    }
    
    /**
     * Load settings from database
     */
    private function loadSettings(): array
    {
        $settings = [];
        
        try {
            $pdo = Propel::getConnection();
            $stmt = $pdo->query("SELECT * FROM media_processor_settings ORDER BY setting_group, setting_key");
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $value = $row->setting_value;
                
                switch ($row->setting_type) {
                    case 'boolean':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'integer':
                        $value = (int)$value;
                        break;
                    case 'float':
                        $value = (float)$value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $settings[$row->setting_key] = [
                    'value' => $value,
                    'type' => $row->setting_type,
                    'group' => $row->setting_group,
                    'description' => $row->description,
                ];
            }
        } catch (Exception $e) {
            // Table might not exist
        }
        
        return $settings;
    }
    
    /**
     * Group settings by group name
     */
    private function groupSettings(array $settings): array
    {
        $grouped = [];
        
        foreach ($settings as $key => $setting) {
            $group = $setting['group'] ?? 'general';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][$key] = $setting;
        }
        
        return $grouped;
    }
    
    /**
     * Check if a tool is available
     */
    private function checkTool(string $path): bool
    {
        return file_exists($path) && is_executable($path);
    }
}
