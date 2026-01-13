<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Checkout Action
 * Handles both Standard (Request to Publish) and E-Commerce checkout
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartCheckoutAction extends sfAction
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

        // Check mode
        $this->ecommerceEnabled = $ecommerceService->isEcommerceEnabled();
        $this->settings = $ecommerceService->getSettings();

        // Get cart items
        if ($this->ecommerceEnabled) {
            $this->items = $ecommerceService->getCartWithPricing($userId);
            $this->totals = $ecommerceService->calculateCartTotals($this->items);
        } else {
            $this->items = $cartService->getUserCart($userId);
            $this->totals = null;
        }

        $this->count = count($this->items);

        if ($this->count === 0) {
            $this->context->user->setFlash('error', __('Your cart is empty.'));
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        // Get user details
        $this->user = DB::table('user')->where('id', $userId)->first();

        // Handle form submission
        if ($request->isMethod('post')) {
            if ($this->ecommerceEnabled) {
                $this->processEcommerceCheckout($request, $userId, $ecommerceService);
            } else {
                $this->processStandardCheckout($request, $userId, $cartService);
            }
        }
    }

    /**
     * Process Standard checkout (Request to Publish)
     */
    protected function processStandardCheckout($request, $userId, $cartService)
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
            $this->context->user->setFlash('error', __('Please fill in all required fields.'));
            return sfView::SUCCESS;
        }

        $createdIds = [];

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
                'unique_identifier' => (string) $userId,
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

        // Clear cart
        $cartService->clearAll($userId);

        $this->context->user->setFlash('notice', sprintf(__('Your request to publish has been submitted for %d item(s).'), count($createdIds)));
        $this->redirect(['module' => 'requestToPublish', 'action' => 'browse']);
    }

    /**
     * Process E-Commerce checkout
     */
    protected function processEcommerceCheckout($request, $userId, $ecommerceService)
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
            $this->context->user->setFlash('error', __('Please fill in all required fields.'));
            return sfView::SUCCESS;
        }

        // Check if all items have product types selected
        $hasUnselectedProducts = false;
        foreach ($this->items as $item) {
            if (!$item->product_type_id) {
                $hasUnselectedProducts = true;
                break;
            }
        }

        if ($hasUnselectedProducts) {
            $this->context->user->setFlash('error', __('Please select a product type for all items.'));
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        // Create order
        $result = $ecommerceService->createOrderFromCart($userId, $customerData);

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

            $this->context->user->setFlash('notice', __('Your order has been processed. You can download your items from the order details page.'));
            $this->redirect(['module' => 'ahgCart', 'action' => 'orderConfirmation', 'order' => $result['order_number']]);
            return;
        }

        // Redirect to payment
        $this->redirect(['module' => 'ahgCart', 'action' => 'payment', 'order' => $result['order_number']]);
    }
}
