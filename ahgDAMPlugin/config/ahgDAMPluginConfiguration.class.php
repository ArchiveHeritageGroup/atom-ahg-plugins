<?php
class ahgDAMPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Digital Asset Management for AtoM';
    public static $version = '1.1.0';
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgDAMPlugin';
        $enabledModules[] = 'ahgDam';
        $enabledModules[] = 'damReports';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();
        error_log("DAM Plugin: Loading routes");
        
        // Base route (must be first - checked last)
        $routing->prependRoute('dam_index', new sfRoute('/dam', ['module' => 'ahgDam', 'action' => 'index']));
        
        // Catch-all slug route (checked second-to-last)
        $routing->prependRoute('dam_view', new QubitMetadataRoute('/dam/:slug', ['module' => 'ahgDAMPlugin', 'action' => 'index'], ['slug' => '[a-zA-Z0-9_-]+']));
        
        // Specific routes (added last so they are checked first)
        $routing->prependRoute('dam_browse', new sfRoute('/dam/browse', ['module' => 'ahgDam', 'action' => 'browse']));
        $routing->prependRoute('dam_lightbox', new sfRoute('/dam/lightbox', ['module' => 'ahgDam', 'action' => 'lightbox']));
        $routing->prependRoute('dam_reports', new sfRoute('/dam/reports', ['module' => 'damReports', 'action' => 'index']));
        $routing->prependRoute('dam_dashboard', new sfRoute('/dam/dashboard', ['module' => 'ahgDam', 'action' => 'dashboard']));
        $routing->prependRoute('dam_create', new sfRoute('/dam/create', ['module' => 'ahgDam', 'action' => 'create']));
        $routing->prependRoute('dam_bulk_create', new sfRoute('/dam/bulk', ['module' => 'ahgDam', 'action' => 'bulkCreate']));
        $routing->prependRoute('dam_bulk_create_alt', new sfRoute('/dam/bulkCreate', ['module' => 'ahgDam', 'action' => 'bulkCreate']));
        $routing->prependRoute('dam_extract_metadata', new sfRoute('/dam/extract/:id', ['module' => 'ahgDam', 'action' => 'extractMetadata']));
        $routing->prependRoute('dam_convert', new sfRoute('/dam/convert/:id', ['module' => 'ahgDam', 'action' => 'convert']));
        $routing->prependRoute('dam_edit_iptc', new sfRoute('/dam/iptc/:slug', ['module' => 'ahgDam', 'action' => 'editIptc']));
    }
}
