<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/OfferService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\OfferService;

class marketplaceSellerOffersAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $sellerService = new SellerService();
        $this->seller = $sellerService->getSellerByUserId($userId);

        if (!$this->seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        $this->statusFilter = $request->getParameter('status', '');
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($this->page - 1) * $limit;

        $offerService = new OfferService();
        $statusParam = !empty($this->statusFilter) ? $this->statusFilter : null;
        $result = $offerService->getSellerOffers($this->seller->id, $statusParam, $limit, $offset);

        $this->offers = $result['items'];
        $this->total = $result['total'];
    }
}
