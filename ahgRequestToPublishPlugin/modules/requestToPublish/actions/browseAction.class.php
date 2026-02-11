<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Request to Publish Browse Action
 *
 * Lists all publication requests with filtering and pagination.
 * Uses Laravel Query Builder via RequestToPublishRepository.
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class requestToPublishBrowseAction extends AhgController
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin permission
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        // Initialize repository
        require_once $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Repositories/RequestToPublishRepository.php';
        $repository = new \ahgRequestToPublishPlugin\Repositories\RequestToPublishRepository();

        // Get filter parameters
        $this->filter = $request->getParameter('filter', 'all');
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $this->sort = $request->getParameter('sort', 'created_at');
        $this->order = $request->getParameter('order', 'desc');
        $perPage = 25;

        // Get status filter
        $statusFilter = null;
        if ($this->filter !== 'all') {
            $statusFilter = $this->filter;
        }

        // Get paginated results
        $result = $repository->paginate($this->page, $perPage, $statusFilter, $this->sort, $this->order);

        $this->requests = $result['results'];
        $this->total = $result['total'];
        $this->pages = $result['pages'];

        // Get status counts for tabs
        $this->statusCounts = $repository->getStatusCounts();

        // Repository reference for templates
        $this->repository = $repository;
    }
}
