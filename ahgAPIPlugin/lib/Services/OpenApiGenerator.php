<?php

namespace AhgApi\Services;

/**
 * OpenApiGenerator — emits an OpenAPI 3.1 document for the apiv2 REST API (#129).
 *
 * The apiv2 routes are registered programmatically in
 * ahgAPIPluginConfiguration::initialize() as
 *   $apiv2->VERB('name', '/api/v2/...', 'action', [constraints]);
 * so this generator parses those declarations (the single source of truth) and
 * builds paths/operations/parameters/security from them — no hand-maintained
 * duplicate. Response bodies use the API's standard success/error envelopes.
 *
 * @package ahgAPIPlugin
 */
class OpenApiGenerator
{
    private string $configPath;

    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath
            ?: dirname(__DIR__, 2) . '/config/ahgAPIPluginConfiguration.class.php';
    }

    public function generate(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'AtoM Heratio API',
                'version' => '2.0',
                'description' => 'REST API (v2) for AtoM Heratio — archival descriptions, authorities, '
                    . 'repositories, taxonomies, conditions, heritage assets, valuations, privacy (DSAR/breach), '
                    . 'events/audit, search, batch, sync and webhooks. Responses use a `{success, data}` envelope; '
                    . 'errors use `{success:false, error, message}`. Authenticate with an API key.',
                'license' => ['name' => 'Proprietary — The Archive and Heritage Group (Pty) Ltd'],
            ],
            'servers' => [['url' => '/', 'description' => 'This instance']],
            'security' => [['apiKey' => []], ['bearer' => []]],
            'tags' => $this->buildTags(),
            'components' => [
                'securitySchemes' => [
                    'apiKey' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-API-Key',
                        'description' => 'API key issued via /api/v2/keys'],
                    'bearer' => ['type' => 'http', 'scheme' => 'bearer',
                        'description' => 'Alternatively pass the API key as `Authorization: Bearer <key>`'],
                ],
                'parameters' => [
                    'page' => ['name' => 'page', 'in' => 'query', 'required' => false,
                        'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1]],
                    'limit' => ['name' => 'limit', 'in' => 'query', 'required' => false,
                        'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 30]],
                    'q' => ['name' => 'q', 'in' => 'query', 'required' => false,
                        'schema' => ['type' => 'string'], 'description' => 'Free-text query filter'],
                    'idempotencyKey' => ['name' => 'Idempotency-Key', 'in' => 'header', 'required' => false,
                        'schema' => ['type' => 'string'], 'description' => 'Dedupe key for safe retries of writes'],
                ],
                'schemas' => [
                    'SuccessEnvelope' => [
                        'type' => 'object',
                        'required' => ['success', 'data'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => true],
                            'data' => ['description' => 'Endpoint payload (object, array, or scalar — shape varies by endpoint)'],
                        ],
                    ],
                    'ErrorEnvelope' => [
                        'type' => 'object',
                        'required' => ['success', 'error'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => false],
                            'error' => ['type' => 'string', 'description' => 'Machine-readable error code'],
                            'message' => ['type' => 'string', 'description' => 'Human-readable detail'],
                        ],
                    ],
                ],
            ],
            'paths' => $this->buildPaths(),
        ];
    }

    /** @return array<string,mixed> */
    private function buildPaths(): array
    {
        $paths = [];
        foreach ($this->parseRoutes() as $r) {
            $oaPath = preg_replace('/:([A-Za-z_][A-Za-z0-9_]*)/', '{$1}', $r['path']);
            foreach ($this->httpMethods($r['verb'], $r['action']) as $method) {
                $paths[$oaPath][$method] = $this->buildOperation($method, $oaPath, $r);
            }
        }
        ksort($paths);

        return $paths;
    }

    private function buildOperation(string $method, string $oaPath, array $r): array
    {
        $tag = $this->tagFor($oaPath);
        $isPublic = in_array($r['name'], ['apiv2_index', 'apiv2_openapi', 'apiv2_docs'], true);
        $isWrite = in_array($method, ['post', 'put', 'patch', 'delete'], true);

        $params = [];
        // Path parameters from :tokens, typed via the route constraints.
        if (preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $oaPath, $m)) {
            foreach ($m[1] as $name) {
                $isInt = isset($r['constraints'][$name]) && str_contains($r['constraints'][$name], '\\d');
                $params[] = ['name' => $name, 'in' => 'path', 'required' => true,
                    'schema' => ['type' => $isInt ? 'integer' : 'string']];
            }
        }
        // Browse/search endpoints accept pagination + query.
        if ($method === 'get' && (str_contains($r['action'], 'Browse') || str_contains($r['action'], 'search'))) {
            foreach (['page', 'limit', 'q'] as $p) {
                $params[] = ['$ref' => '#/components/parameters/' . $p];
            }
        }
        if ($isWrite) {
            $params[] = ['$ref' => '#/components/parameters/idempotencyKey'];
        }

        $op = [
            'tags' => [$tag],
            'summary' => $this->summaryFor($method, $r['action'], $tag),
            'operationId' => $r['name'],
            'responses' => $this->responsesFor($method),
        ];
        if ($params) {
            $op['parameters'] = $params;
        }
        if ($isWrite && $method !== 'delete') {
            $op['requestBody'] = [
                'required' => true,
                'content' => ['application/json' => ['schema' => ['type' => 'object',
                    'description' => 'Resource fields (shape varies by endpoint)']]],
            ];
        }
        if ($isPublic) {
            $op['security'] = [];
        }

        return $op;
    }

    private function responsesFor(string $method): array
    {
        $ok = $method === 'post' ? '201' : '200';
        $resp = [
            $ok => ['description' => 'Success',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/SuccessEnvelope']]]],
            '400' => $this->errResp('Bad request'),
            '401' => $this->errResp('Missing or invalid API key'),
            '404' => $this->errResp('Not found'),
        ];
        if (in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
            $resp['409'] = $this->errResp('Conflict (e.g. idempotency replay mismatch)');
            $resp['422'] = $this->errResp('Validation failed');
        }

        return $resp;
    }

    private function errResp(string $desc): array
    {
        return ['description' => $desc,
            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorEnvelope']]]];
    }

    /** Map a route verb (incl. 'any') to concrete OpenAPI HTTP methods. */
    private function httpMethods(string $verb, string $action): array
    {
        if ($verb !== 'any') {
            return [$verb];
        }
        // 'any' routes are the update/delete handlers — infer from the action name.
        if (str_contains($action, 'Update')) {
            return ['put', 'patch'];
        }
        if (str_contains($action, 'Delete')) {
            return ['delete'];
        }

        return ['get', 'post'];
    }

    private function tagFor(string $oaPath): string
    {
        $parts = array_values(array_filter(explode('/', $oaPath)));
        // /api/v2/<resource>/...
        $seg = $parts[2] ?? 'general';

        return ucfirst(preg_replace('/[{}].*/', '', $seg) ?: 'general');
    }

    private function summaryFor(string $method, string $action, string $tag): string
    {
        $verb = ['get' => 'Get', 'post' => 'Create', 'put' => 'Update', 'patch' => 'Update', 'delete' => 'Delete'][$method] ?? 'Call';
        if (str_contains($action, 'Browse')) {
            return "List {$tag}";
        }
        if (str_contains($action, 'Read')) {
            return "Get a {$tag} item";
        }
        if (str_contains($action, 'search')) {
            return 'Search across descriptions';
        }

        return "{$verb} {$tag}";
    }

    private function buildTags(): array
    {
        $seen = [];
        foreach ($this->parseRoutes() as $r) {
            $tag = $this->tagFor(preg_replace('/:([A-Za-z_][A-Za-z0-9_]*)/', '{$1}', $r['path']));
            $seen[$tag] = true;
        }
        ksort($seen);

        return array_map(fn ($t) => ['name' => $t], array_keys($seen));
    }

    /**
     * Parse the $apiv2->VERB('name','path','action',[constraints]) declarations.
     *
     * @return array<int,array{verb:string,name:string,path:string,action:string,constraints:array}>
     */
    private function parseRoutes(): array
    {
        $src = is_readable($this->configPath) ? file_get_contents($this->configPath) : '';
        $routes = [];
        $re = '/\$apiv2->(get|post|put|patch|delete|any)\(\s*'
            . "'([^']+)'\s*,\s*'([^']+)'\s*,\s*'([^']+)'"
            // Constraints array may contain ']' (e.g. '[a-z0-9_-]+'); greedy to the
            // last ']' on the (single) line — the route regex has no /s flag.
            . '(?:\s*,\s*(\[.*\]))?\s*\)/';
        if (preg_match_all($re, $src, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $routes[] = [
                    'verb' => $row[1],
                    'name' => $row[2],
                    'path' => $row[3],
                    'action' => $row[4],
                    'constraints' => $this->parseConstraints($row[5] ?? ''),
                ];
            }
        }

        return $routes;
    }

    private function parseConstraints(string $literal): array
    {
        $out = [];
        if (preg_match_all("/'([A-Za-z_][A-Za-z0-9_]*)'\s*=>\s*'([^']*)'/", $literal, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $out[$row[1]] = $row[2];
            }
        }

        return $out;
    }
}
