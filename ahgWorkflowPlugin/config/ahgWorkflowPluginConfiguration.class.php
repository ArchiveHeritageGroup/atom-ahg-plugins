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
        $router = new \AtomFramework\Routing\RouteLoader('workflow');

        // Dashboard
        $router->any('workflow_dashboard', '/workflow', 'dashboard');
        $router->any('workflow_index', '/workflow/dashboard', 'dashboard');

        // My Tasks
        $router->any('workflow_my_tasks', '/workflow/my-tasks', 'myTasks');

        // Task Pool
        $router->any('workflow_pool', '/workflow/pool', 'pool');

        // Task actions
        $router->any('workflow_claim_task', '/workflow/task/:id/claim', 'claimTask', ['id' => '\d+']);
        $router->any('workflow_release_task', '/workflow/task/:id/release', 'releaseTask', ['id' => '\d+']);
        $router->any('workflow_approve_task', '/workflow/task/:id/approve', 'approveTask', ['id' => '\d+']);
        $router->any('workflow_reject_task', '/workflow/task/:id/reject', 'rejectTask', ['id' => '\d+']);
        $router->any('workflow_view_task', '/workflow/task/:id', 'viewTask', ['id' => '\d+']);

        // History
        $router->any('workflow_history', '/workflow/history', 'history');
        $router->any('workflow_object_history', '/workflow/history/:object_id', 'objectHistory', ['object_id' => '\d+']);

        // Admin: Workflow configuration
        $router->any('workflow_admin', '/workflow/admin', 'admin');
        $router->any('workflow_create', '/workflow/admin/create', 'createWorkflow');
        $router->any('workflow_edit', '/workflow/admin/edit/:id', 'editWorkflow', ['id' => '\d+']);
        $router->any('workflow_delete', '/workflow/admin/delete/:id', 'deleteWorkflow', ['id' => '\d+']);

        // Step management
        $router->any('workflow_add_step', '/workflow/admin/:workflow_id/step/add', 'addStep', ['workflow_id' => '\d+']);
        $router->any('workflow_edit_step', '/workflow/admin/step/:id/edit', 'editStep', ['id' => '\d+']);
        $router->any('workflow_delete_step', '/workflow/admin/step/:id/delete', 'deleteStep', ['id' => '\d+']);
        $router->any('workflow_reorder_steps', '/workflow/admin/:workflow_id/steps/reorder', 'reorderSteps', ['workflow_id' => '\d+']);

        // Start workflow (triggered when submitting item)
        $router->any('workflow_start', '/workflow/start/:object_id', 'startWorkflow', ['object_id' => '\d+']);

        // API endpoints for AJAX
        $router->any('workflow_api_stats', '/workflow/api/stats', 'apiStats');
        $router->any('workflow_api_tasks', '/workflow/api/tasks', 'apiTasks');

        $router->register($event->getSubject());
    }
}
