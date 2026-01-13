<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;

/**
 * Browse Cart Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartBrowseAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $userId = $this->context->user->getAttribute('user_id');
        $service = new CartService();

        $this->items = $service->getUserCart($userId);
        $this->count = count($this->items);
    }
}
