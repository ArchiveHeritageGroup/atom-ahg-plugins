<?php

/**
 * IIIF AI Extraction Service
 *
 * Bridge between ahgIiifPlugin (manifests, canvases, annotations, OCR)
 * and ahgAIPlugin (NER, translation, summarization, face detection).
 *
 * Fetches images from Cantaloupe, sends to external AI server for processing,
 * writes results back as IIIF annotations and AI entity records.
 *
 * @package ahgAIPlugin
 * @since 2.2.0
 */

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/NerService.php';

class IiifAiService
{
    private ahgNerService $nerService;
    private string $cantaloupeInternalUrl;
    private array $settings = [];

    /** Valid extraction types */
    private const EXTRACTION_TYPES = ['ocr', 'ner', 'translate', 'summarize', 'face'];

    public function __construct()
    {
        \AhgCore\Core\AhgDb::init();
        $this->nerService = new ahgNerService();
        $this->cantaloupeInternalUrl = rtrim(
            \sfConfig::get('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182'),
            '/'
        );
        $this->loadSettings();
    }

    /**
     * Load IIIF AI settings from ahg_ai_settings table.
     */
    private function loadSettings(): void
    {
        $this->settings = [];
        try {
            $rows = DB::table('ahg_ai_settings')
                ->where('feature', 'iiif_ai')
                ->get();
            foreach ($rows as $row) {
                $this->settings[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // Table may not have iiif_ai entries yet — use defaults
        }

        // Merge defaults
        $defaults = [
            'enabled' => '1',
            'auto_extract_on_manifest' => '0',
            'extract_types' => '["ocr","ner"]',
            'annotation_motivation' => 'supplementing',
            'max_canvas_batch' => '50',
            'ocr_language' => 'eng',
            'ocr_confidence_threshold' => '0.60',
        ];
        foreach ($defaults as $key => $value) {
            if (!isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, string $default = ''): string
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Check if IIIF AI extraction is enabled.
     */
    public function isEnabled(): bool
    {
        return ($this->settings['enabled'] ?? '1') === '1';
    }

    // ========================================================================
    // EXTRACTION PIPELINE
    // ========================================================================

    /**
     * Run full extraction pipeline for an information object.
     *
     * Pipeline: OCR → NER → Annotations
     *
     * @param int $objectId Information object ID
     * @param array $extractTypes Types to run: ['ocr', 'ner', 'translate', 'summarize', 'face']
     * @param int|null $createdBy User ID
     * @return array Results keyed by extraction type
     */
    public function extractForObject(int $objectId, array $extractTypes = [], ?int $createdBy = null): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'IIIF AI extraction is disabled'];
        }

        if (empty($extractTypes)) {
            $extractTypes = json_decode($this->settings['extract_types'] ?? '["ocr","ner"]', true) ?: ['ocr', 'ner'];
        }

        $results = [];

        // Step 1: OCR (must run first — other steps depend on text)
        if (in_array('ocr', $extractTypes)) {
            $results['ocr'] = $this->extractOcr($objectId, $createdBy);
        }

        // Get OCR text (either just extracted or previously stored)
        $ocrText = $this->getOcrText($objectId);

        if (empty($ocrText)) {
            return array_merge($results, [
                'success' => true,
                'warning' => 'No OCR text available — skipping text-dependent extractions',
            ]);
        }

        // Step 2: NER (depends on OCR text)
        if (in_array('ner', $extractTypes)) {
            $results['ner'] = $this->extractNer($objectId, $ocrText, $createdBy);
        }

        // Step 3: Summarize (depends on OCR text)
        if (in_array('summarize', $extractTypes)) {
            $results['summarize'] = $this->extractSummary($objectId, $ocrText, $createdBy);
        }

        // Step 4: Translate (depends on OCR text)
        if (in_array('translate', $extractTypes)) {
            $results['translate'] = $this->extractTranslation($objectId, $ocrText, $createdBy);
        }

        // Step 5: Face detection (depends on image, not text)
        if (in_array('face', $extractTypes)) {
            $results['face'] = $this->extractFaces($objectId, $createdBy);
        }

        $results['success'] = true;
        return $results;
    }

    /**
     * Run extraction for all canvases in a manifest.
     *
     * @param int $objectId Information object ID (manifest owner)
     * @param array $extractTypes Types to run
     * @param int|null $createdBy User ID
     * @return array Extraction results
     */
    public function extractForManifest(int $objectId, array $extractTypes = [], ?int $createdBy = null): array
    {
        // For manifests, we just extract for the object — OCR is stored per object
        return $this->extractForObject($objectId, $extractTypes, $createdBy);
    }

    // ========================================================================
    // INDIVIDUAL EXTRACTION METHODS
    // ========================================================================

    /**
     * Extract OCR text from digital object images via AI server.
     */
    public function extractOcr(int $objectId, ?int $createdBy = null): array
    {
        $startTime = microtime(true);

        // Get digital object for this information object
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject) {
            return $this->logExtraction($objectId, null, 'ocr', 'failed', null, 'No digital object found', 0);
        }

        // Build Cantaloupe image URL
        $imagePath = $this->buildCantaloupeImagePath($digitalObject);
        if (!$imagePath) {
            return $this->logExtraction($objectId, null, 'ocr', 'failed', null, 'Cannot build Cantaloupe path', 0);
        }

        $imageUrl = $this->cantaloupeInternalUrl . '/iiif/2/' . $imagePath . '/full/max/0/default.jpg';

        // Call AI server OCR endpoint
        $apiUrl = $this->getAiApiUrl();
        $apiKey = $this->getAiApiKey();

        $response = $this->apiRequest('POST', $apiUrl . '/ocr/extract', [
            'image_url' => $imageUrl,
            'language' => $this->settings['ocr_language'] ?? 'eng',
        ], $apiKey);

        $elapsed = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response || !($response['success'] ?? false)) {
            return $this->logExtraction(
                $objectId, null, 'ocr', 'failed',
                $imageUrl, $response['error'] ?? 'OCR API request failed', $elapsed
            );
        }

        $fullText = $response['text'] ?? '';
        $confidence = $response['confidence'] ?? null;

        // Store OCR text in iiif_ocr_text table
        $this->storeOcrText($objectId, $digitalObject->id, $fullText, $confidence);

        // Store OCR blocks if available (word/line level)
        if (!empty($response['blocks'])) {
            $this->storeOcrBlocks($objectId, $response['blocks']);
        }

        return $this->logExtraction(
            $objectId, null, 'ocr', 'completed',
            $imageUrl, null, $elapsed,
            ['text_length' => strlen($fullText), 'confidence' => $confidence]
        );
    }

    /**
     * Extract named entities from OCR text and create annotations.
     */
    public function extractNer(int $objectId, string $text, ?int $createdBy = null): array
    {
        $startTime = microtime(true);

        $nerResult = $this->nerService->extract($text, true);
        $elapsed = (int) ((microtime(true) - $startTime) * 1000);

        if (!($nerResult['success'] ?? false)) {
            return $this->logExtraction(
                $objectId, null, 'ner', 'failed',
                null, $nerResult['error'] ?? 'NER extraction failed', $elapsed
            );
        }

        $entities = $nerResult['entities'] ?? [];
        $entityCount = 0;

        // Store entities in ahg_ner_entity table
        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'api',
            'status' => 'completed',
            'entity_count' => 0,
            'extracted_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($entities as $type => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                $entityValue = is_array($value) ? ($value['text'] ?? $value['value'] ?? '') : (string) $value;
                $entityConfidence = is_array($value) ? ($value['confidence'] ?? 1.0) : 1.0;

                if (empty($entityValue)) {
                    continue;
                }

                DB::table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $entityValue,
                    'confidence' => $entityConfidence,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $entityCount++;
            }
        }

        // Update extraction count
        DB::table('ahg_ner_extraction')
            ->where('id', $extractionId)
            ->update(['entity_count' => $entityCount]);

        // Create W3C annotations for entities (on first canvas)
        $this->createEntityAnnotations($objectId, $entities, $createdBy);

        return $this->logExtraction(
            $objectId, null, 'ner', 'completed',
            null, null, $elapsed,
            ['entity_count' => $entityCount, 'extraction_id' => $extractionId]
        );
    }

    /**
     * Summarize OCR text and store result.
     */
    public function extractSummary(int $objectId, string $text, ?int $createdBy = null): array
    {
        $startTime = microtime(true);

        $maxLength = (int) ($this->settings['summarize_max_length'] ?? 500);
        $minLength = (int) ($this->settings['summarize_min_length'] ?? 100);

        $result = $this->nerService->summarize($text, $maxLength, $minLength);
        $elapsed = (int) ((microtime(true) - $startTime) * 1000);

        if (!($result['success'] ?? false)) {
            return $this->logExtraction(
                $objectId, null, 'summarize', 'failed',
                null, $result['error'] ?? 'Summarization failed', $elapsed
            );
        }

        $summary = $result['summary'] ?? '';

        return $this->logExtraction(
            $objectId, null, 'summarize', 'completed',
            null, null, $elapsed,
            ['summary_length' => strlen($summary), 'summary' => $summary]
        );
    }

    /**
     * Translate OCR text via machine translation endpoint.
     */
    public function extractTranslation(int $objectId, string $text, ?int $createdBy = null): array
    {
        $startTime = microtime(true);

        // Get translation settings from ahg_ner_settings (legacy) or ahg_ai_settings
        $sourceLang = $this->getTranslationSetting('translation_source_lang', 'en');
        $targetLang = $this->getTranslationSetting('translation_target_lang', 'af');
        $mtEndpoint = $this->getTranslationSetting('mt_endpoint', 'http://127.0.0.1:5100/translate');
        $apiKey = $this->getAiApiKey();

        $response = $this->apiRequest('POST', $mtEndpoint, [
            'text' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
        ], $apiKey);

        $elapsed = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response || !isset($response['translatedText'])) {
            return $this->logExtraction(
                $objectId, null, 'translate', 'failed',
                null, $response['error'] ?? 'Translation failed', $elapsed
            );
        }

        $translatedText = $response['translatedText'];

        // Log to translation log table
        try {
            DB::table('ahg_translation_log')->insert([
                'object_id' => $objectId,
                'field_name' => 'iiif_ocr_text',
                'source_culture' => $sourceLang,
                'target_culture' => $targetLang,
                'source_text' => mb_substr($text, 0, 65000),
                'translated_text' => mb_substr($translatedText, 0, 65000),
                'translation_engine' => 'argos',
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Translation log table may not exist
        }

        // Create translation annotation on canvas
        $this->createTranslationAnnotation($objectId, $translatedText, $targetLang, $createdBy);

        return $this->logExtraction(
            $objectId, null, 'translate', 'completed',
            null, null, $elapsed,
            ['source_lang' => $sourceLang, 'target_lang' => $targetLang, 'translated_length' => strlen($translatedText)]
        );
    }

    /**
     * Detect faces in digital object images via AI server.
     */
    public function extractFaces(int $objectId, ?int $createdBy = null): array
    {
        $startTime = microtime(true);

        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject) {
            return $this->logExtraction($objectId, null, 'face', 'failed', null, 'No digital object found', 0);
        }

        $imagePath = $this->buildCantaloupeImagePath($digitalObject);
        if (!$imagePath) {
            return $this->logExtraction($objectId, null, 'face', 'failed', null, 'Cannot build image path', 0);
        }

        $imageUrl = $this->cantaloupeInternalUrl . '/iiif/2/' . $imagePath . '/full/max/0/default.jpg';

        $apiUrl = $this->getAiApiUrl();
        $apiKey = $this->getAiApiKey();

        $response = $this->apiRequest('POST', $apiUrl . '/face/detect', [
            'image_url' => $imageUrl,
        ], $apiKey);

        $elapsed = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response || !($response['success'] ?? false)) {
            return $this->logExtraction(
                $objectId, null, 'face', 'failed',
                $imageUrl, $response['error'] ?? 'Face detection failed', $elapsed
            );
        }

        $faces = $response['faces'] ?? [];

        // Create annotations for detected faces with bounding boxes
        foreach ($faces as $face) {
            $this->createFaceAnnotation($objectId, $face, $createdBy);
        }

        return $this->logExtraction(
            $objectId, null, 'face', 'completed',
            $imageUrl, null, $elapsed,
            ['face_count' => count($faces)]
        );
    }

    // ========================================================================
    // STORAGE HELPERS
    // ========================================================================

    /**
     * Get existing OCR text for an object.
     */
    public function getOcrText(int $objectId): string
    {
        $ocr = DB::table('iiif_ocr_text')
            ->where('object_id', $objectId)
            ->first();

        return $ocr->full_text ?? '';
    }

    /**
     * Store OCR text in iiif_ocr_text table.
     */
    private function storeOcrText(int $objectId, int $digitalObjectId, string $text, ?float $confidence): void
    {
        $existing = DB::table('iiif_ocr_text')
            ->where('object_id', $objectId)
            ->first();

        $data = [
            'digital_object_id' => $digitalObjectId,
            'object_id' => $objectId,
            'full_text' => $text,
            'format' => 'plain',
            'language' => $this->settings['ocr_language'] ?? 'eng',
            'confidence' => $confidence,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            DB::table('iiif_ocr_text')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::table('iiif_ocr_text')->insert($data);
        }
    }

    /**
     * Store OCR blocks (word/line level with coordinates).
     */
    private function storeOcrBlocks(int $objectId, array $blocks): void
    {
        $ocr = DB::table('iiif_ocr_text')
            ->where('object_id', $objectId)
            ->first();

        if (!$ocr) {
            return;
        }

        // Clear existing blocks for this OCR record
        DB::table('iiif_ocr_block')
            ->where('ocr_id', $ocr->id)
            ->delete();

        $order = 0;
        foreach ($blocks as $block) {
            DB::table('iiif_ocr_block')->insert([
                'ocr_id' => $ocr->id,
                'page_number' => $block['page'] ?? 1,
                'block_type' => $block['type'] ?? 'line',
                'text' => mb_substr($block['text'] ?? '', 0, 1000),
                'x' => $block['x'] ?? 0,
                'y' => $block['y'] ?? 0,
                'width' => $block['width'] ?? 0,
                'height' => $block['height'] ?? 0,
                'confidence' => $block['confidence'] ?? null,
                'block_order' => $order++,
            ]);
        }
    }

    // ========================================================================
    // ANNOTATION HELPERS
    // ========================================================================

    /**
     * Create W3C annotations for extracted entities on the first canvas.
     */
    private function createEntityAnnotations(int $objectId, array $entities, ?int $createdBy): void
    {
        $motivation = $this->settings['annotation_motivation'] ?? 'supplementing';

        foreach ($entities as $type => $values) {
            if (!is_array($values)) {
                continue;
            }

            $entityLabel = match ($type) {
                'PERSON' => 'Person',
                'ORG' => 'Organization',
                'GPE' => 'Place',
                'DATE' => 'Date',
                default => $type,
            };

            foreach ($values as $value) {
                $entityValue = is_array($value) ? ($value['text'] ?? $value['value'] ?? '') : (string) $value;
                if (empty($entityValue)) {
                    continue;
                }

                try {
                    $annotationId = DB::table('iiif_annotation')->insertGetId([
                        'object_id' => $objectId,
                        'canvas_id' => null,
                        'target_canvas' => null,
                        'target_selector' => null,
                        'motivation' => $motivation,
                        'created_by' => $createdBy,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    DB::table('iiif_annotation_body')->insert([
                        'annotation_id' => $annotationId,
                        'body_type' => 'TextualBody',
                        'body_value' => "[{$entityLabel}] {$entityValue}",
                        'body_format' => 'text/plain',
                        'body_language' => 'en',
                        'body_purpose' => 'tagging',
                    ]);
                } catch (\Exception $e) {
                    error_log("IiifAiService: Failed to create entity annotation: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Create a translation annotation on the object's canvas.
     */
    private function createTranslationAnnotation(int $objectId, string $translatedText, string $targetLang, ?int $createdBy): void
    {
        try {
            $annotationId = DB::table('iiif_annotation')->insertGetId([
                'object_id' => $objectId,
                'canvas_id' => null,
                'target_canvas' => null,
                'target_selector' => null,
                'motivation' => 'supplementing',
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('iiif_annotation_body')->insert([
                'annotation_id' => $annotationId,
                'body_type' => 'TextualBody',
                'body_value' => $translatedText,
                'body_format' => 'text/plain',
                'body_language' => $targetLang,
                'body_purpose' => 'transcription',
            ]);
        } catch (\Exception $e) {
            error_log("IiifAiService: Failed to create translation annotation: " . $e->getMessage());
        }
    }

    /**
     * Create an annotation for a detected face with bounding box.
     */
    private function createFaceAnnotation(int $objectId, array $face, ?int $createdBy): void
    {
        $x = $face['x'] ?? 0;
        $y = $face['y'] ?? 0;
        $w = $face['width'] ?? 0;
        $h = $face['height'] ?? 0;
        $label = $face['label'] ?? 'Unknown face';
        $confidence = $face['confidence'] ?? null;

        try {
            $selector = json_encode([
                'type' => 'FragmentSelector',
                'conformsTo' => 'http://www.w3.org/TR/media-frags/',
                'value' => "xywh={$x},{$y},{$w},{$h}",
            ]);

            $annotationId = DB::table('iiif_annotation')->insertGetId([
                'object_id' => $objectId,
                'canvas_id' => null,
                'target_canvas' => null,
                'target_selector' => $selector,
                'motivation' => 'identifying',
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $bodyValue = $label;
            if ($confidence !== null) {
                $bodyValue .= " (confidence: " . round($confidence * 100) . "%)";
            }

            DB::table('iiif_annotation_body')->insert([
                'annotation_id' => $annotationId,
                'body_type' => 'TextualBody',
                'body_value' => $bodyValue,
                'body_format' => 'text/plain',
                'body_language' => 'en',
                'body_purpose' => 'identifying',
            ]);
        } catch (\Exception $e) {
            error_log("IiifAiService: Failed to create face annotation: " . $e->getMessage());
        }
    }

    // ========================================================================
    // EXTRACTION LOG
    // ========================================================================

    /**
     * Log an extraction attempt to ai_iiif_extraction table.
     *
     * @return array Result array with success/error info
     */
    private function logExtraction(
        int $objectId,
        ?int $canvasId,
        string $type,
        string $status,
        ?string $inputSource,
        ?string $errorMessage,
        int $processingTimeMs,
        ?array $outputData = null
    ): array {
        try {
            DB::table('ai_iiif_extraction')->insert([
                'information_object_id' => $objectId,
                'iiif_canvas_id' => $canvasId,
                'extraction_type' => $type,
                'status' => $status,
                'input_source' => $inputSource ? mb_substr($inputSource, 0, 500) : null,
                'output_text' => isset($outputData['summary']) ? $outputData['summary'] : null,
                'output_json' => $outputData ? json_encode($outputData) : null,
                'error_message' => $errorMessage,
                'processing_time_ms' => $processingTimeMs,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("IiifAiService: Failed to log extraction: " . $e->getMessage());
        }

        $result = [
            'success' => ($status === 'completed'),
            'type' => $type,
            'status' => $status,
            'processing_time_ms' => $processingTimeMs,
        ];

        if ($errorMessage) {
            $result['error'] = $errorMessage;
        }
        if ($outputData) {
            $result['data'] = $outputData;
        }

        return $result;
    }

    // ========================================================================
    // STATUS & REPORTING
    // ========================================================================

    /**
     * Get extraction status for an object.
     */
    public function getExtractionStatus(int $objectId): array
    {
        $extractions = DB::table('ai_iiif_extraction')
            ->where('information_object_id', $objectId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        $status = [];
        foreach ($extractions as $ext) {
            $status[$ext->extraction_type] = [
                'status' => $ext->status,
                'processing_time_ms' => $ext->processing_time_ms,
                'error' => $ext->error_message,
                'created_at' => $ext->created_at,
            ];
        }

        return $status;
    }

    /**
     * Get extraction statistics (for admin dashboard).
     */
    public function getStats(): array
    {
        try {
            $total = DB::table('ai_iiif_extraction')->count();
            $completed = DB::table('ai_iiif_extraction')->where('status', 'completed')->count();
            $failed = DB::table('ai_iiif_extraction')->where('status', 'failed')->count();
            $pending = DB::table('ai_iiif_extraction')->where('status', 'pending')->count();

            $byType = DB::table('ai_iiif_extraction')
                ->selectRaw('extraction_type, status, COUNT(*) as cnt')
                ->groupBy('extraction_type', 'status')
                ->get()
                ->all();

            return [
                'total' => $total,
                'completed' => $completed,
                'failed' => $failed,
                'pending' => $pending,
                'by_type' => $byType,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'completed' => 0, 'failed' => 0, 'pending' => 0, 'by_type' => []];
        }
    }

    // ========================================================================
    // INTERNAL HELPERS
    // ========================================================================

    /**
     * Build Cantaloupe image path from digital object record.
     */
    private function buildCantaloupeImagePath(object $digitalObject): ?string
    {
        $path = $digitalObject->path ?? '';
        $name = $digitalObject->name ?? '';

        if (empty($name)) {
            return null;
        }

        // Cantaloupe uses _SL_ as path separator
        $fullPath = trim($path, '/') . '/' . $name;
        return str_replace('/', '_SL_', ltrim($fullPath, '/'));
    }

    /**
     * Get AI API base URL from settings.
     */
    private function getAiApiUrl(): string
    {
        // Try ahg_ai_settings first, then ahg_ner_settings fallback
        try {
            $url = DB::table('ahg_ai_settings')
                ->where('feature', 'general')
                ->where('setting_key', 'api_url')
                ->value('setting_value');
            if ($url) {
                return rtrim($url, '/');
            }
        } catch (\Exception $e) {
            // Fall through
        }

        try {
            $url = DB::table('ahg_ner_settings')
                ->where('setting_key', 'api_url')
                ->value('setting_value');
            if ($url) {
                return rtrim($url, '/');
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return 'http://192.168.0.112:5004/ai/v1';
    }

    /**
     * Get AI API key from settings.
     */
    private function getAiApiKey(): string
    {
        try {
            $key = DB::table('ahg_ai_settings')
                ->where('feature', 'general')
                ->where('setting_key', 'api_key')
                ->value('setting_value');
            if ($key) {
                return $key;
            }
        } catch (\Exception $e) {
            // Fall through
        }

        try {
            $key = DB::table('ahg_ner_settings')
                ->where('setting_key', 'api_key')
                ->value('setting_value');
            if ($key) {
                return $key;
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return '';
    }

    /**
     * Get translation setting from ahg_ner_settings table.
     */
    private function getTranslationSetting(string $key, string $default = ''): string
    {
        try {
            $value = DB::table('ahg_ner_settings')
                ->where('setting_key', $key)
                ->value('setting_value');
            return $value ?: $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Make an API request to the AI server.
     */
    private function apiRequest(string $method, string $url, array $data = [], string $apiKey = ''): ?array
    {
        $ch = curl_init();

        $headers = ['Content-Type: application/json'];
        if (!empty($apiKey)) {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        $timeout = (int) ($this->settings['api_timeout'] ?? 120);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log("IiifAiService API error ({$url}): {$error}");
            return ['success' => false, 'error' => $error];
        }

        if ($httpCode >= 400) {
            return ['success' => false, 'error' => "HTTP {$httpCode}", 'response' => $response];
        }

        return json_decode($response, true) ?? ['success' => false, 'error' => 'Invalid JSON response'];
    }
}
