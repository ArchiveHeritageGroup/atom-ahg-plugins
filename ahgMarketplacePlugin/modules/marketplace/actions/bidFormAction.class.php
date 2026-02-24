<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceBidFormAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $marketplaceService = new MarketplaceService();
        $auctionService = new AuctionService();

        // Get listing
        $listing = DB::table('marketplace_listing')->where('slug', $slug)->first();
        if (!$listing) {
            $this->forward404();
        }

        if ($listing->listing_type !== 'auction') {
            $this->getUser()->setFlash('error', 'This listing is not an auction.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        // Get auction record
        $auction = DB::table('marketplace_auction')->where('listing_id', $listing->id)->first();
        if (!$auction) {
            $this->getUser()->setFlash('error', 'Auction not found for this listing.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        if ($auction->status !== 'active') {
            $this->getUser()->setFlash('error', 'This auction is not currently active.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        // Check if auction has ended by time
        if (strtotime($auction->end_time) <= time()) {
            $this->getUser()->setFlash('error', 'This auction has ended.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        // Get primary image
        $images = $marketplaceService->getListingImages($listing->id);
        $primaryImage = null;
        foreach ($images as $img) {
            if ($img->is_primary) {
                $primaryImage = $img;
                break;
            }
        }
        if (!$primaryImage && !empty($images)) {
            $primaryImage = $images[0];
        }

        // Calculate minimum bid
        $currentBid = $auction->current_bid ?? $auction->starting_bid;
        $minBid = (float) $currentBid + (float) ($auction->bid_increment ?? 1);

        // Handle POST: place bid
        if ($request->isMethod('post')) {
            $bidAmount = (float) $request->getParameter('bid_amount');
            $maxBid = $request->getParameter('max_bid');
            $maxBid = $maxBid ? (float) $maxBid : null;

            $result = $auctionService->placeBid($auction->id, $userId, $bidAmount, $maxBid);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Your bid has been placed successfully.');
                $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
            } else {
                $this->getUser()->setFlash('error', $result['error']);
                // Refresh auction data after failed bid
                $auction = DB::table('marketplace_auction')->where('id', $auction->id)->first();
                $currentBid = $auction->current_bid ?? $auction->starting_bid;
                $minBid = (float) $currentBid + (float) ($auction->bid_increment ?? 1);
            }
        }

        // Get recent bid history (last 5)
        $bidHistory = $auctionService->getBidHistory($auction->id, 5);

        $this->listing = $listing;
        $this->auction = $auction;
        $this->primaryImage = $primaryImage;
        $this->currentBid = $currentBid;
        $this->minBid = $minBid;
        $this->bidHistory = $bidHistory;
    }
}
