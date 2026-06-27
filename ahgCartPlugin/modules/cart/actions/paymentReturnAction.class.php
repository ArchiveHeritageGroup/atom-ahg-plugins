<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Payment Return Action - User returns from PayFast after payment
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartPaymentReturnAction extends AhgController
{
    public function execute($request)
    {
        $orderNumber = $request->getParameter('order');
        $ecommerceService = new EcommerceService();
        
        $order = $ecommerceService->getOrderByNumber($orderNumber);

        if (!$order) {
            $this->getUser()->setFlash('error', 'Order not found.');
            $this->redirect(['module' => 'cart', 'action' => 'browse']);
            return;
        }

        // Verify ownership — deny by default (#180); don't act as an order
        // existence oracle for non-owners.
        $userId = $this->getUser()->isAuthenticated() ? (int) $this->getUser()->getAttribute('user_id') : null;
        $sessionId = session_id();
        if (empty($sessionId)) {
            @session_start();
            $sessionId = session_id();
        }
        if (!$ecommerceService->viewerOwnsOrder($order, $userId, $this->getUser()->isAdministrator(), $sessionId)) {
            $this->getUser()->setFlash('error', 'Access denied.');
            $this->redirect(['module' => 'cart', 'action' => 'browse']);
            return;
        }

        // Payment was successful (ITN will confirm)
        $this->getUser()->setFlash('notice', 'Thank you! Your payment is being processed.');
        $this->redirect(['module' => 'cart', 'action' => 'orderConfirmation', 'order' => $orderNumber]);
    }
}
