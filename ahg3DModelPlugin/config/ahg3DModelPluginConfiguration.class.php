<?php

/**
 * ahg3DModelPlugin Configuration
 * 
 * Provides 3D model viewing with IIIF 3D extension support for museum objects
 * Supports GLB, GLTF, OBJ, STL, FBX, PLY, and USDZ formats
 * 
 * @package ahg3DModelPlugin
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */
class ahg3DModelPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Plugin for 3D model viewing and IIIF 3D extension support';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        // Ensure assets are loaded
    }

    public function initialize()
    {
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahg3DModel';
        $enabledModules[] = 'ahg3DSettings';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', array($this, 'listenToRoutingLoadConfiguration'));
        
        // Connect event handlers
        $this->dispatcher->connect('context.load_factories', array($this, 'contextLoadFactories'));
    }

    public function listenToRoutingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();

        // 3D Model viewing routes
        $routing->prependRoute('ar3d_model_index', new sfRoute(
            '/ahg3DModel/index',
            array('module' => 'ahg3DModel', 'action' => 'index')
        ));

        $routing->prependRoute('ar3d_model_view', new sfRoute(
            '/ahg3DModel/view/:id',
            array('module' => 'ahg3DModel', 'action' => 'view'),
            array('id' => '\d+')
        ));

        $routing->prependRoute('ar3d_model_upload', new sfRoute(
            '/ahg3DModel/upload',
            array('module' => 'ahg3DModel', 'action' => 'upload')
        ));

        $routing->prependRoute('ar3d_model_edit', new sfRoute(
            '/ahg3DModel/edit/:id',
            array('module' => 'ahg3DModel', 'action' => 'edit'),
            array('id' => '\d+')
        ));

        $routing->prependRoute('ar3d_model_delete', new sfRoute(
            '/ahg3DModel/delete/:id',
            array('module' => 'ahg3DModel', 'action' => 'delete'),
            array('id' => '\d+')
        ));

        // IIIF 3D manifest route
        $routing->prependRoute('ar3d_iiif_manifest', new sfRoute(
            '/iiif/3d/:id/manifest.json',
            array('module' => 'ahg3DModel', 'action' => 'iiifManifest'),
            array('id' => '\d+')
        ));

        // Viewer embed route
        $routing->prependRoute('ar3d_viewer_embed', new sfRoute(
            '/ahg3DModel/embed/:id',
            array('module' => 'ahg3DModel', 'action' => 'embed'),
            array('id' => '\d+')
        ));

        // Hotspot routes
        $routing->prependRoute('ar3d_hotspot_add', new sfRoute(
            '/ahg3DModel/addHotspot/:id',
            array('module' => 'ahg3DModel', 'action' => 'addHotspot'),
            array('id' => '\d+')
        ));

        $routing->prependRoute('ar3d_hotspot_delete', new sfRoute(
            '/ahg3DModel/deleteHotspot/:id',
            array('module' => 'ahg3DModel', 'action' => 'deleteHotspot'),
            array('id' => '\d+')
        ));

        // Settings routes
        $routing->prependRoute('ar3d_settings', new sfRoute(
            '/ahg3DSettings/index',
            array('module' => 'ahg3DSettings', 'action' => 'index')
        ));

        // API routes for AJAX
        $routing->prependRoute('ar3d_api_models', new sfRoute(
            '/api/3d/models/:object_id',
            array('module' => 'ahg3DModel', 'action' => 'apiModels'),
            array('object_id' => '\d+')
        ));

        $routing->prependRoute('ar3d_api_hotspots', new sfRoute(
            '/api/3d/hotspots/:model_id',
            array('module' => 'ahg3DModel', 'action' => 'apiHotspots'),
            array('model_id' => '\d+')
        ));
    }
}
