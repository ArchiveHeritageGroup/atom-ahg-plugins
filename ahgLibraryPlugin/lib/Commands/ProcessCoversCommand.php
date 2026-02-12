<?php

namespace AtomFramework\Console\Commands\Library;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Process pending book cover downloads from Open Library.
 */
class ProcessCoversCommand extends BaseCommand
{
    protected string $name = 'library:process-covers';
    protected string $description = 'Process pending book cover downloads from Open Library';
    protected string $detailedDescription = <<<'EOF'
    Downloads book covers from Open Library for queued items and saves locally.

    Examples:
      php bin/atom library:process-covers
      php bin/atom library:process-covers --limit=50
    EOF;

    protected function configure(): void
    {
        $this->addOption('limit', null, 'Max covers to process', '10');
    }

    protected function handle(): int
    {
        // Disable search index updates during batch processing
        \QubitSearch::disable();

        $db = DB::connection();

        $limit = (int) $this->option('limit');

        // Get pending items
        $pending = $db->table('atom_library_cover_queue')
            ->where('status', 'pending')
            ->where('attempts', '<', 3)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $this->info(sprintf('Found %d pending covers to process', count($pending)));

        foreach ($pending as $item) {
            $this->processItem($db, $item);
        }

        // Re-enable search
        \QubitSearch::enable();

        $this->success('Done');

        return 0;
    }

    /**
     * Process a single cover queue item.
     */
    private function processItem($db, $item): void
    {
        $isbn = preg_replace('/[^0-9X]/i', '', $item->isbn);

        $this->line(sprintf('Processing ISBN: %s (IO: %d)', $isbn, $item->information_object_id));

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
            $this->line('Digital object already exists, marking complete');
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
            $this->error(sprintf('No cover found for ISBN %s (size: %d bytes)', $isbn, strlen($imageData)));

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

            $this->success(sprintf('Completed ISBN %s - cover saved locally', $isbn));
        } catch (\Exception $e) {
            $this->error(sprintf('Error: %s', $e->getMessage()));

            $db->table('atom_library_cover_queue')
                ->where('id', $item->id)
                ->update([
                    'status' => 'error',
                    'error_message' => substr($e->getMessage(), 0, 255),
                ]);
        }
    }

    /**
     * Create a digital object from image data.
     */
    private function createDigitalObject($db, $informationObjectId, $imageData, $isbn): void
    {
        // Create temp file
        $tmpFile = tempnam(sys_get_temp_dir(), 'cover_') . '.jpg';
        file_put_contents($tmpFile, $imageData);

        try {
            // Create digital object using AtoM's method
            $do = new \QubitDigitalObject();
            $do->objectId = $informationObjectId;
            $do->usageId = \QubitTerm::MASTER_ID;
            $do->mediaTypeId = \QubitTerm::IMAGE_ID;
            $do->assets[] = new \QubitAsset($tmpFile);
            $do->save();

            $this->line(sprintf('Created digital object ID: %d', $do->id));
        } finally {
            @unlink($tmpFile);
        }
    }
}
