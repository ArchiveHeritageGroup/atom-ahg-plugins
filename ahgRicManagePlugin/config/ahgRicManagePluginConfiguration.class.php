<?php

/**
 * ahgRicManagePlugin configuration.
 *
 * Registers the ricManage module + its AJAX capture routes and an autoloader
 * for the AhgRicManage\ namespace. The RiC standard itself is a term in the
 * information-object template taxonomy (id 70, code 'ric'); records with that
 * display standard render through the normal ISAD theme template, and the RiC
 * capture surface is injected as a display panel (see extension.json).
 *
 * @author The Archive and Heritage Group
 */
class ahgRicManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Records in Context (RiC-O) as a selectable descriptive standard';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ricManage';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));

        $this->registerAutoloader();
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('ricManage');

        // AJAX read/write of a record's RiC metadata (entity type + properties).
        $router->any('ric_get', '/ricManage/get/:objectId', 'get', ['objectId' => '\d+']);
        $router->any('ric_save', '/ricManage/save', 'save');
        // RiC-O JSON-LD export for a single record (MySQL-sourced).
        $router->any('ric_export', '/ricManage/export/:objectId', 'export', ['objectId' => '\d+']);

        $router->register($event->getSubject());
    }

    protected function registerAutoloader()
    {
        $libPath = sfConfig::get('sf_plugins_dir') . '/ahgRicManagePlugin/lib';
        spl_autoload_register(function ($class) use ($libPath) {
            $prefix = 'AhgRicManage\\';
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
