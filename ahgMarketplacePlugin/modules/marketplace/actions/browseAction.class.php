<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceSearchService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceSearchService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceBrowseAction extends AhgController
{
    public function execute($request)
    {
        $service = new MarketplaceService();
        $searchService = new MarketplaceSearchService();
        $settingsRepo = new SettingsRepository();

        // Gather filters from request
        $filters = $searchService->buildSearchFilters([
            'sector' => $request->getParameter('sector'),
            'category_id' => $request->getParameter('category_id'),
            'listing_type' => $request->getParameter('listing_type'),
            'price_min' => $request->getParameter('price_min'),
            'price_max' => $request->getParameter('price_max'),
            'condition_rating' => $request->getParameter('condition_rating'),
            'medium' => $request->getParameter('medium'),
            'country' => $request->getParameter('country'),
            'is_digital' => $request->getParameter('is_digital'),
            'sort' => $request->getParameter('sort'),
        ]);

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;
        $sort = $request->getParameter('sort', 'newest');

        $results = $service->browse($filters, $limit, $offset, $sort);
        $facets = $service->getFacetCounts($filters);

        // Sector list for filter sidebar
        $validSectors = ['gallery', 'museum', 'archive', 'library', 'dam'];

        // Categories for filter sidebar (scoped to selected sector if any)
        $sectorFilter = !empty($filters['sector']) ? $filters['sector'] : null;
        $categories = $settingsRepo->getCategories($sectorFilter);

        $this->listings = $results['items'];
        $this->total = $results['total'];
        $this->filters = $filters;
        $this->facets = $facets;
        $this->page = $page;
        $this->limit = $limit;
        $this->sectors = $validSectors;
        $this->categories = $categories;
    }
}
