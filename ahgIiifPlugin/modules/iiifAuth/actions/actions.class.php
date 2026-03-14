<?php

use AtomFramework\Http\Controllers\AhgController;
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
class iiifAuthActions extends AhgController
{
    protected function getAuthService(): IiifAuthService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services/IiifAuthService.php';
        return new IiifAuthService();
    }

    /**
     * Login endpoint - handles user authentication
     * GET /iiif/auth/login/:service
     */
    public function executeLogin($request)
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
    public function executeToken($request)
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
    public function executeLogout($request)
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
    public function executeConfirm($request)
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
    public function executeIndex($request)
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
    public function executeProtect($request)
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
    public function executeUnprotect($request)
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
    public function executeCheck($request)
    {
        $objectId = (int)$request->getParameter('id');

        $authService = $this->getAuthService();
        $userId = $this->getUser()->isAuthenticated()
            ? $this->getUser()->getAttribute('user_id')
            : null;

        $result = $authService->checkAccess($objectId, $userId);

        return $this->renderJson($result);
    }

    // ========================================================================
    // Auth API 2.0 Endpoints
    // ========================================================================

    /**
     * Probe endpoint - checks access status (Auth 2.0)
     * GET /iiif/auth/2/probe/:service?id=<objectId>
     */
    public function executeProbe($request)
    {
        $serviceName = $request->getParameter('service');
        $objectId = (int) $request->getParameter('id', 0);

        $authService = $this->getAuthService();

        // Extract bearer token from Authorization header
        $bearerToken = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $bearerToken = $matches[1];
            }
        }

        $result = $authService->probeAccess($objectId, $bearerToken);

        // CORS headers for probe endpoint
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', $origin);
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Headers', 'Authorization');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Credentials', 'true');

        $status = $result['status'] ?? 200;
        return $this->renderJson($result, $status === 401 ? 401 : 200);
    }

    /**
     * CORS preflight for probe endpoint
     * OPTIONS /iiif/auth/2/probe/:service
     */
    public function executeProbePreflight($request)
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', $origin);
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Headers', 'Authorization');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Credentials', 'true');
        $this->getResponse()->setHttpHeader('Access-Control-Max-Age', '86400');
        $this->getResponse()->setStatusCode(204);

        return sfView::NONE;
    }

    /**
     * Access service endpoint (Auth 2.0) - opens in new tab
     * GET /iiif/auth/2/access/:service
     */
    public function executeAccessService($request)
    {
        $serviceName = $request->getParameter('service');
        $authService = $this->getAuthService();

        $service = $authService->getAuthServiceByName($serviceName);
        if (!$service) {
            return $this->renderJson(['error' => 'Unknown service'], 404);
        }

        $accessProfile = $service->access_profile ?? $authService->mapProfileToAccessProfile($service->profile);

        // Active profile: show login or clickthrough
        if ($accessProfile === IiifAuthService::ACCESS_ACTIVE) {
            if ($service->profile === IiifAuthService::PROFILE_CLICKTHROUGH) {
                $this->service = $service;
                $this->authVersion = '2.0';
                $this->setTemplate('clickthrough');
                return sfView::SUCCESS;
            }

            // Login flow
            if ($this->getUser()->isAuthenticated()) {
                // Already logged in — issue token and show close-tab page
                $userId = $this->getUser()->getAttribute('user_id');
                $authService->issueAccessToken2($service, $userId);
                $this->setTemplate('accessServiceClose');
                return sfView::SUCCESS;
            }

            // Redirect to login with return URL
            $returnUrl = $this->generateUrl('iiif_auth2_access', ['service' => $serviceName], true);
            $this->redirect('/user/login?next=' . urlencode($returnUrl));
        }

        // Kiosk profile: auto-auth by location
        if ($accessProfile === IiifAuthService::ACCESS_KIOSK) {
            $result = $authService->requestToken($serviceName, null);
            if (!isset($result['error'])) {
                $this->setTemplate('accessServiceClose');
                return sfView::SUCCESS;
            }
            $this->error = $result['description'] ?? 'Access denied';
            $this->setTemplate('authFailed');
            return sfView::SUCCESS;
        }

        // External profile: redirect to external URL
        if ($accessProfile === IiifAuthService::ACCESS_EXTERNAL) {
            if ($service->login_url) {
                $this->redirect($service->login_url);
            }
            return $this->renderJson(['error' => 'External login not configured'], 500);
        }

        return $this->renderJson(['error' => 'Invalid access profile'], 400);
    }

    /**
     * Access token endpoint (Auth 2.0) - loaded in hidden iframe, returns postMessage
     * GET /iiif/auth/2/token/:service
     */
    public function executeAccessToken2($request)
    {
        $serviceName = $request->getParameter('service');
        $messageId = $request->getParameter('messageId');
        $origin = $request->getParameter('origin') ?? ($_SERVER['HTTP_ORIGIN'] ?? '*');

        $authService = $this->getAuthService();

        $service = $authService->getAuthServiceByName($serviceName);
        if (!$service) {
            $this->tokenData = json_encode([
                'type' => 'AuthAccessTokenError2',
                'profile' => 'invalidRequest',
                'heading' => ['en' => ['Unknown Service']],
                'note' => ['en' => ['The requested authentication service does not exist.']],
                'messageId' => $messageId,
            ], JSON_UNESCAPED_SLASHES);
            $this->origin = $origin;
            $this->setTemplate('accessTokenIframe');
            return sfView::SUCCESS;
        }

        $userId = $this->getUser()->isAuthenticated()
            ? $this->getUser()->getAttribute('user_id')
            : null;

        // Check session cookie (set by access service)
        $hasSession = isset($_COOKIE[IiifAuthService::TOKEN_COOKIE]) || $userId;

        if ($hasSession) {
            // Issue token
            $tokenData = $authService->issueAccessToken2($service, $userId, $origin, $messageId);
            $this->tokenData = json_encode($tokenData, JSON_UNESCAPED_SLASHES);
        } else {
            // No session — return error
            $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
            $this->tokenData = json_encode([
                'type' => 'AuthAccessTokenError2',
                'profile' => 'missingCredentials',
                'heading' => [$culture => [$service->heading ?: $service->failure_header ?: 'Authentication Required']],
                'note' => [$culture => [$service->note ?: $service->failure_description ?: 'Please authenticate to access this resource.']],
                'messageId' => $messageId,
            ], JSON_UNESCAPED_SLASHES);
        }

        $this->origin = $origin;
        $this->setTemplate('accessTokenIframe');
        return sfView::SUCCESS;
    }

    /**
     * Cantaloupe authorization check (internal only)
     * GET /iiif/auth/cantaloupe-check
     */
    public function executeCantaloupeCheck($request)
    {
        // Only allow from localhost
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
            return $this->renderJson(['error' => 'Forbidden'], 403);
        }

        $identifier = $request->getParameter('identifier', '');
        $cookie = $request->getParameter('cookie');
        $bearer = $request->getParameter('bearer');

        $authService = $this->getAuthService();
        $result = $authService->cantaloupeCheck($identifier, $cookie, $bearer);

        return $this->renderJson($result);
    }

    protected function renderJson($data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
