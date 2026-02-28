<?php

class ahgIntegrityPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Enterprise-grade automated integrity assurance: scheduled fixity verification, append-only ledger, dead-letter queue';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'integrity';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();
        $r = new \AtomFramework\Routing\RouteLoader('integrity');

        // Dashboard
        $r->any('integrity_index', '/admin/integrity', 'index');

        // Schedule management
        $r->any('integrity_schedules', '/admin/integrity/schedules', 'schedules');
        $r->any('integrity_schedule_edit', '/admin/integrity/schedule/edit', 'scheduleEdit');

        // Run history
        $r->any('integrity_runs', '/admin/integrity/runs', 'runs');
        $r->any('integrity_run_detail', '/admin/integrity/run/:id', 'runDetail', ['id' => '\d+']);

        // Ledger
        $r->any('integrity_ledger', '/admin/integrity/ledger', 'ledger');

        // Dead letter
        $r->any('integrity_dead_letter', '/admin/integrity/dead-letter', 'deadLetter');

        // Report
        $r->any('integrity_report', '/admin/integrity/report', 'report');

        // API endpoints
        $r->any('integrity_api_verify', '/api/integrity/verify', 'apiVerify');
        $r->any('integrity_api_run', '/api/integrity/run/:id', 'apiRun', ['id' => '\d+']);
        $r->any('integrity_api_schedule_toggle', '/api/integrity/schedule/:id/toggle', 'apiScheduleToggle', ['id' => '\d+']);
        $r->any('integrity_api_schedule_delete', '/api/integrity/schedule/:id/delete', 'apiScheduleDelete', ['id' => '\d+']);
        $r->any('integrity_api_dead_letter_action', '/api/integrity/dead-letter/:id/action', 'apiDeadLetterAction', ['id' => '\d+']);
        $r->any('integrity_api_stats', '/api/integrity/stats', 'apiStats');
        $r->any('integrity_api_run_schedule', '/api/integrity/schedule/:id/run', 'apiRunSchedule', ['id' => '\d+']);

        $r->register($routing);
    }
}
