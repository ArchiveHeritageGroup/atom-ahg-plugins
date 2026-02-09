<?php

$frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
if (file_exists($frameworkBootstrap)) {
    require_once $frameworkBootstrap;
}

class backupActions extends AhgActions
{
    private const MAX_UPLOAD_SIZE = 2147483648; // 2GB
    private const ALLOWED_EXTENSIONS = ['gz', 'tar.gz', 'sql', 'sql.gz', 'zip'];

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
        
        // Get pending uploads
        $this->pendingUploads = $this->getPendingUploads();
    }

    public function executeSettings(sfWebRequest $request)
    {
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $this->settings = $settingsService->getAllWithMeta();
        $this->settingsMap = $settingsService->all();

        // Get all available AHG plugins from database
        $this->availablePlugins = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
            ->where('name', 'like', 'ahg%')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

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

            // Process selected plugins from checkboxes (array) or hidden field (newline-separated string)
            $selectedPlugins = $request->getParameter('selected_plugins');
            if (is_array($selectedPlugins) && !empty($selectedPlugins)) {
                // Direct checkbox submission (array)
                $data['custom_plugins'] = json_encode(array_values($selectedPlugins));
            } else {
                // Fallback: hidden field populated by JavaScript (newline-separated string)
                $customPlugins = $request->getParameter('custom_plugins');
                if ($customPlugins) {
                    $plugins = array_filter(array_map('trim', explode("\n", $customPlugins)));
                    $data['custom_plugins'] = json_encode(array_values($plugins));
                } else {
                    // No plugins selected - save empty array
                    $data['custom_plugins'] = json_encode([]);
                }
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
        if (!$request->isXmlHttpRequest() && !$request->isMethod('post')) {
            $this->forward404();
        }

        $options = [
            'database' => (bool)$request->getParameter('database', true),
            'uploads' => (bool)$request->getParameter('uploads', true),
            'plugins' => (bool)$request->getParameter('plugins', true),
            'framework' => (bool)$request->getParameter('framework', true),
            'type' => 'manual',
        ];

        try {
            $service = new \AtomExtensions\Services\BackupService();
            $result = $service->createBackup($options);
            return $this->renderJson($result);
        } catch (Exception $e) {
            return $this->renderJson(['error' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // UPLOAD FUNCTIONALITY
    // ==========================================

    /**
     * Show upload form
     */
    public function executeUpload(sfWebRequest $request)
    {
        $this->maxUploadSize = $this->getMaxUploadSize();
        $this->pendingUploads = $this->getPendingUploads();
    }

    /**
     * Handle file upload (AJAX)
     */
    public function executeDoUpload(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            return $this->renderJson(['error' => 'POST required'], 400);
        }

        $uploadType = $request->getParameter('upload_type', 'full'); // full or db_only
        
        // Check for file
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
            ];
            $error = $errorMessages[$_FILES['backup_file']['error'] ?? 0] ?? 'Upload failed';
            return $this->renderJson(['error' => $error], 400);
        }

        $file = $_FILES['backup_file'];
        $filename = basename($file['name']);
        
        // Validate extension
        $ext = $this->getFileExtension($filename);
        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            return $this->renderJson(['error' => 'Invalid file type. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)], 400);
        }

        // Create staging directory
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $backupPath = $settingsService->get('backup_path', '/var/backups/atom');
        $stagingDir = $backupPath . '/staging';
        
        if (!is_dir($stagingDir)) {
            mkdir($stagingDir, 0755, true);
        }

        // Generate unique ID for this upload
        $uploadId = date('Y-m-d_H-i-s') . '_upload_' . substr(md5(uniqid()), 0, 8);
        $uploadDir = $stagingDir . '/' . $uploadId;
        mkdir($uploadDir, 0755, true);

        // Move uploaded file
        $targetFile = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $this->renderJson(['error' => 'Failed to save uploaded file'], 500);
        }

        // Validate and extract
        try {
            $validation = $this->validateUpload($targetFile, $uploadType, $uploadDir);
            
            // Save upload metadata
            $metadata = [
                'id' => $uploadId,
                'filename' => $filename,
                'type' => $uploadType,
                'size' => $file['size'],
                'uploaded_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'components' => $validation['components'],
                'source' => 'upload'
            ];
            
            file_put_contents($uploadDir . '/upload_manifest.json', json_encode($metadata, JSON_PRETTY_PRINT));

            return $this->renderJson([
                'status' => 'success',
                'upload_id' => $uploadId,
                'filename' => $filename,
                'size' => $this->formatSize($file['size']),
                'type' => $uploadType,
                'components' => $validation['components'],
                'message' => 'Upload successful. Ready to restore.'
            ]);

        } catch (Exception $e) {
            // Clean up on failure
            $this->deleteDirectory($uploadDir);
            return $this->renderJson(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Validate uploaded backup file
     */
    private function validateUpload(string $filePath, string $type, string $extractDir): array
    {
        $ext = $this->getFileExtension($filePath);
        $components = [];

        if ($type === 'db_only') {
            // DB only: expect .sql, .sql.gz, or .gz
            if ($ext === 'sql' || $ext === 'sql.gz' || $ext === 'gz') {
                // Test if it's valid SQL
                $testCmd = $ext === 'sql' 
                    ? "head -50 " . escapeshellarg($filePath) . " | grep -E '^(--|CREATE|INSERT|DROP|USE|SET)'"
                    : "zcat " . escapeshellarg($filePath) . " 2>/dev/null | head -50 | grep -E '^(--|CREATE|INSERT|DROP|USE|SET)'";
                
                exec($testCmd, $output, $returnCode);
                if ($returnCode !== 0 || empty($output)) {
                    throw new Exception('File does not appear to be a valid SQL dump');
                }
                
                $components['database'] = true;
                
                // Create database subdirectory for consistency
                $dbDir = $extractDir . '/database';
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
                // Move/copy SQL file to database directory
                $targetName = ($ext === 'sql') ? 'database.sql' : 'database.sql.gz';
                rename($filePath, $dbDir . '/' . $targetName);
            } else {
                throw new Exception('DB-only upload requires .sql or .sql.gz file');
            }
        } else {
            // Full backup: expect .tar.gz
            if ($ext !== 'tar.gz' && $ext !== 'gz') {
                throw new Exception('Full backup upload requires .tar.gz file');
            }

            // Extract archive
            $cmd = "tar -xzf " . escapeshellarg($filePath) . " -C " . escapeshellarg($extractDir) . " 2>&1";
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Failed to extract archive: ' . implode("\n", $output));
            }

            // Check for manifest or detect components
            $manifestFile = $extractDir . '/manifest.json';
            
            // Handle nested directory (tar might create a subdirectory)
            $subdirs = glob($extractDir . '/*', GLOB_ONLYDIR);
            foreach ($subdirs as $subdir) {
                if (basename($subdir) !== 'database' && file_exists($subdir . '/manifest.json')) {
                    // Move contents up
                    exec("mv " . escapeshellarg($subdir) . "/* " . escapeshellarg($extractDir) . "/");
                    rmdir($subdir);
                    break;
                }
            }

            // Detect components
            if (is_dir($extractDir . '/database') || glob($extractDir . '/*.sql*')) {
                $components['database'] = true;
            }
            if (file_exists($extractDir . '/uploads.tar.gz') || is_dir($extractDir . '/uploads')) {
                $components['uploads'] = true;
            }
            if (file_exists($extractDir . '/plugins.tar.gz') || is_dir($extractDir . '/plugins')) {
                $components['plugins'] = true;
            }
            if (file_exists($extractDir . '/framework.tar.gz') || is_dir($extractDir . '/framework')) {
                $components['framework'] = true;
            }

            if (empty($components)) {
                throw new Exception('No valid backup components found in archive');
            }

            // Remove original tar file to save space
            unlink($filePath);
        }

        return ['components' => $components];
    }

    /**
     * List pending uploads
     */
    private function getPendingUploads(): array
    {
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $backupPath = $settingsService->get('backup_path', '/var/backups/atom');
        $stagingDir = $backupPath . '/staging';

        $uploads = [];
        if (!is_dir($stagingDir)) {
            return $uploads;
        }

        foreach (glob($stagingDir . '/*', GLOB_ONLYDIR) as $dir) {
            $manifestFile = $dir . '/upload_manifest.json';
            if (file_exists($manifestFile)) {
                $manifest = json_decode(file_get_contents($manifestFile), true);
                if ($manifest) {
                    $manifest['path'] = $dir;
                    $uploads[] = $manifest;
                }
            }
        }

        // Sort by upload date descending
        usort($uploads, fn($a, $b) => strcmp($b['uploaded_at'] ?? '', $a['uploaded_at'] ?? ''));

        return $uploads;
    }

    /**
     * Restore from uploaded backup
     */
    public function executeRestoreUpload(sfWebRequest $request)
    {
        $uploadId = $request->getParameter('id');
        
        if (!$uploadId) {
            $this->redirect(['module' => 'backup', 'action' => 'upload']);
        }

        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $backupPath = $settingsService->get('backup_path', '/var/backups/atom');
        $uploadDir = $backupPath . '/staging/' . $uploadId;

        if (!is_dir($uploadDir) || strpos($uploadId, '..') !== false) {
            $this->getUser()->setFlash('error', 'Upload not found');
            $this->redirect(['module' => 'backup', 'action' => 'upload']);
        }

        $manifestFile = $uploadDir . '/upload_manifest.json';
        if (!file_exists($manifestFile)) {
            $this->getUser()->setFlash('error', 'Invalid upload - no manifest');
            $this->redirect(['module' => 'backup', 'action' => 'upload']);
        }

        $this->upload = json_decode(file_get_contents($manifestFile), true);
        $this->uploadId = $uploadId;
    }

    /**
     * Execute restore from upload (AJAX)
     */
    public function executeDoRestoreUpload(sfWebRequest $request)
    {
        if (!$request->isXmlHttpRequest() && !$request->isMethod('post')) {
            $this->forward404();
        }

        $uploadId = $request->getParameter('id');
        if (!$uploadId || strpos($uploadId, '..') !== false) {
            return $this->renderJson(['error' => 'Invalid upload ID'], 400);
        }

        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $backupPath = $settingsService->get('backup_path', '/var/backups/atom');
        $uploadDir = $backupPath . '/staging/' . $uploadId;

        if (!is_dir($uploadDir)) {
            return $this->renderJson(['error' => 'Upload not found'], 404);
        }

        $options = [
            'restore_database' => (bool)$request->getParameter('restore_database', true),
            'restore_uploads' => (bool)$request->getParameter('restore_uploads', false),
            'restore_plugins' => (bool)$request->getParameter('restore_plugins', false),
            'restore_framework' => (bool)$request->getParameter('restore_framework', false),
        ];

        try {
            $service = new \AtomExtensions\Services\BackupService();
            
            // Restore from staging directory instead of regular backup path
            $result = $service->restoreFromPath($uploadDir, $options);

            // Update manifest status
            $manifestFile = $uploadDir . '/upload_manifest.json';
            if (file_exists($manifestFile)) {
                $manifest = json_decode(file_get_contents($manifestFile), true);
                $manifest['status'] = 'restored';
                $manifest['restored_at'] = date('Y-m-d H:i:s');
                $manifest['restore_options'] = $options;
                file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
            }

            return $this->renderJson([
                'status' => 'success',
                'message' => 'Restore completed successfully',
                'details' => $result
            ]);

        } catch (Exception $e) {
            return $this->renderJson(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete pending upload
     */
    public function executeDeleteUpload(sfWebRequest $request)
    {
        if (!$request->isXmlHttpRequest() && !$request->isMethod('post')) {
            $this->forward404();
        }

        $uploadId = $request->getParameter('id');
        if (!$uploadId || strpos($uploadId, '..') !== false) {
            return $this->renderJson(['error' => 'Invalid upload ID'], 400);
        }

        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $backupPath = $settingsService->get('backup_path', '/var/backups/atom');
        $uploadDir = $backupPath . '/staging/' . $uploadId;

        if (!is_dir($uploadDir)) {
            return $this->renderJson(['error' => 'Upload not found'], 404);
        }

        try {
            $this->deleteDirectory($uploadDir);
            return $this->renderJson(['status' => 'success', 'message' => 'Upload deleted']);
        } catch (Exception $e) {
            return $this->renderJson(['error' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // EXISTING RESTORE FUNCTIONALITY
    // ==========================================

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

        try {
            $dsn = sprintf("mysql:host=%s;port=%d;dbname=%s",
                $dbConfig['db_host'] ?? 'localhost',
                $dbConfig['db_port'] ?? 3306,
                $dbConfig['db_name'] ?? 'archive'
            );
            $pdo = new PDO($dsn, $dbConfig['db_user'] ?? 'root', $dbConfig['db_password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return $this->renderJson([
                'status' => 'success',
                'message' => 'Connection successful to ' . ($dbConfig['db_name'] ?? 'archive')
            ]);
        } catch (PDOException $e) {
            return $this->renderJson(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function getFileExtension(string $filename): string
    {
        if (preg_match('/\.(tar\.gz|sql\.gz)$/i', $filename, $matches)) {
            return strtolower($matches[1]);
        }
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    private function getMaxUploadSize(): int
    {
        $maxUpload = $this->parseSize(ini_get('upload_max_filesize'));
        $maxPost = $this->parseSize(ini_get('post_max_size'));
        return min($maxUpload, $maxPost, self::MAX_UPLOAD_SIZE);
    }

    private function parseSize(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int)$size;
        
        return match($unit) {
            'G' => $value * 1073741824,
            'M' => $value * 1048576,
            'K' => $value * 1024,
            default => $value
        };
    }

    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }

    protected function renderJson(array $data, $status = 200)
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data));
        return sfView::NONE;
    }
}
