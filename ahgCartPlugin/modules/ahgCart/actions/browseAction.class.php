<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Cart Browse Action - Shows cart items
 * Supports both Standard (Request to Publish) and E-Commerce modes
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
        $cartService = new CartService();
        $ecommerceService = new EcommerceService();

        // Check if e-commerce is enabled
        $this->ecommerceEnabled = $ecommerceService->isEcommerceEnabled();
        $this->settings = $ecommerceService->getSettings();

        if ($this->ecommerceEnabled) {
            // E-Commerce mode - get items with pricing
            $this->items = $ecommerceService->getCartWithPricing($userId);
            $this->totals = $ecommerceService->calculateCartTotals($this->items);
            $this->productTypes = $ecommerceService->getProductTypes();
            $this->pricing = $ecommerceService->getPricing();
        } else {
            // Standard mode - simple cart
            $this->items = $cartService->getUserCart($userId);
            $this->totals = null;
            $this->productTypes = [];
            $this->pricing = [];
        }

        $this->count = count($this->items);
    }
}
