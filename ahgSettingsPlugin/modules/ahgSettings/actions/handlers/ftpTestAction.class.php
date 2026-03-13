<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Test FTP/SFTP Connection Action
 * AJAX endpoint for testing FTP/SFTP connectivity from settings page
 */
class AhgSettingsFtpTestAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        $data = json_decode($request->getContent(), true);
        $protocol = $data['protocol'] ?? 'sftp';
        $host = $data['host'] ?? '';
        $port = $data['port'] ?? ($protocol === 'sftp' ? 22 : 21);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $remotePath = $data['remote_path'] ?? '/uploads';

        if (empty($host)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No host specified']));
        }

        try {
            // Load FtpService from plugin
            $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgFtpPlugin';
            $servicePath = $pluginDir . '/lib/Services/FtpService.php';

            if (!file_exists($servicePath)) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'FTP plugin not installed']));
            }

            require_once $servicePath;

            $svc = new \AhgFtpPlugin\Services\FtpService([
                'protocol' => $protocol,
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'remote_path' => $remotePath,
                'passive_mode' => $data['passive_mode'] ?? 'true',
            ]);

            $result = $svc->testConnection();

            return $this->renderText(json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
            ]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
