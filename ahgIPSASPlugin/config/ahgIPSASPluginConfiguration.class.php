<?php

/**
 * ahgIPSASPlugin - IPSAS Heritage Asset Management
 *
 * Implements International Public Sector Accounting Standards for heritage assets:
 * - IPSAS 17 (Property, Plant & Equipment) for heritage assets
 * - IPSAS 45 (expected future standard for heritage)
 * - Similar to SA GRAP 103 heritage asset management
 *
 * Features:
 * - Heritage asset valuation (historical cost, fair value, nominal)
 * - Depreciation tracking (or non-depreciation policy)
 * - Impairment assessment
 * - Insurance and risk management
 * - Asset register with financial data
 *
 * @package    ahgIPSASPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2025 The Archive and Heritage Group (Pty) Ltd
 */
class ahgIPSASPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'IPSAS Heritage Asset Management - International public sector accounting for heritage';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event): void
    {
        $context = $event->getSubject();
        $response = $context->getResponse();

        if ('ipsas' === $context->getModuleName()) {
            $response->addStylesheet('/plugins/ahgIPSASPlugin/css/ipsas.css', 'last');
            $response->addJavascript('/plugins/ahgIPSASPlugin/js/ipsas.js', 'last');
        }
    }

    public function initialize(): void
    {
        $this->registerAutoloader();
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ipsas';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    protected function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgIPSAS\\') === 0) {
                $relativePath = str_replace('AhgIPSAS\\', '', $class);
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

    public function routingLoadConfiguration(sfEvent $event): void
    {
        $router = new \AtomFramework\Routing\RouteLoader('ipsas');

        // Dashboard
        $router->any('ipsas_index', '/admin/ipsas', 'index');

        // Asset Register
        $router->any('ipsas_assets', '/admin/ipsas/assets', 'assets');
        $router->any('ipsas_asset_create', '/admin/ipsas/asset/create', 'assetCreate');
        $router->any('ipsas_asset_view', '/admin/ipsas/asset/:id', 'assetView', ['id' => '\d+']);
        $router->any('ipsas_asset_edit', '/admin/ipsas/asset/:id/edit', 'assetEdit', ['id' => '\d+']);

        // Valuations
        $router->any('ipsas_valuations', '/admin/ipsas/valuations', 'valuations');
        $router->any('ipsas_valuation_create', '/admin/ipsas/valuation/create', 'valuationCreate');

        // Impairments
        $router->any('ipsas_impairments', '/admin/ipsas/impairments', 'impairments');

        // Insurance
        $router->any('ipsas_insurance', '/admin/ipsas/insurance', 'insurance');

        // Reports
        $router->any('ipsas_reports', '/admin/ipsas/reports', 'reports');

        // Financial Year
        $router->any('ipsas_financial_year', '/admin/ipsas/financial-year', 'financialYear');

        // Config
        $router->any('ipsas_config', '/admin/ipsas/config', 'config');

        $router->register($event->getSubject());
    }
}
