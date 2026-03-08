<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Add a line item to a purchase order (POST only).
 */
class acquisitionAddLineAction extends AhgController
{
    public function execute($request)
    {

        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'acquisition', 'action' => 'index']);
        }

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';

        $orderId = (int) $request->getParameter('order_id');
        if (!$orderId) {
            $this->getUser()->setFlash('error', __('Order ID is required.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'index']);
        }

        $title = trim($request->getParameter('title', ''));
        if (empty($title)) {
            $this->getUser()->setFlash('error', __('Title is required.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'order', 'order_id' => $orderId]);
        }

        try {
            $service = AcquisitionService::getInstance();
            $service->addOrderLine($orderId, [
                'title' => $title,
                'isbn' => trim($request->getParameter('isbn', '')) ?: null,
                'quantity' => max(1, (int) $request->getParameter('quantity', 1)),
                'unit_price' => (float) $request->getParameter('unit_price', 0),
                'fund_code' => trim($request->getParameter('fund_code', '')) ?: null,
                'notes' => trim($request->getParameter('notes', '')) ?: null,
            ]);

            $this->getUser()->setFlash('notice', __('Line item added successfully.'));
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Failed to add line item: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'acquisition', 'action' => 'order', 'order_id' => $orderId]);
    }
}
