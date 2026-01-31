<?php

class ahgWorkflowPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Configurable approval workflow system for archival submissions';
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
        $enabledModules[] = 'workflow';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('workflow_dashboard', new sfRoute(
            '/workflow',
            ['module' => 'workflow', 'action' => 'dashboard']
        ));
        $routing->prependRoute('workflow_index', new sfRoute(
            '/workflow/dashboard',
            ['module' => 'workflow', 'action' => 'dashboard']
        ));

        // My Tasks
        $routing->prependRoute('workflow_my_tasks', new sfRoute(
            '/workflow/my-tasks',
            ['module' => 'workflow', 'action' => 'myTasks']
        ));

        // Task Pool
        $routing->prependRoute('workflow_pool', new sfRoute(
            '/workflow/pool',
            ['module' => 'workflow', 'action' => 'pool']
        ));

        // Task actions
        $routing->prependRoute('workflow_claim_task', new sfRoute(
            '/workflow/task/:id/claim',
            ['module' => 'workflow', 'action' => 'claimTask'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_release_task', new sfRoute(
            '/workflow/task/:id/release',
            ['module' => 'workflow', 'action' => 'releaseTask'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_approve_task', new sfRoute(
            '/workflow/task/:id/approve',
            ['module' => 'workflow', 'action' => 'approveTask'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_reject_task', new sfRoute(
            '/workflow/task/:id/reject',
            ['module' => 'workflow', 'action' => 'rejectTask'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_view_task', new sfRoute(
            '/workflow/task/:id',
            ['module' => 'workflow', 'action' => 'viewTask'],
            ['id' => '\d+']
        ));

        // History
        $routing->prependRoute('workflow_history', new sfRoute(
            '/workflow/history',
            ['module' => 'workflow', 'action' => 'history']
        ));
        $routing->prependRoute('workflow_object_history', new sfRoute(
            '/workflow/history/:object_id',
            ['module' => 'workflow', 'action' => 'objectHistory'],
            ['object_id' => '\d+']
        ));

        // Admin: Workflow configuration
        $routing->prependRoute('workflow_admin', new sfRoute(
            '/workflow/admin',
            ['module' => 'workflow', 'action' => 'admin']
        ));
        $routing->prependRoute('workflow_create', new sfRoute(
            '/workflow/admin/create',
            ['module' => 'workflow', 'action' => 'createWorkflow']
        ));
        $routing->prependRoute('workflow_edit', new sfRoute(
            '/workflow/admin/edit/:id',
            ['module' => 'workflow', 'action' => 'editWorkflow'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_delete', new sfRoute(
            '/workflow/admin/delete/:id',
            ['module' => 'workflow', 'action' => 'deleteWorkflow'],
            ['id' => '\d+']
        ));

        // Step management
        $routing->prependRoute('workflow_add_step', new sfRoute(
            '/workflow/admin/:workflow_id/step/add',
            ['module' => 'workflow', 'action' => 'addStep'],
            ['workflow_id' => '\d+']
        ));
        $routing->prependRoute('workflow_edit_step', new sfRoute(
            '/workflow/admin/step/:id/edit',
            ['module' => 'workflow', 'action' => 'editStep'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_delete_step', new sfRoute(
            '/workflow/admin/step/:id/delete',
            ['module' => 'workflow', 'action' => 'deleteStep'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('workflow_reorder_steps', new sfRoute(
            '/workflow/admin/:workflow_id/steps/reorder',
            ['module' => 'workflow', 'action' => 'reorderSteps'],
            ['workflow_id' => '\d+']
        ));

        // Start workflow (triggered when submitting item)
        $routing->prependRoute('workflow_start', new sfRoute(
            '/workflow/start/:object_id',
            ['module' => 'workflow', 'action' => 'startWorkflow'],
            ['object_id' => '\d+']
        ));

        // API endpoints for AJAX
        $routing->prependRoute('workflow_api_stats', new sfRoute(
            '/workflow/api/stats',
            ['module' => 'workflow', 'action' => 'apiStats']
        ));
        $routing->prependRoute('workflow_api_tasks', new sfRoute(
            '/workflow/api/tasks',
            ['module' => 'workflow', 'action' => 'apiTasks']
        ));
    }
}
