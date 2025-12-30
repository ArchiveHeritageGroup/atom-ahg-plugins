<?php

class ahgExtendedRightsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Extended Rights: RightsStatements.org, Creative Commons, Embargo, TK Labels';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgExtendedRightsPlugin/css/extended-rights.css', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'extendedRights';
        $enabledModules[] = 'embargo';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
