<?php

class ahgActorManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance actor browse, autocomplete, and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'actorManage';
        $enabledModules[] = 'sfIsaarPlugin';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgActorManage\\') === 0) {
                $relativePath = str_replace('AhgActorManage\\', '', $class);
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

        // sfIsaarPlugin routes (catch-all slug routes registered first = checked last)
        $isaar = new \AtomFramework\Routing\RouteLoader('sfIsaarPlugin');
        $isaar->any('actor_view_override', '/actor/:slug', 'index', ['slug' => '[a-zA-Z0-9_.-]+']);
        $isaar->any('actor_delete_override', '/actor/:slug/delete', 'delete', ['slug' => '[a-zA-Z0-9_.-]+']);
        $isaar->any('actor_edit_override', '/actor/:slug/edit', 'edit', ['slug' => '[a-zA-Z0-9_.-]+']);
        $isaar->any('actor_add_override', '/actor/add', 'edit');
        $isaar->register($routing);

        // actorManage routes (specific routes registered last = checked first)
        $manage = new \AtomFramework\Routing\RouteLoader('actorManage');
        $manage->any('actor_browse_override', '/actor/browse', 'browse');
        $manage->any('actor_autocomplete_override', '/actor/autocomplete', 'autocomplete');
        $manage->register($routing);
    }
}
