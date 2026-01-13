<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Order Confirmation Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartOrderConfirmationAction extends sfAction
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

        $this->items = $ecommerceService->getOrderItems($this->order->id);
        $this->settings = $ecommerceService->getSettings($this->order->repository_id);
    }
}
