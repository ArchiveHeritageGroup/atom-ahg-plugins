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
        $router = new \AtomFramework\Routing\RouteLoader('cart');

        // Cart Browse - override both /cart and /cart/browse
        $router->any('ahg_cart_browse', '/cart', 'browse');
        $router->any('ahg_cart_browse_legacy', '/cart/browse', 'browse');

        // Cart Add
        $router->any('ahg_cart_add', '/cart/add/:slug', 'add', ['slug' => '[^/]+']);

        // Cart Remove
        $router->any('ahg_cart_remove', '/cart/remove/:id', 'remove', ['id' => '\d+']);

        // Cart Clear
        $router->any('ahg_cart_clear', '/cart/clear', 'clear');

        // Checkout
        $router->any('ahg_cart_thank_you', '/cart/thank-you', 'thankYou');
        $router->any('ahg_cart_checkout', '/cart/checkout', 'checkout');

        // Update Products (E-Commerce)
        $router->any('ahg_cart_update_products', '/cart/update-products', 'updateProducts');

        // Update single cart item (AJAX)
        $router->any('ahg_cart_update_item', '/cart/update-item', 'updateItem');

        // Save product selections (AJAX)
        $router->any('ahg_cart_save_selections', '/cart/save-selections', 'saveSelections');

        // Payment Page (E-Commerce)
        $router->any('ahg_cart_payment_return', '/cart/payment-return/:order', 'paymentReturn');
        $router->any('ahg_cart_payment', '/cart/payment/:order', 'payment', ['order' => '[A-Z0-9\-]+']);

        // Payment Success Callback
        $router->any('ahg_cart_payment_success', '/cart/payment/success/:order', 'paymentSuccess', ['order' => '[A-Z0-9\-]+']);

        // Payment Cancel Callback
        $router->any('ahg_cart_payment_cancel', '/cart/payment/cancel/:order', 'paymentCancel', ['order' => '[A-Z0-9\-]+']);

        // Payment Notification (ITN)
        $router->post('ahg_cart_payment_notify', '/cart/payment/notify', 'paymentNotify');

        // Order Confirmation
        $router->any('ahg_cart_order_confirmation', '/cart/order/:order', 'orderConfirmation', ['order' => '[A-Z0-9\-]+']);

        // My Orders
        $router->any('ahg_cart_orders', '/cart/orders', 'orders');

        // Download (for digital products)
        $router->any('ahg_cart_download', '/cart/download/:token', 'download', ['token' => '[a-f0-9]+']);

        // Admin: E-Commerce Settings
        $router->any('ahg_cart_admin_settings', '/admin/ecommerce', 'adminSettings');

        // Admin: Orders List
        $router->any('ahg_cart_admin_orders', '/admin/orders', 'adminOrders');

        // Admin: Order Detail
        $router->any('ahg_cart_admin_order_detail', '/admin/orders/:id', 'adminOrderDetail', ['id' => '\d+']);

        // Admin: Product Pricing
        $router->any('ahg_cart_admin_pricing', '/admin/pricing', 'adminPricing');

        $router->register($event->getSubject());
    }
}
