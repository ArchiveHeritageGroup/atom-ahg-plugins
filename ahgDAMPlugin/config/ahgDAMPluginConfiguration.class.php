<?php
class ahgDAMPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Digital Asset Management for AtoM';
    public static $version = '1.2.0';
    public function initialize()
    {
        // Remove this plugin's rows for a deleted description.
        $this->dispatcher->connect('ahg.information_object.deleted', [$this, 'onDescriptionDeleted']);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'dam';
        $enabledModules[] = 'damReports';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
    public function loadRoutes(sfEvent $event)
    {
        // dam module routes
        $dam = new \AtomFramework\Routing\RouteLoader('dam');

        // Base route (must be first - checked last)
        $dam->any('dam_index', '/dam', 'dashboard');

        // Catch-all slug route - use sfRoute (NOT AhgMetadataRoute) to prevent URL generation conflicts
        $dam->any('dam_view', '/dam/:slug', 'index', ['slug' => '[a-zA-Z0-9_-]+']);

        // Specific routes (added last so they are checked first)
        $dam->any('dam_browse', '/dam/browse', 'browse');
        $dam->any('dam_dashboard', '/dam/dashboard', 'dashboard');
        $dam->any('dam_create', '/dam/create', 'create');
        $dam->any('dam_bulk_create', '/dam/bulk', 'bulkCreate');
        $dam->any('dam_bulk_create_alt', '/dam/bulkCreate', 'bulkCreate');
        $dam->any('dam_extract_metadata', '/dam/extract/:id', 'extractMetadata');
        $dam->any('dam_convert', '/dam/convert/:id', 'convert');
        $dam->any('dam_edit_iptc', '/dam/iptc/:slug', 'editIptc');

        $dam->register($event->getSubject());

        // damReports module routes
        $reports = new \AtomFramework\Routing\RouteLoader('damReports');
        $reports->any('dam_reports', '/dam/reports', 'index');
        $reports->register($event->getSubject());
    }

    /**
     * A description was deleted. Its watermark settings. The watermark log stays.
     */
    public function onDescriptionDeleted(sfEvent $event)
    {
        $id = (int) ($event['id'] ?? 0);
        if ($id <= 0) {
            return;
        }
        try {
            if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('object_watermark_setting')) {
                \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')->where('object_id', $id)->delete();
            }
            if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('custom_watermark')) {
                \Illuminate\Database\Capsule\Manager::table('custom_watermark')->where('object_id', $id)->delete();
            }
        } catch (\Throwable $e) {
            error_log('ahgDAMPlugin.delete_cleanup_failed: '.$id.': '.$e->getMessage());
        }
    }
}
