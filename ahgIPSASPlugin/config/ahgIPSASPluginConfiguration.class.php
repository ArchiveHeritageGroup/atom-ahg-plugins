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
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('ipsas_index', new sfRoute(
            '/admin/ipsas',
            ['module' => 'ipsas', 'action' => 'index']
        ));

        // Asset Register
        $routing->prependRoute('ipsas_assets', new sfRoute(
            '/admin/ipsas/assets',
            ['module' => 'ipsas', 'action' => 'assets']
        ));
        $routing->prependRoute('ipsas_asset_create', new sfRoute(
            '/admin/ipsas/asset/create',
            ['module' => 'ipsas', 'action' => 'assetCreate']
        ));
        $routing->prependRoute('ipsas_asset_view', new sfRoute(
            '/admin/ipsas/asset/:id',
            ['module' => 'ipsas', 'action' => 'assetView'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('ipsas_asset_edit', new sfRoute(
            '/admin/ipsas/asset/:id/edit',
            ['module' => 'ipsas', 'action' => 'assetEdit'],
            ['id' => '\d+']
        ));

        // Valuations
        $routing->prependRoute('ipsas_valuations', new sfRoute(
            '/admin/ipsas/valuations',
            ['module' => 'ipsas', 'action' => 'valuations']
        ));
        $routing->prependRoute('ipsas_valuation_create', new sfRoute(
            '/admin/ipsas/valuation/create',
            ['module' => 'ipsas', 'action' => 'valuationCreate']
        ));

        // Impairments
        $routing->prependRoute('ipsas_impairments', new sfRoute(
            '/admin/ipsas/impairments',
            ['module' => 'ipsas', 'action' => 'impairments']
        ));

        // Insurance
        $routing->prependRoute('ipsas_insurance', new sfRoute(
            '/admin/ipsas/insurance',
            ['module' => 'ipsas', 'action' => 'insurance']
        ));

        // Reports
        $routing->prependRoute('ipsas_reports', new sfRoute(
            '/admin/ipsas/reports',
            ['module' => 'ipsas', 'action' => 'reports']
        ));

        // Financial Year
        $routing->prependRoute('ipsas_financial_year', new sfRoute(
            '/admin/ipsas/financial-year',
            ['module' => 'ipsas', 'action' => 'financialYear']
        ));

        // Config
        $routing->prependRoute('ipsas_config', new sfRoute(
            '/admin/ipsas/config',
            ['module' => 'ipsas', 'action' => 'config']
        ));
    }
}
