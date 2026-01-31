<?php

require_once dirname(__FILE__) . '/../LlmProviderInterface.php';

/**
 * Anthropic LLM Provider
 *
 * Connects to Anthropic API for LLM completions.
 * Supports Claude 3 models (Opus, Sonnet, Haiku).
 *
 * @see https://docs.anthropic.com/en/api/messages
 */
class AnthropicProvider implements LlmProviderInterface
{
    private const ANTHROPIC_VERSION = '2023-06-01';

    private string $endpointUrl;
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $timeout;

    /**
     * @param array $config Configuration from ahg_llm_config table
     */
    public function __construct(array $config)
    {
        $this->endpointUrl = rtrim($config['endpoint_url'] ?? 'https://api.anthropic.com/v1', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'claude-3-haiku-20240307';
        $this->maxTokens = (int) ($config['max_tokens'] ?? 2000);
        $this->temperature = (float) ($config['temperature'] ?? 0.7);
        $this->timeout = (int) ($config['timeout_seconds'] ?? 60);
    }

    /**
     * Send a completion request to Anthropic
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $startTime = microtime(true);

        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Anthropic API key not configured',
                'text' => null,
                'tokens_used' => 0,
                'model' => $this->model,
                'generation_time_ms' => 0,
            ];
        }

        $model = $options['model'] ?? $this->model;
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        // Temperature is optional in Anthropic API
        if ($temperature > 0) {
            $payload['temperature'] = $temperature;
        }

        $response = $this->request('POST', '/messages', $payload);

        $generationTime = round((microtime(true) - $startTime) * 1000);

        if (!$response) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Anthropic',
                'text' => null,
                'tokens_used' => 0,
                'model' => $model,
                'generation_time_ms' => $generationTime,
            ];
        }

        if (isset($response['error'])) {
            $errorMsg = is_array($response['error'])
                ? ($response['error']['message'] ?? json_encode($response['error']))
                : $response['error'];

            return [
                'success' => false,
                'error' => $errorMsg,
                'text' => null,
                'tokens_used' => 0,
                'model' => $model,
                'generation_time_ms' => $generationTime,
            ];
        }

        // Extract text from content blocks
        $text = '';
        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'text') {
                    $text .= $block['text'] ?? '';
                }
            }
        }

        // Calculate tokens from usage
        $tokensUsed = 0;
        if (isset($response['usage'])) {
            $tokensUsed = ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0);
        }

        return [
            'success' => true,
            'text' => trim($text),
            'tokens_used' => $tokensUsed,
            'model' => $response['model'] ?? $model,
            'generation_time_ms' => $generationTime,
            'error' => null,
            'stop_reason' => $response['stop_reason'] ?? null,
        ];
    }

    /**
     * Check if Anthropic is available
     */
    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // Anthropic doesn't have a simple health endpoint, so we just check if we have an API key
        // A more thorough check would make a minimal API call
        return true;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'anthropic';
    }

    /**
     * Get available models from Anthropic
     *
     * Note: Anthropic doesn't have a models listing endpoint, so we return known models
     */
    public function getModels(): array
    {
        // Known Claude 3 models as of 2024
        return [
            'claude-3-opus-20240229',
            'claude-3-sonnet-20240229',
            'claude-3-haiku-20240307',
            'claude-3-5-sonnet-20241022',
            'claude-3-5-haiku-20241022',
        ];
    }

    /**
     * Get health status with details
     */
    public function getHealth(): array
    {
        if (empty($this->apiKey)) {
            return [
                'status' => 'error',
                'models' => [],
                'version' => null,
                'error' => 'API key not configured',
            ];
        }

        // Try a minimal request to verify API key works
        // We could make a simple messages request, but that would cost tokens
        // For now, just return that we have a configured API key
        return [
            'status' => 'configured',
            'models' => $this->getModels(),
            'version' => self::ANTHROPIC_VERSION,
            'error' => null,
            'endpoint' => $this->endpointUrl,
            'default_model' => $this->model,
            'note' => 'API key configured but not verified (would cost tokens)',
        ];
    }

    /**
     * Make HTTP request to Anthropic API
     */
    private function request(string $method, string $endpoint, ?array $data = null, ?int $timeout = null): ?array
    {
        $ch = curl_init();
        $url = $this->endpointUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: ' . self::ANTHROPIC_VERSION,
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout ?? $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST' && $data !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log("Anthropic API error: {$error}");

            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log("Anthropic API HTTP {$httpCode}: {$response}");

            return $decoded ?: ['error' => ['message' => "HTTP {$httpCode}"]];
        }

        return $decoded;
    }
}
