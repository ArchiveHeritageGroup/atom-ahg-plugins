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
     */
    public function identifyFormat(int $digitalObjectId): ?array
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
            ->leftJoin('information_object as io', 'do.information_object_id', '=', 'io.id')
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

        // Handle relative paths
        if (!str_starts_with($path, '/')) {
            $path = $this->uploadsPath . '/' . $path;
        }

        return $path;
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
}
