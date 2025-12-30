<?php

/**
 * AHG Settings Action
 *
 * Centralized settings management for all AHG plugins
 * Integrates into the AtoM admin settings menu
 *
 * @package    arAhgThemePlugin
 * @subpackage modules/settings/actions
 * @author     Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgSettingsAction extends sfAction
{
    // ACL Group IDs
    const GROUP_ADMINISTRATOR = 100;

    /**
     * Available settings sections
     */
    protected $sections = [
        'general' => [
            'label' => 'General Settings',
            'icon' => 'fa-cog',
            'description' => 'General AHG theme and plugin settings',
        ],
        'spectrum' => [
            'label' => 'Spectrum / Collections',
            'icon' => 'fa-archive',
            'description' => 'Museum collections management settings',
        ],
        'media' => [
            'label' => 'Media Player',
            'icon' => 'fa-play-circle',
            'description' => 'Enhanced media player configuration',
        ],
        'photos' => [
            'label' => 'Condition Photos',
            'icon' => 'fa-camera',
            'description' => 'Photo upload and thumbnail settings',
        ],
        'data_protection' => [
            'label' => 'Data Protection',
            'icon' => 'fa-shield-alt',
            'description' => 'POPIA/GDPR compliance settings',
        ],
        'iiif' => [
            'label' => 'IIIF Viewer',
            'icon' => 'fa-images',
            'description' => 'IIIF image viewer configuration',
        ],
        'jobs' => [
            'label' => 'Background Jobs',
            'icon' => 'fa-tasks',
            'description' => 'Job queue and scheduling settings',
        ],
    ];

    /**
     * Execute action
     */
    public function execute($request)
    {
        // Check admin permissions
        if (!$this->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->sections = $this->sections;
        $this->currentSection = $request->getParameter('section', 'general');

        // Validate section
        if (!isset($this->sections[$this->currentSection])) {
            $this->currentSection = 'general';
        }

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->processSettings($request);
        }

        // Load current settings
        $this->settings = $this->loadSettings($this->currentSection);

        // Load section-specific form
        $this->form = $this->getFormForSection($this->currentSection);
    }

    /**
     * Check if current user is administrator
     */
    protected function isAdministrator(): bool
    {
        $user = $this->getUser();

        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return false;
        }

        return DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->where('group_id', self::GROUP_ADMINISTRATOR)
            ->exists();
    }

    /**
     * Process settings form submission
     */
    protected function processSettings($request): void
    {
        $section = $this->currentSection;
        $settings = $request->getParameter('settings', []);
        $userId = $this->getUser()->getAttribute('user_id');

        foreach ($settings as $key => $value) {
            // Handle different value types
            if (is_array($value)) {
                $value = json_encode($value);
                $type = 'json';
            } elseif ($value === 'true' || $value === 'false') {
                $type = 'boolean';
            } elseif (is_numeric($value) && strpos($value, '.') === false) {
                $type = 'integer';
            } else {
                $type = 'string';
            }

            // Upsert setting using Laravel
            DB::table('ahg_settings')
                ->updateOrInsert(
                    ['setting_key' => $key],
                    [
                        'setting_value' => $value,
                        'setting_type' => $type,
                        'setting_group' => $section,
                        'updated_by' => $userId,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
        }

        $this->getUser()->setFlash('notice', 'Settings saved successfully.');

        $this->redirect(['module' => 'settings', 'action' => 'ahgSettings', 'section' => $section]);
    }

    /**
     * Load settings for a section
     */
    protected function loadSettings($section): array
    {
        $settings = [];

        try {
            $rows = DB::table('ahg_settings')
                ->where('setting_group', $section)
                ->select('setting_key', 'setting_value', 'setting_type')
                ->get();

            foreach ($rows as $row) {
                $value = $row->setting_value;

                switch ($row->setting_type) {
                    case 'boolean':
                        $value = $value === 'true' || $value === '1';
                        break;
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }

                $settings[$row->setting_key] = $value;
            }
        } catch (Exception $e) {
            // Table might not exist yet
        }

        // Merge with defaults
        $defaults = $this->getDefaultSettings($section);

        return array_merge($defaults, $settings);
    }

    /**
     * Get default settings for a section
     */
    protected function getDefaultSettings($section): array
    {
        $defaults = [
            'general' => [
                'ahg_theme_enabled' => true,
                'ahg_logo_path' => '',
                'ahg_primary_color' => '#1a5f7a',
                'ahg_secondary_color' => '#57837b',
                'ahg_footer_text' => 'Powered by The AHG',
                'ahg_show_branding' => true,
                'ahg_custom_css' => '',
            ],
            'spectrum' => [
                'spectrum_enabled' => true,
                'spectrum_default_currency' => 'ZAR',
                'spectrum_valuation_reminder_days' => 365,
                'spectrum_loan_default_period' => 90,
                'spectrum_condition_check_interval' => 180,
                'spectrum_auto_create_movement' => true,
                'spectrum_require_photos' => false,
                'spectrum_enable_barcodes' => false,
            ],
            'media' => [
                'media_player_type' => 'enhanced',
                'media_autoplay' => false,
                'media_show_controls' => true,
                'media_loop' => false,
                'media_default_volume' => 0.8,
                'media_show_download' => false,
                'media_max_file_size' => 104857600,
                'media_allowed_video_types' => ['video/mp4', 'video/webm', 'video/ogg'],
                'media_allowed_audio_types' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
            ],
            'photos' => [
                'photo_max_upload_size' => 10485760,
                'photo_allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/tiff'],
                'photo_create_thumbnails' => true,
                'photo_thumbnail_small' => 150,
                'photo_thumbnail_medium' => 300,
                'photo_thumbnail_large' => 600,
                'photo_jpeg_quality' => 85,
                'photo_png_compression' => 8,
                'photo_extract_exif' => true,
                'photo_auto_rotate' => true,
            ],
            'data_protection' => [
                'dp_enabled' => true,
                'dp_default_regulation' => 'popia',
                'dp_auto_deadline' => true,
                'dp_notify_overdue' => true,
                'dp_notify_email' => '',
                'dp_popia_fee' => 50.00,
                'dp_popia_fee_special' => 140.00,
                'dp_gdpr_response_days' => 30,
                'dp_popia_response_days' => 30,
                'dp_ccpa_response_days' => 45,
                'dp_require_dpo_approval' => false,
            ],
            'iiif' => [
                'iiif_enabled' => true,
                'iiif_viewer' => 'openseadragon',
                'iiif_server_url' => '',
                'iiif_default_zoom' => 1,
                'iiif_show_navigator' => true,
                'iiif_show_rotation' => true,
                'iiif_enable_annotations' => false,
                'iiif_max_zoom' => 10,
            ],
            'jobs' => [
                'jobs_enabled' => true,
                'jobs_max_concurrent' => 2,
                'jobs_timeout' => 3600,
                'jobs_retry_attempts' => 3,
                'jobs_cleanup_days' => 30,
                'jobs_notify_on_failure' => true,
                'jobs_notify_email' => '',
            ],
        ];

        return $defaults[$section] ?? [];
    }

    /**
     * Get form for section
     */
    protected function getFormForSection($section)
    {
        // Forms are built dynamically in the template
        return null;
    }

    /**
     * Get setting value helper (static method for use anywhere)
     */
    public static function getSetting($key, $default = null)
    {
        static $cache = [];

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        try {
            $row = DB::table('ahg_settings')
                ->where('setting_key', $key)
                ->select('setting_value', 'setting_type')
                ->first();

            if ($row) {
                $value = $row->setting_value;

                switch ($row->setting_type) {
                    case 'boolean':
                        $value = $value === 'true' || $value === '1';
                        break;
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }

                $cache[$key] = $value;
                return $value;
            }
        } catch (Exception $e) {
            // Table might not exist
        }

        return $default;
    }

    /**
     * Set setting value helper (static method for use anywhere)
     */
    public static function setSetting(string $key, $value, string $group = 'general', ?int $userId = null): bool
    {
        // Handle different value types
        if (is_array($value)) {
            $storedValue = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $storedValue = $value ? 'true' : 'false';
            $type = 'boolean';
        } elseif (is_int($value)) {
            $storedValue = (string) $value;
            $type = 'integer';
        } else {
            $storedValue = (string) $value;
            $type = 'string';
        }

        try {
            DB::table('ahg_settings')
                ->updateOrInsert(
                    ['setting_key' => $key],
                    [
                        'setting_value' => $storedValue,
                        'setting_type' => $type,
                        'setting_group' => $group,
                        'updated_by' => $userId,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete setting helper (static method)
     */
    public static function deleteSetting(string $key): bool
    {
        try {
            return DB::table('ahg_settings')
                ->where('setting_key', $key)
                ->delete() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all settings for a group (static method)
     */
    public static function getSettingsByGroup(string $group): array
    {
        $settings = [];

        try {
            $rows = DB::table('ahg_settings')
                ->where('setting_group', $group)
                ->select('setting_key', 'setting_value', 'setting_type')
                ->get();

            foreach ($rows as $row) {
                $value = $row->setting_value;

                switch ($row->setting_type) {
                    case 'boolean':
                        $value = $value === 'true' || $value === '1';
                        break;
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }

                $settings[$row->setting_key] = $value;
            }
        } catch (Exception $e) {
            // Table might not exist
        }

        return $settings;
    }

    /**
     * Ensure settings table exists
     */
    public static function ensureSettingsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS ahg_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            setting_group VARCHAR(100) DEFAULT 'general',
            updated_by INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_group (setting_group),
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($sql);
        } catch (Exception $e) {
            // Table might already exist
        }
    }
}