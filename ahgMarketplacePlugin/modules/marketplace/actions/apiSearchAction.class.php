<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceSearchService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceSearchService;

class marketplaceApiSearchAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $searchService = new MarketplaceSearchService();

        // Build filters from request parameters
        $filters = $searchService->buildSearchFilters([
            'sector' => $request->getParameter('sector'),
            'category_id' => $request->getParameter('category_id'),
            'listing_type' => $request->getParameter('listing_type'),
            'seller_id' => $request->getParameter('seller_id'),
            'price_min' => $request->getParameter('price_min'),
            'price_max' => $request->getParameter('price_max'),
            'condition_rating' => $request->getParameter('condition_rating'),
            'medium' => $request->getParameter('medium'),
            'country' => $request->getParameter('country'),
            'is_digital' => $request->getParameter('is_digital'),
            'sort' => $request->getParameter('sort'),
        ]);

        $query = trim($request->getParameter('query', ''));
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = min(100, max(1, (int) $request->getParameter('limit', 24)));
        $offset = ($page - 1) * $limit;

        // Perform search
        $results = $searchService->search($query, $filters, $limit, $offset);

        // Normalize image paths in results
        $items = [];
        foreach ($results['items'] as $item) {
            $row = (array) $item;
            // Ensure featured_image_path is a usable URL path
            if (!empty($row['featured_image_path'])) {
                $row['image'] = $row['featured_image_path'];
            } else {
                $row['image'] = null;
            }
            $items[] = $row;
        }

        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $results['total'],
            'page' => $page,
            'limit' => $limit,
            'query' => $results['query'],
            'facets' => $results['facets'],
        ]);

        return sfView::NONE;
    }
}
