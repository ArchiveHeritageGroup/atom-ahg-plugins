<?php
class ahgHeritageAccountingPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Heritage asset financial accounting with multi-standard support (GRAP 103, FRS 102, GASB 34, PSAS 3150).';
    public static $version = '1.0.0';

    public function initialize(): void
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);
    }

    public function configureRouting(sfEvent $event): void
    {
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('heritage_dashboard', new sfRoute(
            '/heritage/dashboard',
            ['module' => 'heritageAccounting', 'action' => 'dashboard']
        ));

        // Settings
        $routing->prependRoute('heritage_settings', new sfRoute(
            '/heritage/settings',
            ['module' => 'heritageAccounting', 'action' => 'settings']
        ));

        // Asset CRUD
        $routing->prependRoute('heritage_browse', new sfRoute(
            '/heritage/browse',
            ['module' => 'heritageAccounting', 'action' => 'browse']
        ));

        $routing->prependRoute('heritage_add', new sfRoute(
            '/heritage/add',
            ['module' => 'heritageAccounting', 'action' => 'add']
        ));

        $routing->prependRoute('heritage_view', new sfRoute(
            '/heritage/:id',
            ['module' => 'heritageAccounting', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('heritage_edit', new sfRoute(
            '/heritage/:id/edit',
            ['module' => 'heritageAccounting', 'action' => 'edit'],
            ['id' => '\d+']
        ));

        // Valuation
        $routing->prependRoute('heritage_valuation_add', new sfRoute(
            '/heritage/:id/valuation/add',
            ['module' => 'heritageAccounting', 'action' => 'addValuation'],
            ['id' => '\d+']
        ));

        // Impairment
        $routing->prependRoute('heritage_impairment_add', new sfRoute(
            '/heritage/:id/impairment/add',
            ['module' => 'heritageAccounting', 'action' => 'addImpairment'],
            ['id' => '\d+']
        ));

        // Movement
        $routing->prependRoute('heritage_movement_add', new sfRoute(
            '/heritage/:id/movement/add',
            ['module' => 'heritageAccounting', 'action' => 'addMovement'],
            ['id' => '\d+']
        ));

        // Journal
        $routing->prependRoute('heritage_journal_add', new sfRoute(
            '/heritage/:id/journal/add',
            ['module' => 'heritageAccounting', 'action' => 'addJournal'],
            ['id' => '\d+']
        ));

        // Reports
        $routing->prependRoute('heritage_reports', new sfRoute(
            '/heritage/reports',
            ['module' => 'heritageReport', 'action' => 'index']
        ));

        $routing->prependRoute('heritage_report_asset_register', new sfRoute(
            '/heritage/report/asset-register',
            ['module' => 'heritageReport', 'action' => 'assetRegister']
        ));

        $routing->prependRoute('heritage_report_valuation', new sfRoute(
            '/heritage/report/valuation',
            ['module' => 'heritageReport', 'action' => 'valuation']
        ));

        $routing->prependRoute('heritage_report_movement', new sfRoute(
            '/heritage/report/movement',
            ['module' => 'heritageReport', 'action' => 'movement']
        ));

        // GRAP 103 Compliance
        $routing->prependRoute('grap_dashboard', new sfRoute(
            '/grap/dashboard',
            ['module' => 'grapCompliance', 'action' => 'dashboard']
        ));

        $routing->prependRoute('grap_check', new sfRoute(
            '/grap/check/:id',
            ['module' => 'grapCompliance', 'action' => 'check'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('grap_batch_check', new sfRoute(
            '/grap/batch-check',
            ['module' => 'grapCompliance', 'action' => 'batchCheck']
        ));

        $routing->prependRoute('grap_national_treasury', new sfRoute(
            '/grap/national-treasury-report',
            ['module' => 'grapCompliance', 'action' => 'nationalTreasuryReport']
        ));

        // API
        $routing->prependRoute('heritage_api_asset', new sfRoute(
            '/api/heritage/asset/:id',
            ['module' => 'heritageApi', 'action' => 'asset'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('heritage_api_summary', new sfRoute(
            '/api/heritage/summary',
            ['module' => 'heritageApi', 'action' => 'summary']
        ));
    }
}
