<?php
/**
 * CLI task for batch Handwritten Text Recognition (HTR)
 *
 * Uses TrOCR models via the AI service (/ai/v1/htr) to transcribe
 * handwritten text from digital object images. Results are stored
 * as transcripts in the property table (same pattern as OCR).
 */
class aiHtrExtractTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process specific information object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process all in repository ID'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Process all objects with images but no transcript'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum to process', 100),
            new sfCommandOption('mode', null, sfCommandOption::PARAMETER_OPTIONAL, 'TrOCR mode: all, date, digits, letters', 'all'),
            new sfCommandOption('no-zones', null, sfCommandOption::PARAMETER_NONE, 'Disable zone detection (process full image)'),
            new sfCommandOption('overwrite', null, sfCommandOption::PARAMETER_NONE, 'Overwrite existing transcripts'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be processed'),
        ]);
        $this->namespace = 'ai';
        $this->name = 'htr';
        $this->briefDescription = 'Extract handwritten text from digital objects using TrOCR';
        $this->detailedDescription = <<<EOD
The [ai:htr|INFO] task extracts handwritten text from images using TrOCR models.

Four recognition modes:
  all     — General handwriting (default)
  date    — Specialized for dates
  digits  — Specialized for numbers
  letters — Specialized for letters/alphabetic text

Examples:
  [php symfony ai:htr --all --limit=50|INFO]
  [php symfony ai:htr --object=12345|INFO]
  [php symfony ai:htr --object=12345 --mode=date|INFO]
  [php symfony ai:htr --repository=6 --limit=20 --overwrite|INFO]
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $this->logSection('ai', 'Starting HTR extraction task');

        // Validate mode
        $mode = $options['mode'] ?: 'all';
        if (!in_array($mode, ['all', 'date', 'digits', 'letters'])) {
            $this->logSection('ai', "Invalid mode: $mode. Use: all, date, digits, letters", null, 'ERROR');
            return 1;
        }
        $this->logSection('ai', "Mode: $mode");

        $settings = $this->getSettings();
        $apiUrl = rtrim($settings['api_url'] ?? 'http://localhost:5004/ai/v1', '/');
        $apiKey = $settings['api_key'] ?? '';

        // Check API availability
        if (!$this->checkApi($apiUrl, $apiKey)) {
            $this->logSection('ai', 'AI service not reachable at ' . $apiUrl, null, 'ERROR');
            return 1;
        }

        $objectIds = $this->getObjectsToProcess($options);
        if (empty($objectIds)) {
            $this->logSection('ai', 'No objects found to process');
            return 0;
        }

        $this->logSection('ai', sprintf('Found %d objects to process', count($objectIds)));

        if ($options['dry-run']) {
            foreach ($objectIds as $id) {
                $imagePath = $this->getImagePath($id);
                $this->logSection('ai', "Would process: $id" . ($imagePath ? " ($imagePath)" : ' (no image)'));
            }
            return 0;
        }

        $processed = 0;
        $errors = 0;
        $skipped = 0;
        $limit = (int)($options['limit'] ?: 100);
        $useZones = !$options['no-zones'];

        foreach ($objectIds as $objectId) {
            if ($processed >= $limit) {
                break;
            }

            try {
                $imagePath = $this->getImagePath($objectId);
                if (!$imagePath) {
                    $skipped++;
                    continue;
                }

                $result = $this->callHtrApi($apiUrl, $apiKey, $imagePath, $mode, $useZones);
                if (empty($result['text'])) {
                    $this->logSection('ai', "No text extracted: $objectId");
                    $skipped++;
                    continue;
                }

                $this->storeTranscript($objectId, $result['text'], $mode, $options['overwrite']);
                $lineCount = $result['count'] ?? 0;
                $timeMs = $result['processing_time_ms'] ?? 0;
                $this->logSection('ai', sprintf(
                    'Processed: %d (%d lines, %dms)',
                    $objectId, $lineCount, $timeMs
                ));
                $processed++;
            } catch (Exception $e) {
                $this->logSection('ai', "Error on $objectId: " . $e->getMessage(), null, 'ERROR');
                $errors++;
            }
        }

        $this->logSection('ai', sprintf('Done: %d processed, %d skipped, %d errors', $processed, $skipped, $errors));
        return $errors > 0 ? 1 : 0;
    }

    /**
     * Get AI settings from ahg_ai_settings table.
     */
    protected function getSettings(): array
    {
        $settings = [];
        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('ahg_ai_settings')
                ->whereIn('feature', ['general', 'htr'])
                ->get();
            foreach ($rows as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // Table may not exist — use defaults
        }
        return $settings;
    }

    /**
     * Find objects to process based on CLI options.
     */
    protected function getObjectsToProcess($options): array
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        if (!empty($options['object'])) {
            return [(int)$options['object']];
        }

        $query = $db->table('information_object as io')
            ->join('digital_object as do_tbl', 'do_tbl.object_id', '=', 'io.id')
            ->select('io.id')
            ->where('io.id', '!=', QubitInformationObject::ROOT_ID)
            ->whereNull('do_tbl.parent_id')
            ->where('do_tbl.mime_type', 'LIKE', 'image/%');

        if (!empty($options['repository'])) {
            $query->where('io.repository_id', (int)$options['repository']);
        }

        // Only objects without existing transcript (unless --overwrite)
        if ($options['all'] && !$options['overwrite']) {
            $query->whereNotExists(function ($sub) {
                $sub->select(\Illuminate\Database\Capsule\Manager::raw(1))
                    ->from('property as p')
                    ->join('property_i18n as pi', 'p.id', '=', 'pi.id')
                    ->whereColumn('p.object_id', 'do_tbl.id')
                    ->where('p.name', 'transcript')
                    ->whereNotNull('pi.value')
                    ->where('pi.value', '!=', '');
            });
        }

        return $query->limit((int)($options['limit'] ?: 100))
            ->pluck('io.id')
            ->toArray();
    }

    /**
     * Get the image file path for an information object.
     * Prefers reference derivative (usage_id=142), falls back to master.
     */
    protected function getImagePath(int $objectId): ?string
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $root = sfConfig::get('sf_root_dir');

        // Get master digital object
        $master = $db->table('digital_object')
            ->where('object_id', $objectId)
            ->whereNull('parent_id')
            ->where('mime_type', 'LIKE', 'image/%')
            ->first();

        if (!$master) {
            return null;
        }

        // Prefer reference derivative
        $ref = $db->table('digital_object')
            ->where('parent_id', $master->id)
            ->where('usage_id', 142)
            ->first();

        if ($ref) {
            $path = $root . '/' . ltrim($ref->path, '/') . $ref->name;
        } else {
            $path = $root . '/' . ltrim($master->path, '/') . $master->name;
        }

        return file_exists($path) ? $path : null;
    }

    /**
     * Call the AI service HTR endpoint.
     */
    protected function callHtrApi(string $apiUrl, string $apiKey, string $imagePath, string $mode, bool $useZones): array
    {
        $url = $apiUrl . '/htr';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'image_path' => $imagePath,
                'mode' => $mode,
                'use_zones' => $useZones,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            throw new Exception("HTR API error: HTTP $httpCode" . ($curlError ? " ($curlError)" : ''));
        }

        $data = json_decode($response, true);
        if (!$data || empty($data['success'])) {
            throw new Exception('HTR API error: ' . ($data['error'] ?? 'unknown'));
        }

        return $data;
    }

    /**
     * Store HTR transcript in the property table (same pattern as OCR transcripts).
     */
    protected function storeTranscript(int $objectId, string $text, string $mode, bool $overwrite): void
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        // Get the digital object ID for this information object
        $doId = $db->table('digital_object')
            ->where('object_id', $objectId)
            ->whereNull('parent_id')
            ->value('id');

        if (!$doId) {
            throw new Exception("No digital object for IO $objectId");
        }

        // Check for existing transcript property
        $existing = $db->table('property as p')
            ->join('property_i18n as pi', 'p.id', '=', 'pi.id')
            ->where('p.object_id', $doId)
            ->where('p.name', 'transcript')
            ->select('p.id')
            ->first();

        if ($existing) {
            if (!$overwrite) {
                return;
            }
            // Update existing transcript
            $db->table('property_i18n')
                ->where('id', $existing->id)
                ->update(['value' => $text]);
        } else {
            // Insert new property
            $propId = $db->table('property')->insertGetId([
                'object_id' => $doId,
                'name' => 'transcript',
                'source_culture' => 'en',
            ]);
            $db->table('property_i18n')->insert([
                'id' => $propId,
                'culture' => 'en',
                'value' => $text,
            ]);
        }
    }

    /**
     * Check if the AI service is reachable.
     */
    protected function checkApi(string $apiUrl, string $apiKey): bool
    {
        $url = rtrim(dirname($apiUrl), '/') . '/v1/health';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
