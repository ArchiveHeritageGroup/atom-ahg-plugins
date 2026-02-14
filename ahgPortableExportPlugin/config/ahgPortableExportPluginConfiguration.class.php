<?php

/**
 * ahgPortableExportPlugin configuration.
 *
 * Standalone portable HTML/JS viewer for offline catalogue access
 * on CD, USB, or downloadable ZIP. Zero server required.
 */
class ahgPortableExportPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Standalone portable catalogue viewer for CD/USB/ZIP distribution';
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
        $enabledModules[] = 'portableExport';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $r = new \AtomFramework\Routing\RouteLoader('portableExport');

        // Main UI
        $r->any('portable_export_index', '/portable-export', 'index');

        // API endpoints
        $r->any('portable_export_api_start', '/portable-export/api/start', 'apiStartExport');
        $r->any('portable_export_api_progress', '/portable-export/api/progress', 'apiProgress');
        $r->any('portable_export_api_list', '/portable-export/api/list', 'apiList');
        $r->any('portable_export_api_delete', '/portable-export/api/delete', 'apiDelete');
        $r->any('portable_export_api_token', '/portable-export/api/token', 'apiToken');

        // Download
        $r->any('portable_export_download', '/portable-export/download', 'download');

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'Portable Export',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                'Self-contained HTML/JS viewer for offline catalogue access',
                'Export full or partial catalogue (fonds, repository scope)',
                'Client-side search with FlexSearch',
                'Hierarchical tree navigation',
                'Digital object inline viewing',
                'Edit mode with researcher exchange format',
                'CLI and web UI for export generation',
                'Background processing with progress tracking',
            ],
        ];
    }
}
