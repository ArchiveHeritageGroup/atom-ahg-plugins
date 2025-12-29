<?php

/**
 * Donor browse action - List all donors with pagination.
 *
 * Pure Laravel Query Builder via DonorRepository.
 */
class DonorBrowseAction extends sfAction
{
    public function execute($request)
    {
        // Initialize framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Set page title
        $this->response->setTitle($this->context->i18n->__('Browse donors').' - '.$this->response->getTitle());

        // Get current culture
        $culture = $this->context->user->getCulture();

        // Pagination settings
        $this->limit = isset($request->limit) ? (int) $request->limit : sfConfig::get('app_hits_per_page', 10);
        $this->page = isset($request->page) ? (int) $request->page : 1;

        // Sort settings
        if (!isset($request->sort)) {
            $request->sort = $this->getUser()->isAuthenticated()
                ? sfConfig::get('app_sort_browser_user', 'lastUpdated')
                : sfConfig::get('app_sort_browser_anonymous', 'alphabetic');
        }
        $this->sort = $request->sort;

        // Sort direction
        $defaultSortDir = ('lastUpdated' === $request->sort) ? 'desc' : 'asc';
        $this->sortDir = (isset($request->sortDir) && in_array($request->sortDir, ['asc', 'desc']))
            ? $request->sortDir
            : $defaultSortDir;

        // Search query
        $this->subquery = isset($request->subquery) ? $request->subquery : null;

        // Load data via repository
        $repository = new \AtomExtensions\Repositories\DonorRepository($culture);
        $result = $repository->browse([
            'page' => $this->page,
            'limit' => $this->limit,
            'sort' => $this->sort,
            'sortDir' => $this->sortDir,
            'subquery' => $this->subquery,
        ]);

        $this->donors = $result['results'];
        $this->total = $result['total'];
        $this->pages = $result['pages'];

        // Permission check
        $this->canCreate = $this->getUser()->isAuthenticated();
    }
}
