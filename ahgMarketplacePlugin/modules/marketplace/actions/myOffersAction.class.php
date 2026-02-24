<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/OfferService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\OfferService;

class marketplaceMyOffersAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $offerService = new OfferService();

        // Handle POST actions (accept counter, withdraw)
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');
            $offerId = (int) $request->getParameter('offer_id');

            if ($formAction === 'accept_counter') {
                $result = $offerService->acceptCounter($offerId, $userId);
                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Counter-offer accepted. A transaction has been created.');
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            } elseif ($formAction === 'withdraw') {
                $result = $offerService->withdrawOffer($offerId, $userId);
                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Offer withdrawn successfully.');
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            }

            $this->redirect(['module' => 'marketplace', 'action' => 'myOffers']);
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $offerService->getBuyerOffers($userId, $limit, $offset);

        $this->offers = $result['items'];
        $this->total = $result['total'];
        $this->page = $page;
        $this->limit = $limit;
    }
}
