<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class marketplaceAdminSellersAction extends AhgController
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

        $sellerRepo = new SellerRepository();

        // Gather filters
        $this->filters = [
            'verification_status' => $request->getParameter('verification_status', ''),
            'search' => $request->getParameter('search', ''),
        ];

        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 30;
        $offset = ($this->page - 1) * $limit;

        // Build filters for repository
        $repoFilters = [];
        if (!empty($this->filters['verification_status'])) {
            $repoFilters['verification_status'] = $this->filters['verification_status'];
        }
        if (!empty($this->filters['search'])) {
            $repoFilters['search'] = $this->filters['search'];
        }

        $result = $sellerRepo->getAllForAdmin($repoFilters, $limit, $offset);

        $this->sellers = $result['items'];
        $this->total = $result['total'];
    }
}
