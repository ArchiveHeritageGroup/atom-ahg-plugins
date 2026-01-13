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
        $routing = $event->getSubject();
        $routing->prependRoute('ahg_favorites_browse', new sfRoute(
            '/favorites',
            ['module' => 'ahgFavorites', 'action' => 'browse']
        ));
        $routing->prependRoute('ahg_favorites_add', new sfRoute(
            '/favorites/add/:slug',
            ['module' => 'ahgFavorites', 'action' => 'add']
        ));
        $routing->prependRoute('ahg_favorites_remove', new sfRoute(
            '/favorites/remove/:id',
            ['module' => 'ahgFavorites', 'action' => 'remove']
        ));
        $routing->prependRoute('ahg_favorites_clear', new sfRoute(
            '/favorites/clear',
            ['module' => 'ahgFavorites', 'action' => 'clear']
        ));
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
        $enabledModules[] = 'ahgFavorites';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
