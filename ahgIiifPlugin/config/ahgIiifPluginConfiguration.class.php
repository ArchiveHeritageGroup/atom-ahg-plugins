<?php

/**
 * ahgIiifPlugin Configuration
 *
 * Consolidated IIIF plugin for AtoM - handles ALL IIIF functionality:
 *
 * VIEWER:
 * - OpenSeadragon deep zoom viewer
 * - Mirador multi-image comparison
 * - Multi-page TIFF support
 * - PDF rendering
 * - 3D model viewing (model-viewer)
 * - Audio/Video playback
 *
 * MANIFESTS:
 * - Single item manifest generation
 * - Multi-page TIFF manifests
 *
 * COLLECTIONS:
 * - IIIF Collection manifests (curated sets of items)
 * - Collection management UI
 *
 * Replaces: ahgIiifCollectionPlugin (deprecated)
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */
class ahgIiifPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'IIIF plugin for manifests, deep zoom viewer, collections, and media viewing';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Set IIIF config defaults (plugin manages its own config)
        $this->setConfigDefaults();

        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'iiif';
        $enabledModules[] = 'iiifCollection';
        $enabledModules[] = 'iiifAuth';
        $enabledModules[] = 'threeDReports';
        $enabledModules[] = 'media';
        $enabledModules[] = 'mediaSettings';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Add routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Include helper
        $this->loadHelpers();
    }

    /**
     * Set IIIF configuration defaults
     * Plugin manages its own config - no base AtoM changes required
     */
    protected function setConfigDefaults()
    {
        // Auto-detect base URL from request (default to https)
        $host = $_SERVER['HTTP_HOST'] ?? 'psis.theahg.co.za';
        $baseUrl = "https://{$host}";

        // Plugin sets its own config when enabled
        sfConfig::set('app_iiif_enabled', true);
        sfConfig::set('app_iiif_base_url', $baseUrl);
        sfConfig::set('app_iiif_cantaloupe_url', "{$baseUrl}/iiif/2");
        sfConfig::set('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182');
        sfConfig::set('app_iiif_plugin_path', '/plugins/ahgIiifPlugin/web');
        sfConfig::set('app_iiif_default_viewer', 'openseadragon');
        sfConfig::set('app_iiif_viewer_height', '600px');
        sfConfig::set('app_iiif_enable_annotations', true);
    }

    protected function loadHelpers()
    {
        $helperFile = dirname(__FILE__) . '/../lib/helper/IiifViewerHelper.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // ===================
        // IIIF MODULE ROUTES (manifests, annotations, settings)
        // ===================
        $iiif = new \AtomFramework\Routing\RouteLoader('iiif');
        $iiif->get('iiif_manifest', '/iiif/manifest/:slug', 'manifest');
        $iiif->get('iiif_manifest_by_id', '/iiif/manifest/id/:id', 'manifestById', ['id' => '\d+']);
        $iiif->get('iiif_manifest_v3', '/iiif/v3/manifest/:slug', 'manifestV3');
        $iiif->get('iiif_viewer', '/iiif/viewer/:id', 'viewer', ['id' => '\d+']);
        $iiif->any('iiif_settings', '/admin/iiif-settings', 'settings');
        $iiif->get('iiif_annotations_object', '/iiif/annotations/object/:id', 'annotationsList', ['id' => '\d+']);
        $iiif->post('iiif_annotations_create', '/iiif/annotations', 'annotationsCreate');
        $iiif->any('iiif_annotations_update', '/iiif/annotations/:id', 'annotationsUpdate', ['id' => '\d+']);
        $iiif->any('iiif_annotations_delete', '/iiif/annotations/:id', 'annotationsDelete', ['id' => '\d+']);
        $iiif->register($routing);

        // ===================
        // IIIF COLLECTION ROUTES
        // ===================
        $collection = new \AtomFramework\Routing\RouteLoader('iiifCollection');
        $collection->any('iiif_collection_autocomplete', '/manifest-collections/autocomplete', 'autocomplete');
        $collection->any('iiif_collection_index', '/manifest-collections', 'index');
        $collection->any('iiif_collection_new', '/manifest-collection/new', 'new');
        $collection->any('iiif_collection_create', '/manifest-collection/create', 'create');
        $collection->any('iiif_collection_reorder', '/manifest-collection/reorder', 'reorder');
        $collection->any('iiif_collection_view', '/manifest-collection/:id/view', 'view');
        $collection->any('iiif_collection_edit', '/manifest-collection/:id/edit', 'edit');
        $collection->any('iiif_collection_update', '/manifest-collection/:id/update', 'update');
        $collection->any('iiif_collection_delete', '/manifest-collection/:id/delete', 'delete');
        $collection->any('iiif_collection_add_items', '/manifest-collection/:id/items/add', 'addItems');
        $collection->any('iiif_collection_remove_item', '/manifest-collection/item/:item_id/remove', 'removeItem');
        $collection->any('iiif_collection_manifest', '/manifest-collection/:slug/manifest.json', 'manifest');
        $collection->register($routing);

        // ===================
        // IIIF AUTH ROUTES (IIIF Auth API 1.0)
        // ===================
        $auth = new \AtomFramework\Routing\RouteLoader('iiifAuth');
        $auth->any('iiif_auth_admin', '/admin/iiif-auth', 'index');
        $auth->any('iiif_auth_login', '/iiif/auth/login/:service', 'login');
        $auth->any('iiif_auth_token', '/iiif/auth/token/:service', 'token');
        $auth->any('iiif_auth_logout', '/iiif/auth/logout/:service', 'logout');
        $auth->any('iiif_auth_confirm', '/iiif/auth/confirm/:service', 'confirm');
        $auth->any('iiif_auth_check', '/iiif/auth/check/:id', 'check', ['id' => '\d+']);
        $auth->any('iiif_auth_protect', '/admin/iiif-auth/protect', 'protect');
        $auth->any('iiif_auth_unprotect', '/admin/iiif-auth/unprotect', 'unprotect');
        $auth->register($routing);

        // ===================
        // 3D REPORTS ROUTES
        // ===================
        $threeD = new \AtomFramework\Routing\RouteLoader('threeDReports');
        $threeD->any('threeD_reports_index', '/threeDReports', 'index');
        $threeD->any('threeD_reports_models', '/threeDReports/models', 'models');
        $threeD->any('threeD_reports_hotspots', '/threeDReports/hotspots', 'hotspots');
        $threeD->any('threeD_reports_thumbnails', '/threeDReports/thumbnails', 'thumbnails');
        $threeD->any('threeD_reports_digitalObjects', '/threeDReports/digitalObjects', 'digitalObjects');
        $threeD->any('threeD_reports_settings', '/threeDReports/settings', 'settings');
        $threeD->any('threeD_reports_createConfig', '/threeDReports/createConfig', 'createConfig');
        $threeD->any('threeD_reports_bulkCreateConfig', '/threeDReports/bulkCreateConfig', 'bulkCreateConfig');
        $threeD->register($routing);

        // ===================
        // MEDIA STREAMING ROUTES
        // ===================
        $media = new \AtomFramework\Routing\RouteLoader('media');
        $media->any('media_stream', '/media/stream/:id', 'stream', ['id' => '\d+']);
        $media->any('media_download', '/media/download/:id', 'download', ['id' => '\d+']);
        $media->any('media_snippets_list', '/media/snippets/:id', 'snippets', ['id' => '\d+']);
        $media->post('media_snippets_save', '/media/snippets', 'saveSnippet');
        $media->any('media_snippets_delete', '/media/snippets/:id/delete', 'deleteSnippet', ['id' => '\d+']);
        $media->any('media_extract', '/media/extract/:id', 'extract', ['id' => '\d+']);
        $media->any('media_transcribe', '/media/transcribe/:id', 'transcribe', ['id' => '\d+']);
        $media->any('media_transcription', '/media/transcription/:id', 'transcription', ['id' => '\d+']);
        $media->any('media_transcription_format', '/media/transcription/:id/:format', 'transcription', ['id' => '\d+', 'format' => '(json|vtt|srt|txt)']);
        $media->any('media_convert', '/media/convert/:id', 'convert', ['id' => '\d+']);
        $media->any('media_metadata', '/media/metadata/:id', 'metadata', ['id' => '\d+']);
        $media->register($routing);

        // ===================
        // MEDIA SETTINGS ROUTES
        // ===================
        $mediaSettings = new \AtomFramework\Routing\RouteLoader('mediaSettings');
        $mediaSettings->any('media_settings_index', '/mediaSettings/index', 'index');
        $mediaSettings->any('media_settings_save', '/mediaSettings/save', 'save');
        $mediaSettings->any('media_settings_test', '/mediaSettings/test', 'test');
        $mediaSettings->any('media_settings_queue', '/mediaSettings/queue', 'queue');
        $mediaSettings->any('media_settings_process_queue', '/mediaSettings/processQueue', 'processQueue');
        $mediaSettings->any('media_settings_clear_queue', '/mediaSettings/clearQueue', 'clearQueue');
        $mediaSettings->any('media_settings_autocomplete', '/mediaSettings/autocomplete', 'autocomplete');
        $mediaSettings->register($routing);
    }
}
