<?php

/*
 * Spectrum Bulk Operations Job
 * 
 * Background job for bulk processing of Spectrum data
 * 
 * @package    ahgSpectrumPlugin
 * @subpackage lib/job
 */

class arSpectrumBulkOperationsJob extends arBaseJob
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
        $conn = Propel::getConnection();
        $sql = "SELECT id FROM spectrum_condition_photo ORDER BY id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
        
        $conn = Propel::getConnection();
        
        $sql = "SELECT 
                    cc.*,
                    io.identifier as object_identifier
                FROM spectrum_condition_check cc
                LEFT JOIN information_object io ON cc.object_id = io.id
                WHERE 1=1";
        
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND cc.check_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND cc.check_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY cc.check_date DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
        
        $conn = Propel::getConnection();
        $processed = 0;
        
        foreach ($updates as $update) {
            $sql = "UPDATE spectrum_location 
                    SET current_location = :new_location,
                        location_date = :move_date,
                        updated_at = NOW()
                    WHERE object_id = :object_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':new_location', $update['new_location']);
            $stmt->bindValue(':move_date', $update['move_date'] ?? date('Y-m-d'));
            $stmt->bindValue(':object_id', $update['object_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            // Create movement record
            $sql = "INSERT INTO spectrum_movement 
                    (object_id, movement_date, location_from, location_to, movement_reason, created_at)
                    VALUES (:object_id, :move_date, :from, :to, :reason, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':object_id', $update['object_id'], PDO::PARAM_INT);
            $stmt->bindValue(':move_date', $update['move_date'] ?? date('Y-m-d'));
            $stmt->bindValue(':from', $update['old_location'] ?? 'Unknown');
            $stmt->bindValue(':to', $update['new_location']);
            $stmt->bindValue(':reason', $update['reason'] ?? 'Bulk location update');
            $stmt->execute();
            
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
        $conn = Propel::getConnection();
        
        $stats = [];
        
        // Total objects with Spectrum data
        $sql = "SELECT COUNT(DISTINCT object_id) as total FROM (
                    SELECT object_id FROM spectrum_entry
                    UNION SELECT object_id FROM spectrum_condition_check
                    UNION SELECT object_id FROM spectrum_location
                    UNION SELECT object_id FROM spectrum_conservation
                ) as all_objects";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats['total_objects'] = $stmt->fetchColumn();
        
        // Condition checks this month
        $sql = "SELECT COUNT(*) FROM spectrum_condition_check 
                WHERE check_date >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats['condition_checks_this_month'] = $stmt->fetchColumn();
        
        // Condition by status
        $sql = "SELECT condition_status, COUNT(*) as count 
                FROM spectrum_condition_check 
                GROUP BY condition_status";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats['condition_by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Active loans
        $sql = "SELECT COUNT(*) FROM spectrum_loan_out 
                WHERE return_date IS NULL OR return_date > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats['active_loans'] = $stmt->fetchColumn();
        
        // Total photos
        $sql = "SELECT COUNT(*) FROM spectrum_condition_photo";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats['total_photos'] = $stmt->fetchColumn();
        
        // Conservation treatments this year
        $sql = "SELECT COUNT(*) FROM spectrum_conservation 
                WHERE YEAR(treatment_date) = YEAR(NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats['treatments_this_year'] = $stmt->fetchColumn();
        
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
        
        $conn = Propel::getConnection();
        
        // Find photos without valid condition checks
        $sql = "SELECT cp.id, cp.file_path 
                FROM spectrum_condition_photo cp
                LEFT JOIN spectrum_condition_check cc ON cp.condition_check_id = cc.id
                WHERE cc.id IS NULL";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
            $sql = "DELETE FROM spectrum_condition_photo WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $orphan['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $deleted++;
        }
        
        $this->info("Cleanup complete. Deleted: {$deleted} orphaned photos");
        
        return true;
    }
    
    /**
     * Archive old records
     */
    protected function archiveOldRecords($parameters)
    {
        $years = $parameters['years'] ?? 5;
        $tables = $parameters['tables'] ?? ['spectrum_condition_check', 'spectrum_movement'];
        
        $cutoffDate = date('Y-m-d', strtotime("-{$years} years"));
        
        $this->info("Archiving records older than {$cutoffDate}");
        
        $conn = Propel::getConnection();
        $archived = [];
        
        foreach ($tables as $table) {
            $dateColumn = 'created_at';
            
            // Determine date column
            if ($table === 'spectrum_condition_check') {
                $dateColumn = 'check_date';
            } elseif ($table === 'spectrum_movement') {
                $dateColumn = 'movement_date';
            }
            
            // Count records to archive
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$dateColumn} < :cutoff";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':cutoff', $cutoffDate);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            $archived[$table] = $count;
            
            // Create archive table if not exists
            $archiveTable = $table . '_archive';
            $sql = "CREATE TABLE IF NOT EXISTS {$archiveTable} LIKE {$table}";
            $conn->exec($sql);
            
            // Move records to archive
            $sql = "INSERT INTO {$archiveTable} SELECT * FROM {$table} WHERE {$dateColumn} < :cutoff";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':cutoff', $cutoffDate);
            $stmt->execute();
            
            // Delete from main table
            $sql = "DELETE FROM {$table} WHERE {$dateColumn} < :cutoff";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':cutoff', $cutoffDate);
            $stmt->execute();
            
            $this->info("{$table}: Archived {$count} records");
        }
        
        $this->job->setOutput(['archived' => $archived]);
        $this->job->save();
        
        return true;
    }
}
