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
        $routing->prependRoute('vendor_view', new sfRoute('/vendor/:slug', ['module' => 'vendor', 'action' => 'view']));
        $routing->prependRoute('vendor_edit', new sfRoute('/vendor/:slug/edit', ['module' => 'vendor', 'action' => 'edit']));
        $routing->prependRoute('vendor_delete', new sfRoute('/vendor/:slug/delete', ['module' => 'vendor', 'action' => 'delete']));
        
        // Transaction routes with :id parameter
        $routing->prependRoute('vendor_view_transaction', new sfRoute('/vendor/transaction/:id', ['module' => 'vendor', 'action' => 'viewTransaction'], ['id' => '\d+']));
        $routing->prependRoute('vendor_edit_transaction', new sfRoute('/vendor/transaction/:id/edit', ['module' => 'vendor', 'action' => 'editTransaction'], ['id' => '\d+']));
        
        // Specific routes LAST so they have HIGHEST priority
        $routing->prependRoute('vendor_add_transaction', new sfRoute('/vendor/transaction/add', ['module' => 'vendor', 'action' => 'addTransaction']));
        $routing->prependRoute('vendor_transactions', new sfRoute('/vendor/transactions', ['module' => 'vendor', 'action' => 'transactions']));
        $routing->prependRoute('vendor_service_types', new sfRoute('/vendor/serviceTypes', ['module' => 'vendor', 'action' => 'serviceTypes']));
        $routing->prependRoute('vendor_add', new sfRoute('/vendor/add', ['module' => 'vendor', 'action' => 'add']));
        $routing->prependRoute('vendor_list', new sfRoute('/vendor/list', ['module' => 'vendor', 'action' => 'list']));
        $routing->prependRoute('vendor_index', new sfRoute('/vendor', ['module' => 'vendor', 'action' => 'index']));
    }
}
