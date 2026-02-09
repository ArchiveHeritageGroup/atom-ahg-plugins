<?php

class ahgTermTaxonomyPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance term and taxonomy browse for subjects, places, and genres';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'termTaxonomy';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgTermTaxonomy\\') === 0) {
                $relativePath = str_replace('AhgTermTaxonomy\\', '', $class);
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

        // All routes target termTaxonomy module
        // Catch-all slug routes registered first = checked last
        $router = new \AtomFramework\Routing\RouteLoader('termTaxonomy');
        $router->any('term_edit_override', '/term/:slug/edit', 'edit', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('term_delete_override', '/term/:slug/delete', 'delete', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('term_browse_override', '/term/:slug', 'index', ['slug' => '[a-zA-Z0-9_-]+']);
        // Taxonomy route with numeric ID
        $router->any('taxonomy_browse_override', '/taxonomy/:id', 'taxonomyIndex', ['id' => '\d+']);
        $router->register($routing);
    }
}
