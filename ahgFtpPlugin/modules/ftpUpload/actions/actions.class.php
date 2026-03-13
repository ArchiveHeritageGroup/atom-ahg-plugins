<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\AclService;

class ftpUploadActions extends AhgController
{
    /** Chunk size must match JS: 10 MB */
    const CHUNK_SIZE = 10 * 1024 * 1024;

    /** Temp directory for chunk assembly */
    const CHUNK_DIR = '/tmp/ahg_ftp_chunks';

    /**
     * Lazy-load FtpService (Symfony 1.x doesn't autoload namespaced plugin classes).
     */
    protected function getFtpService(array $overrideConfig = null)
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/FtpService.php';

        if ($overrideConfig) {
            return new \AhgFtpPlugin\Services\FtpService($overrideConfig);
        }

        return \AhgFtpPlugin\Services\FtpService::fromSettings();
    }

    /**
     * Return JSON helper.
     */
    protected function json(array $data)
    {
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($data));
    }

    /**
     * Main page: upload zone + file listing.
     */
    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            AclService::forwardUnauthorized();
        }

        $svc = $this->getFtpService();
        $remotePath = $svc->getRemotePath();
        $listResult = $svc->listFiles();

        // Load settings for display
        $settings = [];
        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                ->where('setting_group', 'ftp')
                ->get(['setting_key', 'setting_value']);
            foreach ($rows as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        $this->remotePath = $remotePath;
        $this->diskPath = $settings['ftp_disk_path'] ?? $remotePath;
        $this->files = $listResult['success'] ? $listResult['files'] : [];
        $this->listError = $listResult['success'] ? null : ($listResult['message'] ?? 'Connection failed');
        $this->configured = !empty($settings['ftp_host']);
        $this->protocol = $settings['ftp_protocol'] ?? 'sftp';
        $this->chunkSize = self::CHUNK_SIZE;
    }

    /**
     * Handle chunked file upload.
     *
     * Each request sends one chunk with metadata:
     *   - file: the chunk blob
     *   - uploadId: unique ID for this upload session (generated client-side)
     *   - chunkIndex: 0-based chunk number
     *   - totalChunks: total number of chunks
     *   - fileName: original filename
     *   - fileSize: total file size in bytes
     *
     * When the last chunk arrives, the file is reassembled and uploaded via FTP/SFTP.
     */
    public function executeUploadChunk(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized']);
        }

        if (!$request->isMethod('post')) {
            return $this->json(['success' => false, 'message' => 'POST required']);
        }

        // Read chunk metadata
        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->getParameter('uploadId', ''));
        $chunkIndex = (int) $request->getParameter('chunkIndex', -1);
        $totalChunks = (int) $request->getParameter('totalChunks', 0);
        $fileName = $request->getParameter('fileName', '');
        $fileSize = (int) $request->getParameter('fileSize', 0);

        if (empty($uploadId) || $chunkIndex < 0 || $totalChunks < 1 || empty($fileName)) {
            return $this->json(['success' => false, 'message' => 'Missing chunk metadata']);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['file']['error'] ?? -1;

            return $this->json(['success' => false, 'message' => 'Chunk upload error (code ' . $code . ')']);
        }

        // Create chunk directory for this upload
        $uploadDir = self::CHUNK_DIR . '/' . $uploadId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Save chunk to disk
        $chunkPath = $uploadDir . '/chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $chunkPath)) {
            return $this->json(['success' => false, 'message' => 'Failed to save chunk']);
        }

        // Save metadata on first chunk
        $metaPath = $uploadDir . '/meta.json';
        if ($chunkIndex === 0) {
            file_put_contents($metaPath, json_encode([
                'fileName' => $fileName,
                'fileSize' => $fileSize,
                'totalChunks' => $totalChunks,
                'created' => time(),
            ]));
        }

        // Count received chunks
        $receivedChunks = count(glob($uploadDir . '/chunk_*'));

        // Not all chunks received yet — acknowledge this chunk
        if ($receivedChunks < $totalChunks) {
            return $this->json([
                'success' => true,
                'complete' => false,
                'received' => $receivedChunks,
                'total' => $totalChunks,
            ]);
        }

        // All chunks received — reassemble and upload
        return $this->assembleAndUpload($uploadId, $uploadDir, $fileName, $totalChunks);
    }

    /**
     * Reassemble chunks into a single file and upload via FTP/SFTP.
     */
    protected function assembleAndUpload(string $uploadId, string $uploadDir, string $fileName, int $totalChunks)
    {
        $assembledPath = self::CHUNK_DIR . '/' . $uploadId . '_assembled';

        try {
            // Reassemble chunks in order
            $out = fopen($assembledPath, 'wb');
            if (!$out) {
                $this->cleanupChunks($uploadDir, $assembledPath);

                return $this->json(['success' => false, 'message' => 'Failed to create assembled file']);
            }

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $uploadDir . '/chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);
                if (!file_exists($chunkPath)) {
                    fclose($out);
                    $this->cleanupChunks($uploadDir, $assembledPath);

                    return $this->json(['success' => false, 'message' => 'Missing chunk ' . $i]);
                }

                $in = fopen($chunkPath, 'rb');
                if (!$in) {
                    fclose($out);
                    $this->cleanupChunks($uploadDir, $assembledPath);

                    return $this->json(['success' => false, 'message' => 'Cannot read chunk ' . $i]);
                }

                // Stream copy — memory efficient
                stream_copy_to_stream($in, $out);
                fclose($in);
            }

            fclose($out);

            // Upload assembled file via FTP/SFTP
            $svc = $this->getFtpService();
            $result = $svc->upload($assembledPath, $fileName);

            // Cleanup
            $this->cleanupChunks($uploadDir, $assembledPath);

            return $this->json($result);
        } catch (\Exception $e) {
            $this->cleanupChunks($uploadDir, $assembledPath);

            return $this->json(['success' => false, 'message' => 'Assembly failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Clean up chunk directory and assembled file.
     */
    protected function cleanupChunks(string $uploadDir, string $assembledPath = ''): void
    {
        // Remove chunk files
        if (is_dir($uploadDir)) {
            $files = glob($uploadDir . '/*');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
            @rmdir($uploadDir);
        }

        // Remove assembled file
        if ($assembledPath && file_exists($assembledPath)) {
            @unlink($assembledPath);
        }
    }

    /**
     * Legacy single-file upload (kept for small files / backwards compat).
     */
    public function executeUpload(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized']);
        }

        if (!$request->isMethod('post')) {
            return $this->json(['success' => false, 'message' => 'POST required']);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['file']['error'] ?? -1;
            $msg = match ($code) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                default => 'Upload error (code ' . $code . ')',
            };

            return $this->json(['success' => false, 'message' => $msg]);
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $originalName = $_FILES['file']['name'];

        $svc = $this->getFtpService();
        $result = $svc->upload($tmpPath, $originalName);

        @unlink($tmpPath);

        return $this->json($result);
    }

    /**
     * AJAX: list remote files.
     */
    public function executeListFiles(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $svc = $this->getFtpService();

        return $this->json($svc->listFiles());
    }

    /**
     * AJAX: delete a remote file.
     */
    public function executeDeleteFile(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized']);
        }

        if (!$request->isMethod('post')) {
            return $this->json(['success' => false, 'message' => 'POST required']);
        }

        $data = json_decode($request->getContent(), true);
        $filename = $data['filename'] ?? $request->getParameter('filename', '');

        if (empty($filename)) {
            return $this->json(['success' => false, 'message' => 'No filename specified']);
        }

        $svc = $this->getFtpService();

        return $this->json($svc->deleteFile($filename));
    }
}
