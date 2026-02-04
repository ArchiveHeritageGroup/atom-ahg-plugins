<?php

class graphqlIndexAction extends sfAction
{
    protected $startTime;
    protected $apiKeyInfo = null;
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

        // Load API plugin dependencies
        $apiPluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAPIPlugin/lib';
        require_once $apiPluginPath . '/repository/ApiRepository.php';
        require_once $apiPluginPath . '/Services/ApiKeyService.php';

        // Load GraphQL plugin autoloader
        $this->registerAutoloader();

        $this->bootstrapped = true;
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            $prefix = 'AhgGraphQLPlugin\\';
            $baseDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgGraphQLPlugin/lib/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    public function execute($request)
    {
        $this->startTime = microtime(true);
        $this->loadBootstrap();

        // Set JSON headers
        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHttpHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHttpHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, Authorization');

        // Handle CORS preflight
        if ($request->getMethod() === 'OPTIONS') {
            $this->response->setStatusCode(204);

            return sfView::NONE;
        }

        // Authenticate
        if (!$this->authenticate()) {
            return $this->jsonError(401, 'Unauthorized', 'Invalid or missing API key');
        }

        // Check read scope
        if (!$this->hasScope('read')) {
            return $this->jsonError(403, 'Forbidden', 'API key does not have read scope');
        }

        // Parse GraphQL request
        $query = null;
        $variables = null;
        $operationName = null;

        if ($request->getMethod() === 'POST') {
            $contentType = $request->getContentType();

            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode($request->getContent(), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->jsonError(400, 'Bad Request', 'Invalid JSON body');
                }
                $query = $input['query'] ?? null;
                $variables = $input['variables'] ?? null;
                $operationName = $input['operationName'] ?? null;
            } elseif (strpos($contentType, 'application/graphql') !== false) {
                $query = $request->getContent();
            } else {
                $query = $request->getParameter('query');
                $variables = $request->getParameter('variables');
                if (is_string($variables)) {
                    $variables = json_decode($variables, true);
                }
                $operationName = $request->getParameter('operationName');
            }
        } else {
            // GET request
            $query = $request->getParameter('query');
            $variables = $request->getParameter('variables');
            if (is_string($variables)) {
                $variables = json_decode($variables, true);
            }
            $operationName = $request->getParameter('operationName');
        }

        if (empty($query)) {
            return $this->jsonError(400, 'Bad Request', 'No GraphQL query provided');
        }

        // Get culture from request
        $culture = $request->getParameter('culture', 'en');

        // Execute GraphQL query
        $service = new \AhgGraphQLPlugin\GraphQLService([
            'culture' => $culture,
        ]);

        $result = $service->execute($query, $variables, $this->apiKeyInfo);

        // Output result
        $this->response->setStatusCode(200);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sfView::NONE;
    }

    protected function authenticate(): bool
    {
        // Check session auth first
        if ($this->context->user->isAuthenticated()) {
            $this->apiKeyInfo = [
                'type' => 'session',
                'id' => null,
                'user_id' => $this->context->user->getAttribute('user_id'),
                'scopes' => ['read', 'write', 'delete'],
                'rate_limit' => 10000,
            ];

            return true;
        }

        // Try API key auth
        $this->apiKeyService = new \AhgAPIPlugin\Service\ApiKeyService();
        $this->apiKeyInfo = $this->apiKeyService->authenticate();

        if ($this->apiKeyInfo) {
            $user = QubitUser::getById($this->apiKeyInfo['user_id']);
            if ($user) {
                $this->context->user->signIn($user);

                return true;
            }
        }

        return false;
    }

    protected function hasScope(string $scope): bool
    {
        return in_array($scope, $this->apiKeyInfo['scopes'] ?? []);
    }

    protected function jsonError(int $statusCode, string $error, string $message)
    {
        $this->response->setStatusCode($statusCode);
        echo json_encode([
            'errors' => [
                [
                    'message' => $message,
                    'extensions' => [
                        'code' => $error,
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT);

        return sfView::NONE;
    }
}
