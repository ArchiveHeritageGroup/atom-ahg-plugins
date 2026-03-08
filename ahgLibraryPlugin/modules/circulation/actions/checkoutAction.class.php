<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Process a checkout (POST).
 *
 * Expects: patron_barcode, item_barcode
 * Redirects back to circulation/index with flash message.
 */
class circulationCheckoutAction extends AhgController
{
    public function execute($request)
    {
        
        // POST only
        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'circulation', 'action' => 'index']);
        }

        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load CirculationService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Services/CirculationService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $patronBarcode = trim($request->getParameter('patron_barcode', ''));
        $itemBarcode = trim($request->getParameter('item_barcode', ''));

        if (empty($patronBarcode) || empty($itemBarcode)) {
            $this->getUser()->setFlash('error', __('Both patron barcode and item barcode are required.'));
            $this->redirect(['module' => 'circulation', 'action' => 'index']);
        }

        try {
            if (!class_exists('CirculationService')) {
                throw new \RuntimeException('CirculationService not available. Please install the circulation tables first.');
            }

            $service = CirculationService::getInstance();
            $result = $service->checkout($patronBarcode, $itemBarcode);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', $result['message'] ?? __('Item checked out successfully.'));
            } else {
                $this->getUser()->setFlash('error', $result['message'] ?? __('Checkout failed.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Checkout error: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'circulation', 'action' => 'index', 'patron_barcode' => $patronBarcode]);
    }
}
