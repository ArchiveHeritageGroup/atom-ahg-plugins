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
        $isAdmin = $user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID);

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
        $isAdmin = $user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID);

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
            QubitAcl::forwardUnauthorized();
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
        $isAdmin = $user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID);

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
                QubitAcl::forwardUnauthorized();
            }
        }

        $this->stats = $service->getStats($userId, $isAdmin);
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
        $isAdmin = $user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID);

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
