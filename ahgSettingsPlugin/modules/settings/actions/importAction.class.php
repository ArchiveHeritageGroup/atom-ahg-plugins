<?php
use AtomExtensions\Services\AclService;

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
                $this->redirect(['module' => 'settings', 'action' => 'import']);
            }

            $content = file_get_contents($file['tmp_name']);
            $data = json_decode($content, true);

            if (!$data || !isset($data['settings'])) {
                $this->getUser()->setFlash('error', 'Invalid settings file format.');
                $this->redirect(['module' => 'settings', 'action' => 'import']);
            }

            // Import settings
            $conn = Propel::getConnection();
            $imported = 0;

            foreach ($data['settings'] as $group => $groupSettings) {
                foreach ($groupSettings as $key => $value) {
                    $sql = "INSERT INTO ahg_settings (setting_key, setting_value, setting_group, updated_at)
                            VALUES (?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$key, $value, $group, $value]);
                    $imported++;
                }
            }

            $this->getUser()->setFlash('notice', "Successfully imported {$imported} settings.");
            $this->redirect(['module' => 'settings', 'action' => 'index']);
        }

        // Show import form
        $this->setTemplate('ahgImportSettings');
    }
}
