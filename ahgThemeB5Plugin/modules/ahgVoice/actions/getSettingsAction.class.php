<?php
use Illuminate\Database\Capsule\Manager as DB;

class ahgVoiceGetSettingsAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $settings = [];
        try {
            $rows = DB::table('ahg_settings')
                ->where('setting_group', 'voice_ai')
                ->get(['setting_key', 'setting_value']);
            foreach ($rows as $row) {
                // Never expose API key to client
                if ($row->setting_key === 'voice_anthropic_api_key') continue;
                $settings[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // Table may not exist — return defaults
        }

        $response = $this->getResponse();
        $response->setContent(json_encode(['success' => true, 'settings' => $settings]));
        return sfView::NONE;
    }
}
