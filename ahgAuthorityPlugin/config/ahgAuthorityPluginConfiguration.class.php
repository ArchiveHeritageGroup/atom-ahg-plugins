<?php

/**
 * ahgAuthorityPlugin Configuration
 *
 * Comprehensive authority record enhancements:
 * - External authority linking (Wikidata, VIAF, ULAN, LCNAF) (#202)
 * - Completeness and quality dashboard (#206)
 * - NER-to-authority pipeline (#204)
 * - Relationship graph visualisation (#203)
 * - Merge/split workflow (#207)
 * - Bulk deduplication (#208)
 * - Structured occupations (#205)
 * - ISDF function linking (#201)
 * - EAC-CPF export enrichment (#209)
 * - Contact panel surfacing (#210)
 */
class ahgAuthorityPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Authority Records: External linking, completeness dashboard, NER pipeline, merge/split, dedup, occupations, functions, graph, EAC-CPF export';
    public static $version = '1.0.0';
    public static $category = 'authority';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgAuthorityPlugin/web/css/authority.css', 'last');
        $context->response->addJavascript('/plugins/ahgAuthorityPlugin/web/js/authority.js', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'authority';
        $enabledModules[] = 'authorityDedup';
        $enabledModules[] = 'authorityNer';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        // =====================================================================
        // AUTHORITY MODULE — Dashboard, Identifiers, Completeness, Graph,
        //                    Occupations, Functions, Merge/Split, Config, Contact
        // =====================================================================
        $r = new \AtomFramework\Routing\RouteLoader('authority');

        // Dashboard & Workqueue (#206)
        $r->any('ahg_authority_dashboard', '/admin/authority/dashboard', 'dashboard');
        $r->any('ahg_authority_workqueue', '/admin/authority/workqueue', 'workqueue');

        // External Identifiers (#202)
        $r->any('ahg_authority_identifiers', '/admin/authority/:actorId/identifiers', 'identifiers', ['actorId' => '\d+']);
        $r->any('ahg_authority_identifier_save', '/api/authority/identifier/save', 'apiIdentifierSave');
        $r->any('ahg_authority_identifier_delete', '/api/authority/identifier/:id/delete', 'apiIdentifierDelete', ['id' => '\d+']);
        $r->any('ahg_authority_identifier_verify', '/api/authority/identifier/:id/verify', 'apiIdentifierVerify', ['id' => '\d+']);
        $r->any('ahg_authority_wikidata_search', '/api/authority/wikidata/search', 'apiWikidataSearch');
        $r->any('ahg_authority_viaf_search', '/api/authority/viaf/search', 'apiViafSearch');
        $r->any('ahg_authority_ulan_search', '/api/authority/ulan/search', 'apiUlanSearch');
        $r->any('ahg_authority_lcnaf_search', '/api/authority/lcnaf/search', 'apiLcnafSearch');

        // Completeness (#206)
        $r->any('ahg_authority_completeness_recalc', '/api/authority/completeness/:actorId/recalc', 'apiCompletenessRecalc', ['actorId' => '\d+']);
        $r->any('ahg_authority_completeness_batch', '/api/authority/completeness/batch-assign', 'apiCompletenessBatchAssign');

        // Graph (#203)
        $r->any('ahg_authority_graph_data', '/api/authority/graph/:actorId', 'apiGraphData', ['actorId' => '\d+']);

        // Merge/Split (#207)
        $r->any('ahg_authority_merge', '/admin/authority/merge/:id', 'merge', ['id' => '\d+']);
        $r->any('ahg_authority_split', '/admin/authority/split/:id', 'split', ['id' => '\d+']);
        $r->any('ahg_authority_merge_preview', '/api/authority/merge/preview', 'apiMergePreview');
        $r->any('ahg_authority_merge_execute', '/api/authority/merge/execute', 'apiMergeExecute');
        $r->any('ahg_authority_split_execute', '/api/authority/split/execute', 'apiSplitExecute');

        // Occupations (#205)
        $r->any('ahg_authority_occupations', '/admin/authority/:actorId/occupations', 'occupations', ['actorId' => '\d+']);
        $r->any('ahg_authority_occupation_save', '/api/authority/occupation/save', 'apiOccupationSave');
        $r->any('ahg_authority_occupation_delete', '/api/authority/occupation/:id/delete', 'apiOccupationDelete', ['id' => '\d+']);

        // Functions (#201)
        $r->any('ahg_authority_functions', '/admin/authority/:actorId/functions', 'functions', ['actorId' => '\d+']);
        $r->any('ahg_authority_function_browse', '/admin/authority/functions/browse', 'functionBrowse');
        $r->any('ahg_authority_function_save', '/api/authority/function/save', 'apiFunctionSave');
        $r->any('ahg_authority_function_delete', '/api/authority/function/:id/delete', 'apiFunctionDelete', ['id' => '\d+']);

        // Contact (#210)
        $r->any('ahg_authority_contact', '/admin/authority/:actorId/contact', 'contact', ['actorId' => '\d+']);

        // EAC-CPF Export (#209)
        $r->any('ahg_authority_eac_export', '/api/authority/eac-cpf/:actorId', 'apiEacExport', ['actorId' => '\d+']);

        // Config
        $r->any('ahg_authority_config', '/admin/authority/config', 'config');

        $r->register($event->getSubject());

        // =====================================================================
        // DEDUP MODULE (#208)
        // =====================================================================
        $d = new \AtomFramework\Routing\RouteLoader('authorityDedup');

        $d->any('ahg_authority_dedup', '/admin/authority/dedup', 'index');
        $d->any('ahg_authority_dedup_scan', '/admin/authority/dedup/scan', 'scan');
        $d->any('ahg_authority_dedup_compare', '/admin/authority/dedup/compare/:id', 'compare', ['id' => '\d+']);
        $d->any('ahg_authority_dedup_dismiss', '/api/authority/dedup/dismiss/:id', 'apiDismiss', ['id' => '\d+']);
        $d->any('ahg_authority_dedup_merge', '/api/authority/dedup/merge/:id', 'apiMerge', ['id' => '\d+']);

        $d->register($event->getSubject());

        // =====================================================================
        // NER PIPELINE MODULE (#204)
        // =====================================================================
        $n = new \AtomFramework\Routing\RouteLoader('authorityNer');

        $n->any('ahg_authority_ner_pipeline', '/admin/authority/ner-pipeline', 'index');
        $n->any('ahg_authority_ner_create_stub', '/api/authority/ner/create-stub', 'apiCreateStub');
        $n->any('ahg_authority_ner_promote', '/api/authority/ner/:id/promote', 'apiPromote', ['id' => '\d+']);
        $n->any('ahg_authority_ner_reject', '/api/authority/ner/:id/reject', 'apiReject', ['id' => '\d+']);

        $n->register($event->getSubject());
    }
}
