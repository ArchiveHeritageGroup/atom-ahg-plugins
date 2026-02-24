<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceApiBidAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Auth required
        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required']);

            return sfView::NONE;
        }

        // POST only
        if (!$request->isMethod('post')) {
            $this->getResponse()->setStatusCode(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);

            return sfView::NONE;
        }

        $userId = (int) $this->context->user->getAttribute('user_id');
        $listingId = (int) $request->getParameter('id');

        if ($listingId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid listing ID']);

            return sfView::NONE;
        }

        $bidAmount = (float) $request->getParameter('bid_amount', 0);
        $maxBid = $request->getParameter('max_bid');
        $maxBid = ($maxBid !== null && $maxBid !== '') ? (float) $maxBid : null;

        if ($bidAmount <= 0) {
            echo json_encode(['success' => false, 'error' => 'Bid amount must be greater than zero']);

            return sfView::NONE;
        }

        $auctionService = new AuctionService();

        // Get auction by listing_id
        $auction = DB::table('marketplace_auction')->where('listing_id', $listingId)->first();
        if (!$auction) {
            echo json_encode(['success' => false, 'error' => 'Auction not found for this listing']);

            return sfView::NONE;
        }

        // Place bid
        $result = $auctionService->placeBid($auction->id, $userId, $bidAmount, $maxBid);

        // Get updated auction status
        $status = $auctionService->getAuctionStatus($auction->id);

        echo json_encode([
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
            'bid_id' => $result['bid_id'] ?? null,
            'auction' => $status,
        ]);

        return sfView::NONE;
    }
}
