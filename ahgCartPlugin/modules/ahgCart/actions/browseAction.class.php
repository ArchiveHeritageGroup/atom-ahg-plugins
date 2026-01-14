<?php
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;

/**
 * Cart Browse Action - Supports both users and guests
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartBrowseAction extends sfAction
{
    public function execute($request)
    {
        $cartService = new CartService();
        $ecommerceService = new EcommerceService();
        
        // Get user ID or session ID
        $userId = null;
        $sessionId = null;
        
        if ($this->context->user->isAuthenticated()) {
            $userId = $this->context->user->getAttribute('user_id');
            
            // Merge any guest cart items
            $guestSessionId = session_id();
            if ($guestSessionId) {
                $merged = $cartService->mergeGuestCart($guestSessionId, $userId);
                if ($merged > 0) {
                    $this->context->user->setFlash('notice', sprintf('%d item(s) from your guest cart have been added.', $merged));
                }
            }
        } else {
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
            error_log("CART BROWSE DEBUG: Guest session_id = " . $sessionId);
        }
        
        // Check e-commerce mode
        $this->ecommerceEnabled = $ecommerceService->isEcommerceEnabled();
        $this->settings = $ecommerceService->getSettings();
        
        // Get cart items
        if ($this->ecommerceEnabled) {
            $this->items = $ecommerceService->getCartWithPricing($userId, null, $sessionId);
            $this->productTypes = $ecommerceService->getProductTypes();
            $this->pricing = $ecommerceService->getAllPricing();
        } else {
            $this->items = $cartService->getCart($userId, $sessionId);
            $this->productTypes = [];
            $this->pricing = [];
        }
        
        $this->count = count($this->items);
        $this->isGuest = !$this->context->user->isAuthenticated();
    }
}
