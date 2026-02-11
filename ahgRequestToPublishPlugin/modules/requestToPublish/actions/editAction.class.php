<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Request to Publish Edit Action
 *
 * View and update publication request details.
 * Uses Laravel Query Builder via RequestToPublishService.
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class requestToPublishEditAction extends AhgController
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

        // Initialize service and repository
        require_once $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Repositories/RequestToPublishRepository.php';
        require_once $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Services/RequestToPublishService.php';
        
        $repository = new \ahgRequestToPublishPlugin\Repositories\RequestToPublishRepository();
        $service = new \ahgRequestToPublishPlugin\Services\RequestToPublishService();

        // Get request by slug
        $slug = $request->getParameter('slug');
        $this->resource = $repository->findBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        // Get related information object
        $this->resource = $service->getRequestWithObject($this->resource->id);

        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');
            $adminNotes = $request->getParameter('rtp_admin_notes');

            try {
                if ($action === 'approve') {
                    $service->approveRequest($this->resource->id, $adminNotes);
                    $this->getUser()->setFlash('notice', 'Request has been approved.');
                } elseif ($action === 'reject') {
                    $service->rejectRequest($this->resource->id, $adminNotes);
                    $this->getUser()->setFlash('notice', 'Request has been rejected.');
                } else {
                    // Just update admin notes
                    $repository->update($this->resource->id, ['rtp_admin_notes' => $adminNotes]);
                    $this->getUser()->setFlash('notice', 'Request has been updated.');
                }

                $this->redirect(['module' => 'requestToPublish', 'action' => 'browse']);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            }

            // Reload resource after update
            $this->resource = $service->getRequestWithObject($this->resource->id);
        }

        // Repository reference for status helpers
        $this->repository = $repository;
    }
}
