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
        $enabledModules[] = 'informationobject'; // For editRequestToPublish action
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('requestToPublish');

        // IMPORTANT: Order matters! Generic slug route first, specific routes last
        // (prepend adds to front, so last prepended = first matched)

        // Generic slug route (prepend first = matched last)
        $router->any('requesttopublish_edit', '/requesttopublish/:slug', 'edit');

        // Delete route (more specific, prepend second)
        $router->any('requesttopublish_delete', '/requesttopublish/delete/:slug', 'delete');

        // Submit route for public form
        $router->any('requesttopublish_submit', '/requestToPublish/submit/:slug', 'submit');

        // Admin browse route (most specific, prepend last = matched first)
        $router->any('requesttopublish_browse', '/requesttopublish/browse', 'browse');

        $router->register($event->getSubject());
    }
}
