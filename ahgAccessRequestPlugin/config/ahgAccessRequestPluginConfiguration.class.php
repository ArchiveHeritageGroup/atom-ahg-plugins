<?php

class ahgAccessRequestPluginConfiguration extends sfPluginConfiguration
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
            ['module' => 'ahgAccessRequest', 'action' => 'pending']
        ));
        // User routes - clearance requests
        $routing->prependRoute('access_request_new', new sfRoute(
            '/security/request-access',
            ['module' => 'ahgAccessRequest', 'action' => 'new']
        ));

        $routing->prependRoute('access_request_create', new sfRoute(
            '/security/request-access/create',
            ['module' => 'ahgAccessRequest', 'action' => 'create']
        ));

        // Object access request
        $routing->prependRoute('access_request_object', new sfRoute(
            '/security/request-object',
            ['module' => 'ahgAccessRequest', 'action' => 'requestObject']
        ));

        $routing->prependRoute('access_request_object_create', new sfRoute(
            '/security/request-object/create',
            ['module' => 'ahgAccessRequest', 'action' => 'createObjectRequest']
        ));

        // My requests
        $routing->prependRoute('access_request_my', new sfRoute(
            '/security/my-requests',
            ['module' => 'ahgAccessRequest', 'action' => 'myRequests']
        ));

        $routing->prependRoute('access_request_cancel', new sfRoute(
            '/security/request/:id/cancel',
            ['module' => 'ahgAccessRequest', 'action' => 'cancel'],
            ['id' => '\d+']
        ));

        // Approver routes
        $routing->prependRoute('access_request_pending', new sfRoute(
            '/security/access-requests',
            ['module' => 'ahgAccessRequest', 'action' => 'pending']
        ));

        $routing->prependRoute('access_request_view', new sfRoute(
            '/security/request/:id',
            ['module' => 'ahgAccessRequest', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('access_request_approve', new sfRoute(
            '/security/request/:id/approve',
            ['module' => 'ahgAccessRequest', 'action' => 'approve'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('access_request_deny', new sfRoute(
            '/security/request/:id/deny',
            ['module' => 'ahgAccessRequest', 'action' => 'deny'],
            ['id' => '\d+']
        ));

        // Admin routes
        $routing->prependRoute('access_request_approvers', new sfRoute(
            '/security/approvers',
            ['module' => 'ahgAccessRequest', 'action' => 'approvers']
        ));

        $routing->prependRoute('access_request_add_approver', new sfRoute(
            '/security/approvers/add',
            ['module' => 'ahgAccessRequest', 'action' => 'addApprover']
        ));

        $routing->prependRoute('access_request_remove_approver', new sfRoute(
            '/security/approvers/:id/remove',
            ['module' => 'ahgAccessRequest', 'action' => 'removeApprover'],
            ['id' => '\d+']
        ));
    }
}
