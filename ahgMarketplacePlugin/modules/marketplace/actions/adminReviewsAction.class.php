<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/ReviewService.php';
require_once $pluginPath . '/lib/Repositories/ReviewRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\ReviewService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ReviewRepository;

class marketplaceAdminReviewsAction extends AhgController
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

        $reviewRepo = new ReviewRepository();
        $reviewService = new ReviewService();

        // Handle POST: moderate review
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');

            if ($formAction === 'moderate') {
                $reviewId = (int) $request->getParameter('review_id');
                $visible = (bool) $request->getParameter('is_visible', 0);

                if ($reviewId) {
                    $result = $reviewService->moderateReview($reviewId, $visible);
                    if ($result['success']) {
                        $this->getUser()->setFlash('notice', 'Review moderated successfully.');
                    } else {
                        $this->getUser()->setFlash('error', $result['error']);
                    }
                }
            }
        }

        // Gather filters
        $this->filters = [
            'flagged' => $request->getParameter('flagged', ''),
            'is_visible' => $request->getParameter('is_visible', ''),
        ];

        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 30;
        $offset = ($this->page - 1) * $limit;

        // Build filters for repository
        $repoFilters = [];
        if (!empty($this->filters['flagged'])) {
            $repoFilters['flagged'] = 1;
        }
        if ($this->filters['is_visible'] !== '') {
            $repoFilters['is_visible'] = (int) $this->filters['is_visible'];
        }

        $result = $reviewRepo->getAllForAdmin($repoFilters, $limit, $offset);

        $this->reviews = $result['items'];
        $this->total = $result['total'];
    }
}
