<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Payment Cancel Action - User cancelled payment at PayFast
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartPaymentCancelAction extends AhgController
{
    public function execute($request)
    {
        $orderNumber = $request->getParameter('order');
        
        $this->getUser()->setFlash('error', 'Payment was cancelled. Your order is still pending.');
        $this->redirect(['module' => 'cart', 'action' => 'payment', 'order' => $orderNumber]);
    }
}
