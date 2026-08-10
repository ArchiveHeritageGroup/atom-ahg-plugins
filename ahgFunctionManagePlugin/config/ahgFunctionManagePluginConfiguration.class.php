<?php

class ahgFunctionManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'ISDF function browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Contribute this plugin's navigation entry.
        //
        // Registered here rather than named by a theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'FunctionManage', [
                'url' => '/index.php/function/browse',
                'label' => 'Function Manage',
                'credentials' => ['editor', 'administrator'],
                'weight' => 70,
            ]);
        }

        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'functionManage';
        $enabledModules[] = 'function';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgFunctionManage\\') === 0) {
                $relativePath = str_replace('AhgFunctionManage\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            return false;
        });
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('functionManage');

        // Catch-all slug route (checked last after prepending)
        $router->any('function_view_override', '/function/:slug', 'view');
        $router->any('function_delete_override', '/function/:slug/delete', 'delete');
        $router->any('function_edit_override', '/function/:slug/edit', 'edit');

        // Specific routes (checked first after prepending)
        $router->any('function_add_override', '/function/add', 'edit');
        $router->any('function_browse_override', '/function/browse', 'browse');

        $router->register($event->getSubject());
    }
}
