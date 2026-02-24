<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/PayoutService.php';
require_once $pluginPath . '/lib/Repositories/TransactionRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\PayoutService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;

class marketplaceSellerPayoutsAction extends AhgController
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

        $payoutService = new PayoutService();
        $result = $payoutService->getSellerPayouts($this->seller->id);

        $this->payouts = $result['items'];

        // Get pending payout amount (earned but not yet paid out)
        $txnRepo = new TransactionRepository();
        $this->pendingAmount = $txnRepo->getSellerPendingPayoutAmount($this->seller->id);
    }
}
