<?php

/*
 * Spectrum Bulk Operations Job
 * 
 * Background job for bulk processing of Spectrum data
 * 
 * @package    ahgSpectrumPlugin
 * @subpackage lib/job
 */

class ahgSpectrumBulkOperationsJob extends arBaseJob
{
    /**
     * @see arBaseJob::$requiredParameters
     */
    protected $requiredParameters = ['operation'];
    
    /**
     * Job name for display
     */
    protected $jobName = 'Spectrum: Bulk Operations';
    
    /**
     * Execute the job
     */
    public function runJob($parameters)
    {
        $operation = $parameters['operation'];
        
        $this->info('Starting bulk operation: ' . $operation);
        
        switch ($operation) {
            case 'regenerate_thumbnails':
                return $this->regenerateAllThumbnails($parameters);
                
            case 'export_condition_reports':
                return $this->exportConditionReports($parameters);
                
            case 'update_locations':
                return $this->bulkUpdateLocations($parameters);
                
            case 'calculate_statistics':
                return $this->calculateStatistics($parameters);
                
            case 'cleanup_orphaned_photos':
                return $this->cleanupOrphanedPhotos($parameters);
                
            case 'archive_old_records':
                return $this->archiveOldRecords($parameters);
                
            default:
                $this->error('Unknown operation: ' . $operation);
                return false;
        }
    }
    
    /**
     * Regenerate all thumbnails
     */
    protected function regenerateAllThumbnails($parameters)
    {
        $photos = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_photo')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $total = count($photos);
        
        $this->info("Regenerating thumbnails for {$total} photos");
        
        $processed = 0;
        $errors = 0;
        $photoService = new SpectrumPhotoService();
        
        foreach ($photos as $photoId) {
            try {
                $photo = new SpectrumConditionPhoto($photoId);
                $filePath = sfConfig::get('sf_upload_dir') . '/' . $photo->get('file_path');
                
                if (file_exists($filePath)) {
                    // Use the photo service to create thumbnails
                    $sizes = $photoService->getSetting('photo_thumbnail_sizes', ['small' => 150, 'medium' => 300, 'large' => 600]);
                    
                    $imageInfo = getimagesize($filePath);
                    if ($imageInfo) {
                        $this->createThumbnailsForFile($filePath, $imageInfo['mime'], $sizes);
                        $processed++;
                    }
                }
                
                // Update progress every 10 photos
                if ($processed % 10 == 0) {
                    $this->job->setStatusNote(sprintf('Processed %d of %d photos', $processed, $total));
                    $this->job->save();
                }
                
            } catch (Exception $e) {
                $this->error('Error processing photo ' . $photoId . ': ' . $e->getMessage());
                $errors++;
            }
        }
        
        $this->info("Thumbnail regeneration complete. Processed: {$processed}, Errors: {$errors}");
        
        return $errors < ($total * 0.1); // Success if less than 10% errors
    }
    
    /**
     * Create thumbnails for a file
     */
    protected function createThumbnailsForFile($sourcePath, $mimeType, $sizes)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);
        
        $basePath = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
        
        foreach ($sizes as $sizeName => $maxDimension) {
            if ($origWidth > $origHeight) {
                $newWidth = $maxDimension;
                $newHeight = (int) ($origHeight * ($maxDimension / $origWidth));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) ($origWidth * ($maxDimension / $origHeight));
            }
            
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($mimeType === 'image/png') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }
            
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            
            $thumbPath = $basePath . '/' . $filename . '_' . $sizeName . '.' . $ext;
            
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($thumb, $thumbPath, 85);
                    break;
                case 'image/png':
                    imagepng($thumb, $thumbPath, 8);
                    break;
                case 'image/webp':
                    imagewebp($thumb, $thumbPath, 85);
                    break;
            }
            
            imagedestroy($thumb);
        }
        
        imagedestroy($source);
        return true;
    }
    
    /**
     * Export all condition reports
     */
    protected function exportConditionReports($parameters)
    {
        $format = $parameters['format'] ?? 'csv';
        $dateFrom = $parameters['date_from'] ?? null;
        $dateTo = $parameters['date_to'] ?? null;

        $query = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_check as cc')
            ->leftJoin('information_object as io', 'cc.object_id', '=', 'io.id')
            ->select('cc.*', 'io.identifier as object_identifier');

        if ($dateFrom) {
            $query->where('cc.check_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('cc.check_date', '<=', $dateTo);
        }

        $records = $query->orderBy('cc.check_date', 'desc')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
        
        $this->info(sprintf('Exporting %d condition reports', count($records)));
        
        // Create export file
        $outputDir = sfConfig::get('sf_upload_dir') . '/spectrum/exports';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $filename = 'condition_reports_export_' . date('Ymd_His') . '.' . $format;
        $outputPath = $outputDir . '/' . $filename;
        
        switch ($format) {
            case 'csv':
                $this->exportToCsv($records, $outputPath);
                break;
            case 'json':
                file_put_contents($outputPath, json_encode($records, JSON_PRETTY_PRINT));
                break;
            default:
                $this->error('Unknown export format: ' . $format);
                return false;
        }
        
        $this->job->setOutput(['export_path' => 'spectrum/exports/' . $filename]);
        $this->job->save();
        
        $this->info('Export complete: ' . $outputPath);
        
        return true;
    }
    
    /**
     * Export to CSV
     */
    protected function exportToCsv($records, $outputPath)
    {
        $fp = fopen($outputPath, 'w');
        
        if (!empty($records)) {
            // Header row
            fputcsv($fp, array_keys($records[0]));
            
            // Data rows
            foreach ($records as $record) {
                fputcsv($fp, $record);
            }
        }
        
        fclose($fp);
    }
    
    /**
     * Bulk update locations
     */
    protected function bulkUpdateLocations($parameters)
    {
        $updates = $parameters['updates'] ?? [];

        if (empty($updates)) {
            $this->error('No location updates provided');
            return false;
        }

        $processed = 0;

        foreach ($updates as $update) {
            // Update location
            \Illuminate\Database\Capsule\Manager::table('spectrum_location')
                ->where('object_id', $update['object_id'])
                ->update([
                    'current_location' => $update['new_location'],
                    'location_date' => $update['move_date'] ?? date('Y-m-d'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Create movement record
            \Illuminate\Database\Capsule\Manager::table('spectrum_movement')->insert([
                'object_id' => $update['object_id'],
                'movement_date' => $update['move_date'] ?? date('Y-m-d'),
                'location_from' => $update['old_location'] ?? 'Unknown',
                'location_to' => $update['new_location'],
                'movement_reason' => $update['reason'] ?? 'Bulk location update',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $processed++;
        }

        $this->info("Location update complete. Processed: {$processed} objects");

        return true;
    }
    
    /**
     * Calculate statistics
     */
    protected function calculateStatistics($parameters)
    {
        $stats = [];

        // Total objects with Spectrum data (using raw for UNION)
        $result = \Illuminate\Database\Capsule\Manager::select("
            SELECT COUNT(DISTINCT object_id) as total FROM (
                SELECT object_id FROM spectrum_entry
                UNION SELECT object_id FROM spectrum_condition_check
                UNION SELECT object_id FROM spectrum_location
                UNION SELECT object_id FROM spectrum_conservation
            ) as all_objects
        ");
        $stats['total_objects'] = $result[0]->total ?? 0;

        // Condition checks this month
        $stats['condition_checks_this_month'] = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_check')
            ->whereRaw("check_date >= DATE_FORMAT(NOW(), '%Y-%m-01')")
            ->count();

        // Condition by status
        $statusCounts = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_check')
            ->selectRaw('condition_status, COUNT(*) as count')
            ->groupBy('condition_status')
            ->get();
        $stats['condition_by_status'] = [];
        foreach ($statusCounts as $row) {
            $stats['condition_by_status'][$row->condition_status] = $row->count;
        }

        // Active loans
        $stats['active_loans'] = \Illuminate\Database\Capsule\Manager::table('spectrum_loan_out')
            ->where(function ($query) {
                $query->whereNull('return_date')
                    ->orWhereRaw('return_date > NOW()');
            })
            ->count();

        // Total photos
        $stats['total_photos'] = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_photo')->count();

        // Conservation treatments this year
        $stats['treatments_this_year'] = \Illuminate\Database\Capsule\Manager::table('spectrum_conservation')
            ->whereRaw('YEAR(treatment_date) = YEAR(NOW())')
            ->count();

        // Store statistics
        $this->job->setOutput(['statistics' => $stats, 'generated_at' => date('c')]);
        $this->job->save();

        $this->info('Statistics calculated: ' . json_encode($stats));

        return true;
    }
    
    /**
     * Cleanup orphaned photos
     */
    protected function cleanupOrphanedPhotos($parameters)
    {
        $dryRun = $parameters['dry_run'] ?? true;

        // Find photos without valid condition checks
        $orphans = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_photo as cp')
            ->leftJoin('spectrum_condition_check as cc', 'cp.condition_check_id', '=', 'cc.id')
            ->whereNull('cc.id')
            ->select('cp.id', 'cp.file_path')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        $this->info(sprintf('Found %d orphaned photos', count($orphans)));

        if ($dryRun) {
            $this->info('Dry run - no files deleted');
            $this->job->setOutput(['orphans' => $orphans]);
            $this->job->save();
            return true;
        }

        $deleted = 0;

        foreach ($orphans as $orphan) {
            // Delete file
            $filePath = sfConfig::get('sf_upload_dir') . '/' . $orphan['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete thumbnails
            $basePath = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);

            foreach (['small', 'medium', 'large'] as $size) {
                $thumbPath = $basePath . '/' . $filename . '_' . $size . '.' . $ext;
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }

            // Delete database record
            \Illuminate\Database\Capsule\Manager::table('spectrum_condition_photo')
                ->where('id', $orphan['id'])
                ->delete();

            $deleted++;
        }

        $this->info("Cleanup complete. Deleted: {$deleted} orphaned photos");

        return true;
    }
    
    /**
     * Archive old records
     *
     * Uses allowlist validation to prevent SQL injection via table names
     */
    protected function archiveOldRecords($parameters)
    {
        // Allowlist of valid table names with their date columns
        $validTables = [
            'spectrum_condition_check' => 'check_date',
            'spectrum_movement' => 'movement_date',
            'spectrum_conservation' => 'treatment_date',
            'spectrum_loan_out' => 'created_at',
            'spectrum_loan_in' => 'created_at',
        ];

        $years = $parameters['years'] ?? 5;
        $requestedTables = $parameters['tables'] ?? ['spectrum_condition_check', 'spectrum_movement'];

        // Validate and filter to only allowed tables
        $tables = array_intersect($requestedTables, array_keys($validTables));

        if (empty($tables)) {
            $this->error('No valid tables specified for archiving');
            return false;
        }

        $cutoffDate = date('Y-m-d', strtotime("-{$years} years"));

        $this->info("Archiving records older than {$cutoffDate}");

        $archived = [];

        foreach ($tables as $table) {
            // Get the validated date column from allowlist
            $dateColumn = $validTables[$table];

            // Count records to archive using Query Builder
            $count = \Illuminate\Database\Capsule\Manager::table($table)
                ->where($dateColumn, '<', $cutoffDate)
                ->count();

            $archived[$table] = $count;

            // Use quoted identifiers for DDL operations
            $quotedTable = '`' . str_replace('`', '``', $table) . '`';
            $quotedArchiveTable = '`' . str_replace('`', '``', $table . '_archive') . '`';

            // Create archive table if not exists (DDL requires raw statement)
            \Illuminate\Database\Capsule\Manager::statement(
                "CREATE TABLE IF NOT EXISTS {$quotedArchiveTable} LIKE {$quotedTable}"
            );

            // Move records to archive (complex INSERT...SELECT requires raw)
            \Illuminate\Database\Capsule\Manager::statement(
                "INSERT INTO {$quotedArchiveTable} SELECT * FROM {$quotedTable} WHERE `{$dateColumn}` < ?",
                [$cutoffDate]
            );

            // Delete from main table using Query Builder
            \Illuminate\Database\Capsule\Manager::table($table)
                ->where($dateColumn, '<', $cutoffDate)
                ->delete();

            $this->info("{$table}: Archived {$count} records");
        }

        $this->job->setOutput(['archived' => $archived]);
        $this->job->save();

        return true;
    }
}
