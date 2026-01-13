<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;

/**
 * Remove from Cart Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartRemoveAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $id = $request->getParameter('id');
        $returnUrl = $request->getReferer();

        $userId = $this->context->user->getAttribute('user_id');
        $service = new CartService();

        $result = $service->removeFromCart($userId, (int) $id);

        $this->context->user->setFlash($result['success'] ? 'notice' : 'error', $result['message']);

        if ($returnUrl) {
            $this->redirect($returnUrl);
        } else {
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
        }
    }
}
