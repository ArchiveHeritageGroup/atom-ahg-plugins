<?php
use AtomExtensions\Services\AclService;

class AhgSettingsSectionAction extends sfAction
{
    protected $sections = [
        'general' => ['label' => 'General Settings', 'icon' => 'fa-cog'],
        'metadata' => [
            'meta_extract_on_upload', 'meta_auto_populate',
            'meta_images', 'meta_pdf', 'meta_office', 'meta_video', 'meta_audio',
            'meta_extract_gps', 'meta_extract_technical', 'meta_extract_xmp', 'meta_extract_iptc',
            'meta_overwrite_existing', 'meta_create_access_points', 'meta_field_mappings',
            'meta_dam_batch_mode', 'meta_dam_preserve_filename', 'meta_dam_extract_color',
            'meta_dam_extract_faces', 'meta_dam_auto_tag', 'meta_dam_generate_thumbnail',
            'meta_dam_thumb_small', 'meta_dam_thumb_medium', 'meta_dam_thumb_large', 'meta_dam_thumb_preview',
            'map_title_dam', 'map_creator_dam', 'map_keywords_dam', 'map_description_dam',
            'map_date_dam', 'map_copyright_dam', 'map_technical_dam', 'map_gps_dam', 'meta_replace_placeholders'
        ],
        'iiif' => ['label' => 'IIIF Viewer', 'icon' => 'fa-images'],
        'spectrum' => ['label' => 'Spectrum / Collections', 'icon' => 'fa-archive'],
        'data_protection' => ['label' => 'Data Protection', 'icon' => 'fa-shield-alt'],
        'faces' => ['label' => 'Face Detection', 'icon' => 'fa-user-circle'],
        'media' => ['label' => 'Media Player', 'icon' => 'fa-play-circle'],
        'photos' => ['label' => 'Condition Photos', 'icon' => 'fa-camera'],
        'jobs' => ['label' => 'Background Jobs', 'icon' => 'fa-tasks'],
		'fuseki' => ['fuseki_sync_enabled', 'fuseki_queue_enabled', 'fuseki_sync_on_save', 'fuseki_sync_on_delete', 'fuseki_cascade_delete'],
		'fuseki' => ['label' => 'Fuseki / RIC', 'icon' => 'fa-project-diagram'],
    ];

    protected $checkboxFields = [
        'general' => ['ahg_theme_enabled', 'ahg_show_branding'],
        'metadata' => [
            'meta_extract_on_upload', 'meta_auto_populate',
            'meta_images', 'meta_pdf', 'meta_office', 'meta_video', 'meta_audio',
            'meta_extract_gps', 'meta_extract_technical', 'meta_extract_xmp', 'meta_extract_iptc',
            'meta_overwrite_existing', 'meta_create_access_points', 'meta_field_mappings',
            'meta_dam_batch_mode', 'meta_dam_preserve_filename', 'meta_dam_extract_color',
            'meta_dam_extract_faces', 'meta_dam_auto_tag', 'meta_dam_generate_thumbnail',
            'meta_dam_thumb_small', 'meta_dam_thumb_medium', 'meta_dam_thumb_large', 'meta_dam_thumb_preview',
            'map_title_dam', 'map_creator_dam', 'map_keywords_dam', 'map_description_dam',
            'map_date_dam', 'map_copyright_dam', 'map_technical_dam', 'map_gps_dam', 'meta_replace_placeholders'
        ],
        'spectrum' => ['spectrum_enabled', 'spectrum_auto_create_movement', 'spectrum_require_photos'],
        'iiif' => ['iiif_enabled', 'iiif_show_navigator', 'iiif_show_rotation', 'iiif_show_fullscreen'],
        'data_protection' => ['dp_enabled', 'dp_notify_overdue', 'dp_anonymize_on_delete', 'dp_audit_logging', 'dp_consent_required'],
        'faces' => ['face_detect_enabled', 'face_auto_match', 'face_auto_link', 'face_blur_unmatched', 'face_store_embeddings'],
        'media' => ['media_autoplay', 'media_show_controls', 'media_loop', 'media_show_waveform', 'media_transcription_enabled'],
        'photos' => ['photo_create_thumbnails', 'photo_extract_exif', 'photo_auto_rotate'],
        'jobs' => ['jobs_enabled', 'jobs_notify_failure'],
		'fuseki' => ['fuseki_sync_enabled', 'fuseki_queue_enabled', 'fuseki_sync_on_save', 'fuseki_sync_on_delete', 'fuseki_cascade_delete'],
    ];

    
    // Map sections to required plugins - section only shows if plugin is enabled
    protected $sectionPluginMap = [
        'spectrum' => 'ahgSpectrumPlugin',
        'data_protection' => 'ahgDataProtectionPlugin',
        'photos' => 'ahgConditionPlugin',
        'fuseki' => 'ahgRicExplorerPlugin',
        'audit' => 'ahgAuditTrailPlugin',
        'faces' => 'ahgFaceDetectionPlugin',
    ];

    // Check if a plugin is enabled
    protected function isPluginEnabled($pluginName)
    {
        static $enabledPlugins = null;
        if ($enabledPlugins === null) {
            $enabledPlugins = [];
            try {
                $results = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
                    ->where('is_enabled', 1)
                    ->pluck('name')
                    ->toArray();
                $enabledPlugins = array_flip($results);
            } catch (\Exception $e) {
                // Fallback - check class exists
            }
        }
        return isset($enabledPlugins[$pluginName]) || class_exists($pluginName . 'Configuration');
    }

    // Get filtered sections (only show if required plugin is enabled)
    protected function getFilteredSections()
    {
        $filtered = [];
        foreach ($this->sections as $key => $config) {
            // Check if section requires a plugin
            if (isset($this->sectionPluginMap[$key])) {
                if (!$this->isPluginEnabled($this->sectionPluginMap[$key])) {
                    continue; // Skip this section - plugin not enabled
                }
            }
            $filtered[$key] = $config;
        }
        return $filtered;
    }

    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        $this->sections = $this->getFilteredSections();
        $this->currentSection = $request->getParameter('section', 'general');

        if (!isset($this->sections[$this->currentSection])) {
            $this->currentSection = 'general';
        }

        // Debug: Log the request method
        error_log("AHG Settings: Method = " . $request->getMethod() . ", Section = " . $this->currentSection);

        if ($request->isMethod('post')) {
            error_log("AHG Settings: Processing POST for section " . $this->currentSection);
            $this->processSettings($request);
        }

        $this->settings = $this->loadSettings($this->currentSection);
    }

    protected function processSettings($request)
    {
        $settings = $request->getParameter('settings', []);
        
        error_log("AHG Settings: Received " . count($settings) . " settings");
        error_log("AHG Settings: Keys = " . implode(', ', array_keys($settings)));

        $conn = Propel::getConnection();

        // Handle unchecked checkboxes
        if (isset($this->checkboxFields[$this->currentSection])) {
            foreach ($this->checkboxFields[$this->currentSection] as $checkboxField) {
                if (!isset($settings[$checkboxField])) {
                    $settings[$checkboxField] = 'false';
                }
            }
        }

        $saved = 0;
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            
            try {
                $sql = "INSERT INTO ahg_settings (setting_key, setting_value, setting_group, updated_at)
                        VALUES (?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$key, $value, $this->currentSection, $value]);
                $saved++;
            } catch (Exception $e) {
                error_log("AHG Settings: Error saving $key: " . $e->getMessage());
            }
        }

        error_log("AHG Settings: Saved $saved settings");
        
        // Regenerate static CSS
        if ($this->currentSection === 'general') {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/lib/AhgCssGenerator.class.php';
            AhgCssGenerator::generate();
        }
        $this->getUser()->setFlash('notice', "Settings saved successfully. ($saved items)");
        $this->redirect(['module' => 'ahgSettings', 'action' => 'section', 'section' => $this->currentSection]);
    }

    protected function loadSettings($section)
    {
        $settings = [];
        try {
            $conn = Propel::getConnection();
            $sql = "SELECT setting_key, setting_value FROM ahg_settings WHERE setting_group = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$section]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("AHG Settings: Error loading: " . $e->getMessage());
        }
        return $settings;
    }
}
