<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/ReviewService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\ReviewService;

class marketplaceSellerReviewsAction extends AhgController
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

        $reviewService = new ReviewService();

        // Get reviews with pagination
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $this->reviews = $reviewService->getSellerReviews($this->seller->id, $limit, $offset);

        // Rating breakdown stats
        $this->ratingStats = $reviewService->getRatingStats($this->seller->id);
    }
}
