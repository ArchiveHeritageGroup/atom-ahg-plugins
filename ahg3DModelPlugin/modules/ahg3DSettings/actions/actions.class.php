<?php

/**
 * ahg3DSettings Actions
 * 
 * Handles global 3D viewer settings configuration
 * 
 * @package ahg3DModelPlugin
 * @subpackage actions
 */
class ahg3DSettingsActions extends sfActions
{
    private $db;

    public function preExecute()
    {
        // Load the Laravel bootstrap
        $bootstrapPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }
        
        // Initialize database manager
        $this->db = \Illuminate\Database\Capsule\Manager::class;
        
        // Check admin access
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }
    }

    /**
     * Settings index page
     */
    public function executeIndex(sfWebRequest $request)
    {
        $db = $this->db;

        // Get all settings
        $this->settings = $db::table('viewer_3d_settings')
            ->orderBy('setting_key')
            ->get()
            ->keyBy('setting_key')
            ->toArray();

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

            $this->getUser()->setFlash('notice', 'Settings saved successfully.');
            $this->redirect(['module' => 'ahg3DSettings', 'action' => 'index']);
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
}
