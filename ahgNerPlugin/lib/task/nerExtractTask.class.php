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
        ]);
        $this->namespace = 'ner';
        $this->name = 'extract';
        $this->briefDescription = 'Extract named entities from archival records';
        $this->detailedDescription = <<<EOD
The [ner:extract|INFO] task extracts named entities from records.

Examples:
  [php symfony ner:extract --all --limit=100|INFO]
  [php symfony ner:extract --object=12345|INFO]
  [php symfony ner:extract --repository=5|INFO]
  [php symfony ner:extract --uploaded-today --queue|INFO]
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        
        // Bootstrap Laravel
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $this->logSection('ner', 'Starting NER extraction task');

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

        foreach ($objectIds as $objectId) {
            if ($processed >= $limit) break;

            try {
                if ($options['queue']) {
                    $this->queueJob($objectId);
                    $this->logSection('ner', "Queued: $objectId");
                } else {
                    $this->processObject($objectId, $settings);
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

    protected function processObject($objectId, $settings)
    {
        $io = QubitInformationObject::getById($objectId);
        if (!$io) throw new Exception("Not found");

        $text = $this->extractText($io);
        if (empty($text)) {
            $this->logSection('ner', "No text for $objectId", null, 'COMMENT');
            return;
        }

        $this->callNerApi($objectId, $text, $settings);
    }

    protected function extractText($io)
    {
        $text = '';
        foreach (['title', 'scopeAndContent', 'archivalHistory'] as $field) {
            $getter = 'get' . ucfirst($field);
            $val = $io->$getter(['fallback' => true]);
            if ($val) $text .= $val . "\n";
        }
        return trim($text);
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
            CURLOPT_TIMEOUT => 60
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
        
        $extractionId = $db->table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'local',
            'status' => 'completed',
            'entity_count' => count($entities),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        foreach ($entities as $entity) {
            $db->table('ahg_ner_entity')->insert([
                'extraction_id' => $extractionId,
                'object_id' => $objectId,
                'entity_type' => $entity['type'] ?? $entity['label'] ?? 'UNKNOWN',
                'entity_value' => $entity['text'] ?? $entity['value'] ?? '',
                'confidence' => $entity['confidence'] ?? $entity['score'] ?? 0.0,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
