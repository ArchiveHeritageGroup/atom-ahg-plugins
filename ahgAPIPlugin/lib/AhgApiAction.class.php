<?php

class AhgApiAction extends sfAction
{
    protected $startTime;
    protected $apiKeyInfo = null;
    protected $repository;
    protected $apiKeyService;
    protected $bootstrapped = false;

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
        error_log("AhgApiAction::authenticate called");
        if ($this->context->user->isAuthenticated()) {
            $this->apiKeyInfo = [
                'type' => 'session',
                'id' => null,
                'user_id' => $this->context->user->getAttribute('user_id'),
                'scopes' => ['read', 'write', 'delete'],
                'rate_limit' => 10000
            ];
            return true;
        }
        error_log("Calling apiKeyService->authenticate()");
        $this->apiKeyInfo = $this->apiKeyService->authenticate();
        error_log("apiKeyInfo = " . json_encode($this->apiKeyInfo));

        if ($this->apiKeyInfo) {
            error_log("Looking up user ID: " . $this->apiKeyInfo['user_id']);
            $user = QubitUser::getById($this->apiKeyInfo['user_id']);
            error_log("QubitUser result: " . ($user ? "found user " . $user->username : "NULL"));
            if ($user) {
                $this->context->user->signIn($user);
                error_log("User signed in successfully");
                return true;
            }
        }
        error_log("authenticate returning false");
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
        try {
            $result = $this->$method($request, $data);
            $this->logRequest(200);
            return $result;
        } catch (Exception $e) {
            $this->logRequest(500);
            return $this->error(500, 'Server Error', $e->getMessage());
        }
    }

    protected function success($data, int $statusCode = 200)
    {
        $this->response->setStatusCode($statusCode);
        return $this->renderText(json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
}
