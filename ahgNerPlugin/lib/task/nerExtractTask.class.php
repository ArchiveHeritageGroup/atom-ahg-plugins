<?php
/**
 * CLI task for batch NER extraction
 */
class nerExtractTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process specific object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process all objects in repository ID'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Process all unprocessed objects'),
            new sfCommandOption('uploaded-today', null, sfCommandOption::PARAMETER_NONE, 'Process objects uploaded today'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number to process', 100),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be processed'),
            new sfCommandOption('queue', null, sfCommandOption::PARAMETER_NONE, 'Queue jobs instead of direct processing'),
            new sfCommandOption('with-pdf', null, sfCommandOption::PARAMETER_NONE, 'Extract text from PDFs (slower but more content)'),
        ]);
        $this->namespace = 'ner';
        $this->name = 'extract';
        $this->briefDescription = 'Extract named entities from archival records';
        $this->detailedDescription = <<<EOD
The [ner:extract|INFO] task extracts named entities from records.

Examples:
  [php symfony ner:extract --all --limit=100|INFO]
  [php symfony ner:extract --all --with-pdf --limit=100|INFO]
  [php symfony ner:extract --object=12345|INFO]
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $this->logSection('ner', 'Starting NER extraction task');
        if ($options['with-pdf']) {
            $this->logSection('ner', 'PDF extraction enabled');
        }

        $settings = $this->getSettings();
        if (($settings['ner_enabled'] ?? '1') !== '1') {
            $this->logSection('ner', 'NER is disabled in settings', null, 'ERROR');
            return 1;
        }

        $objectIds = $this->getObjectsToProcess($options);
        if (empty($objectIds)) {
            $this->logSection('ner', 'No objects found to process');
            return 0;
        }

        $this->logSection('ner', sprintf('Found %d objects to process', count($objectIds)));

        if ($options['dry-run']) {
            foreach ($objectIds as $id) {
                $this->logSection('ner', "Would process: $id");
            }
            return 0;
        }

        $processed = 0;
        $errors = 0;
        $limit = (int)($options['limit'] ?: 100);
        $withPdf = $options['with-pdf'] || ($settings['extract_from_pdf'] ?? '0') === '1';

        foreach ($objectIds as $objectId) {
            if ($processed >= $limit) break;

            try {
                if ($options['queue']) {
                    $this->queueJob($objectId);
                    $this->logSection('ner', "Queued: $objectId");
                } else {
                    $this->processObject($objectId, $settings, $withPdf);
                    $this->logSection('ner', "Processed: $objectId");
                }
                $processed++;
            } catch (Exception $e) {
                $this->logSection('ner', "Error on $objectId: " . $e->getMessage(), null, 'ERROR');
                $errors++;
            }
        }

        $this->logSection('ner', sprintf('Done: %d processed, %d errors', $processed, $errors));
        return $errors > 0 ? 1 : 0;
    }

    protected function getSettings()
    {
        $settings = [];
        $rows = \Illuminate\Database\Capsule\Manager::table('ahg_ner_settings')->get();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    protected function getObjectsToProcess($options)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        if (!empty($options['object'])) {
            return [(int)$options['object']];
        }

        $query = $db->table('information_object as io')
            ->select('io.id')
            ->where('io.id', '!=', QubitInformationObject::ROOT_ID);

        if (!empty($options['repository'])) {
            $query->where('io.repository_id', (int)$options['repository']);
        }

        if ($options['uploaded-today']) {
            $query->whereDate('io.created_at', '=', date('Y-m-d'));
        }

        if ($options['all'] || $options['uploaded-today']) {
            $query->leftJoin('ahg_ner_extraction as ne', 'io.id', '=', 'ne.object_id')
                  ->whereNull('ne.id');
        }

        return $query->limit((int)($options['limit'] ?: 100))->pluck('id')->toArray();
    }

    protected function queueJob($objectId)
    {
        $job = new QubitJob();
        $job->name = 'arNerExtractJob';
        $job->setParameter('objectId', $objectId);
        $job->setParameter('runNer', true);
        $job->setParameter('runSummarize', false);
        $job->save();
    }

    protected function processObject($objectId, $settings, $withPdf = false)
    {
        $io = QubitInformationObject::getById($objectId);
        if (!$io) throw new Exception("Not found");

        $text = $this->extractText($io, $withPdf);
        if (empty($text) || strlen($text) < 10) {
            // Still create extraction record but with 0 entities
            $this->storeEntities($objectId, []);
            return;
        }

        $this->callNerApi($objectId, $text, $settings);
    }

    protected function extractText($io, $withPdf = false)
    {
        $text = '';
        
        // Get metadata text
        foreach (['title', 'scopeAndContent', 'archivalHistory'] as $field) {
            $getter = 'get' . ucfirst($field);
            $val = $io->$getter(['fallback' => true]);
            if ($val) $text .= $val . "\n";
        }

        // Extract from PDF if enabled
        if ($withPdf) {
            foreach ($io->digitalObjectsRelatedByobjectId as $do) {
                if ($do->mimeType === 'application/pdf') {
                    $pdfText = $this->extractPdfText($do);
                    if (!empty($pdfText)) {
                        $text .= "\n" . $pdfText;
                    }
                    break; // Only process first PDF
                }
            }
        }

        return trim($text);
    }

    protected function extractPdfText($digitalObject)
    {
        // Get the path - need to construct full path
        $path = $digitalObject->path;
        $name = $digitalObject->name;
        
        // AtoM stores path relative to uploads
        $basePath = sfConfig::get('sf_root_dir') . $path;
        
        // Find the PDF file
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
        exec(sprintf('pdftotext -enc UTF-8 %s %s 2>/dev/null', 
            escapeshellarg($pdfFile), 
            escapeshellarg($tempFile)
        ));
        
        $text = '';
        if (file_exists($tempFile)) {
            $text = file_get_contents($tempFile);
            unlink($tempFile);
        }

        // Limit text length to avoid API timeouts
        if (strlen($text) > 50000) {
            $text = substr($text, 0, 50000);
        }

        return $text;
    }

    protected function callNerApi($objectId, $text, $settings)
    {
        $apiUrl = rtrim($settings['api_url'], '/') . '/ner/extract';
        
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['text' => $text]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-Key: ' . ($settings['api_key'] ?? '')],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) throw new Exception("API error: HTTP $httpCode");

        $data = json_decode($response, true);
        if (!isset($data['entities'])) throw new Exception("Invalid response");

        $this->storeEntities($objectId, $data['entities']);
    }

    protected function storeEntities($objectId, $entities)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        
        // Count total entities (API returns grouped by type)
        $totalCount = 0;
        foreach ($entities as $type => $values) {
            if (is_array($values)) {
                $totalCount += count($values);
            }
        }
        
        $extractionId = $db->table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'local',
            'status' => 'completed',
            'entity_count' => $totalCount,
            'extracted_at' => date('Y-m-d H:i:s')
        ]);

        // API returns: {"PERSON": ["Name1", "Name2"], "ORG": ["Org1"], ...}
        foreach ($entities as $type => $values) {
            if (!is_array($values)) continue;
            foreach ($values as $value) {
                $db->table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $value,
                    'confidence' => 0.95,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
}
