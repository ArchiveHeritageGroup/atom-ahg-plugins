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
        sfConfig::set('app_iiif_plugin_path', '/plugins/ahgIiifPlugin');
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
        // MANIFEST ROUTES
        // ===================

        // IIIF Manifest by slug
        $routing->prependRoute('iiif_manifest', new sfRoute(
            '/iiif/manifest/:slug',
            ['module' => 'iiif', 'action' => 'manifest']
        ));

        // IIIF Manifest by ID
        $routing->prependRoute('iiif_manifest_by_id', new sfRoute(
            '/iiif/manifest/id/:id',
            ['module' => 'iiif', 'action' => 'manifestById'],
            ['id' => '\d+']
        ));

        // Viewer settings admin page
        $routing->prependRoute('iiif_settings', new sfRoute(
            '/admin/iiif-settings',
            ['module' => 'iiif', 'action' => 'settings']
        ));

        // ===================
        // COLLECTION ROUTES
        // ===================

        // Autocomplete (most specific first)
        $routing->prependRoute('iiif_collection_autocomplete', new sfRoute(
            '/manifest-collections/autocomplete',
            ['module' => 'iiifCollection', 'action' => 'autocomplete']
        ));

        // Index/list
        $routing->prependRoute('iiif_collection_index', new sfRoute(
            '/manifest-collections',
            ['module' => 'iiifCollection', 'action' => 'index']
        ));

        // Create/new
        $routing->prependRoute('iiif_collection_new', new sfRoute(
            '/manifest-collection/new',
            ['module' => 'iiifCollection', 'action' => 'new']
        ));

        $routing->prependRoute('iiif_collection_create', new sfRoute(
            '/manifest-collection/create',
            ['module' => 'iiifCollection', 'action' => 'create']
        ));

        // Reorder
        $routing->prependRoute('iiif_collection_reorder', new sfRoute(
            '/manifest-collection/reorder',
            ['module' => 'iiifCollection', 'action' => 'reorder']
        ));

        // View/edit/update/delete
        $routing->prependRoute('iiif_collection_view', new sfRoute(
            '/manifest-collection/:id/view',
            ['module' => 'iiifCollection', 'action' => 'view']
        ));

        $routing->prependRoute('iiif_collection_edit', new sfRoute(
            '/manifest-collection/:id/edit',
            ['module' => 'iiifCollection', 'action' => 'edit']
        ));

        $routing->prependRoute('iiif_collection_update', new sfRoute(
            '/manifest-collection/:id/update',
            ['module' => 'iiifCollection', 'action' => 'update']
        ));

        $routing->prependRoute('iiif_collection_delete', new sfRoute(
            '/manifest-collection/:id/delete',
            ['module' => 'iiifCollection', 'action' => 'delete']
        ));

        // Items management
        $routing->prependRoute('iiif_collection_add_items', new sfRoute(
            '/manifest-collection/:id/items/add',
            ['module' => 'iiifCollection', 'action' => 'addItems']
        ));

        $routing->prependRoute('iiif_collection_remove_item', new sfRoute(
            '/manifest-collection/item/:item_id/remove',
            ['module' => 'iiifCollection', 'action' => 'removeItem']
        ));

        // IIIF Collection JSON output (must be last - has wildcard slug)
        $routing->prependRoute('iiif_collection_manifest', new sfRoute(
            '/manifest-collection/:slug/manifest.json',
            ['module' => 'iiifCollection', 'action' => 'manifest']
        ));

        // ===================
        // AUTH ROUTES (IIIF Auth API 1.0)
        // ===================

        // Auth admin
        $routing->prependRoute('iiif_auth_admin', new sfRoute(
            '/admin/iiif-auth',
            ['module' => 'iiifAuth', 'action' => 'index']
        ));

        // Login service
        $routing->prependRoute('iiif_auth_login', new sfRoute(
            '/iiif/auth/login/:service',
            ['module' => 'iiifAuth', 'action' => 'login']
        ));

        // Token service
        $routing->prependRoute('iiif_auth_token', new sfRoute(
            '/iiif/auth/token/:service',
            ['module' => 'iiifAuth', 'action' => 'token']
        ));

        // Logout service
        $routing->prependRoute('iiif_auth_logout', new sfRoute(
            '/iiif/auth/logout/:service',
            ['module' => 'iiifAuth', 'action' => 'logout']
        ));

        // Confirm (clickthrough)
        $routing->prependRoute('iiif_auth_confirm', new sfRoute(
            '/iiif/auth/confirm/:service',
            ['module' => 'iiifAuth', 'action' => 'confirm']
        ));

        // Access check API
        $routing->prependRoute('iiif_auth_check', new sfRoute(
            '/iiif/auth/check/:id',
            ['module' => 'iiifAuth', 'action' => 'check'],
            ['id' => '\d+']
        ));

        // Protect/unprotect (admin)
        $routing->prependRoute('iiif_auth_protect', new sfRoute(
            '/admin/iiif-auth/protect',
            ['module' => 'iiifAuth', 'action' => 'protect']
        ));

        $routing->prependRoute('iiif_auth_unprotect', new sfRoute(
            '/admin/iiif-auth/unprotect',
            ['module' => 'iiifAuth', 'action' => 'unprotect']
        ));

        // ===================
        // 3D REPORTS ROUTES
        // ===================

        $routing->prependRoute('threeD_reports_index', new sfRoute(
            '/threeDReports',
            ['module' => 'threeDReports', 'action' => 'index']
        ));

        $routing->prependRoute('threeD_reports_models', new sfRoute(
            '/threeDReports/models',
            ['module' => 'threeDReports', 'action' => 'models']
        ));

        $routing->prependRoute('threeD_reports_hotspots', new sfRoute(
            '/threeDReports/hotspots',
            ['module' => 'threeDReports', 'action' => 'hotspots']
        ));

        $routing->prependRoute('threeD_reports_thumbnails', new sfRoute(
            '/threeDReports/thumbnails',
            ['module' => 'threeDReports', 'action' => 'thumbnails']
        ));

        $routing->prependRoute('threeD_reports_digitalObjects', new sfRoute(
            '/threeDReports/digitalObjects',
            ['module' => 'threeDReports', 'action' => 'digitalObjects']
        ));

        $routing->prependRoute('threeD_reports_settings', new sfRoute(
            '/threeDReports/settings',
            ['module' => 'threeDReports', 'action' => 'settings']
        ));

        $routing->prependRoute('threeD_reports_createConfig', new sfRoute(
            '/threeDReports/createConfig',
            ['module' => 'threeDReports', 'action' => 'createConfig']
        ));

        $routing->prependRoute('threeD_reports_bulkCreateConfig', new sfRoute(
            '/threeDReports/bulkCreateConfig',
            ['module' => 'threeDReports', 'action' => 'bulkCreateConfig']
        ));

        // ===================
        // MEDIA STREAMING ROUTES (moved from theme routing.yml)
        // ===================

        $routing->prependRoute('media_stream', new sfRoute(
            '/media/stream/:id',
            ['module' => 'media', 'action' => 'stream'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('media_download', new sfRoute(
            '/media/download/:id',
            ['module' => 'media', 'action' => 'download'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('media_snippets_list', new sfRoute(
            '/media/snippets/:id',
            ['module' => 'media', 'action' => 'snippets'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('media_snippets_save', new sfRoute(
            '/media/snippets',
            ['module' => 'media', 'action' => 'saveSnippet']
        ));
    }
}
