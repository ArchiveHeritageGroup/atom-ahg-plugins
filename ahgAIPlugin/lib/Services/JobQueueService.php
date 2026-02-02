<?php

declare(strict_types=1);

namespace ahgAIPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AI Job Queue Service.
 *
 * Manages batch jobs and individual job items for AI processing tasks.
 * Integrates with Gearman for background processing.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class JobQueueService
{
    public const TASK_NER = 'ner';
    public const TASK_SUMMARIZE = 'summarize';
    public const TASK_SUGGEST = 'suggest';
    public const TASK_TRANSLATE = 'translate';
    public const TASK_SPELLCHECK = 'spellcheck';
    public const TASK_OCR = 'ocr';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SKIPPED = 'skipped';

    private array $settings = [];

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Load job queue settings.
     */
    private function loadSettings(): void
    {
        $rows = DB::table('ahg_ai_settings')
            ->where('feature', 'jobqueue')
            ->get();

        foreach ($rows as $row) {
            $this->settings[$row->setting_key] = $row->setting_value;
        }
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Create a new batch job.
     */
    public function createBatch(array $data): int
    {
        $batchId = DB::table('ahg_ai_batch')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'task_types' => json_encode($data['task_types']),
            'status' => self::STATUS_PENDING,
            'priority' => $data['priority'] ?? 5,
            'total_items' => 0,
            'completed_items' => 0,
            'failed_items' => 0,
            'max_concurrent' => $data['max_concurrent'] ?? (int) $this->getSetting('default_max_concurrent', 5),
            'delay_between_ms' => $data['delay_between_ms'] ?? (int) $this->getSetting('default_delay_ms', 1000),
            'max_retries' => $data['max_retries'] ?? (int) $this->getSetting('default_max_retries', 3),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'options' => isset($data['options']) ? json_encode($data['options']) : null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEvent($batchId, null, 'created', 'Batch job created');

        return $batchId;
    }

    /**
     * Add items to a batch.
     */
    public function addItemsToBatch(int $batchId, array $objectIds, array $taskTypes): int
    {
        $batch = $this->getBatch($batchId);
        if (!$batch) {
            throw new \Exception("Batch not found: {$batchId}");
        }

        $count = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($objectIds as $objectId) {
            foreach ($taskTypes as $taskType) {
                // Check if job already exists
                $exists = DB::table('ahg_ai_job')
                    ->where('batch_id', $batchId)
                    ->where('object_id', $objectId)
                    ->where('task_type', $taskType)
                    ->exists();

                if (!$exists) {
                    DB::table('ahg_ai_job')->insert([
                        'batch_id' => $batchId,
                        'object_id' => $objectId,
                        'task_type' => $taskType,
                        'status' => self::STATUS_PENDING,
                        'priority' => $batch->priority,
                        'attempt_count' => 0,
                        'created_at' => $now,
                    ]);
                    $count++;
                }
            }
        }

        // Update batch total
        DB::table('ahg_ai_batch')
            ->where('id', $batchId)
            ->update([
                'total_items' => DB::raw('total_items + ' . $count),
                'updated_at' => $now,
            ]);

        $this->logEvent($batchId, null, 'items_added', "Added {$count} job items");

        return $count;
    }

    /**
     * Start a batch job.
     */
    public function startBatch(int $batchId): bool
    {
        $batch = $this->getBatch($batchId);
        if (!$batch || !in_array($batch->status, [self::STATUS_PENDING, self::STATUS_PAUSED])) {
            return false;
        }

        DB::table('ahg_ai_batch')
            ->where('id', $batchId)
            ->update([
                'status' => self::STATUS_RUNNING,
                'started_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logEvent($batchId, null, 'started', 'Batch job started');

        // Queue pending items
        $this->queueBatchItems($batchId);

        return true;
    }

    /**
     * Queue items from a batch to Gearman.
     */
    public function queueBatchItems(int $batchId, int $limit = null): int
    {
        $batch = $this->getBatch($batchId);
        if (!$batch || $batch->status !== self::STATUS_RUNNING) {
            return 0;
        }

        $limit = $limit ?? $batch->max_concurrent;

        // Get pending items
        $items = DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->where('status', self::STATUS_PENDING)
            ->orderBy('priority')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $queued = 0;
        foreach ($items as $item) {
            if ($this->queueJob($item)) {
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Queue a single job to Gearman.
     */
    public function queueJob(object $job): bool
    {
        $now = date('Y-m-d H:i:s');

        // Update job status to queued
        DB::table('ahg_ai_job')
            ->where('id', $job->id)
            ->update([
                'status' => self::STATUS_QUEUED,
                'queued_at' => $now,
                'updated_at' => $now,
            ]);

        // Try to submit to Gearman
        try {
            $client = new \GearmanClient();
            $client->addServer();

            $params = [
                'jobId' => $job->id,
                'batchId' => $job->batch_id,
                'objectId' => $job->object_id,
                'taskType' => $job->task_type,
            ];

            // Add batch options
            $batch = $this->getBatch($job->batch_id);
            if ($batch && $batch->options) {
                $params['options'] = json_decode($batch->options, true);
            }

            $handle = $client->doBackground('arAiBatchJob', json_encode($params));

            DB::table('ahg_ai_job')
                ->where('id', $job->id)
                ->update([
                    'gearman_handle' => $handle,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return true;
        } catch (\Exception $e) {
            // Gearman not available, fall back to synchronous processing marker
            DB::table('ahg_ai_job')
                ->where('id', $job->id)
                ->update([
                    'status' => self::STATUS_PENDING,
                    'error_message' => 'Gearman not available: ' . $e->getMessage(),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return false;
        }
    }

    /**
     * Process a job (called by worker).
     */
    public function processJob(int $jobId): array
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        $startTime = microtime(true);
        $now = date('Y-m-d H:i:s');

        // Update status to running
        DB::table('ahg_ai_job')
            ->where('id', $jobId)
            ->update([
                'status' => self::STATUS_RUNNING,
                'started_at' => $now,
                'attempt_count' => DB::raw('attempt_count + 1'),
                'updated_at' => $now,
            ]);

        try {
            // Execute the task
            $result = $this->executeTask($job);

            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            // Mark as completed
            DB::table('ahg_ai_job')
                ->where('id', $jobId)
                ->update([
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'processing_time_ms' => $processingTime,
                    'result_data' => json_encode($result),
                    'error_message' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->updateBatchProgress($job->batch_id);
            $this->logEvent($job->batch_id, $jobId, 'completed', "Job completed in {$processingTime}ms");

            return ['success' => true, 'result' => $result, 'processing_time_ms' => $processingTime];

        } catch (\Exception $e) {
            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            // Check if we should retry
            $batch = $this->getBatch($job->batch_id);
            $maxRetries = $batch ? $batch->max_retries : 3;

            if ($job->attempt_count < $maxRetries) {
                // Queue for retry
                DB::table('ahg_ai_job')
                    ->where('id', $jobId)
                    ->update([
                        'status' => self::STATUS_PENDING,
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode() ?: 'EXCEPTION',
                        'processing_time_ms' => $processingTime,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->logEvent($job->batch_id, $jobId, 'retry', "Retry scheduled: {$e->getMessage()}");
            } else {
                // Mark as failed
                DB::table('ahg_ai_job')
                    ->where('id', $jobId)
                    ->update([
                        'status' => self::STATUS_FAILED,
                        'completed_at' => date('Y-m-d H:i:s'),
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode() ?: 'EXCEPTION',
                        'processing_time_ms' => $processingTime,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->updateBatchProgress($job->batch_id, true);
                $this->logEvent($job->batch_id, $jobId, 'failed', "Job failed: {$e->getMessage()}");
            }

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute a task based on type.
     */
    private function executeTask(object $job): array
    {
        switch ($job->task_type) {
            case self::TASK_NER:
                return $this->executeNerTask($job);

            case self::TASK_SUMMARIZE:
                return $this->executeSummarizeTask($job);

            case self::TASK_SUGGEST:
                return $this->executeSuggestTask($job);

            case self::TASK_TRANSLATE:
                return $this->executeTranslateTask($job);

            case self::TASK_SPELLCHECK:
                return $this->executeSpellcheckTask($job);

            case self::TASK_OCR:
                return $this->executeOcrTask($job);

            default:
                throw new \Exception("Unknown task type: {$job->task_type}");
        }
    }

    /**
     * Execute NER extraction task.
     */
    private function executeNerTask(object $job): array
    {
        $service = new \ahgNerService();
        $io = \QubitInformationObject::getById($job->object_id);

        if (!$io) {
            throw new \Exception("Object not found: {$job->object_id}");
        }

        $text = $this->extractText($io);
        if (empty($text)) {
            return ['skipped' => true, 'reason' => 'No text content'];
        }

        $result = $service->extract($text);
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'NER extraction failed');
        }

        // Store entities
        $this->storeNerEntities($job->object_id, $result['entities']);

        return [
            'entity_count' => $result['entity_count'] ?? count($result['entities'] ?? []),
            'entities' => $result['entities'],
        ];
    }

    /**
     * Execute summarization task.
     */
    private function executeSummarizeTask(object $job): array
    {
        $service = new \ahgNerService();
        $io = \QubitInformationObject::getById($job->object_id);

        if (!$io) {
            throw new \Exception("Object not found: {$job->object_id}");
        }

        $text = $this->extractTextFromPdf($io);
        if (strlen($text) < 200) {
            return ['skipped' => true, 'reason' => 'Insufficient text for summarization'];
        }

        $result = $service->summarize($text);
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Summarization failed');
        }

        // Save to scope_and_content
        $io->setScopeAndContent($result['summary']);
        $io->save();

        return [
            'summary_length' => strlen($result['summary']),
            'original_length' => strlen($text),
        ];
    }

    /**
     * Execute LLM suggestion task.
     */
    private function executeSuggestTask(object $job): array
    {
        $service = new DescriptionService();
        $result = $service->generateSuggestion($job->object_id);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Description suggestion failed');
        }

        return [
            'suggestion_id' => $result['suggestion_id'],
            'tokens_used' => $result['tokens_used'] ?? null,
        ];
    }

    /**
     * Execute translation task.
     */
    private function executeTranslateTask(object $job): array
    {
        // Get batch options for translation
        $batch = $this->getBatch($job->batch_id);
        $options = $batch && $batch->options ? json_decode($batch->options, true) : [];

        $fromCulture = $options['from_culture'] ?? 'en';
        $toCulture = $options['to_culture'] ?? 'af';
        $fields = $options['fields'] ?? ['title', 'scopeAndContent'];

        // Execute translation
        $io = \QubitInformationObject::getById($job->object_id);
        if (!$io) {
            throw new \Exception("Object not found: {$job->object_id}");
        }

        $translated = 0;
        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);

            if (!method_exists($io, $getter)) {
                continue;
            }

            $text = $io->$getter(['culture' => $fromCulture]);
            if (empty($text)) {
                continue;
            }

            // Call translation API
            $result = $this->translateText($text, $fromCulture, $toCulture);
            if ($result['success']) {
                $io->$setter($result['translated'], ['culture' => $toCulture]);
                $translated++;
            }
        }

        $io->save();

        return ['fields_translated' => $translated];
    }

    /**
     * Execute spellcheck task.
     */
    private function executeSpellcheckTask(object $job): array
    {
        $io = \QubitInformationObject::getById($job->object_id);
        if (!$io) {
            throw new \Exception("Object not found: {$job->object_id}");
        }

        $language = $this->getSetting('spellcheck_language', 'en');
        $fields = ['title', 'scopeAndContent', 'archivalHistory'];
        $errors = [];

        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            if (!method_exists($io, $getter)) {
                continue;
            }

            $text = $io->$getter(['fallback' => true]);
            if (empty($text)) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'spell_');
            file_put_contents($tmp, $text);
            exec("cat " . escapeshellarg($tmp) . " | aspell -l " . escapeshellarg($language) . " list 2>/dev/null | sort -u", $misspelled);
            unlink($tmp);

            if (!empty($misspelled)) {
                $errors[$field] = $misspelled;
            }
        }

        return [
            'error_count' => array_sum(array_map('count', $errors)),
            'errors' => $errors,
        ];
    }

    /**
     * Execute OCR task.
     */
    private function executeOcrTask(object $job): array
    {
        $io = \QubitInformationObject::getById($job->object_id);
        if (!$io) {
            throw new \Exception("Object not found: {$job->object_id}");
        }

        $ocrText = '';
        $pageCount = 0;

        foreach ($io->digitalObjectsRelatedByobjectId as $do) {
            $path = $do->getAbsolutePath();
            if (!file_exists($path)) {
                continue;
            }

            $mimeType = $do->mimeType;

            if (strpos($mimeType, 'image/') === 0) {
                // OCR image using tesseract
                $tmp = tempnam(sys_get_temp_dir(), 'ocr_');
                exec("tesseract " . escapeshellarg($path) . " " . escapeshellarg($tmp) . " 2>/dev/null");
                if (file_exists($tmp . '.txt')) {
                    $ocrText .= file_get_contents($tmp . '.txt') . "\n";
                    unlink($tmp . '.txt');
                    $pageCount++;
                }
                @unlink($tmp);
            } elseif ($mimeType === 'application/pdf') {
                // Extract text from PDF
                $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
                exec("pdftotext -enc UTF-8 " . escapeshellarg($path) . " " . escapeshellarg($tmp) . " 2>/dev/null");
                if (file_exists($tmp)) {
                    $ocrText .= file_get_contents($tmp) . "\n";
                    unlink($tmp);
                    $pageCount++;
                }
            }
        }

        if (empty(trim($ocrText))) {
            return ['skipped' => true, 'reason' => 'No OCR text extracted'];
        }

        // Store OCR text (if IIIF plugin is available)
        $this->storeOcrText($job->object_id, $ocrText);

        return [
            'text_length' => strlen($ocrText),
            'pages_processed' => $pageCount,
        ];
    }

    /**
     * Extract text from information object.
     */
    private function extractText($io): string
    {
        $text = '';
        foreach (['title', 'scopeAndContent', 'archivalHistory'] as $field) {
            $getter = 'get' . ucfirst($field);
            $val = $io->$getter(['fallback' => true]);
            if ($val) {
                $text .= $val . "\n";
            }
        }

        // Also try PDF extraction
        $text .= $this->extractTextFromPdf($io);

        return trim($text);
    }

    /**
     * Extract text from PDF digital objects.
     */
    private function extractTextFromPdf($io): string
    {
        $text = '';
        foreach ($io->digitalObjectsRelatedByobjectId as $do) {
            if ($do->mimeType === 'application/pdf') {
                $path = $do->getAbsolutePath();
                if (file_exists($path)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
                    exec("pdftotext -enc UTF-8 " . escapeshellarg($path) . " " . escapeshellarg($tmp) . " 2>/dev/null");
                    if (file_exists($tmp)) {
                        $text .= file_get_contents($tmp);
                        unlink($tmp);
                    }
                }
                break;
            }
        }
        return $text;
    }

    /**
     * Store NER entities.
     */
    private function storeNerEntities(int $objectId, array $entities): void
    {
        $totalCount = 0;
        foreach ($entities as $type => $values) {
            if (is_array($values)) {
                $totalCount += count($values);
            }
        }

        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'batch',
            'status' => 'completed',
            'entity_count' => $totalCount,
            'extracted_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($entities as $type => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                DB::table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $value,
                    'confidence' => 0.95,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Store OCR text.
     */
    private function storeOcrText(int $objectId, string $ocrText): void
    {
        // Check if iiif_ocr_text table exists
        if (!DB::getSchemaBuilder()->hasTable('iiif_ocr_text')) {
            return;
        }

        // Get digital object ID
        $do = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$do) {
            return;
        }

        DB::table('iiif_ocr_text')->updateOrInsert(
            ['digital_object_id' => $do->id],
            [
                'full_text' => $ocrText,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Translate text using Argos.
     */
    private function translateText(string $text, string $from, string $to): array
    {
        $settings = [];
        $rows = DB::table('ahg_ai_settings')->where('feature', 'general')->get();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        $apiUrl = rtrim($settings['api_url'] ?? 'http://localhost:5004/ai/v1', '/') . '/translate';

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'text' => $text,
                'source' => $from,
                'target' => $to,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . ($settings['api_key'] ?? ''),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP {$httpCode}"];
        }

        $data = json_decode($response, true);
        return [
            'success' => isset($data['translated']),
            'translated' => $data['translated'] ?? '',
        ];
    }

    /**
     * Update batch progress.
     */
    public function updateBatchProgress(int $batchId, bool $failed = false): void
    {
        $stats = DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        $total = $stats->total ?: 1;
        $completed = $stats->completed ?: 0;
        $failedCount = $stats->failed ?: 0;
        $progress = round(($completed + $failedCount) / $total * 100, 2);

        $updates = [
            'completed_items' => $completed,
            'failed_items' => $failedCount,
            'progress_percent' => $progress,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Check if batch is complete
        if (($completed + $failedCount) >= $total) {
            $updates['status'] = $failedCount > 0 && $failedCount == $total
                ? self::STATUS_FAILED
                : self::STATUS_COMPLETED;
            $updates['completed_at'] = date('Y-m-d H:i:s');

            $this->logEvent($batchId, null, 'completed', "Batch completed: {$completed} succeeded, {$failedCount} failed");
        }

        DB::table('ahg_ai_batch')
            ->where('id', $batchId)
            ->update($updates);

        // Queue more items if batch is still running
        $batch = $this->getBatch($batchId);
        if ($batch && $batch->status === self::STATUS_RUNNING) {
            $this->queueBatchItems($batchId, 1);
        }
    }

    /**
     * Pause a batch.
     */
    public function pauseBatch(int $batchId): bool
    {
        $batch = $this->getBatch($batchId);
        if (!$batch || $batch->status !== self::STATUS_RUNNING) {
            return false;
        }

        DB::table('ahg_ai_batch')
            ->where('id', $batchId)
            ->update([
                'status' => self::STATUS_PAUSED,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logEvent($batchId, null, 'paused', 'Batch job paused');

        return true;
    }

    /**
     * Resume a batch.
     */
    public function resumeBatch(int $batchId): bool
    {
        return $this->startBatch($batchId);
    }

    /**
     * Cancel a batch.
     */
    public function cancelBatch(int $batchId): bool
    {
        $batch = $this->getBatch($batchId);
        if (!$batch) {
            return false;
        }

        DB::table('ahg_ai_batch')
            ->where('id', $batchId)
            ->update([
                'status' => self::STATUS_CANCELLED,
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Cancel pending jobs
        DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_QUEUED])
            ->update([
                'status' => self::STATUS_CANCELLED,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logEvent($batchId, null, 'cancelled', 'Batch job cancelled');

        return true;
    }

    /**
     * Retry failed jobs in a batch.
     */
    public function retryFailed(int $batchId): int
    {
        $count = DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->where('status', self::STATUS_FAILED)
            ->update([
                'status' => self::STATUS_PENDING,
                'attempt_count' => 0,
                'error_message' => null,
                'error_code' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if ($count > 0) {
            DB::table('ahg_ai_batch')
                ->where('id', $batchId)
                ->update([
                    'status' => self::STATUS_PENDING,
                    'failed_items' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logEvent($batchId, null, 'retry_all', "Retrying {$count} failed jobs");
        }

        return $count;
    }

    /**
     * Delete a batch and its jobs.
     */
    public function deleteBatch(int $batchId): bool
    {
        DB::table('ahg_ai_job_log')->where('batch_id', $batchId)->delete();
        DB::table('ahg_ai_job')->where('batch_id', $batchId)->delete();
        DB::table('ahg_ai_batch')->where('id', $batchId)->delete();

        return true;
    }

    /**
     * Get a batch by ID.
     */
    public function getBatch(int $batchId): ?object
    {
        return DB::table('ahg_ai_batch')->where('id', $batchId)->first();
    }

    /**
     * Get a job by ID.
     */
    public function getJob(int $jobId): ?object
    {
        return DB::table('ahg_ai_job')->where('id', $jobId)->first();
    }

    /**
     * Get all batches with optional filters.
     */
    public function getBatches(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('ahg_ai_batch')
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->offset($offset)->limit($limit)->get()->all();
    }

    /**
     * Get jobs for a batch.
     */
    public function getBatchJobs(int $batchId, array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $query = DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->orderBy('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['task_type'])) {
            $query->where('task_type', $filters['task_type']);
        }

        return $query->offset($offset)->limit($limit)->get()->all();
    }

    /**
     * Get batch statistics.
     */
    public function getBatchStats(int $batchId): array
    {
        $stats = DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "queued" THEN 1 ELSE 0 END) as queued,
                SUM(CASE WHEN status = "running" THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "skipped" THEN 1 ELSE 0 END) as skipped,
                AVG(processing_time_ms) as avg_processing_time
            ')
            ->first();

        $byTaskType = DB::table('ahg_ai_job')
            ->where('batch_id', $batchId)
            ->selectRaw('task_type, COUNT(*) as count, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->groupBy('task_type')
            ->get()
            ->keyBy('task_type')
            ->toArray();

        return [
            'total' => $stats->total ?? 0,
            'pending' => $stats->pending ?? 0,
            'queued' => $stats->queued ?? 0,
            'running' => $stats->running ?? 0,
            'completed' => $stats->completed ?? 0,
            'failed' => $stats->failed ?? 0,
            'skipped' => $stats->skipped ?? 0,
            'avg_processing_time_ms' => round($stats->avg_processing_time ?? 0),
            'by_task_type' => $byTaskType,
        ];
    }

    /**
     * Get recent log events.
     */
    public function getLogEvents(int $batchId = null, int $limit = 50): array
    {
        $query = DB::table('ahg_ai_job_log')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        return $query->get()->all();
    }

    /**
     * Log an event.
     */
    public function logEvent(?int $batchId, ?int $jobId, string $eventType, string $message, array $details = []): void
    {
        DB::table('ahg_ai_job_log')->insert([
            'batch_id' => $batchId,
            'job_id' => $jobId,
            'event_type' => $eventType,
            'message' => $message,
            'details' => !empty($details) ? json_encode($details) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check server load and pause if needed.
     */
    public function checkServerLoad(): bool
    {
        if ($this->getSetting('pause_on_high_load', '1') !== '1') {
            return true;
        }

        $threshold = (int) $this->getSetting('high_load_threshold', 80);

        // Check CPU load
        $load = sys_getloadavg();
        $cpuCount = (int) shell_exec('nproc') ?: 1;
        $loadPercent = ($load[0] / $cpuCount) * 100;

        return $loadPercent < $threshold;
    }

    /**
     * Get available task types.
     */
    public static function getTaskTypes(): array
    {
        return [
            self::TASK_NER => [
                'label' => 'Named Entity Recognition',
                'description' => 'Extract persons, organizations, places, and dates',
                'icon' => 'fa-user-tag',
            ],
            self::TASK_SUMMARIZE => [
                'label' => 'Summarization',
                'description' => 'Generate AI summary from PDF content',
                'icon' => 'fa-file-alt',
            ],
            self::TASK_SUGGEST => [
                'label' => 'Description Suggestion',
                'description' => 'Generate scope_and_content using LLM',
                'icon' => 'fa-robot',
            ],
            self::TASK_TRANSLATE => [
                'label' => 'Translation',
                'description' => 'Translate metadata between languages',
                'icon' => 'fa-language',
            ],
            self::TASK_SPELLCHECK => [
                'label' => 'Spell Check',
                'description' => 'Check spelling in metadata fields',
                'icon' => 'fa-spell-check',
            ],
            self::TASK_OCR => [
                'label' => 'OCR Extraction',
                'description' => 'Extract text from images and PDFs',
                'icon' => 'fa-file-image',
            ],
        ];
    }

    /**
     * Cleanup old completed batches.
     */
    public function cleanup(int $days = null): int
    {
        $days = $days ?? (int) $this->getSetting('auto_cleanup_days', 30);
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $batches = DB::table('ahg_ai_batch')
            ->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED])
            ->where('completed_at', '<', $cutoff)
            ->pluck('id');

        foreach ($batches as $batchId) {
            $this->deleteBatch($batchId);
        }

        return count($batches);
    }
}
