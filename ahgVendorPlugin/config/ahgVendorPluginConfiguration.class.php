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
        $routing = $event->getSubject();

        // IMPORTANT: Add generic :slug routes FIRST so they have LOWEST priority
        // prependRoute adds to front, so last added = highest priority
        $routing->prependRoute('ahg_vend_view', new sfRoute('/vendor/:slug', ['module' => 'vendor', 'action' => 'view']));
        $routing->prependRoute('ahg_vend_edit', new sfRoute('/vendor/:slug/edit', ['module' => 'vendor', 'action' => 'edit']));
        $routing->prependRoute('ahg_vend_delete', new sfRoute('/vendor/:slug/delete', ['module' => 'vendor', 'action' => 'delete']));

        // Contact routes
        $routing->prependRoute('ahg_vend_contact_add', new sfRoute('/vendor/:slug/contact/add', ['module' => 'vendor', 'action' => 'addContact']));
        $routing->prependRoute('ahg_vend_contact_delete', new sfRoute('/vendor/:slug/contact/:contact_id/delete', ['module' => 'vendor', 'action' => 'deleteContact'], ['contact_id' => '\d+']));

        // Transaction routes with :id parameter
        $routing->prependRoute('ahg_vend_transaction_view', new sfRoute('/vendor/transaction/:id', ['module' => 'vendor', 'action' => 'viewTransaction'], ['id' => '\d+']));
        $routing->prependRoute('ahg_vend_transaction_edit', new sfRoute('/vendor/transaction/:id/edit', ['module' => 'vendor', 'action' => 'editTransaction'], ['id' => '\d+']));
        $routing->prependRoute('ahg_vend_transaction_status', new sfRoute('/vendor/transaction/:id/status', ['module' => 'vendor', 'action' => 'updateTransactionStatus'], ['id' => '\d+']));
        $routing->prependRoute('ahg_vend_transaction_item_add', new sfRoute('/vendor/transaction/:id/item/add', ['module' => 'vendor', 'action' => 'addTransactionItem'], ['id' => '\d+']));
        $routing->prependRoute('ahg_vend_transaction_item_remove', new sfRoute('/vendor/transaction/:transaction_id/item/:item_id/remove', ['module' => 'vendor', 'action' => 'removeTransactionItem'], ['transaction_id' => '\d+', 'item_id' => '\d+']));

        // Specific routes LAST so they have HIGHEST priority
        $routing->prependRoute('ahg_vend_transaction_add', new sfRoute('/vendor/transaction/add', ['module' => 'vendor', 'action' => 'addTransaction']));
        $routing->prependRoute('ahg_vend_transactions', new sfRoute('/vendor/transactions', ['module' => 'vendor', 'action' => 'transactions']));
        $routing->prependRoute('ahg_vend_service_types', new sfRoute('/vendor/serviceTypes', ['module' => 'vendor', 'action' => 'serviceTypes']));
        $routing->prependRoute('ahg_vend_add', new sfRoute('/vendor/add', ['module' => 'vendor', 'action' => 'add']));
        $routing->prependRoute('ahg_vend_list', new sfRoute('/vendor/list', ['module' => 'vendor', 'action' => 'list']));
        $routing->prependRoute('ahg_vend_index', new sfRoute('/vendor', ['module' => 'vendor', 'action' => 'index']));
    }
}
