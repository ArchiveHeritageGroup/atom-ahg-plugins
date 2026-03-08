<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Process a renewal (POST).
 *
 * Expects: item_barcode (and optionally patron_barcode for redirect context)
 * Redirects back to circulation/index with flash message.
 */
class circulationRenewAction extends AhgController
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

        $itemBarcode = trim($request->getParameter('item_barcode', ''));
        $patronBarcode = trim($request->getParameter('patron_barcode', ''));

        if (empty($itemBarcode)) {
            $this->getUser()->setFlash('error', __('Item barcode is required for renewal.'));
            $this->redirect(['module' => 'circulation', 'action' => 'index']);
        }

        try {
            if (!class_exists('CirculationService')) {
                throw new \RuntimeException('CirculationService not available. Please install the circulation tables first.');
            }

            $service = CirculationService::getInstance();
            $result = $service->renew($itemBarcode);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', $result['message'] ?? __('Item renewed successfully.'));
            } else {
                $this->getUser()->setFlash('error', $result['message'] ?? __('Renewal failed.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Renewal error: %1%', ['%1%' => $e->getMessage()]));
        }

        $redirectParams = ['module' => 'circulation', 'action' => 'index'];
        if (!empty($patronBarcode)) {
            $redirectParams['patron_barcode'] = $patronBarcode;
        }
        $this->redirect($redirectParams);
    }
}
