<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for batch NER extraction.
 */
class NerExtractCommand extends BaseCommand
{
    protected string $name = 'ai:ner-extract';
    protected string $description = 'Extract named entities from archival records';
    protected string $detailedDescription = <<<'EOF'
    Extract named entities (persons, organizations, places, dates) from records.

    Examples:
      php bin/atom ai:ner-extract --all --limit=100
      php bin/atom ai:ner-extract --all --with-pdf --limit=100
      php bin/atom ai:ner-extract --object=12345
      php bin/atom ai:ner-extract --repository=5 --limit=50
      php bin/atom ai:ner-extract --uploaded-today
      php bin/atom ai:ner-extract --all --queue
    EOF;

    protected function configure(): void
    {
        $this->addOption('object', null, 'Process specific object ID');
        $this->addOption('repository', null, 'Process all objects in repository ID');
        $this->addOption('all', null, 'Process all unprocessed objects');
        $this->addOption('uploaded-today', null, 'Process objects uploaded today');
        $this->addOption('limit', null, 'Maximum number to process', '100');
        $this->addOption('dry-run', null, 'Show what would be processed');
        $this->addOption('queue', null, 'Queue jobs instead of direct processing');
        $this->addOption('with-pdf', null, 'Extract text from PDFs (slower but more content)');
    }

    protected function handle(): int
    {
        $this->info('Starting NER extraction task');

        if ($this->hasOption('with-pdf')) {
            $this->info('PDF extraction enabled');
        }

        $nerSettings = $this->getSettings('ner');
        if (($nerSettings['enabled'] ?? '1') !== '1') {
            $this->error('NER is disabled in settings');
            return 1;
        }

        $objectIds = $this->getObjectsToProcess();
        if (empty($objectIds)) {
            $this->info('No objects found to process');
            return 0;
        }

        $this->info(sprintf('Found %d objects to process', count($objectIds)));

        if ($this->hasOption('dry-run')) {
            foreach ($objectIds as $id) {
                $this->line("  Would process: $id");
            }
            return 0;
        }

        $generalSettings = $this->getSettings('general');
        $settings = array_merge($generalSettings, $nerSettings);

        $processed = 0;
        $errors = 0;
        $limit = (int) ($this->option('limit') ?: 100);
        $withPdf = $this->hasOption('with-pdf') || ($settings['extract_from_pdf'] ?? '0') === '1';

        foreach ($objectIds as $objectId) {
            if ($processed >= $limit) {
                break;
            }

            try {
                if ($this->hasOption('queue')) {
                    $this->queueJob($objectId);
                    $this->line("  Queued: $objectId");
                } else {
                    $this->processObject($objectId, $settings, $withPdf);
                    $this->line("  Processed: $objectId");
                }
                $processed++;
            } catch (\Exception $e) {
                $this->error("Error on $objectId: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info(sprintf('Done: %d processed, %d errors', $processed, $errors));
        return $errors > 0 ? 1 : 0;
    }

    private function getSettings(string $feature): array
    {
        $settings = [];
        $rows = DB::table('ahg_ai_settings')
            ->where('feature', $feature)
            ->get();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    private function getObjectsToProcess(): array
    {
        if ($this->option('object')) {
            return [(int) $this->option('object')];
        }

        $query = DB::table('information_object as io')
            ->select('io.id')
            ->where('io.id', '!=', \QubitInformationObject::ROOT_ID);

        if ($this->option('repository')) {
            $query->where('io.repository_id', (int) $this->option('repository'));
        }

        if ($this->hasOption('uploaded-today')) {
            $query->whereDate('io.created_at', '=', date('Y-m-d'));
        }

        if ($this->hasOption('all') || $this->hasOption('uploaded-today')) {
            $query->leftJoin('ahg_ner_extraction as ne', 'io.id', '=', 'ne.object_id')
                  ->whereNull('ne.id');
        }

        return $query->limit((int) ($this->option('limit') ?: 100))->pluck('id')->toArray();
    }

    private function queueJob(int $objectId): void
    {
        $job = new \QubitJob();
        $job->name = 'arNerExtractJob';
        $job->setParameter('objectId', $objectId);
        $job->setParameter('runNer', true);
        $job->setParameter('runSummarize', false);
        $job->save();
    }

    private function processObject(int $objectId, array $settings, bool $withPdf = false): void
    {
        $io = \QubitInformationObject::getById($objectId);
        if (!$io) {
            throw new \Exception('Not found');
        }

        $text = $this->extractText($io, $withPdf);
        if (empty($text) || strlen($text) < 10) {
            $this->storeEntities($objectId, []);
            return;
        }

        $this->callNerApi($objectId, $text, $settings);
    }

    private function extractText($io, bool $withPdf = false): string
    {
        $text = '';

        foreach (['title', 'scopeAndContent', 'archivalHistory'] as $field) {
            $getter = 'get' . ucfirst($field);
            $val = $io->$getter(['fallback' => true]);
            if ($val) {
                $text .= $val . "\n";
            }
        }

        if ($withPdf) {
            foreach ($io->digitalObjectsRelatedByobjectId as $do) {
                if ($do->mimeType === 'application/pdf') {
                    $pdfText = $this->extractPdfText($do);
                    if (!empty($pdfText)) {
                        $text .= "\n" . $pdfText;
                    }
                    break;
                }
            }
        }

        return trim($text);
    }

    private function extractPdfText($digitalObject): string
    {
        $path = $digitalObject->path;
        $basePath = \sfConfig::get('sf_root_dir') . $path;

        $pdfFile = null;
        if (is_dir($basePath)) {
            $files = glob($basePath . '*.pdf');
            if (!empty($files)) {
                $pdfFile = $files[0];
            }
        }

        if (!$pdfFile || !file_exists($pdfFile)) {
            return '';
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        exec(sprintf(
            'pdftotext -enc UTF-8 %s %s 2>/dev/null',
            escapeshellarg($pdfFile),
            escapeshellarg($tempFile)
        ));

        $text = '';
        if (file_exists($tempFile)) {
            $text = file_get_contents($tempFile);
            unlink($tempFile);
        }

        if (strlen($text) > 50000) {
            $text = substr($text, 0, 50000);
        }

        return $text;
    }

    private function callNerApi(int $objectId, string $text, array $settings): void
    {
        $apiUrl = rtrim($settings['api_url'], '/') . '/ner/extract';

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['text' => $text]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-Key: ' . ($settings['api_key'] ?? '')],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("API error: HTTP $httpCode");
        }

        $data = json_decode($response, true);
        if (!isset($data['entities'])) {
            throw new \Exception('Invalid response');
        }

        $this->storeEntities($objectId, $data['entities']);
    }

    private function storeEntities(int $objectId, array $entities): void
    {
        $totalCount = 0;
        foreach ($entities as $type => $values) {
            if (is_array($values)) {
                $totalCount += count($values);
            }
        }

        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'local',
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
}
