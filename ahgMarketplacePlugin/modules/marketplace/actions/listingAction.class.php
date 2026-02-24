<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/SellerService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/CurrencyService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CurrencyService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceListingAction extends AhgController
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $service = new MarketplaceService();
        $sellerService = new SellerService();
        $currencyService = new CurrencyService();
        $auctionService = new AuctionService();
        $settingsRepo = new SettingsRepository();

        // Get listing (also increments view count)
        $listing = $service->getListing($slug);
        if (!$listing) {
            $this->forward404();
        }

        // Get seller profile
        $seller = $sellerService->getSellerById($listing->seller_id);

        // Get listing images
        $images = $service->getListingImages($listing->id);

        // Get auction details if this is an auction listing
        $auction = null;
        if ($listing->listing_type === 'auction') {
            $auctionData = DB::table('marketplace_auction')
                ->where('listing_id', $listing->id)
                ->first();

            if ($auctionData) {
                $auction = $auctionService->getAuctionStatus($auctionData->id);
            }
        }

        // Get available currencies
        $currencies = $currencyService->getCurrencies();

        // Check if current user follows this seller
        $isFollowing = false;
        $isFavourited = false;
        if ($this->getUser()->isAuthenticated()) {
            $userId = (int) $this->getUser()->getAttribute('user_id');
            $isFollowing = $settingsRepo->isFollowing($userId, $listing->seller_id);

            // Check if user has favourited this listing
            $isFavourited = DB::table('marketplace_favourite')
                ->where('user_id', $userId)
                ->where('listing_id', $listing->id)
                ->exists();
        }

        // Get related listings (same sector and/or category, excluding current)
        $relatedFilters = ['sector' => $listing->sector];
        if ($listing->category_id) {
            $relatedFilters['category_id'] = $listing->category_id;
        }
        $relatedResults = $service->browse($relatedFilters, 6, 0, 'newest');
        $relatedListings = array_filter($relatedResults['items'], function ($item) use ($listing) {
            return $item->id !== $listing->id;
        });
        $relatedListings = array_slice(array_values($relatedListings), 0, 4);

        $this->listing = $listing;
        $this->seller = $seller;
        $this->images = $images;
        $this->auction = $auction;
        $this->currencies = $currencies;
        $this->isFollowing = $isFollowing;
        $this->isFavourited = $isFavourited;
        $this->relatedListings = $relatedListings;
    }
}
