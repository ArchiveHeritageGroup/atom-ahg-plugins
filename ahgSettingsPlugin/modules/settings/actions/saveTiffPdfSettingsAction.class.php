<?php

/**
 * Save TIFF to PDF Merge Settings Action
 */

\AhgCore\Core\AhgDb::init();

use Illuminate\Database\Capsule\Manager as DB;

class saveTiffPdfSettingsAction extends sfAction
{
    public function execute($request)
    {
        // Check admin access
        if (!$this->context->user->hasCredential('administrator')) {
            return $this->jsonResponse(['success' => false, 'error' => 'Administrator access required']);
        }

        // Get JSON data
        $data = json_decode($request->getContent(), true);
        $settings = $data['settings'] ?? [];

        if (empty($settings)) {
            return $this->jsonResponse(['success' => false, 'error' => 'No settings provided']);
        }

        try {
            foreach ($settings as $key => $value) {
                $exists = DB::table('tiff_pdf_settings')
                    ->where('setting_key', $key)
                    ->exists();

                if ($exists) {
                    DB::table('tiff_pdf_settings')
                        ->where('setting_key', $key)
                        ->update([
                            'setting_value' => (string) $value,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            return $this->jsonResponse(['success' => true, 'message' => 'Settings saved successfully']);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    protected function jsonResponse($data)
    {
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');
        return $this->renderText(json_encode($data));
    }
}
