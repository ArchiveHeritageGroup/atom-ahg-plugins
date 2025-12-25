<?php

/**
 * Donor list action - List for dropdowns/selects.
 *
 * Pure Laravel Query Builder via DonorRepository.
 */
class DonorListAction extends sfAction
{
    public function execute($request)
    {
        // Initialize framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Get current culture
        $culture = $this->context->user->getCulture();

        // Load data via repository
        $repository = new \AtomExtensions\Repositories\DonorRepository($culture);
        $this->donors = $repository->getList();
    }
}
