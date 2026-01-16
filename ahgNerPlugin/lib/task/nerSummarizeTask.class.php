<?php
/**
 * CLI task for batch summarization
 */
class nerSummarizeTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process specific object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process all in repository ID'),
            new sfCommandOption('all-empty', null, sfCommandOption::PARAMETER_NONE, 'Process records with empty summary'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum to process', 100),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be processed'),
            new sfCommandOption('field', null, sfCommandOption::PARAMETER_OPTIONAL, 'Target field', 'scopeAndContent'),
        ]);
        $this->namespace = 'ner';
        $this->name = 'summarize';
        $this->briefDescription = 'Generate summaries for archival records';
        $this->detailedDescription = <<<EOD
The [ner:summarize|INFO] task generates AI summaries for records.

Examples:
  [php symfony ner:summarize --all-empty --limit=50|INFO]
  [php symfony ner:summarize --object=12345|INFO]
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $this->logSection('summarize', 'Starting summarization task');

        $settings = $this->getSettings();
        if (($settings['summarizer_enabled'] ?? '1') !== '1') {
            $this->logSection('summarize', 'Summarization disabled', null, 'ERROR');
            return 1;
        }

        $targetField = $options['field'] ?: ($settings['summary_field'] ?? 'scopeAndContent');
        $objectIds = $this->getObjectsToProcess($options, $targetField);

        if (empty($objectIds)) {
            $this->logSection('summarize', 'No objects to process');
            return 0;
        }

        $this->logSection('summarize', sprintf('Found %d objects', count($objectIds)));

        if ($options['dry-run']) {
            foreach ($objectIds as $id) $this->logSection('summarize', "Would process: $id");
            return 0;
        }

        $processed = 0;
        $errors = 0;

        foreach ($objectIds as $objectId) {
            if ($processed >= (int)($options['limit'] ?: 100)) break;

            try {
                $this->processObject($objectId, $settings, $targetField);
                $this->logSection('summarize', "Processed: $objectId");
                $processed++;
            } catch (Exception $e) {
                $this->logSection('summarize', "Error: " . $e->getMessage(), null, 'ERROR');
                $errors++;
            }
        }

        $this->logSection('summarize', sprintf('Done: %d processed, %d errors', $processed, $errors));
        return 0;
    }

    protected function getSettings()
    {
        $settings = [];
        $rows = \Illuminate\Database\Capsule\Manager::table('ahg_ner_settings')->get();
        foreach ($rows as $row) $settings[$row->setting_key] = $row->setting_value;
        return $settings;
    }

    protected function getObjectsToProcess($options, $targetField)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        if (!empty($options['object'])) return [(int)$options['object']];

        $query = $db->table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->select('io.id')
            ->where('io.id', '!=', QubitInformationObject::ROOT_ID);

        if (!empty($options['repository'])) {
            $query->where('io.repository_id', (int)$options['repository']);
        }

        if ($options['all-empty']) {
            $query->where(function($q) use ($targetField) {
                $q->whereNull("ioi.$targetField")->orWhere("ioi.$targetField", '');
            });
        }

        return $query->limit((int)($options['limit'] ?: 100))->pluck('io.id')->toArray();
    }

    protected function processObject($objectId, $settings, $targetField)
    {
        $io = QubitInformationObject::getById($objectId);
        if (!$io) throw new Exception("Not found");

        $text = $this->extractText($io);
        if (strlen($text) < 200) throw new Exception("Text too short");

        $apiUrl = rtrim($settings['api_url'], '/') . '/summarize';
        
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'text' => $text,
                'max_length' => (int)($settings['summarizer_max_length'] ?? 500),
                'min_length' => (int)($settings['summarizer_min_length'] ?? 100)
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-Key: ' . ($settings['api_key'] ?? '')],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) throw new Exception("API error: HTTP $httpCode");

        $data = json_decode($response, true);
        if (!isset($data['summary'])) throw new Exception("Invalid response");

        $setter = 'set' . ucfirst($targetField);
        $io->$setter($data['summary']);
        $io->save();
    }

    protected function extractText($io)
    {
        $text = '';
        foreach ($io->digitalObjectsRelatedByobjectId as $do) {
            if ($do->mimeType === 'application/pdf') {
                $path = $do->getAbsolutePath();
                if (file_exists($path)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
                    exec("pdftotext -enc UTF-8 " . escapeshellarg($path) . " " . escapeshellarg($tmp));
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
