<?php

/**
 * IIIF Authentication Actions
 *
 * Implements IIIF Auth API 1.0 endpoints:
 * - Login service (interactive user auth)
 * - Token service (access token issuance)
 * - Logout service (token revocation)
 *
 * @see https://iiif.io/api/auth/1.0/
 */
class iiifAuthActions extends AhgActions
{
    protected function getAuthService(): IiifAuthService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services/IiifAuthService.php';
        return new IiifAuthService();
    }

    /**
     * Login endpoint - handles user authentication
     * GET /iiif/auth/login/:service
     */
    public function executeLogin(sfWebRequest $request)
    {
        $serviceName = $request->getParameter('service');
        $authService = $this->getAuthService();

        $service = $authService->getAuthServiceByName($serviceName);
        if (!$service) {
            return $this->renderJson(['error' => 'Unknown service'], 404);
        }

        // For clickthrough - just display terms
        if ($service->profile === IiifAuthService::PROFILE_CLICKTHROUGH) {
            $this->service = $service;
            $this->setTemplate('clickthrough');
            return sfView::SUCCESS;
        }

        // For login - show login form or redirect to AtoM login
        if ($service->profile === IiifAuthService::PROFILE_LOGIN) {
            // Check if already authenticated
            if ($this->getUser()->isAuthenticated()) {
                // Issue token and close window
                $userId = $this->getUser()->getAttribute('user_id');
                $result = $authService->requestToken($serviceName, $userId);

                if (!isset($result['error'])) {
                    $this->setTemplate('authSuccess');
                    return sfView::SUCCESS;
                }
            }

            // Redirect to AtoM login with return URL
            $returnUrl = $this->generateUrl('iiif_auth_login', ['service' => $serviceName], true);
            $this->redirect('/user/login?next=' . urlencode($returnUrl));
        }

        // For external - redirect to external login URL
        if ($service->profile === IiifAuthService::PROFILE_EXTERNAL) {
            if ($service->login_url) {
                $this->redirect($service->login_url);
            }
            return $this->renderJson(['error' => 'External login not configured'], 500);
        }

        // Kiosk - auto-authenticate based on location
        if ($service->profile === IiifAuthService::PROFILE_KIOSK) {
            $result = $authService->requestToken($serviceName, null);

            if (!isset($result['error'])) {
                $this->setTemplate('authSuccess');
                return sfView::SUCCESS;
            }

            $this->service = $service;
            $this->error = $result['description'] ?? 'Access denied';
            $this->setTemplate('authFailed');
            return sfView::SUCCESS;
        }

        return $this->renderJson(['error' => 'Invalid profile'], 400);
    }

    /**
     * Token endpoint - issues access tokens
     * GET /iiif/auth/token/:service
     */
    public function executeToken(sfWebRequest $request)
    {
        $serviceName = $request->getParameter('service');
        $messageId = $request->getParameter('messageId');
        $origin = $request->getParameter('origin');

        $authService = $this->getAuthService();
        $userId = $this->getUser()->isAuthenticated()
            ? $this->getUser()->getAttribute('user_id')
            : null;

        $result = $authService->requestToken($serviceName, $userId, $messageId);

        // CORS headers for token endpoint
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', $origin ?: '*');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Credentials', 'true');

        return $this->renderJson($result);
    }

    /**
     * Logout endpoint - revokes token
     * GET /iiif/auth/logout/:service
     */
    public function executeLogout(sfWebRequest $request)
    {
        $authService = $this->getAuthService();
        $authService->logout();

        $this->setTemplate('logoutSuccess');
        return sfView::SUCCESS;
    }

    /**
     * Confirm clickthrough - user agreed to terms
     * POST /iiif/auth/confirm/:service
     */
    public function executeConfirm(sfWebRequest $request)
    {
        $serviceName = $request->getParameter('service');

        if ($request->getMethod() !== sfWebRequest::POST) {
            return $this->renderJson(['error' => 'POST required'], 405);
        }

        $authService = $this->getAuthService();
        $userId = $this->getUser()->isAuthenticated()
            ? $this->getUser()->getAttribute('user_id')
            : null;

        $result = $authService->requestToken($serviceName, $userId);

        if (!isset($result['error'])) {
            $this->setTemplate('authSuccess');
            return sfView::SUCCESS;
        }

        $this->error = $result['description'] ?? 'Failed to issue token';
        $this->setTemplate('authFailed');
        return sfView::SUCCESS;
    }

    /**
     * Admin: List protected resources
     * GET /admin/iiif-auth
     */
    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $authService = $this->getAuthService();

        $this->services = $authService->getAllServices();
        $this->resources = $authService->getProtectedResources(50);
    }

    /**
     * Admin: Protect a resource
     * POST /admin/iiif-auth/protect
     */
    public function executeProtect(sfWebRequest $request)
    {
        if (!$this->getUser()->hasCredential('administrator')) {
            return $this->renderJson(['error' => 'Unauthorized'], 403);
        }

        $objectId = (int)$request->getParameter('object_id');
        $serviceName = $request->getParameter('service');
        $applyToChildren = (bool)$request->getParameter('apply_to_children', true);
        $degradedAccess = (bool)$request->getParameter('degraded_access', false);

        if (!$objectId || !$serviceName) {
            return $this->renderJson(['error' => 'Missing parameters'], 400);
        }

        $authService = $this->getAuthService();
        $result = $authService->setObjectAuth($objectId, $serviceName, [
            'apply_to_children' => $applyToChildren,
            'degraded_access' => $degradedAccess,
        ]);

        return $this->renderJson(['success' => $result]);
    }

    /**
     * Admin: Remove protection from a resource
     * POST /admin/iiif-auth/unprotect
     */
    public function executeUnprotect(sfWebRequest $request)
    {
        if (!$this->getUser()->hasCredential('administrator')) {
            return $this->renderJson(['error' => 'Unauthorized'], 403);
        }

        $objectId = (int)$request->getParameter('object_id');

        if (!$objectId) {
            return $this->renderJson(['error' => 'Missing object_id'], 400);
        }

        $authService = $this->getAuthService();
        $result = $authService->removeObjectAuth($objectId);

        return $this->renderJson(['success' => $result]);
    }

    /**
     * Check access for an object (API)
     * GET /iiif/auth/check/:id
     */
    public function executeCheck(sfWebRequest $request)
    {
        $objectId = (int)$request->getParameter('id');

        $authService = $this->getAuthService();
        $userId = $this->getUser()->isAuthenticated()
            ? $this->getUser()->getAttribute('user_id')
            : null;

        $result = $authService->checkAccess($objectId, $userId);

        return $this->renderJson($result);
    }

    protected function renderJson($data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
