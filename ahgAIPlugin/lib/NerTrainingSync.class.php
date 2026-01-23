<?php
/**
 * NER Training Sync Service
 * Pushes corrections to central training server
 */

use Illuminate\Database\Capsule\Manager as DB;

class NerTrainingSync
{
    private $centralApiUrl;
    private $siteId;
    private $apiKey;
    
    public function __construct()
    {
        // Load config from settings or environment
        $this->centralApiUrl = getenv('NER_TRAINING_API_URL') ?: 'https://train.theahg.co.za/api/ner';
        $this->siteId = getenv('NER_SITE_ID') ?: $this->generateSiteId();
        $this->apiKey = getenv('NER_API_KEY') ?: '';
    }
    
    /**
     * Get corrections that haven't been exported yet
     */
    public function getUnexportedCorrections($limit = 500)
    {
        return DB::table('ahg_ner_entity')
            ->where('training_exported', 0)
            ->whereIn('correction_type', ['value_edit', 'type_change', 'both', 'rejected', 'approved'])
            ->whereNotNull('reviewed_at')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get context around entity from source text
     */
    public function getEntityContext($objectId, $entityValue, $contextLength = 200)
    {
        // Get digital object text or scope and content
        $io = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->first();
        
        if (!$io) {
            return null;
        }
        
        $text = $io->scope_and_content ?? '';
        
        if (empty($text)) {
            return null;
        }
        
        // Find entity in text and extract context
        $pos = stripos($text, $entityValue);
        if ($pos === false) {
            return ['text' => substr($text, 0, $contextLength * 2), 'start' => 0, 'end' => 0];
        }
        
        $start = max(0, $pos - $contextLength);
        $end = min(strlen($text), $pos + strlen($entityValue) + $contextLength);
        
        return [
            'text' => substr($text, $start, $end - $start),
            'start' => $pos - $start,
            'end' => $pos - $start + strlen($entityValue)
        ];
    }
    
    /**
     * Prepare corrections for export
     */
    public function prepareExportData($corrections)
    {
        $exportData = [];
        
        foreach ($corrections as $c) {
            $context = $this->getEntityContext(
                $c->object_id, 
                $c->original_value ?? $c->entity_value
            );
            
            $exportData[] = [
                'entity_id' => $c->id,
                'original_value' => $c->original_value ?? $c->entity_value,
                'corrected_value' => $c->entity_value,
                'original_type' => $c->original_type ?? $c->entity_type,
                'corrected_type' => $c->entity_type,
                'correction_type' => $c->correction_type,
                'confidence' => $c->confidence,
                'context' => $context,
                'reviewed_at' => $c->reviewed_at
            ];
        }
        
        return $exportData;
    }
    
    /**
     * Push corrections to central server
     */
    public function pushCorrections()
    {
        $corrections = $this->getUnexportedCorrections();
        
        if ($corrections->isEmpty()) {
            return ['status' => 'no_data', 'message' => 'No new corrections to export'];
        }
        
        $exportData = $this->prepareExportData($corrections);
        
        $payload = [
            'site_id' => $this->siteId,
            'site_name' => $this->getSiteName(),
            'timestamp' => date('c'),
            'corrections' => $exportData
        ];
        
        // Send to central server
        $response = $this->sendToServer($payload);
        
        if ($response['success']) {
            // Mark as exported
            $ids = $corrections->pluck('id')->toArray();
            DB::table('ahg_ner_entity')
                ->whereIn('id', $ids)
                ->update(['training_exported' => 1]);
            
            return [
                'status' => 'success',
                'exported' => count($ids),
                'response' => $response
            ];
        }
        
        return [
            'status' => 'error',
            'message' => $response['error'] ?? 'Unknown error'
        ];
    }
    
    /**
     * Send data to central training server
     */
    private function sendToServer($payload)
    {
        $ch = curl_init($this->centralApiUrl . '/corrections');
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'X-Site-ID: ' . $this->siteId
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => json_decode($response, true)];
        }
        
        return ['success' => false, 'error' => "HTTP $httpCode: $response"];
    }
    
    /**
     * Export corrections to local file (for manual transfer)
     */
    public function exportToFile($filename = null)
    {
        $corrections = $this->getUnexportedCorrections(10000);
        
        if ($corrections->isEmpty()) {
            return ['status' => 'no_data'];
        }
        
        $exportData = $this->prepareExportData($corrections);
        
        $filename = $filename ?: '/tmp/ner_corrections_' . date('Y-m-d_His') . '.json';
        
        $payload = [
            'site_id' => $this->siteId,
            'site_name' => $this->getSiteName(),
            'exported_at' => date('c'),
            'total_corrections' => count($exportData),
            'corrections' => $exportData
        ];
        
        file_put_contents($filename, json_encode($payload, JSON_PRETTY_PRINT));
        
        // Mark as exported
        $ids = $corrections->pluck('id')->toArray();
        DB::table('ahg_ner_entity')
            ->whereIn('id', $ids)
            ->update(['training_exported' => 1]);
        
        return [
            'status' => 'success',
            'file' => $filename,
            'exported' => count($exportData)
        ];
    }
    
    /**
     * Get training statistics
     */
    public function getStats()
    {
        $stats = DB::table('ahg_ner_entity')
            ->select(DB::raw("
                correction_type,
                COUNT(*) as count,
                SUM(CASE WHEN training_exported = 1 THEN 1 ELSE 0 END) as exported
            "))
            ->whereIn('correction_type', ['value_edit', 'type_change', 'both', 'rejected', 'approved'])
            ->groupBy('correction_type')
            ->get();
        
        return $stats;
    }
    
    private function generateSiteId()
    {
        // Generate unique site ID based on hostname
        return md5(gethostname() . __DIR__);
    }
    
    private function getSiteName()
    {
        return DB::table('setting_i18n')
            ->where('id', function($q) {
                $q->select('id')->from('setting')->where('name', 'siteTitle')->limit(1);
            })
            ->where('culture', 'en')
            ->value('value') ?? gethostname();
    }
}
