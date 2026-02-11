<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * ahg3DSettings Actions
 * 
 * Handles global 3D viewer settings configuration
 * 
 * @package ahg3DModelPlugin
 * @subpackage actions
 */
class model3dSettingsActions extends AhgController
{
    private $db;

    public function boot(): void
    {
        // Load the Laravel bootstrap
        $bootstrapPath = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }
        
        // Initialize database manager
        $this->db = \Illuminate\Database\Capsule\Manager::class;
        
        // Check admin access
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }
    }

    /**
     * Settings index page
     */
    public function executeIndex($request)
    {
        $db = $this->db;

        // Get all settings
        $this->settings = $db::table('viewer_3d_settings')
            ->orderBy('setting_key')
            ->get()
            ->keyBy('setting_key')
            ->toArray();

        // Get TripoSR health status
        $this->triposrHealth = $this->checkTripoSRHealth();

        // Get statistics
        $this->stats = [
            'total_models' => $db::table('object_3d_model')->count(),
            'total_hotspots' => $db::table('object_3d_hotspot')->count(),
            'ar_enabled_models' => $db::table('object_3d_model')->where('ar_enabled', 1)->count(),
            'total_views' => $db::table('object_3d_audit_log')->where('action', 'view')->count(),
            'total_ar_views' => $db::table('object_3d_audit_log')->where('action', 'ar_view')->count(),
            'storage_used' => $db::table('object_3d_model')->sum('file_size'),
        ];

        // Get format distribution
        $this->formatStats = $db::table('object_3d_model')
            ->select('format', $db::raw('COUNT(*) as count'))
            ->groupBy('format')
            ->pluck('count', 'format')
            ->toArray();

        // Handle form submission
        if ($request->isMethod('post')) {
            $settingsToUpdate = [
                'default_viewer' => ['value' => $request->getParameter('default_viewer'), 'type' => 'string'],
                'enable_ar' => ['value' => $request->getParameter('enable_ar') ? '1' : '0', 'type' => 'boolean'],
                'enable_fullscreen' => ['value' => $request->getParameter('enable_fullscreen') ? '1' : '0', 'type' => 'boolean'],
                'enable_download' => ['value' => $request->getParameter('enable_download') ? '1' : '0', 'type' => 'boolean'],
                'default_background' => ['value' => $request->getParameter('default_background'), 'type' => 'string'],
                'default_exposure' => ['value' => $request->getParameter('default_exposure'), 'type' => 'string'],
                'default_shadow_intensity' => ['value' => $request->getParameter('default_shadow_intensity'), 'type' => 'string'],
                'max_file_size_mb' => ['value' => $request->getParameter('max_file_size_mb'), 'type' => 'integer'],
                'enable_annotations' => ['value' => $request->getParameter('enable_annotations') ? '1' : '0', 'type' => 'boolean'],
                'enable_auto_rotate' => ['value' => $request->getParameter('enable_auto_rotate') ? '1' : '0', 'type' => 'boolean'],
                'rotation_speed' => ['value' => $request->getParameter('rotation_speed'), 'type' => 'integer'],
                'watermark_enabled' => ['value' => $request->getParameter('watermark_enabled') ? '1' : '0', 'type' => 'boolean'],
                'watermark_text' => ['value' => $request->getParameter('watermark_text'), 'type' => 'string'],
            ];

            foreach ($settingsToUpdate as $key => $data) {
                $this->updateSetting($key, $data['value'], $data['type']);
            }

            // Handle allowed formats
            $allowedFormats = $request->getParameter('allowed_formats', []);
            $this->updateSetting('allowed_formats', json_encode($allowedFormats), 'json');

            // Handle TripoSR settings
            $triposrSettings = [
                'triposr_enabled' => ['value' => $request->getParameter('triposr_enabled') ? '1' : '0', 'type' => 'boolean'],
                'triposr_api_url' => ['value' => $request->getParameter('triposr_api_url', 'http://127.0.0.1:5050'), 'type' => 'string'],
                'triposr_mode' => ['value' => $request->getParameter('triposr_mode', 'local'), 'type' => 'string'],
                'triposr_remote_url' => ['value' => $request->getParameter('triposr_remote_url', ''), 'type' => 'string'],
                'triposr_remote_api_key' => ['value' => $request->getParameter('triposr_remote_api_key', ''), 'type' => 'string'],
                'triposr_timeout' => ['value' => $request->getParameter('triposr_timeout', '300'), 'type' => 'integer'],
                'triposr_remove_bg' => ['value' => $request->getParameter('triposr_remove_bg') ? '1' : '0', 'type' => 'boolean'],
                'triposr_foreground_ratio' => ['value' => $request->getParameter('triposr_foreground_ratio', '0.85'), 'type' => 'string'],
                'triposr_mc_resolution' => ['value' => $request->getParameter('triposr_mc_resolution', '256'), 'type' => 'integer'],
                'triposr_bake_texture' => ['value' => $request->getParameter('triposr_bake_texture') ? '1' : '0', 'type' => 'boolean'],
            ];

            foreach ($triposrSettings as $key => $data) {
                // Don't overwrite API key with masked value
                if ($key === 'triposr_remote_api_key' && $data['value'] === '***') {
                    continue;
                }
                $this->updateSetting($key, $data['value'], $data['type']);
            }

            // Configure the TripoSR API with new settings
            $this->configureTripoSRApi();

            $this->getUser()->setFlash('notice', 'Settings saved successfully.');
            $this->redirect(['module' => 'model3dSettings', 'action' => 'index']);
        }
    }

    /**
     * Update a setting in the database
     */
    private function updateSetting(string $key, $value, string $type = 'string')
    {
        $db = $this->db;

        $db::table('viewer_3d_settings')
            ->updateOrInsert(
                ['setting_key' => $key],
                [
                    'setting_value' => (string)$value,
                    'setting_type' => $type,
                ]
            );
    }

    /**
     * Check TripoSR API health status
     */
    private function checkTripoSRHealth(): array
    {
        $db = $this->db;

        $apiUrl = $db::table('viewer_3d_settings')
            ->where('setting_key', 'triposr_api_url')
            ->value('setting_value') ?? 'http://127.0.0.1:5050';

        try {
            $ch = curl_init($apiUrl . '/health');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200) {
                return [
                    'status' => 'error',
                    'message' => $error ?: 'HTTP ' . $httpCode,
                    'api_url' => $apiUrl,
                ];
            }

            $data = json_decode($response, true);
            $data['status'] = 'ok';
            $data['api_url'] = $apiUrl;

            return $data;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'api_url' => $apiUrl,
            ];
        }
    }

    /**
     * Configure TripoSR API with current settings
     */
    private function configureTripoSRApi(): bool
    {
        $db = $this->db;

        $settings = $db::table('viewer_3d_settings')
            ->whereIn('setting_key', [
                'triposr_api_url', 'triposr_mode', 'triposr_remote_url',
                'triposr_remote_api_key', 'triposr_timeout', 'triposr_remove_bg',
                'triposr_foreground_ratio', 'triposr_mc_resolution',
            ])
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $apiUrl = $settings['triposr_api_url'] ?? 'http://127.0.0.1:5050';

        $config = [
            'mode' => $settings['triposr_mode'] ?? 'local',
            'remote_url' => $settings['triposr_remote_url'] ?? '',
            'remote_api_key' => $settings['triposr_remote_api_key'] ?? '',
            'default_remove_bg' => ($settings['triposr_remove_bg'] ?? '1') === '1',
            'default_foreground_ratio' => (float)($settings['triposr_foreground_ratio'] ?? 0.85),
            'default_mc_resolution' => (int)($settings['triposr_mc_resolution'] ?? 256),
            'timeout_seconds' => (int)($settings['triposr_timeout'] ?? 300),
        ];

        try {
            $ch = curl_init($apiUrl . '/config');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($config),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * TripoSR settings page (standalone)
     */
    public function executeTriposr($request)
    {
        $db = $this->db;

        // Get TripoSR-specific settings
        $this->settings = $db::table('viewer_3d_settings')
            ->where('setting_key', 'LIKE', 'triposr_%')
            ->get()
            ->keyBy('setting_key')
            ->toArray();

        // Get health status
        $this->health = $this->checkTripoSRHealth();

        // Get job stats
        $this->stats = [
            'total_jobs' => $db::table('triposr_jobs')->count(),
            'completed' => $db::table('triposr_jobs')->where('status', 'completed')->count(),
            'failed' => $db::table('triposr_jobs')->where('status', 'failed')->count(),
            'pending' => $db::table('triposr_jobs')->whereIn('status', ['pending', 'processing'])->count(),
        ];

        // Get recent jobs
        $this->recentJobs = $db::table('triposr_jobs')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Handle form submission
        if ($request->isMethod('post')) {
            $triposrSettings = [
                'triposr_enabled' => $request->getParameter('triposr_enabled') ? '1' : '0',
                'triposr_api_url' => $request->getParameter('triposr_api_url', 'http://127.0.0.1:5050'),
                'triposr_mode' => $request->getParameter('triposr_mode', 'local'),
                'triposr_remote_url' => $request->getParameter('triposr_remote_url', ''),
                'triposr_remote_api_key' => $request->getParameter('triposr_remote_api_key', ''),
                'triposr_timeout' => $request->getParameter('triposr_timeout', '300'),
                'triposr_remove_bg' => $request->getParameter('triposr_remove_bg') ? '1' : '0',
                'triposr_foreground_ratio' => $request->getParameter('triposr_foreground_ratio', '0.85'),
                'triposr_mc_resolution' => $request->getParameter('triposr_mc_resolution', '256'),
                'triposr_bake_texture' => $request->getParameter('triposr_bake_texture') ? '1' : '0',
            ];

            foreach ($triposrSettings as $key => $value) {
                // Don't overwrite API key with masked value
                if ($key === 'triposr_remote_api_key' && $value === '***') {
                    continue;
                }
                $this->updateSetting($key, $value, 'string');
            }

            // Configure the API
            $this->configureTripoSRApi();

            $this->getUser()->setFlash('notice', 'TripoSR settings saved successfully.');
            $this->redirect(['module' => 'model3dSettings', 'action' => 'triposr']);
        }
    }
}
