<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class marketplaceAdminSellerVerifyAction extends AhgController
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

        $sellerId = (int) $request->getParameter('id');
        if (!$sellerId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'adminSellers']);
        }

        $sellerService = new SellerService();
        $sellerRepo = new SellerRepository();

        $this->seller = $sellerRepo->getById($sellerId);
        if (!$this->seller) {
            $this->forward404();
        }

        // Handle POST actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');

            if ($formAction === 'verify') {
                $result = $sellerService->verifySeller($sellerId);
                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Seller verified successfully.');
                } else {
                    $this->getUser()->setFlash('error', 'Failed to verify seller.');
                }
                $this->redirect(['module' => 'marketplace', 'action' => 'adminSellers']);
            } elseif ($formAction === 'suspend') {
                $result = $sellerService->suspendSeller($sellerId);
                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Seller suspended.');
                } else {
                    $this->getUser()->setFlash('error', 'Failed to suspend seller.');
                }
                $this->redirect(['module' => 'marketplace', 'action' => 'adminSellers']);
            }
        }
    }
}
