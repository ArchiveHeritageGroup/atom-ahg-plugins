<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;

/**
 * Clear Cart Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartClearAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'cart', 'action' => 'browse']);
            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new CartService();

        $result = $service->clearAll($userId);

        $this->getUser()->setFlash('notice', $result['message']);
        $this->redirect(['module' => 'cart', 'action' => 'browse']);
    }
}
