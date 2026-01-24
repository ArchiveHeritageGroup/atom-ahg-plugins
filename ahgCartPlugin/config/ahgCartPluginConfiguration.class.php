<?php

/**
 * ahgCartPlugin configuration
 * Shopping cart with dual-mode support: Standard (Request to Publish) + E-Commerce
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Shopping cart for reproduction requests and e-commerce';
    public static $version = '2.0.0';

    public function initialize()
    {
        // Connect to routing event
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        
        // Connect to context load for Laravel initialization
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
    }

    public function contextLoadFactories(sfEvent $event)
    {
        // Initialize Laravel database if needed
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        $bootstrapFile = $frameworkPath . '/bootstrap.php';
        
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Cart Browse - override both /cart and /cart/browse
        $routing->prependRoute('ahg_cart_browse', new sfRoute(
            '/cart',
            ['module' => 'cart', 'action' => 'browse']
        ));
        $routing->prependRoute('ahg_cart_browse_legacy', new sfRoute(
            '/cart/browse',
            ['module' => 'cart', 'action' => 'browse']
        ));

        // Cart Add
        $routing->prependRoute('ahg_cart_add', new sfRoute(
            '/cart/add/:slug',
            ['module' => 'cart', 'action' => 'add'],
            ['slug' => '[^/]+']
        ));

        // Cart Remove
        $routing->prependRoute('ahg_cart_remove', new sfRoute(
            '/cart/remove/:id',
            ['module' => 'cart', 'action' => 'remove'],
            ['id' => '\d+']
        ));

        // Cart Clear
        $routing->prependRoute('ahg_cart_clear', new sfRoute(
            '/cart/clear',
            ['module' => 'cart', 'action' => 'clear']
        ));

        // Checkout
        $routing->prependRoute('ahg_cart_thank_you', new sfRoute(
            '/cart/thank-you',
            ['module' => 'cart', 'action' => 'thankYou']
        ));
        $routing->prependRoute('ahg_cart_checkout', new sfRoute(
            '/cart/checkout',
            ['module' => 'cart', 'action' => 'checkout']
        ));

        // Update Products (E-Commerce)
        $routing->prependRoute('ahg_cart_update_products', new sfRoute(
            '/cart/update-products',
            ['module' => 'cart', 'action' => 'updateProducts']
        ));

        // Update single cart item (AJAX)
        $routing->prependRoute('ahg_cart_update_item', new sfRoute(
            '/cart/update-item',
            ['module' => 'cart', 'action' => 'updateItem']
        ));

        // Save product selections (AJAX)
        $routing->prependRoute('ahg_cart_save_selections', new sfRoute(
            '/cart/save-selections',
            ['module' => 'cart', 'action' => 'saveSelections']
        ));

        // Payment Page (E-Commerce)
        $routing->prependRoute('ahg_cart_payment_return', new sfRoute(
            '/cart/payment-return/:order',
            ['module' => 'cart', 'action' => 'paymentReturn']
        ));
        $routing->prependRoute('ahg_cart_payment_cancel', new sfRoute(
            '/cart/payment-cancel/:order',
            ['module' => 'cart', 'action' => 'paymentCancel']
        ));
        $routing->prependRoute('ahg_cart_order_confirmation', new sfRoute(
            '/cart/order/:order',
            ['module' => 'cart', 'action' => 'orderConfirmation']
        ));
        $routing->prependRoute('ahg_cart_payment', new sfRoute(
            '/cart/payment/:order',
            ['module' => 'cart', 'action' => 'payment'],
            ['order' => '[A-Z0-9\-]+']
        ));

        // Payment Success Callback
        $routing->prependRoute('ahg_cart_payment_success', new sfRoute(
            '/cart/payment/success/:order',
            ['module' => 'cart', 'action' => 'paymentSuccess'],
            ['order' => '[A-Z0-9\-]+']
        ));

        // Payment Cancel Callback
        $routing->prependRoute('ahg_cart_payment_cancel', new sfRoute(
            '/cart/payment/cancel/:order',
            ['module' => 'cart', 'action' => 'paymentCancel'],
            ['order' => '[A-Z0-9\-]+']
        ));

        // Payment Notification (ITN)
        $routing->prependRoute('ahg_cart_payment_notify', new sfRoute(
            '/cart/payment/notify',
            ['module' => 'cart', 'action' => 'paymentNotify']
        ));

        // Order Confirmation
        $routing->prependRoute('ahg_cart_order_confirmation', new sfRoute(
            '/cart/order/:order',
            ['module' => 'cart', 'action' => 'orderConfirmation'],
            ['order' => '[A-Z0-9\-]+']
        ));

        // My Orders
        $routing->prependRoute('ahg_cart_orders', new sfRoute(
            '/cart/orders',
            ['module' => 'cart', 'action' => 'orders']
        ));

        // Download (for digital products)
        $routing->prependRoute('ahg_cart_download', new sfRoute(
            '/cart/download/:token',
            ['module' => 'cart', 'action' => 'download'],
            ['token' => '[a-f0-9]+']
        ));

        // Admin: E-Commerce Settings
        $routing->prependRoute('ahg_cart_admin_settings', new sfRoute(
            '/admin/ecommerce',
            ['module' => 'cart', 'action' => 'adminSettings']
        ));

        // Admin: Orders List
        $routing->prependRoute('ahg_cart_admin_orders', new sfRoute(
            '/admin/orders',
            ['module' => 'cart', 'action' => 'adminOrders']
        ));

        // Admin: Order Detail
        $routing->prependRoute('ahg_cart_admin_order_detail', new sfRoute(
            '/admin/orders/:id',
            ['module' => 'cart', 'action' => 'adminOrderDetail'],
            ['id' => '\d+']
        ));

        // Admin: Product Pricing
        $routing->prependRoute('ahg_cart_admin_pricing', new sfRoute(
            '/admin/pricing',
            ['module' => 'cart', 'action' => 'adminPricing']
        ));
    }
}
