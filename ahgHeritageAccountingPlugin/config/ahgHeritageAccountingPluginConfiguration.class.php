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

        // heritageAdmin module routes (#262)
        //
        // This module had 16 actions and 16 templates but was never routed, so
        // regions, accounting standards and compliance rules were unreachable.
        //
        // State-changing actions are registered with post() rather than any().
        // That is defence in depth alongside requireSafePost() in the action:
        // the router refuses a GET before the action runs, and the action still
        // validates the CSRF token in case a route is ever loosened.
        $admin = new \AtomFramework\Routing\RouteLoader('heritageAdmin');

        $admin->any('heritage_admin', '/heritage/admin', 'index');

        // Regions
        $admin->any('heritage_admin_regions', '/heritage/admin/regions', 'regions');
        $admin->any('heritage_admin_region_info', '/heritage/admin/region/:region', 'regionInfo');
        // Under /regions/ (plural), because /heritage/admin/region/:region would
        // otherwise match these verbs as a region code and dispatch regionInfo.
        $admin->post('heritage_admin_region_install', '/heritage/admin/regions/install', 'regionInstall');
        $admin->post('heritage_admin_region_uninstall', '/heritage/admin/regions/uninstall', 'regionUninstall');
        $admin->post('heritage_admin_region_activate', '/heritage/admin/regions/activate', 'regionSetActive');

        // Accounting standards
        $admin->any('heritage_admin_standards', '/heritage/admin/standards', 'standardList');
        $admin->any('heritage_admin_standard_add', '/heritage/admin/standard/add', 'standardAdd');
        $admin->any('heritage_admin_standard_edit', '/heritage/admin/standard/:id/edit', 'standardEdit', ['id' => '\d+']);
        $admin->post('heritage_admin_standard_toggle', '/heritage/admin/standard/toggle', 'standardToggle');
        $admin->post('heritage_admin_standard_delete', '/heritage/admin/standard/delete', 'standardDelete');

        // Compliance rules
        $admin->any('heritage_admin_rules', '/heritage/admin/rules', 'ruleList');
        $admin->any('heritage_admin_rule_add', '/heritage/admin/rule/add', 'ruleAdd');
        $admin->any('heritage_admin_rule_edit', '/heritage/admin/rule/:id/edit', 'ruleEdit', ['id' => '\d+']);
        $admin->post('heritage_admin_rule_toggle', '/heritage/admin/rule/toggle', 'ruleToggle');
        $admin->post('heritage_admin_rule_delete', '/heritage/admin/rule/delete', 'ruleDelete');

        $admin->register($routing);

        // heritageApi module routes
        $api = new \AtomFramework\Routing\RouteLoader('heritageApi');

        $api->any('heritage_api_asset', '/api/heritage/asset/:id', 'asset', ['id' => '\d+']);
        $api->any('heritage_api_actor_autocomplete', '/api/heritage/actor-autocomplete', 'actorAutocomplete');
        $api->any('heritage_api_autocomplete', '/api/heritage/autocomplete', 'autocomplete');
        $api->any('heritage_api_summary', '/api/heritage/summary', 'summary');

        $api->register($routing);
    }
}
