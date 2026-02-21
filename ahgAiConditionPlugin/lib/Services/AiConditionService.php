<?php

namespace ahgAiConditionPlugin\Services;

/**
 * AI Condition Service - HTTP client for the FastAPI condition assessment service.
 */
class AiConditionService
{
    private string $apiUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $settingsFile = \sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/AhgSettingsService.php';
        if (file_exists($settingsFile)) {
            require_once $settingsFile;
        }

        $this->apiUrl = \AtomExtensions\Services\AhgSettingsService::get('ai_condition_service_url', 'http://localhost:8100');
        $this->apiKey = \AtomExtensions\Services\AhgSettingsService::get('ai_condition_api_key', 'ahg_ai_condition_internal_2026');
        $this->timeout = 120;
    }

    /**
     * Check if the AI service is reachable and healthy.
     */
    public function healthCheck(): array
    {
        $response = $this->request('GET', '/api/v1/health', [], false);

        if (!$response) {
            return ['success' => false, 'error' => 'Service unreachable'];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Submit an image for AI condition assessment.
     *
     * @param string $imageData Base64-encoded image data
     * @param array  $options   Assessment options (confidence threshold, store, etc.)
     * @return array Assessment result or error
     */
    public function assess(string $imageData, array $options = []): array
    {
        $payload = [
            'image_base64' => $imageData,
            'object_id' => !empty($options['information_object_id']) ? (int) $options['information_object_id'] : null,
            'store' => $options['store'] ?? true,
            'include_overlay' => $options['overlay'] ?? true,
        ];

        $response = $this->request('POST', '/api/v1/assess', $payload);

        if (!$response) {
            return ['success' => false, 'error' => 'Assessment request failed'];
        }

        return $response;
    }

    /**
     * Submit a file path for assessment (server-side image).
     */
    public function assessFile(string $filePath, array $options = []): array
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }

        $imageData = base64_encode(file_get_contents($filePath));
        return $this->assess($imageData, $options);
    }

    /**
     * Get a stored assessment report from the AI service.
     */
    public function getReport(int $id): array
    {
        $response = $this->request('GET', '/api/v1/report/' . $id);

        if (!$response) {
            return ['success' => false, 'error' => 'Report not found'];
        }

        return $response;
    }

    /**
     * Get usage stats for the configured API key.
     */
    public function getUsage(): array
    {
        $response = $this->request('GET', '/api/v1/usage');

        if (!$response) {
            return ['success' => false, 'error' => 'Usage data unavailable'];
        }

        return $response;
    }

    // =========================================================================
    // Proxy Methods (for training page to call Python service)
    // =========================================================================

    /**
     * Proxy a GET request to the Python service.
     */
    public function proxyGet(string $endpoint): array
    {
        $result = $this->request('GET', $endpoint);
        return $result ?? ['success' => false, 'error' => 'Service unreachable'];
    }

    /**
     * Proxy a POST request with JSON body to the Python service.
     */
    public function proxyPost(string $endpoint, array $data): array
    {
        $result = $this->request('POST', $endpoint, $data);
        return $result ?? ['success' => false, 'error' => 'Service unreachable'];
    }

    /**
     * Proxy a DELETE request to the Python service.
     */
    public function proxyDelete(string $endpoint): array
    {
        $result = $this->request('DELETE', $endpoint);
        return $result ?? ['success' => false, 'error' => 'Service unreachable'];
    }

    /**
     * Proxy a file upload to the Python service.
     */
    public function proxyFileUpload(string $endpoint, string $filePath, string $fileName, string $fieldName = 'file'): array
    {
        $ch = curl_init();
        $url = rtrim($this->apiUrl, '/') . $endpoint;

        $cfile = curl_file_create($filePath, 'application/zip', $fileName);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [$fieldName => $cfile],
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-Key: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'Upload failed: ' . $error];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            return $decoded ?: ['success' => false, 'error' => 'HTTP ' . $httpCode];
        }

        return $decoded ?: ['success' => false, 'error' => 'Invalid response'];
    }

    /**
     * Make an HTTP request to the AI service.
     */
    private function request(string $method, string $endpoint, array $data = [], bool $auth = true): ?array
    {
        $ch = curl_init();
        $url = rtrim($this->apiUrl, '/') . $endpoint;

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($auth) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('AiConditionService error: ' . $error);
            return null;
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);
            error_log('AiConditionService HTTP ' . $httpCode . ': ' . ($decoded['error'] ?? $response));
            return $decoded ?: ['success' => false, 'error' => 'HTTP ' . $httpCode];
        }

        return json_decode($response, true);
    }
}
