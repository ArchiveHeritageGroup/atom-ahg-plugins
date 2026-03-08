<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * View a single purchase order with its lines.
 */
class acquisitionOrderAction extends AhgController
{
    public function execute($request)
    {

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        $orderId = (int) $request->getParameter('order_id');
        if (!$orderId) {
            $this->getUser()->setFlash('error', __('Order ID is required.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'index']);
        }

        $service = AcquisitionService::getInstance();
        $data = $service->getOrder($orderId);

        if (!$data) {
            $this->getUser()->setFlash('error', __('Order not found.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'index']);
        }

        $this->order = $data['order'];
        $this->lines = $data['lines'];
    }
}
