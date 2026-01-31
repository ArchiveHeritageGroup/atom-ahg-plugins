<?php

require_once dirname(__FILE__) . '/../LlmProviderInterface.php';

/**
 * Ollama LLM Provider
 *
 * Connects to local Ollama instance for LLM completions.
 * Default endpoint: http://localhost:11434
 *
 * @see https://github.com/ollama/ollama/blob/main/docs/api.md
 */
class OllamaProvider implements LlmProviderInterface
{
    private string $endpointUrl;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $timeout;

    /**
     * @param array $config Configuration from ahg_llm_config table
     */
    public function __construct(array $config)
    {
        $this->endpointUrl = rtrim($config['endpoint_url'] ?? 'http://localhost:11434', '/');
        $this->model = $config['model'] ?? 'llama3.1:8b';
        $this->maxTokens = (int) ($config['max_tokens'] ?? 2000);
        $this->temperature = (float) ($config['temperature'] ?? 0.7);
        $this->timeout = (int) ($config['timeout_seconds'] ?? 120);
    }

    /**
     * Send a completion request to Ollama
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $startTime = microtime(true);

        $model = $options['model'] ?? $this->model;
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $payload = [
            'model' => $model,
            'prompt' => $userPrompt,
            'system' => $systemPrompt,
            'stream' => false,
            'options' => [
                'num_predict' => $maxTokens,
                'temperature' => $temperature,
            ],
        ];

        $response = $this->request('POST', '/api/generate', $payload);

        $generationTime = round((microtime(true) - $startTime) * 1000);

        if (!$response) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Ollama',
                'text' => null,
                'tokens_used' => 0,
                'model' => $model,
                'generation_time_ms' => $generationTime,
            ];
        }

        if (isset($response['error'])) {
            return [
                'success' => false,
                'error' => $response['error'],
                'text' => null,
                'tokens_used' => 0,
                'model' => $model,
                'generation_time_ms' => $generationTime,
            ];
        }

        $text = $response['response'] ?? '';
        $tokensUsed = ($response['prompt_eval_count'] ?? 0) + ($response['eval_count'] ?? 0);

        return [
            'success' => true,
            'text' => trim($text),
            'tokens_used' => $tokensUsed,
            'model' => $response['model'] ?? $model,
            'generation_time_ms' => $generationTime,
            'error' => null,
        ];
    }

    /**
     * Check if Ollama is available
     */
    public function isAvailable(): bool
    {
        $response = $this->request('GET', '/api/tags', null, 5);

        return $response !== null && isset($response['models']);
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'ollama';
    }

    /**
     * Get available models from Ollama
     */
    public function getModels(): array
    {
        $response = $this->request('GET', '/api/tags', null, 10);

        if (!$response || !isset($response['models'])) {
            return [];
        }

        $models = [];
        foreach ($response['models'] as $model) {
            $models[] = $model['name'] ?? $model['model'] ?? 'unknown';
        }

        return $models;
    }

    /**
     * Get health status with details
     */
    public function getHealth(): array
    {
        $response = $this->request('GET', '/api/tags', null, 5);

        if (!$response) {
            return [
                'status' => 'error',
                'models' => [],
                'version' => null,
                'error' => 'Cannot connect to Ollama at ' . $this->endpointUrl,
            ];
        }

        $models = [];
        if (isset($response['models'])) {
            foreach ($response['models'] as $model) {
                $models[] = [
                    'name' => $model['name'] ?? 'unknown',
                    'size' => $model['size'] ?? 0,
                    'modified_at' => $model['modified_at'] ?? null,
                ];
            }
        }

        // Get version from /api/version
        $versionResponse = $this->request('GET', '/api/version', null, 5);
        $version = $versionResponse['version'] ?? 'unknown';

        return [
            'status' => 'ok',
            'models' => $models,
            'version' => $version,
            'error' => null,
            'endpoint' => $this->endpointUrl,
            'default_model' => $this->model,
        ];
    }

    /**
     * Make HTTP request to Ollama API
     */
    private function request(string $method, string $endpoint, ?array $data = null, ?int $timeout = null): ?array
    {
        $ch = curl_init();
        $url = $this->endpointUrl . $endpoint;

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout ?? $this->timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
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
            error_log("Ollama API error: {$error}");

            return null;
        }

        if ($httpCode >= 400) {
            error_log("Ollama API HTTP {$httpCode}: {$response}");

            return ['error' => "HTTP {$httpCode}: " . ($response ?: 'Unknown error')];
        }

        return json_decode($response, true);
    }
}
