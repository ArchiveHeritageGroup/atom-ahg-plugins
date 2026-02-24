<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/OfferService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\OfferService;

class marketplaceSellerOfferRespondAction extends AhgController
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

        $offerId = (int) $request->getParameter('id');
        if (!$offerId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerOffers']);
        }

        $offerService = new OfferService();
        $this->offer = $offerService->getOfferWithDetails($offerId);

        if (!$this->offer) {
            $this->forward404();
        }

        // Verify the listing belongs to this seller
        if ((int) $this->offer->seller_id !== (int) $seller->id) {
            $this->getUser()->setFlash('error', 'You do not have permission to respond to this offer.');
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerOffers']);
        }

        // Handle POST actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');
            $response = trim($request->getParameter('seller_response', ''));

            if ($formAction === 'accept') {
                $result = $offerService->acceptOffer($offerId);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Offer accepted. A transaction will be created for the buyer.');
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }

                $this->redirect(['module' => 'marketplace', 'action' => 'sellerOffers']);
            } elseif ($formAction === 'reject') {
                $result = $offerService->rejectOffer($offerId, $response ?: null);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Offer rejected.');
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }

                $this->redirect(['module' => 'marketplace', 'action' => 'sellerOffers']);
            } elseif ($formAction === 'counter') {
                $counterAmount = (float) $request->getParameter('counter_amount');

                if ($counterAmount <= 0) {
                    $this->getUser()->setFlash('error', 'Counter amount must be greater than zero.');
                } else {
                    $result = $offerService->counterOffer($offerId, $counterAmount, $response ?: null);

                    if ($result['success']) {
                        $this->getUser()->setFlash('notice', 'Counter-offer sent.');
                    } else {
                        $this->getUser()->setFlash('error', $result['error']);
                    }

                    $this->redirect(['module' => 'marketplace', 'action' => 'sellerOffers']);
                }
            }
        }
    }
}
