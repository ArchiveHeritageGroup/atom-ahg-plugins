<?php

class ahgAccessRequestPluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('accessRequest');

        // Admin menu route
        $router->any('accessRequest_index', '/accessRequest', 'pending');

        // User routes - clearance requests
        $router->any('access_request_new', '/security/request-access', 'new');
        $router->any('access_request_create', '/security/request-access/create', 'create');

        // Object access request
        $router->any('access_request_object', '/security/request-object', 'requestObject');
        $router->any('access_request_object_create', '/security/request-object/create', 'createObjectRequest');

        // My requests
        $router->any('access_request_my', '/security/my-requests', 'myRequests');
        $router->any('access_request_cancel', '/security/request/:id/cancel', 'cancel', ['id' => '\d+']);

        // Approver routes
        $router->any('access_request_pending', '/security/access-requests', 'pending');
        $router->any('access_request_view', '/security/request/:id', 'view', ['id' => '\d+']);
        $router->any('access_request_approve', '/security/request/:id/approve', 'approve', ['id' => '\d+']);
        $router->any('access_request_deny', '/security/request/:id/deny', 'deny', ['id' => '\d+']);

        // Admin routes
        $router->any('access_request_approvers', '/security/approvers', 'approvers');
        $router->any('access_request_add_approver', '/security/approvers/add', 'addApprover');
        $router->any('access_request_remove_approver', '/security/approvers/:id/remove', 'removeApprover', ['id' => '\d+']);

        $router->register($event->getSubject());
    }
}
