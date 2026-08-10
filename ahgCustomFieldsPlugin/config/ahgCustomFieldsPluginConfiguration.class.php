<?php

class ahgCustomFieldsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Admin-configurable custom metadata fields for any entity type';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Contribute this plugin's navigation entry.
        //
        // Registered here rather than named by a theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'CustomFields', [
                'url' => '/index.php/admin/customFields',
                'label' => 'Custom Fields',
                'credentials' => ['administrator'],
                'weight' => 70,
            ]);
        }

        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $this->dispatcher->connect('response.filter_content', [$this, 'addAssets']);

        // Register modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'customFieldAdmin';
        $enabledModules[] = 'customField';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));

        // Register autoloader for namespaced classes
        $this->registerAutoloader();
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('customFieldAdmin');

        // Admin routes
        $router->any('custom_field_admin_index', '/admin/customFields', 'index');
        $router->any('custom_field_admin_edit', '/admin/customFields/edit', 'edit');
        $router->any('custom_field_admin_save', '/admin/customFields/save', 'save');
        $router->any('custom_field_admin_delete', '/admin/customFields/delete', 'delete');
        $router->any('custom_field_admin_reorder', '/admin/customFields/reorder', 'reorder');
        $router->any('custom_field_admin_export', '/admin/customFields/export', 'export');
        $router->any('custom_field_admin_import', '/admin/customFields/import', 'import');

        $router->register($event->getSubject());

        // Entity value routes
        $router2 = new \AtomFramework\Routing\RouteLoader('customField');

        $router2->any('custom_field_search', '/customFields/search', 'search');
        $router2->any('custom_field_save_values', '/customFields/save', 'saveValues');
        $router2->any('custom_field_get_values', '/customFields/get/:entityType/:objectId', 'getValues', [
            'entityType' => '[a-z]+',
            'objectId' => '\d+',
        ]);

        $router2->register($event->getSubject());
    }

    public function addAssets(sfEvent $event, $content)
    {
        $response = $event->getSubject();
        if ($response instanceof sfWebResponse) {
            $response->addStylesheet('/plugins/ahgCustomFieldsPlugin/web/css/custom-fields.css', 'last');
            $response->addJavaScript('/plugins/ahgCustomFieldsPlugin/web/js/custom-fields.js', 'last');
        }

        return $content;
    }

    protected function registerAutoloader()
    {
        $libPath = sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib';
        spl_autoload_register(function ($class) use ($libPath) {
            $prefix = 'AhgCustomFieldsPlugin\\';
            if (strpos($class, $prefix) === 0) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $libPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }

            return false;
        });
    }
}
