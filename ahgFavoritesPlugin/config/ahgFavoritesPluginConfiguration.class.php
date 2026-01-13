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

    public function contextLoadFactories(sfEvent $event)
    {
        // Plugin initialization
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahgFavorites';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
