<?php

class ahgMarketplacePluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
    }

    public function contextLoadFactories(sfEvent $event)
    {
        // Bootstrap Laravel DB if needed
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        $bootstrapFile = $frameworkPath . '/src/bootstrap.php';

        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/src/Routing/RouteLoader.php';

        $router = new \AtomFramework\Routing\RouteLoader('marketplace');

        // =====================================================================
        // PUBLIC ROUTES (no auth required)
        // =====================================================================
        $router->any('ahg_marketplace_browse', '/marketplace', 'browse');
        $router->any('ahg_marketplace_search', '/marketplace/search', 'search');
        $router->any('ahg_marketplace_sector', '/marketplace/sector/:sector', 'sector', ['sector' => '[a-z]+']);
        $router->any('ahg_marketplace_category', '/marketplace/category/:sector/:slug', 'category', ['sector' => '[a-z]+', 'slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_auctions', '/marketplace/auctions', 'auctionBrowse');
        $router->any('ahg_marketplace_featured', '/marketplace/featured', 'featured');
        $router->any('ahg_marketplace_collection', '/marketplace/collection/:slug', 'collection', ['slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_seller', '/marketplace/seller/:slug', 'seller', ['slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_listing', '/marketplace/listing/:slug', 'listing', ['slug' => '[a-z0-9\-]+']);

        // =====================================================================
        // BUYER ROUTES (auth required)
        // =====================================================================
        $router->any('ahg_marketplace_buy', '/marketplace/buy/:slug', 'buy', ['slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_offer', '/marketplace/offer/:slug', 'offerForm', ['slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_bid', '/marketplace/bid/:slug', 'bidForm', ['slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_enquiry', '/marketplace/enquiry/:slug', 'enquiryForm', ['slug' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_my_purchases', '/marketplace/my/purchases', 'myPurchases');
        $router->any('ahg_marketplace_my_bids', '/marketplace/my/bids', 'myBids');
        $router->any('ahg_marketplace_my_offers', '/marketplace/my/offers', 'myOffers');
        $router->any('ahg_marketplace_my_following', '/marketplace/my/following', 'myFollowing');
        $router->post('ahg_marketplace_follow', '/marketplace/follow/:seller', 'follow', ['seller' => '[a-z0-9\-]+']);
        $router->any('ahg_marketplace_review', '/marketplace/review/:id', 'reviewForm', ['id' => '\d+']);

        // =====================================================================
        // SELLER ROUTES (auth required, seller profile)
        // =====================================================================
        $router->any('ahg_marketplace_sell', '/marketplace/sell', 'dashboard');
        $router->any('ahg_marketplace_sell_register', '/marketplace/sell/register', 'sellerRegister');
        $router->any('ahg_marketplace_sell_profile', '/marketplace/sell/profile', 'sellerProfile');
        $router->any('ahg_marketplace_sell_listings', '/marketplace/sell/listings', 'sellerListings');
        $router->any('ahg_marketplace_sell_listing_create', '/marketplace/sell/listings/create', 'sellerListingCreate');
        $router->any('ahg_marketplace_sell_listing_edit', '/marketplace/sell/listings/:id/edit', 'sellerListingEdit', ['id' => '\d+']);
        $router->any('ahg_marketplace_sell_listing_images', '/marketplace/sell/listings/:id/images', 'sellerListingImages', ['id' => '\d+']);
        $router->any('ahg_marketplace_sell_listing_publish', '/marketplace/sell/listings/:id/publish', 'sellerListingPublish', ['id' => '\d+']);
        $router->any('ahg_marketplace_sell_listing_withdraw', '/marketplace/sell/listings/:id/withdraw', 'sellerListingWithdraw', ['id' => '\d+']);
        $router->any('ahg_marketplace_sell_offers', '/marketplace/sell/offers', 'sellerOffers');
        $router->any('ahg_marketplace_sell_offer_respond', '/marketplace/sell/offers/:id/respond', 'sellerOfferRespond', ['id' => '\d+']);
        $router->any('ahg_marketplace_sell_transactions', '/marketplace/sell/transactions', 'sellerTransactions');
        $router->any('ahg_marketplace_sell_transaction_detail', '/marketplace/sell/transactions/:id', 'sellerTransactionDetail', ['id' => '\d+']);
        $router->any('ahg_marketplace_sell_payouts', '/marketplace/sell/payouts', 'sellerPayouts');
        $router->any('ahg_marketplace_sell_reviews', '/marketplace/sell/reviews', 'sellerReviews');
        $router->any('ahg_marketplace_sell_enquiries', '/marketplace/sell/enquiries', 'sellerEnquiries');
        $router->any('ahg_marketplace_sell_collections', '/marketplace/sell/collections', 'sellerCollections');
        $router->any('ahg_marketplace_sell_collection_create', '/marketplace/sell/collections/create', 'sellerCollectionCreate');
        $router->any('ahg_marketplace_sell_analytics', '/marketplace/sell/analytics', 'sellerAnalytics');

        // =====================================================================
        // ADMIN ROUTES
        // =====================================================================
        $router->any('ahg_marketplace_admin', '/marketplace/admin', 'adminDashboard');
        $router->any('ahg_marketplace_admin_listings', '/marketplace/admin/listings', 'adminListings');
        $router->any('ahg_marketplace_admin_listing_review', '/marketplace/admin/listings/:id/review', 'adminListingReview', ['id' => '\d+']);
        $router->any('ahg_marketplace_admin_sellers', '/marketplace/admin/sellers', 'adminSellers');
        $router->any('ahg_marketplace_admin_seller_verify', '/marketplace/admin/sellers/:id/verify', 'adminSellerVerify', ['id' => '\d+']);
        $router->any('ahg_marketplace_admin_transactions', '/marketplace/admin/transactions', 'adminTransactions');
        $router->any('ahg_marketplace_admin_payouts', '/marketplace/admin/payouts', 'adminPayouts');
        $router->post('ahg_marketplace_admin_payouts_batch', '/marketplace/admin/payouts/batch', 'adminPayoutsBatch');
        $router->any('ahg_marketplace_admin_reviews', '/marketplace/admin/reviews', 'adminReviews');
        $router->any('ahg_marketplace_admin_categories', '/marketplace/admin/categories', 'adminCategories');
        $router->any('ahg_marketplace_admin_currencies', '/marketplace/admin/currencies', 'adminCurrencies');
        $router->any('ahg_marketplace_admin_settings', '/marketplace/admin/settings', 'adminSettings');
        $router->any('ahg_marketplace_admin_reports', '/marketplace/admin/reports', 'adminReports');

        // =====================================================================
        // API ROUTES (AJAX / future mobile)
        // =====================================================================
        $router->any('ahg_marketplace_api_search', '/marketplace/api/search', 'apiSearch');
        $router->post('ahg_marketplace_api_bid', '/marketplace/api/listing/:id/bid', 'apiBid', ['id' => '\d+']);
        $router->post('ahg_marketplace_api_favourite', '/marketplace/api/listing/:id/favourite', 'apiFavourite', ['id' => '\d+']);
        $router->any('ahg_marketplace_api_auction_status', '/marketplace/api/auction/:id/status', 'apiAuctionStatus', ['id' => '\d+']);
        $router->any('ahg_marketplace_api_currencies', '/marketplace/api/currencies', 'apiCurrencies');
        $router->any('ahg_marketplace_api_categories', '/marketplace/api/categories/:sector', 'apiCategories', ['sector' => '[a-z]+']);

        $router->register($event->getSubject());
    }
}
