<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Receive items on an order line (POST only).
 */
class acquisitionReceiveAction extends AhgController
{
    public function execute($request)
    {

        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'acquisition', 'action' => 'index']);
        }

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';

        $orderLineId = (int) $request->getParameter('order_line_id');
        $quantityReceived = max(1, (int) $request->getParameter('quantity_received', 1));

        if (!$orderLineId) {
            $this->getUser()->setFlash('error', __('Order line ID is required.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'index']);
        }

        // Look up the order ID for redirect
        $line = DB::table('library_order_line')->where('id', $orderLineId)->first();
        $orderId = $line ? (int) $line->order_id : 0;

        try {
            $service = AcquisitionService::getInstance();
            $result = $service->receiveOrderLine($orderLineId, $quantityReceived);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', __('Items received successfully. Status: %1%', ['%1%' => $result['status']]));
            } else {
                $this->getUser()->setFlash('error', $result['error'] ?? __('Failed to receive items.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Error receiving items: %1%', ['%1%' => $e->getMessage()]));
        }

        if ($orderId) {
            $this->redirect(['module' => 'acquisition', 'action' => 'order', 'order_id' => $orderId]);
        }

        $this->redirect(['module' => 'acquisition', 'action' => 'index']);
    }
}
