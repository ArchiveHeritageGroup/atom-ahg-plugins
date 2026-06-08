<?php

declare(strict_types=1);

namespace AtomFramework\Services;

use AtomFramework\Repositories\TiffPdfMergeRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

/**
 * Service for merging TIFF/image files into PDF documents
 */
class TiffPdfMergeService
{
    protected TiffPdfMergeRepository $repository;
    protected array $settings;
    protected string $tempDir;
    protected string $uploadDir;

    // Supported input formats
    protected array $supportedFormats = [
        'tif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp',
    ];

    public function __construct()
    {
        $this->repository = new TiffPdfMergeRepository();
        $this->settings = $this->repository->getSettings();
        $this->tempDir = $this->settings['temp_directory'] ?? '/tmp/tiff-pdf-merge';
        
        // Get upload directory - handle CLI context
        if (class_exists('sfConfig')) {
            $this->uploadDir = \sfConfig::get('sf_upload_dir', 'uploads');
        } else {
            $this->uploadDir = 'uploads';
        }

        // Ensure temp directory exists
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Create a new merge job
     */
    public function createJob(int $userId, string $jobName, ?int $informationObjectId = null, array $options = []): int
    {
        $jobData = [
            'user_id' => $userId,
            'job_name' => $jobName,
            'information_object_id' => $informationObjectId,
            'pdf_standard' => $options['pdf_standard'] ?? $this->settings['default_pdf_standard'] ?? 'pdfa-2b',
            'compression_quality' => $options['compression_quality'] ?? $this->settings['default_quality'] ?? 85,
            'page_size' => $options['page_size'] ?? 'auto',
            'orientation' => $options['orientation'] ?? 'auto',
            'dpi' => $options['dpi'] ?? $this->settings['default_dpi'] ?? 300,
            'preserve_originals' => $options['preserve_originals'] ?? 1,
            'attach_to_record' => $options['attach_to_record'] ?? 1,
            'options' => $options,
        ];

        return $this->repository->createJob($jobData);
    }

    /**
     * Handle file upload for a merge job
     */
    public function uploadFile(int $jobId, array $uploadedFile): array
    {
        $job = $this->repository->getJob($jobId);

        if (!$job) {
            throw new Exception("Job not found: {$jobId}");
        }

        if ($job->status !== 'pending') {
            throw new Exception("Cannot add files to a job that is not pending");
        }

        // Validate file
        $validation = $this->validateFile($uploadedFile);

        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $storedFilename = uniqid('tiff_') . '.' . $extension;
        $jobDir = $this->getJobDirectory($jobId);
        $filePath = $jobDir . '/' . $storedFilename;

        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        // Get image information
        $imageInfo = $this->getImageInfo($filePath);

        // Get current max page order
        $files = $this->repository->getJobFiles($jobId);
        $maxOrder = $files->isEmpty() ? -1 : $files->max('page_order');

        // Add file record
        $fileId = $this->repository->addFile($jobId, [
            'original_filename' => $uploadedFile['name'],
            'stored_filename' => $storedFilename,
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'mime_type' => $imageInfo['mime_type'] ?? 'image/tiff',
            'width' => $imageInfo['width'] ?? null,
            'height' => $imageInfo['height'] ?? null,
            'bit_depth' => $imageInfo['bit_depth'] ?? null,
            'color_space' => $imageInfo['color_space'] ?? null,
            'page_order' => $maxOrder + 1,
            'checksum_md5' => md5_file($filePath),
            'metadata' => $imageInfo,
        ]);

        return [
            'success' => true,
            'file_id' => $fileId,
            'filename' => $uploadedFile['name'],
            'stored_filename' => $storedFilename,
            'image_info' => $imageInfo,
        ];
    }

    /**
     * Process a merge job
     */
    public function processJob(int $jobId): array
    {
        $job = $this->repository->getJob($jobId);

        if (!$job) {
            throw new Exception("Job not found: {$jobId}");
        }

        if ($job->status === 'processing') {
            throw new Exception("Job is already being processed");
        }

        if ($job->status === 'completed') {
            return ['success' => true, 'message' => 'Job already completed', 'output_path' => $job->output_path];
        }

        // Update status to processing
        $this->repository->updateJobStatus($jobId, 'processing');

        try {
            // Get files in order
            $files = $this->repository->getJobFiles($jobId);

            if ($files->isEmpty()) {
                throw new Exception("No files to process");
            }

            // Prepare output filename
            $outputFilename = $this->sanitizeFilename($job->job_name) . '.pdf';
            $outputPath = $this->getJobDirectory($jobId) . '/' . $outputFilename;

            // Always produce PDF/A (archival target). If the job carries a
            // non-PDF/A standard, convertToPdfA() defaults it to pdfa-2b.
            if (strpos((string) $job->pdf_standard, 'pdfa') !== 0) {
                $job->pdf_standard = 'pdfa-2b';
            }
            $result = $this->convertToPdfA($files, $outputPath, $job);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            // Update each file status
            foreach ($files as $file) {
                $this->repository->updateFileStatus($file->id, 'processed');
            }

            // Update job with output info
            $this->repository->updateJobOutput($jobId, [
                'output_filename' => $outputFilename,
                'output_path' => $outputPath,
                'processed_files' => $files->count(),
            ]);

            // Attach to information object if requested
            $digitalObjectId = null;

            if ($job->attach_to_record && $job->information_object_id) {
                $digitalObjectId = $this->attachToRecord($job->information_object_id, $outputPath, $outputFilename, $job->user_id);

                $this->repository->updateJobOutput($jobId, [
                    'output_digital_object_id' => $digitalObjectId,
                ]);
            }

            // Mark job as completed
            $this->repository->updateJobStatus($jobId, 'completed');

            return [
                'success' => true,
                'output_path' => $outputPath,
                'output_filename' => $outputFilename,
                'digital_object_id' => $digitalObjectId,
                'pages' => $files->count(),
            ];
        } catch (Exception $e) {
            $this->repository->updateJobStatus($jobId, 'failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Public conversion entrypoint shared by the Service's own processJob() and
     * by TiffPdfMergeJob (the web/worker path), so there is ONE memory-safe
     * conversion implementation. Always targets PDF/A (archival).
     *
     * @param  iterable  $files  rows with a ->file_path, in page order
     */
    public function mergeFilesToPdf($files, string $outputPath, object $job): array
    {
        if (strpos((string) ($job->pdf_standard ?? ''), 'pdfa') !== 0) {
            $job->pdf_standard = 'pdfa-2b';
        }

        return $this->convertToPdfA($files, $outputPath, $job);
    }

    /**
     * Convert images to a standard (non-PDF/A) PDF.
     *
     * Memory-safe: each page is converted to its own single-page PDF (bounded
     * ImageMagick resources) and the pages are concatenated with qpdf. This
     * NEVER loads all pages into one ImageMagick process, so a 258 x 42 MB scan
     * set merges in roughly one page's worth of RAM instead of ~11 GB.
     */
    protected function convertToPdf($files, string $outputPath, object $job): array
    {
        $build = $this->buildMergedPdf($files, $job);
        if (!$build['success']) {
            return $build;
        }

        $ok = @rename($build['path'], $outputPath) || @copy($build['path'], $outputPath);
        $this->cleanupWorkDir($build['work']);

        if (!$ok || !is_file($outputPath)) {
            return ['success' => false, 'error' => 'Failed to write merged PDF output'];
        }

        return ['success' => true, 'output_path' => $outputPath];
    }

    /**
     * Convert images to PDF/A.
     *
     * Same memory-safe per-page merge as convertToPdf(), then a single
     * Ghostscript PDF/A pass over the merged PDF (gs streams page-by-page, so
     * this stage is also bounded regardless of page count).
     */
    protected function convertToPdfA($files, string $outputPath, object $job): array
    {
        $ghostscript = $this->settings['ghostscript_path'] ?? '/usr/bin/gs';

        $build = $this->buildMergedPdf($files, $job);
        if (!$build['success']) {
            return $build;
        }
        $tempPdf = $build['path'];

        // Determine PDF/A level
        $pdfaLevel = match ($job->pdf_standard) {
            'pdfa-1b' => '1',
            'pdfa-2b' => '2',
            'pdfa-3b' => '3',
            default => '2',
        };

        $pdfaDefFile = $this->createPdfaDefinition($pdfaLevel);

        $gsCmd = sprintf(
            '%s -dPDFA=%s -dBATCH -dNOPAUSE -dNOOUTERSAVE -dUseCIEColor ' .
            '-sProcessColorModel=DeviceRGB -sDEVICE=pdfwrite ' .
            '-dPDFACompatibilityPolicy=1 -sOutputFile=%s %s %s 2>&1',
            escapeshellcmd($ghostscript),
            $pdfaLevel,
            escapeshellarg($outputPath),
            escapeshellarg($pdfaDefFile),
            escapeshellarg($tempPdf)
        );

        exec($gsCmd, $gsOutput, $gsReturn);

        @unlink($pdfaDefFile);
        $this->cleanupWorkDir($build['work']);   // removes the merged temp PDF + work dir

        if ($gsReturn !== 0) {
            return [
                'success' => false,
                'error' => 'Ghostscript PDF/A conversion failed: ' . implode("\n", $gsOutput),
            ];
        }

        return ['success' => true, 'output_path' => $outputPath];
    }

    /**
     * Build a single merged PDF from the job's page images WITHOUT loading them
     * all into one process. Returns ['success'=>bool, 'path'=>mergedPdf,
     * 'work'=>workDir, 'error'=>?]. The caller owns the work dir (cleanup via
     * cleanupWorkDir()). Peak memory is bounded to one page.
     */
    protected function buildMergedPdf($files, object $job): array
    {
        @set_time_limit(0);

        $imageMagick = $this->settings['imagemagick_path'] ?? '/usr/bin/convert';
        $qpdf = $this->settings['qpdf_path'] ?? '/usr/bin/qpdf';

        $work = $this->tempDir . '/merge_' . uniqid();
        if (!@mkdir($work, 0775, true) && !is_dir($work)) {
            return ['success' => false, 'error' => 'Could not create work directory', 'work' => $work];
        }
        // Keep ImageMagick's own scratch off /tmp's memory-backed space.
        putenv('MAGICK_TMPDIR=' . $work);

        // Optional fixed page geometry (carried over from the old code path).
        $pageGeom = null;
        if ($job->page_size !== 'auto') {
            $pageSizes = ['a4' => '595x842', 'letter' => '612x792', 'legal' => '612x1008', 'a3' => '842x1190'];
            $pageGeom = $pageSizes[$job->page_size] ?? null;
        }

        $pagePdfs = [];
        $n = 0;
        foreach ($files as $file) {
            if (!file_exists($file->file_path)) {
                continue;
            }
            $n++;
            $pageOut = sprintf('%s/p-%05d.pdf', $work, $n);
            // Bounded-memory single-page convert: caps RAM/scratch so one huge
            // uncompressed scan cannot blow the box; JPEG-compresses at -quality.
            $cmd = sprintf(
                '%s -limit memory 256MiB -limit map 512MiB -limit disk 8GiB -density %d -quality %d -compress JPEG %s%s %s 2>&1',
                escapeshellcmd($imageMagick),
                (int) $job->dpi,
                (int) $job->compression_quality,
                $pageGeom ? ('-page ' . $pageGeom . ' ') : '',
                escapeshellarg($file->file_path . '[0]'),   // [0] = first frame (guards multi-page TIFFs)
                escapeshellarg($pageOut)
            );
            // -compress JPEG keeps each page PDF small (~hundreds of KB vs ~30 MB
            // zip-stored), so 258 pages of intermediates stay well under disk too.
            exec($cmd, $out, $rc);
            if ($rc !== 0 || !is_file($pageOut) || filesize($pageOut) < 1) {
                $this->cleanupWorkDir($work);

                return ['success' => false, 'error' => 'Page conversion failed for ' . basename($file->file_path) . ': ' . implode("\n", array_slice((array) $out, -3)), 'work' => $work];
            }
            $pagePdfs[] = $pageOut;
        }

        if (empty($pagePdfs)) {
            $this->cleanupWorkDir($work);

            return ['success' => false, 'error' => 'No valid input files found', 'work' => $work];
        }

        $merged = $this->concatPdfs($qpdf, $pagePdfs, $work, 0);
        if (!$merged) {
            $this->cleanupWorkDir($work);

            return ['success' => false, 'error' => 'qpdf concatenation failed', 'work' => $work];
        }

        return ['success' => true, 'path' => $merged, 'work' => $work];
    }

    /**
     * Concatenate page PDFs with qpdf, in batches of 100 so neither the arg
     * list nor any single qpdf invocation grows unbounded. Recurses on the
     * sub-batch results. Returns the merged path or null on failure.
     */
    protected function concatPdfs(string $qpdf, array $pdfs, string $work, int $depth): ?string
    {
        $batchSize = 100;

        if (count($pdfs) <= $batchSize) {
            $out = $work . '/merged_' . $depth . '_0.pdf';
            $args = implode(' ', array_map('escapeshellarg', $pdfs));
            exec(sprintf('%s --empty --pages %s -- %s 2>&1', escapeshellcmd($qpdf), $args, escapeshellarg($out)), $o, $rc);

            // qpdf rc 3 = warnings only, output still valid.
            return (($rc === 0 || $rc === 3) && is_file($out)) ? $out : null;
        }

        $subMerged = [];
        foreach (array_chunk($pdfs, $batchSize) as $i => $chunk) {
            $sub = sprintf('%s/sub_%d_%03d.pdf', $work, $depth, $i);
            $args = implode(' ', array_map('escapeshellarg', $chunk));
            exec(sprintf('%s --empty --pages %s -- %s 2>&1', escapeshellcmd($qpdf), $args, escapeshellarg($sub)), $o, $rc);
            if (($rc !== 0 && $rc !== 3) || !is_file($sub)) {
                return null;
            }
            $subMerged[] = $sub;
            foreach ($chunk as $p) {
                @unlink($p);   // free page PDFs as we fold them up
            }
        }

        return $this->concatPdfs($qpdf, $subMerged, $work, $depth + 1);
    }

    /** Recursively remove a buildMergedPdf() work directory (guarded to merge_ dirs). */
    protected function cleanupWorkDir(?string $dir): void
    {
        if (!$dir || !is_dir($dir) || strpos(basename($dir), 'merge_') !== 0) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }

    /**
     * Create PDF/A definition PostScript file
     */
    protected function createPdfaDefinition(string $level): string
    {
        $defFile = $this->tempDir . '/' . uniqid('pdfa_def_') . '.ps';

        $content = <<<POSTSCRIPT
%!PS
% PDF/A definition file

/ICCProfile (/usr/share/color/icc/colord/sRGB.icc)
def

[
    /Title (PDF/A Document)
    /DOCINFO pdfmark

% PDF/A-{$level}b compliance
[ /GTS_PDFXVersion (PDF/A-{$level}b) /DOCINFO pdfmark
POSTSCRIPT;

        file_put_contents($defFile, $content);

        return $defFile;
    }

    /**
     * Attach generated PDF to information object as digital object
     */
    protected function attachToRecord(int $informationObjectId, string $filePath, string $filename, int $userId): ?int
    {
        // Copy file to uploads directory
        $uploadsSubdir = 'r/' . sprintf('%010d', $informationObjectId);
        $uploadsDir = $this->uploadDir . '/' . $uploadsSubdir;

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $newPath = $uploadsDir . '/' . $filename;
        copy($filePath, $newPath);

        // Create digital object record
        $digitalObjectId = DB::table('digital_object')->insertGetId([
            'information_object_id' => $informationObjectId,
            'usage_id' => 166, // Master
            'media_type_id' => 183, // Application
            'mime_type' => 'application/pdf',
            'byte_size' => filesize($newPath),
            'checksum_type' => 'md5',
            'checksum' => md5_file($newPath),
            'path' => $uploadsSubdir . '/' . $filename,
            'name' => $filename,
            'sequence' => $this->getNextSequence($informationObjectId),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Generate derivatives (thumbnail, reference)
        $this->generateDerivatives($digitalObjectId, $newPath);

        return $digitalObjectId;
    }

    /**
     * Get next sequence number for digital objects
     */
    protected function getNextSequence(int $informationObjectId): int
    {
        $maxSeq = DB::table('digital_object')
            ->where('information_object_id', $informationObjectId)
            ->max('sequence');

        return ($maxSeq ?? 0) + 1;
    }

    /**
     * Generate thumbnail and reference derivatives for PDF
     */
    protected function generateDerivatives(int $digitalObjectId, string $masterPath): void
    {
        $imageMagick = $this->settings['imagemagick_path'] ?? '/usr/bin/convert';
        $uploadsDir = dirname($masterPath);

        // Generate thumbnail (first page)
        $thumbPath = $uploadsDir . '/thumbnail_' . basename($masterPath, '.pdf') . '.jpg';
        $thumbCmd = sprintf(
            '%s -density 150 %s[0] -thumbnail 100x100 -flatten %s 2>&1',
            escapeshellcmd($imageMagick),
            escapeshellarg($masterPath),
            escapeshellarg($thumbPath)
        );
        exec($thumbCmd);

        // Generate reference image (first page, larger)
        $refPath = $uploadsDir . '/reference_' . basename($masterPath, '.pdf') . '.jpg';
        $refCmd = sprintf(
            '%s -density 150 %s[0] -resize 480x480 -flatten %s 2>&1',
            escapeshellcmd($imageMagick),
            escapeshellarg($masterPath),
            escapeshellarg($refPath)
        );
        exec($refCmd);
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $this->settings['allowed_extensions'] ?? $this->supportedFormats;

        if (!in_array($extension, $allowed)) {
            return ['valid' => false, 'error' => "File type not allowed: {$extension}"];
        }

        // Check file size
        $maxSize = ($this->settings['max_file_size_mb'] ?? 500) * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File exceeds maximum size limit'];
        }

        // Verify it's actually an image
        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'File is not a valid image'];
        }

        return ['valid' => true];
    }

    /**
     * Get image information
     */
    protected function getImageInfo(string $filePath): array
    {
        $info = [
            'width' => null,
            'height' => null,
            'mime_type' => null,
            'bit_depth' => null,
            'color_space' => null,
            'pages' => 1,
        ];

        // Basic image info
        $imageInfo = @getimagesize($filePath);

        if ($imageInfo) {
            $info['width'] = $imageInfo[0];
            $info['height'] = $imageInfo[1];
            $info['mime_type'] = $imageInfo['mime'] ?? null;
            $info['bit_depth'] = $imageInfo['bits'] ?? null;
        }

        // Try to get more detailed info with identify (ImageMagick)
        $imageMagickDir = dirname($this->settings['imagemagick_path'] ?? '/usr/bin/convert');
        $identify = $imageMagickDir . '/identify';

        if (file_exists($identify)) {
            $cmd = sprintf('%s -verbose %s 2>&1', escapeshellcmd($identify), escapeshellarg($filePath));
            exec($cmd, $output);

            $outputStr = implode("\n", $output);

            if (preg_match('/Colorspace:\s*(\w+)/i', $outputStr, $matches)) {
                $info['color_space'] = $matches[1];
            }

            if (preg_match('/Depth:\s*(\d+)-bit/i', $outputStr, $matches)) {
                $info['bit_depth'] = (int) $matches[1];
            }

            // Count pages for multi-page TIFF
            if (preg_match_all('/Image:/i', $outputStr, $matches)) {
                $info['pages'] = count($matches[0]);
            }
        }

        return $info;
    }

    /**
     * Get job directory, creating if necessary
     */
    protected function getJobDirectory(int $jobId): string
    {
        $dir = $this->tempDir . '/job_' . $jobId;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Sanitize filename
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove path info
        $filename = basename($filename);

        // Replace spaces and special chars
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);

        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);

        return trim($filename, '_');
    }

    /**
     * Get upload error message
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    /**
     * Download output PDF
     */
    public function getOutputPath(int $jobId): ?string
    {
        $job = $this->repository->getJob($jobId);

        if (!$job || $job->status !== 'completed') {
            return null;
        }

        if ($job->output_path && file_exists($job->output_path)) {
            return $job->output_path;
        }

        return null;
    }

    /**
     * Cleanup job files
     */
    public function cleanupJob(int $jobId): bool
    {
        $jobDir = $this->getJobDirectory($jobId);

        if (is_dir($jobDir)) {
            $files = glob($jobDir . '/*');

            foreach ($files as $file) {
                @unlink($file);
            }

            @rmdir($jobDir);
        }

        return true;
    }
}
