<?php

class ahgRightsHolderManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance rights holder browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Contribute this plugin's navigation entry.
        //
        // Registered here rather than named by a theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'RightsHolderManage', [
                'url' => '/index.php/rightsholder/browse',
                'label' => 'Rights Holder Manage',
                'credentials' => ['editor', 'administrator'],
                'weight' => 70,
            ]);
        }

        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'rightsHolderManage';
        $enabledModules[] = 'rightsholder';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgRightsHolderManage\\') === 0) {
                $relativePath = str_replace('AhgRightsHolderManage\\', '', $class);
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

        // All routes target rightsHolderManage module
        // Catch-all slug routes registered first = checked last
        $router = new \AtomFramework\Routing\RouteLoader('rightsHolderManage');
        $router->any('rightsholder_view_override', '/rightsholder/:slug', 'view');
        $router->any('rightsholder_delete_override', '/rightsholder/:slug/delete', 'delete');
        $router->any('rightsholder_edit_override', '/rightsholder/:slug/edit', 'edit');
        // Specific routes registered last = checked first
        $router->any('rightsholder_add_override', '/rightsholder/add', 'edit');
        $router->any('rightsholder_browse_override', '/rightsholder/browse', 'browse');
        $router->register($routing);
    }
}
