<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/TransactionRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;

class marketplaceAdminTransactionsAction extends AhgController
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

        $txnRepo = new TransactionRepository();

        // Gather filters
        $this->filters = [
            'status' => $request->getParameter('status', ''),
            'payment_status' => $request->getParameter('payment_status', ''),
            'search' => $request->getParameter('search', ''),
        ];

        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 30;
        $offset = ($this->page - 1) * $limit;

        // Build filters for repository
        $repoFilters = [];
        if (!empty($this->filters['status'])) {
            $repoFilters['status'] = $this->filters['status'];
        }
        if (!empty($this->filters['payment_status'])) {
            $repoFilters['payment_status'] = $this->filters['payment_status'];
        }
        if (!empty($this->filters['search'])) {
            $repoFilters['search'] = $this->filters['search'];
        }

        $result = $txnRepo->getAllForAdmin($repoFilters, $limit, $offset);

        $this->transactions = $result['items'];
        $this->total = $result['total'];
    }
}
