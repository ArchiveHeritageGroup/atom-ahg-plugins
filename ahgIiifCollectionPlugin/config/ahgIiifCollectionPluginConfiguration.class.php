<?php

class ahgIiifCollectionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'IIIF Manifest Collections plugin for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Enable the module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahgIiifCollection';
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
            ['module' => 'ahgIiifCollection', 'action' => 'autocomplete']
        ));

        // Index/list
        $routing->prependRoute('iiif_collection_index', new sfRoute(
            '/manifest-collections',
            ['module' => 'ahgIiifCollection', 'action' => 'index']
        ));

        // Create/new
        $routing->prependRoute('iiif_collection_new', new sfRoute(
            '/manifest-collection/new',
            ['module' => 'ahgIiifCollection', 'action' => 'new']
        ));

        $routing->prependRoute('iiif_collection_create', new sfRoute(
            '/manifest-collection/create',
            ['module' => 'ahgIiifCollection', 'action' => 'create']
        ));

        // Reorder
        $routing->prependRoute('iiif_collection_reorder', new sfRoute(
            '/manifest-collection/reorder',
            ['module' => 'ahgIiifCollection', 'action' => 'reorder']
        ));

        // View/edit/update/delete
        $routing->prependRoute('iiif_collection_view', new sfRoute(
            '/manifest-collection/:id/view',
            ['module' => 'ahgIiifCollection', 'action' => 'view']
        ));

        $routing->prependRoute('iiif_collection_edit', new sfRoute(
            '/manifest-collection/:id/edit',
            ['module' => 'ahgIiifCollection', 'action' => 'edit']
        ));

        $routing->prependRoute('iiif_collection_update', new sfRoute(
            '/manifest-collection/:id/update',
            ['module' => 'ahgIiifCollection', 'action' => 'update']
        ));

        $routing->prependRoute('iiif_collection_delete', new sfRoute(
            '/manifest-collection/:id/delete',
            ['module' => 'ahgIiifCollection', 'action' => 'delete']
        ));

        // Items management
        $routing->prependRoute('iiif_collection_add_items', new sfRoute(
            '/manifest-collection/:id/items/add',
            ['module' => 'ahgIiifCollection', 'action' => 'addItems']
        ));

        $routing->prependRoute('iiif_collection_remove_item', new sfRoute(
            '/manifest-collection/item/:item_id/remove',
            ['module' => 'ahgIiifCollection', 'action' => 'removeItem']
        ));

        // IIIF JSON output (must be last - has wildcard slug)
        $routing->prependRoute('iiif_collection_manifest', new sfRoute(
            '/manifest-collection/:slug/manifest.json',
            ['module' => 'ahgIiifCollection', 'action' => 'manifest']
        ));
    }
}
