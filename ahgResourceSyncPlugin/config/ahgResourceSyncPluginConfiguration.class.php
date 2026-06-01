<?php

/**
 * ahgResourceSyncPluginConfiguration.
 *
 * Enables the `resourcesync` module and registers the four ResourceSync 1.1
 * Source-role endpoints as Symfony routes via the framework RouteLoader.
 *
 * Endpoints (all GET, sitemap-formatted XML):
 *   /.well-known/resourcesync            -> sourceDescription
 *   /resourcesync/capabilitylist.xml     -> capabilityList
 *   /resourcesync/resourcelist.xml       -> resourceList   (?page=N)
 *   /resourcesync/changelist.xml         -> changeList     (?page=N)
 *
 * @package    ahgResourceSyncPlugin
 * @author     The Archive and Heritage Group (Pty) Ltd
 */
class ahgResourceSyncPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'ResourceSync 1.1 Source endpoints (Source Description, Capability List, Resource List, Change List)';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'resourcesync';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();

        $router = new \AtomFramework\Routing\RouteLoader('resourcesync');

        // SourceDescription — well-known discovery file (ResourceSync spec §11).
        $router->get(
            'resourcesync_source_description',
            '/.well-known/resourcesync',
            'sourceDescription'
        );

        // CapabilityList — advertises ResourceList + ChangeList.
        $router->get(
            'resourcesync_capability_list',
            '/resourcesync/capabilitylist.xml',
            'capabilityList'
        );

        // ResourceList — full inventory, paged via ?page=N.
        $router->get(
            'resourcesync_resource_list',
            '/resourcesync/resourcelist.xml',
            'resourceList'
        );

        // ChangeList — recent updates + tombstones, paged via ?page=N.
        $router->get(
            'resourcesync_change_list',
            '/resourcesync/changelist.xml',
            'changeList'
        );

        $router->register($routing);
    }
}
