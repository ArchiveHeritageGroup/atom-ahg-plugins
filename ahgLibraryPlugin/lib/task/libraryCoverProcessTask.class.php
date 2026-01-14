<?php

class libraryCoverProcessTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max covers to process', 10),
        ]);

        $this->namespace = 'library';
        $this->name = 'process-covers';
        $this->briefDescription = 'Process pending book cover downloads from Open Library';
        $this->detailedDescription = <<<EOF
Downloads book covers from Open Library for queued items and saves locally.

  php symfony library:process-covers
  php symfony library:process-covers --limit=50
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        
        // Disable search index updates during batch processing
        QubitSearch::disable();
        
        $db = \Illuminate\Database\Capsule\Manager::connection();
        
        $limit = (int) ($options['limit'] ?? 10);
        
        // Get pending items
        $pending = $db->table('atom_library_cover_queue')
            ->where('status', 'pending')
            ->where('attempts', '<', 3)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
        
        $this->logSection('library', sprintf('Found %d pending covers to process', count($pending)));
        
        foreach ($pending as $item) {
            $this->processItem($db, $item);
        }
        
        // Re-enable search
        QubitSearch::enable();
        
        $this->logSection('library', 'Done');
    }
    
    protected function processItem($db, $item)
    {
        $isbn = preg_replace('/[^0-9X]/i', '', $item->isbn);
        
        $this->logSection('library', sprintf('Processing ISBN: %s (IO: %d)', $isbn, $item->information_object_id));
        
        // Update attempts
        $db->table('atom_library_cover_queue')
            ->where('id', $item->id)
            ->update([
                'attempts' => $item->attempts + 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Check if digital object already exists
        $existing = $db->table('digital_object')
            ->where('object_id', $item->information_object_id)
            ->first();
            
        if ($existing) {
            $this->logSection('library', 'Digital object already exists, marking complete');
            $db->table('atom_library_cover_queue')
                ->where('id', $item->id)
                ->update(['status' => 'completed']);
            return;
        }
        
        // Try to fetch cover from Open Library
        $coverUrl = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
        
        $ch = curl_init($coverUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'AtoM/2.10 (Library Cover Fetcher)',
        ]);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check if we got a valid image (Open Library returns 1x1 pixel for missing covers)
        if ($httpCode !== 200 || strlen($imageData) < 1000) {
            $this->logSection('library', sprintf('No cover found for ISBN %s (size: %d bytes)', $isbn, strlen($imageData)), null, 'ERROR');
            
            $db->table('atom_library_cover_queue')
                ->where('id', $item->id)
                ->update([
                    'status' => 'not_found',
                    'error_message' => 'No cover available or image too small',
                ]);
            return;
        }
        
        // Save cover locally and create digital object
        try {
            $this->createDigitalObject($db, $item->information_object_id, $imageData, $isbn);
            
            // Also save the cover URL for reference
            $db->table('library_item')
                ->where('information_object_id', $item->information_object_id)
                ->update([
                    'cover_url' => $coverUrl,
                    'cover_url_original' => $coverUrl,
                ]);
            
            // Mark as completed
            $db->table('atom_library_cover_queue')
                ->where('id', $item->id)
                ->update(['status' => 'completed']);
            
            $this->logSection('library', sprintf('Completed ISBN %s - cover saved locally', $isbn));
            
        } catch (Exception $e) {
            $this->logSection('library', sprintf('Error: %s', $e->getMessage()), null, 'ERROR');
            
            $db->table('atom_library_cover_queue')
                ->where('id', $item->id)
                ->update([
                    'status' => 'error',
                    'error_message' => substr($e->getMessage(), 0, 255),
                ]);
        }
    }
    
    protected function createDigitalObject($db, $informationObjectId, $imageData, $isbn)
    {
        // Create temp file
        $tmpFile = tempnam(sys_get_temp_dir(), 'cover_') . '.jpg';
        file_put_contents($tmpFile, $imageData);
        
        try {
            // Create digital object using AtoM's method
            $do = new QubitDigitalObject();
            $do->objectId = $informationObjectId;
            $do->usageId = QubitTerm::MASTER_ID;
            $do->mediaTypeId = QubitTerm::IMAGE_ID;
            $do->assets[] = new QubitAsset($tmpFile);
            $do->save();
            
            $this->logSection('library', sprintf('Created digital object ID: %d', $do->id));
            
        } finally {
            @unlink($tmpFile);
        }
    }
}
