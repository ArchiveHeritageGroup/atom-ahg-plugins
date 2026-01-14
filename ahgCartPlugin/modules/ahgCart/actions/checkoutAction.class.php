<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Checkout Action
 * Handles both Standard (Request to Publish) and E-Commerce checkout
 * Supports both logged-in users and guests
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartCheckoutAction extends sfAction
{
    public function execute($request)
    {
        $cartService = new CartService();
        $ecommerceService = new EcommerceService();

        // Get user ID or session ID
        $userId = null;
        $sessionId = null;
        $this->isGuest = true;

        if ($this->context->user->isAuthenticated()) {
            $userId = $this->context->user->getAttribute('user_id');
            $this->isGuest = false;
        } else {
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
        }

        // Check mode
        $this->ecommerceEnabled = $ecommerceService->isEcommerceEnabled();
        $this->settings = $ecommerceService->getSettings();

        // Process product selections from browse page (sent as POST)
        if ($this->ecommerceEnabled && $request->getParameter('selections')) {
            $this->processSelections($request->getParameter('selections'), $userId, $sessionId);
        }

        // Get cart items
        if ($this->ecommerceEnabled) {
            $this->items = $ecommerceService->getCartWithPricing($userId, null, $sessionId);
            $this->totals = $ecommerceService->calculateCartTotals($this->items);
        } else {
            $this->items = $cartService->getCart($userId, $sessionId);
            $this->totals = null;
        }

        $this->count = count($this->items);

        if ($this->count === 0) {
            $this->context->user->setFlash('error', 'Your cart is empty.');
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        // Check if any items have no product selected (e-commerce mode)
        if ($this->ecommerceEnabled) {
            $hasUnselected = false;
            foreach ($this->items as $item) {
                if (!$item->product_type_id) {
                    $hasUnselected = true;
                    break;
                }
            }
            if ($hasUnselected) {
                $this->context->user->setFlash('error', 'Please select a product type for all items in your cart.');
                $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
                return;
            }
        }

        // Get user details if logged in
        if ($userId) {
            $this->user = DB::table('user')->where('id', $userId)->first();
        } else {
            $this->user = null;
        }

        // Handle billing form submission
        if ($request->isMethod('post') && ($request->getParameter('billing_name') || $request->getParameter('rtp_name'))) {
            if ($this->ecommerceEnabled) {
                $this->processEcommerceCheckout($request, $userId, $sessionId, $ecommerceService);
            } else {
                $this->processStandardCheckout($request, $userId, $sessionId, $cartService);
            }
        }
    }

    /**
     * Process product selections from browse page and update cart
     */
    protected function processSelections($selectionsJson, $userId, $sessionId)
    {
        $selections = json_decode($selectionsJson, true);
        if (empty($selections) || !is_array($selections)) {
            return;
        }

        foreach ($selections as $cartId => $products) {
            if (empty($products) || !is_array($products)) {
                continue;
            }

            // Verify cart item belongs to user or session
            $cartItem = DB::table('cart')->where('id', $cartId)->first();
            if (!$cartItem) {
                continue;
            }
            
            // Check ownership
            if ($userId && $cartItem->user_id != $userId) {
                continue;
            }
            if (!$userId && $cartItem->session_id != $sessionId) {
                continue;
            }

            // For now, take the first selected product
            $firstProduct = $products[0];
            $productTypeId = intval($firstProduct['id']);
            $unitPrice = floatval($firstProduct['price']);

            // Update cart item with selected product
            DB::table('cart')
                ->where('id', $cartId)
                ->update([
                    'product_type_id' => $productTypeId,
                    'unit_price' => $unitPrice,
                    'quantity' => count($products) > 1 ? count($products) : 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // If multiple products selected for same image, create additional cart items
            if (count($products) > 1) {
                for ($i = 1; $i < count($products); $i++) {
                    $product = $products[$i];
                    DB::table('cart')->insert([
                        'user_id' => $userId,
                        'session_id' => $sessionId,
                        'archival_description_id' => $cartItem->archival_description_id,
                        'archival_description' => $cartItem->archival_description,
                        'slug' => $cartItem->slug,
                        'product_type_id' => intval($product['id']),
                        'unit_price' => floatval($product['price']),
                        'quantity' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    /**
     * Process Standard checkout (Request to Publish) - supports guests
     */
    protected function processStandardCheckout($request, $userId, $sessionId, $cartService)
    {
        $rtp_name = $request->getParameter('rtp_name');
        $rtp_surname = $request->getParameter('rtp_surname');
        $rtp_phone = $request->getParameter('rtp_phone');
        $rtp_email = $request->getParameter('rtp_email');
        $rtp_institution = $request->getParameter('rtp_institution');
        $rtp_planned_use = $request->getParameter('rtp_planned_use');
        $rtp_motivation = $request->getParameter('rtp_motivation');
        $rtp_need_image_by = $request->getParameter('rtp_need_image_by');

        // Validation
        if (empty($rtp_name) || empty($rtp_surname) || empty($rtp_email) || empty($rtp_phone) || empty($rtp_institution) || empty($rtp_planned_use)) {
            $this->context->user->setFlash('error', 'Please fill in all required fields.');
            return sfView::SUCCESS;
        }

        $createdIds = [];
        
        // Use email as unique identifier for guests
        $uniqueIdentifier = $userId ? (string) $userId : 'guest:' . $rtp_email;

        foreach ($this->items as $item) {
            // Create object record
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitRequestToPublish',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Create request_to_publish record
            DB::table('request_to_publish')->insert([
                'id' => $objectId,
                'parent_id' => null,
                'rtp_type_id' => null,
                'lft' => 0,
                'rgt' => 1,
                'source_culture' => 'en',
            ]);

            // Create i18n record
            DB::table('request_to_publish_i18n')->insert([
                'id' => $objectId,
                'unique_identifier' => $uniqueIdentifier,
                'object_id' => (string) $item->archival_description_id,
                'rtp_name' => $rtp_name,
                'rtp_surname' => $rtp_surname,
                'rtp_phone' => $rtp_phone,
                'rtp_email' => $rtp_email,
                'rtp_institution' => $rtp_institution,
                'rtp_motivation' => $rtp_motivation,
                'rtp_planned_use' => $rtp_planned_use,
                'rtp_need_image_by' => $rtp_need_image_by ? $rtp_need_image_by . ' 00:00:00' : null,
                'status_id' => 220,
                'created_at' => date('Y-m-d H:i:s'),
                'culture' => 'en',
            ]);

            $createdIds[] = $objectId;
        }

        // Clear cart (by user_id or session_id)
        if ($userId) {
            $cartService->clearAll($userId);
        } else {
            $cartService->clearAllBySession($sessionId);
        }

        $this->context->user->setFlash('notice', sprintf('Your request to publish has been submitted for %d item(s). A confirmation will be sent to %s.', count($createdIds), $rtp_email));
        
        // Guests go to a thank you page, logged in users go to browse
        if ($userId) {
            $this->redirect(['module' => 'requestToPublish', 'action' => 'browse']);
        } else {
            $this->redirect(['module' => 'ahgCart', 'action' => 'thankYou']);
        }
    }

    /**
     * Process E-Commerce checkout - supports guests
     */
    protected function processEcommerceCheckout($request, $userId, $sessionId, $ecommerceService)
    {
        $customerData = [
            'name' => trim($request->getParameter('billing_name', '')),
            'email' => trim($request->getParameter('billing_email', '')),
            'phone' => trim($request->getParameter('billing_phone', '')),
            'billing_address' => trim($request->getParameter('billing_address', '')),
            'shipping_address' => trim($request->getParameter('shipping_address', '')),
            'notes' => trim($request->getParameter('notes', '')),
        ];

        // Validation
        if (empty($customerData['name']) || empty($customerData['email'])) {
            $this->context->user->setFlash('error', 'Please fill in all required fields.');
            return sfView::SUCCESS;
        }

        // Create order (pass session_id for guests)
        $result = $ecommerceService->createOrderFromCart($userId, $customerData, $sessionId, null);

        if (!$result['success']) {
            $this->context->user->setFlash('error', $result['message']);
            return sfView::SUCCESS;
        }

        // Check if total is 0 (free items like Research Use)
        if ($result['total'] == 0) {
            // Mark order as completed immediately
            DB::table('ahg_order')
                ->where('id', $result['order_id'])
                ->update([
                    'status' => 'completed',
                    'paid_at' => date('Y-m-d H:i:s'),
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);

            // Generate download tokens
            $ecommerceService->generateDownloadTokens($result['order_id']);

            $this->context->user->setFlash('notice', 'Your order has been processed. You can download your items from the order details page.');
            $this->redirect(['module' => 'ahgCart', 'action' => 'orderConfirmation', 'order' => $result['order_number']]);
            return;
        }

        // Redirect to payment
        $this->redirect(['module' => 'ahgCart', 'action' => 'payment', 'order' => $result['order_number']]);
    }
}
