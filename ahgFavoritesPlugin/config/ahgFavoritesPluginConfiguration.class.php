<?php

/**
 * ahgFavoritesPlugin configuration
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgFavoritesPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'User favorites/bookmarks management plugin';
    public static $version = '1.0.0';

    public function routingLoadConfiguration(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('favorites');

        $router->any('ahg_favorites_browse', '/favorites', 'browse');
        $router->any('ahg_favorites_add', '/favorites/add/:slug', 'add');
        $router->any('ahg_favorites_remove', '/favorites/remove/:id', 'remove');
        $router->any('ahg_favorites_clear', '/favorites/clear', 'clear');

        $router->register($event->getSubject());
    }

    public function contextLoadFactories(sfEvent $event)
    {
        // Plugin initialization
    }

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'favorites';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
