<?php

class ahgAccessRequestPluginConfiguration extends sfPluginConfiguration
{
    /** Shown by AtoM's stock plugin admin; omitted plugins are hidden there. */
    public static $summary = 'Researcher access requests for restricted material';

    public function initialize()
    {

        // Navigation contributed to the theme. The pending count is this
        // plugin's query, not the theme's: the theme used to SELECT from
        // access_request directly, which meant it knew this plugin's schema.
        if (class_exists('AhgNav')) {
            AhgNav::register('user', 'access_request_my', [
                'route' => '@access_request_my',
                'label' => 'My Access Requests',
                'icon' => 'fas fa-key',
                'section' => 'Security',
                'weight' => 10,
            ]);
            // 'manage', not 'user': only the manage and browse groups are rendered
            // without ahgThemeB5Plugin, so on a stock-theme instance the approval
            // queue appeared nowhere - requests could sit unactioned with no way
            // to reach them from the interface. Found on the RARI dev instance.
            //
            // Gating is unchanged and stays in the closure below rather than
            // moving to a credentials list: 'credentials' => ['administrator']
            // would be applied AND the closure, which would cut out a delegated
            // approver who is not an administrator - exactly the person this
            // queue exists for. The personal "My Access Requests" entry above
            // keeps its 'user' group.
            AhgNav::register('manage', 'access_request_pending', [
                'route' => '@access_request_pending',
                'label' => 'Pending Requests',
                'icon' => 'fas fa-clock',
                'section' => 'Security',
                'weight' => 20,
                'visible' => static function ($user) {
                    if (null === $user || !$user->isAuthenticated()) {
                        return false;
                    }

                    if ($user->isAdministrator()) {
                        return true;
                    }

                    return \AtomExtensions\Services\AccessRequestService::isApprover($user->getUserID());
                },
                'badge' => static function () {
                    return \Illuminate\Database\Capsule\Manager::table('access_request')
                        ->where('status', 'pending')->count();
                },
            ]);
        }
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

        // History route — full audit trail of all access request actions
        $router->any('access_request_history', '/accessRequest/history', 'history');

        $router->register($event->getSubject());
    }
}
