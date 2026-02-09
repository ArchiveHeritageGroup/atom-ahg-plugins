<?php

/**
 * ahgNAZPlugin - National Archives of Zimbabwe Act [Chapter 25:06]
 *
 * Implements compliance features for Zimbabwe's archival legislation:
 * - 25-year closure period for restricted records
 * - Research permit tracking (local/foreign)
 * - Records schedule classification
 * - Records transfer to NAZ workflow
 * - Protected records management
 *
 * @package    ahgNAZPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2025 The Archive and Heritage Group (Pty) Ltd
 */
class ahgNAZPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'National Archives of Zimbabwe Act [Chapter 25:06] compliance plugin';
    public static $version = '1.0.0';

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            // Handle AhgNAZ namespace
            if (strpos($class, 'AhgNAZ\\') === 0) {
                $relativePath = str_replace('AhgNAZ\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }
            return false;
        });
    }

    public function contextLoadFactories(sfEvent $event): void
    {
        // Load CSS and JS assets
        $context = $event->getSubject();
        $response = $context->getResponse();

        // Only load on NAZ module pages
        if ('naz' === $context->getModuleName()) {
            $response->addStylesheet('/plugins/ahgNAZPlugin/css/naz.css', 'last');
            $response->addJavascript('/plugins/ahgNAZPlugin/js/naz.js', 'last');
        }
    }

    public function initialize(): void
    {
        // Register autoloader for AhgNAZ namespace
        $this->registerAutoloader();

        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);

        // Enable NAZ module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'naz';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function routingLoadConfiguration(sfEvent $event): void
    {
        $routing = $event->getSubject();

        $naz = new \AtomFramework\Routing\RouteLoader('naz');

        // NAZ Dashboard
        $naz->any('naz_index', '/admin/naz', 'index');

        // Closure periods
        $naz->any('naz_closures', '/admin/naz/closures', 'closures');
        $naz->any('naz_closure_create', '/admin/naz/closure/create', 'closureCreate');
        $naz->any('naz_closure_edit', '/admin/naz/closure/:id/edit', 'closureEdit', ['id' => '\d+']);

        // Research permits
        $naz->any('naz_permits', '/admin/naz/permits', 'permits');
        $naz->any('naz_permit_create', '/admin/naz/permit/create', 'permitCreate');
        $naz->any('naz_permit_view', '/admin/naz/permit/:id', 'permitView', ['id' => '\d+']);

        // Researchers
        $naz->any('naz_researchers', '/admin/naz/researchers', 'researchers');
        $naz->any('naz_researcher_create', '/admin/naz/researcher/create', 'researcherCreate');
        $naz->any('naz_researcher_view', '/admin/naz/researcher/:id', 'researcherView', ['id' => '\d+']);

        // Records schedules
        $naz->any('naz_schedules', '/admin/naz/schedules', 'schedules');
        $naz->any('naz_schedule_create', '/admin/naz/schedule/create', 'scheduleCreate');
        $naz->any('naz_schedule_view', '/admin/naz/schedule/:id', 'scheduleView', ['id' => '\d+']);

        // Transfers
        $naz->any('naz_transfers', '/admin/naz/transfers', 'transfers');
        $naz->any('naz_transfer_create', '/admin/naz/transfer/create', 'transferCreate');
        $naz->any('naz_transfer_view', '/admin/naz/transfer/:id', 'transferView', ['id' => '\d+']);

        // Protected records
        $naz->any('naz_protected', '/admin/naz/protected', 'protectedRecords');

        // Reports
        $naz->any('naz_reports', '/admin/naz/reports', 'reports');

        // Config
        $naz->any('naz_config', '/admin/naz/config', 'config');

        // Direct access routes (without /admin prefix)
        $naz->any('naz_index_direct', '/naz', 'index');
        $naz->any('naz_closures_direct', '/naz/closures', 'closures');
        $naz->any('naz_permits_direct', '/naz/permits', 'permits');
        $naz->any('naz_researchers_direct', '/naz/researchers', 'researchers');
        $naz->any('naz_schedules_direct', '/naz/schedules', 'schedules');
        $naz->any('naz_transfers_direct', '/naz/transfers', 'transfers');
        $naz->any('naz_protected_direct', '/naz/protected', 'protectedRecords');
        $naz->any('naz_reports_direct', '/naz/reports', 'reports');
        $naz->any('naz_config_direct', '/naz/config', 'config');

        $naz->register($routing);
    }
}
