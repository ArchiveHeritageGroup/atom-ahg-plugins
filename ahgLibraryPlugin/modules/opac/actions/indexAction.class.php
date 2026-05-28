<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * OPAC index — public catalog search page.
 *
 * Supports FRBR work-set clustering. When clustering is enabled,
 * results are grouped by work key so one card shows all manifestations.
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

        // Check if OPAC is enabled
        try {
            $opacEnabled = \Illuminate\Database\Capsule\Manager::table('library_settings')
                ->where('setting_key', 'opac_enabled')
                ->value('setting_value');
            if ($opacEnabled === '0') {
                $this->forward404();
            }
        } catch (\Exception $e) {
            // Table may not exist — allow by default
        }

        // Load OpacService
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OpacService.php';

        $service = OpacService::getInstance();

        // Search parameters
        $this->q            = trim($request->getParameter('q', ''));
        $this->searchType   = $request->getParameter('search_type', 'keyword');
        $this->sort         = $request->getParameter('sort', 'relevance');
        $this->materialType = $request->getParameter('material_type', '');
        $publicationYear    = $request->getParameter('publication_year', '');
        $this->page         = max(1, (int) $request->getParameter('page', 1));

        // FRBR clustering toggle (default on)
        $this->frbrCluster = (bool) $request->getParameter('frbr_cluster', 1);

        // Perform search if query present
        if (!empty($this->q) || !empty($this->materialType) || !empty($publicationYear)) {
            $searchResult = $service->search([
                'q'             => $this->q,
                'search_type'   => $this->searchType,
                'material_type' => $this->materialType,
                'publication_year' => $publicationYear,
                'sort'          => $this->sort,
                'page'          => $this->page,
                'limit'         => 20,
                'frbr_cluster'  => $this->frbrCluster,
            ]);

            if ($this->frbrCluster) {
                $this->clusters    = $searchResult['clusters'];
                $this->totalWorks = $searchResult['total_works'];
                $this->total       = $searchResult['total'];
                $this->totalPages  = $searchResult['pages'];
                // Flat items still passed for non-FRBR fallbacks
                $this->results     = $searchResult['items'];
            } else {
                $this->results    = $searchResult['items'];
                $this->total      = $searchResult['total'];
                $this->totalPages = $searchResult['pages'];
                $this->clusters   = [];
                $this->totalWorks = 0;
            }
        } else {
            $this->results    = [];
            $this->clusters   = [];
            $this->total      = 0;
            $this->totalWorks = 0;
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