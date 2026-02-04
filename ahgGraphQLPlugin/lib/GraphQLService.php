<?php

namespace AhgGraphQLPlugin;

use AhgAPIPlugin\Repository\ApiRepository;
use AhgGraphQLPlugin\GraphQL\Resolvers\ActorResolver;
use AhgGraphQLPlugin\GraphQL\Resolvers\ItemResolver;
use AhgGraphQLPlugin\GraphQL\Resolvers\TaxonomyResolver;
use AhgGraphQLPlugin\GraphQL\Resolvers\UserResolver;
use AhgGraphQLPlugin\GraphQL\Schema\SchemaBuilder;
use AhgGraphQLPlugin\GraphQL\Security\ComplexityAnalyzer;
use AhgGraphQLPlugin\GraphQL\Security\DepthLimitRule;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use Illuminate\Database\Capsule\Manager as DB;

class GraphQLService
{
    private Schema $schema;
    private array $validationRules;
    private bool $debugMode;
    private int $maxDepth;
    private int $maxComplexity;
    private bool $introspectionEnabled;
    private ?ApiRepository $repository = null;
    private string $culture;

    public function __construct(array $options = [])
    {
        $this->debugMode = $options['debug'] ?? (sfConfig::get('sf_debug', false) || sfConfig::get('sf_environment') === 'dev');
        $this->maxDepth = $options['maxDepth'] ?? 10;
        $this->maxComplexity = $options['maxComplexity'] ?? 1000;
        $this->introspectionEnabled = $options['introspection'] ?? $this->debugMode;
        $this->culture = $options['culture'] ?? 'en';

        $this->initializeSchema();
        $this->initializeValidationRules();
    }

    private function initializeSchema(): void
    {
        $builder = new SchemaBuilder($this->introspectionEnabled);
        $this->schema = $builder->build();
    }

    private function initializeValidationRules(): void
    {
        // Start with default rules
        $this->validationRules = DocumentValidator::defaultRules();

        // Add depth limiting
        $this->validationRules['DepthLimit'] = new DepthLimitRule($this->maxDepth);

        // Add complexity analysis
        $this->validationRules['ComplexityAnalyzer'] = new ComplexityAnalyzer($this->maxComplexity);

        // Disable introspection in production
        if (!$this->introspectionEnabled) {
            $this->validationRules['DisableIntrospection'] = new DisableIntrospection();
        }
    }

    public function execute(string $query, ?array $variables = null, ?array $apiKeyInfo = null): array
    {
        $startTime = microtime(true);

        try {
            // Build context with resolvers
            $context = $this->buildContext($apiKeyInfo);

            // Execute the query
            $result = GraphQL::executeQuery(
                $this->schema,
                $query,
                null,
                $context,
                $variables,
                null,
                null,
                $this->validationRules
            );

            $output = $result->toArray($this->getDebugFlags());

            // Log the query (optional)
            $this->logQuery($apiKeyInfo, $query, $startTime, !isset($output['errors']));

            return $output;
        } catch (\Throwable $e) {
            $this->logQuery($apiKeyInfo, $query, $startTime, false);

            return [
                'errors' => [
                    [
                        'message' => $this->debugMode ? $e->getMessage() : 'Internal server error',
                        'extensions' => $this->debugMode ? [
                            'category' => 'internal',
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ] : null,
                    ],
                ],
            ];
        }
    }

    private function buildContext(?array $apiKeyInfo): array
    {
        // Initialize repository if not done
        if ($this->repository === null) {
            $this->repository = new ApiRepository($this->culture);
        }

        // Create resolver instances
        $resolvers = new \stdClass();
        $resolvers->item = new ItemResolver($this->repository, $this->culture);
        $resolvers->actor = new ActorResolver($this->repository, $this->culture);
        $resolvers->taxonomy = new TaxonomyResolver($this->repository, $this->culture);
        $resolvers->user = new UserResolver($this->repository, $this->culture);

        return [
            'resolvers' => $resolvers,
            'apiKeyInfo' => $apiKeyInfo,
            'userId' => $apiKeyInfo['user_id'] ?? null,
            'isAdmin' => $this->checkIsAdmin($apiKeyInfo),
            'scopes' => $apiKeyInfo['scopes'] ?? [],
        ];
    }

    private function checkIsAdmin(?array $apiKeyInfo): bool
    {
        if (!$apiKeyInfo || empty($apiKeyInfo['user_id'])) {
            return false;
        }

        // Check if user has admin group membership
        $adminGroup = DB::table('aclUserGroup')
            ->join('aclGroup', 'aclUserGroup.group_id', '=', 'aclGroup.id')
            ->where('aclUserGroup.user_id', $apiKeyInfo['user_id'])
            ->where('aclGroup.name', 'administrator')
            ->exists();

        return $adminGroup;
    }

    private function getDebugFlags(): int
    {
        if ($this->debugMode) {
            return DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
        }

        return DebugFlag::NONE;
    }

    private function logQuery(?array $apiKeyInfo, string $query, float $startTime, bool $success): void
    {
        // Only log if table exists
        try {
            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Extract operation name from query
            $operationName = null;
            if (preg_match('/(?:query|mutation|subscription)\s+(\w+)/i', $query, $matches)) {
                $operationName = $matches[1];
            }

            // Calculate rough depth and complexity for logging
            $depth = substr_count($query, '{');

            DB::table('ahg_graphql_log')->insert([
                'api_key_id' => $apiKeyInfo['id'] ?? null,
                'operation_name' => $operationName,
                'complexity_score' => null, // Could calculate but expensive
                'depth' => min($depth, 255),
                'execution_time_ms' => $executionTimeMs,
                'success' => $success ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silently ignore logging errors
        }
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function isIntrospectionEnabled(): bool
    {
        return $this->introspectionEnabled;
    }
}
