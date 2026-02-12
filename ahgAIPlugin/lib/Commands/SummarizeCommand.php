<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for batch summarization.
 */
class SummarizeCommand extends BaseCommand
{
    protected string $name = 'ai:summarize';
    protected string $description = 'Generate summaries for archival records';
    protected string $detailedDescription = <<<'EOF'
    Generate AI summaries for archival records using PDF text extraction.

    Examples:
      php bin/atom ai:summarize --all-empty --limit=50
      php bin/atom ai:summarize --object=12345
      php bin/atom ai:summarize --repository=5 --field=scope_and_content
    EOF;

    protected function configure(): void
    {
        $this->addOption('object', null, 'Process specific object ID');
        $this->addOption('repository', null, 'Process all in repository ID');
        $this->addOption('all-empty', null, 'Process records with empty summary');
        $this->addOption('limit', null, 'Maximum to process', '100');
        $this->addOption('dry-run', null, 'Show what would be processed');
        $this->addOption('field', null, 'Target field', 'scope_and_content');
    }

    protected function handle(): int
    {
        $this->info('Starting summarization task');

        $settings = $this->getSettings('summarize');
        if (($settings['enabled'] ?? '1') !== '1') {
            $this->error('Summarization disabled');
            return 1;
        }

        $targetField = $this->option('field') ?: ($settings['target_field'] ?? 'scope_and_content');
        $objectIds = $this->getObjectsToProcess($targetField);

        if (empty($objectIds)) {
            $this->info('No objects to process');
            return 0;
        }

        $this->info(sprintf('Found %d objects', count($objectIds)));

        if ($this->hasOption('dry-run')) {
            foreach ($objectIds as $id) {
                $this->line("  Would process: $id");
            }
            return 0;
        }

        $generalSettings = $this->getSettings('general');
        $processed = 0;
        $errors = 0;

        foreach ($objectIds as $objectId) {
            if ($processed >= (int) ($this->option('limit') ?: 100)) {
                break;
            }

            try {
                $this->processObject($objectId, array_merge($generalSettings, $settings), $targetField);
                $this->line("  Processed: $objectId");
                $processed++;
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                $errors++;
            }
        }

        $this->info(sprintf('Done: %d processed, %d errors', $processed, $errors));
        return 0;
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

    private function getObjectsToProcess(string $targetField): array
    {
        if ($this->option('object')) {
            return [(int) $this->option('object')];
        }

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->select('io.id')
            ->where('io.id', '!=', \QubitInformationObject::ROOT_ID);

        if ($this->option('repository')) {
            $query->where('io.repository_id', (int) $this->option('repository'));
        }

        if ($this->hasOption('all-empty')) {
            $query->where(function ($q) use ($targetField) {
                $q->whereNull("ioi.$targetField")->orWhere("ioi.$targetField", '');
            });
        }

        return $query->limit((int) ($this->option('limit') ?: 100))->pluck('io.id')->toArray();
    }

    private function processObject(int $objectId, array $settings, string $targetField): void
    {
        $io = \QubitInformationObject::getById($objectId);
        if (!$io) {
            throw new \Exception('Not found');
        }

        $text = $this->extractText($io);
        if (strlen($text) < 200) {
            throw new \Exception('Text too short');
        }

        $apiUrl = rtrim($settings['api_url'], '/') . '/summarize';

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'text' => $text,
                'max_length' => (int) ($settings['max_length'] ?? 500),
                'min_length' => (int) ($settings['min_length'] ?? 100),
            ]),
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
        if (!isset($data['summary'])) {
            throw new \Exception('Invalid response');
        }

        $setter = 'set' . str_replace('_', '', ucwords($targetField, '_'));
        $io->$setter($data['summary']);
        $io->save();
    }

    private function extractText($io): string
    {
        $text = '';
        foreach ($io->digitalObjectsRelatedByobjectId as $do) {
            if ($do->mimeType === 'application/pdf') {
                $path = $do->getAbsolutePath();
                if (file_exists($path)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
                    exec('pdftotext -enc UTF-8 ' . escapeshellarg($path) . ' ' . escapeshellarg($tmp));
                    if (file_exists($tmp)) {
                        $text = file_get_contents($tmp);
                        unlink($tmp);
                    }
                }
                break;
            }
        }
        return $text;
    }
}
