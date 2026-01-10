<?php

/**
 * NER Service - Calls AHG NER API
 */
class ahgNerService
{
    private $apiUrl;
    private $apiKey;
    private $timeout;

    public function __construct()
    {
        $this->apiUrl = 'http://192.168.0.112:5004/ner/v1';
        $this->apiKey = 'ner_demo_ahg_internal_2026';
        $this->timeout = 60; // Longer timeout for PDF processing
    }

    public function extract($text, $clean = true)
    {
        $response = $this->request('POST', '/extract', [
            'text' => $text,
            'clean' => $clean
        ]);

        return $response ?? [
            'success' => false,
            'error' => 'API request failed',
            'entities' => ['PERSON' => [], 'ORG' => [], 'GPE' => [], 'DATE' => []]
        ];
    }

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
            CURLOPT_URL => $this->apiUrl . '/extract-pdf',
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

    public function health()
    {
        return $this->request('GET', '/health', [], false) ?? ['status' => 'error'];
    }

    public function usage()
    {
        return $this->request('GET', '/usage') ?? ['error' => 'Failed to get usage'];
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
            error_log("NER API error: $error");
            return null;
        }

        return json_decode($response, true);
    }
}
