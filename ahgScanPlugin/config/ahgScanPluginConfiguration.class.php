<?php

/**
 * ahgScanPlugin configuration.
 *
 * Watched-folder streaming ingest. Registers the scanManage admin module and
 * its routes. The scanner pipeline itself runs via the scan:watch CLI command
 * (lib/Commands), feeding ahgIngestPlugin's commit pipeline.
 */
class ahgScanPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Watched-folder streaming ingest with checksum dedupe';
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
        $enabledModules[] = 'scanManage';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $r = new \AtomFramework\Routing\RouteLoader('scanManage');

        // Dashboard + folder management
        $r->any('scan_index', '/admin/scan', 'index');
        $r->any('scan_folder_new', '/admin/scan/new', 'edit');
        $r->any('scan_folder_create', '/admin/scan/create', 'create');
        $r->any('scan_folder_edit', '/admin/scan/:id/edit', 'edit', ['id' => '\d+']);
        $r->any('scan_folder_update', '/admin/scan/:id/update', 'update', ['id' => '\d+']);
        $r->any('scan_folder_delete', '/admin/scan/:id/delete', 'delete', ['id' => '\d+']);
        $r->any('scan_folder_toggle', '/admin/scan/:id/toggle', 'toggle', ['id' => '\d+']);
        $r->any('scan_folder_run', '/admin/scan/:id/run', 'run', ['id' => '\d+']);
        $r->any('scan_folder_history', '/admin/scan/:id/history', 'history', ['id' => '\d+']);

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'Watched Folder Scanner',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                'Configurable watched folders bound to ingest sessions',
                'scan:watch CLI detects new files and feeds the ingest pipeline',
                'SHA-256 checksum dedupe',
                'Processed (archive) and failed (quarantine) disposition dirs',
                'Per-pass scan_event audit log',
                'Admin UI at /admin/scan',
            ],
        ];
    }
}
