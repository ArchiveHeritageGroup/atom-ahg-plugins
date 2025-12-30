<?php

/*
 * Spectrum Photo Processing Job
 * 
 * Background job for generating thumbnails and processing photos
 * 
 * @package    ahgSpectrumPlugin
 * @subpackage lib/job
 */

class arSpectrumPhotoProcessingJob extends arBaseJob
{
    /**
     * @see arBaseJob::$requiredParameters
     */
    protected $requiredParameters = ['conditionCheckId'];
    
    /**
     * Job name for display
     */
    protected $jobName = 'Spectrum: Process Condition Photos';
    
    /**
     * Execute the job
     */
    public function runJob($parameters)
    {
        $this->info('Starting photo processing job');
        
        $conditionCheckId = $parameters['conditionCheckId'];
        $regenerate = $parameters['regenerate'] ?? false;
        
        // Get all photos for this condition check
        $photos = SpectrumConditionPhoto::getByConditionCheck($conditionCheckId);
        
        if (empty($photos)) {
            $this->info('No photos found for condition check ID: ' . $conditionCheckId);
            return true;
        }
        
        $this->info(sprintf('Processing %d photos', count($photos)));
        
        $processed = 0;
        $errors = 0;
        $photoService = new SpectrumPhotoService();
        
        foreach ($photos as $photoData) {
            try {
                $photo = new SpectrumConditionPhoto($photoData['id']);
                $filePath = sfConfig::get('sf_upload_dir') . '/' . $photo->get('file_path');
                
                if (!file_exists($filePath)) {
                    $this->error('File not found: ' . $filePath);
                    $errors++;
                    continue;
                }
                
                // Check if thumbnails need regeneration
                $thumbPath = dirname($filePath) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '_medium.' . pathinfo($filePath, PATHINFO_EXTENSION);
                
                if ($regenerate || !file_exists($thumbPath)) {
                    $this->info('Generating thumbnails for: ' . $photo->get('original_filename'));
                    
                    // Use reflection to access protected method (or make it public)
                    $this->createThumbnails($filePath, $photoService);
                    
                    $processed++;
                }
                
                // Update job progress
                $this->job->setStatusNote(sprintf('Processed %d of %d photos', $processed, count($photos)));
                $this->job->save();
                
            } catch (Exception $e) {
                $this->error('Error processing photo ID ' . $photoData['id'] . ': ' . $e->getMessage());
                $errors++;
            }
        }
        
        $this->info(sprintf('Photo processing complete. Processed: %d, Errors: %d', $processed, $errors));
        
        return $errors === 0;
    }
    
    /**
     * Create thumbnails
     */
    protected function createThumbnails($sourcePath, $photoService)
    {
        $sizes = $photoService->getSetting('photo_thumbnail_sizes', ['small' => 150, 'medium' => 300, 'large' => 600]);
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new Exception('Invalid image file');
        }
        
        $mimeType = $imageInfo['mime'];
        
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
            case 'image/tiff':
                // TIFF requires additional handling
                $this->info('TIFF format - skipping thumbnail generation');
                return;
            default:
                throw new Exception('Unsupported image type: ' . $mimeType);
        }
        
        if (!$source) {
            throw new Exception('Failed to load image');
        }
        
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
                $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                imagefill($thumb, 0, 0, $transparent);
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
            
            $this->info('Created ' . $sizeName . ' thumbnail: ' . $thumbPath);
        }
        
        imagedestroy($source);
    }
}
