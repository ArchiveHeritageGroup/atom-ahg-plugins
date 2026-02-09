<?php

class ahgLabelPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Label generation for archival objects';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'label';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('label');

        $router->any('label_index', '/label/:slug', 'index');

        $router->register($event->getSubject());
    }
}
