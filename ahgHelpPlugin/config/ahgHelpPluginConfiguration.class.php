<?php

class ahgHelpPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Online help system with searchable documentation and contextual help';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        // Inject help CSS + JS globally for contextual help button
        $this->dispatcher->connect('context.load_factories', [$this, 'injectAssets']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'help';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgHelp\\') === 0) {
                $relativePath = str_replace('AhgHelp\\', '', $class);
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

        $router = new \AtomFramework\Routing\RouteLoader('help');

        // Routes are prepended: LAST in code = checked FIRST by router.

        // Catch-all / less specific routes registered first (checked last).
        $router->get('help_index', '/help', 'index');

        // Category and article routes
        $router->get('help_category', '/help/category/:category', 'category');
        $router->get('help_article_view', '/help/article/:slug', 'article');

        // Search
        $router->get('help_search', '/help/search', 'search');

        // API endpoints (checked first after prepending)
        $router->get('help_api_search', '/help/api/search', 'apiSearch');
        $router->get('help_api_index', '/help/api/search-index', 'apiSearchIndex');
        $router->get('help_api_context', '/help/api/context-map', 'apiContextMap');
        $router->any('help_api_chat', '/help/api/chat', 'apiChat');

        $router->register($routing);
    }

    public function injectAssets(sfEvent $event)
    {
        $context = $event->getSubject();
        $response = $context->getResponse();

        // Inject help CSS and JS on all pages
        $response->addStylesheet('/plugins/ahgHelpPlugin/css/help.css', 'last');
        $response->addJavascript('/plugins/ahgHelpPlugin/js/help-search.js', 'last');
        $response->addJavascript('/plugins/ahgHelpPlugin/js/help-context.js', 'last');
        $response->addJavascript('/plugins/ahgHelpPlugin/js/help-chatbot.js', 'last');
    }
}
