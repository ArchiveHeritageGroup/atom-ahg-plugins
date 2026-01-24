<?php
/**
 * ahgRequestToPublishPlugin Configuration
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class ahgRequestToPublishPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Manage publication requests for archival images';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'requestToPublish';
        $enabledModules[] = 'requesttopublish';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // IMPORTANT: Order matters! Generic slug route first, specific routes last
        // (prepend adds to front, so last prepended = first matched)

        // Generic slug route (prepend first = matched last)
        $routing->prependRoute(
            'requesttopublish_edit',
            new sfRoute(
                '/requesttopublish/:slug',
                ['module' => 'requestToPublish', 'action' => 'edit']
            )
        );

        // Delete route (more specific, prepend second)
        $routing->prependRoute(
            'requesttopublish_delete',
            new sfRoute(
                '/requesttopublish/delete/:slug',
                ['module' => 'requestToPublish', 'action' => 'delete']
            )
        );

        // Submit route for public form
        $routing->prependRoute(
            'requesttopublish_submit',
            new sfRoute(
                '/requestToPublish/submit/:slug',
                ['module' => 'requestToPublish', 'action' => 'submit']
            )
        );

        // Admin browse route (most specific, prepend last = matched first)
        $routing->prependRoute(
            'requesttopublish_browse',
            new sfRoute(
                '/requesttopublish/browse',
                ['module' => 'requestToPublish', 'action' => 'browse']
            )
        );
    }
}
