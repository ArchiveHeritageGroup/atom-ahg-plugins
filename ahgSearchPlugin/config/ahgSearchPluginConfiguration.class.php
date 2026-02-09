<?php

class ahgSearchPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Global search, autocomplete, description updates, and search/replace';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgSearch';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgSearch\\') === 0) {
                $relativePath = str_replace('AhgSearch\\', '', $class);
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

        // ahgSearch module routes
        $search = new \AtomFramework\Routing\RouteLoader('ahgSearch');
        $search->any('search_autocomplete_override', '/search/autocomplete', 'autocomplete');
        $search->any('search_index_override', '/search/index', 'index');
        $search->any('search_descriptionupdates_override', '/search/descriptionUpdates', 'descriptionUpdates');
        $search->any('search_globalreplace_override', '/search/globalReplace', 'globalReplace');
        $search->register($routing);

        // Semantic search route (ricSemanticSearch module - optional)
        $semantic = new \AtomFramework\Routing\RouteLoader('ricSemanticSearch');
        $semantic->any('search_semantic_override', '/search/semantic', 'index');
        $semantic->register($routing);
    }
}
