<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/TransactionService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/ReviewService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\ReviewService;

class marketplaceMyPurchasesAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $transactionService = new TransactionService();
        $reviewService = new ReviewService();

        // Handle confirm receipt POST
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'confirm_receipt') {
            $txnId = (int) $request->getParameter('transaction_id');
            $result = $transactionService->confirmReceipt($txnId, $userId);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Receipt confirmed. Thank you!');
            } else {
                $this->getUser()->setFlash('error', $result['error']);
            }
            $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $transactionService->getBuyerTransactions($userId, $limit, $offset);

        // Check which transactions have been reviewed
        $reviewedMap = [];
        foreach ($result['items'] as $txn) {
            $reviewedMap[$txn->id] = $reviewService->hasReviewed($txn->id, $userId);
        }

        $this->transactions = $result['items'];
        $this->total = $result['total'];
        $this->page = $page;
        $this->limit = $limit;
        $this->reviewedMap = $reviewedMap;
    }
}
