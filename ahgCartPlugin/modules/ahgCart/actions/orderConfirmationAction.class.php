<?php
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Order Confirmation Action - Shows order details after payment
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartOrderConfirmationAction extends sfAction
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
        
        // Verify access (user or session)
        if ($this->context->user->isAuthenticated()) {
            $userId = $this->context->user->getAttribute('user_id');
            if ($this->order->user_id && $this->order->user_id != $userId) {
                $this->context->user->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
                return;
            }
        } else {
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
            if ($this->order->session_id && $this->order->session_id != $sessionId) {
                $this->context->user->setFlash('error', 'Access denied.');
                $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
                return;
            }
        }
        
        $this->items = $ecommerceService->getOrderItems($this->order->id);
    }
}
