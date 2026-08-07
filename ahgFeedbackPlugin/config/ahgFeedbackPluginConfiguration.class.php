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

        // Contribute this plugin's own navigation entry.
        //
        // Registered here rather than named by the theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'feedback', [
                'url' => '/index.php/feedback',
                'label' => 'Feedback',
            'credentials' => ['editor', 'administrator'],
                'weight' => 50,
            ]);
        }
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // The item feedback button on a record page. Registered here so it exists
        // only while this plugin is enabled.
        require_once __DIR__.'/../lib/Listeners/FeedbackButtonInjector.php';
        $this->dispatcher->connect('response.filter_content', ['\AhgFeedbackPlugin\Listeners\FeedbackButtonInjector', 'filter']);

        // The site-wide link on the landing page. Registered here so it exists
        // only while this plugin is enabled.
        require_once __DIR__.'/../lib/Listeners/FeedbackLandingLink.php';
        $this->dispatcher->connect('response.filter_content', ['\\AhgFeedbackPlugin\\Listeners\\FeedbackLandingLink', 'filter']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'feedback';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
