<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/SellerService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/ReviewService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/CollectionService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\ReviewService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CollectionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class marketplaceSellerAction extends AhgController
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $sellerService = new SellerService();
        $marketplaceService = new MarketplaceService();
        $reviewService = new ReviewService();
        $collectionService = new CollectionService();
        $settingsRepo = new SettingsRepository();
        $sellerRepo = new SellerRepository();

        // Get seller profile
        $seller = $sellerService->getSellerBySlug($slug);
        if (!$seller) {
            $this->forward404();
        }

        // Get seller's active listings with pagination
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;

        $listingsResult = $marketplaceService->browse(
            ['seller_id' => $seller->id],
            $limit,
            $offset,
            'newest'
        );

        // Get seller reviews
        $reviews = $reviewService->getSellerReviews($seller->id, 10, 0);

        // Get rating breakdown stats
        $ratingStats = $reviewService->getRatingStats($seller->id);

        // Get seller's public collections
        $collections = $collectionService->getSellerCollections($seller->id);
        // Filter to public only for visitor view
        $collections = array_filter($collections, function ($c) {
            return $c->is_public;
        });
        $collections = array_values($collections);

        // Follower count
        $followerCount = $sellerRepo->getFollowerCount($seller->id);

        // Check if current user follows this seller
        $isFollowing = false;
        if ($this->getUser()->isAuthenticated()) {
            $userId = (int) $this->getUser()->getAttribute('user_id');
            $isFollowing = $settingsRepo->isFollowing($userId, $seller->id);
        }

        $this->seller = $seller;
        $this->listings = $listingsResult['items'];
        $this->total = $listingsResult['total'];
        $this->page = $page;
        $this->reviews = $reviews;
        $this->ratingStats = $ratingStats;
        $this->collections = $collections;
        $this->followerCount = $followerCount;
        $this->isFollowing = $isFollowing;
    }
}
