<?php

use AtomFramework\Http\Controllers\AhgController;
class accessRequestActions extends AhgController
{
    public function boot(): void
    {
        // Relative to this file, never built from sf_root_dir.
        //
        // '<root>/atom-ahg-plugins/...' only resolves where the plugins directory
        // is the symlink layout PSIS happens to use. Installed the ordinary way -
        // a real directory under plugins/, which is what a standalone install and
        // the RARI dev instance both have - the require fails AFTER headers are
        // sent, so the caller gets HTTP 200 with a zero-byte body: a white screen
        // with no error page and nothing in ahg_error_log.
        require_once dirname(__FILE__, 4).'/lib/Service/AccessRequestService.php';

        // The framework lives in different places depending on the install:
        // atom-framework/ alongside the app, or bundled inside ahgRuntimePlugin.
        // Resolve it rather than assuming, and never fatal when it is absent.
        $this->requireSecurityClearanceService();
    }

    /**
     * Load SecurityClearanceService from wherever this install keeps it.
     *
     * Does nothing when the class is already autoloaded, and fails quietly when
     * no copy is found - a missing optional service must not white-screen the
     * page, which is the failure this whole method exists to stop repeating.
     */
    private function requireSecurityClearanceService(): void
    {
        // AtomExtensions\, not AtomFramework\ - the file declares
        // `namespace AtomExtensions\Services` in both the framework and the
        // runtime-plugin copy, and this action calls it by that name. Checking
        // the wrong namespace makes the guard always false: harmless here, since
        // the search below still finds the file, but it defeats the early exit.
        if (class_exists('\AtomExtensions\Services\SecurityClearanceService')) {
            return;
        }

        $root = $this->config('sf_root_dir');

        $candidates = [
            $root.'/atom-framework/src/Services/SecurityClearanceService.php',
            $root.'/plugins/ahgRuntimePlugin/src/Services/SecurityClearanceService.php',
            // Relative to this file, so it resolves in both the symlink and the
            // real-directory layout - PHP resolves symlinks in __FILE__, so this
            // lands in whichever directory actually holds the plugins.
            dirname(__FILE__, 5).'/ahgRuntimePlugin/src/Services/SecurityClearanceService.php',
        ];

        foreach ($candidates as $file) {
            if (file_exists($file)) {
                require_once $file;

                return;
            }
        }
    }

    /**
     * New clearance request form
     */
    public function executeNew($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->classifications = \AtomExtensions\Services\SecurityClearanceService::getAllClassifications();
        $this->currentClearance = \AtomExtensions\Services\SecurityClearanceService::getUserClearance(
            $this->getUser()->getAttribute('user_id')
        );
        $this->pendingRequest = \Illuminate\Database\Capsule\Manager::table('access_request')
            ->where('user_id', $this->getUser()->getAttribute('user_id'))
            ->where('status', 'pending')
            ->where('request_type', 'clearance')
            ->first();

        // Repositories for the "all holdings of a repository" scope option.
        $this->repositories = \AtomExtensions\Services\AccessRequestService::getRepositoriesList();
    }

    /**
     * JSON type-ahead for the specific-item / collection scope pickers.
     */
    public function executeSearchObjects($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson([]);
        }

        $term = (string) $request->getParameter('q', '');

        return $this->renderJson(
            \AtomExtensions\Services\AccessRequestService::searchInformationObjects($term)
        );
    }

    /**
     * Request access to specific object
     */
    public function executeRequestObject($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $objectType = $request->getParameter('type', 'information_object');
        $objectId = (int) $request->getParameter('id');

        if (!$objectId) {
            $this->forward404('Invalid object');
        }

        $this->objectType = $objectType;
        $this->objectId = $objectId;
        $this->objectTitle = \AtomExtensions\Services\AccessRequestService::getObjectTitle($objectType, $objectId);
        $this->objectPath = \AtomExtensions\Services\AccessRequestService::getObjectPath($objectType, $objectId);
        $this->descendantCount = \AtomExtensions\Services\AccessRequestService::countDescendants($objectType, $objectId);
        
        $this->hasPendingRequest = \AtomExtensions\Services\AccessRequestService::hasPendingRequestForObject(
            $userId, $objectType, $objectId
        );
        
        $this->hasAccess = \AtomExtensions\Services\AccessRequestService::hasObjectAccess(
            $userId, $objectType, $objectId
        );
    }

    /**
     * Create clearance request
     */
    public function executeCreate($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if ($request->isMethod('post')) {
            $userId = $this->getUser()->getAttribute('user_id');
            $scope = $request->getParameter('scope', 'clearance');
            $reason = trim((string) $request->getParameter('reason'));
            $justification = trim((string) $request->getParameter('justification'));
            $urgency = $request->getParameter('urgency', 'normal');
            $accessLevel = $request->getParameter('access_level', 'view');

            if (empty($reason)) {
                $this->getUser()->setFlash('error', 'Please provide a reason for your request.');
                $this->redirect('security/request-access');
            }

            $svc = \AtomExtensions\Services\AccessRequestService::class;
            $requestId = null;

            switch ($scope) {
                case 'clearance':
                    $classificationId = (int) $request->getParameter('classification_id');
                    if (empty($classificationId)) {
                        $this->getUser()->setFlash('error', 'Please select a clearance level.');
                        $this->redirect('security/request-access');
                    }
                    $requestId = $svc::createClearanceRequest($userId, $classificationId, $reason, $justification, $urgency);
                    break;

                case 'item':
                case 'collection':
                    $objectId = (int) $request->getParameter('object_id');
                    if (empty($objectId)) {
                        $this->getUser()->setFlash('error', 'Please choose a record to request access to.');
                        $this->redirect('security/request-access');
                    }
                    $requestId = $svc::createObjectAccessRequest(
                        $userId,
                        [[
                            'object_type' => 'information_object',
                            'object_id' => $objectId,
                            'include_descendants' => ($scope === 'collection'),
                        ]],
                        $reason, $justification, $urgency, $accessLevel
                    );
                    break;

                case 'repository':
                    $repoId = (int) $request->getParameter('repository_id');
                    if (empty($repoId)) {
                        $this->getUser()->setFlash('error', 'Please choose a repository.');
                        $this->redirect('security/request-access');
                    }
                    $requestId = $svc::createObjectAccessRequest(
                        $userId,
                        [[
                            'object_type' => 'repository',
                            'object_id' => $repoId,
                            'include_descendants' => true,
                        ]],
                        $reason, $justification, $urgency, $accessLevel
                    );
                    break;

                case 'all':
                    // Entire archive: grant on the tree root (id 1) with descendants.
                    // hasObjectAccess() treats the root as an ancestor of every record,
                    // so this effectively grants access across all holdings.
                    $requestId = $svc::createObjectAccessRequest(
                        $userId,
                        [[
                            'object_type' => 'information_object',
                            'object_id' => 1,
                            'include_descendants' => true,
                        ]],
                        $reason, $justification, $urgency, $accessLevel,
                        'all', 'object'
                    );
                    break;

                default:
                    $this->getUser()->setFlash('error', 'Unknown request scope.');
                    $this->redirect('security/request-access');
            }

            if ($requestId) {
                $this->getUser()->setFlash('success', 'Your access request has been submitted.');
            } else {
                $this->getUser()->setFlash('error', 'Failed to create request. You may already have a pending request.');
            }
        }

        $this->redirect('security/my-requests');
    }

    /**
     * Create object access request
     */
    public function executeCreateObjectRequest($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if ($request->isMethod('post')) {
            $userId = $this->getUser()->getAttribute('user_id');
            $objectType = $request->getParameter('object_type');
            $objectId = (int) $request->getParameter('object_id');
            $includeDescendants = (bool) $request->getParameter('include_descendants');
            $reason = trim($request->getParameter('reason'));
            $justification = trim($request->getParameter('justification'));
            $urgency = $request->getParameter('urgency', 'normal');
            $accessLevel = $request->getParameter('access_level', 'view');

            if (empty($objectId) || empty($reason)) {
                $this->getUser()->setFlash('error', 'Please fill in all required fields.');
                $this->redirect("security/request-object?type={$objectType}&id={$objectId}");
            }

            $scopes = [[
                'object_type' => $objectType,
                'object_id' => $objectId,
                'include_descendants' => $includeDescendants,
            ]];

            $requestId = \AtomExtensions\Services\AccessRequestService::createObjectAccessRequest(
                $userId, $scopes, $reason, $justification, $urgency, $accessLevel
            );

            if ($requestId) {
                $this->getUser()->setFlash('success', 'Your access request has been submitted.');
            } else {
                $this->getUser()->setFlash('error', 'Failed to create request.');
            }
        }

        $this->redirect('security/my-requests');
    }

    /**
     * View user's requests
     */
    public function executeMyRequests($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->requests = \AtomExtensions\Services\AccessRequestService::getUserRequests($userId);
        $this->currentClearance = \AtomExtensions\Services\SecurityClearanceService::getUserClearance($userId);
        $this->accessGrants = \AtomExtensions\Services\AccessRequestService::getUserAccessGrants($userId);
    }

    /**
     * Cancel request
     */
    public function executeCancel($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->getUser()->getAttribute('user_id');

        if (\AtomExtensions\Services\AccessRequestService::cancelRequest($requestId, $userId)) {
            $this->getUser()->setFlash('success', 'Request cancelled successfully.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to cancel request.');
        }

        $this->redirect('security/my-requests');
    }

    /**
     * Pending requests for approvers
     */
    public function executePending($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');

        if (!\AtomExtensions\Services\AccessRequestService::isApprover($userId)) {
            $this->getUser()->setFlash('error', 'You are not authorized to view this page.');
            $this->redirect('@homepage');
        }

        $this->requests = \AtomExtensions\Services\AccessRequestService::getPendingRequests($userId);
        $this->stats = \AtomExtensions\Services\AccessRequestService::getStats();
    }

    /**
     * View single request
     */
    public function executeView($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->getUser()->getAttribute('user_id');

        $this->accessRequest = \AtomExtensions\Services\AccessRequestService::getRequest($requestId);

        if (!$this->accessRequest) {
            $this->forward404('Request not found');
        }

        // Cast both sides: the DB returns user_id as a string, getAttribute() an int,
        // so a strict === wrongly denied requesters access to their own request.
        $isOwner = (int) $this->accessRequest->user_id === (int) $userId;
        $isApprover = \AtomExtensions\Services\AccessRequestService::isApprover($userId);

        if (!$isOwner && !$isApprover) {
            $this->getUser()->setFlash('error', 'You are not authorized to view this request.');
            $this->redirect('@homepage');
        }

        $this->isApprover = $isApprover;
        $this->canApprove = $isApprover && $this->accessRequest->status === 'pending';
        $this->log = \AtomExtensions\Services\AccessRequestService::getRequestLog($requestId);
    }

    /**
     * Approve request
     */
    public function executeApprove($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->getUser()->getAttribute('user_id');

        // SECURITY: only a designated approver may approve — previously ANY
        // authenticated user could approve their own access request and thereby
        // self-grant a security clearance (privilege escalation).
        if (!\AtomExtensions\Services\AccessRequestService::isApprover($userId)) {
            $this->forward404('Not authorised to approve access requests');
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'accessRequest', 'action' => 'view', 'id' => $requestId]);
        }

        $notes = trim($request->getParameter('notes'));
        $expiresAt = $request->getParameter('expires_at');
        $expiresAt = !empty($expiresAt) ? $expiresAt : null;

        if (\AtomExtensions\Services\AccessRequestService::approveRequest($requestId, $userId, $notes, $expiresAt)) {
            $this->getUser()->setFlash('success', 'Request approved successfully.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to approve request.');
        }

        $this->redirect('security/access-requests');
    }

    /**
     * Deny request
     */
    public function executeDeny($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->getUser()->getAttribute('user_id');

        // SECURITY: only a designated approver may deny/decide requests.
        if (!\AtomExtensions\Services\AccessRequestService::isApprover($userId)) {
            $this->forward404('Not authorised to decide access requests');
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'accessRequest', 'action' => 'view', 'id' => $requestId]);
        }

        $notes = trim($request->getParameter('notes'));

        if (empty($notes)) {
            $this->getUser()->setFlash('error', 'Please provide a reason for denial.');
            $this->redirect(['module' => 'accessRequest', 'action' => 'view', 'id' => $requestId]);
        }

        if (\AtomExtensions\Services\AccessRequestService::denyRequest($requestId, $userId, $notes)) {
            $this->getUser()->setFlash('success', 'Request denied.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to deny request.');
        }

        $this->redirect('security/access-requests');
    }

    /**
     * Manage approvers
     */
    public function executeApprovers($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $this->approvers = \AtomExtensions\Services\AccessRequestService::getApprovers();
        $this->classifications = \AtomExtensions\Services\SecurityClearanceService::getAllClassifications();

        $this->users = \Illuminate\Database\Capsule\Manager::table('user')
            ->whereNotIn('id', function($query) {
                $query->select('user_id')->from('access_request_approver')->where('active', 1);
            })
            ->orderBy('username')
            ->get()
            ->toArray();
    }

    /**
     * Add approver
     */
    public function executeAddApprover($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        if ($request->isMethod('post')) {
            $userId = (int) $request->getParameter('user_id');
            $minLevel = (int) $request->getParameter('min_level', 0);
            $maxLevel = (int) $request->getParameter('max_level', 5);
            $emailNotifications = (bool) $request->getParameter('email_notifications', true);

            if (\AtomExtensions\Services\AccessRequestService::setApprover($userId, $minLevel, $maxLevel, $emailNotifications)) {
                // Email the new approver
                $this->sendApproverEmail($userId, 'added');
                $this->getUser()->setFlash('success', 'Approver added successfully.');
            } else {
                $this->getUser()->setFlash('error', 'Failed to add approver.');
            }
        }

        $this->redirect('security/approvers');
    }

    /**
     * Remove approver
     */
    public function executeRemoveApprover($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $userId = (int) $request->getParameter('id');

        if (\AtomExtensions\Services\AccessRequestService::removeApprover($userId)) {
            // Email the removed approver
            $this->sendApproverEmail($userId, 'removed');
            $this->getUser()->setFlash('success', 'Approver removed.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to remove approver.');
        }

        $this->redirect('security/approvers');
    }

    /**
     * Send email notification to an approver about their role change
     */
    protected function sendApproverEmail($userId, $action)
    {
        try {
            // Check if access request email notifications are enabled
            $enabled = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                ->where('setting_key', 'access_request_email_notifications')
                ->value('setting_value');
            if ($enabled === 'false' || $enabled === '0') {
                return;
            }

            $emailServicePath = sfConfig::get('sf_plugins_dir', '')
                . '/ahgCorePlugin/lib/Services/EmailService.php';
            if (!class_exists('AhgCore\Services\EmailService') && file_exists($emailServicePath)) {
                require_once $emailServicePath;
            }
            if (!class_exists('AhgCore\Services\EmailService') || !\AhgCore\Services\EmailService::isEnabled()) {
                return;
            }

            $user = \Illuminate\Database\Capsule\Manager::table('user')->where('id', $userId)->first();
            if (!$user || empty($user->email)) {
                return;
            }

            $siteTitle = sfConfig::get('app_siteTitle', 'AtoM Archive');
            if ($action === 'added') {
                $subject = "You have been added as an Access Request Approver";
                $body = "Dear {$user->username},\n\n"
                    . "You have been designated as an access request approver on {$siteTitle}.\n\n"
                    . "You will receive notifications when new access requests are submitted for your review.";
            } else {
                $subject = "Access Request Approver Role Removed";
                $body = "Dear {$user->username},\n\n"
                    . "Your access request approver role on {$siteTitle} has been removed.\n\n"
                    . "You will no longer receive access request notifications.";
            }

            \AhgCore\Services\EmailService::send($user->email, $subject, $body);
        } catch (\Exception $e) {
            error_log('Access request approver email failed: ' . $e->getMessage());
        }
    }

    /**
     * Display full audit history of all access request actions.
     */
    public function executeHistory($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward404();
            return;
        }

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        $db = \Illuminate\Database\Capsule\Manager::class;

        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 50;
        $statusFilter = $request->getParameter('status', '');
        $actionFilter = $request->getParameter('action_filter', '');

        $query = $db::table('access_request_log as l')
            ->leftJoin('access_request as r', 'l.request_id', '=', 'r.id')
            ->leftJoin('user as u', 'l.actor_id', '=', 'u.id')
            ->orderBy('l.created_at', 'desc');

        if ($actionFilter !== '') {
            $query->where('l.action', $actionFilter);
        }
        if ($statusFilter !== '') {
            $query->where('r.status', $statusFilter);
        }

        $this->total = (clone $query)->count();
        $this->logs = $query
            ->select('l.*', 'r.status as request_status', 'r.reason', 'r.urgency', 'u.username as actor_username')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->all();

        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = max(1, (int) ceil($this->total / $perPage));
        $this->statusFilter = $statusFilter;
        $this->actionFilter = $actionFilter;

        // Stats
        $this->stats = [
            'total_requests' => $db::table('access_request')->count(),
            'pending' => $db::table('access_request')->where('status', 'pending')->count(),
            'approved' => $db::table('access_request')->where('status', 'approved')->count(),
            'denied' => $db::table('access_request')->where('status', 'denied')->count(),
        ];
    }
}
