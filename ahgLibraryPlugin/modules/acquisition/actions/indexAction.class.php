<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Acquisition orders list with search/filter and budget summary.
 */
class acquisitionIndexAction extends AhgController
{
    public function execute($request)
    {

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        $service = AcquisitionService::getInstance();

        // Search/filter params
        $this->q = trim($request->getParameter('q', ''));
        $this->orderStatus = $request->getParameter('order_status', '');
        $this->orderType = $request->getParameter('order_type', '');
        $page = max(1, (int) $request->getParameter('page', 1));

        $result = $service->searchOrders([
            'q' => $this->q,
            'order_status' => $this->orderStatus,
            'order_type' => $this->orderType,
            'page' => $page,
            'limit' => 25,
        ]);

        $this->results = $result['items'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->totalPages = $result['pages'];

        // Budget summary for sidebar
        try {
            $this->budgets = $service->getBudgets(date('Y'));
        } catch (\Exception $e) {
            $this->budgets = [];
        }
    }
}
