<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService;

class marketplaceMyBidsAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $auctionService = new AuctionService();

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $auctionService->getUserBids($userId, $limit, $offset);

        $this->bids = $result['items'];
        $this->total = $result['total'];
        $this->page = $page;
        $this->limit = $limit;
    }
}
