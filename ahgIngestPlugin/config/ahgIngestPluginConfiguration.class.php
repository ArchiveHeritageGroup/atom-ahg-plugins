<?php

/**
 * ahgIngestPlugin configuration.
 *
 * OAIS-aligned multi-stage ingestion pipeline for batch import
 * of records and digital objects into AtoM.
 */
class ahgIngestPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Multi-stage ingestion pipeline with validation, preview and commit';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ingest';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $r = new \AtomFramework\Routing\RouteLoader('ingest');

        // Dashboard
        $r->any('ingest_index', '/ingest', 'index');
        $r->any('ingest_new', '/ingest/new', 'configure');

        // Wizard steps (session-bound)
        $r->any('ingest_configure', '/ingest/:id/configure', 'configure', ['id' => '\d+']);
        $r->any('ingest_upload', '/ingest/:id/upload', 'upload', ['id' => '\d+']);
        $r->any('ingest_map', '/ingest/:id/map', 'map', ['id' => '\d+']);
        $r->any('ingest_validate', '/ingest/:id/validate', 'validate', ['id' => '\d+']);
        $r->any('ingest_preview', '/ingest/:id/preview', 'preview', ['id' => '\d+']);
        $r->any('ingest_commit', '/ingest/:id/commit', 'commit', ['id' => '\d+']);

        // AJAX endpoints
        $r->any('ingest_ajax_search_parent', '/ingest/ajax/search-parent', 'searchParent');
        $r->any('ingest_ajax_auto_map', '/ingest/ajax/auto-map', 'autoMap');
        $r->any('ingest_ajax_extract_metadata', '/ingest/ajax/extract-metadata', 'extractMetadata');
        $r->any('ingest_ajax_job_status', '/ingest/ajax/job-status', 'jobStatus');
        $r->any('ingest_ajax_preview_tree', '/ingest/ajax/preview-tree', 'previewTree');

        // Management
        $r->any('ingest_cancel', '/ingest/:id/cancel', 'cancel', ['id' => '\d+']);
        $r->any('ingest_rollback', '/ingest/:id/rollback', 'rollback', ['id' => '\d+']);
        $r->any('ingest_download_manifest', '/ingest/:id/manifest', 'downloadManifest', ['id' => '\d+']);
        $r->any('ingest_download_template', '/ingest/template/:sector', 'downloadTemplate');

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'Ingestion Manager',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                '6-step wizard: configure, upload, map, validate, preview, commit',
                'CSV/ZIP/EAD upload with auto-detection',
                'Auto field mapping with confidence indicators',
                'Embedded metadata extraction (EXIF/IPTC/XMP)',
                'Hierarchical tree preview with approval',
                'OAIS-aligned SIP/DIP packaging',
                'Rollback support',
            ],
        ];
    }
}
