<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/ListingRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;

class marketplaceAdminListingsAction extends AhgController
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

        $listingRepo = new ListingRepository();

        // Gather filters
        $this->filters = [
            'status' => $request->getParameter('status', ''),
            'sector' => $request->getParameter('sector', ''),
            'search' => $request->getParameter('search', ''),
            'include_all_statuses' => true,
        ];

        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 30;
        $offset = ($this->page - 1) * $limit;

        // Build browse filters
        $browseFilters = ['include_all_statuses' => true];
        if (!empty($this->filters['status'])) {
            $browseFilters['status'] = $this->filters['status'];
        }
        if (!empty($this->filters['sector'])) {
            $browseFilters['sector'] = $this->filters['sector'];
        }
        if (!empty($this->filters['search'])) {
            $browseFilters['search'] = $this->filters['search'];
        }

        $result = $listingRepo->browse($browseFilters, $limit, $offset, 'newest');

        $this->listings = $result['items'];
        $this->total = $result['total'];
    }
}
