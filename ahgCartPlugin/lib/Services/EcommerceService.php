<?php

namespace AtomAhgPlugins\ahgCartPlugin\Services;

require_once dirname(__DIR__) . '/Repositories/EcommerceRepository.php';
require_once dirname(__DIR__) . '/Repositories/CartRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Repositories\EcommerceRepository;
use AtomAhgPlugins\ahgCartPlugin\Repositories\CartRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * E-Commerce Service - Business logic for orders, payments, checkout
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class EcommerceService
{
    private EcommerceRepository $ecommerceRepo;
    private CartRepository $cartRepo;

    public function __construct()
    {
        $this->ecommerceRepo = new EcommerceRepository();
        $this->cartRepo = new CartRepository();
    }

    // ========================================================================
    // MODE DETECTION
    // ========================================================================

    /**
     * Check if e-commerce is enabled for a repository
     */
    public function isEcommerceEnabled(?int $repositoryId = null): bool
    {
        return $this->ecommerceRepo->isEcommerceEnabled($repositoryId);
    }

    /**
     * Get e-commerce settings
     */
    public function getSettings(?int $repositoryId = null): ?object
    {
        return $this->ecommerceRepo->getSettings($repositoryId);
    }

    // ========================================================================
    // CART WITH PRICING
    // ========================================================================

    /**
     * Get cart items with pricing information
     */
    public function getCartWithPricing($userId = null, ?int $repositoryId = null, $sessionId = null): array
    {
        $items = $this->cartRepo->getCart($userId, $sessionId);
        $result = [];
        $settings = $this->getSettings($repositoryId);
        $vatRate = $settings->vat_rate ?? 15.00;

        foreach ($items as $item) {
            $title = DB::table('information_object_i18n')
                ->where('id', $item->archival_description_id)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('title');

            $slug = DB::table('slug')
                ->where('object_id', $item->archival_description_id)
                ->value('slug');

            $hasDigitalObject = DB::table('digital_object')
                ->where('object_id', $item->archival_description_id)
                ->exists();

            // Get pricing if product_type_id is set
            $pricing = null;
            if ($item->product_type_id) {
                $pricing = $this->ecommerceRepo->getPrice($item->product_type_id, $repositoryId);
            }

            $unitPrice = $item->unit_price ?? ($pricing->price ?? 0);
            $quantity = $item->quantity ?? 1;
            $lineTotal = $unitPrice * $quantity;

            $result[] = (object) [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'archival_description_id' => $item->archival_description_id,
                'title' => $title ?? $item->archival_description ?? 'Untitled',
                'slug' => $slug ?? $item->slug,
                'has_digital_object' => $hasDigitalObject,
                'product_type_id' => $item->product_type_id,
                'product_name' => $pricing->name ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'notes' => $item->notes,
                'created_at' => $item->created_at,
            ];
        }

        return $result;
    }

    /**
     * Calculate cart totals
     */
    public function calculateCartTotals(array $items, ?int $repositoryId = null): array
    {
        $settings = $this->getSettings($repositoryId);
        $vatRate = $settings->vat_rate ?? 15.00;
        $priceIncludesVat = true; // Default assumption

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item->line_total;
        }

        // Calculate VAT
        if ($priceIncludesVat) {
            // VAT is included in prices, extract it
            $vatAmount = $subtotal - ($subtotal / (1 + ($vatRate / 100)));
            $netAmount = $subtotal - $vatAmount;
        } else {
            // VAT not included, add it
            $netAmount = $subtotal;
            $vatAmount = $subtotal * ($vatRate / 100);
            $subtotal = $netAmount + $vatAmount;
        }

        return [
            'item_count' => count($items),
            'net_amount' => round($netAmount, 2),
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'currency' => $settings->currency ?? 'ZAR',
        ];
    }

    /**
     * Update cart item with product selection
     */
    public function updateCartItemProduct(int $cartId, int $productTypeId, ?int $repositoryId = null): array
    {
        $pricing = $this->ecommerceRepo->getPrice($productTypeId, $repositoryId);
        
        if (!$pricing) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        DB::table('cart')
            ->where('id', $cartId)
            ->update([
                'product_type_id' => $productTypeId,
                'unit_price' => $pricing->price,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return ['success' => true, 'message' => 'Product updated.', 'price' => $pricing->price];
    }

    // ========================================================================
    // CHECKOUT & ORDER CREATION
    // ========================================================================

    /**
     * Create order from cart (E-Commerce mode)
     */
    public function createOrderFromCart(?int $userId, array $customerData, ?string $sessionId = null, ?int $repositoryId = null): array
    {
        $items = $this->getCartWithPricing($userId, $repositoryId, $sessionId);
        
        if (empty($items)) {
            return ['success' => false, 'message' => 'Cart is empty.'];
        }

        $totals = $this->calculateCartTotals($items, $repositoryId);

        // Create order
        $orderId = $this->ecommerceRepo->createOrder([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'repository_id' => $repositoryId,
            'status' => 'pending',
            'subtotal' => $totals['net_amount'],
            'vat_amount' => $totals['vat_amount'],
            'total' => $totals['total'],
            'currency' => $totals['currency'],
            'customer_name' => $customerData['name'] ?? null,
            'customer_email' => $customerData['email'] ?? null,
            'customer_phone' => $customerData['phone'] ?? null,
            'billing_address' => $customerData['billing_address'] ?? null,
            'shipping_address' => $customerData['shipping_address'] ?? null,
            'notes' => $customerData['notes'] ?? null,
        ]);

        // Add order items
        foreach ($items as $item) {
            $this->ecommerceRepo->addOrderItem([
                'order_id' => $orderId,
                'archival_description_id' => $item->archival_description_id,
                'archival_description' => $item->title,
                'slug' => $item->slug,
                'product_type_id' => $item->product_type_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]);
        }

        // Clear cart
        $this->cartRepo->clearByUserId($userId);

        $order = $this->ecommerceRepo->getOrder($orderId);

        return [
            'success' => true,
            'message' => 'Order created successfully.',
            'order_id' => $orderId,
            'order_number' => $order->order_number,
            'total' => $totals['total'],
        ];
    }

    // ========================================================================
    // PAYMENT PROCESSING
    // ========================================================================

    /**
     * Initiate PayFast payment
     */
    public function initiatePayFastPayment(int $orderId): array
    {
        $order = $this->ecommerceRepo->getOrder($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $settings = $this->getSettings($order->repository_id);
        if (!$settings || !$settings->payfast_merchant_id) {
            return ['success' => false, 'message' => 'PayFast not configured.'];
        }

        $sandbox = $settings->payfast_sandbox ?? true;
        $baseUrl = $sandbox 
            ? 'https://sandbox.payfast.co.za/eng/process'
            : 'https://www.payfast.co.za/eng/process';

        // Get site URL
        $siteUrl = \sfConfig::get('app_siteBaseUrl', 'https://psis.theahg.co.za');
        
        // Parse customer name
        $nameParts = explode(' ', trim($order->customer_name ?? ''));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        // Build PayFast data array - ORDER MATTERS for signature!
        $pfData = [
            'merchant_id' => trim($settings->payfast_merchant_id),
            'merchant_key' => trim($settings->payfast_merchant_key),
            'return_url' => $siteUrl . '/index.php/cart/order/' . $order->order_number,
            'cancel_url' => $siteUrl . '/index.php/cart',
            'notify_url' => $siteUrl . '/index.php/cart/payment/notify',
        ];
        
        // Add optional buyer details
        if (!empty($firstName)) {
            $pfData['name_first'] = substr($firstName, 0, 100);
        }
        if (!empty($lastName)) {
            $pfData['name_last'] = substr($lastName, 0, 100);
        }
        if (!empty($order->customer_email)) {
            $pfData['email_address'] = trim($order->customer_email);
        }
        
        // Transaction details
        $pfData['m_payment_id'] = $order->order_number;
        $pfData['amount'] = number_format((float)$order->total, 2, '.', '');
        $pfData['item_name'] = 'Order-' . $order->order_number;

        // Generate signature string - do NOT urlencode for signature calculation
        $signatureString = '';
        foreach ($pfData as $key => $val) {
            if ($val !== null && $val !== '') {
                $signatureString .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        // Remove trailing &
        $signatureString = rtrim($signatureString, '&');
        
        // Add passphrase if set (for sandbox, leave empty)
        $passphrase = trim($settings->payfast_passphrase ?? '');
        if (!empty($passphrase)) {
            $signatureString .= '&passphrase=' . urlencode($passphrase);
        }
        
        // Generate MD5 signature
        error_log('PayFast Signature String: ' . $signatureString);
        $pfData['signature'] = md5($signatureString);
        error_log('PayFast Signature: ' . $pfData['signature']);

        // Create payment record
        $this->ecommerceRepo->createPayment([
            'order_id' => $orderId,
            'payment_gateway' => 'payfast',
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        return [
            'success' => true,
            'payment_url' => $baseUrl,
            'payment_data' => $pfData,
            'method' => 'POST',
        ];
    }

    /**
     * Process PayFast ITN (Instant Transaction Notification)
     */
    public function processPayFastNotification(array $data): array
    {
        $orderNumber = $data['m_payment_id'] ?? null;
        if (!$orderNumber) {
            return ['success' => false, 'message' => 'Invalid notification.'];
        }

        $order = $this->ecommerceRepo->getOrderByNumber($orderNumber);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $paymentStatus = $data['payment_status'] ?? '';
        $transactionId = $data['pf_payment_id'] ?? null;

        if ($paymentStatus === 'COMPLETE') {
            // Update order
            $this->ecommerceRepo->updateOrderStatus($order->id, 'paid');

            // Update payment
            DB::table('ahg_payment')
                ->where('order_id', $order->id)
                ->where('payment_gateway', 'payfast')
                ->update([
                    'status' => 'completed',
                    'transaction_id' => $transactionId,
                    'gateway_response' => json_encode($data),
                    'paid_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Generate download tokens for digital items
            $this->generateDownloadTokens($order->id);

            return ['success' => true, 'message' => 'Payment processed.'];
        }

        return ['success' => false, 'message' => 'Payment not completed.'];
    }

    /**
     * Generate download tokens for paid order
     */
    public function generateDownloadTokens(int $orderId): array
    {
        $items = $this->ecommerceRepo->getOrderItems($orderId);
        $tokens = [];

        foreach ($items as $item) {
            $productType = $this->ecommerceRepo->getProductType($item->product_type_id);
            
            if ($productType && $productType->is_digital) {
                $token = $this->ecommerceRepo->createDownloadToken($item->id);
                $tokens[$item->id] = $token;

                // Update order item with download URL
                DB::table('ahg_order_item')
                    ->where('id', $item->id)
                    ->update([
                        'download_url' => '/cart/download/' . $token,
                        'download_expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                    ]);
            }
        }

        return $tokens;
    }

    // ========================================================================
    // ORDER MANAGEMENT
    // ========================================================================

    public function getOrder(int $orderId): ?object
    {
        return $this->ecommerceRepo->getOrder($orderId);
    }

    public function getOrderByNumber(string $orderNumber): ?object
    {
        return $this->ecommerceRepo->getOrderByNumber($orderNumber);
    }

    public function getUserOrders(int $userId): array
    {
        return $this->ecommerceRepo->getUserOrders($userId);
    }

    public function getOrderItems(int $orderId): array
    {
        return $this->ecommerceRepo->getOrderItems($orderId);
    }

    public function getProductTypes(): array
    {
        return $this->ecommerceRepo->getProductTypes();
    }

    public function getPricing(?int $repositoryId = null): array
    {
        return $this->ecommerceRepo->getPricing($repositoryId);
    }

    public function getAllPricing(?int $repositoryId = null): array
    {
        return $this->ecommerceRepo->getPricing($repositoryId);
    }
}