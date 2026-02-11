<?php

class ahgJobsManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance job management and monitoring';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'jobsManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgJobsManage\\') === 0) {
                $relativePath = str_replace('AhgJobsManage\\', '', $class);
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

        // Routes registered via prependRoute — LAST in file = checked FIRST
        // Catch-all routes first (checked last), specific routes last (checked first)
        $router = new \AtomFramework\Routing\RouteLoader('jobsManage');
        $router->any('jobs_browse', '/jobs', 'browse');
        $router->any('jobs_report', '/jobs/report/:id', 'report', ['id' => '\d+']);
        $router->any('jobs_delete', '/jobs/delete', 'delete');
        $router->any('jobs_export', '/jobs/export', 'export');
        $router->register($routing);
    }
}
