<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * TIFF to PDF Merge Module Actions
 * Uses Laravel Query Builder and framework polling worker
 */

class tiffpdfmergeActions extends AhgController
{
    protected $repository = null;
    protected $frameworkLoaded = false;

    protected function initFramework()
    {
        if ($this->frameworkLoaded) {
            return;
        }

        if (!class_exists('AtomFramework\Repositories\TiffPdfMergeRepository')) {
            require_once $this->config('sf_plugins_dir') . '/ahgPreservationPlugin/lib/Repositories/TiffPdfMergeRepository.php';
        }

        $this->frameworkLoaded = true;
    }

    protected function getRepository()
    {
        if ($this->repository === null) {
            $this->initFramework();
            $this->repository = new \AtomFramework\Repositories\TiffPdfMergeRepository();
        }
        return $this->repository;
    }

    protected function getDB()
    {
        $this->initFramework();
        return \Illuminate\Database\Capsule\Manager::class;
    }

    public function executeIndex($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->informationObjectSlug = $request->getParameter('informationObject');
        $this->informationObject = null;
        $this->informationObjectId = null;

        if ($this->informationObjectSlug) {
            $this->informationObject = QubitInformationObject::getBySlug($this->informationObjectSlug);
            if ($this->informationObject) {
                $this->informationObjectId = $this->informationObject->id;
            }
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->pendingJobs = $this->getRepository()->getPendingJobs($userId);
        $this->stats = $this->getRepository()->getStatistics($userId);
    }

    public function executeCreate($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required']);
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $jobName = $request->getParameter('job_name', 'Merged PDF ' . date('Y-m-d H:i:s'));
        $informationObjectId = $request->getParameter('information_object_id');

        if ($informationObjectId && !is_numeric($informationObjectId)) {
            $io = QubitInformationObject::getBySlug($informationObjectId);
            $informationObjectId = $io ? $io->id : null;
        }

        try {
            $mergeJobId = $this->getRepository()->createJob([
                'user_id' => $userId,
                'job_name' => $jobName,
                'information_object_id' => $informationObjectId ?: null,
                'pdf_standard' => $request->getParameter('pdf_standard', 'pdfa-2b'),
                'compression_quality' => (int) $request->getParameter('compression_quality', 85),
                'dpi' => (int) $request->getParameter('dpi', 300),
                'attach_to_record' => (int) $request->getParameter('attach_to_record', 1),
            ]);

            return $this->renderJson(['success' => true, 'job_id' => $mergeJobId]);
        } catch (Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeUpload($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        if (!$mergeJobId) {
            return $this->renderJson(['success' => false, 'error' => 'Job ID required']);
        }

        $job = $this->getRepository()->getJob($mergeJobId);
        if (!$job || !in_array($job->status, ['pending', 'queued'])) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid or completed job']);
        }

        $uploadedFiles = $request->getFiles('files');
        if (empty($uploadedFiles) || !isset($uploadedFiles['name']) || !is_array($uploadedFiles['name'])) {
            $singleFile = $request->getFiles('file');
            if ($singleFile && $singleFile['error'] === UPLOAD_ERR_OK) {
                $uploadedFiles = [
                    'name' => [$singleFile['name']],
                    'type' => [$singleFile['type']],
                    'tmp_name' => [$singleFile['tmp_name']],
                    'error' => [$singleFile['error']],
                    'size' => [$singleFile['size']],
                ];
            } else {
                return $this->renderJson(['success' => false, 'error' => 'No files uploaded']);
            }
        }

        $results = [];
        $tempDir = '/tmp/tiff-pdf-merge';
        $jobDir = $tempDir . '/job_' . $mergeJobId;

        if (!is_dir($jobDir)) {
            mkdir($jobDir, 0755, true);
        }

        $maxOrder = $this->getRepository()->getMaxPageOrder($mergeJobId);
        $allowedExtensions = ['tif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'gif'];
        $fileCount = count($uploadedFiles['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $uploadedFiles['name'][$i];
            $tmpName = $uploadedFiles['tmp_name'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                $results[] = ['success' => false, 'filename' => $originalName, 'error' => 'Invalid file type'];
                continue;
            }

            $storedFilename = uniqid('tiff_') . '.' . $extension;
            $filePath = $jobDir . '/' . $storedFilename;

            if (!move_uploaded_file($tmpName, $filePath)) {
                $results[] = ['success' => false, 'filename' => $originalName, 'error' => 'Failed to save file'];
                continue;
            }

            $imageInfo = @getimagesize($filePath);
            $maxOrder++;

            // Detect page count for multipage TIFFs
            $pageCount = 1;
            if (in_array($extension, ['tif', 'tiff'])) {
                $identifyCmd = sprintf('/usr/bin/identify -format "%%n\n" %s 2>/dev/null | head -1',
                    escapeshellarg($filePath));
                $pageCount = max(1, (int) trim(shell_exec($identifyCmd)));
            }

            try {
                $fileId = $this->getRepository()->addFile($mergeJobId, [
                    'original_filename' => $originalName,
                    'stored_filename' => $storedFilename,
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'mime_type' => $imageInfo['mime'] ?? 'image/tiff',
                    'width' => $imageInfo[0] ?? null,
                    'height' => $imageInfo[1] ?? null,
                    'page_order' => $maxOrder,
                    'checksum_md5' => md5_file($filePath),
                    'metadata' => ['page_count' => $pageCount],
                ]);

                $results[] = [
                    'success' => true,
                    'file_id' => $fileId,
                    'filename' => $originalName,
                    'width' => $imageInfo[0] ?? null,
                    'height' => $imageInfo[1] ?? null,
                    'size' => filesize($filePath),
                    'pages' => $pageCount,
                ];
            } catch (Exception $e) {
                $results[] = ['success' => false, 'filename' => $originalName, 'error' => $e->getMessage()];
            }
        }

        $successful = count(array_filter($results, function($r) { return $r['success']; }));
        return $this->renderJson(['success' => $successful > 0, 'uploaded' => $successful, 'results' => $results]);
    }

    public function executeReorder($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        $fileOrder = $request->getParameter('file_order', []);

        // Symfony may return a comma-separated string instead of an array
        if (is_string($fileOrder)) {
            $fileOrder = array_filter(array_map('intval', explode(',', $fileOrder)));
        }

        if (!$mergeJobId || empty($fileOrder)) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid parameters']);
        }

        $this->getRepository()->updateFileOrder($mergeJobId, $fileOrder);
        return $this->renderJson(['success' => true]);
    }

    public function executeRemoveFile($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $fileId = (int) $request->getParameter('file_id');
        $db = $this->getDB();
        $file = $db::table('tiff_pdf_merge_file')->where('id', $fileId)->first();

        if (!$file) {
            return $this->renderJson(['success' => false, 'error' => 'File not found']);
        }

        if (file_exists($file->file_path)) {
            @unlink($file->file_path);
        }

        $this->getRepository()->deleteFile($fileId);
        return $this->renderJson(['success' => true]);
    }

    public function executeGetJob($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        $mergeJobId = (int) $request->getParameter('job_id');
        $job = $this->getRepository()->getJob($mergeJobId);

        if (!$job) {
            return $this->renderJson(['success' => false, 'error' => 'Job not found']);
        }

        $files = $this->getRepository()->getJobFiles($mergeJobId);
        return $this->renderJson(['success' => true, 'job' => $job, 'files' => $files->toArray()]);
    }

    public function executeProcess($request)
    {
        sfConfig::set('sf_web_debug', false);

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        $job = $this->getRepository()->getJob($mergeJobId);

        if (!$job) {
            return $this->renderJson(['success' => false, 'error' => 'Job not found']);
        }

        if ($job->status === 'processing') {
            return $this->renderJson(['success' => false, 'error' => 'Job is already being processed']);
        }

        if ($job->status === 'completed') {
            return $this->renderJson(['success' => true, 'message' => 'Job already completed', 'output_path' => $job->output_path]);
        }

        $files = $this->getRepository()->getJobFiles($mergeJobId);
        if ($files->isEmpty()) {
            return $this->renderJson(['success' => false, 'error' => 'No files to process']);
        }

        // Queue for the background worker (ahg:tiff-pdf-process). Large volumes
        // (hundreds of 40 MB+ TIFFs) must never run in the web request - they
        // exceed the request time and memory limits. The worker runs the
        // memory-safe batched merge and notifies the user on completion.
        $db = $this->getDB();
        $db::table('tiff_pdf_merge_job')
            ->where('id', $mergeJobId)
            ->update([
                'status' => 'queued',
                'error_message' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->renderJson([
            'success' => true,
            'queued' => true,
            'status' => 'queued',
            'message' => 'Queued. The PDF/A is being created in the background; you will be notified when it is ready.',
        ]);
    }

    /**
     * Recreate / retry a job. Re-queues a completed or failed job so the
     * operator can regenerate the PDF/A after an upload finishes or after a
     * convert failure. Clears prior output + resets file statuses.
     */
    public function executeRecreate($request)
    {
        sfConfig::set('sf_web_debug', false);

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        $job = $this->getRepository()->getJob($mergeJobId);

        if (!$job) {
            return $this->renderJson(['success' => false, 'error' => 'Job not found']);
        }
        if ($job->status === 'processing') {
            return $this->renderJson(['success' => false, 'error' => 'Job is currently processing']);
        }

        $files = $this->getRepository()->getJobFiles($mergeJobId);
        if ($files->isEmpty()) {
            return $this->renderJson(['success' => false, 'error' => 'No files to process']);
        }

        $db = $this->getDB();
        $db::table('tiff_pdf_merge_job')
            ->where('id', $mergeJobId)
            ->update([
                'status' => 'queued',
                'error_message' => null,
                'output_path' => null,
                'output_filename' => null,
                'completed_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        $db::table('tiff_pdf_merge_file')
            ->where('merge_job_id', $mergeJobId)
            ->update(['status' => 'uploaded']);

        return $this->renderJson([
            'success' => true,
            'queued' => true,
            'status' => 'queued',
            'message' => 'Re-queued. The PDF/A will be recreated in the background.',
        ]);
    }

    /**
     * Import a server-side folder of images (already placed via FTP) as a
     * combine job, referencing the files IN PLACE (no browser upload, no copy),
     * and queue it for the background worker. The folder is restricted to an
     * allowed base to prevent arbitrary filesystem access.
     */
    public function executeImportFolder($request)
    {
        sfConfig::set('sf_web_debug', false);

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }
        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required']);
        }

        $folder = (string) $request->getParameter('folder');
        $real = $folder !== '' ? realpath($folder) : false;
        if (!$real || !is_dir($real)) {
            return $this->renderJson(['success' => false, 'error' => 'Folder not found']);
        }

        $base = realpath($this->getImportBase());
        if (!$base || strpos($real, $base) !== 0) {
            return $this->renderJson(['success' => false, 'error' => 'Folder is outside the allowed import area']);
        }

        $images = $this->listImageFiles($real);
        if (empty($images)) {
            return $this->renderJson(['success' => false, 'error' => 'No TIFF/image files found in folder']);
        }

        $informationObjectId = $request->getParameter('information_object_id');
        if ($informationObjectId && !is_numeric($informationObjectId)) {
            $io = QubitInformationObject::getBySlug($informationObjectId);
            $informationObjectId = $io ? $io->id : null;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $jobName = $request->getParameter('job_name', basename($real));

        try {
            $repo = $this->getRepository();
            $jobId = $repo->createJob([
                'user_id' => $userId,
                'job_name' => $jobName,
                'information_object_id' => $informationObjectId ?: null,
                'pdf_standard' => 'pdfa-2b',
                'attach_to_record' => (int) $request->getParameter('attach_to_record', 1),
                'status' => 'queued',
            ]);

            $order = 0;
            foreach ($images as $path) {
                ++$order;
                $repo->addFile($jobId, [
                    'file_path' => $path,
                    'original_filename' => basename($path),
                    'stored_filename' => basename($path),   // referenced in place; no separate stored copy
                    'mime_type' => $this->guessImageMime($path),
                    'page_order' => $order,
                    'file_size' => @filesize($path) ?: 0,
                ]);
            }

            return $this->renderJson([
                'success' => true,
                'job_id' => $jobId,
                'queued' => true,
                'files' => count($images),
                'message' => count($images) . ' file(s) queued. The PDF/A is being created in the background; you will be notified when it is ready.',
            ]);
        } catch (Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * List completed combine jobs whose PDF is not yet linked to a record, so
     * the user can pick one by name and attach it ("Link to digital object").
     */
    public function executeReadyToLink($request)
    {
        sfConfig::set('sf_web_debug', false);

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $rows = $db::table('tiff_pdf_merge_job')
            ->where('status', 'completed')
            ->whereNull('output_digital_object_id')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'job_name', 'output_filename', 'output_path', 'processed_files', 'completed_at']);

        $list = [];
        foreach ($rows as $r) {
            if (empty($r->output_path) || !is_file($r->output_path)) {
                continue;
            }
            $list[] = [
                'job_id' => (int) $r->id,
                'name' => $r->output_filename ?: $r->job_name,
                'pages' => (int) $r->processed_files,
                'size_mb' => round((@filesize($r->output_path) ?: 0) / 1048576, 2),
                'completed_at' => $r->completed_at,
            ];
        }

        return $this->renderJson(['success' => true, 'items' => $list]);
    }

    /**
     * Attach an already-combined job PDF to a record chosen by slug - the
     * after-the-fact "link by name" path for combines made without a slug.
     */
    public function executeAttachExisting($request)
    {
        sfConfig::set('sf_web_debug', false);

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }
        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required']);
        }

        $jobId = (int) $request->getParameter('job_id');
        $slug = trim((string) $request->getParameter('slug', ''));
        if (!$jobId || $slug === '') {
            return $this->renderJson(['success' => false, 'error' => 'Missing job or record slug']);
        }

        $ioId = (int) \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('slug', $slug)
            ->value('object_id');
        if (!$ioId) {
            return $this->renderJson(['success' => false, 'error' => 'Record not found for slug: ' . $slug]);
        }

        if (!class_exists('AtomFramework\Jobs\TiffPdfMergeJob')) {
            require_once $this->config('sf_plugins_dir') . '/ahgPreservationPlugin/lib/Jobs/TiffPdfMergeJob.php';
        }
        $job = new \AtomFramework\Jobs\TiffPdfMergeJob($jobId);
        $result = $job->attachExisting($ioId);

        return $this->renderJson($result);
    }

    /** Allowed base directory for folder imports (configurable; defaults to the web dir). */
    protected function getImportBase()
    {
        $cfg = sfConfig::get('app_tiff_combine_import_base');

        return $cfg ?: sfConfig::get('sf_web_dir');
    }

    /** Best-effort image mime from extension (for the file row). */
    protected function guessImageMime($path)
    {
        $map = ['tif' => 'image/tiff', 'tiff' => 'image/tiff', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'bmp' => 'image/bmp', 'gif' => 'image/gif', 'webp' => 'image/webp'];

        return $map[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    /** List image files in a folder, natural-sorted so page order follows filenames. */
    protected function listImageFiles($dir)
    {
        $out = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ('.' === $f || '..' === $f) {
                continue;
            }
            $p = $dir . '/' . $f;
            if (!is_file($p)) {
                continue;
            }
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['tif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'], true)) {
                $out[] = $p;
            }
        }
        natcasesort($out);

        return array_values($out);
    }

    public function executeDownload($request)
    {
        $mergeJobId = (int) $request->getParameter('job_id');
        $job = $this->getRepository()->getJob($mergeJobId);

        if (!$job || $job->status !== 'completed' || !$job->output_path) {
            $this->forward404('PDF not found');
        }

        if (!file_exists($job->output_path)) {
            $this->forward404('PDF file not found');
        }

        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Content-Type', 'application/pdf');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $job->output_filename . '"');
        $this->getResponse()->setHttpHeader('Content-Length', filesize($job->output_path));
        $this->getResponse()->sendHttpHeaders();

        readfile($job->output_path);
        
        return sfView::NONE;
    }

    public function executeDelete($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        $files = $this->getRepository()->getJobFiles($mergeJobId);

        foreach ($files as $file) {
            if (file_exists($file->file_path)) {
                @unlink($file->file_path);
            }
        }

        $job = $this->getRepository()->getJob($mergeJobId);
        if ($job && $job->output_path && file_exists($job->output_path)) {
            @unlink($job->output_path);
        }

        $this->getRepository()->deleteJob($mergeJobId);

        $tempDir = '/tmp/tiff-pdf-merge';
        @rmdir($tempDir . '/job_' . $mergeJobId);

        return $this->renderJson(['success' => true]);
    }

    public function executeBrowse($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $db = $this->getDB();

        $this->filterStatus = $request->getParameter('status', '');
        $this->currentPage = max(1, (int) $request->getParameter('page', 1));
        $perPage = 20;

        $query = $db::table('tiff_pdf_merge_job as j')
            ->leftJoin('user as u', 'j.user_id', '=', 'u.id')
            ->select([
                'j.*',
                'u.username',
                $db::raw('(SELECT COUNT(*) FROM tiff_pdf_merge_file WHERE merge_job_id = j.id) as total_files'),
            ]);

        if ($this->filterStatus) {
            $query->where('j.status', $this->filterStatus);
        }

        $this->totalJobs = (clone $query)->count();
        $this->totalPages = (int) ceil($this->totalJobs / $perPage);

        $this->jobs = $query
            ->orderByDesc('j.created_at')
            ->offset(($this->currentPage - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $this->stats = $this->getRepository()->getStatistics();
        $this->stats['queued'] = $db::table('tiff_pdf_merge_job')->where('status', 'queued')->count();

        $this->hasProcessingJobs = $db::table('tiff_pdf_merge_job')
            ->whereIn('status', ['queued', 'processing'])
            ->exists();
    }

    public function executeView($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $db = $this->getDB();

        $jobId = (int) $request->getParameter('job_id');
        $this->job = $db::table('tiff_pdf_merge_job as j')
            ->leftJoin('user as u', 'j.user_id', '=', 'u.id')
            ->select([
                'j.*',
                'u.username',
                $db::raw('(SELECT COUNT(*) FROM tiff_pdf_merge_file WHERE merge_job_id = j.id) as total_files'),
            ])
            ->where('j.id', $jobId)
            ->first();

        if (!$this->job) {
            $this->forward404('Job not found');
        }

        $this->files = $this->getRepository()->getJobFiles($jobId);

        // Get linked record info if applicable
        $this->linkedRecord = null;
        $this->linkedRecordTitle = null;

        if ($this->job->information_object_id) {
            $io = QubitInformationObject::getById($this->job->information_object_id);
            if ($io) {
                $this->linkedRecord = $io;
                $this->linkedRecordTitle = $io->title ?? $io->slug;
            }
        }
    }
}
