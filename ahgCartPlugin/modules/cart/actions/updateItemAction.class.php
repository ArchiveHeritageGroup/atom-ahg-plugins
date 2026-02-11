<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Repositories/EcommerceRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use AtomAhgPlugins\ahgCartPlugin\Repositories\EcommerceRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Update Cart Item Action (AJAX)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartUpdateItemAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $cartId = intval($request->getParameter('cart_id'));
        $productTypeId = intval($request->getParameter('product_type_id'));
        $quantity = max(1, intval($request->getParameter('quantity', 1)));

        // Verify cart item belongs to user
        $cartItem = DB::table('cart')->where('id', $cartId)->first();
        
        if (!$cartItem || $cartItem->user_id != $userId) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Item not found']));
        }

        // Get price for product type
        $ecommerceRepo = new EcommerceRepository();
        $pricing = $ecommerceRepo->getPrice($productTypeId, null);
        $unitPrice = $pricing ? $pricing->price : 0;

        // Update cart item
        DB::table('cart')
            ->where('id', $cartId)
            ->update([
                'product_type_id' => $productTypeId,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $lineTotal = $unitPrice * $quantity;

        return $this->renderText(json_encode([
            'success' => true,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ]));
    }
}
