<?php

class ahgDonorManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance donor browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'donorManage';
        $enabledModules[] = 'donor';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgDonorManage\\') === 0) {
                $relativePath = str_replace('AhgDonorManage\\', '', $class);
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

        // All routes target donorManage module
        // Catch-all slug routes registered first = checked last
        $router = new \AtomFramework\Routing\RouteLoader('donorManage');
        $router->any('donor_view_override', '/donor/:slug', 'view');
        $router->any('donor_delete_override', '/donor/:slug/delete', 'delete');
        $router->any('donor_edit_override', '/donor/:slug/edit', 'edit');
        // Specific routes registered last = checked first
        $router->any('donor_add_override', '/donor/add', 'edit');
        $router->any('donor_browse_override', '/donor/browse', 'browse');
        $router->register($routing);
    }
}
