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

        // The item feedback button on a record page. Registered here so it exists
        // only while this plugin is enabled.
        require_once __DIR__.'/../lib/Listeners/FeedbackButtonInjector.php';
        $this->dispatcher->connect('response.filter_content', ['\AhgFeedbackPlugin\Listeners\FeedbackButtonInjector', 'filter']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'feedback';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
