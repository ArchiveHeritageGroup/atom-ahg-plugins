<?php

/**
 * Request to Publish Delete Action
 *
 * Delete a publication request.
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class requestToPublishDeleteAction extends sfAction
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin permission
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        // Initialize repository
        require_once sfConfig::get('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Repositories/RequestToPublishRepository.php';
        $repository = new \ahgRequestToPublishPlugin\Repositories\RequestToPublishRepository();

        // Get request by slug
        $slug = $request->getParameter('slug');
        $resource = $repository->findBySlug($slug);

        if (!$resource) {
            $this->forward404();
        }

        // Handle confirmation
        if ($request->isMethod('post') && $request->getParameter('confirm') === 'yes') {
            try {
                $repository->delete($resource->id);
                $this->context->user->setFlash('notice', 'Request has been deleted.');
                $this->redirect(['module' => 'requestToPublish', 'action' => 'browse']);
            } catch (\Exception $e) {
                $this->context->user->setFlash('error', 'Error deleting request: ' . $e->getMessage());
            }
        }

        $this->resource = $resource;
    }
}
