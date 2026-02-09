<?php

/**
 * ahgNMMZPlugin - National Museums and Monuments of Zimbabwe Act [Chapter 25:11]
 *
 * Implements compliance features for Zimbabwe's heritage protection legislation:
 * - National Monuments registration and protection
 * - Antiquities management (objects > 100 years old)
 * - Export permit tracking
 * - Archaeological site protection
 * - Heritage impact assessments
 *
 * @package    ahgNMMZPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2025 The Archive and Heritage Group (Pty) Ltd
 */
class ahgNMMZPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'National Museums and Monuments of Zimbabwe Act [Chapter 25:11] compliance';
    public static $version = '1.0.0';

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            // Handle AhgNMMZ namespace
            if (strpos($class, 'AhgNMMZ\\') === 0) {
                $relativePath = str_replace('AhgNMMZ\\', '', $class);
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
        $context = $event->getSubject();
        $response = $context->getResponse();

        if ('nmmz' === $context->getModuleName()) {
            $response->addStylesheet('/plugins/ahgNMMZPlugin/css/nmmz.css', 'last');
            $response->addJavascript('/plugins/ahgNMMZPlugin/js/nmmz.js', 'last');
        }
    }

    public function initialize(): void
    {
        // Register autoloader for AhgNMMZ namespace
        $this->registerAutoloader();

        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'nmmz';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function routingLoadConfiguration(sfEvent $event): void
    {
        $routing = $event->getSubject();

        $nmmz = new \AtomFramework\Routing\RouteLoader('nmmz');

        // Dashboard
        $nmmz->any('nmmz_index', '/admin/nmmz', 'index');

        // National Monuments
        $nmmz->any('nmmz_monuments', '/admin/nmmz/monuments', 'monuments');
        $nmmz->any('nmmz_monument_create', '/admin/nmmz/monument/create', 'monumentCreate');
        $nmmz->any('nmmz_monument_view', '/admin/nmmz/monument/:id', 'monumentView', ['id' => '\d+']);

        // Antiquities
        $nmmz->any('nmmz_antiquities', '/admin/nmmz/antiquities', 'antiquities');
        $nmmz->any('nmmz_antiquity_create', '/admin/nmmz/antiquity/create', 'antiquityCreate');
        $nmmz->any('nmmz_antiquity_view', '/admin/nmmz/antiquity/:id', 'antiquityView', ['id' => '\d+']);

        // Export Permits
        $nmmz->any('nmmz_permits', '/admin/nmmz/permits', 'permits');
        $nmmz->any('nmmz_permit_create', '/admin/nmmz/permit/create', 'permitCreate');
        $nmmz->any('nmmz_permit_view', '/admin/nmmz/permit/:id', 'permitView', ['id' => '\d+']);

        // Archaeological Sites
        $nmmz->any('nmmz_sites', '/admin/nmmz/sites', 'sites');
        $nmmz->any('nmmz_site_create', '/admin/nmmz/site/create', 'siteCreate');
        $nmmz->any('nmmz_site_view', '/admin/nmmz/site/:id', 'siteView', ['id' => '\d+']);

        // Heritage Impact Assessments
        $nmmz->any('nmmz_hia', '/admin/nmmz/hia', 'hia');
        $nmmz->any('nmmz_hia_create', '/admin/nmmz/hia/create', 'hiaCreate');

        // Reports
        $nmmz->any('nmmz_reports', '/admin/nmmz/reports', 'reports');

        // Config
        $nmmz->any('nmmz_config', '/admin/nmmz/config', 'config');

        $nmmz->register($routing);
    }
}
