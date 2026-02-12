<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Order Confirmation Action - Shows order details after payment
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartOrderConfirmationAction extends AhgController
{
    public function execute($request)
    {
        $orderNumber = $request->getParameter('order');
        $ecommerceService = new EcommerceService();
        
        $this->order = $ecommerceService->getOrderByNumber($orderNumber);
        
        if (!$this->order) {
            $this->getUser()->setFlash('error', 'Order not found.');
            $this->redirect(['module' => 'cart', 'action' => 'browse']);
            return;
        }
        
        // Verify access (user or session)
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            if ($this->order->user_id && $this->order->user_id != $userId) {
                $this->getUser()->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'cart', 'action' => 'browse']);
                return;
            }
        } else {
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
            if ($this->order->session_id && $this->order->session_id != $sessionId) {
                $this->getUser()->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'cart', 'action' => 'browse']);
                return;
            }
        }
        
        $this->items = $ecommerceService->getOrderItems($this->order->id);
    }
}
