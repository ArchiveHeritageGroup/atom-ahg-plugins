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
        require_once dirname(__FILE__, 4).'/lib/Service/AcquisitionService.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        $service = AcquisitionService::getInstance();

        // Order types from taxonomy. getTerms() does not exist on the service -
        // getTermsWithAttributes() returns [code => term-object] (with ->code and
        // ->name); catch Throwable so a bad call degrades to the template's
        // built-in default order types instead of a white screen.
        //
        // The require is inside the guard for the same reason. ahgCorePlugin is a
        // declared hard dependency and cannot be disabled, so it should always be
        // there - but an unguarded require fails after headers are sent, and the
        // caller gets HTTP 200 with an empty body and nothing in the log. The
        // catch below only ever protected the call, never the include.
        $this->orderTypes = [];
        $taxonomyFile = dirname(__FILE__, 5).'/ahgCorePlugin/lib/Services/AhgTaxonomyService.php';

        if (file_exists($taxonomyFile)) {
            require_once $taxonomyFile;

            try {
                $taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
                $this->orderTypes = $taxonomyService->getTermsWithAttributes('library_order_type');
            } catch (\Throwable $e) {
                $this->orderTypes = [];
            }
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

        // Handle POST - save
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
