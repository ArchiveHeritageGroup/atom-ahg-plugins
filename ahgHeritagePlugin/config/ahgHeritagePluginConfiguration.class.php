<?php

class ahgHeritagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Heritage discovery platform with contributor system, custodian management, and analytics';
    public static $version = '1.1.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url', 'Tag', 'Partial']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'heritage';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
