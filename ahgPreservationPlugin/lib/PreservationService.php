<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Preservation Service
 *
 * Core service for digital preservation operations including:
 * - Checksum generation and verification
 * - Fixity checking
 * - PREMIS event logging
 * - Format identification
 */
class PreservationService
{
    private string $uploadsPath;

    public function __construct()
    {
        $this->uploadsPath = sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads');
    }

    // =========================================
    // CHECKSUM OPERATIONS
    // =========================================

    /**
     * Generate checksums for a digital object
     *
     * @param int   $digitalObjectId
     * @param array $algorithms      Algorithms to use (md5, sha1, sha256, sha512)
     *
     * @return array Generated checksums
     */
    public function generateChecksums(int $digitalObjectId, array $algorithms = ['sha256']): array
    {
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            throw new Exception("Digital object not found: {$digitalObjectId}");
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $fileSize = filesize($filePath);
        $results = [];
        $now = date('Y-m-d H:i:s');

        foreach ($algorithms as $algo) {
            $algo = strtolower($algo);
            if (!in_array($algo, ['md5', 'sha1', 'sha256', 'sha512'])) {
                continue;
            }

            $hashValue = hash_file($algo, $filePath);

            // Upsert checksum record
            $existing = DB::table('preservation_checksum')
                ->where('digital_object_id', $digitalObjectId)
                ->where('algorithm', $algo)
                ->first();

            if ($existing) {
                DB::table('preservation_checksum')
                    ->where('id', $existing->id)
                    ->update([
                        'checksum_value' => $hashValue,
                        'file_size' => $fileSize,
                        'generated_at' => $now,
                        'verification_status' => 'valid',
                        'verified_at' => $now,
                    ]);
                $checksumId = $existing->id;
            } else {
                $checksumId = DB::table('preservation_checksum')->insertGetId([
                    'digital_object_id' => $digitalObjectId,
                    'algorithm' => $algo,
                    'checksum_value' => $hashValue,
                    'file_size' => $fileSize,
                    'generated_at' => $now,
                    'verification_status' => 'valid',
                    'verified_at' => $now,
                    'created_at' => $now,
                ]);
            }

            $results[$algo] = [
                'id' => $checksumId,
                'value' => $hashValue,
                'file_size' => $fileSize,
            ];
        }

        // Log the event
        $this->logEvent(
            $digitalObjectId,
            null,
            'fixity_check',
            'Checksums generated: ' . implode(', ', $algorithms),
            'success'
        );

        return $results;
    }

    /**
     * Get checksums for a digital object
     */
    public function getChecksums(int $digitalObjectId): array
    {
        return DB::table('preservation_checksum')
            ->where('digital_object_id', $digitalObjectId)
            ->get()
            ->toArray();
    }

    // =========================================
    // FIXITY VERIFICATION
    // =========================================

    /**
     * Verify fixity for a digital object
     *
     * @param int         $digitalObjectId
     * @param string|null $algorithm       Specific algorithm to verify (null = all)
     * @param string      $checkedBy       User or system identifier
     *
     * @return array Verification results
     */
    public function verifyFixity(int $digitalObjectId, ?string $algorithm = null, string $checkedBy = 'system'): array
    {
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            throw new Exception("Digital object not found: {$digitalObjectId}");
        }

        $filePath = $this->getFilePath($digitalObject);
        $results = [];
        $now = date('Y-m-d H:i:s');

        // Get stored checksums
        $query = DB::table('preservation_checksum')
            ->where('digital_object_id', $digitalObjectId);

        if ($algorithm) {
            $query->where('algorithm', strtolower($algorithm));
        }

        $checksums = $query->get();

        if ($checksums->isEmpty()) {
            // No checksums to verify - generate them first
            return $this->generateChecksums($digitalObjectId, $algorithm ? [$algorithm] : ['sha256']);
        }

        foreach ($checksums as $checksum) {
            $startTime = microtime(true);
            $status = 'pass';
            $actualValue = null;
            $errorMessage = null;

            if (!file_exists($filePath)) {
                $status = 'missing';
                $errorMessage = 'File not found';
            } else {
                try {
                    $actualValue = hash_file($checksum->algorithm, $filePath);
                    if ($actualValue !== $checksum->checksum_value) {
                        $status = 'fail';
                        $errorMessage = 'Checksum mismatch';
                    }
                } catch (Exception $e) {
                    $status = 'error';
                    $errorMessage = $e->getMessage();
                }
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log the fixity check
            DB::table('preservation_fixity_check')->insert([
                'digital_object_id' => $digitalObjectId,
                'checksum_id' => $checksum->id,
                'algorithm' => $checksum->algorithm,
                'expected_value' => $checksum->checksum_value,
                'actual_value' => $actualValue,
                'status' => $status,
                'error_message' => $errorMessage,
                'checked_at' => $now,
                'checked_by' => $checkedBy,
                'duration_ms' => $durationMs,
                'created_at' => $now,
            ]);

            // Update checksum verification status
            $verificationStatus = $status === 'pass' ? 'valid' : ($status === 'fail' ? 'invalid' : 'error');
            DB::table('preservation_checksum')
                ->where('id', $checksum->id)
                ->update([
                    'verified_at' => $now,
                    'verification_status' => $verificationStatus,
                ]);

            $results[$checksum->algorithm] = [
                'status' => $status,
                'expected' => $checksum->checksum_value,
                'actual' => $actualValue,
                'duration_ms' => $durationMs,
                'error' => $errorMessage,
            ];
        }

        // Log preservation event
        $overallStatus = collect($results)->every(fn ($r) => $r['status'] === 'pass') ? 'success' : 'failure';
        $this->logEvent(
            $digitalObjectId,
            null,
            'fixity_check',
            'Fixity verification completed',
            $overallStatus,
            json_encode($results)
        );

        return $results;
    }

    /**
     * Run batch fixity verification
     *
     * @param int    $limit     Max objects to check
     * @param int    $minAge    Min days since last check
     * @param string $checkedBy Identifier for the checker
     *
     * @return array Summary of results
     */
    public function runBatchFixityCheck(int $limit = 100, int $minAge = 7, string $checkedBy = 'cron'): array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$minAge} days"));

        // Find objects needing verification
        $objects = DB::table('preservation_checksum as pc')
            ->join('digital_object as do', 'pc.digital_object_id', '=', 'do.id')
            ->where(function ($q) use ($cutoffDate) {
                $q->whereNull('pc.verified_at')
                  ->orWhere('pc.verified_at', '<', $cutoffDate);
            })
            ->select('pc.digital_object_id')
            ->distinct()
            ->limit($limit)
            ->get();

        $summary = [
            'total' => $objects->count(),
            'passed' => 0,
            'failed' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($objects as $obj) {
            try {
                $results = $this->verifyFixity($obj->digital_object_id, null, $checkedBy);
                $allPassed = collect($results)->every(fn ($r) => $r['status'] === 'pass');

                if ($allPassed) {
                    $summary['passed']++;
                } else {
                    $summary['failed']++;
                    $summary['details'][$obj->digital_object_id] = $results;
                }
            } catch (Exception $e) {
                $summary['errors']++;
                $summary['details'][$obj->digital_object_id] = ['error' => $e->getMessage()];
            }
        }

        return $summary;
    }

    // =========================================
    // FORMAT IDENTIFICATION
    // =========================================

    /**
     * Identify file format for a digital object
     * Uses Siegfried if available, falls back to PHP finfo
     *
     * @param int  $digitalObjectId
     * @param bool $updateRegistry   Also update preservation_format table if new format found
     *
     * @return array|null Identification results
     */
    public function identifyFormatBasic(int $digitalObjectId): ?array
    {
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            return null;
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!file_exists($filePath)) {
            return null;
        }

        // Use PHP's finfo for basic identification
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        // Try to get more details
        $formatName = $this->getMimeTypeName($mimeType);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Look up in format registry
        $format = DB::table('preservation_format')
            ->where('mime_type', $mimeType)
            ->first();

        $now = date('Y-m-d H:i:s');

        // Store identification
        DB::table('preservation_object_format')
            ->updateOrInsert(
                ['digital_object_id' => $digitalObjectId],
                [
                    'format_id' => $format->id ?? null,
                    'mime_type' => $mimeType,
                    'format_name' => $format->format_name ?? $formatName,
                    'format_version' => $format->format_version ?? null,
                    'identification_tool' => 'finfo',
                    'identification_date' => $now,
                    'confidence' => $format ? 'high' : 'medium',
                    'created_at' => $now,
                ]
            );

        // Log event
        $this->logEvent(
            $digitalObjectId,
            null,
            'format_identification',
            "Format identified: {$mimeType}",
            'success'
        );

        return [
            'success' => true,
            'mime_type' => $mimeType,
            'format_name' => $format->format_name ?? $formatName,
            'format_version' => $format->format_version ?? null,
            'risk_level' => $format->risk_level ?? 'unknown',
            'is_preservation_format' => $format->is_preservation_format ?? false,
        ];
    }

    // =========================================
    // PREMIS EVENT LOGGING
    // =========================================

    /**
     * Log a preservation event
     */
    public function logEvent(
        ?int $digitalObjectId,
        ?int $informationObjectId,
        string $eventType,
        string $detail,
        string $outcome = 'success',
        ?string $outcomeDetail = null,
        string $agentType = 'system',
        ?string $agentValue = null
    ): int {
        return DB::table('preservation_event')->insertGetId([
            'digital_object_id' => $digitalObjectId,
            'information_object_id' => $informationObjectId,
            'event_type' => $eventType,
            'event_datetime' => date('Y-m-d H:i:s'),
            'event_detail' => $detail,
            'event_outcome' => $outcome,
            'event_outcome_detail' => $outcomeDetail,
            'linking_agent_type' => $agentType,
            'linking_agent_value' => $agentValue ?? php_uname('n'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get events for a digital object
     */
    public function getEvents(int $digitalObjectId, int $limit = 50): array
    {
        return DB::table('preservation_event')
            ->where('digital_object_id', $digitalObjectId)
            ->orderBy('event_datetime', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================
    // STATISTICS & REPORTING
    // =========================================

    /**
     * Get preservation dashboard statistics
     */
    public function getStatistics(): array
    {
        $totalObjects = DB::table('digital_object')->count();
        $totalSize = DB::table('digital_object')->sum('byte_size') ?: 0;

        $withChecksums = DB::table('preservation_checksum')
            ->distinct('digital_object_id')
            ->count('digital_object_id');

        $recentChecks = DB::table('preservation_fixity_check')
            ->where('checked_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->count();

        $failedChecks = DB::table('preservation_fixity_check')
            ->where('checked_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->where('status', 'fail')
            ->count();

        $pendingVerification = DB::table('preservation_checksum')
            ->where(function ($q) {
                $q->whereNull('verified_at')
                  ->orWhere('verified_at', '<', date('Y-m-d', strtotime('-7 days')));
            })
            ->count();

        $formatRisk = DB::table('preservation_object_format as pof')
            ->join('preservation_format as pf', 'pof.format_id', '=', 'pf.id')
            ->whereIn('pf.risk_level', ['high', 'critical'])
            ->count();

        $recentEvents = DB::table('preservation_event')
            ->where('event_datetime', '>=', date('Y-m-d', strtotime('-30 days')))
            ->count();

        return [
            'total_objects' => $totalObjects,
            'total_size_bytes' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'objects_with_checksum' => $withChecksums,
            'checksum_coverage' => $totalObjects > 0 ? round(($withChecksums / $totalObjects) * 100, 1) : 0,
            'fixity_checks_30d' => $recentChecks,
            'fixity_failures_30d' => $failedChecks,
            'pending_verification' => $pendingVerification,
            'formats_at_risk' => $formatRisk,
            'events_30d' => $recentEvents,
        ];
    }

    /**
     * Get fixity check history
     */
    public function getFixityLog(int $limit = 100, ?string $status = null): array
    {
        $query = DB::table('preservation_fixity_check as fc')
            ->leftJoin('digital_object as do', 'fc.digital_object_id', '=', 'do.id')
            ->leftJoin('information_object as io', 'do.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as io_i18n', function ($join) {
                $join->on('io.id', '=', 'io_i18n.id')
                     ->where('io_i18n.culture', '=', 'en');
            })
            ->select(
                'fc.*',
                'do.name as filename',
                'io_i18n.title as object_title'
            )
            ->orderBy('fc.checked_at', 'desc')
            ->limit($limit);

        if ($status) {
            $query->where('fc.status', $status);
        }

        return $query->get()->toArray();
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function getDigitalObject(int $id): ?object
    {
        return DB::table('digital_object')->where('id', $id)->first();
    }

    private function getFilePath(object $digitalObject): string
    {
        $path = $digitalObject->path ?? '';
        $name = $digitalObject->name ?? '';

        // Combine path and name (AtoM stores directory in path, filename in name)
        $fullPath = rtrim($path, '/') . '/' . $name;

        // Handle relative paths
        if (!str_starts_with($fullPath, '/')) {
            $fullPath = sfConfig::get('sf_root_dir') . '/' . ltrim($fullPath, '/');
        } else {
            // Path starts with / but is relative to sf_root_dir
            $fullPath = sfConfig::get('sf_root_dir') . $fullPath;
        }

        return $fullPath;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function getMimeTypeName(string $mimeType): string
    {
        $names = [
            'image/jpeg' => 'JPEG Image',
            'image/png' => 'PNG Image',
            'image/tiff' => 'TIFF Image',
            'image/gif' => 'GIF Image',
            'application/pdf' => 'PDF Document',
            'text/plain' => 'Plain Text',
            'text/xml' => 'XML Document',
            'application/xml' => 'XML Document',
            'audio/mpeg' => 'MP3 Audio',
            'audio/wav' => 'WAV Audio',
            'audio/x-wav' => 'WAV Audio',
            'video/mp4' => 'MP4 Video',
            'application/zip' => 'ZIP Archive',
        ];

        return $names[$mimeType] ?? $mimeType;
    }

    // =========================================
    // VIRUS SCANNING (ClamAV)
    // =========================================

    /**
     * Check if ClamAV is available
     */
    public function isClamAvAvailable(): bool
    {
        $clamscan = trim(shell_exec('which clamscan 2>/dev/null') ?? '');
        $clamdscan = trim(shell_exec('which clamdscan 2>/dev/null') ?? '');

        return !empty($clamscan) || !empty($clamdscan);
    }

    /**
     * Get ClamAV version info
     */
    public function getClamAvVersion(): ?array
    {
        if (!$this->isClamAvAvailable()) {
            return null;
        }

        $output = shell_exec('clamscan --version 2>/dev/null') ?? '';
        preg_match('/ClamAV\s+([\d.]+)/', $output, $matches);
        $engineVersion = $matches[1] ?? 'unknown';

        // Get signature database info
        $sigOutput = shell_exec('sigtool --info /var/lib/clamav/daily.cvd 2>/dev/null') ?? '';
        preg_match('/Version:\s+(\d+)/', $sigOutput, $sigMatches);
        $sigVersion = $sigMatches[1] ?? 'unknown';

        return [
            'engine_version' => $engineVersion,
            'signature_version' => $sigVersion,
            'available' => true,
        ];
    }

    /**
     * Scan a digital object for viruses
     *
     * @param int    $digitalObjectId
     * @param bool   $quarantine      Move infected files to quarantine
     * @param string $scannedBy       User or system identifier
     *
     * @return array Scan results
     */
    public function scanForVirus(int $digitalObjectId, bool $quarantine = true, string $scannedBy = 'system'): array
    {
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            throw new Exception("Digital object not found: {$digitalObjectId}");
        }

        $filePath = $this->getFilePath($digitalObject);
        $now = date('Y-m-d H:i:s');

        // Check if ClamAV is available
        if (!$this->isClamAvAvailable()) {
            // Log as skipped
            DB::table('preservation_virus_scan')->insert([
                'digital_object_id' => $digitalObjectId,
                'scan_engine' => 'clamav',
                'status' => 'skipped',
                'file_path' => $filePath,
                'scanned_at' => $now,
                'scanned_by' => $scannedBy,
                'error_message' => 'ClamAV not installed. Install with: sudo apt install clamav clamav-daemon',
                'created_at' => $now,
            ]);

            return [
                'status' => 'skipped',
                'message' => 'ClamAV not installed',
                'install_command' => 'sudo apt install clamav clamav-daemon && sudo freshclam',
            ];
        }

        if (!file_exists($filePath)) {
            return [
                'status' => 'error',
                'message' => 'File not found',
            ];
        }

        $fileSize = filesize($filePath);
        $versionInfo = $this->getClamAvVersion();
        $startTime = microtime(true);

        // Use clamdscan if daemon is running, otherwise clamscan
        $useDaemon = !empty(trim(shell_exec('pgrep clamd 2>/dev/null') ?? ''));
        $scanCmd = $useDaemon ? 'clamdscan' : 'clamscan';

        // Run scan
        $escapedPath = escapeshellarg($filePath);
        $output = [];
        $returnCode = 0;
        exec("{$scanCmd} --no-summary {$escapedPath} 2>&1", $output, $returnCode);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        $outputStr = implode("\n", $output);

        // Parse results
        // Return codes: 0 = clean, 1 = virus found, 2 = error
        $status = 'clean';
        $threatName = null;
        $quarantined = false;
        $quarantinePath = null;

        if ($returnCode === 1) {
            $status = 'infected';
            // Extract threat name from output
            if (preg_match('/:\s*(.+)\s+FOUND/', $outputStr, $matches)) {
                $threatName = trim($matches[1]);
            }

            // Quarantine if requested
            if ($quarantine) {
                $quarantineDir = sfConfig::get('sf_upload_dir') . '/quarantine';
                if (!is_dir($quarantineDir)) {
                    mkdir($quarantineDir, 0750, true);
                }
                $quarantinePath = $quarantineDir . '/' . basename($filePath) . '.' . time() . '.quarantine';
                if (rename($filePath, $quarantinePath)) {
                    $quarantined = true;
                }
            }
        } elseif ($returnCode === 2) {
            $status = 'error';
        }

        // Log scan result
        DB::table('preservation_virus_scan')->insert([
            'digital_object_id' => $digitalObjectId,
            'scan_engine' => 'clamav',
            'engine_version' => $versionInfo['engine_version'] ?? null,
            'signature_version' => $versionInfo['signature_version'] ?? null,
            'status' => $status,
            'threat_name' => $threatName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'scanned_at' => $now,
            'scanned_by' => $scannedBy,
            'duration_ms' => $durationMs,
            'error_message' => $status === 'error' ? $outputStr : null,
            'quarantined' => $quarantined ? 1 : 0,
            'quarantine_path' => $quarantinePath,
            'created_at' => $now,
        ]);

        // Log PREMIS event
        $this->logEvent(
            $digitalObjectId,
            null,
            'virus_check',
            "Virus scan completed: {$status}" . ($threatName ? " ({$threatName})" : ''),
            $status === 'clean' ? 'success' : 'failure',
            $outputStr
        );

        return [
            'status' => $status,
            'threat_name' => $threatName,
            'quarantined' => $quarantined,
            'quarantine_path' => $quarantinePath,
            'duration_ms' => $durationMs,
            'engine_version' => $versionInfo['engine_version'] ?? null,
        ];
    }

    /**
     * Batch virus scan for multiple objects
     */
    public function runBatchVirusScan(int $limit = 100, bool $newOnly = true, string $scannedBy = 'cron'): array
    {
        $query = DB::table('digital_object as do')
            ->select('do.id');

        if ($newOnly) {
            // Only scan objects not previously scanned
            $query->leftJoin('preservation_virus_scan as vs', 'do.id', '=', 'vs.digital_object_id')
                  ->whereNull('vs.id');
        }

        $objects = $query->limit($limit)->get();

        $summary = [
            'total' => $objects->count(),
            'clean' => 0,
            'infected' => 0,
            'errors' => 0,
            'skipped' => 0,
            'threats' => [],
        ];

        foreach ($objects as $obj) {
            try {
                $result = $this->scanForVirus($obj->id, true, $scannedBy);
                $summary[$result['status']]++;
                if ($result['status'] === 'infected' && $result['threat_name']) {
                    $summary['threats'][$obj->id] = $result['threat_name'];
                }
            } catch (Exception $e) {
                $summary['errors']++;
            }
        }

        return $summary;
    }

    /**
     * Get virus scan history
     */
    public function getVirusScanLog(int $limit = 100, ?string $status = null): array
    {
        $query = DB::table('preservation_virus_scan as vs')
            ->leftJoin('digital_object as do', 'vs.digital_object_id', '=', 'do.id')
            ->select('vs.*', 'do.name as filename')
            ->orderBy('vs.scanned_at', 'desc')
            ->limit($limit);

        if ($status) {
            $query->where('vs.status', $status);
        }

        return $query->get()->toArray();
    }

    // =========================================
    // FORMAT CONVERSION (ImageMagick/FFmpeg)
    // =========================================

    /**
     * Get available conversion tools
     */
    public function getConversionTools(): array
    {
        $tools = [];

        // ImageMagick
        $convert = trim(shell_exec('which convert 2>/dev/null') ?? '');
        if ($convert) {
            $version = shell_exec('convert --version 2>/dev/null | head -1') ?? '';
            preg_match('/ImageMagick\s+([\d.-]+)/', $version, $m);
            $tools['imagemagick'] = [
                'available' => true,
                'path' => $convert,
                'version' => $m[1] ?? 'unknown',
                'formats' => ['image/*'],
            ];
        }

        // FFmpeg
        $ffmpeg = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');
        if ($ffmpeg) {
            $version = shell_exec('ffmpeg -version 2>/dev/null | head -1') ?? '';
            preg_match('/ffmpeg version\s+([\d.]+)/', $version, $m);
            $tools['ffmpeg'] = [
                'available' => true,
                'path' => $ffmpeg,
                'version' => $m[1] ?? 'unknown',
                'formats' => ['video/*', 'audio/*'],
            ];
        }

        // Ghostscript
        $gs = trim(shell_exec('which gs 2>/dev/null') ?? '');
        if ($gs) {
            $version = shell_exec('gs --version 2>/dev/null') ?? '';
            $tools['ghostscript'] = [
                'available' => true,
                'path' => $gs,
                'version' => trim($version),
                'formats' => ['application/pdf', 'application/postscript'],
            ];
        }

        // LibreOffice (for document conversion)
        $libreoffice = trim(shell_exec('which libreoffice 2>/dev/null') ?? '');
        if ($libreoffice) {
            $tools['libreoffice'] = [
                'available' => true,
                'path' => $libreoffice,
                'version' => 'installed',
                'formats' => ['application/msword', 'application/vnd.ms-*', 'application/vnd.openxmlformats-*'],
            ];
        }

        return $tools;
    }

    /**
     * Convert a digital object to a different format
     *
     * @param int         $digitalObjectId
     * @param string      $targetFormat     Target format (tiff, pdf, mp4, etc.)
     * @param array       $options          Conversion options
     * @param string      $createdBy        User identifier
     *
     * @return array Conversion result
     */
    public function convertFormat(int $digitalObjectId, string $targetFormat, array $options = [], string $createdBy = 'system'): array
    {
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            throw new Exception("Digital object not found: {$digitalObjectId}");
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $now = date('Y-m-d H:i:s');
        $sourceSize = filesize($filePath);
        $sourceMime = mime_content_type($filePath);
        $sourceFormat = pathinfo($filePath, PATHINFO_EXTENSION);
        $sourceChecksum = hash_file('sha256', $filePath);

        // Determine conversion tool
        $tool = $this->selectConversionTool($sourceMime, $targetFormat);
        if (!$tool) {
            throw new Exception("No conversion tool available for {$sourceMime} to {$targetFormat}");
        }

        // Create conversion record
        $conversionId = DB::table('preservation_format_conversion')->insertGetId([
            'digital_object_id' => $digitalObjectId,
            'source_format' => $sourceFormat,
            'source_mime_type' => $sourceMime,
            'target_format' => $targetFormat,
            'target_mime_type' => $this->getMimeTypeForFormat($targetFormat),
            'conversion_tool' => $tool['name'],
            'tool_version' => $tool['version'],
            'status' => 'processing',
            'source_path' => $filePath,
            'source_size' => $sourceSize,
            'source_checksum' => $sourceChecksum,
            'conversion_options' => json_encode($options),
            'started_at' => $now,
            'created_by' => $createdBy,
            'created_at' => $now,
        ]);

        $startTime = microtime(true);

        // Generate output path (include object ID for uniqueness)
        $outputDir = sfConfig::get('sf_upload_dir') . '/conversions';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        $outputPath = $outputDir . '/' . $baseName . '_' . $digitalObjectId . '.' . $targetFormat;

        // Run conversion
        try {
            $result = $this->executeConversion($tool['name'], $filePath, $outputPath, $targetFormat, $options);

            // LibreOffice uses its own output naming convention (original filename.targetFormat)
            $actualOutputPath = $outputPath;
            if ($tool['name'] === 'libreoffice' && !file_exists($outputPath)) {
                // LibreOffice outputs to: {outputDir}/{original_filename}.{targetFormat}
                $loOutputPath = dirname($outputPath) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $targetFormat;
                if (file_exists($loOutputPath)) {
                    // Rename to our expected path with object ID
                    rename($loOutputPath, $outputPath);
                    $actualOutputPath = $outputPath;
                }
            }

            if ($result['success'] && file_exists($actualOutputPath)) {
                $outputSize = filesize($actualOutputPath);
                $outputChecksum = hash_file('sha256', $actualOutputPath);
                $durationMs = (int) ((microtime(true) - $startTime) * 1000);

                // Update record
                DB::table('preservation_format_conversion')
                    ->where('id', $conversionId)
                    ->update([
                        'status' => 'completed',
                        'output_path' => $actualOutputPath,
                        'output_size' => $outputSize,
                        'output_checksum' => $outputChecksum,
                        'completed_at' => date('Y-m-d H:i:s'),
                        'duration_ms' => $durationMs,
                    ]);

                // Log PREMIS event
                $this->logEvent(
                    $digitalObjectId,
                    null,
                    'normalization',
                    "Format converted from {$sourceFormat} to {$targetFormat}",
                    'success',
                    json_encode(['tool' => $tool['name'], 'output' => $actualOutputPath])
                );

                return [
                    'success' => true,
                    'conversion_id' => $conversionId,
                    'output_path' => $actualOutputPath,
                    'output_size' => $outputSize,
                    'duration_ms' => $durationMs,
                ];
            } else {
                // Provide more descriptive error message
                $errorMsg = $result['error'] ?? $result['output'] ?? '';
                if (empty($errorMsg)) {
                    $errorMsg = "Conversion command completed but output file not found: {$outputPath}";
                }
                throw new Exception($errorMsg);
            }
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Sanitize error message - remove non-UTF8 characters
            $errorMsg = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $e->getMessage());
            $errorMsg = mb_substr($errorMsg, 0, 1000); // Limit length

            DB::table('preservation_format_conversion')
                ->where('id', $conversionId)
                ->update([
                    'status' => 'failed',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'duration_ms' => $durationMs,
                    'error_message' => $errorMsg,
                ]);

            $this->logEvent(
                $digitalObjectId,
                null,
                'normalization',
                "Format conversion failed: {$e->getMessage()}",
                'failure'
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Select appropriate conversion tool
     */
    private function selectConversionTool(string $sourceMime, string $targetFormat): ?array
    {
        $tools = $this->getConversionTools();

        // Office document conversions FIRST (Word, Excel, PowerPoint → PDF)
        // Must check before generic PDF operations since target is often PDF
        if (str_contains($sourceMime, 'msword')
            || str_contains($sourceMime, 'officedocument')
            || str_contains($sourceMime, 'ms-excel')
            || str_contains($sourceMime, 'ms-powerpoint')
            || str_contains($sourceMime, 'spreadsheet')
            || str_contains($sourceMime, 'presentation')) {
            if (isset($tools['libreoffice']) && $tools['libreoffice']['available']) {
                return ['name' => 'libreoffice', 'version' => $tools['libreoffice']['version']];
            }
        }

        // Image conversions
        if (str_starts_with($sourceMime, 'image/') && in_array($targetFormat, ['tiff', 'tif', 'png', 'jpg', 'jpeg', 'pdf'])) {
            if (isset($tools['imagemagick'])) {
                return ['name' => 'imagemagick', 'version' => $tools['imagemagick']['version']];
            }
        }

        // Video/audio conversions
        if ((str_starts_with($sourceMime, 'video/') || str_starts_with($sourceMime, 'audio/'))
            && in_array($targetFormat, ['mp4', 'mkv', 'webm', 'mp3', 'wav', 'flac'])) {
            if (isset($tools['ffmpeg'])) {
                return ['name' => 'ffmpeg', 'version' => $tools['ffmpeg']['version']];
            }
        }

        // PDF operations (PDF → PDF/A or image → PDF)
        if ($sourceMime === 'application/pdf' || $targetFormat === 'pdf') {
            if (isset($tools['ghostscript'])) {
                return ['name' => 'ghostscript', 'version' => $tools['ghostscript']['version']];
            }
            if (isset($tools['imagemagick'])) {
                return ['name' => 'imagemagick', 'version' => $tools['imagemagick']['version']];
            }
        }

        return null;
    }

    /**
     * Execute the actual conversion
     */
    private function executeConversion(string $tool, string $input, string $output, string $targetFormat, array $options): array
    {
        $escapedInput = escapeshellarg($input);
        $escapedOutput = escapeshellarg($output);

        switch ($tool) {
            case 'imagemagick':
                $quality = $options['quality'] ?? 95;
                $compress = $options['compress'] ?? 'lzw';
                $cmd = "convert {$escapedInput} -quality {$quality} -compress {$compress} {$escapedOutput} 2>&1";
                break;

            case 'ffmpeg':
                $preset = $options['preset'] ?? 'medium';
                $crf = $options['crf'] ?? 23;
                if (in_array($targetFormat, ['mp3', 'wav', 'flac'])) {
                    $cmd = "ffmpeg -i {$escapedInput} -y {$escapedOutput} 2>&1";
                } else {
                    $cmd = "ffmpeg -i {$escapedInput} -preset {$preset} -crf {$crf} -y {$escapedOutput} 2>&1";
                }
                break;

            case 'ghostscript':
                $pdfSettings = $options['pdf_settings'] ?? '/ebook';
                $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS={$pdfSettings} -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$escapedOutput} {$escapedInput} 2>&1";
                break;

            case 'libreoffice':
                $outputDir = dirname($output);
                $cmd = "libreoffice --headless --convert-to {$targetFormat} --outdir {$outputDir} {$escapedInput} 2>&1";
                break;

            default:
                return ['success' => false, 'error' => "Unknown tool: {$tool}"];
        }

        $cmdOutput = [];
        $returnCode = 0;
        exec($cmd, $cmdOutput, $returnCode);

        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $cmdOutput),
            'error' => $returnCode !== 0 ? implode("\n", $cmdOutput) : null,
        ];
    }

    /**
     * Get MIME type for a format extension
     */
    private function getMimeTypeForFormat(string $format): string
    {
        $mimes = [
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/x-wav',
            'flac' => 'audio/flac',
        ];

        return $mimes[$format] ?? 'application/octet-stream';
    }

    /**
     * Get conversion history
     */
    public function getConversionLog(int $limit = 100, ?string $status = null): array
    {
        $query = DB::table('preservation_format_conversion as fc')
            ->leftJoin('digital_object as do', 'fc.digital_object_id', '=', 'do.id')
            ->select('fc.*', 'do.name as filename')
            ->orderBy('fc.created_at', 'desc')
            ->limit($limit);

        if ($status) {
            $query->where('fc.status', $status);
        }

        return $query->get()->toArray();
    }

    // =========================================
    // BACKUP VERIFICATION
    // =========================================

    /**
     * Verify backup integrity
     *
     * @param string      $backupPath    Path to backup file or directory
     * @param string      $backupType    Type: database, files, full
     * @param string|null $expectedChecksum Expected checksum if known
     * @param string      $verifiedBy    User identifier
     *
     * @return array Verification results
     */
    public function verifyBackup(string $backupPath, string $backupType = 'full', ?string $expectedChecksum = null, string $verifiedBy = 'system'): array
    {
        $now = date('Y-m-d H:i:s');
        $startTime = microtime(true);

        if (!file_exists($backupPath)) {
            return [
                'status' => 'missing',
                'message' => 'Backup file not found',
            ];
        }

        $backupSize = is_file($backupPath) ? filesize($backupPath) : $this->getDirectorySize($backupPath);
        $status = 'valid';
        $errorMessage = null;
        $filesChecked = 0;
        $filesValid = 0;
        $filesInvalid = 0;
        $filesMissing = 0;
        $details = [];

        try {
            if (is_file($backupPath)) {
                // Single file verification
                $actualChecksum = hash_file('sha256', $backupPath);
                $filesChecked = 1;

                if ($expectedChecksum && $actualChecksum !== $expectedChecksum) {
                    $status = 'invalid';
                    $filesInvalid = 1;
                    $errorMessage = 'Checksum mismatch';
                    $details['expected'] = $expectedChecksum;
                    $details['actual'] = $actualChecksum;
                } else {
                    $filesValid = 1;
                    $details['checksum'] = $actualChecksum;
                }

                // Additional checks for compressed files
                if (preg_match('/\.(tar\.gz|tgz|zip)$/', $backupPath)) {
                    $integrityResult = $this->verifyArchiveIntegrity($backupPath);
                    if (!$integrityResult['valid']) {
                        $status = 'corrupted';
                        $errorMessage = $integrityResult['error'];
                    }
                }
            } else {
                // Directory verification - sample files
                $files = $this->getFilesInDirectory($backupPath, 100);
                $filesChecked = count($files);

                foreach ($files as $file) {
                    if (!file_exists($file)) {
                        $filesMissing++;
                        continue;
                    }

                    // Verify file is readable and not corrupted
                    if (is_readable($file) && filesize($file) > 0) {
                        $filesValid++;
                    } else {
                        $filesInvalid++;
                    }
                }

                if ($filesInvalid > 0 || $filesMissing > 0) {
                    $status = ($filesInvalid > $filesValid) ? 'corrupted' : 'warning';
                }

                $details['files_sampled'] = $filesChecked;
            }
        } catch (Exception $e) {
            $status = 'error';
            $errorMessage = $e->getMessage();
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // Log verification result
        DB::table('preservation_backup_verification')->insert([
            'backup_type' => $backupType,
            'backup_path' => $backupPath,
            'backup_size' => $backupSize,
            'original_checksum' => $expectedChecksum,
            'verified_checksum' => $details['checksum'] ?? ($details['actual'] ?? null),
            'status' => $status,
            'verification_method' => 'sha256',
            'files_checked' => $filesChecked,
            'files_valid' => $filesValid,
            'files_invalid' => $filesInvalid,
            'files_missing' => $filesMissing,
            'verified_at' => $now,
            'verified_by' => $verifiedBy,
            'duration_ms' => $durationMs,
            'error_message' => $errorMessage,
            'details' => json_encode($details),
            'created_at' => $now,
        ]);

        return [
            'status' => $status,
            'backup_size' => $backupSize,
            'files_checked' => $filesChecked,
            'files_valid' => $filesValid,
            'files_invalid' => $filesInvalid,
            'files_missing' => $filesMissing,
            'duration_ms' => $durationMs,
            'error' => $errorMessage,
            'details' => $details,
        ];
    }

    /**
     * Verify all recent backups
     */
    public function verifyAllBackups(string $backupDir = null, string $verifiedBy = 'cron'): array
    {
        $backupDir = $backupDir ?? sfConfig::get('sf_upload_dir') . '/backups';

        if (!is_dir($backupDir)) {
            return ['error' => 'Backup directory not found'];
        }

        $summary = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'missing' => 0,
            'errors' => 0,
            'details' => [],
        ];

        // Find backup files (tar.gz, zip, sql.gz)
        $backups = glob($backupDir . '/*.{tar.gz,tgz,zip,sql.gz,sql}', GLOB_BRACE);

        foreach ($backups as $backup) {
            $summary['total']++;
            $backupType = str_contains($backup, '.sql') ? 'database' : 'files';

            $result = $this->verifyBackup($backup, $backupType, null, $verifiedBy);
            $summary[$result['status']]++;
            $summary['details'][basename($backup)] = $result['status'];
        }

        return $summary;
    }

    /**
     * Verify archive file integrity
     */
    private function verifyArchiveIntegrity(string $archivePath): array
    {
        $extension = pathinfo($archivePath, PATHINFO_EXTENSION);
        $escapedPath = escapeshellarg($archivePath);

        if ($extension === 'zip') {
            exec("unzip -t {$escapedPath} 2>&1", $output, $returnCode);
        } elseif (in_array($extension, ['gz', 'tgz']) || str_ends_with($archivePath, '.tar.gz')) {
            exec("gzip -t {$escapedPath} 2>&1", $output, $returnCode);
        } else {
            return ['valid' => true];
        }

        return [
            'valid' => $returnCode === 0,
            'error' => $returnCode !== 0 ? implode("\n", $output) : null,
        ];
    }

    /**
     * Get total size of a directory
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Get sample files from a directory
     */
    private function getFilesInDirectory(string $path, int $limit = 100): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
                if (count($files) >= $limit) {
                    break;
                }
            }
        }

        return $files;
    }

    /**
     * Get backup verification history
     */
    public function getBackupVerificationLog(int $limit = 100, ?string $status = null): array
    {
        $query = DB::table('preservation_backup_verification')
            ->orderBy('verified_at', 'desc')
            ->limit($limit);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->toArray();
    }

    /**
     * Get extended statistics including new features
     */
    public function getExtendedStatistics(): array
    {
        $base = $this->getStatistics();

        // Virus scan stats
        $virusScanStats = DB::table('preservation_virus_scan')
            ->selectRaw("
                COUNT(*) as total_scans,
                SUM(CASE WHEN status = 'clean' THEN 1 ELSE 0 END) as clean,
                SUM(CASE WHEN status = 'infected' THEN 1 ELSE 0 END) as infected,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            ")
            ->where('scanned_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->first();

        // Format conversion stats
        $conversionStats = DB::table('preservation_format_conversion')
            ->selectRaw("
                COUNT(*) as total_conversions,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            ")
            ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->first();

        // Backup verification stats
        $backupStats = DB::table('preservation_backup_verification')
            ->selectRaw("
                COUNT(*) as total_verified,
                SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid,
                SUM(CASE WHEN status IN ('invalid', 'corrupted') THEN 1 ELSE 0 END) as invalid
            ")
            ->where('verified_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->first();

        return array_merge($base, [
            'virus_scans_30d' => (int) ($virusScanStats->total_scans ?? 0),
            'virus_clean_30d' => (int) ($virusScanStats->clean ?? 0),
            'virus_infected_30d' => (int) ($virusScanStats->infected ?? 0),
            'conversions_30d' => (int) ($conversionStats->total_conversions ?? 0),
            'conversions_completed_30d' => (int) ($conversionStats->completed ?? 0),
            'conversions_failed_30d' => (int) ($conversionStats->failed ?? 0),
            'backups_verified_30d' => (int) ($backupStats->total_verified ?? 0),
            'backups_valid_30d' => (int) ($backupStats->valid ?? 0),
            'backups_invalid_30d' => (int) ($backupStats->invalid ?? 0),
            'clamav_available' => $this->isClamAvAvailable(),
            'conversion_tools' => array_keys($this->getConversionTools()),
            'siegfried_available' => $this->isSiegfriedAvailable(),
        ]);
    }

    // =========================================
    // FORMAT IDENTIFICATION (SIEGFRIED/PRONOM)
    // =========================================

    /**
     * Check if Siegfried is available
     */
    public function isSiegfriedAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('which sf 2>/dev/null', $output, $returnCode);

        return $returnCode === 0 && !empty($output);
    }

    /**
     * Get Siegfried version information
     */
    public function getSiegfriedVersion(): ?array
    {
        if (!$this->isSiegfriedAvailable()) {
            return null;
        }

        $output = [];
        exec('sf -version 2>&1', $output);
        $versionLine = $output[0] ?? '';

        // Parse: "siegfried 1.11.1"
        if (preg_match('/siegfried\s+([\d.]+)/', $versionLine, $matches)) {
            $version = $matches[1];
        } else {
            $version = 'unknown';
        }

        // Get signature info
        $sigInfo = $output[1] ?? '';
        $signatureDate = null;
        if (preg_match('/\((\d{4}-\d{2}-\d{2})/', $sigInfo, $matches)) {
            $signatureDate = $matches[1];
        }

        return [
            'version' => $version,
            'signature_date' => $signatureDate,
            'full_output' => implode("\n", $output),
        ];
    }

    /**
     * Identify format of a digital object using Siegfried
     *
     * @param int  $digitalObjectId
     * @param bool $updateRegistry   Also update preservation_format table if new format found
     *
     * @return array Identification results
     */
    public function identifyFormat(int $digitalObjectId, bool $updateRegistry = true): array
    {
        if (!$this->isSiegfriedAvailable()) {
            throw new Exception('Siegfried is not installed. Install with: apt install siegfried');
        }

        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            throw new Exception("Digital object not found: {$digitalObjectId}");
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $startTime = microtime(true);

        // Run Siegfried with JSON output
        $escapedPath = escapeshellarg($filePath);
        $output = [];
        $returnCode = 0;
        exec("sf -json {$escapedPath} 2>&1", $output, $returnCode);

        $jsonOutput = implode('', $output);
        $result = json_decode($jsonOutput, true);

        if (!$result || empty($result['files'])) {
            throw new Exception('Siegfried failed to identify file: ' . $jsonOutput);
        }

        $fileResult = $result['files'][0];
        $matches = $fileResult['matches'] ?? [];

        if (empty($matches)) {
            // No format identified
            return [
                'success' => false,
                'error' => 'No format identified',
                'file_path' => $filePath,
            ];
        }

        // Get the best match (first match is usually highest priority)
        $match = $matches[0];

        // Siegfried uses 'id' for PUID (PRONOM identifier)
        $puid = $match['id'] ?? null;
        $formatName = $match['format'] ?? 'Unknown';
        $formatVersion = $match['version'] ?? null;
        $mimeType = $match['mime'] ?? $digitalObject->mime_type;
        $basis = $match['basis'] ?? null;
        $warning = $match['warning'] ?? null;

        // Determine confidence based on basis
        $confidence = $this->determineConfidence($basis, $warning);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        $now = date('Y-m-d H:i:s');

        // Get Siegfried version info
        $sfVersion = $this->getSiegfriedVersion();
        $toolVersion = $sfVersion ? "siegfried {$sfVersion['version']}" : 'siegfried';

        // Find or create format in registry
        $formatId = null;
        if ($puid && $updateRegistry) {
            $formatId = $this->findOrCreateFormat($puid, $mimeType, $formatName, $formatVersion);
        }

        // Check for existing identification
        $existing = DB::table('preservation_object_format')
            ->where('digital_object_id', $digitalObjectId)
            ->first();

        $identificationData = [
            'digital_object_id' => $digitalObjectId,
            'format_id' => $formatId,
            'puid' => $puid,
            'mime_type' => $mimeType,
            'format_name' => $formatName,
            'format_version' => $formatVersion,
            'identification_tool' => $toolVersion,
            'identification_date' => $now,
            'confidence' => $confidence,
            'basis' => $basis,
            'warning' => $warning,
        ];

        if ($existing) {
            DB::table('preservation_object_format')
                ->where('id', $existing->id)
                ->update($identificationData);
            $identificationId = $existing->id;
        } else {
            $identificationData['created_at'] = $now;
            $identificationId = DB::table('preservation_object_format')->insertGetId($identificationData);
        }

        // Log PREMIS event
        $this->logEvent(
            $digitalObjectId,
            null,
            'format_identification',
            "Format identified as {$formatName}" . ($puid ? " ({$puid})" : ''),
            'success',
            json_encode([
                'tool' => $toolVersion,
                'puid' => $puid,
                'mime_type' => $mimeType,
                'confidence' => $confidence,
                'basis' => $basis,
            ])
        );

        return [
            'success' => true,
            'identification_id' => $identificationId,
            'puid' => $puid,
            'format_name' => $formatName,
            'format_version' => $formatVersion,
            'mime_type' => $mimeType,
            'confidence' => $confidence,
            'basis' => $basis,
            'warning' => $warning,
            'duration_ms' => $durationMs,
            'all_matches' => $matches,
        ];
    }

    /**
     * Determine confidence level based on identification basis
     */
    private function determineConfidence(?string $basis, ?string $warning): string
    {
        if ($warning && str_contains(strtolower($warning), 'extension mismatch')) {
            return 'low';
        }

        if (!$basis) {
            return 'medium';
        }

        $basis = strtolower($basis);

        if (str_contains($basis, 'container')) {
            return 'certain';
        }

        if (str_contains($basis, 'byte match') || str_contains($basis, 'signature')) {
            return 'high';
        }

        if (str_contains($basis, 'extension')) {
            return 'low';
        }

        return 'medium';
    }

    /**
     * Find or create a format entry in the registry
     */
    private function findOrCreateFormat(?string $puid, ?string $mimeType, string $formatName, ?string $formatVersion): ?int
    {
        // Normalize empty strings to null
        $puid = !empty($puid) && $puid !== 'UNKNOWN' ? $puid : null;
        $mimeType = !empty($mimeType) ? $mimeType : null;
        $formatVersion = !empty($formatVersion) ? $formatVersion : null;

        if (!$puid && !$mimeType) {
            return null;
        }

        // First try to find by PUID
        if ($puid) {
            $existing = DB::table('preservation_format')
                ->where('puid', $puid)
                ->first();

            if ($existing) {
                return $existing->id;
            }
        }

        // Then try by MIME type and version
        if ($mimeType) {
            $query = DB::table('preservation_format')
                ->where('mime_type', $mimeType);

            if ($formatVersion) {
                $query->where('format_version', $formatVersion);
            } else {
                $query->whereNull('format_version');
            }

            $existing = $query->first();

            if ($existing) {
                // Update PUID if we have one and it's missing
                if ($puid && !$existing->puid) {
                    DB::table('preservation_format')
                        ->where('id', $existing->id)
                        ->update(['puid' => $puid]);
                }

                return $existing->id;
            }

            // Try without version constraint as fallback
            $existing = DB::table('preservation_format')
                ->where('mime_type', $mimeType)
                ->first();

            if ($existing) {
                if ($puid && !$existing->puid) {
                    DB::table('preservation_format')
                        ->where('id', $existing->id)
                        ->update(['puid' => $puid]);
                }

                return $existing->id;
            }
        }

        // Don't create entries for formats without MIME type
        if (!$mimeType) {
            return null;
        }

        // Create new format entry
        $riskLevel = $this->assessFormatRisk($puid, $mimeType, $formatName);

        return DB::table('preservation_format')->insertGetId([
            'puid' => $puid,
            'mime_type' => $mimeType,
            'format_name' => $formatName,
            'format_version' => $formatVersion,
            'risk_level' => $riskLevel,
            'preservation_action' => $riskLevel === 'low' ? 'none' : 'monitor',
            'is_preservation_format' => in_array($mimeType, [
                'image/tiff', 'image/png', 'application/pdf',
                'audio/x-wav', 'audio/flac', 'text/plain', 'text/xml',
            ]) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Assess risk level for a format
     */
    private function assessFormatRisk(?string $puid, ?string $mimeType, string $formatName): string
    {
        // Low risk - standard preservation formats
        $lowRiskMimes = [
            'image/tiff', 'image/png', 'application/pdf',
            'audio/x-wav', 'audio/wav', 'audio/flac',
            'text/plain', 'text/xml', 'application/xml',
            'image/jpeg', 'audio/mpeg', 'video/mp4',
        ];

        if ($mimeType && in_array($mimeType, $lowRiskMimes)) {
            return 'low';
        }

        // High risk - proprietary or legacy formats
        $highRiskPatterns = [
            'msword', 'ms-excel', 'ms-powerpoint',
            'lotus', 'wordperfect', 'corel',
        ];

        $formatLower = strtolower($formatName);
        foreach ($highRiskPatterns as $pattern) {
            if (str_contains($formatLower, $pattern)) {
                return 'high';
            }
        }

        // Critical risk - obsolete formats
        $criticalPatterns = ['macpaint', 'pict', 'superpaint', 'clarisworks'];
        foreach ($criticalPatterns as $pattern) {
            if (str_contains($formatLower, $pattern)) {
                return 'critical';
            }
        }

        return 'medium';
    }

    /**
     * Batch identify formats for multiple objects
     *
     * @param int  $limit           Maximum objects to identify
     * @param bool $unidentifiedOnly Only identify objects without existing identification
     * @param bool $updateRegistry   Update format registry with new formats
     *
     * @return array Summary results
     */
    public function runBatchIdentification(int $limit = 100, bool $unidentifiedOnly = true, bool $updateRegistry = true): array
    {
        $query = DB::table('digital_object as do')
            ->where('do.usage_id', 140) // Masters only
            ->select('do.id');

        if ($unidentifiedOnly) {
            $query->leftJoin('preservation_object_format as pof', 'do.id', '=', 'pof.digital_object_id')
                ->whereNull('pof.id');
        }

        $objects = $query->limit($limit)->get();

        $results = [
            'total' => $objects->count(),
            'identified' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($objects as $obj) {
            try {
                $result = $this->identifyFormat($obj->id, $updateRegistry);
                if ($result['success']) {
                    ++$results['identified'];
                } else {
                    ++$results['failed'];
                    $results['errors'][] = "Object {$obj->id}: {$result['error']}";
                }
            } catch (Exception $e) {
                ++$results['failed'];
                $results['errors'][] = "Object {$obj->id}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Get format identification statistics
     */
    public function getIdentificationStatistics(): array
    {
        $totalObjects = DB::table('digital_object')
            ->where('usage_id', 140)
            ->count();

        $identified = DB::table('preservation_object_format')
            ->count();

        $byConfidence = DB::table('preservation_object_format')
            ->selectRaw('confidence, COUNT(*) as count')
            ->groupBy('confidence')
            ->pluck('count', 'confidence')
            ->toArray();

        $byTool = DB::table('preservation_object_format')
            ->selectRaw('identification_tool, COUNT(*) as count')
            ->groupBy('identification_tool')
            ->pluck('count', 'identification_tool')
            ->toArray();

        $withWarnings = DB::table('preservation_object_format')
            ->whereNotNull('warning')
            ->where('warning', '!=', '')
            ->count();

        $topFormats = DB::table('preservation_object_format')
            ->selectRaw('format_name, puid, COUNT(*) as count')
            ->groupBy('format_name', 'puid')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'total_objects' => $totalObjects,
            'identified' => $identified,
            'unidentified' => $totalObjects - $identified,
            'coverage_percent' => $totalObjects > 0 ? round(($identified / $totalObjects) * 100, 1) : 0,
            'by_confidence' => $byConfidence,
            'by_tool' => $byTool,
            'with_warnings' => $withWarnings,
            'top_formats' => $topFormats,
            'siegfried_available' => $this->isSiegfriedAvailable(),
            'siegfried_version' => $this->getSiegfriedVersion(),
        ];
    }

    /**
     * Get identification log/history
     */
    public function getIdentificationLog(int $limit = 100, ?string $confidence = null): array
    {
        $query = DB::table('preservation_object_format as pof')
            ->join('digital_object as do', 'pof.digital_object_id', '=', 'do.id')
            ->select(
                'pof.*',
                'do.name as object_name',
                'do.path as object_path'
            )
            ->orderByDesc('pof.identification_date')
            ->limit($limit);

        if ($confidence) {
            $query->where('pof.confidence', $confidence);
        }

        return $query->get()->toArray();
    }

    /**
     * Re-identify a single object (force new identification)
     */
    public function reidentifyFormat(int $digitalObjectId): array
    {
        // Delete existing identification
        DB::table('preservation_object_format')
            ->where('digital_object_id', $digitalObjectId)
            ->delete();

        // Run fresh identification
        return $this->identifyFormat($digitalObjectId, true);
    }

    // =========================================
    // WORKFLOW SCHEDULE MANAGEMENT
    // =========================================

    /**
     * Get all workflow schedules
     */
    public function getWorkflowSchedules(?string $workflowType = null, ?bool $enabledOnly = null): array
    {
        $query = DB::table('preservation_workflow_schedule')
            ->orderBy('workflow_type')
            ->orderBy('name');

        if ($workflowType) {
            $query->where('workflow_type', $workflowType);
        }

        if ($enabledOnly !== null) {
            $query->where('is_enabled', $enabledOnly ? 1 : 0);
        }

        return $query->get()->toArray();
    }

    /**
     * Get a single workflow schedule by ID
     */
    public function getWorkflowSchedule(int $id): ?object
    {
        return DB::table('preservation_workflow_schedule')
            ->where('id', $id)
            ->first();
    }

    /**
     * Create a new workflow schedule
     */
    public function createWorkflowSchedule(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $insertData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'workflow_type' => $data['workflow_type'],
            'is_enabled' => $data['is_enabled'] ?? 1,
            'schedule_type' => $data['schedule_type'] ?? 'cron',
            'cron_expression' => $data['cron_expression'] ?? null,
            'interval_hours' => $data['interval_hours'] ?? null,
            'batch_limit' => $data['batch_limit'] ?? 100,
            'timeout_minutes' => $data['timeout_minutes'] ?? 60,
            'options' => isset($data['options']) ? json_encode($data['options']) : null,
            'notify_on_failure' => $data['notify_on_failure'] ?? 1,
            'notify_email' => $data['notify_email'] ?? null,
            'created_by' => $data['created_by'] ?? 'system',
            'created_at' => $now,
        ];

        // Calculate next run time
        if ($insertData['is_enabled'] && $insertData['cron_expression']) {
            $insertData['next_run_at'] = $this->calculateNextRunTime($insertData['cron_expression']);
        }

        return DB::table('preservation_workflow_schedule')->insertGetId($insertData);
    }

    /**
     * Update a workflow schedule
     */
    public function updateWorkflowSchedule(int $id, array $data): bool
    {
        $updateData = [];

        $allowedFields = [
            'name', 'description', 'workflow_type', 'is_enabled',
            'schedule_type', 'cron_expression', 'interval_hours',
            'batch_limit', 'timeout_minutes', 'options',
            'notify_on_failure', 'notify_email',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'options' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        // Recalculate next run time if schedule changed
        if (isset($data['is_enabled']) || isset($data['cron_expression'])) {
            $schedule = $this->getWorkflowSchedule($id);
            $isEnabled = $data['is_enabled'] ?? $schedule->is_enabled;
            $cronExpr = $data['cron_expression'] ?? $schedule->cron_expression;

            if ($isEnabled && $cronExpr) {
                $updateData['next_run_at'] = $this->calculateNextRunTime($cronExpr);
            } else {
                $updateData['next_run_at'] = null;
            }
        }

        return DB::table('preservation_workflow_schedule')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Delete a workflow schedule
     */
    public function deleteWorkflowSchedule(int $id): bool
    {
        return DB::table('preservation_workflow_schedule')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Toggle schedule enabled/disabled
     */
    public function toggleWorkflowSchedule(int $id): bool
    {
        $schedule = $this->getWorkflowSchedule($id);
        if (!$schedule) {
            return false;
        }

        $newEnabled = !$schedule->is_enabled;
        $updateData = ['is_enabled' => $newEnabled ? 1 : 0];

        if ($newEnabled && $schedule->cron_expression) {
            $updateData['next_run_at'] = $this->calculateNextRunTime($schedule->cron_expression);
        } else {
            $updateData['next_run_at'] = null;
        }

        return DB::table('preservation_workflow_schedule')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Get workflow run history
     */
    public function getWorkflowRuns(?int $scheduleId = null, int $limit = 50): array
    {
        $query = DB::table('preservation_workflow_run as r')
            ->join('preservation_workflow_schedule as s', 'r.schedule_id', '=', 's.id')
            ->select('r.*', 's.name as schedule_name')
            ->orderByDesc('r.started_at')
            ->limit($limit);

        if ($scheduleId) {
            $query->where('r.schedule_id', $scheduleId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get schedules that are due to run
     */
    public function getDueSchedules(): array
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('preservation_workflow_schedule')
            ->where('is_enabled', 1)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', $now);
            })
            ->orderBy('next_run_at')
            ->get()
            ->toArray();
    }

    /**
     * Start a workflow run
     */
    public function startWorkflowRun(int $scheduleId, string $triggeredBy = 'scheduler', ?string $triggeredByUser = null): int
    {
        $schedule = $this->getWorkflowSchedule($scheduleId);
        if (!$schedule) {
            throw new Exception("Schedule not found: {$scheduleId}");
        }

        $now = date('Y-m-d H:i:s');

        return DB::table('preservation_workflow_run')->insertGetId([
            'schedule_id' => $scheduleId,
            'workflow_type' => $schedule->workflow_type,
            'status' => 'running',
            'started_at' => $now,
            'triggered_by' => $triggeredBy,
            'triggered_by_user' => $triggeredByUser,
            'created_at' => $now,
        ]);
    }

    /**
     * Complete a workflow run
     */
    public function completeWorkflowRun(int $runId, string $status, array $results): bool
    {
        $run = DB::table('preservation_workflow_run')->where('id', $runId)->first();
        if (!$run) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $startedAt = strtotime($run->started_at);
        $durationMs = (time() - $startedAt) * 1000;

        // Update the run record
        DB::table('preservation_workflow_run')
            ->where('id', $runId)
            ->update([
                'status' => $status,
                'completed_at' => $now,
                'duration_ms' => $durationMs,
                'objects_processed' => $results['processed'] ?? 0,
                'objects_succeeded' => $results['succeeded'] ?? 0,
                'objects_failed' => $results['failed'] ?? 0,
                'objects_skipped' => $results['skipped'] ?? 0,
                'error_message' => $results['error'] ?? null,
                'summary' => isset($results['summary']) ? json_encode($results['summary']) : null,
            ]);

        // Update the schedule record
        $schedule = $this->getWorkflowSchedule($run->schedule_id);
        if ($schedule) {
            $updateData = [
                'last_run_at' => $now,
                'last_run_status' => $status,
                'last_run_processed' => $results['processed'] ?? 0,
                'last_run_duration_ms' => $durationMs,
                'total_runs' => $schedule->total_runs + 1,
                'total_processed' => $schedule->total_processed + ($results['processed'] ?? 0),
            ];

            // Calculate next run time
            if ($schedule->is_enabled && $schedule->cron_expression) {
                $updateData['next_run_at'] = $this->calculateNextRunTime($schedule->cron_expression);
            }

            DB::table('preservation_workflow_schedule')
                ->where('id', $run->schedule_id)
                ->update($updateData);
        }

        return true;
    }

    /**
     * Execute a workflow based on its type
     */
    public function executeWorkflow(int $scheduleId, string $triggeredBy = 'scheduler', ?string $triggeredByUser = null): array
    {
        $schedule = $this->getWorkflowSchedule($scheduleId);
        if (!$schedule) {
            return ['success' => false, 'error' => 'Schedule not found'];
        }

        $runId = $this->startWorkflowRun($scheduleId, $triggeredBy, $triggeredByUser);
        $options = $schedule->options ? json_decode($schedule->options, true) : [];
        $limit = $schedule->batch_limit ?? 100;

        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'error' => null,
            'summary' => [],
        ];

        try {
            switch ($schedule->workflow_type) {
                case 'format_identification':
                    $unidentifiedOnly = $options['unidentified_only'] ?? true;
                    $updateRegistry = $options['update_registry'] ?? true;
                    $batchResult = $this->runBatchIdentification($limit, $unidentifiedOnly, $updateRegistry);
                    $results['processed'] = $batchResult['total'];
                    $results['succeeded'] = $batchResult['identified'];
                    $results['failed'] = $batchResult['failed'];
                    $results['summary'] = $batchResult;
                    break;

                case 'fixity_check':
                    $batchResult = $this->runBatchFixityCheck($limit);
                    $results['processed'] = $batchResult['total_checked'];
                    $results['succeeded'] = $batchResult['passed'];
                    $results['failed'] = $batchResult['failed'];
                    $results['summary'] = $batchResult;
                    break;

                case 'virus_scan':
                    $newOnly = $options['new_only'] ?? true;
                    $batchResult = $this->runBatchVirusScan($limit, $newOnly);
                    $results['processed'] = $batchResult['total'];
                    $results['succeeded'] = $batchResult['clean'];
                    $results['failed'] = $batchResult['infected'] + $batchResult['errors'];
                    $results['summary'] = $batchResult;
                    break;

                case 'format_conversion':
                    // Placeholder - conversion typically done manually
                    $results['skipped'] = 1;
                    $results['summary'] = ['message' => 'Conversion requires manual target selection'];
                    break;

                case 'backup_verification':
                    $batchResult = $this->verifyAllBackups();
                    $results['processed'] = $batchResult['total'];
                    $results['succeeded'] = $batchResult['valid'];
                    $results['failed'] = $batchResult['invalid'] + $batchResult['corrupted'];
                    $results['summary'] = $batchResult;
                    break;

                case 'replication':
                    // Placeholder - replication requires target configuration
                    $results['skipped'] = 1;
                    $results['summary'] = ['message' => 'Replication requires target configuration'];
                    break;

                default:
                    throw new Exception("Unknown workflow type: {$schedule->workflow_type}");
            }

            $status = $results['failed'] > 0 ? 'partial' : 'completed';
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            $status = 'failed';
        }

        $this->completeWorkflowRun($runId, $status, $results);

        return [
            'success' => $status !== 'failed',
            'run_id' => $runId,
            'status' => $status,
            'results' => $results,
        ];
    }

    /**
     * Get workflow scheduler statistics
     */
    public function getSchedulerStatistics(): array
    {
        $schedules = DB::table('preservation_workflow_schedule')->get();

        $stats = [
            'total_schedules' => count($schedules),
            'enabled_schedules' => 0,
            'disabled_schedules' => 0,
            'by_type' => [],
            'last_24h_runs' => 0,
            'last_24h_success' => 0,
            'last_24h_failed' => 0,
            'upcoming' => [],
        ];

        foreach ($schedules as $s) {
            if ($s->is_enabled) {
                ++$stats['enabled_schedules'];
            } else {
                ++$stats['disabled_schedules'];
            }

            if (!isset($stats['by_type'][$s->workflow_type])) {
                $stats['by_type'][$s->workflow_type] = ['total' => 0, 'enabled' => 0];
            }
            ++$stats['by_type'][$s->workflow_type]['total'];
            if ($s->is_enabled) {
                ++$stats['by_type'][$s->workflow_type]['enabled'];
            }
        }

        // Get runs from last 24 hours
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $recentRuns = DB::table('preservation_workflow_run')
            ->where('started_at', '>=', $yesterday)
            ->get();

        $stats['last_24h_runs'] = count($recentRuns);
        foreach ($recentRuns as $run) {
            if ('completed' === $run->status) {
                ++$stats['last_24h_success'];
            } elseif ('failed' === $run->status) {
                ++$stats['last_24h_failed'];
            }
        }

        // Get upcoming schedules
        $stats['upcoming'] = DB::table('preservation_workflow_schedule')
            ->where('is_enabled', 1)
            ->whereNotNull('next_run_at')
            ->orderBy('next_run_at')
            ->limit(5)
            ->get()
            ->toArray();

        return $stats;
    }

    /**
     * Calculate next run time from cron expression
     */
    private function calculateNextRunTime(string $cronExpression): string
    {
        // Parse cron expression: minute hour day month weekday
        $parts = preg_split('/\s+/', trim($cronExpression));
        if (count($parts) !== 5) {
            // Invalid expression, default to tomorrow at the same time
            return date('Y-m-d H:i:s', strtotime('+1 day'));
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        // Simple calculation - find next occurrence
        $now = time();
        $checkTime = $now;

        // Check up to 366 days ahead
        for ($i = 0; $i < 366 * 24 * 60; ++$i) {
            $checkTime = $now + ($i * 60); // Check each minute

            $m = (int) date('i', $checkTime);
            $h = (int) date('G', $checkTime);
            $d = (int) date('j', $checkTime);
            $mo = (int) date('n', $checkTime);
            $wd = (int) date('w', $checkTime);

            // Check if this time matches the cron expression
            if ($this->matchesCronField($minute, $m)
                && $this->matchesCronField($hour, $h)
                && $this->matchesCronField($day, $d)
                && $this->matchesCronField($month, $mo)
                && $this->matchesCronField($weekday, $wd)) {
                // Found a match, but make sure it's in the future
                if ($checkTime > $now) {
                    return date('Y-m-d H:i:s', $checkTime);
                }
            }

            // Optimization: skip to next hour if minute doesn't match
            if ('*' !== $minute && !$this->matchesCronField($minute, $m)) {
                $checkTime = strtotime(date('Y-m-d H:00:00', $checkTime)) + 3600 - 60;
                $i += 59 - $m;
            }
        }

        // Fallback to tomorrow
        return date('Y-m-d H:i:s', strtotime('+1 day'));
    }

    /**
     * Check if a value matches a cron field
     */
    private function matchesCronField(string $field, int $value): bool
    {
        // Wildcard matches everything
        if ('*' === $field) {
            return true;
        }

        // Handle */n (every n)
        if (str_starts_with($field, '*/')) {
            $interval = (int) substr($field, 2);

            return 0 === $value % $interval;
        }

        // Handle ranges (e.g., 1-5)
        if (str_contains($field, '-')) {
            [$start, $end] = explode('-', $field);

            return $value >= (int) $start && $value <= (int) $end;
        }

        // Handle lists (e.g., 1,3,5)
        if (str_contains($field, ',')) {
            $values = array_map('intval', explode(',', $field));

            return in_array($value, $values);
        }

        // Direct comparison
        return (int) $field === $value;
    }

    /**
     * Get human-readable description of cron expression
     */
    public function describeCronExpression(string $cronExpression): string
    {
        $parts = preg_split('/\s+/', trim($cronExpression));
        if (count($parts) !== 5) {
            return 'Invalid schedule';
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        $desc = [];

        // Time
        if ('*' !== $hour && '*' !== $minute) {
            $desc[] = sprintf('at %02d:%02d', (int) $hour, (int) $minute);
        } elseif ('*' !== $hour) {
            $desc[] = sprintf('at %02d:00', (int) $hour);
        }

        // Day of week
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        if ('*' !== $weekday) {
            if (is_numeric($weekday)) {
                $desc[] = 'on '.$days[(int) $weekday];
            }
        }

        // Day of month
        if ('*' !== $day) {
            $desc[] = 'on day '.$day;
        }

        // Month
        $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        if ('*' !== $month) {
            if (is_numeric($month)) {
                $desc[] = 'in '.$months[(int) $month];
            }
        }

        if (empty($desc)) {
            // All wildcards = every minute
            if ('*' === $minute && '*' === $hour) {
                return 'Every minute';
            }
        }

        // Common patterns
        if ('0' === $minute && '*' === $hour && '*' === $day && '*' === $month && '*' === $weekday) {
            return 'Every hour at :00';
        }

        if ('*' === $day && '*' === $month && '*' === $weekday) {
            return 'Daily '.implode(' ', $desc);
        }

        if ('*' === $day && '*' === $month && '0' === $weekday) {
            return 'Weekly on Sunday '.implode(' ', $desc);
        }

        if ('*' === $day && '*' === $month && '6' === $weekday) {
            return 'Weekly on Saturday '.implode(' ', $desc);
        }

        return ucfirst(implode(' ', $desc)) ?: 'Custom schedule';
    }

    /**
     * Get workflow type display info
     */
    public function getWorkflowTypeInfo(string $type): array
    {
        $types = [
            'format_identification' => [
                'label' => 'Format Identification',
                'icon' => 'fa-fingerprint',
                'color' => 'info',
                'description' => 'Identify file formats using Siegfried (PRONOM)',
            ],
            'fixity_check' => [
                'label' => 'Fixity Check',
                'icon' => 'fa-shield-alt',
                'color' => 'primary',
                'description' => 'Verify file integrity via checksums',
            ],
            'virus_scan' => [
                'label' => 'Virus Scan',
                'icon' => 'fa-bug',
                'color' => 'danger',
                'description' => 'Scan files for malware using ClamAV',
            ],
            'format_conversion' => [
                'label' => 'Format Conversion',
                'icon' => 'fa-exchange-alt',
                'color' => 'warning',
                'description' => 'Convert files to preservation formats',
            ],
            'backup_verification' => [
                'label' => 'Backup Verification',
                'icon' => 'fa-database',
                'color' => 'secondary',
                'description' => 'Verify backup file integrity',
            ],
            'replication' => [
                'label' => 'Replication',
                'icon' => 'fa-clone',
                'color' => 'success',
                'description' => 'Replicate files to backup targets',
            ],
        ];

        return $types[$type] ?? [
            'label' => ucfirst(str_replace('_', ' ', $type)),
            'icon' => 'fa-cog',
            'color' => 'secondary',
            'description' => '',
        ];
    }

    // =========================================
    // OAIS PACKAGE MANAGEMENT
    // =========================================

    /**
     * Get all packages with optional filtering
     */
    public function getPackages(?string $type = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('preservation_package')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('package_type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->offset($offset)->limit($limit)->get()->all();
    }

    /**
     * Get a single package by ID
     */
    public function getPackage(int $id): ?object
    {
        return DB::table('preservation_package')->where('id', $id)->first();
    }

    /**
     * Get a package by UUID
     */
    public function getPackageByUuid(string $uuid): ?object
    {
        return DB::table('preservation_package')->where('uuid', $uuid)->first();
    }

    /**
     * Create a new package
     */
    public function createPackage(array $data): int
    {
        $uuid = $this->generateUuid();

        $packageId = DB::table('preservation_package')->insertGetId([
            'uuid' => $uuid,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'package_type' => $data['package_type'],
            'status' => 'draft',
            'package_format' => $data['package_format'] ?? 'bagit',
            'bagit_version' => $data['bagit_version'] ?? '1.0',
            'manifest_algorithm' => $data['manifest_algorithm'] ?? 'sha256',
            'originator' => $data['originator'] ?? null,
            'submission_agreement' => $data['submission_agreement'] ?? null,
            'retention_period' => $data['retention_period'] ?? null,
            'parent_package_id' => $data['parent_package_id'] ?? null,
            'information_object_id' => $data['information_object_id'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
        ]);

        // Log creation event
        $this->logPackageEvent($packageId, 'creation', 'Package created', 'success');

        return $packageId;
    }

    /**
     * Update a package
     */
    public function updatePackage(int $id, array $data): bool
    {
        $updateData = [];

        $allowedFields = [
            'name', 'description', 'package_format', 'bagit_version',
            'manifest_algorithm', 'originator', 'submission_agreement',
            'retention_period', 'parent_package_id', 'information_object_id',
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['metadata'])) {
            $updateData['metadata'] = json_encode($data['metadata']);
        }

        if (empty($updateData)) {
            return true;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $result = DB::table('preservation_package')
            ->where('id', $id)
            ->update($updateData) >= 0;

        if ($result) {
            $this->logPackageEvent($id, 'modification', 'Package updated', 'success');
        }

        return $result;
    }

    /**
     * Delete a package and its related data
     */
    public function deletePackage(int $id): bool
    {
        $package = $this->getPackage($id);
        if (!$package) {
            return false;
        }

        // Don't delete exported or validated packages without force
        if (in_array($package->status, ['exported', 'validated'])) {
            throw new Exception('Cannot delete exported or validated packages');
        }

        // Delete related objects and events (cascaded by FK)
        DB::table('preservation_package')->where('id', $id)->delete();

        return true;
    }

    /**
     * Update package status
     */
    public function updatePackageStatus(int $id, string $status, ?string $detail = null): bool
    {
        $validStatuses = ['draft', 'building', 'complete', 'validated', 'exported', 'error'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status: {$status}");
        }

        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Set timestamp fields based on status
        switch ($status) {
            case 'complete':
                $updateData['built_at'] = date('Y-m-d H:i:s');
                break;
            case 'validated':
                $updateData['validated_at'] = date('Y-m-d H:i:s');
                break;
            case 'exported':
                $updateData['exported_at'] = date('Y-m-d H:i:s');
                break;
        }

        $result = DB::table('preservation_package')
            ->where('id', $id)
            ->update($updateData) >= 0;

        if ($result) {
            $eventType = 'error' === $status ? 'error' : 'modification';
            $outcome = 'error' === $status ? 'failure' : 'success';
            $this->logPackageEvent($id, $eventType, "Status changed to {$status}".($detail ? ": {$detail}" : ''), $outcome);
        }

        return $result;
    }

    /**
     * Add a digital object to a package
     */
    public function addObjectToPackage(int $packageId, int $digitalObjectId, array $options = []): int
    {
        $package = $this->getPackage($packageId);
        if (!$package) {
            throw new Exception("Package not found: {$packageId}");
        }

        if ('draft' !== $package->status) {
            throw new Exception('Can only add objects to draft packages');
        }

        // Get digital object info
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            throw new Exception("Digital object not found: {$digitalObjectId}");
        }

        // Check if already in package
        $existing = DB::table('preservation_package_object')
            ->where('package_id', $packageId)
            ->where('digital_object_id', $digitalObjectId)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        $filePath = $this->getFilePath($digitalObject);
        $fileName = basename($filePath);

        // Get current max sequence
        $maxSeq = DB::table('preservation_package_object')
            ->where('package_id', $packageId)
            ->max('sequence') ?? -1;

        // Get checksum if available
        $checksum = DB::table('preservation_checksum')
            ->where('digital_object_id', $digitalObjectId)
            ->where('algorithm', $package->manifest_algorithm)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get format info if available
        $formatInfo = DB::table('preservation_object_format')
            ->where('digital_object_id', $digitalObjectId)
            ->orderBy('identification_date', 'desc')
            ->first();

        $objectId = DB::table('preservation_package_object')->insertGetId([
            'package_id' => $packageId,
            'digital_object_id' => $digitalObjectId,
            'relative_path' => $options['relative_path'] ?? "data/{$fileName}",
            'file_name' => $fileName,
            'file_size' => file_exists($filePath) ? filesize($filePath) : null,
            'checksum_algorithm' => $package->manifest_algorithm,
            'checksum_value' => $checksum->checksum_value ?? null,
            'mime_type' => $digitalObject->mime_type ?? null,
            'puid' => $formatInfo->puid ?? null,
            'object_role' => $options['object_role'] ?? 'payload',
            'sequence' => $maxSeq + 1,
            'added_at' => date('Y-m-d H:i:s'),
        ]);

        // Update package counts
        $this->updatePackageCounts($packageId);

        return $objectId;
    }

    /**
     * Remove an object from a package
     */
    public function removeObjectFromPackage(int $packageId, int $digitalObjectId): bool
    {
        $package = $this->getPackage($packageId);
        if (!$package) {
            throw new Exception("Package not found: {$packageId}");
        }

        if ('draft' !== $package->status) {
            throw new Exception('Can only remove objects from draft packages');
        }

        DB::table('preservation_package_object')
            ->where('package_id', $packageId)
            ->where('digital_object_id', $digitalObjectId)
            ->delete();

        $this->updatePackageCounts($packageId);

        return true;
    }

    /**
     * Get objects in a package
     */
    public function getPackageObjects(int $packageId): array
    {
        return DB::table('preservation_package_object as ppo')
            ->leftJoin('digital_object as do', 'ppo.digital_object_id', '=', 'do.id')
            ->leftJoin('information_object as io', 'do.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('ppo.package_id', $packageId)
            ->orderBy('ppo.sequence')
            ->select([
                'ppo.*',
                'do.name as digital_object_name',
                'do.path as digital_object_path',
                'do.mime_type as digital_object_mime',
                'ioi.title as information_object_title',
            ])
            ->get()
            ->all();
    }

    /**
     * Update package object/size counts
     */
    private function updatePackageCounts(int $packageId): void
    {
        $stats = DB::table('preservation_package_object')
            ->where('package_id', $packageId)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(file_size), 0) as total_size')
            ->first();

        DB::table('preservation_package')
            ->where('id', $packageId)
            ->update([
                'object_count' => $stats->count ?? 0,
                'total_size' => $stats->total_size ?? 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Log a package event
     */
    public function logPackageEvent(
        int $packageId,
        string $eventType,
        ?string $detail = null,
        string $outcome = 'success',
        ?string $outcomeDetail = null,
        ?string $agentType = null,
        ?string $agentValue = null,
        ?string $createdBy = null
    ): int {
        return DB::table('preservation_package_event')->insertGetId([
            'package_id' => $packageId,
            'event_type' => $eventType,
            'event_datetime' => date('Y-m-d H:i:s'),
            'event_detail' => $detail,
            'event_outcome' => $outcome,
            'event_outcome_detail' => $outcomeDetail,
            'agent_type' => $agentType ?? 'software',
            'agent_value' => $agentValue ?? 'ahgPreservationPlugin',
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Get events for a package
     */
    public function getPackageEvents(int $packageId, int $limit = 50): array
    {
        return DB::table('preservation_package_event')
            ->where('package_id', $packageId)
            ->orderBy('event_datetime', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Build a BagIt package
     */
    public function buildBagItPackage(int $packageId, ?string $outputPath = null): array
    {
        $package = $this->getPackage($packageId);
        if (!$package) {
            throw new Exception("Package not found: {$packageId}");
        }

        if ('draft' !== $package->status) {
            throw new Exception('Can only build draft packages');
        }

        $this->updatePackageStatus($packageId, 'building');
        $this->logPackageEvent($packageId, 'building', 'Started building BagIt package');

        try {
            // Determine output directory
            $basePath = $outputPath ?? sfConfig::get('sf_upload_dir').'/packages';
            $packageDir = $basePath.'/'.$package->uuid;

            if (!is_dir($basePath)) {
                mkdir($basePath, 0755, true);
            }

            if (is_dir($packageDir)) {
                $this->recursiveDelete($packageDir);
            }

            mkdir($packageDir, 0755, true);
            mkdir($packageDir.'/data', 0755, true);

            // Get package objects
            $objects = $this->getPackageObjects($packageId);
            $manifests = [];
            $totalSize = 0;
            $copiedFiles = 0;

            // Copy files and build manifest
            foreach ($objects as $obj) {
                if ('payload' !== $obj->object_role) {
                    continue;
                }

                // Build source path: root_dir + path + filename
                $rootDir = sfConfig::get('sf_root_dir', '/usr/share/nginx/archive');
                $sourcePath = $rootDir.$obj->digital_object_path.$obj->digital_object_name;
                $destPath = $packageDir.'/'.$obj->relative_path;

                // Ensure destination directory exists
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                if (file_exists($sourcePath)) {
                    copy($sourcePath, $destPath);
                    ++$copiedFiles;
                    $totalSize += filesize($destPath);

                    // Calculate checksum if not present
                    $checksum = $obj->checksum_value;
                    if (!$checksum) {
                        $checksum = hash_file($package->manifest_algorithm, $destPath);
                    }

                    $manifests[] = "{$checksum}  {$obj->relative_path}";
                }
            }

            // Write bagit.txt
            file_put_contents($packageDir.'/bagit.txt', "BagIt-Version: {$package->bagit_version}\nTag-File-Character-Encoding: UTF-8\n");

            // Write manifest
            $manifestFile = "manifest-{$package->manifest_algorithm}.txt";
            file_put_contents($packageDir.'/'.$manifestFile, implode("\n", $manifests)."\n");

            // Write bag-info.txt
            $bagInfo = [
                'Source-Organization: '.($package->originator ?? 'Unknown'),
                'Organization-Address: ',
                'Contact-Name: ',
                'Contact-Email: ',
                'External-Description: '.($package->description ?? ''),
                'Bagging-Date: '.date('Y-m-d'),
                'External-Identifier: '.$package->uuid,
                'Bag-Size: '.$this->formatBytes($totalSize),
                'Payload-Oxum: '.$totalSize.'.'.$copiedFiles,
                'Bag-Group-Identifier: '.strtoupper($package->package_type),
                'Bag-Count: 1 of 1',
                'Internal-Sender-Identifier: '.$package->name,
            ];
            file_put_contents($packageDir.'/bag-info.txt', implode("\n", $bagInfo)."\n");

            // Write tagmanifest
            $tagManifests = [];
            foreach (['bagit.txt', 'bag-info.txt', $manifestFile] as $tagFile) {
                $checksum = hash_file($package->manifest_algorithm, $packageDir.'/'.$tagFile);
                $tagManifests[] = "{$checksum}  {$tagFile}";
            }
            file_put_contents($packageDir."/tagmanifest-{$package->manifest_algorithm}.txt", implode("\n", $tagManifests)."\n");

            // Calculate overall package checksum
            $packageChecksum = hash_file($package->manifest_algorithm, $packageDir.'/'.$manifestFile);

            // Update package
            DB::table('preservation_package')
                ->where('id', $packageId)
                ->update([
                    'status' => 'complete',
                    'source_path' => $packageDir,
                    'total_size' => $totalSize,
                    'package_checksum' => $packageChecksum,
                    'built_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logPackageEvent($packageId, 'building', "BagIt package built successfully: {$copiedFiles} files, ".$this->formatBytes($totalSize), 'success');

            return [
                'success' => true,
                'path' => $packageDir,
                'files' => $copiedFiles,
                'size' => $totalSize,
                'checksum' => $packageChecksum,
            ];
        } catch (Exception $e) {
            $this->updatePackageStatus($packageId, 'error', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate a BagIt package
     */
    public function validateBagItPackage(int $packageId): array
    {
        $package = $this->getPackage($packageId);
        if (!$package) {
            throw new Exception("Package not found: {$packageId}");
        }

        if (!$package->source_path || !is_dir($package->source_path)) {
            throw new Exception('Package has not been built yet');
        }

        $this->logPackageEvent($packageId, 'validation', 'Started BagIt validation');

        $errors = [];
        $warnings = [];
        $packageDir = $package->source_path;

        // Check required files
        $requiredFiles = ['bagit.txt', 'bag-info.txt', "manifest-{$package->manifest_algorithm}.txt"];
        foreach ($requiredFiles as $file) {
            if (!file_exists($packageDir.'/'.$file)) {
                $errors[] = "Missing required file: {$file}";
            }
        }

        if (!empty($errors)) {
            $this->logPackageEvent($packageId, 'validation', 'Validation failed: '.implode(', ', $errors), 'failure');

            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        // Validate bagit.txt
        $bagitContent = file_get_contents($packageDir.'/bagit.txt');
        if (!preg_match('/BagIt-Version:\s*[\d.]+/', $bagitContent)) {
            $errors[] = 'Invalid bagit.txt: missing BagIt-Version';
        }

        // Validate manifest checksums
        $manifestFile = $packageDir."/manifest-{$package->manifest_algorithm}.txt";
        $manifestContent = file_get_contents($manifestFile);
        $lines = array_filter(explode("\n", $manifestContent));

        $validatedFiles = 0;
        $failedFiles = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse: checksum  filepath (two spaces)
            if (preg_match('/^([a-f0-9]+)\s{2}(.+)$/', $line, $matches)) {
                $expectedChecksum = $matches[1];
                $relativePath = $matches[2];
                $filePath = $packageDir.'/'.$relativePath;

                if (!file_exists($filePath)) {
                    $errors[] = "File in manifest not found: {$relativePath}";
                    ++$failedFiles;
                } else {
                    $actualChecksum = hash_file($package->manifest_algorithm, $filePath);
                    if ($actualChecksum !== $expectedChecksum) {
                        $errors[] = "Checksum mismatch for {$relativePath}: expected {$expectedChecksum}, got {$actualChecksum}";
                        ++$failedFiles;
                    } else {
                        ++$validatedFiles;
                    }
                }
            }
        }

        // Validate tagmanifest if present
        $tagManifestFile = $packageDir."/tagmanifest-{$package->manifest_algorithm}.txt";
        if (file_exists($tagManifestFile)) {
            $tagManifestContent = file_get_contents($tagManifestFile);
            $tagLines = array_filter(explode("\n", $tagManifestContent));

            foreach ($tagLines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                if (preg_match('/^([a-f0-9]+)\s{2}(.+)$/', $line, $matches)) {
                    $expectedChecksum = $matches[1];
                    $relativePath = $matches[2];
                    $filePath = $packageDir.'/'.$relativePath;

                    if (file_exists($filePath)) {
                        $actualChecksum = hash_file($package->manifest_algorithm, $filePath);
                        if ($actualChecksum !== $expectedChecksum) {
                            $errors[] = "Tag file checksum mismatch for {$relativePath}";
                        }
                    }
                }
            }
        }

        $valid = empty($errors);

        if ($valid) {
            DB::table('preservation_package')
                ->where('id', $packageId)
                ->update([
                    'status' => 'validated',
                    'validated_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logPackageEvent($packageId, 'validation', "Validation passed: {$validatedFiles} files verified", 'success');
        } else {
            $this->logPackageEvent($packageId, 'validation', 'Validation failed: '.count($errors).' errors', 'failure', implode("\n", $errors));
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_files' => $validatedFiles,
            'failed_files' => $failedFiles,
        ];
    }

    /**
     * Export a package to a specific format
     */
    public function exportPackage(int $packageId, string $format = 'zip', ?string $outputPath = null): array
    {
        $package = $this->getPackage($packageId);
        if (!$package) {
            throw new Exception("Package not found: {$packageId}");
        }

        if (!in_array($package->status, ['complete', 'validated'])) {
            throw new Exception('Package must be built and optionally validated before export');
        }

        if (!$package->source_path || !is_dir($package->source_path)) {
            throw new Exception('Package source directory not found');
        }

        $this->logPackageEvent($packageId, 'export', "Starting export to {$format}");

        try {
            $basePath = $outputPath ?? sfConfig::get('sf_upload_dir').'/exports';
            if (!is_dir($basePath)) {
                mkdir($basePath, 0755, true);
            }

            $exportFile = $basePath.'/'.$package->uuid.'.'.$format;

            switch ($format) {
                case 'zip':
                    $this->createZipArchive($package->source_path, $exportFile);
                    break;
                case 'tar':
                    $this->createTarArchive($package->source_path, $exportFile);
                    break;
                case 'tar.gz':
                    $this->createTarGzArchive($package->source_path, $exportFile);
                    break;
                default:
                    throw new Exception("Unsupported export format: {$format}");
            }

            // Calculate export file checksum
            $exportChecksum = hash_file($package->manifest_algorithm, $exportFile);
            $exportSize = filesize($exportFile);

            DB::table('preservation_package')
                ->where('id', $packageId)
                ->update([
                    'status' => 'exported',
                    'export_path' => $exportFile,
                    'exported_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logPackageEvent($packageId, 'export', "Exported to {$format}: ".$this->formatBytes($exportSize), 'success');

            return [
                'success' => true,
                'path' => $exportFile,
                'format' => $format,
                'size' => $exportSize,
                'checksum' => $exportChecksum,
            ];
        } catch (Exception $e) {
            $this->logPackageEvent($packageId, 'export', "Export failed: {$e->getMessage()}", 'failure');

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create ZIP archive
     */
    private function createZipArchive(string $sourceDir, string $destFile): void
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($destFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception('Could not create ZIP archive');
        }

        $sourceDir = realpath($sourceDir);
        $baseName = basename($sourceDir);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $baseName.'/'.substr($filePath, strlen($sourceDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
    }

    /**
     * Create TAR archive
     */
    private function createTarArchive(string $sourceDir, string $destFile): void
    {
        $phar = new PharData($destFile);
        $phar->buildFromDirectory($sourceDir);
    }

    /**
     * Create TAR.GZ archive
     */
    private function createTarGzArchive(string $sourceDir, string $destFile): void
    {
        $tarFile = str_replace('.tar.gz', '.tar', $destFile);
        $this->createTarArchive($sourceDir, $tarFile);

        $phar = new PharData($tarFile);
        $phar->compress(Phar::GZ);

        unlink($tarFile);
    }

    /**
     * Get package statistics
     */
    public function getPackageStatistics(): array
    {
        $stats = [];

        // Total packages by type
        $byType = DB::table('preservation_package')
            ->selectRaw('package_type, COUNT(*) as count, COALESCE(SUM(total_size), 0) as total_size')
            ->groupBy('package_type')
            ->get();

        $stats['by_type'] = [];
        foreach ($byType as $row) {
            $stats['by_type'][$row->package_type] = [
                'count' => $row->count,
                'total_size' => $row->total_size,
                'total_size_formatted' => $this->formatBytes($row->total_size),
            ];
        }

        // By status
        $byStatus = DB::table('preservation_package')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $stats['by_status'] = [];
        foreach ($byStatus as $row) {
            $stats['by_status'][$row->status] = $row->count;
        }

        // Totals
        $totals = DB::table('preservation_package')
            ->selectRaw('COUNT(*) as total_packages, COALESCE(SUM(object_count), 0) as total_objects, COALESCE(SUM(total_size), 0) as total_size')
            ->first();

        $stats['total_packages'] = $totals->total_packages ?? 0;
        $stats['total_objects'] = $totals->total_objects ?? 0;
        $stats['total_size'] = $totals->total_size ?? 0;
        $stats['total_size_formatted'] = $this->formatBytes($totals->total_size ?? 0);

        // Recent packages
        $stats['recent'] = DB::table('preservation_package')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->all();

        return $stats;
    }

    /**
     * Create a DIP from an AIP
     */
    public function createDipFromAip(int $aipId, array $options = []): int
    {
        $aip = $this->getPackage($aipId);
        if (!$aip) {
            throw new Exception("AIP not found: {$aipId}");
        }

        if ('aip' !== $aip->package_type) {
            throw new Exception('Source package must be an AIP');
        }

        // Create DIP
        $dipId = $this->createPackage([
            'name' => $options['name'] ?? $aip->name.' - DIP',
            'description' => $options['description'] ?? 'Dissemination Information Package derived from AIP '.$aip->uuid,
            'package_type' => 'dip',
            'package_format' => $options['package_format'] ?? 'bagit',
            'parent_package_id' => $aipId,
            'originator' => $aip->originator,
            'created_by' => $options['created_by'] ?? null,
        ]);

        // Copy selected objects from AIP to DIP
        $aipObjects = $this->getPackageObjects($aipId);
        foreach ($aipObjects as $obj) {
            if ('payload' === $obj->object_role) {
                $this->addObjectToPackage($dipId, $obj->digital_object_id, [
                    'relative_path' => $obj->relative_path,
                    'object_role' => 'payload',
                ]);
            }
        }

        $this->logPackageEvent($dipId, 'creation', "DIP created from AIP {$aip->uuid}");

        return $dipId;
    }

    /**
     * Convert SIP to AIP
     */
    public function convertSipToAip(int $sipId, array $options = []): int
    {
        $sip = $this->getPackage($sipId);
        if (!$sip) {
            throw new Exception("SIP not found: {$sipId}");
        }

        if ('sip' !== $sip->package_type) {
            throw new Exception('Source package must be a SIP');
        }

        if (!in_array($sip->status, ['complete', 'validated', 'exported'])) {
            throw new Exception('SIP must be built and validated before conversion to AIP');
        }

        // Create AIP
        $aipId = $this->createPackage([
            'name' => $options['name'] ?? str_replace('SIP', 'AIP', $sip->name),
            'description' => $options['description'] ?? 'Archival Information Package created from SIP '.$sip->uuid,
            'package_type' => 'aip',
            'package_format' => $options['package_format'] ?? 'bagit',
            'parent_package_id' => $sipId,
            'originator' => $sip->originator,
            'submission_agreement' => $sip->submission_agreement,
            'retention_period' => $sip->retention_period,
            'information_object_id' => $sip->information_object_id,
            'created_by' => $options['created_by'] ?? null,
        ]);

        // Copy all objects from SIP to AIP
        $sipObjects = $this->getPackageObjects($sipId);
        foreach ($sipObjects as $obj) {
            $this->addObjectToPackage($aipId, $obj->digital_object_id, [
                'relative_path' => $obj->relative_path,
                'object_role' => $obj->object_role,
            ]);
        }

        $this->logPackageEvent($aipId, 'creation', "AIP created from SIP {$sip->uuid}");
        $this->logPackageEvent($sipId, 'transfer', "SIP converted to AIP {$aipId}");

        return $aipId;
    }

    /**
     * Generate a UUID
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * Recursively delete a directory
     */
    private function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ('.' !== $object && '..' !== $object) {
                    if (is_dir($dir.'/'.$object)) {
                        $this->recursiveDelete($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    // =========================================
    // SELF-HEALING STORAGE
    // Auto-repair from redundant backup copies
    // =========================================

    /**
     * Verify fixity with automatic repair from backup on failure
     *
     * @param int         $digitalObjectId Digital object ID
     * @param string|null $algorithm       Specific algorithm or null for all
     * @param string      $checkedBy       Identifier of checker
     * @param bool        $autoRepair      Attempt auto-repair on failure
     *
     * @return array Results including repair status
     */
    public function verifyFixityWithRepair(
        int $digitalObjectId,
        ?string $algorithm = null,
        string $checkedBy = 'system',
        bool $autoRepair = true
    ): array {
        // First run normal fixity check
        $results = $this->verifyFixity($digitalObjectId, $algorithm, $checkedBy);

        // Check if any failures occurred
        $hasFailure = false;
        foreach ($results as $algo => $result) {
            if (in_array($result['status'], ['fail', 'missing', 'error'])) {
                $hasFailure = true;
                break;
            }
        }

        // If no failures or auto-repair disabled, return results
        if (!$hasFailure || !$autoRepair) {
            return $results;
        }

        // Attempt self-healing repair
        $repairResult = $this->repairFromBackup($digitalObjectId);

        if ($repairResult['success']) {
            // Re-verify after repair
            $postRepairResults = $this->verifyFixity($digitalObjectId, $algorithm, $checkedBy);

            // Merge repair info into results
            foreach ($postRepairResults as $algo => &$result) {
                $result['repaired'] = true;
                $result['repair_source'] = $repairResult['source'];
            }

            return $postRepairResults;
        }

        // Repair failed - add repair attempt info to original results
        foreach ($results as $algo => &$result) {
            if (in_array($result['status'], ['fail', 'missing', 'error'])) {
                $result['repair_attempted'] = true;
                $result['repair_failed'] = true;
                $result['repair_error'] = $repairResult['error'];
            }
        }

        return $results;
    }

    /**
     * Attempt to repair a corrupted/missing file from backup copies
     *
     * @param int $digitalObjectId Digital object ID to repair
     *
     * @return array ['success' => bool, 'source' => string|null, 'error' => string|null]
     */
    public function repairFromBackup(int $digitalObjectId): array
    {
        $digitalObject = $this->getDigitalObject($digitalObjectId);
        if (!$digitalObject) {
            return [
                'success' => false,
                'source' => null,
                'error' => "Digital object not found: {$digitalObjectId}",
            ];
        }

        $filePath = $this->getFilePath($digitalObject);
        $now = date('Y-m-d H:i:s');

        // Get stored checksum to validate backup copies
        $checksum = DB::table('preservation_checksum')
            ->where('digital_object_id', $digitalObjectId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$checksum) {
            return [
                'success' => false,
                'source' => null,
                'error' => 'No stored checksum to validate backup',
            ];
        }

        // Find a valid backup copy
        $backupResult = $this->findValidBackupCopy($digitalObject, $checksum);

        if (!$backupResult['found']) {
            // Log failed repair attempt
            $this->logEvent(
                $digitalObjectId,
                null,
                'repair_attempt',
                'Self-healing repair failed - no valid backup found',
                'failure',
                json_encode(['targets_checked' => $backupResult['targets_checked']])
            );

            return [
                'success' => false,
                'source' => null,
                'error' => 'No valid backup copy found in any replication target',
            ];
        }

        // Attempt to restore the file
        try {
            // Create parent directory if needed
            $parentDir = dirname($filePath);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            // Backup corrupted file if it exists
            if (file_exists($filePath)) {
                $corruptBackup = $filePath.'.corrupt.'.date('YmdHis');
                rename($filePath, $corruptBackup);
            }

            // Copy from backup source
            $copied = $this->copyFromBackupTarget(
                $backupResult['target'],
                $backupResult['backup_path'],
                $filePath
            );

            if (!$copied) {
                // Restore corrupted file if backup failed
                if (isset($corruptBackup) && file_exists($corruptBackup)) {
                    rename($corruptBackup, $filePath);
                }

                throw new Exception('Failed to copy file from backup target');
            }

            // Verify the restored file
            $restoredChecksum = hash_file($checksum->algorithm, $filePath);
            if ($restoredChecksum !== $checksum->checksum_value) {
                // Restored file is also corrupt - restore original and fail
                unlink($filePath);
                if (isset($corruptBackup) && file_exists($corruptBackup)) {
                    rename($corruptBackup, $filePath);
                }

                throw new Exception('Restored file checksum mismatch');
            }

            // Remove corrupt backup after successful restore
            if (isset($corruptBackup) && file_exists($corruptBackup)) {
                unlink($corruptBackup);
            }

            // Log successful repair
            $this->logEvent(
                $digitalObjectId,
                null,
                'repair',
                "File restored from backup: {$backupResult['target']->name}",
                'success',
                json_encode([
                    'source_target' => $backupResult['target']->name,
                    'source_path' => $backupResult['backup_path'],
                    'algorithm' => $checksum->algorithm,
                    'checksum' => $checksum->checksum_value,
                ])
            );

            // Log to replication log
            DB::table('preservation_replication_log')->insert([
                'target_id' => $backupResult['target']->id,
                'operation' => 'restore',
                'status' => 'completed',
                'files_total' => 1,
                'files_synced' => 1,
                'bytes_transferred' => filesize($filePath),
                'started_at' => $now,
                'completed_at' => $now,
                'details' => json_encode([
                    'digital_object_id' => $digitalObjectId,
                    'restored_file' => $filePath,
                ]),
                'created_at' => $now,
            ]);

            return [
                'success' => true,
                'source' => $backupResult['target']->name,
                'error' => null,
            ];
        } catch (Exception $e) {
            // Log failed repair
            $this->logEvent(
                $digitalObjectId,
                null,
                'repair_attempt',
                'Self-healing repair failed: '.$e->getMessage(),
                'failure',
                json_encode([
                    'target' => $backupResult['target']->name ?? null,
                    'error' => $e->getMessage(),
                ])
            );

            return [
                'success' => false,
                'source' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find a valid backup copy of a digital object
     *
     * @param object $digitalObject The digital object
     * @param object $checksum      The expected checksum record
     *
     * @return array ['found' => bool, 'target' => object|null, 'backup_path' => string|null, 'targets_checked' => array]
     */
    private function findValidBackupCopy(object $digitalObject, object $checksum): array
    {
        $targets = DB::table('preservation_replication_target')
            ->where('is_active', 1)
            ->orderBy('last_sync_at', 'desc') // Prefer most recently synced targets
            ->get();

        $result = [
            'found' => false,
            'target' => null,
            'backup_path' => null,
            'targets_checked' => [],
        ];

        // Get the relative path from uploads directory
        $relativePath = ltrim($digitalObject->path, '/').'/'.($digitalObject->name ?? '');
        if (str_starts_with($relativePath, 'uploads/')) {
            $relativePath = substr($relativePath, 8);
        }

        foreach ($targets as $target) {
            $targetName = $target->name;
            $result['targets_checked'][] = $targetName;

            try {
                $config = json_decode($target->connection_config ?? '{}', true);

                // Build backup file path based on target type
                $backupPath = $this->getBackupFilePath($target, $relativePath, $config);

                if (!$backupPath) {
                    continue;
                }

                // Check if backup file exists and is valid
                $isValid = $this->validateBackupFile($target, $backupPath, $checksum, $config);

                if ($isValid) {
                    $result['found'] = true;
                    $result['target'] = $target;
                    $result['backup_path'] = $backupPath;

                    return $result;
                }
            } catch (Exception $e) {
                // Continue to next target on error
                continue;
            }
        }

        return $result;
    }

    /**
     * Get the backup file path for a target
     *
     * @param object $target       Replication target
     * @param string $relativePath Relative path to the file
     * @param array  $config       Target connection config
     *
     * @return string|null Full path to backup file or null if not determinable
     */
    private function getBackupFilePath(object $target, string $relativePath, array $config): ?string
    {
        switch ($target->target_type) {
            case 'local':
            case 'rsync':
                $basePath = $config['path'] ?? $config['target_path'] ?? null;
                if (!$basePath) {
                    return null;
                }

                return rtrim($basePath, '/').'/uploads/'.$relativePath;

            case 'sftp':
                $basePath = $config['remote_path'] ?? $config['path'] ?? null;
                if (!$basePath) {
                    return null;
                }

                return rtrim($basePath, '/').'/uploads/'.$relativePath;

            case 's3':
            case 'azure':
            case 'gcs':
                // For cloud targets, return the object key
                $prefix = $config['prefix'] ?? '';

                return ltrim($prefix.'/uploads/'.$relativePath, '/');

            default:
                return null;
        }
    }

    /**
     * Validate a backup file against expected checksum
     *
     * @param object $target     Replication target
     * @param string $backupPath Path to backup file
     * @param object $checksum   Expected checksum record
     * @param array  $config     Target connection config
     *
     * @return bool True if backup is valid
     */
    private function validateBackupFile(object $target, string $backupPath, object $checksum, array $config): bool
    {
        switch ($target->target_type) {
            case 'local':
                if (!file_exists($backupPath)) {
                    return false;
                }
                $actualChecksum = hash_file($checksum->algorithm, $backupPath);

                return $actualChecksum === $checksum->checksum_value;

            case 'rsync':
                // For rsync targets, they're typically local mounts
                if (!file_exists($backupPath)) {
                    return false;
                }
                $actualChecksum = hash_file($checksum->algorithm, $backupPath);

                return $actualChecksum === $checksum->checksum_value;

            case 'sftp':
                // For SFTP, we need to download and verify
                return $this->validateSftpBackup($backupPath, $checksum, $config);

            case 's3':
                // For S3, check if object exists and verify checksum if possible
                return $this->validateS3Backup($backupPath, $checksum, $config);

            case 'azure':
            case 'gcs':
                // Cloud validation - would require SDK integration
                // For now, just check object metadata if available
                return false;

            default:
                return false;
        }
    }

    /**
     * Validate SFTP backup file
     */
    private function validateSftpBackup(string $remotePath, object $checksum, array $config): bool
    {
        if (!function_exists('ssh2_connect')) {
            return false;
        }

        try {
            $host = $config['host'] ?? null;
            $port = $config['port'] ?? 22;
            $username = $config['username'] ?? null;

            if (!$host || !$username) {
                return false;
            }

            $connection = ssh2_connect($host, $port);
            if (!$connection) {
                return false;
            }

            // Authenticate
            if (!empty($config['private_key'])) {
                ssh2_auth_pubkey_file(
                    $connection,
                    $username,
                    $config['public_key'] ?? '',
                    $config['private_key']
                );
            } elseif (!empty($config['password'])) {
                ssh2_auth_password($connection, $username, $config['password']);
            }

            $sftp = ssh2_sftp($connection);
            if (!$sftp) {
                return false;
            }

            // Check if file exists
            $sftpPath = "ssh2.sftp://{$sftp}{$remotePath}";
            if (!file_exists($sftpPath)) {
                return false;
            }

            // Verify checksum
            $actualChecksum = hash_file($checksum->algorithm, $sftpPath);

            return $actualChecksum === $checksum->checksum_value;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate S3 backup file
     */
    private function validateS3Backup(string $objectKey, object $checksum, array $config): bool
    {
        // Check if AWS SDK is available
        if (!class_exists('Aws\S3\S3Client')) {
            return false;
        }

        try {
            $bucket = $config['bucket'] ?? null;
            $region = $config['region'] ?? 'us-east-1';

            if (!$bucket) {
                return false;
            }

            $s3Config = [
                'version' => 'latest',
                'region' => $region,
            ];

            // Add credentials if provided
            if (!empty($config['access_key']) && !empty($config['secret_key'])) {
                $s3Config['credentials'] = [
                    'key' => $config['access_key'],
                    'secret' => $config['secret_key'],
                ];
            }

            $s3 = new \Aws\S3\S3Client($s3Config);

            // Check if object exists
            if (!$s3->doesObjectExist($bucket, $objectKey)) {
                return false;
            }

            // For S3, we can check ETag (MD5 for single-part uploads)
            // or download a small portion to verify
            $head = $s3->headObject([
                'Bucket' => $bucket,
                'Key' => $objectKey,
            ]);

            // If we have MD5 checksum, compare with ETag
            if ('md5' === $checksum->algorithm) {
                $etag = trim($head['ETag'], '"');
                // ETag for single-part uploads is MD5
                if (false === strpos($etag, '-')) {
                    return $etag === $checksum->checksum_value;
                }
            }

            // For other algorithms, we'd need to download the file
            // For now, just verify the object exists
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Copy file from backup target to destination
     *
     * @param object $target     Replication target
     * @param string $sourcePath Source path in backup
     * @param string $destPath   Local destination path
     *
     * @return bool True if copy succeeded
     */
    private function copyFromBackupTarget(object $target, string $sourcePath, string $destPath): bool
    {
        $config = json_decode($target->connection_config ?? '{}', true);

        switch ($target->target_type) {
            case 'local':
            case 'rsync':
                return copy($sourcePath, $destPath);

            case 'sftp':
                return $this->copyFromSftp($sourcePath, $destPath, $config);

            case 's3':
                return $this->copyFromS3($sourcePath, $destPath, $config);

            case 'azure':
            case 'gcs':
                // Cloud providers would need SDK integration
                return false;

            default:
                return false;
        }
    }

    /**
     * Copy file from SFTP backup
     */
    private function copyFromSftp(string $remotePath, string $localPath, array $config): bool
    {
        if (!function_exists('ssh2_connect')) {
            return false;
        }

        try {
            $host = $config['host'] ?? null;
            $port = $config['port'] ?? 22;
            $username = $config['username'] ?? null;

            if (!$host || !$username) {
                return false;
            }

            $connection = ssh2_connect($host, $port);
            if (!$connection) {
                return false;
            }

            // Authenticate
            if (!empty($config['private_key'])) {
                ssh2_auth_pubkey_file(
                    $connection,
                    $username,
                    $config['public_key'] ?? '',
                    $config['private_key']
                );
            } elseif (!empty($config['password'])) {
                ssh2_auth_password($connection, $username, $config['password']);
            }

            return ssh2_scp_recv($connection, $remotePath, $localPath);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Copy file from S3 backup
     */
    private function copyFromS3(string $objectKey, string $localPath, array $config): bool
    {
        if (!class_exists('Aws\S3\S3Client')) {
            return false;
        }

        try {
            $bucket = $config['bucket'] ?? null;
            $region = $config['region'] ?? 'us-east-1';

            if (!$bucket) {
                return false;
            }

            $s3Config = [
                'version' => 'latest',
                'region' => $region,
            ];

            if (!empty($config['access_key']) && !empty($config['secret_key'])) {
                $s3Config['credentials'] = [
                    'key' => $config['access_key'],
                    'secret' => $config['secret_key'],
                ];
            }

            $s3 = new \Aws\S3\S3Client($s3Config);

            $result = $s3->getObject([
                'Bucket' => $bucket,
                'Key' => $objectKey,
                'SaveAs' => $localPath,
            ]);

            return file_exists($localPath);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Run batch fixity check with auto-repair
     *
     * @param int    $limit      Max objects to check
     * @param int    $minAge     Min days since last check
     * @param string $checkedBy  Identifier for the checker
     * @param bool   $autoRepair Enable auto-repair on failure
     *
     * @return array Summary of results
     */
    public function runBatchFixityCheckWithRepair(
        int $limit = 100,
        int $minAge = 7,
        string $checkedBy = 'cron',
        bool $autoRepair = true
    ): array {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$minAge} days"));

        // Find objects needing verification
        $objects = DB::table('preservation_checksum as pc')
            ->join('digital_object as do', 'pc.digital_object_id', '=', 'do.id')
            ->where(function ($q) use ($cutoffDate) {
                $q->whereNull('pc.verified_at')
                  ->orWhere('pc.verified_at', '<', $cutoffDate);
            })
            ->select('pc.digital_object_id')
            ->distinct()
            ->limit($limit)
            ->get();

        $summary = [
            'total' => $objects->count(),
            'passed' => 0,
            'failed' => 0,
            'repaired' => 0,
            'repair_failed' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($objects as $obj) {
            try {
                $results = $this->verifyFixityWithRepair(
                    $obj->digital_object_id,
                    null,
                    $checkedBy,
                    $autoRepair
                );

                $allPassed = collect($results)->every(fn ($r) => 'pass' === $r['status']);
                $anyRepaired = collect($results)->contains(fn ($r) => !empty($r['repaired']));
                $repairFailed = collect($results)->contains(fn ($r) => !empty($r['repair_failed']));

                if ($allPassed) {
                    if ($anyRepaired) {
                        $summary['repaired']++;
                    } else {
                        $summary['passed']++;
                    }
                } elseif ($repairFailed) {
                    $summary['repair_failed']++;
                    $summary['details'][$obj->digital_object_id] = $results;
                } else {
                    $summary['failed']++;
                    $summary['details'][$obj->digital_object_id] = $results;
                }
            } catch (Exception $e) {
                $summary['errors']++;
                $summary['details'][$obj->digital_object_id] = ['error' => $e->getMessage()];
            }
        }

        return $summary;
    }

    /**
     * Get self-healing statistics
     *
     * @param int $days Number of days to look back
     *
     * @return array Statistics about self-healing operations
     */
    public function getSelfHealingStats(int $days = 30): array
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $repairEvents = DB::table('preservation_event')
            ->where('event_type', 'repair')
            ->where('event_datetime', '>=', $startDate)
            ->get();

        $repairAttempts = DB::table('preservation_event')
            ->where('event_type', 'repair_attempt')
            ->where('event_datetime', '>=', $startDate)
            ->get();

        $restoreLogs = DB::table('preservation_replication_log')
            ->where('operation', 'restore')
            ->where('started_at', '>=', $startDate)
            ->get();

        $successfulRepairs = $repairEvents->where('event_outcome', 'success')->count();
        $failedRepairs = $repairAttempts->where('event_outcome', 'failure')->count();

        // Get repairs by source target
        $repairsByTarget = [];
        foreach ($restoreLogs as $log) {
            $target = DB::table('preservation_replication_target')
                ->where('id', $log->target_id)
                ->first();
            $targetName = $target ? $target->name : 'unknown';

            if (!isset($repairsByTarget[$targetName])) {
                $repairsByTarget[$targetName] = 0;
            }
            $repairsByTarget[$targetName]++;
        }

        return [
            'period_days' => $days,
            'successful_repairs' => $successfulRepairs,
            'failed_repairs' => $failedRepairs,
            'total_attempts' => $successfulRepairs + $failedRepairs,
            'success_rate' => ($successfulRepairs + $failedRepairs) > 0
                ? round(($successfulRepairs / ($successfulRepairs + $failedRepairs)) * 100, 1)
                : 100,
            'bytes_restored' => $restoreLogs->sum('bytes_transferred'),
            'repairs_by_target' => $repairsByTarget,
        ];
    }

    // =========================================
    // PRONOM REGISTRY SYNC
    // Sync format data from UK National Archives
    // =========================================

    /**
     * PRONOM API base URL
     */
    private const PRONOM_API_BASE = 'https://www.nationalarchives.gov.uk/pronom/';
    private const PRONOM_SPARQL_ENDPOINT = 'https://www.nationalarchives.gov.uk/pronom/sparql';

    /**
     * Sync format registry from PRONOM
     *
     * @param array $puids Optional specific PUIDs to sync (e.g., ['fmt/18', 'fmt/43'])
     *
     * @return array Sync results
     */
    public function syncPronomRegistry(array $puids = []): array
    {
        $results = [
            'synced' => 0,
            'updated' => 0,
            'created' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // If no specific PUIDs, get all known PUIDs from our database
        if (empty($puids)) {
            $puids = DB::table('preservation_object_format')
                ->whereNotNull('puid')
                ->distinct()
                ->pluck('puid')
                ->toArray();
        }

        if (empty($puids)) {
            $results['errors'][] = 'No PUIDs to sync';

            return $results;
        }

        foreach ($puids as $puid) {
            try {
                $formatData = $this->fetchPronomFormat($puid);

                if (!$formatData) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to fetch PUID: {$puid}";
                    continue;
                }

                // Check if format exists
                $existing = DB::table('preservation_format')
                    ->where('puid', $puid)
                    ->first();

                if ($existing) {
                    // Update existing format
                    DB::table('preservation_format')
                        ->where('id', $existing->id)
                        ->update([
                            'format_name' => $formatData['name'],
                            'format_version' => $formatData['version'],
                            'mime_type' => $formatData['mime_type'] ?? $existing->mime_type,
                            'extension' => $formatData['extension'],
                            'risk_level' => $this->assessPronomFormatRisk($formatData),
                            'risk_notes' => $formatData['risk_notes'] ?? null,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    $results['updated']++;
                } else {
                    // Create new format entry
                    // Use PUID-based MIME type to avoid unique constraint issues
                    $mimeType = $formatData['mime_type'] ?? null;
                    if (!$mimeType || 'application/octet-stream' === $mimeType) {
                        // Create PUID-based MIME type for unknown formats
                        $mimeType = 'application/x-pronom-'.$puid;
                    }

                    // Check if mime/version combo already exists
                    $mimeExists = DB::table('preservation_format')
                        ->where('mime_type', $mimeType)
                        ->where('format_version', $formatData['version'] ?? '')
                        ->exists();

                    if ($mimeExists) {
                        // Use PUID as part of MIME type to make it unique
                        $mimeType = 'application/x-pronom-'.str_replace('/', '-', $puid);
                    }

                    DB::table('preservation_format')->insert([
                        'puid' => $puid,
                        'format_name' => $formatData['name'],
                        'format_version' => $formatData['version'],
                        'mime_type' => $mimeType,
                        'extension' => $formatData['extension'],
                        'risk_level' => $this->assessPronomFormatRisk($formatData),
                        'risk_notes' => $formatData['risk_notes'] ?? null,
                        'preservation_action' => 'monitor',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $results['created']++;
                }

                $results['synced']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$puid}: ".$e->getMessage();
            }
        }

        // Log the sync event
        $this->logEvent(
            null,
            null,
            'format_identification',
            "PRONOM registry sync completed: {$results['synced']} synced",
            $results['failed'] > 0 ? 'warning' : 'success',
            json_encode($results)
        );

        return $results;
    }

    /**
     * Fetch format information from PRONOM API
     *
     * @param string $puid PRONOM Unique Identifier (e.g., 'fmt/18')
     *
     * @return array|null Format data or null on failure
     */
    public function fetchPronomFormat(string $puid): ?array
    {
        // PRONOM provides XML data at /PUID.xml
        $url = self::PRONOM_API_BASE.$puid.'.xml';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => "Accept: application/xml\r\nUser-Agent: AtoM-Preservation/1.4\r\n",
            ],
        ]);

        $xml = @file_get_contents($url, false, $context);

        if (false === $xml) {
            return null;
        }

        return $this->parseProNomXml($xml, $puid);
    }

    /**
     * Parse PRONOM XML response
     *
     * @param string $xml  XML content
     * @param string $puid The PUID being parsed
     *
     * @return array|null Parsed format data
     */
    private function parseProNomXml(string $xml, string $puid): ?array
    {
        try {
            $doc = new DOMDocument();
            if (!$doc->loadXML($xml)) {
                return null;
            }

            $xpath = new DOMXPath($doc);

            // Register namespaces - PRONOM uses various namespaces
            $xpath->registerNamespace('pronom', 'http://pronom.nationalarchives.gov.uk');

            // Extract format information
            $formatData = [
                'puid' => $puid,
                'name' => null,
                'version' => null,
                'mime_type' => null,
                'extension' => null,
                'description' => null,
                'binary_signature' => false,
                'risk_notes' => null,
                'release_date' => null,
                'withdrawn' => false,
            ];

            // Try different XML structures (PRONOM XML varies)
            $nameNode = $xpath->query('//FormatName')->item(0)
                ?? $xpath->query('//pronom:FormatName')->item(0)
                ?? $xpath->query('//*[local-name()="FormatName"]')->item(0);

            if ($nameNode) {
                $formatData['name'] = trim($nameNode->textContent);
            }

            $versionNode = $xpath->query('//FormatVersion')->item(0)
                ?? $xpath->query('//pronom:FormatVersion')->item(0)
                ?? $xpath->query('//*[local-name()="FormatVersion"]')->item(0);

            if ($versionNode) {
                $formatData['version'] = trim($versionNode->textContent);
            }

            // Get MIME type
            $mimeNode = $xpath->query('//MIMEType')->item(0)
                ?? $xpath->query('//pronom:MIMEType')->item(0)
                ?? $xpath->query('//*[local-name()="MIMEType"]')->item(0);

            if ($mimeNode) {
                $formatData['mime_type'] = trim($mimeNode->textContent);
            }

            // Get extensions
            $extNodes = $xpath->query('//Extension')
                ?? $xpath->query('//pronom:Extension')
                ?? $xpath->query('//*[local-name()="Extension"]');

            $extensions = [];
            foreach ($extNodes as $node) {
                $ext = trim($node->textContent);
                if ($ext) {
                    $extensions[] = $ext;
                }
            }
            if (!empty($extensions)) {
                $formatData['extension'] = implode(',', array_slice($extensions, 0, 3));
            }

            // Check for binary signatures (indicates good identification)
            $sigNodes = $xpath->query('//InternalSignature')
                ?? $xpath->query('//pronom:InternalSignature')
                ?? $xpath->query('//*[local-name()="InternalSignature"]');

            $formatData['binary_signature'] = $sigNodes && $sigNodes->length > 0;

            // Get description
            $descNode = $xpath->query('//FormatDescription')->item(0)
                ?? $xpath->query('//pronom:FormatDescription')->item(0)
                ?? $xpath->query('//*[local-name()="FormatDescription"]')->item(0);

            if ($descNode) {
                $formatData['description'] = substr(trim($descNode->textContent), 0, 1000);
            }

            // Check if format has risks
            $riskNode = $xpath->query('//FormatRisk')->item(0)
                ?? $xpath->query('//pronom:FormatRisk')->item(0)
                ?? $xpath->query('//*[local-name()="FormatRisk"]')->item(0);

            if ($riskNode) {
                $formatData['risk_notes'] = trim($riskNode->textContent);
            }

            // Check if withdrawn
            $formatData['withdrawn'] = false !== stripos($xml, 'withdrawn');

            return $formatData;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Assess format risk level based on PRONOM data
     *
     * @param array $formatData Format data from PRONOM
     *
     * @return string Risk level: low, medium, high, or critical
     */
    private function assessPronomFormatRisk(array $formatData): string
    {
        // Start with medium risk
        $riskScore = 50;

        // Withdrawn formats are critical risk
        if (!empty($formatData['withdrawn'])) {
            return 'critical';
        }

        // Binary signature available = lower risk (better identification)
        if (!empty($formatData['binary_signature'])) {
            $riskScore -= 20;
        } else {
            $riskScore += 10;
        }

        // Proprietary formats without open specs tend to be higher risk
        $proprietaryIndicators = ['microsoft', 'adobe', 'apple', 'proprietary'];
        $name = strtolower($formatData['name'] ?? '');
        foreach ($proprietaryIndicators as $indicator) {
            if (str_contains($name, $indicator)) {
                $riskScore += 10;
                break;
            }
        }

        // Open formats are lower risk
        $openIndicators = ['pdf/a', 'odf', 'png', 'tiff', 'jpeg 2000', 'flac', 'wav', 'xml'];
        foreach ($openIndicators as $indicator) {
            if (str_contains($name, $indicator)) {
                $riskScore -= 15;
                break;
            }
        }

        // Explicit risk notes increase risk
        if (!empty($formatData['risk_notes'])) {
            $riskScore += 15;
        }

        // Map score to risk level
        if ($riskScore <= 25) {
            return 'low';
        } elseif ($riskScore <= 50) {
            return 'medium';
        } elseif ($riskScore <= 75) {
            return 'high';
        }

        return 'critical';
    }

    /**
     * Get PRONOM sync status and statistics
     *
     * @return array Sync status information
     */
    public function getPronomSyncStatus(): array
    {
        // Count formats with PUIDs
        $withPuid = DB::table('preservation_format')
            ->whereNotNull('puid')
            ->where('puid', '!=', '')
            ->count();

        // Count formats without PUIDs
        $withoutPuid = DB::table('preservation_format')
            ->where(function ($q) {
                $q->whereNull('puid')->orWhere('puid', '=', '');
            })
            ->count();

        // Count unique PUIDs in object formats
        $objectPuids = DB::table('preservation_object_format')
            ->whereNotNull('puid')
            ->distinct()
            ->count('puid');

        // Get format distribution
        $riskDistribution = DB::table('preservation_format')
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        // Get last sync event
        $lastSync = DB::table('preservation_event')
            ->where('event_type', 'format_identification')
            ->where('event_detail', 'like', '%PRONOM registry sync%')
            ->orderBy('event_datetime', 'desc')
            ->first();

        // Get unregistered PUIDs (in objects but not in format registry)
        $unregisteredPuids = DB::table('preservation_object_format as pof')
            ->leftJoin('preservation_format as pf', 'pof.puid', '=', 'pf.puid')
            ->whereNotNull('pof.puid')
            ->whereNull('pf.id')
            ->distinct()
            ->pluck('pof.puid')
            ->toArray();

        return [
            'registered_formats' => $withPuid + $withoutPuid,
            'formats_with_puid' => $withPuid,
            'formats_without_puid' => $withoutPuid,
            'unique_object_puids' => $objectPuids,
            'unregistered_puids' => $unregisteredPuids,
            'unregistered_count' => count($unregisteredPuids),
            'risk_distribution' => $riskDistribution,
            'last_sync' => $lastSync ? [
                'datetime' => $lastSync->event_datetime,
                'outcome' => $lastSync->event_outcome,
            ] : null,
        ];
    }

    /**
     * Sync all unregistered PUIDs from PRONOM
     *
     * @return array Sync results
     */
    public function syncAllUnregisteredPuids(): array
    {
        $status = $this->getPronomSyncStatus();

        if (empty($status['unregistered_puids'])) {
            return [
                'synced' => 0,
                'message' => 'All PUIDs are already registered',
            ];
        }

        return $this->syncPronomRegistry($status['unregistered_puids']);
    }

    /**
     * Get format details with PRONOM information
     *
     * @param string $puid PRONOM Unique Identifier
     *
     * @return array|null Format details
     */
    public function getFormatByPuid(string $puid): ?array
    {
        $format = DB::table('preservation_format')
            ->where('puid', $puid)
            ->first();

        if (!$format) {
            return null;
        }

        // Get objects count with this format
        $objectCount = DB::table('preservation_object_format')
            ->where('puid', $puid)
            ->count();

        return [
            'id' => $format->id,
            'puid' => $format->puid,
            'name' => $format->format_name,
            'version' => $format->format_version,
            'mime_type' => $format->mime_type,
            'extension' => $format->extension,
            'risk_level' => $format->risk_level,
            'risk_notes' => $format->risk_notes,
            'preservation_action' => $format->preservation_action,
            'is_preservation_format' => (bool) $format->is_preservation_format,
            'object_count' => $objectCount,
            'pronom_url' => self::PRONOM_API_BASE.$puid,
        ];
    }

    /**
     * Bulk fetch PRONOM data for common formats
     *
     * @return array Sync results for common formats
     */
    public function syncCommonFormats(): array
    {
        // Common archival format PUIDs
        $commonPuids = [
            // Images
            'fmt/42' => 'JPEG 2000',
            'fmt/43' => 'JPEG 2000',
            'fmt/44' => 'JPEG 2000',
            'fmt/353' => 'TIFF',
            'fmt/155' => 'PDF/A-1a',
            'fmt/354' => 'PDF/A-1b',
            'fmt/476' => 'PDF/A-2a',
            'fmt/477' => 'PDF/A-2b',
            'fmt/11' => 'PNG',
            'fmt/12' => 'PNG',
            'fmt/13' => 'PNG',
            // Audio
            'fmt/141' => 'WAV',
            'fmt/142' => 'WAV',
            'fmt/703' => 'FLAC',
            // Video
            'fmt/569' => 'Matroska',
            'fmt/199' => 'MPEG-4',
            // Documents
            'fmt/95' => 'PDF/A',
            'fmt/18' => 'PDF 1.4',
            'fmt/19' => 'PDF 1.5',
            'fmt/20' => 'PDF 1.6',
            // Office
            'fmt/412' => 'ODF Text',
            'fmt/413' => 'ODF Spreadsheet',
            'fmt/414' => 'ODF Presentation',
            // Archives
            'x-fmt/263' => 'ZIP',
            'x-fmt/265' => 'TAR',
            'fmt/484' => '7z',
        ];

        return $this->syncPronomRegistry(array_keys($commonPuids));
    }
}
