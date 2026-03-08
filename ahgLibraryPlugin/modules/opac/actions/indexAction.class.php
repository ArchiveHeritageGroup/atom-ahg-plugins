<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * OPAC index — public catalog search page.
 *
 * @package    ahgLibraryPlugin
 * @subpackage opac
 */
class opacIndexAction extends AhgController
{
    public function execute($request)
    {
        // Initialize database
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load OpacService
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OpacService.php';

        $service = OpacService::getInstance();

        // Search parameters
        $this->q          = trim($request->getParameter('q', ''));
        $this->searchType = $request->getParameter('search_type', 'keyword');
        $this->sort       = $request->getParameter('sort', 'relevance');
        $this->materialType = $request->getParameter('material_type', '');
        $publicationYear  = $request->getParameter('publication_year', '');
        $page             = max(1, (int) $request->getParameter('page', 1));

        // Perform search if query present
        if (!empty($this->q) || !empty($this->materialType) || !empty($publicationYear)) {
            $searchResult = $service->search([
                'q'            => $this->q,
                'search_type'  => $this->searchType,
                'material_type' => $this->materialType,
                'publication_year' => $publicationYear,
                'sort'         => $this->sort,
                'page'         => $page,
                'limit'        => 20,
            ]);

            $this->results    = $searchResult['items'];
            $this->total      = $searchResult['total'];
            $this->page       = $searchResult['page'];
            $this->totalPages = $searchResult['pages'];
        } else {
            $this->results    = [];
            $this->total      = 0;
            $this->page       = 1;
            $this->totalPages = 0;
        }

        // Facets for sidebar
        $this->facets = $service->getFacets();

        // Discovery sections (shown when no search query)
        $this->newArrivals = $service->getNewArrivals(8);
        $this->popular     = $service->getPopular(8);
    }
}
