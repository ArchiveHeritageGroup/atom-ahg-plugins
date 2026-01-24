<?php

class accessRequestActions extends sfActions
{
    public function preExecute()
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgAccessRequestPlugin/lib/Service/AccessRequestService.php';
        require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/SecurityClearanceService.php';
    }

    /**
     * New clearance request form
     */
    public function executeNew(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->classifications = \AtomExtensions\Services\SecurityClearanceService::getAllClassifications();
        $this->currentClearance = \AtomExtensions\Services\SecurityClearanceService::getUserClearance(
            $this->context->user->getAttribute('user_id')
        );
        $this->pendingRequest = \Illuminate\Database\Capsule\Manager::table('access_request')
            ->where('user_id', $this->context->user->getAttribute('user_id'))
            ->where('status', 'pending')
            ->where('request_type', 'clearance')
            ->first();
    }

    /**
     * Request access to specific object
     */
    public function executeRequestObject(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->context->user->getAttribute('user_id');
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
    public function executeCreate(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if ($request->isMethod('post')) {
            $userId = $this->context->user->getAttribute('user_id');
            $classificationId = (int) $request->getParameter('classification_id');
            $reason = trim($request->getParameter('reason'));
            $justification = trim($request->getParameter('justification'));
            $urgency = $request->getParameter('urgency', 'normal');

            if (empty($classificationId) || empty($reason)) {
                $this->getUser()->setFlash('error', 'Please fill in all required fields.');
                $this->redirect('security/request-access');
            }

            $requestId = \AtomExtensions\Services\AccessRequestService::createClearanceRequest(
                $userId, $classificationId, $reason, $justification, $urgency
            );

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
    public function executeCreateObjectRequest(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if ($request->isMethod('post')) {
            $userId = $this->context->user->getAttribute('user_id');
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
    public function executeMyRequests(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->context->user->getAttribute('user_id');
        $this->requests = \AtomExtensions\Services\AccessRequestService::getUserRequests($userId);
        $this->currentClearance = \AtomExtensions\Services\SecurityClearanceService::getUserClearance($userId);
        $this->accessGrants = \AtomExtensions\Services\AccessRequestService::getUserAccessGrants($userId);
    }

    /**
     * Cancel request
     */
    public function executeCancel(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->context->user->getAttribute('user_id');

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
    public function executePending(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->context->user->getAttribute('user_id');

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
    public function executeView(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->context->user->getAttribute('user_id');

        $this->accessRequest = \AtomExtensions\Services\AccessRequestService::getRequest($requestId);

        if (!$this->accessRequest) {
            $this->forward404('Request not found');
        }

        $isOwner = $this->accessRequest->user_id === $userId;
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
    public function executeApprove(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->context->user->getAttribute('user_id');

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
    public function executeDeny(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $requestId = (int) $request->getParameter('id');
        $userId = $this->context->user->getAttribute('user_id');

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
    public function executeApprovers(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
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
    public function executeAddApprover(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        if ($request->isMethod('post')) {
            $userId = (int) $request->getParameter('user_id');
            $minLevel = (int) $request->getParameter('min_level', 0);
            $maxLevel = (int) $request->getParameter('max_level', 5);
            $emailNotifications = (bool) $request->getParameter('email_notifications', true);

            if (\AtomExtensions\Services\AccessRequestService::setApprover($userId, $minLevel, $maxLevel, $emailNotifications)) {
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
    public function executeRemoveApprover(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $userId = (int) $request->getParameter('id');

        if (\AtomExtensions\Services\AccessRequestService::removeApprover($userId)) {
            $this->getUser()->setFlash('success', 'Approver removed.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to remove approver.');
        }

        $this->redirect('security/approvers');
    }
}
