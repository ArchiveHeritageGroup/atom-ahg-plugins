<?php

/**
 * ahgSharePointPlugin configuration.
 *
 * Microsoft 365 SharePoint integration. One-way SharePoint -> AtoM ingest.
 * Phased rollout: foundation (1) -> webhooks (2) -> discovery surfaces (3).
 *
 * Mirrored in heratio/packages/ahg-sharepoint/src/Providers/AhgSharePointServiceProvider.php.
 */
class ahgSharePointPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Microsoft 365 SharePoint integration: tenant config, drives, ingest, federated search';
    public static $version = '0.1.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'sharepoint';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));

        if (class_exists('\AtomFramework\Services\QueueJobRegistry')) {
            \AtomFramework\Services\QueueJobRegistry::register(
                'sharepoint:sync',
                \AtomFramework\Services\QueueCliTaskHandler::class
            );
            // Phase 2 handlers — registered up-front but only dispatched once Phase 2 ships.
            \AtomFramework\Services\QueueJobRegistry::register(
                'sharepoint:ingest-event',
                \AtomFramework\Services\QueueCliTaskHandler::class
            );
            \AtomFramework\Services\QueueJobRegistry::register(
                'sharepoint:renew-subscriptions',
                \AtomFramework\Services\QueueCliTaskHandler::class
            );
        }
    }

    public function addRoutes(sfEvent $event)
    {
        if (!class_exists('\AtomFramework\Routing\RouteLoader')) {
            return;
        }

        $r = new \AtomFramework\Routing\RouteLoader('sharepoint');

        // Phase 1 — foundation
        $r->any('sharepoint_index',         '/sharepoint',                              'index');
        $r->any('sharepoint_tenants',       '/sharepoint/tenants',                      'tenants');
        $r->any('sharepoint_tenant_edit',   '/sharepoint/tenants/:id',                  'tenantEdit',     ['id' => '\d+']);
        $r->any('sharepoint_tenant_test',   '/sharepoint/tenants/:id/test',             'tenantTest',     ['id' => '\d+']);
        $r->any('sharepoint_drives',        '/sharepoint/drives',                       'drives');
        $r->any('sharepoint_drive_browse',  '/sharepoint/drives/browse',                'driveBrowse');
        $r->any('sharepoint_drive_mapping', '/sharepoint/drives/:id/mapping',           'mapping',        ['id' => '\d+']);

        // Phase 2 — webhooks (routes registered up-front; actions return 503 until Phase 2 ships)
        $r->any('sharepoint_subscriptions', '/sharepoint/subscriptions',                'subscriptions');
        $r->any('sharepoint_events',        '/sharepoint/events',                       'events');
        $r->any('sharepoint_event_detail',  '/sharepoint/events/:id',                   'eventDetail',    ['id' => '\d+']);
        $r->any('sharepoint_webhook',       '/sharepoint/webhook',                      'webhook'); // PUBLIC, NO CSRF

        // Phase 2.B — manual push (SPFx → AtoM)
        $r->any('sharepoint_user_mappings', '/sharepoint/user-mappings',                'userMappings');
        $r->any('sharepoint_user_mapping_edit', '/sharepoint/user-mappings/:id',        'userMappingEdit', ['id' => '\d+']);
        $r->any('sharepoint_push_projection', '/api/v2/sharepoint/push/projection',     'pushProjection');
        $r->any('sharepoint_push',          '/api/v2/sharepoint/push',                  'push');
        $r->any('sharepoint_push_job',      '/api/v2/sharepoint/push/jobs/:id',         'pushJob', ['id' => '\d+']);

        // Phase 3 — discovery
        $r->any('sharepoint_federated',     '/sharepoint/federated-search',             'federatedSearch');

        // Phase 2 — v2 ingest plan: auto-ingest rules + per-drive mapping templates
        $r->any('sharepoint_rules',         '/sharepoint/rules',                        'rules');
        $r->any('sharepoint_rule_edit',     '/sharepoint/rules/edit',                   'ruleEdit');
        $r->any('sharepoint_rule_save',     '/sharepoint/rules/save',                   'ruleSave');
        $r->any('sharepoint_rule_delete',   '/sharepoint/rules/:id/delete',             'ruleDelete', ['id' => '\d+']);
        $r->any('sharepoint_rule_run',      '/sharepoint/rules/:id/run',                'ruleRun',    ['id' => '\d+']);
        $r->any('sharepoint_mappings',      '/sharepoint/mappings',                     'mappings');
        $r->any('sharepoint_mappings_save', '/sharepoint/mappings/save',                'mappingsSave');
        $r->any('sharepoint_mapping_template_delete', '/sharepoint/mappings/template/delete', 'mappingTemplateDelete');
        $r->any('sharepoint_columns',       '/sharepoint/columns',                      'columns');

        // Drives admin (replaces the Phase 1 stub)
        $r->any('sharepoint_drive_register', '/sharepoint/drives/register',             'driveRegister');
        $r->any('sharepoint_drive_save',     '/sharepoint/drives/save',                 'driveSave');
        $r->any('sharepoint_drive_delete',   '/sharepoint/drives/:id/delete',           'driveDelete', ['id' => '\d+']);
    }
}
