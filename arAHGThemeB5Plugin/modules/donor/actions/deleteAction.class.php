<?php

/**
 * Donor delete action - Delete donor with confirmation.
 *
 * Pure Laravel Query Builder via DonorRepository.
 */
class DonorDeleteAction extends sfAction
{
    public function execute($request)
    {
        // Initialize framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Get slug from request
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->forward404();
        }

        // Get current culture
        $culture = $this->context->user->getCulture();
        $repository = new \AtomExtensions\Repositories\DonorRepository($culture);

        // Load donor
        $this->donor = $repository->findBySlug($slug);

        if (null === $this->donor) {
            $this->forward404();
        }

        // Handle delete submission
        if ($request->isMethod('post') || $request->isMethod('delete')) {
            $repository->delete($this->donor->id);
            $this->redirect(['module' => 'donor', 'action' => 'browse']);
        }
    }
}
