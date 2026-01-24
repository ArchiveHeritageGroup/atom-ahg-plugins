<?php

/**
 * ahgFeedbackPlugin configuration.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class ahgFeedbackPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'User feedback and suggestions management plugin';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        // Add feedback-specific context if needed
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'feedback';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
