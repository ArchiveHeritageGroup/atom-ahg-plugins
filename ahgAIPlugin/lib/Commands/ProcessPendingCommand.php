<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Process pending AI extraction queue.
 *
 * Processes records queued for NER extraction when Gearman is unavailable.
 * Run via cron: php bin/atom ai:process-pending --limit=50
 */
class ProcessPendingCommand extends BaseCommand
{
    protected string $name = 'ai:process-pending';
    protected string $description = 'Process pending AI extraction queue';
    protected string $detailedDescription = <<<'EOF'
    Processes records queued for AI extraction when Gearman is not available.
    Serves as a fallback for auto-triggered extractions from document uploads.

    Examples:
      php bin/atom ai:process-pending
      php bin/atom ai:process-pending --limit=50
      php bin/atom ai:process-pending --task-type=summarize
      php bin/atom ai:process-pending --dry-run
    EOF;

    protected function configure(): void
    {
        $this->addOption('limit', null, 'Maximum items to process', '50');
        $this->addOption('task-type', null, 'Task type to process (ner, summarize, etc)', 'ner');
        $this->addOption('dry-run', null, 'Preview without processing');
    }

    protected function handle(): int
    {
        $limit = (int) ($this->option('limit') ?? 50);
        $taskType = $this->option('task-type') ?? 'ner';
        $dryRun = $this->hasOption('dry-run');

        $this->info("Processing pending {$taskType} extractions (limit: {$limit})");

        // Get pending items
        $pending = DB::table('ahg_ai_pending_extraction')
            ->where('status', 'pending')
            ->where('task_type', $taskType)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending items found');
            return 0;
        }

        $this->info(sprintf('Found %d pending items', count($pending)));

        if ($dryRun) {
            foreach ($pending as $item) {
                $this->line(sprintf('  Would process: object_id=%d, digital_object_id=%d',
                    $item->object_id, $item->digital_object_id ?? 0));
            }
            return 0;
        }

        $processed = 0;
        $failed = 0;

        foreach ($pending as $item) {
            // Mark as processing
            DB::table('ahg_ai_pending_extraction')
                ->where('id', $item->id)
                ->update([
                    'status' => 'processing',
                    'attempt_count' => DB::raw('attempt_count + 1'),
                ]);

            try {
                $result = $this->processItem($item, $taskType);

                if ($result['success']) {
                    DB::table('ahg_ai_pending_extraction')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'completed',
                            'processed_at' => date('Y-m-d H:i:s'),
                        ]);
                    $processed++;
                    $this->line(sprintf('  Processed object_id=%d: %s',
                        $item->object_id, $result['message'] ?? 'OK'));
                } else {
                    throw new \Exception($result['error'] ?? 'Unknown error');
                }
            } catch (\Exception $e) {
                $failed++;

                // Check if we should retry or mark as failed
                $maxRetries = 3;
                if ($item->attempt_count >= $maxRetries) {
                    DB::table('ahg_ai_pending_extraction')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'processed_at' => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    // Reset to pending for retry
                    DB::table('ahg_ai_pending_extraction')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'pending',
                            'error_message' => $e->getMessage(),
                        ]);
                }

                $this->error(sprintf('Failed object_id=%d: %s',
                    $item->object_id, $e->getMessage()));
            }
        }

        $this->info(sprintf('Complete: %d processed, %d failed', $processed, $failed));

        return $failed > 0 ? 1 : 0;
    }

    private function processItem(object $item, string $taskType): array
    {
        $io = \QubitInformationObject::getById($item->object_id);
        if (!$io) {
            return ['success' => false, 'error' => 'Object not found'];
        }

        switch ($taskType) {
            case 'ner':
                return $this->processNer($io);

            case 'summarize':
                return $this->processSummarize($io);

            default:
                return ['success' => false, 'error' => "Unknown task type: {$taskType}"];
        }
    }

    private function processNer($io): array
    {
        $text = $this->extractText($io);
        if (empty($text)) {
            return ['success' => true, 'message' => 'No text content, skipped'];
        }

        $settings = $this->getSettings();

        $apiUrl = rtrim($settings['api_url'] ?? 'https://ai.theahg.co.za/ai/v1', '/') . '/ner/extract';

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['text' => substr($text, 0, 100000)]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . ($settings['api_key'] ?? ''),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) ($settings['api_timeout'] ?? 60),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "CURL error: {$error}"];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "API returned HTTP {$httpCode}"];
        }

        $data = json_decode($response, true);
        if (!isset($data['entities'])) {
            return ['success' => false, 'error' => 'Invalid API response'];
        }

        // New flat per-entity payload with real offsets + score. Absent on
        // pre-deploy API versions — storeEntities() falls back to the legacy
        // dict in that case.
        $entitiesV2 = $data['entities_v2'] ?? null;

        $this->storeEntities($io->id, $data['entities'], $text, $entitiesV2);

        $entityCount = 0;
        foreach ($data['entities'] as $values) {
            if (is_array($values)) {
                $entityCount += count($values);
            }
        }

        return ['success' => true, 'message' => "Extracted {$entityCount} entities"];
    }

    private function processSummarize($io): array
    {
        $text = $this->extractTextFromPdf($io);
        if (strlen($text) < 200) {
            return ['success' => true, 'message' => 'Insufficient text, skipped'];
        }

        $settings = $this->getSettings();
        $apiUrl = rtrim($settings['api_url'] ?? 'https://ai.theahg.co.za/ai/v1', '/') . '/summarize';

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'text' => $text,
                'max_length' => (int) ($settings['summarizer_max_length'] ?? 500),
                'min_length' => (int) ($settings['summarizer_min_length'] ?? 100),
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . ($settings['api_key'] ?? ''),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "API returned HTTP {$httpCode}"];
        }

        $data = json_decode($response, true);
        if (!isset($data['summary'])) {
            return ['success' => false, 'error' => 'Invalid API response'];
        }

        $io->setScopeAndContent($data['summary']);
        $io->save();

        return ['success' => true, 'message' => 'Summary saved (' . strlen($data['summary']) . ' chars)'];
    }

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

        $text .= $this->extractTextFromPdf($io);

        return trim($text);
    }

    private function extractTextFromPdf($io): string
    {
        $text = '';
        foreach ($io->digitalObjectsRelatedByobjectId as $do) {
            if ($do->mimeType === 'application/pdf') {
                $path = $do->getAbsolutePath();
                if (file_exists($path)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
                    exec('pdftotext -enc UTF-8 ' . escapeshellarg($path) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null');
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

    private function getSettings(): array
    {
        $settings = [];
        $rows = DB::table('ahg_ai_settings')
            ->whereIn('feature', ['general', 'ner', 'summarize'])
            ->get();

        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        return $settings;
    }

    /**
     * Store NER entities.
     *
     * When $entitiesV2 (the flat per-entity list from the new API response)
     * is a non-empty array, it is iterated instead of the legacy dict: each
     * record carries a real per-entity offset and score, and confidence is
     * written from that score (real float or null — never a fabricated 0.95).
     * When $entitiesV2 is null/empty (pre-deploy API), the legacy dict is
     * iterated and confidence is written as null (no score available).
     *
     * @param int        $objectId
     * @param array      $entities    legacy {TYPE: [values]} dict
     * @param string|null $sourceText exact text NER ran against
     * @param array|null $entitiesV2  flat list of {value,type,offset_start,offset_end,score}
     */
    private function storeEntities(int $objectId, array $entities, ?string $sourceText = null, ?array $entitiesV2 = null): void
    {
        $useV2 = is_array($entitiesV2) && !empty($entitiesV2);

        if ($useV2) {
            $totalCount = count($entitiesV2);
        } else {
            $totalCount = 0;
            foreach ($entities as $values) {
                if (is_array($values)) {
                    $totalCount += count($values);
                }
            }
        }

        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'auto_trigger',
            'status' => 'completed',
            'entity_count' => $totalCount,
            'extracted_at' => date('Y-m-d H:i:s'),
        ]);

        if ($useV2) {
            foreach ($entitiesV2 as $rec) {
                if (!is_array($rec) || !isset($rec['value'], $rec['type'])) {
                    continue;
                }
                $score = isset($rec['score']) && $rec['score'] !== null
                    ? (float) $rec['score']
                    : null;

                $nerEntityId = (int) DB::table('ahg_ner_entity')->insertGetId([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $rec['type'],
                    'entity_value' => $rec['value'],
                    'confidence' => $score,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $knownOffset = (isset($rec['offset_start'], $rec['offset_end']))
                    ? ['start' => (int) $rec['offset_start'], 'end' => (int) $rec['offset_end']]
                    : null;

                $this->maybePromoteToMention($nerEntityId, $sourceText, $knownOffset, $score);
            }
            return;
        }

        foreach ($entities as $type => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                $nerEntityId = (int) DB::table('ahg_ner_entity')->insertGetId([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $value,
                    'confidence' => null,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $this->maybePromoteToMention($nerEntityId, $sourceText);
            }
        }
    }

    /**
     * Hook: forward newly-inserted ner_entity rows to the authority-resolution
     * engine for promotion to a workflow mention with neighbourhood context.
     * Safe no-op when ahgAuthorityResolutionPlugin isn't installed.
     *
     * @param int        $nerEntityId
     * @param string|null $sourceText
     * @param array|null $knownOffset    {start,end} per-entity offset from entities_v2
     * @param float|null $realConfidence per-entity score from entities_v2
     */
    private function maybePromoteToMention(int $nerEntityId, ?string $sourceText, ?array $knownOffset = null, ?float $realConfidence = null): void
    {
        if ($nerEntityId <= 0) {
            return;
        }
        if (!class_exists(\AtomFramework\Services\AuthorityResolution\PromoteToMentionService::class)) {
            return;
        }
        try {
            $promoter = new \AtomFramework\Services\AuthorityResolution\PromoteToMentionService(
                new \AtomFramework\Services\AuthorityResolution\ContextDerivationService()
            );
            $promoter->promote($nerEntityId, $sourceText, $knownOffset, $realConfidence);
        } catch (\Throwable $e) {
            error_log('ProcessPendingCommand::maybePromoteToMention failed (ner_entity_id=' . $nerEntityId . '): ' . $e->getMessage());
        }
    }
}
