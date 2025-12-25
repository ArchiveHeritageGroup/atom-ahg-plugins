<?php
use AtomExtensions\Services\AclService;

class AhgSettingsExportAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        // Get all settings
        $conn = Propel::getConnection();
        $sql = "SELECT setting_key, setting_value, setting_group FROM ahg_settings ORDER BY setting_group, setting_key";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($settings[$row['setting_group']])) {
                $settings[$row['setting_group']] = [];
            }
            $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
        }

        // Export as JSON
        $export = [
            'exported_at' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'settings' => $settings
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT);

        // Send as download
        $response = $this->getResponse();
        $response->setHttpHeader('Content-Type', 'application/json');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="ahg-settings-' . date('Y-m-d') . '.json"');
        $response->setContent($json);

        return sfView::NONE;
    }
}
