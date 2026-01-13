<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;

/**
 * Clear Cart Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartClearAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        $userId = $this->context->user->getAttribute('user_id');
        $service = new CartService();

        $result = $service->clearAll($userId);

        $this->context->user->setFlash('notice', $result['message']);
        $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
    }
}
