<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TTS Settings Action
 *
 * Admin UI for managing text-to-speech read-aloud settings per GLAM sector.
 * Settings stored in `ahg_tts_settings` table (ahgCorePlugin).
 */
class settingsTtsAction extends AhgController
{
    protected $sectors = ['all', 'archive', 'library', 'museum', 'gallery', 'dam'];

    protected $availableFields = [
        'title', 'scopeAndContent', 'arrangement', 'abstract', 'physicalDescription',
        'medium', 'technicalNotes', 'extentAndMedium', 'archivalHistory', 'acquisition',
        'appraisal', 'accruals', 'conditionsOfAccess', 'conditionsOfReproduction',
        'findingAids', 'relatedUnitsOfDescription', 'locationOfOriginals',
        'locationOfCopies', 'notes', 'publicationNote',
    ];

    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->i18n = $this->context->i18n;
        $this->form = new sfForm();

        if ($request->isMethod('post')) {
            $this->saveSettings($request);
            $this->getUser()->setFlash('notice', $this->i18n->__('Text-to-speech settings saved.'));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'tts']);
        }

        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $this->settings = [];

        try {
            $rows = DB::table('ahg_tts_settings')->get();

            foreach ($rows as $row) {
                $this->settings[$row->sector][$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            $this->settings = [];
        }

        $this->availableFieldsList = $this->availableFields;
        $this->sectorList = $this->sectors;
    }

    protected function saveSettings($request)
    {
        $tts = $request->getParameter('tts', []);

        // Global settings
        $globalKeys = ['enabled', 'default_rate', 'read_labels', 'keyboard_shortcuts'];
        foreach ($globalKeys as $key) {
            $value = $tts['all'][$key] ?? '0';
            if ($key === 'default_rate') {
                $value = $tts['all'][$key] ?? '1.0';
            }

            DB::table('ahg_tts_settings')->updateOrInsert(
                ['sector' => 'all', 'setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
            );
        }

        // Per-sector fields_to_read
        $sectorNames = ['archive', 'library', 'museum', 'gallery', 'dam'];
        foreach ($sectorNames as $sector) {
            $fields = $tts[$sector]['fields_to_read'] ?? [];
            if (!is_array($fields)) {
                $fields = [];
            }

            DB::table('ahg_tts_settings')->updateOrInsert(
                ['sector' => $sector, 'setting_key' => 'fields_to_read'],
                ['setting_value' => json_encode(array_values($fields)), 'updated_at' => date('Y-m-d H:i:s')]
            );
        }
    }
}
