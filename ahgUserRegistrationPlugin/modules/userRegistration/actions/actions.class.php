<?php

use AtomFramework\Http\Controllers\AhgController;
use AhgUserRegistration\Services\RegistrationService;

/**
 * User Registration Actions
 *
 * Handles public registration, email verification, and admin approval queue.
 */
class userRegistrationActions extends AhgController
{
    /**
     * Pre-execute — check permissions for admin actions.
     */
    public function boot(): void
    {
        $publicActions = ['register', 'verify'];
        $actionName = $this->getActionName();

        if (in_array($actionName, $publicActions)) {
            return;
        }

        // Admin actions require administrator role
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * Public — Registration form + submission.
     */
    public function executeRegister($request)
    {
        // If user is already logged in, redirect
        if ($this->getUser()->isAuthenticated()) {
            $this->redirect('/');
        }

        // Check if registration is enabled
        $enabled = \AtomExtensions\Services\AhgSettingsService::get('registration_enabled', '1');
        if (!$enabled || $enabled === '0') {
            $this->registrationDisabled = true;
            return;
        }

        $this->error = null;
        $this->success = false;
        $this->formData = [];

        if ($request->isMethod('post')) {
            $data = [
                'email' => trim($request->getParameter('email', '')),
                'username' => trim($request->getParameter('username', '')),
                'password' => $request->getParameter('password', ''),
                'full_name' => trim($request->getParameter('full_name', '')),
                'institution' => trim($request->getParameter('institution', '')),
                'research_interest' => trim($request->getParameter('research_interest', '')),
                'reason' => trim($request->getParameter('reason', '')),
            ];

            $this->formData = $data;

            // Server-side validation
            $validationError = $this->validateRegistration($data, $request);
            if ($validationError) {
                $this->error = $validationError;
                return;
            }

            $service = $this->getService();
            $ipAddress = $request->getHttpHeader('X-Forwarded-For')
                ?: $request->getRemoteAddress();

            $result = $service->createRequest($data, $ipAddress);

            if (!$result['success']) {
                $this->error = $result['error'];
                return;
            }

            $this->success = true;
        }
    }

    /**
     * Public — Verify email via token.
     */
    public function executeVerify($request)
    {
        $token = $request->getParameter('token', '');

        if (empty($token)) {
            $this->error = 'No verification token provided.';
            $this->verified = false;
            return;
        }

        $service = $this->getService();
        $result = $service->verifyEmail($token);

        $this->verified = $result['success'];
        $this->error = $result['error'] ?? null;
    }

    /**
     * Admin — Pending registrations queue.
     */
    public function executePending($request)
    {
        $service = $this->getService();
        $statusFilter = $request->getParameter('status', null);

        if ($statusFilter && !in_array($statusFilter, ['pending', 'verified', 'approved', 'rejected', 'expired'])) {
            $statusFilter = null;
        }

        $this->requests = $service->getAllRegistrations($statusFilter);
        $this->statusFilter = $statusFilter;

        // Get groups for the approval dropdown
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $this->groups = \Illuminate\Database\Capsule\Manager::table('acl_group')
            ->leftJoin('acl_group_i18n', function ($join) use ($culture) {
                $join->on('acl_group.id', '=', 'acl_group_i18n.id')
                     ->where('acl_group_i18n.culture', '=', $culture);
            })
            ->where('acl_group.id', '>', 99)
            ->select(['acl_group.id', 'acl_group_i18n.name'])
            ->orderBy('acl_group_i18n.name')
            ->get()
            ->all();
    }

    /**
     * Admin — Approve a registration (AJAX).
     */
    public function executeApprove($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $requestId = (int) $request->getParameter('request_id');
        $notes = $request->getParameter('admin_notes', '');
        $groupId = $request->getParameter('group_id') ? (int) $request->getParameter('group_id') : null;
        $adminId = $this->getUser()->getAttribute('user_id');

        $service = $this->getService();
        $result = $service->approve($requestId, $adminId, $notes, $groupId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin — Reject a registration (AJAX).
     */
    public function executeReject($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $requestId = (int) $request->getParameter('request_id');
        $notes = $request->getParameter('admin_notes', '');
        $adminId = $this->getUser()->getAttribute('user_id');

        $service = $this->getService();
        $result = $service->reject($requestId, $adminId, $notes);

        return $this->renderText(json_encode($result));
    }

    /**
     * Validate registration form data.
     */
    private function validateRegistration(array $data, $request): ?string
    {
        if (empty($data['full_name'])) {
            return 'Full name is required.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'A valid email address is required.';
        }
        if (empty($data['username']) || strlen($data['username']) < 3) {
            return 'Username must be at least 3 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['username'])) {
            return 'Username may only contain letters, numbers, dots, hyphens, and underscores.';
        }
        if (empty($data['password']) || strlen($data['password']) < 8) {
            return 'Password must be at least 8 characters.';
        }

        $confirmPassword = $request->getParameter('confirm_password', '');
        if ($data['password'] !== $confirmPassword) {
            return 'Passwords do not match.';
        }

        if (empty($data['reason'])) {
            return 'Please provide a reason for your registration request.';
        }

        return null;
    }

    /**
     * Get service instance (lazy load to avoid Symfony autoloader issues).
     */
    private function getService(): RegistrationService
    {
        require_once dirname(__FILE__) . '/../../lib/Services/RegistrationService.php';
        return new RegistrationService();
    }
}
