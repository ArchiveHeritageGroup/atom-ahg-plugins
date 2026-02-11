<?php

class ahgStaticPagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Static page management with Laravel Query Builder';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'staticPageManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgStaticPage\\') === 0) {
                $relativePath = str_replace('AhgStaticPage\\', '', $class);
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

        $router = new \AtomFramework\Routing\RouteLoader('staticPageManage');

        // Routes are prepended: LAST in code = checked FIRST by router.
        // Catch-all / less specific routes registered first (checked last).
        // Specific routes registered last (checked first).

        // Edit by ID (checked after list/add/home but before generic patterns)
        $router->any('staticpage_delete', '/staticpage/:id/delete', 'delete', ['id' => '\d+']);
        $router->any('staticpage_edit', '/staticpage/:id/edit', 'edit', ['id' => '\d+']);

        // Specific routes (checked first after prepending)
        $router->any('staticpage_home', '/staticpage/home', 'edit', [], ['id' => 'home']);
        $router->any('staticpage_add', '/staticpage/add', 'edit');
        $router->any('staticpage_list', '/staticpage/list', 'list');

        $router->register($routing);
    }
}
