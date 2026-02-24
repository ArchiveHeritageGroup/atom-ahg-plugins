<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/TransactionService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/ReviewService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\ReviewService;

class marketplaceReviewFormAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');
        $txnId = (int) $request->getParameter('id');

        if (!$txnId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
        }

        $transactionService = new TransactionService();
        $reviewService = new ReviewService();

        // Get transaction with details
        $transaction = $transactionService->getTransaction($txnId);
        if (!$transaction) {
            $this->getUser()->setFlash('error', 'Transaction not found.');
            $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
        }

        // Validate buyer owns transaction
        if ((int) $transaction->buyer_id !== $userId) {
            $this->getUser()->setFlash('error', 'You do not have permission to review this transaction.');
            $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
        }

        // Check transaction is completed
        if ($transaction->status !== 'completed') {
            $this->getUser()->setFlash('error', 'Transaction must be completed before leaving a review.');
            $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
        }

        // Check if already reviewed
        if ($reviewService->hasReviewed($txnId, $userId)) {
            $this->getUser()->setFlash('error', 'You have already reviewed this transaction.');
            $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
        }

        // Handle POST: create review
        if ($request->isMethod('post')) {
            $rating = (int) $request->getParameter('rating');
            $title = trim($request->getParameter('review_title', ''));
            $comment = trim($request->getParameter('review_comment', ''));

            $errors = [];
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Please select a rating between 1 and 5.';
            }
            if (empty($title)) {
                $errors[] = 'Review title is required.';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));
            } else {
                $result = $reviewService->createReview(
                    $txnId,
                    $userId,
                    $rating,
                    $title,
                    $comment ?: null,
                    'buyer_to_seller'
                );

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Thank you for your review!');
                    $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            }
        }

        $this->transaction = $transaction;
    }
}
