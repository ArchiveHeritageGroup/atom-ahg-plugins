<?php

class ahgAccessionManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance accession browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'accessionManage';
        $enabledModules[] = 'accession';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgAccessionManage\\') === 0) {
                $relativePath = str_replace('AhgAccessionManage\\', '', $class);
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

        // accession module routes (catch-all slug routes registered first = checked last)
        $accession = new \AtomFramework\Routing\RouteLoader('accession');
        $accession->any('accession_view_override', '/accession/:slug', 'index', ['slug' => '[a-zA-Z0-9_.-]+']);
        $accession->any('accession_delete_override', '/accession/:slug/delete', 'delete', ['slug' => '[a-zA-Z0-9_.-]+']);
        $accession->any('accession_edit_override', '/accession/:slug/edit', 'edit', ['slug' => '[a-zA-Z0-9_.-]+']);
        $accession->any('accession_add_override', '/accession/add', 'edit');
        $accession->register($routing);

        // accessionManage module routes (specific routes registered last = checked first)
        $manage = new \AtomFramework\Routing\RouteLoader('accessionManage');
        $manage->any('accession_browse_override', '/accession/browse', 'browse');
        $manage->register($routing);
    }
}
