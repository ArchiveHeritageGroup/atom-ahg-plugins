<?php

class ahgRepositoryManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance archival institution browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'repositoryManage';
        $enabledModules[] = 'sfIsdiahPlugin';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgRepositoryManage\\') === 0) {
                $relativePath = str_replace('AhgRepositoryManage\\', '', $class);
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

        // sfIsdiahPlugin module routes (QubitResourceRoute with requirements - registered directly)
        // Catch-all slug routes (checked last after prepending)
        $routing->prependRoute('repository_view_override', new \QubitResourceRoute(
            '/repository/:slug',
            ['module' => 'sfIsdiahPlugin', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_.-]+']
        ));
        $routing->prependRoute('repository_delete_override', new \QubitResourceRoute(
            '/repository/:slug/delete',
            ['module' => 'sfIsdiahPlugin', 'action' => 'delete'],
            ['slug' => '[a-zA-Z0-9_.-]+']
        ));
        $routing->prependRoute('repository_edit_override', new \QubitResourceRoute(
            '/repository/:slug/edit',
            ['module' => 'sfIsdiahPlugin', 'action' => 'edit'],
            ['slug' => '[a-zA-Z0-9_.-]+']
        ));

        // sfIsdiahPlugin add route (no requirements, sfRoute is fine)
        $sfIsdiah = new \AtomFramework\Routing\RouteLoader('sfIsdiahPlugin');
        $sfIsdiah->any('repository_add_override', '/repository/add', 'edit');
        $sfIsdiah->register($routing);

        // repositoryManage module routes
        $repoManage = new \AtomFramework\Routing\RouteLoader('repositoryManage');
        $repoManage->any('repository_browse_override', '/repository/browse', 'browse');
        $repoManage->register($routing);
    }
}
