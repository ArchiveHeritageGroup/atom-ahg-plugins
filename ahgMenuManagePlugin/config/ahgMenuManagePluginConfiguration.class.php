<?php

class ahgMenuManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Menu management with Laravel Query Builder and MPTT support';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'menuManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgMenuManage\\') === 0) {
                $relativePath = str_replace('AhgMenuManage\\', '', $class);
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
        $routing = $event->getSubject();

        $router = new \AtomFramework\Routing\RouteLoader('menuManage');

        // Routes are prepended: LAST in code = checked FIRST by router.
        // Catch-all / less specific routes registered first (checked last).
        // Specific routes registered last (checked first).

        // Edit/delete by ID (checked after list/add but before generic patterns)
        $router->any('menu_delete', '/menu/:id/delete', 'delete', ['id' => '\d+']);
        $router->any('menu_edit', '/menu/:id/edit', 'edit', ['id' => '\d+']);

        // Specific routes (checked first after prepending)
        $router->any('menu_add', '/menu/add', 'edit');
        $router->any('menu_list', '/menu/list', 'list');

        $router->register($routing);
    }
}
