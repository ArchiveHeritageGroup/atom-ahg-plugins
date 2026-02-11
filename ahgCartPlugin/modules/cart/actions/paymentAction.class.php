<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Payment Action - Initiates payment with gateway
 * Supports both logged-in users and guests
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartPaymentAction extends AhgController
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
        
        // Verify order belongs to user or session
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            // Check if order belongs to this user
            if ($this->order->user_id && $this->order->user_id != $userId) {
                $this->getUser()->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'cart', 'action' => 'browse']);
                return;
            }
        } else {
            // Guest - verify by session
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
            // Check if order belongs to this session
            if ($this->order->session_id && $this->order->session_id != $sessionId) {
                $this->getUser()->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'cart', 'action' => 'browse']);
                return;
            }
        }
        
        // Check order status
        if ($this->order->status !== 'pending') {
            $this->getUser()->setFlash('error', 'This order has already been processed.');
            $this->redirect(['module' => 'cart', 'action' => 'orderConfirmation', 'order' => $orderNumber]);
            return;
        }
        
        $this->items = $ecommerceService->getOrderItems($this->order->id);
        $this->settings = $ecommerceService->getSettings($this->order->repository_id);
        
        // Use the EcommerceService method to initiate PayFast payment
        $this->paymentData = $ecommerceService->initiatePayFastPayment($this->order->id);
    }
}
