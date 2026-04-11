<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/CollectionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CollectionService;

class marketplaceFeaturedAction extends AhgController
{
    public function execute($request)
    {
        $service = new MarketplaceService();
        $collectionService = new CollectionService();

        // Get featured listings (seller featured or high view count)
        $featuredListings = $service->getFeaturedListings(12);

        // Get featured collections
        $featuredCollections = $collectionService->getFeatured(6);

        $this->featuredListings = $featuredListings;
        $this->featuredCollections = $featuredCollections;

        // Expose featured listing fee for display in templates
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';
        $settingsRepo = new \AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository();
        $this->featuredListingFee = (float) $settingsRepo->get('featured_listing_fee', '0');
        $this->defaultCurrency = $settingsRepo->get('default_currency', 'ZAR');
    }
}
