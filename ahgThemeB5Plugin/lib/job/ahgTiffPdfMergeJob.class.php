<?php

/**
 * TIFF to PDF Merge Job - Integrates with AtoM Job Scheduler
 * Uses Laravel Query Builder for database operations
 */

// Ensure Laravel DB is available
\AhgCore\Core\AhgDb::init();

use Illuminate\Database\Capsule\Manager as DB;

class ahgTiffPdfMergeJob extends arBaseJob
{
    /**
     * @see arBaseJob::$requiredParameters
     */
    protected $extraRequiredParameters = ['mergeJobId'];

    protected $jobTable = 'tiff_pdf_merge_job';
    protected $fileTable = 'tiff_pdf_merge_file';

    /**
     * Run the merge job
     */
    public function run($parameters = null, $options = [])
    {
        parent::run($parameters, $options);

        $this->job->addNoteText('Starting TIFF to PDF merge...');
        $this->job->save();

        $mergeJobId = (int) $this->parameters['mergeJobId'];

        // Get merge job details
        $mergeJob = DB::table($this->jobTable)->where('id', $mergeJobId)->first();

        if (!$mergeJob) {
            $this->error($this, 'Merge job not found: ' . $mergeJobId);
            return false;
        }

        // Update status to processing
        DB::table($this->jobTable)
            ->where('id', $mergeJobId)
            ->update([
                'status' => 'processing',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        try {
            // Get files to merge
            $files = DB::table($this->fileTable)
                ->where('merge_job_id', $mergeJobId)
                ->orderBy('page_order')
                ->get();

            if ($files->isEmpty()) {
                throw new Exception('No files to process');
            }

            $this->job->addNoteText(sprintf('Processing %d files...', $files->count()));
            $this->job->save();

            // Prepare paths
            $tempDir = sfConfig::get('app_tiff_pdf_temp_dir', '/tmp/tiff-pdf-merge');
            $jobDir = $tempDir . '/job_' . $mergeJobId;

            if (!is_dir($jobDir)) {
                mkdir($jobDir, 0755, true);
            }

            // Generate output filename
            $outputFilename = $this->sanitizeFilename($mergeJob->job_name) . '.pdf';
            $outputPath = $jobDir . '/' . $outputFilename;

            // Convert to PDF
            $this->job->addNoteText('Converting images to PDF...');
            $this->job->save();

            if (strpos($mergeJob->pdf_standard, 'pdfa') === 0) {
                $result = $this->convertToPdfA($files, $outputPath, $mergeJob);
            } else {
                $result = $this->convertToPdf($files, $outputPath, $mergeJob);
            }

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            // Update file statuses
            DB::table($this->fileTable)
                ->where('merge_job_id', $mergeJobId)
                ->update(['status' => 'processed']);

            // Update merge job with output info
            DB::table($this->jobTable)
                ->where('id', $mergeJobId)
                ->update([
                    'output_filename' => $outputFilename,
                    'output_path' => $outputPath,
                    'processed_files' => $files->count(),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->job->addNoteText('PDF created: ' . $outputFilename);
            $this->job->save();

            // Attach to information object if requested
            $digitalObjectId = null;

            if ($mergeJob->attach_to_record && $mergeJob->information_object_id) {
                $this->job->addNoteText('Attaching PDF to record...');
                $this->job->save();

                $digitalObjectId = $this->attachToRecord(
                    $mergeJob->information_object_id,
                    $outputPath,
                    $outputFilename
                );

                if ($digitalObjectId) {
                    DB::table($this->jobTable)
                        ->where('id', $mergeJobId)
                        ->update([
                            'output_digital_object_id' => $digitalObjectId,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    $this->job->addNoteText('Attached as digital object ID: ' . $digitalObjectId);
                    $this->job->save();
                }
            }

            // Mark as completed
            DB::table($this->jobTable)
                ->where('id', $mergeJobId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->job->setStatusCompleted();
            $this->job->addNoteText(sprintf(
                'Merge complete! Created %d-page PDF%s',
                $files->count(),
                $digitalObjectId ? ' and attached to record' : ''
            ));
            $this->job->save();

            return true;

        } catch (Exception $e) {
            DB::table($this->jobTable)
                ->where('id', $mergeJobId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->error($this, 'Merge failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Convert images to PDF using ImageMagick
     */
    protected function convertToPdf($files, $outputPath, $job)
    {
        $convert = sfConfig::get('app_tiff_pdf_imagemagick_path', '/usr/bin/convert');

        $inputFiles = [];
        foreach ($files as $file) {
            if (file_exists($file->file_path)) {
                $inputFiles[] = escapeshellarg($file->file_path);
            }
        }

        if (empty($inputFiles)) {
            return ['success' => false, 'error' => 'No valid input files'];
        }

        $cmd = sprintf(
            '%s -quality %d -density %d %s %s 2>&1',
            escapeshellcmd($convert),
            (int) $job->compression_quality,
            (int) $job->dpi,
            implode(' ', $inputFiles),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            return ['success' => false, 'error' => 'ImageMagick failed: ' . implode("\n", $output)];
        }

        return ['success' => true];
    }

    /**
     * Convert images to PDF/A using ImageMagick + Ghostscript
     */
    protected function convertToPdfA($files, $outputPath, $job)
    {
        $convert = sfConfig::get('app_tiff_pdf_imagemagick_path', '/usr/bin/convert');
        $gs = sfConfig::get('app_tiff_pdf_ghostscript_path', '/usr/bin/gs');
        $tempDir = sfConfig::get('app_tiff_pdf_temp_dir', '/tmp/tiff-pdf-merge');

        // First create intermediate PDF
        $tempPdf = $tempDir . '/temp_' . uniqid() . '.pdf';

        $inputFiles = [];
        foreach ($files as $file) {
            if (file_exists($file->file_path)) {
                $inputFiles[] = escapeshellarg($file->file_path);
            }
        }

        if (empty($inputFiles)) {
            return ['success' => false, 'error' => 'No valid input files'];
        }

        // Create PDF with ImageMagick
        $imCmd = sprintf(
            '%s -quality %d -density %d %s %s 2>&1',
            escapeshellcmd($convert),
            (int) $job->compression_quality,
            (int) $job->dpi,
            implode(' ', $inputFiles),
            escapeshellarg($tempPdf)
        );

        exec($imCmd, $imOutput, $imReturn);

        if ($imReturn !== 0) {
            return ['success' => false, 'error' => 'ImageMagick failed: ' . implode("\n", $imOutput)];
        }

        // Determine PDF/A level
        $pdfaLevel = match ($job->pdf_standard) {
            'pdfa-1b' => '1',
            'pdfa-3b' => '3',
            default => '2',
        };

        // Create PDF/A definition file
        $pdfaDef = $tempDir . '/pdfa_def_' . uniqid() . '.ps';
        file_put_contents($pdfaDef, $this->getPdfaDefinition($pdfaLevel));

        // Convert to PDF/A with Ghostscript
        $gsCmd = sprintf(
            '%s -dPDFA=%s -dBATCH -dNOPAUSE -dNOOUTERSAVE ' .
            '-sProcessColorModel=DeviceRGB -sDEVICE=pdfwrite ' .
            '-dPDFACompatibilityPolicy=1 -sOutputFile=%s %s %s 2>&1',
            escapeshellcmd($gs),
            $pdfaLevel,
            escapeshellarg($outputPath),
            escapeshellarg($pdfaDef),
            escapeshellarg($tempPdf)
        );

        exec($gsCmd, $gsOutput, $gsReturn);

        // Cleanup temp files
        @unlink($tempPdf);
        @unlink($pdfaDef);

        if ($gsReturn !== 0) {
            return ['success' => false, 'error' => 'Ghostscript failed: ' . implode("\n", $gsOutput)];
        }

        return ['success' => true];
    }

    /**
     * Get PDF/A PostScript definition
     */
    protected function getPdfaDefinition($level)
    {
        return <<<PS
%!PS
% PDF/A-{$level}b definition
/ICCProfile (/usr/share/color/icc/colord/sRGB.icc) def
[ /Title (PDF/A Document) /DOCINFO pdfmark
[ /GTS_PDFXVersion (PDF/A-{$level}b) /DOCINFO pdfmark
PS;
    }

    /**
     * Attach PDF to information object as digital object
     */
    protected function attachToRecord($informationObjectId, $filePath, $filename)
    {
        // Get information object
        $informationObject = QubitInformationObject::getById($informationObjectId);

        if (!$informationObject) {
            $this->job->addNoteText('Warning: Information object not found');
            return null;
        }

        // Copy to uploads directory
        $uploadDir = sfConfig::get('sf_upload_dir') . '/r/' . sprintf('%010d', $informationObjectId);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . '/' . $filename;
        copy($filePath, $destPath);

        // Create digital object using Laravel Query Builder
        $digitalObjectId = DB::table('digital_object')->insertGetId([
            'information_object_id' => $informationObjectId,
            'usage_id' => QubitTerm::MASTER_ID,
            'mime_type' => 'application/pdf',
            'byte_size' => filesize($destPath),
            'checksum' => md5_file($destPath),
            'checksum_type' => 'md5',
            'path' => 'r/' . sprintf('%010d', $informationObjectId) . '/' . $filename,
            'name' => $filename,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Generate derivatives
        $this->generateDerivatives($digitalObjectId, $destPath, $informationObjectId);

        return $digitalObjectId;
    }

    /**
     * Generate thumbnail and reference derivatives
     */
    protected function generateDerivatives($digitalObjectId, $masterPath, $informationObjectId)
    {
        $convert = sfConfig::get('app_tiff_pdf_imagemagick_path', '/usr/bin/convert');
        $uploadDir = sfConfig::get('sf_upload_dir') . '/r/' . sprintf('%010d', $informationObjectId);

        $baseName = pathinfo(basename($masterPath), PATHINFO_FILENAME);

        // Thumbnail
        $thumbFilename = $baseName . '_thumb.jpg';
        $thumbPath = $uploadDir . '/' . $thumbFilename;
        $thumbCmd = sprintf(
            '%s -density 150 %s[0] -thumbnail 100x100 -flatten %s 2>&1',
            escapeshellcmd($convert),
            escapeshellarg($masterPath),
            escapeshellarg($thumbPath)
        );
        exec($thumbCmd);

        if (file_exists($thumbPath)) {
            DB::table('digital_object')->insert([
                'parent_id' => $digitalObjectId,
                'usage_id' => QubitTerm::THUMBNAIL_ID,
                'mime_type' => 'image/jpeg',
                'byte_size' => filesize($thumbPath),
                'path' => 'r/' . sprintf('%010d', $informationObjectId) . '/' . $thumbFilename,
                'name' => $thumbFilename,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Reference
        $refFilename = $baseName . '_ref.jpg';
        $refPath = $uploadDir . '/' . $refFilename;
        $refCmd = sprintf(
            '%s -density 150 %s[0] -resize 480x480 -flatten %s 2>&1',
            escapeshellcmd($convert),
            escapeshellarg($masterPath),
            escapeshellarg($refPath)
        );
        exec($refCmd);

        if (file_exists($refPath)) {
            DB::table('digital_object')->insert([
                'parent_id' => $digitalObjectId,
                'usage_id' => QubitTerm::REFERENCE_ID,
                'mime_type' => 'image/jpeg',
                'byte_size' => filesize($refPath),
                'path' => 'r/' . sprintf('%010d', $informationObjectId) . '/' . $refFilename,
                'name' => $refFilename,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Sanitize filename
     */
    protected function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($filename));
        $filename = preg_replace('/_+/', '_', $filename);

        return trim($filename, '_') ?: 'merged_document';
    }
}
