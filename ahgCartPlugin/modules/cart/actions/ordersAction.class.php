<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * My Orders Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartOrdersAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $ecommerceService = new EcommerceService();

        $this->orders = $ecommerceService->getUserOrders($userId);
        $this->ecommerceEnabled = $ecommerceService->isEcommerceEnabled();
    }
}
