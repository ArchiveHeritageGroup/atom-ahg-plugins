<?php
class ahgDAMPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Digital Asset Management for AtoM';
    public static $version = '1.2.0';
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'dam';
        $enabledModules[] = 'damReports';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Base route (must be first - checked last)
        $routing->prependRoute('dam_index', new sfRoute('/dam', ['module' => 'dam', 'action' => 'dashboard']));

        // Catch-all slug route (checked second-to-last)
        $routing->prependRoute('dam_view', new AhgMetadataRoute('/dam/:slug', ['module' => 'dam', 'action' => 'index'], ['slug' => '[a-zA-Z0-9_-]+']));

        // Specific routes (added last so they are checked first)
        $routing->prependRoute('dam_browse', new sfRoute('/dam/browse', ['module' => 'dam', 'action' => 'browse']));
        $routing->prependRoute('dam_lightbox', new sfRoute('/dam/lightbox', ['module' => 'dam', 'action' => 'lightbox']));
        $routing->prependRoute('dam_reports', new sfRoute('/dam/reports', ['module' => 'damReports', 'action' => 'index']));
        $routing->prependRoute('dam_dashboard', new sfRoute('/dam/dashboard', ['module' => 'dam', 'action' => 'dashboard']));
        $routing->prependRoute('dam_create', new sfRoute('/dam/create', ['module' => 'dam', 'action' => 'create']));
        $routing->prependRoute('dam_bulk_create', new sfRoute('/dam/bulk', ['module' => 'dam', 'action' => 'bulkCreate']));
        $routing->prependRoute('dam_bulk_create_alt', new sfRoute('/dam/bulkCreate', ['module' => 'dam', 'action' => 'bulkCreate']));
        $routing->prependRoute('dam_extract_metadata', new sfRoute('/dam/extract/:id', ['module' => 'dam', 'action' => 'extractMetadata']));
        $routing->prependRoute('dam_convert', new sfRoute('/dam/convert/:id', ['module' => 'dam', 'action' => 'convert']));
        $routing->prependRoute('dam_edit_iptc', new sfRoute('/dam/iptc/:slug', ['module' => 'dam', 'action' => 'editIptc']));
    }
}
