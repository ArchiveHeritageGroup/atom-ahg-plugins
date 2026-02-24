<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/MarketplaceService.php';
require_once $pluginPath . '/lib/Repositories/ListingRepository.php';
require_once $pluginPath . '/lib/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class marketplaceAdminListingReviewAction extends AhgController
{
    public function execute($request)
    {
        // Admin check
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Admin access required.');
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $listingId = (int) $request->getParameter('id');
        if (!$listingId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'adminListings']);
        }

        $listingRepo = new ListingRepository();
        $sellerRepo = new SellerRepository();
        $marketplaceService = new MarketplaceService();

        $this->listing = $listingRepo->getById($listingId);
        if (!$this->listing) {
            $this->forward404();
        }

        $this->seller = $sellerRepo->getById($this->listing->seller_id);
        $this->images = $listingRepo->getImages($listingId);

        // Handle POST actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');

            if ($formAction === 'approve') {
                $result = $marketplaceService->approveListing($listingId);
                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Listing approved and now active.');
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
                $this->redirect(['module' => 'marketplace', 'action' => 'adminListings']);
            } elseif ($formAction === 'reject') {
                $result = $marketplaceService->rejectListing($listingId);
                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Listing rejected and returned to draft.');
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
                $this->redirect(['module' => 'marketplace', 'action' => 'adminListings']);
            } elseif ($formAction === 'suspend') {
                $listingRepo->update($listingId, ['status' => 'suspended']);
                $this->getUser()->setFlash('notice', 'Listing suspended.');
                $this->redirect(['module' => 'marketplace', 'action' => 'adminListings']);
            }
        }
    }
}
