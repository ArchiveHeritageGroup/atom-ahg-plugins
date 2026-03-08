<?php
/**
 * AHG AI Service - Calls AHG AI API (NER + Summarization)
 */
class ahgNerService
{
    private $apiUrl;
    private $apiKey;
    private $timeout;

    public function __construct()
    {
        $this->apiUrl = $this->loadSetting('api_url', 'http://192.168.0.112:5004/ai/v1');
        $this->apiKey = $this->loadSetting('api_key', '');
        $this->timeout = (int) $this->loadSetting('api_timeout', '300');
    }

    /**
     * Load a setting from ahg_ai_settings (general feature) with ahg_ner_settings fallback.
     */
    private function loadSetting(string $key, string $default): string
    {
        try {
            \AhgCore\Core\AhgDb::init();
            $db = \Illuminate\Database\Capsule\Manager::class;

            // Try ahg_ai_settings first
            $value = $db::table('ahg_ai_settings')
                ->where('feature', 'general')
                ->where('setting_key', $key)
                ->value('setting_value');
            if ($value !== null && $value !== '') {
                return $value;
            }

            // Fallback to ahg_ner_settings
            $value = $db::table('ahg_ner_settings')
                ->where('setting_key', $key)
                ->value('setting_value');
            if ($value !== null && $value !== '') {
                return $value;
            }
        } catch (\Exception $e) {
            // DB not available yet (CLI bootstrap, etc.)
        }

        return $default;
    }

    /**
     * Extract named entities from text
     */
    public function extract($text, $clean = true)
    {
        $response = $this->request('POST', '/ner/extract', [
            'text' => $text,
            'clean' => $clean
        ]);

        return $response ?? [
            'success' => false,
            'error' => 'API request failed',
            'entities' => ['PERSON' => [], 'ORG' => [], 'GPE' => [], 'DATE' => []]
        ];
    }

    /**
     * Extract named entities from PDF
     */
    public function extractFromPdf($filePath)
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $ch = curl_init();
        $postFields = [
            'file' => new CURLFile($filePath, 'application/pdf', basename($filePath))
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/ner/extract-pdf',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log("NER PDF API error: $error");
            return ['success' => false, 'error' => $error];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'API returned ' . $httpCode];
        }

        return json_decode($response, true) ?? ['success' => false, 'error' => 'Invalid JSON'];
    }

    /**
     * Generate summary from text (for Scope & Content)
     */
    public function summarize($text, $maxLength = 1000, $minLength = 100)
    {
        $response = $this->request('POST', '/summarize', [
            'text' => $text,
            'max_length' => $maxLength,
            'min_length' => $minLength,
            'clean' => true
        ]);

        return $response ?? [
            'success' => false,
            'error' => 'API request failed',
            'summary' => null
        ];
    }

    /**
     * Generate summary from PDF
     */
    public function summarizeFromPdf($filePath, $maxLength = 1000, $minLength = 100)
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $ch = curl_init();
        $postFields = [
            'file' => new CURLFile($filePath, 'application/pdf', basename($filePath)),
            'max_length' => $maxLength,
            'min_length' => $minLength
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/summarize-pdf',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log("Summarize PDF API error: $error");
            return ['success' => false, 'error' => $error];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'API returned ' . $httpCode];
        }

        return json_decode($response, true) ?? ['success' => false, 'error' => 'Invalid JSON'];
    }

    /**
     * Health check
     */
    public function health()
    {
        return $this->request('GET', '/health', [], false) ?? ['status' => 'error'];
    }

    /**
     * Get usage statistics
     */
    public function usage()
    {
        return $this->request('GET', '/usage') ?? ['error' => 'Failed to get usage'];
    }

    /**
     * Check if summarizer is available
     */
    public function isSummarizerAvailable()
    {
        $health = $this->health();
        return isset($health['services']['summarizer']) && $health['services']['summarizer'] === true;
    }

    private function request($method, $endpoint, $data = [], $auth = true)
    {
        $ch = curl_init();
        $url = $this->apiUrl . $endpoint;

        $headers = ['Content-Type: application/json'];
        if ($auth) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("AI API error: $error");
            return null;
        }

        return json_decode($response, true);
    }
}
