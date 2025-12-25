<?php

/**
 * TIFF to PDF Merge Module Actions
 * Uses Laravel Query Builder and framework polling worker
 */

class tiffpdfmergeActions extends sfActions
{
    protected $repository = null;
    protected $frameworkLoaded = false;

    protected function initFramework()
    {
        if ($this->frameworkLoaded) {
            return;
        }
        
        // Capture any output from bootstrap
        ob_start();
        
        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            require_once '/usr/share/nginx/archive/atom-framework/bootstrap.php';
        }
        
        if (!class_exists('AtomFramework\Repositories\TiffPdfMergeRepository')) {
            require_once '/usr/share/nginx/archive/atom-framework/src/Repositories/TiffPdfMergeRepository.php';
        }
        
        // Discard any output
        ob_end_clean();
        
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

    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
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

        $userId = $this->context->user->getAttribute('user_id');
        $this->pendingJobs = $this->getRepository()->getPendingJobs($userId);
        $this->stats = $this->getRepository()->getStatistics($userId);
    }

    public function executeCreate(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required']);
        }

        $userId = $this->context->user->getAttribute('user_id');
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

    public function executeUpload(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->context->user->isAuthenticated()) {
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
                ]);

                $results[] = [
                    'success' => true,
                    'file_id' => $fileId,
                    'filename' => $originalName,
                    'width' => $imageInfo[0] ?? null,
                    'height' => $imageInfo[1] ?? null,
                    'size' => filesize($filePath),
                ];
            } catch (Exception $e) {
                $results[] = ['success' => false, 'filename' => $originalName, 'error' => $e->getMessage()];
            }
        }

        $successful = count(array_filter($results, function($r) { return $r['success']; }));
        return $this->renderJson(['success' => $successful > 0, 'uploaded' => $successful, 'results' => $results]);
    }

    public function executeReorder(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        $fileOrder = $request->getParameter('file_order', []);

        if (!$mergeJobId || empty($fileOrder)) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid parameters']);
        }

        $this->getRepository()->updateFileOrder($mergeJobId, $fileOrder);
        return $this->renderJson(['success' => true]);
    }

    public function executeRemoveFile(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->context->user->isAuthenticated()) {
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

    public function executeGetJob(sfWebRequest $request)
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

    public function executeProcess(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required']);
        }

        $mergeJobId = (int) $request->getParameter('job_id');
        $job = $this->getRepository()->getJob($mergeJobId);

        if (!$job) {
            return $this->renderJson(['success' => false, 'error' => 'Job not found']);
        }

        if ($job->status !== 'pending') {
            return $this->renderJson(['success' => false, 'error' => 'Job already processed or processing']);
        }

        $files = $this->getRepository()->getJobFiles($mergeJobId);
        if ($files->isEmpty()) {
            return $this->renderJson(['success' => false, 'error' => 'No files to process']);
        }

        // Mark job as queued for the worker to pick up
        $db = $this->getDB();
        $db::table('tiff_pdf_merge_job')
            ->where('id', $mergeJobId)
            ->update([
                'status' => 'queued',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->renderJson([
            'success' => true,
            'message' => 'Job queued for processing. Refresh this page to check status.',
        ]);
    }

    public function executeDownload(sfWebRequest $request)
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

    public function executeDelete(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
        
        if (!$this->context->user->isAuthenticated()) {
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

    public function executeBrowse(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
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

    public function executeView(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
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

    protected function renderJson($data)
    {
        // Clean any existing output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');
        
        return $this->renderText(json_encode($data));
    }
}
