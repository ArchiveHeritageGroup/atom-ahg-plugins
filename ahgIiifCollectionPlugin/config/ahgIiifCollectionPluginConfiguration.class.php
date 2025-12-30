<?php

class ahgIiifCollectionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'IIIF Manifest Collections plugin for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Enable the module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'arIiifCollection';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Add routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Autocomplete first (more specific)
        $routing->prependRoute('iiif_collection_autocomplete', new sfRoute(
            '/manifest-collections/autocomplete',
            ['module' => 'arIiifCollection', 'action' => 'autocomplete']
        ));

        // Index/list
        $routing->prependRoute('iiif_collection_index', new sfRoute(
            '/manifest-collections',
            ['module' => 'arIiifCollection', 'action' => 'index']
        ));

        // Create/new
        $routing->prependRoute('iiif_collection_new', new sfRoute(
            '/manifest-collection/new',
            ['module' => 'arIiifCollection', 'action' => 'new']
        ));

        $routing->prependRoute('iiif_collection_create', new sfRoute(
            '/manifest-collection/create',
            ['module' => 'arIiifCollection', 'action' => 'create']
        ));

        // Reorder
        $routing->prependRoute('iiif_collection_reorder', new sfRoute(
            '/manifest-collection/reorder',
            ['module' => 'arIiifCollection', 'action' => 'reorder']
        ));

        // View/edit/update/delete
        $routing->prependRoute('iiif_collection_view', new sfRoute(
            '/manifest-collection/:id/view',
            ['module' => 'arIiifCollection', 'action' => 'view']
        ));

        $routing->prependRoute('iiif_collection_edit', new sfRoute(
            '/manifest-collection/:id/edit',
            ['module' => 'arIiifCollection', 'action' => 'edit']
        ));

        $routing->prependRoute('iiif_collection_update', new sfRoute(
            '/manifest-collection/:id/update',
            ['module' => 'arIiifCollection', 'action' => 'update']
        ));

        $routing->prependRoute('iiif_collection_delete', new sfRoute(
            '/manifest-collection/:id/delete',
            ['module' => 'arIiifCollection', 'action' => 'delete']
        ));

        // Items management
        $routing->prependRoute('iiif_collection_add_items', new sfRoute(
            '/manifest-collection/:id/items/add',
            ['module' => 'arIiifCollection', 'action' => 'addItems']
        ));

        $routing->prependRoute('iiif_collection_remove_item', new sfRoute(
            '/manifest-collection/item/:item_id/remove',
            ['module' => 'arIiifCollection', 'action' => 'removeItem']
        ));

        // IIIF JSON output (must be last - has wildcard slug)
        $routing->prependRoute('iiif_collection_manifest', new sfRoute(
            '/manifest-collection/:slug/manifest.json',
            ['module' => 'arIiifCollection', 'action' => 'manifest']
        ));
    }
}
