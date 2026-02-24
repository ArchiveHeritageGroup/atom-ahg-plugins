<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService;

class marketplaceAuctionBrowseAction extends AhgController
{
    public function execute($request)
    {
        $auctionService = new AuctionService();

        // Pagination for active auctions
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;

        // Get all active auctions
        $results = $auctionService->getActiveAuctions($limit, $offset);

        // Get auctions ending within 60 minutes
        $endingSoon = $auctionService->getEndingSoon(60);

        $this->auctions = $results['items'];
        $this->total = $results['total'];
        $this->endingSoon = $endingSoon;
        $this->page = $page;
    }
}
