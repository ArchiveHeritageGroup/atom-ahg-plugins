<?php

/**
 * Donor index action - View single donor.
 *
 * Pure Laravel Query Builder via DonorRepository.
 */
class DonorIndexAction extends sfAction
{
    public function execute($request)
    {
        // Initialize framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Get slug from request
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->forward404();
        }

        // Get current culture
        $culture = $this->context->user->getCulture();

        // Load donor via repository
        $repository = new \AtomExtensions\Repositories\DonorRepository($culture);
        $this->donor = $repository->findBySlug($slug);

        if (null === $this->donor) {
            $this->forward404();
        }

        // Set page title
        $title = $this->donor->authorizedFormOfName ?: $this->context->i18n->__('Untitled');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Check permissions (simplified - check if user is authenticated)
        $this->canEdit = $this->getUser()->isAuthenticated();
        $this->canDelete = $this->getUser()->isAuthenticated();
        $this->canCreate = $this->getUser()->isAuthenticated();
    }
}
