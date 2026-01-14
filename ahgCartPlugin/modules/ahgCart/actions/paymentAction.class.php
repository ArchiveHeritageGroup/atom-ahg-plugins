<?php
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Payment Action - Initiates payment with gateway
 * Supports both logged-in users and guests
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartPaymentAction extends sfAction
{
    public function execute($request)
    {
        $orderNumber = $request->getParameter('order');
        $ecommerceService = new EcommerceService();
        
        $this->order = $ecommerceService->getOrderByNumber($orderNumber);
        
        if (!$this->order) {
            $this->context->user->setFlash('error', 'Order not found.');
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }
        
        // Verify order belongs to user or session
        if ($this->context->user->isAuthenticated()) {
            $userId = $this->context->user->getAttribute('user_id');
            // Check if order belongs to this user
            if ($this->order->user_id && $this->order->user_id != $userId) {
                $this->context->user->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
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
                $this->context->user->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
                return;
            }
        }
        
        // Check order status
        if ($this->order->status !== 'pending') {
            $this->context->user->setFlash('error', 'This order has already been processed.');
            $this->redirect(['module' => 'ahgCart', 'action' => 'orderConfirmation', 'order' => $orderNumber]);
            return;
        }
        
        $this->items = $ecommerceService->getOrderItems($this->order->id);
        $this->settings = $ecommerceService->getSettings($this->order->repository_id);
        
        // Use the EcommerceService method to initiate PayFast payment
        $this->paymentData = $ecommerceService->initiatePayFastPayment($this->order->id);
    }
}
