<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Create or edit a purchase order.
 */
class acquisitionOrderEditAction extends AhgController
{
    public function execute($request)
    {

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AcquisitionService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgCorePlugin/lib/Services/AhgTaxonomyService.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        $service = AcquisitionService::getInstance();
        $taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();

        // Load order types from taxonomy
        try {
            $this->orderTypes = $taxonomyService->getTerms('library_order_type');
        } catch (\Exception $e) {
            $this->orderTypes = [];
        }

        // Load budgets for dropdown
        try {
            $this->budgets = $service->getBudgets();
        } catch (\Exception $e) {
            $this->budgets = [];
        }

        // Editing existing order?
        $orderId = (int) $request->getParameter('order_id');
        $this->order = null;

        if ($orderId) {
            $data = $service->getOrder($orderId);
            if ($data) {
                $this->order = $data['order'];
            }
        }

        // Handle POST — save
        if ('POST' === $request->getMethod()) {
            $this->saveOrder($request, $service, $orderId);

            return;
        }
    }

    protected function saveOrder($request, AcquisitionService $service, int $orderId): void
    {
        $vendorName = trim($request->getParameter('vendor_name', ''));
        if (empty($vendorName)) {
            $this->getUser()->setFlash('error', __('Vendor name is required.'));
            $this->redirect(['module' => 'acquisition', 'action' => 'orderEdit', 'order_id' => $orderId ?: null]);
        }

        $data = [
            'vendor_name' => $vendorName,
            'vendor_account' => trim($request->getParameter('vendor_account', '')) ?: null,
            'order_date' => $request->getParameter('order_date', date('Y-m-d')),
            'order_type' => $request->getParameter('order_type', 'purchase'),
            'budget_id' => $request->getParameter('budget_id') ?: null,
            'currency' => $request->getParameter('currency', 'USD'),
            'notes' => trim($request->getParameter('notes', '')) ?: null,
        ];

        try {
            if ($orderId) {
                // Update existing order
                DB::table('library_order')
                    ->where('id', $orderId)
                    ->update(array_merge($data, ['updated_at' => date('Y-m-d H:i:s')]));
                $this->getUser()->setFlash('notice', __('Order updated successfully.'));
            } else {
                // Create new order
                $orderId = $service->createOrder($data);
                $this->getUser()->setFlash('notice', __('Order created successfully.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Failed to save order: %1%', ['%1%' => $e->getMessage()]));
            $this->redirect(['module' => 'acquisition', 'action' => 'orderEdit']);

            return;
        }

        $this->redirect(['module' => 'acquisition', 'action' => 'order', 'order_id' => $orderId]);
    }
}
