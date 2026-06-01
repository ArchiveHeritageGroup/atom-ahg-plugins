<?php

/**
 * ahgDonutService - Document Understanding (DONUT) client for AtoM.
 *
 * Mirrors the Heratio AhgAiServices\Services\DonutService path: it sends
 * document images to the DONUT model served by the ahg-ai python service
 * (default host .115, port 5008) for structured document parsing, then
 * stores the structured result in ahg_donut_extraction and best-effort
 * records a provenance row in ahg_ai_inference.
 *
 * The service degrades gracefully: when the DONUT gateway is unreachable
 * every public method returns null (or ['success' => false, ...]) rather
 * than throwing, so callers can fall back to manual entry.
 *
 * Loaded via require_once + new (Symfony 1.x does not autoload namespaced
 * plugin classes), so this class is intentionally in the global namespace
 * to match the sibling ahgNerService.
 */
class ahgDonutService
{
    /** @var string Base URL of the DONUT gateway, no trailing slash. */
    private $baseUrl;

    /** @var int HTTP timeout for extraction calls (seconds). */
    private $timeout;

    /** @var string Optional API key forwarded as X-API-Key. */
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim($this->loadSetting('donut_service_url', 'http://192.168.0.115:5008'), '/');
        $this->timeout = (int) $this->loadSetting('donut_timeout', '60');
        if ($this->timeout <= 0) {
            $this->timeout = 60;
        }
        $this->apiKey = $this->loadSetting('api_key', '');
    }

    /**
     * Load a setting from ahg_ai_settings (donut feature) with a general fallback.
     */
    private function loadSetting(string $key, string $default): string
    {
        try {
            if (class_exists('\AhgCore\Core\AhgDb')) {
                \AhgCore\Core\AhgDb::init();
            }
            $db = \Illuminate\Database\Capsule\Manager::class;

            $value = $db::table('ahg_ai_settings')
                ->where('feature', 'donut')
                ->where('setting_key', $key)
                ->value('setting_value');
            if ($value !== null && $value !== '') {
                return $value;
            }

            $value = $db::table('ahg_ai_settings')
                ->where('feature', 'general')
                ->where('setting_key', $key)
                ->value('setting_value');
            if ($value !== null && $value !== '') {
                return $value;
            }
        } catch (\Exception $e) {
            // DB not available yet (CLI bootstrap, etc.) - fall through.
        }

        return $default;
    }

    /**
     * Health probe against the DONUT gateway.
     *
     * @return array|null Decoded health payload, or null when unavailable.
     */
    public function health(): ?array
    {
        return $this->getJson('/health', [], 10);
    }

    /**
     * Whether the DONUT gateway is reachable right now.
     */
    public function isAvailable(): bool
    {
        return $this->health() !== null;
    }

    /**
     * Extract structured document fields from a document image and persist
     * the result. Mirrors the Heratio extract() path.
     *
     * @param string   $filePath        Absolute path to the document image.
     * @param int|null $informationObjectId Optional IO to attach the result to.
     * @param int|null $userId          Triggering user, for provenance.
     *
     * @return array|null The decoded DONUT payload (with extraction_id added),
     *                     or null when the gateway is unavailable / failed.
     */
    public function extract(string $filePath, ?int $informationObjectId = null, ?int $userId = null): ?array
    {
        if (!is_readable($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }

        $t0 = microtime(true);
        $body = $this->postFile('/extract', $filePath);
        if ($body === null) {
            return null;
        }

        $durationMs = (int) round((microtime(true) - $t0) * 1000);
        $extractionId = $this->storeResult(
            $filePath,
            $body,
            $informationObjectId,
            $userId,
            $durationMs
        );
        if ($extractionId !== null) {
            $body['extraction_id'] = $extractionId;
        }
        $body['success'] = $body['success'] ?? true;

        return $body;
    }

    /**
     * Extract by server-side path (no upload) - used when the image already
     * lives on a path the DONUT gateway can read directly.
     */
    public function extractByPath(string $imagePath): ?array
    {
        return $this->postJson('/extract', ['image_path' => $imagePath], $this->timeout);
    }

    /**
     * Classify document type only (lighter than full extraction).
     */
    public function classify(string $filePath): ?array
    {
        if (!is_readable($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }

        return $this->postFile('/classify', $filePath, 30);
    }

    /**
     * Batch extract from multiple images. Each result is stored individually.
     *
     * @param array<int,string> $filePaths Absolute image paths.
     */
    public function batch(array $filePaths, ?int $userId = null): ?array
    {
        $results = [];
        foreach ($filePaths as $path) {
            $one = $this->extract($path, null, $userId);
            $results[] = [
                'file'   => basename($path),
                'result' => $one,
                'ok'     => is_array($one) && empty($one['error']),
            ];
        }

        return [
            'success' => true,
            'count'   => count($results),
            'results' => $results,
        ];
    }

    /**
     * Median field positions from training annotations (for overlay UI).
     */
    public function positions(string $docType = 'type_a'): ?array
    {
        return $this->getJson('/positions', ['doc_type' => $docType], 15);
    }

    /**
     * Download the result payload for a previously completed gateway job.
     */
    public function downloadResult(string $jobId): ?array
    {
        return $this->getJson('/download/' . rawurlencode($jobId), [], 30);
    }

    public function trainingStatus(): ?array
    {
        return $this->getJson('/training/status', [], 10);
    }

    public function triggerTraining(int $epochs = 15, int $batchSize = 2): ?array
    {
        return $this->postJson('/train', [
            'epochs'     => $epochs,
            'batch_size' => $batchSize,
        ], 30);
    }

    /**
     * Persist a structured extraction result. Returns the new
     * ahg_donut_extraction id, or null on failure (never throws).
     */
    private function storeResult(
        string $filePath,
        array $body,
        ?int $informationObjectId,
        ?int $userId,
        int $durationMs
    ): ?int {
        $fields = $this->normaliseFields($body);
        $confidence = isset($body['confidence']) ? (float) $body['confidence'] : null;
        $docType = isset($body['doc_type']) ? (string) $body['doc_type']
                 : (isset($body['document_type']) ? (string) $body['document_type'] : null);
        $needsReview = !empty($body['needs_review']) ? 1 : 0;
        $modelName = (string) ($body['model'] ?? 'donut-base');
        $modelVersion = (string) ($body['model_version'] ?? 'v0');

        $extractionId = null;
        try {
            $db = \Illuminate\Database\Capsule\Manager::class;
            $extractionId = $db::table('ahg_donut_extraction')->insertGetId([
                'information_object_id' => $informationObjectId,
                'source_filename'       => mb_substr(basename($filePath), 0, 255),
                'input_hash'            => @hash_file('sha256', $filePath) ?: null,
                'doc_type'              => $docType !== null ? mb_substr($docType, 0, 64) : null,
                'confidence'            => $confidence,
                'needs_review'          => $needsReview,
                'fields_json'           => json_encode($fields, JSON_UNESCAPED_UNICODE),
                'raw_json'              => json_encode($body, JSON_UNESCAPED_UNICODE),
                'model_name'            => mb_substr($modelName, 0, 255),
                'model_version'         => mb_substr($modelVersion, 0, 64),
                'service_url'           => mb_substr($this->baseUrl, 0, 255),
                'elapsed_ms'            => $durationMs,
                'status'                => $needsReview ? 'needs_review' : 'extracted',
                'user_id'               => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log('ahgDonutService: storeResult failed - ' . $e->getMessage());
        }

        $this->recordInference(
            $filePath,
            $fields,
            $confidence,
            $informationObjectId,
            $userId,
            $modelName,
            $modelVersion,
            $durationMs
        );

        return $extractionId;
    }

    /**
     * Best-effort provenance row in ahg_ai_inference (owned by
     * ahgProvenancePlugin). Skipped silently when the table is absent.
     */
    private function recordInference(
        string $filePath,
        array $fields,
        ?float $confidence,
        ?int $informationObjectId,
        ?int $userId,
        string $modelName,
        string $modelVersion,
        int $durationMs
    ): void {
        try {
            $db = \Illuminate\Database\Capsule\Manager::class;
            $outputJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
            $uuid = $this->uuidV4();
            $db::table('ahg_ai_inference')->insert([
                'uuid'               => $uuid,
                'service_name'       => 'DONUT',
                'model_name'         => mb_substr($modelName, 0, 255),
                'model_version'      => mb_substr($modelVersion, 0, 64),
                'endpoint'           => mb_substr($this->baseUrl . '/extract', 0, 500),
                'input_hash'         => @hash_file('sha256', $filePath) ?: str_repeat('0', 64),
                'input_excerpt'      => mb_substr('image=' . basename($filePath), 0, 500),
                'output_hash'        => hash('sha256', (string) $outputJson),
                'output_excerpt'     => mb_substr((string) $outputJson, 0, 500),
                'confidence'         => $confidence,
                'target_entity_type' => $informationObjectId ? 'information_object' : 'pending',
                'target_entity_id'   => $informationObjectId ?: 0,
                'target_field'       => 'donut_extraction',
                'elapsed_ms'         => $durationMs,
                'user_id'            => $userId,
                'occurred_at'        => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Provenance is best-effort and must never break extraction.
        }
    }

    /**
     * Flatten the gateway payload to a name => value field map.
     */
    private function normaliseFields(array $body): array
    {
        if (isset($body['fields']) && is_array($body['fields'])) {
            return $body['fields'];
        }

        $fields = [];
        foreach ($body as $k => $v) {
            if (in_array($k, ['success', 'error', 'job_id', 'model', 'model_version', 'confidence', 'needs_review'], true)) {
                continue;
            }
            $fields[$k] = $v;
        }

        return $fields;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ─── Low-level HTTP helpers (curl, graceful) ──────────────────────────

    private function getJson(string $path, array $query = [], int $timeout = 0): ?array
    {
        $url = $this->baseUrl . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $this->exec($url, null, $timeout ?: $this->timeout);
    }

    private function postJson(string $path, array $payload, int $timeout = 0): ?array
    {
        return $this->exec(
            $this->baseUrl . $path,
            json_encode($payload),
            $timeout ?: $this->timeout,
            ['Content-Type: application/json']
        );
    }

    private function postFile(string $path, string $filePath, int $timeout = 0): ?array
    {
        if (!class_exists('CURLFile')) {
            return null;
        }
        $mime = $this->guessMime($filePath);
        $postFields = ['file' => new \CURLFile($filePath, $mime, basename($filePath))];

        return $this->exec(
            $this->baseUrl . $path,
            $postFields,
            $timeout ?: $this->timeout
        );
    }

    /**
     * @param mixed $body Raw string body, array (multipart), or null (GET).
     */
    private function exec(string $url, $body, int $timeout, array $extraHeaders = []): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init();
        $headers = $extraHeaders;
        if ($this->apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error !== '' || $response === false) {
            error_log('ahgDonutService: gateway unreachable (' . $url . '): ' . $error);
            return null;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('ahgDonutService: gateway returned HTTP ' . $httpCode . ' for ' . $url);
            return null;
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function guessMime(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'pdf':
                return 'application/pdf';
            case 'png':
                return 'image/png';
            case 'tif':
            case 'tiff':
                return 'image/tiff';
            case 'jpg':
            case 'jpeg':
            default:
                return 'image/jpeg';
        }
    }
}
