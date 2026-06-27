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

        // Verify ownership — deny by default (#180). Closes the prior
        // null-conditional bypass (guest orders leaked to any authed user,
        // account orders to any anonymous visitor).
        $userId = $this->getUser()->isAuthenticated() ? (int) $this->getUser()->getAttribute('user_id') : null;
        $sessionId = session_id();
        if (empty($sessionId)) {
            @session_start();
            $sessionId = session_id();
        }
        if (!$ecommerceService->viewerOwnsOrder($this->order, $userId, $this->getUser()->isAdministrator(), $sessionId)) {
            $this->getUser()->setFlash('error', 'Access denied.');
            $this->redirect(['module' => 'cart', 'action' => 'browse']);
            return;
        }

        $this->items = $ecommerceService->getOrderItems($this->order->id);
    }
}
