<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Process a checkin / return (POST).
 *
 * Expects: item_barcode
 * Redirects back to circulation/index with flash message.
 */
class circulationCheckinAction extends AhgController
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

        if (empty($itemBarcode)) {
            $this->getUser()->setFlash('error', __('Item barcode is required for return.'));
            $this->redirect(['module' => 'circulation', 'action' => 'index']);
        }

        try {
            if (!class_exists('CirculationService')) {
                throw new \RuntimeException('CirculationService not available. Please install the circulation tables first.');
            }

            $service = CirculationService::getInstance();
            $result = $service->checkin($itemBarcode);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', $result['message'] ?? __('Item returned successfully.'));
            } else {
                $this->getUser()->setFlash('error', $result['message'] ?? __('Return failed.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Return error: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'circulation', 'action' => 'index']);
    }
}
