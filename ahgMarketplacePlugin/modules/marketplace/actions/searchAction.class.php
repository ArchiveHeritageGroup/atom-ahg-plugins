<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceSearchService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceSearchService;

class marketplaceSearchAction extends AhgController
{
    public function execute($request)
    {
        $searchService = new MarketplaceSearchService();

        $query = trim($request->getParameter('query', ''));

        // Build filters from request parameters
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

        $results = $searchService->search($query, $filters, $limit, $offset);

        $this->results = $results['items'];
        $this->total = $results['total'];
        $this->query = $results['query'];
        $this->filters = $filters;
        $this->facets = $results['facets'];
        $this->page = $page;
    }
}
