<?php

use AtomFramework\Http\Controllers\AhgController;

class jobsManageActions extends AhgController
{
    /**
     * Browse jobs with filters, pagination, and stats.
     */
    public function executeBrowse($request)
    {
        // Require authenticated user
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $user = $this->getUser();
        $userId = $this->userId();
        $isAdmin = $user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID);

        $this->response->setTitle(__('Jobs') . ' - ' . $this->response->getTitle());

        $service = new \AhgJobsManage\Services\JobsService();

        // Filters
        $status = $request->getParameter('status', 'all');
        $sort = $request->getParameter('sort', 'date');
        $sortDir = $request->getParameter('sortDir', 'desc');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = max(1, min(100, (int) ($request->getParameter('limit') ?: $this->config('app_hits_per_page', 25))));

        // Get stats
        $this->stats = $service->getStats($userId, $isAdmin);

        // Get jobs
        $result = $service->browse([
            'status' => $status,
            'sort' => $sort,
            'sortDir' => $sortDir,
        ], $userId, $isAdmin, $limit, $page);

        $this->jobs = $result['items'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->pages = $result['pages'];
        $this->limit = $limit;
        $this->currentStatus = $status;
        $this->currentSort = $sort;
        $this->currentSortDir = $sortDir;
        $this->isAdmin = $isAdmin;

        // Flash message from delete
        if ($request->hasParameter('deleted')) {
            $count = (int) $request->getParameter('deleted');
            $this->getUser()->setFlash('notice', __('%1% job(s) deleted.', ['%1%' => $count]));
        }
    }

    /**
     * Show detailed report for a single job.
     */
    public function executeReport($request)
    {
        // Require authenticated user
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $user = $this->getUser();
        $userId = $this->userId();
        $isAdmin = $user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID);

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404();
        }

        $service = new \AhgJobsManage\Services\JobsService();
        $this->job = $service->getById($id);

        if (!$this->job) {
            $this->forward404();
        }

        // Non-admins can only see their own jobs
        if (!$isAdmin && $this->job->user_id != $userId) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        // Get notes
        $this->notes = $service->getNotes($id);

        // Separate error notes (type_id = 197) from info notes
        $this->errorNotes = [];
        $this->infoNotes = [];
        foreach ($this->notes as $note) {
            if ($note->type_id == 197) {
                $this->errorNotes[] = $note;
            } else {
                $this->infoNotes[] = $note;
            }
        }

        $this->isAdmin = $isAdmin;

        $this->response->setTitle(__('Job report #%1%', ['%1%' => $id]) . ' - ' . $this->response->getTitle());
    }

    /**
     * Delete jobs — single or all inactive.
     */
    public function executeDelete($request)
    {
        // Require authenticated user
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $user = $this->getUser();
        $userId = $this->userId();
        $isAdmin = $user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID);

        $service = new \AhgJobsManage\Services\JobsService();

        if ($request->isMethod('post')) {
            $id = (int) $request->getParameter('id', 0);

            if ($id > 0) {
                // Delete single job
                $deleted = $service->deleteSingle($id, $userId, $isAdmin);
                $count = $deleted ? 1 : 0;
            } else {
                // Delete all inactive (completed + error)
                $count = $service->deleteInactive($userId, $isAdmin);
            }

            $this->redirect('@jobs_browse?deleted=' . $count);
        }

        // GET — show confirmation page
        $this->id = (int) $request->getParameter('id', 0);
        $this->isAdmin = $isAdmin;

        if ($this->id > 0) {
            $this->job = $service->getById($this->id);
            if (!$this->job) {
                $this->forward404();
            }
            // Non-admins can only delete their own jobs
            if (!$isAdmin && $this->job->user_id != $userId) {
                \AtomExtensions\Services\AclService::forwardUnauthorized();
            }
        }

        $this->stats = $service->getStats($userId, $isAdmin);
    }

    // =========================================================================
    // Queue Management Actions
    // =========================================================================

    /**
     * Browse queue jobs with filters and pagination.
     */
    public function executeQueueBrowse($request)
    {
        $this->requireAdmin();

        $this->response->setTitle(__('Queue') . ' - ' . $this->response->getTitle());

        $service = $this->getQueueService();

        // Filters
        $queue = $request->getParameter('queue', '');
        $status = $request->getParameter('status', '');
        $jobType = $request->getParameter('job_type', '');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = max(1, min(100, (int) ($request->getParameter('limit') ?: $this->config('app_hits_per_page', 25))));

        $filters = [];
        if ($queue) {
            $filters['queue'] = $queue;
        }
        if ($status) {
            $filters['status'] = $status;
        }
        if ($jobType) {
            $filters['job_type'] = $jobType;
        }

        $result = $service->browseQueueJobs($filters, $limit, $page);

        $this->queueJobs = $result['items'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->pages = $result['pages'];
        $this->limit = $limit;
        $this->currentQueue = $queue;
        $this->currentStatus = $status;
        $this->currentJobType = $jobType;

        // Stats
        $this->stats = $service->getQueueStats();

        // Available queues for filter
        $this->queueNames = \AtomFramework\Services\QueueService::queueNames();
    }

    /**
     * Queue job detail with log timeline.
     */
    public function executeQueueDetail($request)
    {
        $this->requireAdmin();

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404();
        }

        $service = $this->getQueueService();
        $this->queueJob = $service->getQueueJob($id);

        if (!$this->queueJob) {
            $this->forward404();
        }

        // Get log events
        $queueService = new \AtomFramework\Services\QueueService();
        $this->logEvents = $queueService->getLogEvents($id, null, 100);

        $this->response->setTitle(__('Queue Job #%1%', ['%1%' => $id]) . ' - ' . $this->response->getTitle());
    }

    /**
     * Browse batches with progress.
     */
    public function executeQueueBatches($request)
    {
        $this->requireAdmin();

        $this->response->setTitle(__('Queue Batches') . ' - ' . $this->response->getTitle());

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = max(1, min(100, (int) ($request->getParameter('limit') ?: 25)));

        $service = $this->getQueueService();
        $result = $service->browseQueueBatches($limit, $page);

        $this->batches = $result['items'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->pages = $result['pages'];
        $this->limit = $limit;
    }

    /**
     * JSON endpoint for AJAX progress polling.
     */
    public function executeQueueProgress($request)
    {
        $this->requireAdmin();

        $queueService = new \AtomFramework\Services\QueueService();

        $jobId = (int) $request->getParameter('job_id', 0);
        $batchId = (int) $request->getParameter('batch_id', 0);

        if ($jobId) {
            $progress = $queueService->getProgress($jobId);
        } elseif ($batchId) {
            $progress = $queueService->getBatchProgress($batchId);
        } else {
            $progress = ['error' => 'Provide job_id or batch_id'];
        }

        return $this->renderJson($progress);
    }

    /**
     * POST: Retry a failed queue job.
     */
    public function executeQueueRetry($request)
    {
        $this->requireAdmin();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $queueService = new \AtomFramework\Services\QueueService();

        $failedId = (int) $request->getParameter('failed_id', 0);
        $jobId = (int) $request->getParameter('job_id', 0);

        if ($failedId) {
            $newId = $queueService->retryFailed($failedId);
            if ($newId) {
                $this->getUser()->setFlash('notice', __('Job retried as #%1%.', ['%1%' => $newId]));
            } else {
                $this->getUser()->setFlash('error', __('Failed job not found.'));
            }
        } elseif ($jobId) {
            // Retry a specific job (reset it)
            $job = $queueService->getJob($jobId);
            if ($job && $job->status === 'failed') {
                \Illuminate\Database\Capsule\Manager::table('ahg_queue_job')
                    ->where('id', $jobId)
                    ->update([
                        'status' => 'pending',
                        'available_at' => date('Y-m-d H:i:s'),
                        'attempt_count' => 0,
                        'error_message' => null,
                        'error_code' => null,
                        'error_trace' => null,
                        'worker_id' => null,
                        'reserved_at' => null,
                        'started_at' => null,
                        'completed_at' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                $queueService->logEvent($jobId, null, 'retried', 'Job retried from admin UI');
                $this->getUser()->setFlash('notice', __('Job #%1% retried.', ['%1%' => $jobId]));
            } else {
                $this->getUser()->setFlash('error', __('Job not found or not in failed state.'));
            }
        }

        $this->redirect('@queue_browse');
    }

    /**
     * POST: Cancel a queue job or batch.
     */
    public function executeQueueCancel($request)
    {
        $this->requireAdmin();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $queueService = new \AtomFramework\Services\QueueService();

        $jobId = (int) $request->getParameter('job_id', 0);
        $batchId = (int) $request->getParameter('batch_id', 0);

        if ($jobId) {
            if ($queueService->cancelJob($jobId)) {
                $this->getUser()->setFlash('notice', __('Job #%1% cancelled.', ['%1%' => $jobId]));
            } else {
                $this->getUser()->setFlash('error', __('Could not cancel job.'));
            }
        } elseif ($batchId) {
            if ($queueService->cancelBatch($batchId)) {
                $this->getUser()->setFlash('notice', __('Batch #%1% cancelled.', ['%1%' => $batchId]));
            } else {
                $this->getUser()->setFlash('error', __('Could not cancel batch.'));
            }
        }

        $referer = $request->getReferer();
        $this->redirect($referer ?: '@queue_browse');
    }

    /**
     * Helper: require admin access.
     */
    private function requireAdmin()
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    /**
     * Helper: get QueueJobsService (lazy-loaded).
     */
    private function getQueueService()
    {
        static $service;
        if (!$service) {
            require_once dirname(__DIR__) . '/../../lib/Services/QueueJobsService.php';
            $service = new \AhgJobsManage\Services\QueueJobsService();
        }

        return $service;
    }

    /**
     * Export jobs as CSV download.
     */
    public function executeExport($request)
    {
        // Require authenticated user
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $user = $this->getUser();
        $userId = $this->userId();
        $isAdmin = $user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID);

        $service = new \AhgJobsManage\Services\JobsService();
        $jobs = $service->exportCsv($userId, $isAdmin);

        $filename = 'jobs-export-' . date('Y-m-d-His') . '.csv';

        $this->response->setContentType('text/csv; charset=utf-8');
        $this->response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://temp', 'r+');

        // CSV header
        fputcsv($output, [
            'ID',
            'Job Name',
            'Status',
            'User',
            'Created',
            'Completed',
            'Related Object Slug',
            'Output',
        ]);

        foreach ($jobs as $job) {
            fputcsv($output, [
                $job->id,
                $job->name,
                \AhgJobsManage\Services\JobsService::getStatusLabel($job->status_id),
                $job->user_name ?? '',
                $job->created_at ?? '',
                $job->completed_at ?? '',
                $job->object_slug ?? '',
                $job->output ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $this->renderText($csv);
    }
}
