<?php
class ahgVendorPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Vendor and supplier management';
    public static $version = '1.0.0';
    
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'vendor';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
    
    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('vendor');

        // IMPORTANT: Add generic :slug routes FIRST so they have LOWEST priority
        // prependRoute adds to front, so last added = highest priority
        $router->any('ahg_vend_view', '/vendor/:slug', 'view');
        $router->any('ahg_vend_edit', '/vendor/:slug/edit', 'edit');
        $router->any('ahg_vend_delete', '/vendor/:slug/delete', 'delete');

        // Contact routes
        $router->any('ahg_vend_contact_add', '/vendor/:slug/contact/add', 'addContact');
        $router->any('ahg_vend_contact_delete', '/vendor/:slug/contact/:contact_id/delete', 'deleteContact', ['contact_id' => '\d+']);

        // Transaction routes with :id parameter
        $router->any('ahg_vend_transaction_view', '/vendor/transaction/:id', 'viewTransaction', ['id' => '\d+']);
        $router->any('ahg_vend_transaction_edit', '/vendor/transaction/:id/edit', 'editTransaction', ['id' => '\d+']);
        $router->any('ahg_vend_transaction_status', '/vendor/transaction/:id/status', 'updateTransactionStatus', ['id' => '\d+']);
        $router->any('ahg_vend_transaction_item_add', '/vendor/transaction/:id/item/add', 'addTransactionItem', ['id' => '\d+']);
        $router->any('ahg_vend_transaction_item_remove', '/vendor/transaction/:transaction_id/item/:item_id/remove', 'removeTransactionItem', ['transaction_id' => '\d+', 'item_id' => '\d+']);

        // Specific routes LAST so they have HIGHEST priority
        $router->any('ahg_vend_transaction_add', '/vendor/transaction/add', 'addTransaction');
        $router->any('ahg_vend_transactions', '/vendor/transactions', 'transactions');
        $router->any('ahg_vend_service_types', '/vendor/serviceTypes', 'serviceTypes');
        $router->any('ahg_vend_add', '/vendor/add', 'add');
        $router->any('ahg_vend_list', '/vendor/list', 'list');
        $router->any('ahg_vend_index', '/vendor', 'index');

        $router->register($event->getSubject());
    }
}
