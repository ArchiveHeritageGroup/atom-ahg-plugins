<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * PayFast ITN (Instant Transaction Notification) Handler
 * This receives payment confirmations from PayFast
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartPaymentNotifyAction extends AhgController
{
    public function execute($request)
    {
        // Disable layout - this is an API endpoint
        $this->setLayout(false);
        sfConfig::set('sf_web_debug', false);
        
        // Get POST data via request object
        $pfData = $request->getPostParameters();

        if (empty($pfData)) {
            return $this->renderText('NO_DATA');
        }

        // Get the payment ID (order number)
        $orderNumber = $pfData['m_payment_id'] ?? null;
        if (!$orderNumber) {
            return $this->renderText('NO_ORDER');
        }

        $ecommerceService = new EcommerceService();

        // Process the notification
        $result = $ecommerceService->processPayFastNotification($pfData);

        if ($result['success']) {
            return $this->renderText('OK');
        } else {
            error_log('PayFast ITN failed for ' . $orderNumber . ': ' . ($result['message'] ?? 'Unknown error'));
            return $this->renderText('FAILED: ' . ($result['message'] ?? 'Unknown error'));
        }
    }
}
