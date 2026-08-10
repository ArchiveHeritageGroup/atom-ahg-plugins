<?php

class ahgStorageManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance physical storage browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Contribute this plugin's navigation entry.
        //
        // Registered here rather than named by a theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'StorageManage', [
                'url' => '/index.php/physicalobject/browse',
                'label' => 'Storage Manage',
                'credentials' => ['editor', 'administrator'],
                'weight' => 70,
            ]);
        }

        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'storageManage';
        $enabledModules[] = 'physicalobject';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgStorageManage\\') === 0) {
                $relativePath = str_replace('AhgStorageManage\\', '', $class);
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
        $router = new \AtomFramework\Routing\RouteLoader('storageManage');

        $router->any('physicalobject_browse_override', '/physicalobject/browse', 'browse');
        $router->any('physicalobject_autocomplete_override', '/physicalobject/autocomplete', 'autocomplete');
        $router->any('physicalobject_boxlist_override', '/physicalobject/boxList', 'boxList');
        $router->any('physicalobject_holdings_export_override', '/physicalobject/holdingsReportExport', 'holdingsReportExport');

        $router->register($event->getSubject());
    }
}
