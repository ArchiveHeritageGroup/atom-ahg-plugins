<?php

require_once dirname(__FILE__) . '/../LlmProviderInterface.php';

/**
 * AHG AI Gateway LLM Provider.
 *
 * Routes completions through the AHG AI gateway (https://ai.theahg.co.za/ai/v1)
 * instead of a direct GPU-node port — the fleet-wide compliance requirement.
 * Thin adapter over \AtomFramework\Services\AI\AiGatewayClient so the existing
 * LlmService factory + #141 guardrail pipeline keep working unchanged: switch a
 * row in ahg_llm_config to provider='gateway' and generation moves to the
 * gateway with no other code change.
 *
 * The api_key comes from ahg_ai_settings (feature='gateway', fallback
 * feature='general') via AiGatewayClient::fromSettings(); the per-config model
 * still wins when set.
 */
class GatewayProvider implements LlmProviderInterface
{
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $timeout;

    /**
     * @param array $config Configuration from ahg_llm_config table
     */
    public function __construct(array $config)
    {
        $this->model = $config['model'] ?? '';
        $this->maxTokens = (int) ($config['max_tokens'] ?? 2000);
        $this->temperature = (float) ($config['temperature'] ?? 0.7);
        $this->timeout = (int) ($config['timeout_seconds'] ?? 120);
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $startTime = microtime(true);
        $client = $this->client();
        $model = $options['model'] ?? ($this->model ?: $client->getChatModel());

        if (!$client->isConfigured()) {
            return [
                'success' => false,
                'error' => 'AI gateway API key not configured (ahg_ai_settings feature=gateway)',
                'text' => null,
                'tokens_used' => 0,
                'model' => $model,
                'generation_time_ms' => 0,
            ];
        }

        $result = $client->chat(
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            [
                'model' => $model,
                'temperature' => $options['temperature'] ?? $this->temperature,
                'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
                'timeout' => $this->timeout,
            ]
        );

        $generationTime = round((microtime(true) - $startTime) * 1000);

        // token counts aren't surfaced by the chat passthrough; report 0.
        return [
            'success' => (bool) ($result['success'] ?? false),
            'text' => isset($result['text']) ? trim((string) $result['text']) : null,
            'tokens_used' => 0,
            'model' => $result['model'] ?? $model,
            'generation_time_ms' => $generationTime,
            'error' => $result['error'] ?? null,
        ];
    }

    public function isAvailable(): bool
    {
        return $this->client()->isAvailable();
    }

    public function getName(): string
    {
        return 'gateway';
    }

    public function getModels(): array
    {
        // The gateway does not expose a public model list; return the configured
        // model so callers/Ui have something meaningful.
        $client = $this->client();

        return array_values(array_filter([$this->model ?: $client->getChatModel()]));
    }

    public function getHealth(): array
    {
        $client = $this->client();
        $ok = $client->isAvailable();

        return [
            'status' => $ok ? 'ok' : 'error',
            'models' => $this->getModels(),
            'version' => null,
            'error' => $ok ? null : 'AI gateway not reachable',
            'endpoint' => \AtomFramework\Services\AI\AiGatewayClient::DEFAULT_BASE_URL,
            'default_model' => $this->model ?: $client->getChatModel(),
        ];
    }

    private function client(): \AtomFramework\Services\AI\AiGatewayClient
    {
        return \AtomFramework\Services\AI\AiGatewayClient::fromSettings();
    }
}
