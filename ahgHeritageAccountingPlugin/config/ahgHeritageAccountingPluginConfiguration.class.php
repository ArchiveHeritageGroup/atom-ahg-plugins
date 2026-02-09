<?php
class ahgHeritageAccountingPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Heritage asset financial accounting with multi-standard support (GRAP 103, FRS 102, GASB 34, PSAS 3150).';
    public static $version = '1.1.0';

    public function initialize(): void
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);
    }

    public function configureRouting(sfEvent $event): void
    {
        $routing = $event->getSubject();

        // heritageAccounting module routes
        $accounting = new \AtomFramework\Routing\RouteLoader('heritageAccounting');

        // Dashboard
        $accounting->any('heritage_dashboard', '/heritage/dashboard', 'dashboard');

        // Settings
        $accounting->any('heritage_settings', '/heritage/settings', 'settings');

        // Asset CRUD
        $accounting->any('heritage_browse', '/heritage/browse', 'browse');
        $accounting->any('heritage_add', '/heritage/add', 'add');
        $accounting->any('heritage_view', '/heritage/:id', 'view', ['id' => '\d+']);
        $accounting->any('heritage_edit', '/heritage/:id/edit', 'edit', ['id' => '\d+']);

        // Valuation
        $accounting->any('heritage_valuation_add', '/heritage/:id/valuation/add', 'addValuation', ['id' => '\d+']);

        // Impairment
        $accounting->any('heritage_impairment_add', '/heritage/:id/impairment/add', 'addImpairment', ['id' => '\d+']);

        // Movement
        $accounting->any('heritage_movement_add', '/heritage/:id/movement/add', 'addMovement', ['id' => '\d+']);

        // Journal
        $accounting->any('heritage_journal_add', '/heritage/:id/journal/add', 'addJournal', ['id' => '\d+']);

        // Object-linked routes
        $accounting->any('heritage_view_by_object', '/heritage/object/:slug', 'viewByObject');
        $accounting->any('heritage_edit_by_object', '/heritage/object/:slug/edit', 'editByObject');

        $accounting->register($routing);

        // heritageReport module routes
        $reports = new \AtomFramework\Routing\RouteLoader('heritageReport');

        $reports->any('heritage_reports', '/heritage/reports', 'index');
        $reports->any('heritage_report_asset_register', '/heritage/report/asset-register', 'assetRegister');
        $reports->any('heritage_report_valuation', '/heritage/report/valuation', 'valuation');
        $reports->any('heritage_report_movement', '/heritage/report/movement', 'movement');

        $reports->register($routing);

        // grapCompliance module routes
        $grap = new \AtomFramework\Routing\RouteLoader('grapCompliance');

        $grap->any('grap_dashboard', '/grap/dashboard', 'dashboard');
        $grap->any('grap_check', '/grap/check/:id', 'check', ['id' => '\d+']);
        $grap->any('grap_batch_check', '/grap/batch-check', 'batchCheck');
        $grap->any('grap_national_treasury', '/grap/national-treasury-report', 'nationalTreasuryReport');

        $grap->register($routing);

        // heritageApi module routes
        $api = new \AtomFramework\Routing\RouteLoader('heritageApi');

        $api->any('heritage_api_asset', '/api/heritage/asset/:id', 'asset', ['id' => '\d+']);
        $api->any('heritage_api_actor_autocomplete', '/api/heritage/actor-autocomplete', 'actorAutocomplete');
        $api->any('heritage_api_autocomplete', '/api/heritage/autocomplete', 'autocomplete');
        $api->any('heritage_api_summary', '/api/heritage/summary', 'summary');

        $api->register($routing);
    }
}
