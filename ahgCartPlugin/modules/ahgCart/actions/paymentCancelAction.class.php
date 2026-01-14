<?php
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Payment Cancel Action - User cancelled payment at PayFast
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartPaymentCancelAction extends sfAction
{
    public function execute($request)
    {
        $orderNumber = $request->getParameter('order');
        
        $this->context->user->setFlash('error', 'Payment was cancelled. Your order is still pending.');
        $this->redirect(['module' => 'ahgCart', 'action' => 'payment', 'order' => $orderNumber]);
    }
}
