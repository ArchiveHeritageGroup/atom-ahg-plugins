<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService;

class marketplaceApiAuctionStatusAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $auctionId = (int) $request->getParameter('id');

        if ($auctionId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid auction ID']);

            return sfView::NONE;
        }

        $auctionService = new AuctionService();
        $status = $auctionService->getAuctionStatus($auctionId);

        if (!$status) {
            $this->getResponse()->setStatusCode(404);
            echo json_encode(['success' => false, 'error' => 'Auction not found']);

            return sfView::NONE;
        }

        echo json_encode([
            'success' => true,
            'current_bid' => $status['current_bid'],
            'bid_count' => $status['bid_count'],
            'end_time' => $status['end_time'],
            'time_remaining' => $status['time_remaining'],
            'reserve_met' => $status['reserve_met'],
            'status' => $status['status'],
            'buy_now_price' => $status['buy_now_price'],
        ]);

        return sfView::NONE;
    }
}
