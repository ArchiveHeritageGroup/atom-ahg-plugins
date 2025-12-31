<?php

// Load framework autoloader
$frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
if (file_exists($frameworkBootstrap)) {
    require_once $frameworkBootstrap;
}

class backupActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();
        
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeIndex(sfWebRequest $request)
    {
        $backupService = new \AtomExtensions\Services\BackupService();
        $scheduleService = new \AtomExtensions\Services\ScheduleService();
        
        $this->backups = $backupService->listBackups();
        $this->schedules = $scheduleService->getSchedules();
        $this->backupService = $backupService;
    }

    public function executeSettings(sfWebRequest $request)
    {
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $this->settings = $settingsService->getAllWithMeta();
        $this->settingsMap = $settingsService->all();
        
        if ($request->isMethod('post')) {
            $data = [
                'backup_path' => $request->getParameter('backup_path'),
                'log_path' => $request->getParameter('log_path'),
                'max_backups' => (int)$request->getParameter('max_backups'),
                'retention_days' => (int)$request->getParameter('retention_days'),
                'include_database' => $request->getParameter('include_database') ? '1' : '0',
                'include_uploads' => $request->getParameter('include_uploads') ? '1' : '0',
                'include_plugins' => $request->getParameter('include_plugins') ? '1' : '0',
                'include_framework' => $request->getParameter('include_framework') ? '1' : '0',
                'compression_level' => (int)$request->getParameter('compression_level'),
                'notify_email' => $request->getParameter('notify_email'),
                'notify_on_success' => $request->getParameter('notify_on_success') ? '1' : '0',
                'notify_on_failure' => $request->getParameter('notify_on_failure') ? '1' : '0',
            ];
            
            $customPlugins = $request->getParameter('custom_plugins');
            if ($customPlugins) {
                $plugins = array_filter(array_map('trim', explode("\n", $customPlugins)));
                $data['custom_plugins'] = json_encode(array_values($plugins));
            }
            
            if ($settingsService->saveMultiple($data)) {
                $this->getUser()->setFlash('notice', 'Settings saved successfully');
            } else {
                $this->getUser()->setFlash('error', 'Failed to save settings');
            }
            
            $this->redirect(['module' => 'backup', 'action' => 'settings']);
        }
    }

        public function executeCreate(sfWebRequest $request)
    {
        // Suppress PHP errors from corrupting JSON output
        error_reporting(0);
        ini_set('display_errors', 0);
        
        if (!$request->isXmlHttpRequest() && !$request->isMethod('post')) {
            $this->forward404();
        }
        
        $preset = $request->getParameter('preset', 'ahg');
        
        $options = ['preset' => $preset];
        
        // If custom preset, get individual options
        if ($preset === 'custom') {
            $options['database'] = (bool)$request->getParameter('database', true);
            $options['digital_objects'] = (bool)$request->getParameter('digital_objects', false);
            $options['uploads'] = (bool)$request->getParameter('uploads', false);
            $options['atom_base'] = (bool)$request->getParameter('atom_base', false);
            $options['plugins'] = (bool)$request->getParameter('plugins', true);
            $options['framework'] = (bool)$request->getParameter('framework', true);
            $options['fuseki'] = (bool)$request->getParameter('fuseki', false);
        }
        
        try {
            $service = new \AtomExtensions\Services\BackupService();
            $result = $service->createBackup($options);
            return $this->renderJson($result);
        } catch (Exception $e) {
            return $this->renderJson(['error' => $e->getMessage()], 500);
        }
    }

    public function executeRestore(sfWebRequest $request)
    {
        $backupId = $request->getParameter('id');
        
        if (!$backupId) {
            $this->redirect(['module' => 'backup', 'action' => 'index']);
        }
        
        $backupService = new \AtomExtensions\Services\BackupService();
        $this->backup = $backupService->getBackupDetails($backupId);
        $this->backupId = $backupId;
        
        if (!$this->backup) {
            $this->forward404();
        }
    }

    public function executeDoRestore(sfWebRequest $request)
    {
        if (!$request->isXmlHttpRequest() && !$request->isMethod('post')) {
            $this->forward404();
        }
        
        $backupId = $request->getParameter('id');
        
        if (!$backupId) {
            return $this->renderJson(['error' => 'Backup ID required'], 400);
        }
        
        $options = [
            'restore_database' => (bool)$request->getParameter('restore_database', true),
            'restore_uploads' => (bool)$request->getParameter('restore_uploads', false),
            'restore_plugins' => (bool)$request->getParameter('restore_plugins', false),
            'restore_framework' => (bool)$request->getParameter('restore_framework', false),
        ];
        
        try {
            $service = new \AtomExtensions\Services\BackupService();
            $service->restoreBackup($backupId, $options);
            return $this->renderJson(['status' => 'success', 'message' => 'Restore completed']);
        } catch (Exception $e) {
            return $this->renderJson(['error' => $e->getMessage()], 500);
        }
    }

    public function executeDelete(sfWebRequest $request)
    {
        if (!$request->isXmlHttpRequest() && !$request->isMethod('post')) {
            $this->forward404();
        }
        
        $backupId = $request->getParameter('id');
        
        try {
            $service = new \AtomExtensions\Services\BackupService();
            if ($service->deleteBackup($backupId)) {
                return $this->renderJson(['status' => 'success']);
            }
            return $this->renderJson(['error' => 'Delete failed'], 500);
        } catch (Exception $e) {
            return $this->renderJson(['error' => $e->getMessage()], 500);
        }
    }

    public function executeDownload(sfWebRequest $request)
    {
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $backupPath = $settingsService->get('backup_path', '/var/backups/atom');
        
        $backupId = $request->getParameter('id');
        $component = $request->getParameter('component', 'all');
        
        $backupDir = $backupPath . '/' . $backupId;
        
        if (!is_dir($backupDir) || strpos($backupId, '..') !== false) {
            $this->forward404();
        }
        
        if ($component === 'all') {
            $tarFile = '/tmp/backup_' . $backupId . '.tar.gz';
            exec('tar -czf ' . escapeshellarg($tarFile) . ' -C ' . escapeshellarg($backupPath) . ' ' . escapeshellarg($backupId));
            $file = $tarFile;
            $filename = 'backup_' . $backupId . '.tar.gz';
        } else {
            $componentFiles = [
                'database' => glob($backupDir . '/database/*.sql.gz')[0] ?? null,
                'uploads' => $backupDir . '/uploads.tar.gz',
                'plugins' => $backupDir . '/plugins.tar.gz',
                'framework' => $backupDir . '/framework.tar.gz',
            ];
            
            $file = $componentFiles[$component] ?? null;
            $filename = basename($file ?? '');
        }
        
        if (!$file || !file_exists($file)) {
            $this->forward404();
        }
        
        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Content-Type', 'application/gzip');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->getResponse()->setHttpHeader('Content-Length', filesize($file));
        $this->getResponse()->sendHttpHeaders();
        
        readfile($file);
        
        if ($component === 'all' && strpos($file, '/tmp/') === 0) {
            unlink($file);
        }
        
        return sfView::NONE;
    }

    public function executeTestConnection(sfWebRequest $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->forward404();
        }
        
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $dbConfig = $settingsService->getDbConfigFromFile();
        
        $host = $dbConfig['db_host'] ?? 'localhost';
        $port = $dbConfig['db_port'] ?? 3306;
        $user = $dbConfig['db_user'] ?? 'root';
        $password = $dbConfig['db_password'] ?? '';
        $database = $dbConfig['db_name'] ?? 'archive';
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            
            return $this->renderJson([
                'status' => 'success', 
                'message' => 'Connection successful to ' . $database . '@' . $host
            ]);
        } catch (PDOException $e) {
            return $this->renderJson([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function renderJson(array $data, $status = 200)
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data));
        
        return sfView::NONE;
    }
}
