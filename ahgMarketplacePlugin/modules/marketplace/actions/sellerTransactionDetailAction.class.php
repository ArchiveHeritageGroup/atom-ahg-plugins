<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/TransactionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;

class marketplaceSellerTransactionDetailAction extends AhgController
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

        $txnId = (int) $request->getParameter('id');
        if (!$txnId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerTransactions']);
        }

        $transactionService = new TransactionService();
        $this->transaction = $transactionService->getTransaction($txnId);

        if (!$this->transaction) {
            $this->forward404();
        }

        // Verify seller owns this transaction
        if ((int) $this->transaction->seller_id !== (int) $seller->id) {
            $this->getUser()->setFlash('error', 'You do not have permission to view this transaction.');
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerTransactions']);
        }

        // Handle POST: update shipping information
        if ($request->isMethod('post')) {
            $shippingData = [
                'tracking_number' => trim($request->getParameter('tracking_number', '')),
                'courier' => trim($request->getParameter('courier', '')),
                'shipping_status' => $request->getParameter('shipping_status', ''),
            ];

            // Filter out empty values
            $shippingData = array_filter($shippingData, function ($v) {
                return $v !== '';
            });

            if (!empty($shippingData)) {
                $result = $transactionService->updateShipping($txnId, $shippingData);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Shipping information updated.');
                    // Reload transaction data
                    $this->transaction = $transactionService->getTransaction($txnId);
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            }
        }
    }
}
