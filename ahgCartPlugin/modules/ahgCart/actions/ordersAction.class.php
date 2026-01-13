<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * My Orders Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartOrdersAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $userId = $this->context->user->getAttribute('user_id');
        $ecommerceService = new EcommerceService();

        $this->orders = $ecommerceService->getUserOrders($userId);
        $this->ecommerceEnabled = $ecommerceService->isEcommerceEnabled();
    }
}
