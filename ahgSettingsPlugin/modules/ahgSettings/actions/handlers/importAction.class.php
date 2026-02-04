<?php
use AtomExtensions\Services\AclService;
use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsImportAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        if ($request->isMethod('post')) {
            // Handle file upload
            $file = $request->getFiles('settings_file');

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->getUser()->setFlash('error', 'Please select a valid settings file.');
                $this->redirect(['module' => 'ahgSettings', 'action' => 'import']);
            }

            $content = file_get_contents($file['tmp_name']);
            $data = json_decode($content, true);

            if (!$data || !isset($data['settings'])) {
                $this->getUser()->setFlash('error', 'Invalid settings file format.');
                $this->redirect(['module' => 'ahgSettings', 'action' => 'import']);
            }

            // Import settings
            $imported = 0;

            foreach ($data['settings'] as $group => $groupSettings) {
                foreach ($groupSettings as $key => $value) {
                    DB::table('ahg_settings')->updateOrInsert(
                        ['setting_key' => $key],
                        [
                            'setting_value' => $value,
                            'setting_group' => $group,
                            'updated_at' => DB::raw('NOW()')
                        ]
                    );
                    $imported++;
                }
            }

            $this->getUser()->setFlash('notice', "Successfully imported {$imported} settings.");
            $this->redirect(['module' => 'ahgSettings', 'action' => 'index']);
        }

        // Show import form
        $this->setTemplate('ahgImportSettings');
    }
}
