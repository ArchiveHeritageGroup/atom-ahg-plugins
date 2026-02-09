<?php

use Illuminate\Database\Capsule\Manager as DB;

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
 * @subpackage ahgMediaSettings
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class mediaSettingsActions extends AhgActions
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

            foreach ($settings as $key => $value) {
                // Get setting type
                $row = DB::table('media_processor_settings')
                    ->where('setting_key', $key)
                    ->first(['setting_type']);

                $type = $row ? $row->setting_type : 'string';

                // Convert checkbox values
                if ('boolean' === $type) {
                    $value = $value ? '1' : '0';
                } elseif ('json' === $type && is_array($value)) {
                    $value = json_encode($value);
                }

                // Update or insert
                DB::table('media_processor_settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'updated_at' => DB::raw('NOW()')]
                );
            }

            // Handle unchecked checkboxes (they won't be in POST data)
            $booleanKeys = [
                'thumbnail_enabled', 'preview_enabled', 'waveform_enabled',
                'poster_enabled', 'audio_preview_enabled', 'transcription_enabled',
                'auto_detect_language',
            ];

            foreach ($booleanKeys as $key) {
                if (!isset($settings[$key])) {
                    DB::table('media_processor_settings')
                        ->where('setting_key', $key)
                        ->update(['setting_value' => '0', 'updated_at' => DB::raw('NOW()')]);
                }
            }

            $this->getUser()->setFlash('notice', 'Media processing settings saved successfully.');
        }

        $this->redirect(['module' => 'mediaSettings', 'action' => 'index']);
    }

    /**
     * Test processing on a specific file
     */
    public function executeTest(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        if (!$slug) {
            $this->forward404('Please select an archival description');
        }

        // Find information object by slug
        $informationObject = QubitInformationObject::getBySlug($slug);
        if (!$informationObject) {
            $this->forward404('Archival description not found');
        }

        // Get the digital object for this information object
        $digitalObject = $informationObject->getDigitalObject();
        if (!$digitalObject) {
            $this->getUser()->setFlash('error', 'No digital object attached to this archival description');
            $this->redirect(['module' => 'mediaSettings', 'action' => 'index']);
        }

        // Load processor
        require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/MediaUploadHook.php';
        $result = MediaUploadHook::processDigitalObject($digitalObject);

        $this->result = $result;
        $this->digitalObjectId = $digitalObject->id;
        $this->informationObject = $informationObject;
    }

    /**
     * JSON autocomplete for information objects
     */
    public function executeAutocomplete(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = $request->getParameter('query', '');
        if (strlen($query) < 2) {
            return $this->renderText(json_encode([]));
        }


        $results = DB::table('information_object')
            ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
            ->join('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('digital_object', 'information_object.id', '=', 'digital_object.object_id')
            ->select([
                'information_object.id',
                'information_object.identifier',
                'information_object_i18n.title',
                'slug.slug',
                'digital_object.id as digital_object_id',
            ])
            ->where('information_object_i18n.culture', 'en')
            ->where(function ($q) use ($query) {
                $q->where('information_object_i18n.title', 'LIKE', '%'.$query.'%')
                    ->orWhere('information_object.identifier', 'LIKE', '%'.$query.'%');
            })
            ->whereNotNull('digital_object.id')
            ->orderBy('information_object_i18n.title')
            ->limit(20)
            ->get();

        $formatted = [];
        foreach ($results as $row) {
            $formatted[] = [
                'slug' => $row->slug,
                'title' => $row->title ?: '(Untitled)',
                'identifier' => $row->identifier ?: '',
            ];
        }

        return $this->renderText(json_encode($formatted));
    }

    public function executeQueue(sfWebRequest $request)
    {
        // Get queue statistics
        $stats = DB::table('media_processing_queue')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $this->stats = [];
        foreach ($stats as $row) {
            $this->stats[$row->status] = $row->count;
        }

        // Get recent items
        $this->items = DB::table('media_processing_queue as q')
            ->leftJoin('digital_object as d', 'q.digital_object_id', '=', 'd.id')
            ->select('q.*', 'd.name as filename')
            ->orderBy('q.created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Process queue items manually
     */
    public function executeProcessQueue(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/MediaUploadHook.php';

        $limit = $request->getParameter('limit', 5);
        $results = MediaUploadHook::processQueue($limit);

        $this->getUser()->setFlash('notice', 'Processed '.count($results).' queue items.');
        $this->redirect(['module' => 'mediaSettings', 'action' => 'queue']);
    }

    /**
     * Clear completed queue items
     */
    public function executeClearQueue(sfWebRequest $request)
    {
        DB::table('media_processing_queue')
            ->whereIn('status', ['completed', 'failed'])
            ->delete();

        $this->getUser()->setFlash('notice', 'Queue cleared.');
        $this->redirect(['module' => 'mediaSettings', 'action' => 'queue']);
    }

    /**
     * Load settings from database
     */
    private function loadSettings(): array
    {
        $settings = [];

        try {
            $rows = DB::table('media_processor_settings')
                ->orderBy('setting_group')
                ->orderBy('setting_key')
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
