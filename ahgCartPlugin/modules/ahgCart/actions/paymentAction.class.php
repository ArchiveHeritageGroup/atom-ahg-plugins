<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Payment Action - Initiates payment with gateway
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartPaymentAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $orderNumber = $request->getParameter('order');
        $ecommerceService = new EcommerceService();

        $this->order = $ecommerceService->getOrderByNumber($orderNumber);
        
        if (!$this->order) {
            $this->context->user->setFlash('error', __('Order not found.'));
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        // Verify order belongs to user
        $userId = $this->context->user->getAttribute('user_id');
        if ($this->order->user_id != $userId) {
            $this->context->user->setFlash('error', __('Access denied.'));
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        // Check order status
        if ($this->order->status !== 'pending') {
            $this->context->user->setFlash('error', __('This order has already been processed.'));
            $this->redirect(['module' => 'ahgCart', 'action' => 'orderConfirmation', 'order' => $orderNumber]);
            return;
        }

        $this->items = $ecommerceService->getOrderItems($this->order->id);
        $this->settings = $ecommerceService->getSettings($this->order->repository_id);

        // Get PayFast payment data
        $this->paymentData = $ecommerceService->initiatePayFastPayment($this->order->id);

        if (!$this->paymentData['success']) {
            $this->context->user->setFlash('error', $this->paymentData['message']);
            return sfView::SUCCESS;
        }
    }
}
