<?php

class arAccessRequestPluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

		// Admin menu route
        $routing->prependRoute('accessRequest_index', new sfRoute(
            '/accessRequest',
            ['module' => 'arAccessRequest', 'action' => 'pending']
        ));
        // User routes - clearance requests
        $routing->prependRoute('access_request_new', new sfRoute(
            '/security/request-access',
            ['module' => 'arAccessRequest', 'action' => 'new']
        ));

        $routing->prependRoute('access_request_create', new sfRoute(
            '/security/request-access/create',
            ['module' => 'arAccessRequest', 'action' => 'create']
        ));

        // Object access request
        $routing->prependRoute('access_request_object', new sfRoute(
            '/security/request-object',
            ['module' => 'arAccessRequest', 'action' => 'requestObject']
        ));

        $routing->prependRoute('access_request_object_create', new sfRoute(
            '/security/request-object/create',
            ['module' => 'arAccessRequest', 'action' => 'createObjectRequest']
        ));

        // My requests
        $routing->prependRoute('access_request_my', new sfRoute(
            '/security/my-requests',
            ['module' => 'arAccessRequest', 'action' => 'myRequests']
        ));

        $routing->prependRoute('access_request_cancel', new sfRoute(
            '/security/request/:id/cancel',
            ['module' => 'arAccessRequest', 'action' => 'cancel'],
            ['id' => '\d+']
        ));

        // Approver routes
        $routing->prependRoute('access_request_pending', new sfRoute(
            '/security/access-requests',
            ['module' => 'arAccessRequest', 'action' => 'pending']
        ));

        $routing->prependRoute('access_request_view', new sfRoute(
            '/security/request/:id',
            ['module' => 'arAccessRequest', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('access_request_approve', new sfRoute(
            '/security/request/:id/approve',
            ['module' => 'arAccessRequest', 'action' => 'approve'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('access_request_deny', new sfRoute(
            '/security/request/:id/deny',
            ['module' => 'arAccessRequest', 'action' => 'deny'],
            ['id' => '\d+']
        ));

        // Admin routes
        $routing->prependRoute('access_request_approvers', new sfRoute(
            '/security/approvers',
            ['module' => 'arAccessRequest', 'action' => 'approvers']
        ));

        $routing->prependRoute('access_request_add_approver', new sfRoute(
            '/security/approvers/add',
            ['module' => 'arAccessRequest', 'action' => 'addApprover']
        ));

        $routing->prependRoute('access_request_remove_approver', new sfRoute(
            '/security/approvers/:id/remove',
            ['module' => 'arAccessRequest', 'action' => 'removeApprover'],
            ['id' => '\d+']
        ));
    }
}
