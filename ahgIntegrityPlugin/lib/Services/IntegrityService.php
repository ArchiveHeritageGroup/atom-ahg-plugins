<?php

use Illuminate\Database\Capsule\Manager as DB;

class IntegrityService
{
    private string $uploadsPath;
    private string $lockDir;
    private ?object $preservationService = null;

    public function __construct()
    {
        $this->uploadsPath = \sfConfig::get('sf_upload_dir', \sfConfig::get('sf_root_dir') . '/uploads');
        $this->lockDir = \sfConfig::get('sf_root_dir') . '/cache/integrity_locks';
        if (!is_dir($this->lockDir)) {
            @mkdir($this->lockDir, 0755, true);
        }
    }

    // ------------------------------------------------------------------
    // Preservation Service delegation
    // ------------------------------------------------------------------

    protected function getPreservationService(): object
    {
        if ($this->preservationService === null) {
            $path = \sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPreservationPlugin/lib/PreservationService.php';
            if (!file_exists($path)) {
                throw new \RuntimeException('ahgPreservationPlugin is required but PreservationService.php not found');
            }
            require_once $path;
            $this->preservationService = new \PreservationService();
        }

        return $this->preservationService;
    }

    // ------------------------------------------------------------------
    // Single object verification
    // ------------------------------------------------------------------

    public function verifyObject(int $digitalObjectId, ?int $runId, string $algorithm = 'sha256'): array
    {
        $startTime = microtime(true);
        $now = date('Y-m-d H:i:s');

        // Resolve digital object
        $doRow = DB::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first();

        if (!$doRow) {
            return $this->appendLedger($runId, $digitalObjectId, [
                'outcome' => 'error',
                'error_detail' => 'Digital object record not found in database',
                'algorithm' => $algorithm,
                'duration_ms' => $this->elapsed($startTime),
            ]);
        }

        // Resolve file path
        $filePath = $this->resolveFilePath($doRow);

        // Denormalized IDs for scoped queries
        $informationObjectId = $doRow->object_id ?? null;
        $repositoryId = $this->resolveRepositoryId($informationObjectId);

        // File existence check
        if (!file_exists($filePath)) {
            $entry = $this->appendLedger($runId, $digitalObjectId, [
                'information_object_id' => $informationObjectId,
                'repository_id' => $repositoryId,
                'file_path' => $filePath,
                'file_exists' => 0,
                'file_readable' => 0,
                'algorithm' => $algorithm,
                'outcome' => 'missing',
                'duration_ms' => $this->elapsed($startTime),
            ]);
            $this->escalateDeadLetter($digitalObjectId, 'missing', $runId, 'File not found: ' . $filePath);

            return $entry;
        }

        // Readability check
        if (!is_readable($filePath)) {
            $entry = $this->appendLedger($runId, $digitalObjectId, [
                'information_object_id' => $informationObjectId,
                'repository_id' => $repositoryId,
                'file_path' => $filePath,
                'file_size' => @filesize($filePath) ?: null,
                'file_exists' => 1,
                'file_readable' => 0,
                'algorithm' => $algorithm,
                'outcome' => 'unreadable',
                'duration_ms' => $this->elapsed($startTime),
            ]);
            $this->escalateDeadLetter($digitalObjectId, 'unreadable', $runId, 'File not readable: ' . $filePath);

            return $entry;
        }

        $fileSize = filesize($filePath);

        // Baseline hash from preservation_checksum
        $baseline = DB::table('preservation_checksum')
            ->where('digital_object_id', $digitalObjectId)
            ->where('algorithm', $algorithm)
            ->first();

        if (!$baseline) {
            // No baseline — generate one via PreservationService
            try {
                $this->getPreservationService()->generateChecksums($digitalObjectId, [$algorithm]);
                $baseline = DB::table('preservation_checksum')
                    ->where('digital_object_id', $digitalObjectId)
                    ->where('algorithm', $algorithm)
                    ->first();
            } catch (\Exception $e) {
                // Could not generate baseline
            }

            if (!$baseline) {
                return $this->appendLedger($runId, $digitalObjectId, [
                    'information_object_id' => $informationObjectId,
                    'repository_id' => $repositoryId,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_exists' => 1,
                    'file_readable' => 1,
                    'algorithm' => $algorithm,
                    'outcome' => 'no_baseline',
                    'error_detail' => 'No baseline checksum and could not generate one',
                    'duration_ms' => $this->elapsed($startTime),
                ]);
            }
        }

        // Compute hash
        $expectedHash = $baseline->checksum_value;
        $computedHash = null;

        try {
            $computedHash = hash_file($algorithm, $filePath);
        } catch (\Exception $e) {
            $entry = $this->appendLedger($runId, $digitalObjectId, [
                'information_object_id' => $informationObjectId,
                'repository_id' => $repositoryId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'file_exists' => 1,
                'file_readable' => 1,
                'algorithm' => $algorithm,
                'expected_hash' => $expectedHash,
                'outcome' => 'error',
                'error_detail' => 'hash_file() failed: ' . $e->getMessage(),
                'duration_ms' => $this->elapsed($startTime),
            ]);
            $this->escalateDeadLetter($digitalObjectId, 'error', $runId, $e->getMessage());

            return $entry;
        }

        // Compare
        $hashMatch = hash_equals($expectedHash, $computedHash);
        $outcome = $hashMatch ? 'pass' : 'mismatch';

        $entry = $this->appendLedger($runId, $digitalObjectId, [
            'information_object_id' => $informationObjectId,
            'repository_id' => $repositoryId,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_exists' => 1,
            'file_readable' => 1,
            'algorithm' => $algorithm,
            'expected_hash' => $expectedHash,
            'computed_hash' => $computedHash,
            'hash_match' => $hashMatch ? 1 : 0,
            'outcome' => $outcome,
            'duration_ms' => $this->elapsed($startTime),
        ]);

        if (!$hashMatch) {
            $this->escalateDeadLetter($digitalObjectId, 'mismatch', $runId,
                "Expected: {$expectedHash}, Got: {$computedHash}");
        } else {
            // Clear dead letter if previously failing
            $this->clearDeadLetter($digitalObjectId);
        }

        return $entry;
    }

    // ------------------------------------------------------------------
    // Batch verification engine
    // ------------------------------------------------------------------

    public function executeBatchVerification(int $scheduleId, string $triggeredBy = 'manual', ?string $triggeredByUser = null): array
    {
        $schedule = DB::table('integrity_schedule')->where('id', $scheduleId)->first();
        if (!$schedule) {
            throw new \RuntimeException("Schedule #{$scheduleId} not found");
        }

        // Overlap prevention
        $runningCount = DB::table('integrity_run')
            ->where('schedule_id', $scheduleId)
            ->where('status', 'running')
            ->count();

        if ($runningCount >= $schedule->max_concurrent_runs) {
            throw new \RuntimeException("Schedule #{$scheduleId} already has {$runningCount} running (max: {$schedule->max_concurrent_runs})");
        }

        // Acquire lock
        $lockToken = bin2hex(random_bytes(16));
        $lockHandle = $this->acquireLock($scheduleId);

        if ($lockHandle === false) {
            throw new \RuntimeException("Could not acquire lock for schedule #{$scheduleId}");
        }

        $now = date('Y-m-d H:i:s');

        // Create run record
        $runId = DB::table('integrity_run')->insertGetId([
            'schedule_id' => $scheduleId,
            'status' => 'running',
            'algorithm' => $schedule->algorithm,
            'triggered_by' => $triggeredBy,
            'triggered_by_user' => $triggeredByUser,
            'lock_token' => $lockToken,
            'started_at' => $now,
            'created_at' => $now,
        ]);

        $counters = [
            'objects_scanned' => 0,
            'objects_passed' => 0,
            'objects_failed' => 0,
            'objects_missing' => 0,
            'objects_error' => 0,
            'objects_skipped' => 0,
            'bytes_scanned' => 0,
        ];

        $finalStatus = 'completed';
        $errorMessage = null;
        $startTime = microtime(true);
        $memoryLimitBytes = $schedule->max_memory_mb * 1024 * 1024;
        $runtimeLimitSec = $schedule->max_runtime_minutes * 60;
        $batchSize = $schedule->batch_size > 0 ? $schedule->batch_size : PHP_INT_MAX;
        $throttleUs = $schedule->io_throttle_ms * 1000;

        try {
            $query = $this->buildScopeQuery($schedule);
            $objects = $query->limit($batchSize)->get();

            foreach ($objects as $idx => $obj) {
                // Memory guard (every 50 objects)
                if ($idx > 0 && $idx % 50 === 0) {
                    if (memory_get_usage(true) > $memoryLimitBytes) {
                        $finalStatus = 'partial';
                        $errorMessage = 'Memory limit reached (' . $schedule->max_memory_mb . 'MB)';
                        break;
                    }
                }

                // Runtime guard
                $elapsed = microtime(true) - $startTime;
                if ($elapsed > $runtimeLimitSec) {
                    $finalStatus = 'timeout';
                    $errorMessage = 'Runtime limit reached (' . $schedule->max_runtime_minutes . ' min)';
                    break;
                }

                // Verify
                $result = $this->verifyObject($obj->id, $runId, $schedule->algorithm);
                $counters['objects_scanned']++;

                switch ($result['outcome'] ?? 'error') {
                    case 'pass':
                        $counters['objects_passed']++;
                        break;
                    case 'mismatch':
                        $counters['objects_failed']++;
                        break;
                    case 'missing':
                        $counters['objects_missing']++;
                        break;
                    case 'no_baseline':
                        $counters['objects_skipped']++;
                        break;
                    default:
                        $counters['objects_error']++;
                        break;
                }

                if (!empty($result['file_size'])) {
                    $counters['bytes_scanned'] += (int) $result['file_size'];
                }

                // IO throttle
                if ($throttleUs > 0) {
                    usleep($throttleUs);
                }
            }
        } catch (\Exception $e) {
            $finalStatus = 'failed';
            $errorMessage = $e->getMessage();
        }

        // Update run record
        DB::table('integrity_run')->where('id', $runId)->update(array_merge($counters, [
            'status' => $finalStatus,
            'error_message' => $errorMessage,
            'completed_at' => date('Y-m-d H:i:s'),
        ]));

        // Update schedule
        DB::table('integrity_schedule')->where('id', $scheduleId)->update([
            'last_run_at' => date('Y-m-d H:i:s'),
            'total_runs' => DB::raw('total_runs + 1'),
        ]);

        // Release lock
        $this->releaseLock($lockHandle, $scheduleId);

        return [
            'run_id' => $runId,
            'status' => $finalStatus,
            'counters' => $counters,
            'error' => $errorMessage,
            'duration_seconds' => round(microtime(true) - $startTime, 2),
        ];
    }

    // ------------------------------------------------------------------
    // Scope query builder
    // ------------------------------------------------------------------

    public function buildScopeQuery(object $schedule)
    {
        $query = DB::table('digital_object as do')
            ->where('do.usage_id', 140); // Master objects only

        switch ($schedule->scope_type) {
            case 'repository':
                if ($schedule->repository_id) {
                    $query->join('information_object as io', 'do.object_id', '=', 'io.id')
                        ->where('io.repository_id', $schedule->repository_id);
                }
                break;

            case 'hierarchy':
                if ($schedule->information_object_id) {
                    // Use lft/rgt range from nested set
                    $parent = DB::table('information_object')
                        ->where('id', $schedule->information_object_id)
                        ->first();

                    if ($parent) {
                        $query->join('information_object as io', 'do.object_id', '=', 'io.id')
                            ->where('io.lft', '>=', $parent->lft)
                            ->where('io.rgt', '<=', $parent->rgt);
                    }
                }
                break;

            // 'global' — no extra filter
        }

        $query->select('do.id');

        return $query;
    }

    // ------------------------------------------------------------------
    // Ad-hoc verification (single object or repository scope)
    // ------------------------------------------------------------------

    public function verifyByObjectId(int $digitalObjectId, string $algorithm = 'sha256', string $triggeredBy = 'manual'): array
    {
        $now = date('Y-m-d H:i:s');
        $runId = DB::table('integrity_run')->insertGetId([
            'schedule_id' => null,
            'status' => 'running',
            'algorithm' => $algorithm,
            'triggered_by' => $triggeredBy,
            'started_at' => $now,
            'created_at' => $now,
        ]);

        $result = $this->verifyObject($digitalObjectId, $runId, $algorithm);
        $passed = ($result['outcome'] ?? '') === 'pass' ? 1 : 0;

        DB::table('integrity_run')->where('id', $runId)->update([
            'status' => 'completed',
            'objects_scanned' => 1,
            'objects_passed' => $passed,
            'objects_failed' => ($result['outcome'] ?? '') === 'mismatch' ? 1 : 0,
            'objects_missing' => ($result['outcome'] ?? '') === 'missing' ? 1 : 0,
            'objects_error' => in_array($result['outcome'] ?? '', ['error', 'unreadable', 'permission_error']) ? 1 : 0,
            'bytes_scanned' => $result['file_size'] ?? 0,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        return $result;
    }

    // ------------------------------------------------------------------
    // Append-only ledger
    // ------------------------------------------------------------------

    protected function appendLedger(?int $runId, int $digitalObjectId, array $data): array
    {
        $row = array_merge([
            'run_id' => $runId,
            'digital_object_id' => $digitalObjectId,
            'information_object_id' => null,
            'repository_id' => null,
            'file_path' => null,
            'file_size' => null,
            'file_exists' => 0,
            'file_readable' => 0,
            'algorithm' => 'sha256',
            'expected_hash' => null,
            'computed_hash' => null,
            'hash_match' => null,
            'outcome' => 'error',
            'error_detail' => null,
            'duration_ms' => null,
            'verified_at' => date('Y-m-d H:i:s'),
        ], $data);

        $row['id'] = DB::table('integrity_ledger')->insertGetId($row);

        return $row;
    }

    // ------------------------------------------------------------------
    // Dead letter queue
    // ------------------------------------------------------------------

    protected function escalateDeadLetter(int $digitalObjectId, string $failureType, ?int $runId, ?string $errorDetail): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = DB::table('integrity_dead_letter')
            ->where('digital_object_id', $digitalObjectId)
            ->where('failure_type', $failureType)
            ->first();

        if ($existing) {
            DB::table('integrity_dead_letter')
                ->where('id', $existing->id)
                ->update([
                    'consecutive_failures' => $existing->consecutive_failures + 1,
                    'last_failure_at' => $now,
                    'last_error_detail' => $errorDetail,
                    'last_run_id' => $runId,
                    'status' => $existing->status === 'resolved' ? 'open' : $existing->status,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('integrity_dead_letter')->insert([
                'digital_object_id' => $digitalObjectId,
                'failure_type' => $failureType,
                'status' => 'open',
                'consecutive_failures' => 1,
                'first_failure_at' => $now,
                'last_failure_at' => $now,
                'last_error_detail' => $errorDetail,
                'last_run_id' => $runId,
                'retry_count' => 0,
                'max_retries' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function clearDeadLetter(int $digitalObjectId): void
    {
        DB::table('integrity_dead_letter')
            ->where('digital_object_id', $digitalObjectId)
            ->where('status', '!=', 'ignored')
            ->update([
                'status' => 'resolved',
                'resolved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function updateDeadLetterStatus(int $deadLetterId, string $status, ?string $notes = null, ?string $user = null): bool
    {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];

        if ($status === 'acknowledged' || $status === 'investigating') {
            $data['acknowledged_by'] = $user;
            $data['acknowledged_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'resolved') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
        }

        if ($notes !== null) {
            $data['resolution_notes'] = $notes;
        }

        return DB::table('integrity_dead_letter')->where('id', $deadLetterId)->update($data) > 0;
    }

    // ------------------------------------------------------------------
    // Lock management (flock + PID stale recovery)
    // ------------------------------------------------------------------

    protected function acquireLock(int $scheduleId)
    {
        $lockFile = $this->lockDir . '/schedule_' . $scheduleId . '.lock';
        $handle = @fopen($lockFile, 'c+');

        if (!$handle) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            // Check for stale lock
            $content = stream_get_contents($handle);
            if ($content && is_numeric(trim($content))) {
                $pid = (int) trim($content);
                if ($pid > 0 && !file_exists("/proc/{$pid}")) {
                    // Stale lock — process is dead, break it
                    flock($handle, LOCK_UN);
                    fclose($handle);

                    $handle = fopen($lockFile, 'c+');
                    if ($handle && flock($handle, LOCK_EX | LOCK_NB)) {
                        ftruncate($handle, 0);
                        fwrite($handle, (string) getmypid());
                        fflush($handle);

                        return $handle;
                    }
                }
            }

            fclose($handle);

            return false;
        }

        // Write our PID
        ftruncate($handle, 0);
        fwrite($handle, (string) getmypid());
        fflush($handle);

        return $handle;
    }

    protected function releaseLock($handle, int $scheduleId): void
    {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $lockFile = $this->lockDir . '/schedule_' . $scheduleId . '.lock';
        @unlink($lockFile);
    }

    // ------------------------------------------------------------------
    // Schedule CRUD
    // ------------------------------------------------------------------

    public function createSchedule(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('integrity_schedule')->insertGetId(array_merge([
            'name' => 'New Schedule',
            'scope_type' => 'global',
            'algorithm' => 'sha256',
            'frequency' => 'weekly',
            'batch_size' => 200,
            'io_throttle_ms' => 0,
            'max_memory_mb' => 512,
            'max_runtime_minutes' => 120,
            'max_concurrent_runs' => 1,
            'is_enabled' => 0,
            'notify_on_failure' => 1,
            'notify_on_mismatch' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $data));
    }

    public function updateSchedule(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('integrity_schedule')->where('id', $id)->update($data) > 0;
    }

    public function deleteSchedule(int $id): bool
    {
        // Don't delete if running
        $running = DB::table('integrity_run')
            ->where('schedule_id', $id)
            ->where('status', 'running')
            ->exists();

        if ($running) {
            throw new \RuntimeException('Cannot delete schedule with active runs');
        }

        return DB::table('integrity_schedule')->where('id', $id)->delete() > 0;
    }

    public function toggleSchedule(int $id): bool
    {
        $schedule = DB::table('integrity_schedule')->where('id', $id)->first();
        if (!$schedule) {
            return false;
        }

        $newState = $schedule->is_enabled ? 0 : 1;
        $data = ['is_enabled' => $newState, 'updated_at' => date('Y-m-d H:i:s')];

        if ($newState && !$schedule->next_run_at) {
            require_once dirname(__FILE__) . '/IntegrityScheduler.php';
            $scheduler = new \IntegrityScheduler();
            $data['next_run_at'] = $scheduler->computeNextRun($schedule->frequency, $schedule->cron_expression);
        }

        return DB::table('integrity_schedule')->where('id', $id)->update($data) > 0;
    }

    // ------------------------------------------------------------------
    // Dashboard statistics
    // ------------------------------------------------------------------

    public function getDashboardStats(): array
    {
        $totalObjects = DB::table('digital_object')->where('usage_id', 140)->count();

        $lastRun = DB::table('integrity_run')
            ->orderByDesc('started_at')
            ->first();

        $recentOutcomes = DB::table('integrity_ledger')
            ->select('outcome', DB::raw('COUNT(*) as cnt'))
            ->where('verified_at', '>=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->groupBy('outcome')
            ->pluck('cnt', 'outcome')
            ->all();

        $openDeadLetters = DB::table('integrity_dead_letter')
            ->whereIn('status', ['open', 'acknowledged', 'investigating'])
            ->count();

        $scheduleCount = DB::table('integrity_schedule')->count();
        $enabledSchedules = DB::table('integrity_schedule')->where('is_enabled', 1)->count();

        $totalVerifications = DB::table('integrity_ledger')->count();
        $totalPassed = DB::table('integrity_ledger')->where('outcome', 'pass')->count();

        return [
            'total_master_objects' => $totalObjects,
            'total_verifications' => $totalVerifications,
            'total_passed' => $totalPassed,
            'pass_rate' => $totalVerifications > 0 ? round(($totalPassed / $totalVerifications) * 100, 1) : null,
            'recent_outcomes' => $recentOutcomes,
            'open_dead_letters' => $openDeadLetters,
            'schedule_count' => $scheduleCount,
            'enabled_schedules' => $enabledSchedules,
            'last_run' => $lastRun,
        ];
    }

    public function getRecentRuns(int $limit = 10): array
    {
        return DB::table('integrity_run')
            ->leftJoin('integrity_schedule', 'integrity_run.schedule_id', '=', 'integrity_schedule.id')
            ->select(
                'integrity_run.*',
                'integrity_schedule.name as schedule_name'
            )
            ->orderByDesc('integrity_run.started_at')
            ->limit($limit)
            ->get()
            ->values()
            ->all();
    }

    public function getRecentFailures(int $limit = 10): array
    {
        return DB::table('integrity_ledger')
            ->where('outcome', '!=', 'pass')
            ->orderByDesc('verified_at')
            ->limit($limit)
            ->get()
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function resolveFilePath(object $doRow): string
    {
        $path = $doRow->path ?? '';
        $name = $doRow->name ?? '';
        $rootDir = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive');

        if ($path && $name) {
            // DB path is relative to sf_root_dir (e.g., /uploads/r/repo/hash/)
            $fullPath = $rootDir . '/' . ltrim($path, '/') . $name;

            return $fullPath;
        }

        return $rootDir . '/uploads/unknown_' . $doRow->id;
    }

    protected function resolveRepositoryId(?int $informationObjectId): ?int
    {
        if (!$informationObjectId) {
            return null;
        }

        $io = DB::table('information_object')
            ->where('id', $informationObjectId)
            ->value('repository_id');

        return $io ?: null;
    }

    protected function elapsed(float $startTime): int
    {
        return (int) ((microtime(true) - $startTime) * 1000);
    }
}
