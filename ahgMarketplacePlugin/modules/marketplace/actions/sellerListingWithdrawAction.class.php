<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/MarketplaceService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;

class marketplaceSellerListingWithdrawAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $sellerService = new SellerService();
        $seller = $sellerService->getSellerByUserId($userId);

        if (!$seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        $listingId = (int) $request->getParameter('id');
        if (!$listingId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerListings']);
        }

        $marketplaceService = new MarketplaceService();
        $listing = $marketplaceService->getListingById($listingId);

        if (!$listing) {
            $this->forward404();
        }

        // Verify seller owns this listing
        if ((int) $listing->seller_id !== (int) $seller->id) {
            $this->getUser()->setFlash('error', 'You do not have permission to withdraw this listing.');
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerListings']);
        }

        $result = $marketplaceService->withdrawListing($listingId);

        if ($result['success']) {
            $this->getUser()->setFlash('notice', 'Listing has been withdrawn.');
        } else {
            $this->getUser()->setFlash('error', $result['error']);
        }

        $this->redirect(['module' => 'marketplace', 'action' => 'sellerListings']);
    }
}
