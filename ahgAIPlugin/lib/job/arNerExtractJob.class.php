<?php
/**
 * Background job for NER extraction and summarization via Gearman
 */
class arNerExtractJob extends arBaseJob
{
    protected $extraRequiredParameters = ['objectId'];

    public function runJob($parameters)
    {
        $objectId = $parameters['objectId'];
        $runNer = $parameters['runNer'] ?? true;
        $runSummarize = $parameters['runSummarize'] ?? false;
        $runSpellCheck = $parameters['runSpellCheck'] ?? false;

        $this->info("AI processing for object: $objectId");

        // Bootstrap Laravel
        \AhgCore\Core\AhgDb::init();

        $settings = $this->getSettings();
        
        $io = QubitInformationObject::getById($objectId);
        if (!$io) {
            $this->error("Object not found: $objectId");
            return false;
        }

        $success = true;

        if ($runNer && ($settings['ner_enabled'] ?? '1') === '1') {
            $text = $this->extractText($io);
            if (!empty($text)) {
                $success = $this->runNer($objectId, $text, $settings) && $success;
            }
        }

        if ($runSummarize && ($settings['summarizer_enabled'] ?? '1') === '1') {
            $success = $this->runSummarize($io, $settings) && $success;
        }

        if ($runSpellCheck && ($settings['spellcheck_enabled'] ?? '0') === '1') {
            $this->runSpellCheck($io, $settings);
        }

        $this->info("Complete for object: $objectId");
        return $success;
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

    protected function extractText($io)
    {
        $text = '';
        foreach (['title', 'scopeAndContent', 'archivalHistory'] as $field) {
            $getter = 'get' . ucfirst($field);
            $val = $io->$getter(['fallback' => true]);
            if ($val) $text .= $val . "\n";
        }

        // Also try PDF
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

        return trim($text);
    }

    /**
     * Run NER extraction against the API.
     *
     * Handles both the new API response (entities_v2 + entities) and the
     * legacy response (entities dict only). Real per-entity scores are stored
     * when entities_v2 is available; otherwise confidence is null.
     *
     * @param int    $objectId
     * @param string $text
     * @param array  $settings
     * @return bool
     */
    protected function runNer($objectId, $text, $settings)
    {
        $apiUrl = rtrim($settings['api_url'], '/') . '/ner/extract';
        
        // Adjust timeout based on processing_mode
        $mode = $settings['processing_mode'] ?? 'job';
        $timeout = ($mode === 'hybrid') ? 30 : 60;

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['text' => substr($text, 0, 100000)]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-Key: ' . ($settings['api_key'] ?? '')],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->error("NER API error: HTTP $httpCode");
            return false;
        }

        $data = json_decode($response, true);
        if (!isset($data['entities'])) {
            $this->error("Invalid NER response");
            return false;
        }

        // entities_v2 is the flat per-entity list from the new API response.
        // Absent on pre-deploy versions — storeEntities() falls back to null.
        $entitiesV2 = $data['entities_v2'] ?? null;
        $this->storeEntities($objectId, $data['entities'], $entitiesV2);
        $this->info("Extracted " . count($data['entities']) . " entity groups");
        return true;
    }

    /**
     * Store NER entities.
     *
     * When $entitiesV2 is a non-empty array, each entry carries a real
     * per-entity score and confidence is written from that (real float or null).
     * When $entitiesV2 is null/empty, the legacy dict is iterated and
     * confidence is written as null — no fabricated 0.95.
     *
     * @param int        $objectId
     * @param array      $entities    legacy {TYPE: [values]} dict
     * @param array|null $entitiesV2  flat list of {value,type,offset_start,offset_end,score}
     */
    protected function storeEntities($objectId, $entities, ?array $entitiesV2 = null)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $useV2 = is_array($entitiesV2) && !empty($entitiesV2);

        if ($useV2) {
            $totalCount = count($entitiesV2);
        } else {
            $totalCount = 0;
            foreach ($entities as $type => $values) {
                if (is_array($values)) {
                    $totalCount += count($values);
                }
            }
        }
        
        $extractionId = $db->table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'local',
            'status' => 'completed',
            'entity_count' => $totalCount,
            'extracted_at' => date('Y-m-d H:i:s')
        ]);

        // Filter entity types if configured
        $allowedTypes = null;
        $settings = $this->getSettings();
        $entityTypesJson = $settings['ner_entity_types'] ?? '';
        if (!empty($entityTypesJson)) {
            $decoded = json_decode($entityTypesJson, true);
            if (is_array($decoded) && !empty($decoded)) {
                $allowedTypes = array_map('strtoupper', $decoded);
            }
        }

        if ($useV2) {
            foreach ($entitiesV2 as $rec) {
                if (!is_array($rec) || !isset($rec['value'], $rec['type'])) {
                    continue;
                }
                if ($allowedTypes !== null && !in_array(strtoupper($rec['type']), $allowedTypes)) {
                    continue;
                }
                $score = isset($rec['score']) && $rec['score'] !== null
                    ? (float) $rec['score']
                    : null;

                $db->table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $rec['type'],
                    'entity_value' => $rec['value'],
                    'confidence' => $score,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            return;
        }

        // Legacy dict path — no real scores available
        foreach ($entities as $type => $values) {
            if (!is_array($values)) continue;
            if ($allowedTypes !== null && !in_array(strtoupper($type), $allowedTypes)) continue;
            foreach ($values as $value) {
                $db->table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $value,
                    'confidence' => null,  // no fabricated score
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    protected function runSummarize($io, $settings)
    {
        $text = '';
        foreach ($io->digitalObjectsRelatedByobjectId as $do) {
            if ($do->mimeType === 'application/pdf') {
                $path = $do->getAbsolutePath();
                if (file_exists($path)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
                    exec("pdftotext -enc UTF-8 " . escapeshellarg($path) . " " . escapeshellarg($tmp) . " 2>/dev/null");
                    if (file_exists($tmp)) {
                        $text = file_get_contents($tmp);
                        unlink($tmp);
                    }
                }
                break;
            }
        }

        if (strlen($text) < 200) {
            $this->info("Insufficient text for summarization");
            return true;
        }

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

        if ($httpCode !== 200) {
            $this->error("Summarize API error: HTTP $httpCode");
            return false;
        }

        $data = json_decode($response, true);
        if (!isset($data['summary'])) {
            $this->error("Invalid summarize response");
            return false;
        }

        $targetField = $settings['summary_field'] ?? 'scopeAndContent';
        $setter = 'set' . ucfirst($targetField);
        $io->$setter($data['summary']);
        $io->save();
        
        $this->info("Summary saved (" . strlen($data['summary']) . " chars)");
        return true;
    }

    protected function runSpellCheck($io, $settings)
    {
        $language = $settings['spellcheck_language'] ?? 'en_US';
        $fieldsJson = $settings['spellcheck_fields'] ?? '["title","scopeAndContent"]';
        $fields = json_decode($fieldsJson, true) ?: ['title', 'scopeAndContent'];
        $lang = explode('_', $language)[0];

        $errors = [];
        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            if (!method_exists($io, $getter)) continue;

            $text = $io->$getter(['fallback' => true]);
            if (empty($text)) continue;

            $tmp = tempnam(sys_get_temp_dir(), 'spell_');
            file_put_contents($tmp, $text);
            exec("cat " . escapeshellarg($tmp) . " | aspell -l " . escapeshellarg($lang) . " list 2>/dev/null | sort -u", $misspelled);
            unlink($tmp);

            if (!empty($misspelled)) {
                $errors[$field] = $misspelled;
            }
        }

        if (!empty($errors)) {
            $db = \Illuminate\Database\Capsule\Manager::connection();
            $db->table('ahg_spellcheck_result')->where('object_id', $io->id)->delete();
            $db->table('ahg_spellcheck_result')->insert([
                'object_id' => $io->id,
                'errors_json' => json_encode($errors),
                'error_count' => array_sum(array_map('count', $errors)),
                'status' => 'pending',
                'extracted_at' => date('Y-m-d H:i:s')
            ]);
            $this->info("Spell check: " . array_sum(array_map('count', $errors)) . " issues found");
        }
    }
}