<?php

class AhgApiAction extends sfAction
{
    protected $startTime;
    protected $apiKeyInfo = null;
    protected $repository;
    protected $apiKeyService;
    protected $bootstrapped = false;
    protected $user = null;
    protected $idemSvc = null;   // #idempotency: IdempotencyService when a key is in play
    protected $idemKey = null;

    protected function loadBootstrap()
    {
        if ($this->bootstrapped) {
            return;
        }
        $bootstrapPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }
        require_once dirname(__FILE__) . '/repository/ApiRepository.php';
        require_once dirname(__FILE__) . '/service/ApiKeyService.php';

        // Register Services namespace autoloader
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgAPI\\Services\\') === 0) {
                $relativePath = str_replace('AhgAPI\\Services\\', '', $class);
                $filePath = dirname(__FILE__) . '/Services/' . $relativePath . '.php';
                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }
            return false;
        });

        $this->bootstrapped = true;
    }

    public function preExecute()
    {
        $this->startTime = microtime(true);
        $this->loadBootstrap();
        $this->apiKeyService = new \AhgAPIPlugin\Service\ApiKeyService();
        $culture = $this->request->getParameter('culture', 'en');
        $this->repository = new \AhgAPIPlugin\Repository\ApiRepository($culture);
    }

    public function execute($request)
    {
        $this->preExecute();
        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHttpHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->setHttpHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, Authorization');

        if ($request->getMethod() === 'OPTIONS') {
            $this->response->setStatusCode(204);
            return sfView::NONE;
        }

        if (!$this->authenticate()) {
            return $this->error(401, 'Unauthorized', 'Invalid or missing API key');
        }

        if (isset($this->apiKeyInfo['type']) && $this->apiKeyInfo['type'] === 'ahg_api_key') {
            if (!$this->apiKeyService->checkRateLimit($this->apiKeyInfo['id'], $this->apiKeyInfo['rate_limit'])) {
                return $this->error(429, 'Too Many Requests', 'Rate limit exceeded');
            }
        }
        return $this->process($request);
    }

    protected function authenticate(): bool
    {
        if ($this->context->user->isAuthenticated()) {
            $userId = $this->context->user->getAttribute('user_id');
            $this->apiKeyInfo = [
                'type' => 'session',
                'id' => null,
                'user_id' => $userId,
                'scopes' => ['read', 'write', 'delete'],
                'rate_limit' => 10000
            ];
            $this->user = (object) ['id' => $userId];
            return true;
        }

        $this->apiKeyInfo = $this->apiKeyService->authenticate();

        if ($this->apiKeyInfo) {
            $user = QubitUser::getById($this->apiKeyInfo['user_id']);
            if ($user) {
                $this->context->user->signIn($user);
                $this->user = (object) ['id' => $this->apiKeyInfo['user_id']];
                return true;
            }
        }

        return false;
    }
    protected function hasScope(string $scope): bool
    {
        return in_array($scope, $this->apiKeyInfo['scopes'] ?? []);
    }

    protected function process($request)
    {
        $method = strtoupper($request->getMethod());
        if (!method_exists($this, $method)) {
            return $this->error(405, 'Method Not Allowed', "Method $method not supported");
        }
        $data = null;
        if (in_array($method, ['POST', 'PUT']) && $request->getContentType() === 'application/json') {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->error(400, 'Bad Request', 'Invalid JSON body');
            }
        }
        // #idempotency: replay/guard mutating requests carrying an Idempotency-Key.
        $idem = $this->idempotencyBegin($method, $request);
        if (null !== $idem && in_array($idem['action'], ['replay', 'conflict', 'error'], true)) {
            if ('replay' === $idem['action']) {
                $this->response->setStatusCode((int) $idem['status']);
                return $this->renderText((string) $idem['body']);
            }
            return $this->error((int) $idem['status'], 'conflict' === $idem['action'] ? 'Conflict' : 'Bad Request', (string) ($idem['message'] ?? ''));
        }

        try {
            $result = $this->$method($request, $data);
            $status = $this->response->getStatusCode() ?: 200;
            $this->logRequest($status);
            $this->idempotencyStore($request, $status);
            return $result;
        } catch (Exception $e) {
            $this->logRequest(500);
            return $this->error(500, 'Server Error', $e->getMessage());
        }
    }

    protected function success($data, int $statusCode = 200)
    {
        $body = json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->response->setStatusCode($statusCode);

        // #etag: conditional GET — sets ETag and returns 304 on If-None-Match match.
        // Defensive: an ETag failure must never break the response.
        if ($this->applyEtag($statusCode, $body)) {
            return sfView::NONE;
        }

        return $this->renderText($body);
    }

    protected function error(int $statusCode, string $error, string $message)
    {
        $this->response->setStatusCode($statusCode);
        $this->logRequest($statusCode);
        return $this->renderText(json_encode(['success' => false, 'error' => $error, 'message' => $message], JSON_PRETTY_PRINT));
    }

    protected function logRequest(int $statusCode): void
    {
        if (!$this->apiKeyService) return;
        $duration = (int)((microtime(true) - $this->startTime) * 1000);
        $this->apiKeyService->logRequest([
            'api_key_id' => $this->apiKeyInfo['id'] ?? null,
            'user_id' => $this->apiKeyInfo['user_id'] ?? null,
            'method' => $this->request->getMethod(),
            'endpoint' => $this->request->getPathInfo(),
            'status_code' => $statusCode,
            'duration_ms' => $duration
        ]);
    }

    /**
     * Return 401 Unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error(401, 'Unauthorized', $message);
    }

    /**
     * Return 403 Forbidden response
     */
    protected function forbidden(string $message = 'Forbidden')
    {
        return $this->error(403, 'Forbidden', $message);
    }

    /**
     * Return 404 Not Found response
     */
    protected function notFound(string $message = 'Not Found')
    {
        return $this->error(404, 'Not Found', $message);
    }

    /**
     * Get JSON body from request
     */
    protected function getJsonBody(): array
    {
        $content = $this->request->getContent();
        if (empty($content)) {
            return [];
        }
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Check if current user is an administrator
     */
    protected function isAdmin(): bool
    {
        return $this->context->user->isAdministrator();
    }

    /**
     * #etag: set the ETag header and detect an If-None-Match match (→ 304).
     * Only fires for cacheable methods/status (the middleware decides). Defensive:
     * any failure returns false so the normal body is still emitted.
     */
    protected function applyEtag(int $statusCode, string $body): bool
    {
        try {
            require_once dirname(__FILE__) . '/Services/ApiETagMiddleware.php';
            $etag = new \AhgAPIPlugin\Service\ApiETagMiddleware();

            return $etag->apply(
                $this->response,
                $this->request->getMethod(),
                $statusCode,
                $body,
                $etag->getIfNoneMatchFromServer(),
                false
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * #idempotency: for mutating requests carrying an Idempotency-Key, ask the
     * service whether to replay/conflict. Returns the begin() array, or null when
     * not applicable (no key / non-mutating / failure). Sets $idemSvc/$idemKey so
     * a subsequent success can be stored.
     */
    protected function idempotencyBegin(string $method, $request): ?array
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }
        $key = $request->getHttpHeader('Idempotency-Key') ?: $request->getHttpHeader('X-Idempotency-Key');
        if (empty($key)) {
            return null;
        }
        try {
            require_once dirname(__FILE__) . '/Services/IdempotencyService.php';
            $this->idemSvc = new \AhgAPIPlugin\Service\IdempotencyService();
            $this->idemKey = (string) $key;
            $userId = (int) ($this->apiKeyInfo['user_id'] ?? 0);

            return $this->idemSvc->begin($method, $this->idemKey, $userId, $request->getPathInfo(), (string) $request->getContent());
        } catch (\Throwable $e) {
            $this->idemSvc = null;
            $this->idemKey = null;

            return null;
        }
    }

    /**
     * #idempotency: persist the produced response against the key (best-effort).
     */
    protected function idempotencyStore($request, int $status): void
    {
        if (!$this->idemSvc || !$this->idemKey) {
            return;
        }
        try {
            $userId = (int) ($this->apiKeyInfo['user_id'] ?? 0);
            $this->idemSvc->store($userId, $request->getPathInfo(), (string) $request->getContent(), $status, (string) $this->response->getContent(), []);
        } catch (\Throwable $e) {
            // best-effort; never affect the response
        }
    }
}
