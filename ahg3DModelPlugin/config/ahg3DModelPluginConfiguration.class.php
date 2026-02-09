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
        $enabledModules[] = 'model3d';
        $enabledModules[] = 'model3dSettings';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register 3D model provider with framework
        $this->registerProviders();

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', array($this, 'listenToRoutingLoadConfiguration'));
        
        // Connect event handlers
        $this->dispatcher->connect('context.load_factories', array($this, 'contextLoadFactories'));
    }

    /**
     * Register providers with the framework.
     */
    protected function registerProviders(): void
    {
        // Only register if framework is loaded
        if (!class_exists('AtomFramework\\Providers')) {
            return;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahg3DModelPlugin/lib/Provider/Model3DProvider.php';

        \AtomFramework\Providers::register(
            'model_3d',
            new \ahg3DModelPlugin\Provider\Model3DProvider()
        );
    }

    public function listenToRoutingLoadConfiguration(sfEvent $event)
    {
        // model3d module routes
        $model = new \AtomFramework\Routing\RouteLoader('model3d');
        $model->any('ar3d_model_index', '/ahg3DModel/index', 'index');
        $model->any('ar3d_model_view', '/ahg3DModel/view/:id', 'view', ['id' => '\d+']);
        $model->any('ar3d_model_upload', '/ahg3DModel/upload', 'upload');
        $model->any('ar3d_model_edit', '/ahg3DModel/edit/:id', 'edit', ['id' => '\d+']);
        $model->any('ar3d_model_delete', '/ahg3DModel/delete/:id', 'delete', ['id' => '\d+']);
        $model->any('ar3d_iiif_manifest', '/iiif/3d/:id/manifest.json', 'iiifManifest', ['id' => '\d+']);
        $model->any('ar3d_viewer_embed', '/ahg3DModel/embed/:id', 'embed', ['id' => '\d+']);
        $model->any('ar3d_hotspot_add', '/ahg3DModel/addHotspot/:id', 'addHotspot', ['id' => '\d+']);
        $model->any('ar3d_hotspot_delete', '/ahg3DModel/deleteHotspot/:id', 'deleteHotspot', ['id' => '\d+']);
        $model->any('ar3d_api_models', '/api/3d/models/:object_id', 'apiModels', ['object_id' => '\d+']);
        $model->any('ar3d_api_hotspots', '/api/3d/hotspots/:model_id', 'apiHotspots', ['model_id' => '\d+']);
        $model->register($event->getSubject());

        // model3dSettings module routes
        $settings = new \AtomFramework\Routing\RouteLoader('model3dSettings');
        $settings->any('ar3d_settings', '/ahg3DSettings/index', 'index');
        $settings->register($event->getSubject());
    }
}
