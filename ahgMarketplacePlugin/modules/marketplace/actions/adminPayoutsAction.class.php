<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/TransactionRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;

class marketplaceAdminPayoutsAction extends AhgController
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

        $this->statusFilter = $request->getParameter('status', '');

        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 30;
        $offset = ($this->page - 1) * $limit;

        // Build filters
        $filters = [];
        if (!empty($this->statusFilter)) {
            $filters['status'] = $this->statusFilter;
        }

        $result = $txnRepo->getAllPayoutsForAdmin($filters, $limit, $offset);

        $this->payouts = $result['items'];
        $this->total = $result['total'];
    }
}
