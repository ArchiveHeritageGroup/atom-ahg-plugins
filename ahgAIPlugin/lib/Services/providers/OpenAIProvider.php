<?php

require_once dirname(__FILE__) . '/../LlmProviderInterface.php';

/**
 * OpenAI LLM Provider
 *
 * Connects to OpenAI API for LLM completions.
 * Supports GPT-4, GPT-4o, GPT-3.5-turbo and other chat models.
 *
 * @see https://platform.openai.com/docs/api-reference/chat
 */
class OpenAIProvider implements LlmProviderInterface
{
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
        $this->endpointUrl = rtrim($config['endpoint_url'] ?? 'https://api.openai.com/v1', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-4o-mini';
        $this->maxTokens = (int) ($config['max_tokens'] ?? 2000);
        $this->temperature = (float) ($config['temperature'] ?? 0.7);
        $this->timeout = (int) ($config['timeout_seconds'] ?? 60);
    }

    /**
     * Send a completion request to OpenAI
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $startTime = microtime(true);

        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'OpenAI API key not configured',
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
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        $response = $this->request('POST', '/chat/completions', $payload);

        $generationTime = round((microtime(true) - $startTime) * 1000);

        if (!$response) {
            return [
                'success' => false,
                'error' => 'Failed to connect to OpenAI',
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

        $text = $response['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $response['usage']['total_tokens'] ?? 0;

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
     * Check if OpenAI is available
     */
    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        $response = $this->request('GET', '/models', null, 10);

        return $response !== null && isset($response['data']);
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'openai';
    }

    /**
     * Get available models from OpenAI
     */
    public function getModels(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $response = $this->request('GET', '/models', null, 10);

        if (!$response || !isset($response['data'])) {
            return [];
        }

        $models = [];
        foreach ($response['data'] as $model) {
            $id = $model['id'] ?? '';
            // Filter to chat models only
            if (strpos($id, 'gpt') !== false || strpos($id, 'o1') !== false) {
                $models[] = $id;
            }
        }

        sort($models);

        return $models;
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

        $response = $this->request('GET', '/models', null, 10);

        if (!$response) {
            return [
                'status' => 'error',
                'models' => [],
                'version' => null,
                'error' => 'Cannot connect to OpenAI API',
            ];
        }

        if (isset($response['error'])) {
            return [
                'status' => 'error',
                'models' => [],
                'version' => null,
                'error' => $response['error']['message'] ?? 'Unknown error',
            ];
        }

        $models = $this->getModels();

        return [
            'status' => 'ok',
            'models' => $models,
            'version' => 'v1',
            'error' => null,
            'endpoint' => $this->endpointUrl,
            'default_model' => $this->model,
        ];
    }

    /**
     * Make HTTP request to OpenAI API
     */
    private function request(string $method, string $endpoint, ?array $data = null, ?int $timeout = null): ?array
    {
        $ch = curl_init();
        $url = $this->endpointUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
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
            error_log("OpenAI API error: {$error}");

            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log("OpenAI API HTTP {$httpCode}: {$response}");

            return $decoded ?: ['error' => "HTTP {$httpCode}"];
        }

        return $decoded;
    }
}
