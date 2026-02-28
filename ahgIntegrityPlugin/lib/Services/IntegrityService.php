<?php

use Illuminate\Database\Capsule\Manager as DB;

class IntegrityService
{
    private string $uploadsPath;
    private string $lockDir;
    private ?object $preservationService = null;
    private string $currentActor = 'system';

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
    // Schema migration (Issue #188)
    // ------------------------------------------------------------------

    public function runMigration(): void
    {
        $dbName = DB::connection()->getDatabaseName();

        // Add actor column if missing
        $hasActor = DB::select(
            "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'integrity_ledger' AND COLUMN_NAME = 'actor'",
            [$dbName]
        );
        if (($hasActor[0]->cnt ?? 0) == 0) {
            DB::statement('ALTER TABLE `integrity_ledger` ADD COLUMN `actor` VARCHAR(255) NULL AFTER `duration_ms`');
        }

        // Add hostname column if missing
        $hasHostname = DB::select(
            "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'integrity_ledger' AND COLUMN_NAME = 'hostname'",
            [$dbName]
        );
        if (($hasHostname[0]->cnt ?? 0) == 0) {
            DB::statement('ALTER TABLE `integrity_ledger` ADD COLUMN `hostname` VARCHAR(255) NULL AFTER `actor`');
        }
    }

    // ------------------------------------------------------------------
    // Single object verification
    // ------------------------------------------------------------------

    public function verifyObject(int $digitalObjectId, ?int $runId, string $algorithm = 'sha256', string $actor = 'system'): array
    {
        $startTime = microtime(true);
        $this->currentActor = $actor;

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
        $actor = $triggeredByUser ?: $triggeredBy;

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

                // Verify with actor tracking
                $result = $this->verifyObject($obj->id, $runId, $schedule->algorithm, $actor);
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

        $runResult = [
            'run_id' => $runId,
            'schedule_id' => $scheduleId,
            'status' => $finalStatus,
            'counters' => $counters,
            'error' => $errorMessage,
            'duration_seconds' => round(microtime(true) - $startTime, 2),
        ];

        // Issue #190: Alert checking (non-fatal)
        try {
            $alertServicePath = dirname(__FILE__) . '/IntegrityAlertService.php';
            if (file_exists($alertServicePath)) {
                require_once $alertServicePath;
                $alertService = new \IntegrityAlertService();
                $alertService->checkThresholds($runResult);
                $alertService->sendScheduleNotification($schedule, $runResult);
            }
        } catch (\Exception $e) {
            // Alert failures are non-fatal — never break verification
        }

        return $runResult;
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

        $result = $this->verifyObject($digitalObjectId, $runId, $algorithm, $triggeredBy);
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
    // Append-only ledger (Issue #188: actor + hostname)
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
            'actor' => $this->currentActor,
            'hostname' => gethostname() ?: null,
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
    // Issue #188: CSV Export
    // ------------------------------------------------------------------

    public function exportLedgerCsv(array $filters = []): string
    {
        $query = DB::table('integrity_ledger');

        if (!empty($filters['date_from'])) {
            $query->where('verified_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $query->where('verified_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['repository_id'])) {
            $query->where('repository_id', (int) $filters['repository_id']);
        }
        if (!empty($filters['outcome'])) {
            $query->where('outcome', $filters['outcome']);
        }
        if (!empty($filters['information_object_id'])) {
            // Hierarchy scope via nested set
            $parent = DB::table('information_object')
                ->where('id', (int) $filters['information_object_id'])
                ->first();
            if ($parent) {
                $query->join('information_object as io', 'integrity_ledger.information_object_id', '=', 'io.id')
                    ->where('io.lft', '>=', $parent->lft)
                    ->where('io.rgt', '<=', $parent->rgt);
            }
        }

        $entries = $query->orderByDesc('verified_at')->limit(50000)->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'id', 'run_id', 'digital_object_id', 'information_object_id', 'repository_id',
            'file_path', 'file_size', 'file_exists', 'file_readable', 'algorithm',
            'expected_hash', 'computed_hash', 'hash_match', 'outcome', 'error_detail',
            'duration_ms', 'actor', 'hostname', 'verified_at',
        ]);

        foreach ($entries as $e) {
            fputcsv($handle, [
                $e->id, $e->run_id, $e->digital_object_id, $e->information_object_id,
                $e->repository_id, $e->file_path, $e->file_size, $e->file_exists,
                $e->file_readable, $e->algorithm, $e->expected_hash, $e->computed_hash,
                $e->hash_match, $e->outcome, $e->error_detail ?? '',
                $e->duration_ms, $e->actor ?? '', $e->hostname ?? '', $e->verified_at,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    // ------------------------------------------------------------------
    // Issue #188: Auditor Pack (ZIP)
    // ------------------------------------------------------------------

    public function generateAuditorPack(array $filters = []): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'integrity_auditor_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // 1. CSV of exceptions (non-pass entries)
        $exFilters = array_merge($filters, ['outcome' => null]);
        $exQuery = DB::table('integrity_ledger')->where('outcome', '!=', 'pass');
        if (!empty($filters['date_from'])) {
            $exQuery->where('verified_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $exQuery->where('verified_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['repository_id'])) {
            $exQuery->where('repository_id', (int) $filters['repository_id']);
        }
        $exceptions = $exQuery->orderByDesc('verified_at')->limit(50000)->get();

        $csvHandle = fopen('php://temp', 'r+');
        fputcsv($csvHandle, ['id', 'digital_object_id', 'outcome', 'file_path', 'error_detail', 'actor', 'hostname', 'verified_at']);
        foreach ($exceptions as $e) {
            fputcsv($csvHandle, [
                $e->id, $e->digital_object_id, $e->outcome, $e->file_path,
                $e->error_detail ?? '', $e->actor ?? '', $e->hostname ?? '', $e->verified_at,
            ]);
        }
        rewind($csvHandle);
        $zip->addFromString('exceptions.csv', stream_get_contents($csvHandle));
        fclose($csvHandle);

        // 2. Config snapshot
        $schedules = DB::table('integrity_schedule')->get()->values()->all();
        $deadLetterCounts = DB::table('integrity_dead_letter')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();
        $stats = $this->getDashboardStats();

        $configSnapshot = [
            'generated_at' => date('Y-m-d H:i:s'),
            'hostname' => gethostname(),
            'filters' => $filters,
            'schedules' => $schedules,
            'dead_letter_summary' => $deadLetterCounts,
            'stats' => $stats,
        ];
        $zip->addFromString('config-snapshot.json', json_encode($configSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 3. Summary HTML
        $summaryHtml = $this->renderAuditorSummaryHtml($stats, $exceptions->count(), $schedules, $filters);
        $zip->addFromString('summary.html', $summaryHtml);

        $zip->close();

        return $tmpFile;
    }

    public function renderAuditorSummaryHtml(array $stats, int $exceptionCount, array $schedules, array $filters): string
    {
        $date = date('Y-m-d H:i:s');
        $host = htmlspecialchars(gethostname() ?: 'unknown');
        $filterDesc = !empty($filters) ? htmlspecialchars(json_encode($filters)) : 'None';
        $passRate = $stats['pass_rate'] ?? 'N/A';
        $totalVerifications = number_format($stats['total_verifications'] ?? 0);
        $totalPassed = number_format($stats['total_passed'] ?? 0);
        $totalObjects = number_format($stats['total_master_objects'] ?? 0);
        $openDL = $stats['open_dead_letters'] ?? 0;
        $scheduleCount = count($schedules);

        $scheduleRows = '';
        foreach ($schedules as $s) {
            $name = htmlspecialchars($s->name ?? '');
            $enabled = $s->is_enabled ? 'Yes' : 'No';
            $scheduleRows .= "<tr><td>{$s->id}</td><td>{$name}</td><td>{$s->scope_type}</td><td>{$s->frequency}</td><td>{$enabled}</td><td>{$s->total_runs}</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Integrity Auditor Pack - Summary</title>
<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 2em auto; color: #333; }
h1 { color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 0.3em; }
h2 { color: #2c3e50; margin-top: 1.5em; }
table { border-collapse: collapse; width: 100%; margin: 1em 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f4f6f7; }
.stat { display: inline-block; margin: 0.5em 1em 0.5em 0; padding: 0.8em 1.2em; background: #eaf2f8; border-radius: 6px; }
.stat .label { font-size: 0.85em; color: #666; }
.stat .value { font-size: 1.4em; font-weight: bold; }
.warn { color: #e74c3c; }
.ok { color: #27ae60; }
.meta { color: #888; font-size: 0.9em; }
</style>
</head>
<body>
<h1>Integrity Assurance - Auditor Pack</h1>
<p class="meta">Generated: {$date} | Host: {$host} | Filters: {$filterDesc}</p>

<h2>Summary</h2>
<div>
  <div class="stat"><div class="label">Master Objects</div><div class="value">{$totalObjects}</div></div>
  <div class="stat"><div class="label">Total Verifications</div><div class="value">{$totalVerifications}</div></div>
  <div class="stat"><div class="label">Total Passed</div><div class="value">{$totalPassed}</div></div>
  <div class="stat"><div class="label">Pass Rate</div><div class="value {$this->passRateClass($stats['pass_rate'] ?? null)}">{$passRate}%</div></div>
  <div class="stat"><div class="label">Open Dead Letters</div><div class="value {$this->deadLetterClass($openDL)}">{$openDL}</div></div>
  <div class="stat"><div class="label">Exceptions in Export</div><div class="value">{$exceptionCount}</div></div>
</div>

<h2>Schedules ({$scheduleCount})</h2>
<table>
<thead><tr><th>ID</th><th>Name</th><th>Scope</th><th>Frequency</th><th>Enabled</th><th>Total Runs</th></tr></thead>
<tbody>{$scheduleRows}</tbody>
</table>

<h2>Included Files</h2>
<ul>
<li><strong>summary.html</strong> - This document</li>
<li><strong>exceptions.csv</strong> - All non-pass verification ledger entries</li>
<li><strong>config-snapshot.json</strong> - Schedule configuration, dead letter summary, and current statistics</li>
</ul>

<p class="meta">AtoM Heratio - Integrity Assurance Plugin v1.1.0 | The Archive and Heritage Group (Pty) Ltd</p>
</body>
</html>
HTML;
    }

    private function passRateClass(?float $rate): string
    {
        if ($rate === null) {
            return '';
        }

        return $rate < 95 ? 'warn' : 'ok';
    }

    private function deadLetterClass(int $count): string
    {
        return $count > 0 ? 'warn' : 'ok';
    }

    // ------------------------------------------------------------------
    // Issue #190: Enhanced statistics
    // ------------------------------------------------------------------

    public function calculateBacklog(): int
    {
        // Master DOs not in ledger (never verified)
        $verified = DB::table('integrity_ledger')
            ->select('digital_object_id')
            ->distinct();

        return DB::table('digital_object')
            ->where('usage_id', 140)
            ->whereNotIn('id', $verified)
            ->count();
    }

    public function calculateThroughput(int $days = 7): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $runs = DB::table('integrity_run')
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since)
            ->whereNotNull('completed_at')
            ->get();

        $totalObjects = 0;
        $totalBytes = 0;
        $totalSeconds = 0;

        foreach ($runs as $run) {
            $totalObjects += $run->objects_scanned;
            $totalBytes += $run->bytes_scanned;

            $started = strtotime($run->started_at);
            $completed = strtotime($run->completed_at);
            if ($started && $completed && $completed > $started) {
                $totalSeconds += ($completed - $started);
            }
        }

        $totalHours = $totalSeconds > 0 ? $totalSeconds / 3600 : 0;

        return [
            'objects_per_hour' => $totalHours > 0 ? round($totalObjects / $totalHours, 1) : 0,
            'bytes_per_hour' => $totalHours > 0 ? round($totalBytes / $totalHours) : 0,
            'gb_per_hour' => $totalHours > 0 ? round($totalBytes / $totalHours / 1073741824, 2) : 0,
            'total_objects' => $totalObjects,
            'total_bytes' => $totalBytes,
            'total_hours' => round($totalHours, 2),
            'runs_completed' => count($runs),
        ];
    }

    public function getDailyTrend(int $days = 30): array
    {
        return DB::table('integrity_ledger')
            ->select(
                DB::raw('DATE(verified_at) as day'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN outcome = 'pass' THEN 1 ELSE 0 END) as passed"),
                DB::raw("SUM(CASE WHEN outcome != 'pass' THEN 1 ELSE 0 END) as failed")
            )
            ->where('verified_at', '>=', date('Y-m-d', strtotime("-{$days} days")))
            ->groupBy(DB::raw('DATE(verified_at)'))
            ->orderBy('day')
            ->get()
            ->values()
            ->all();
    }

    public function getFailureTypeBreakdown(int $days = 30): array
    {
        return DB::table('integrity_ledger')
            ->select('outcome', DB::raw('COUNT(*) as cnt'))
            ->where('outcome', '!=', 'pass')
            ->where('verified_at', '>=', date('Y-m-d', strtotime("-{$days} days")))
            ->groupBy('outcome')
            ->orderByDesc('cnt')
            ->get()
            ->values()
            ->all();
    }

    public function getRepositoryBreakdown(): array
    {
        return DB::table('integrity_ledger as il')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('ai.id', '=', 'il.repository_id')
                  ->where('ai.culture', '=', 'en');
            })
            ->select(
                'il.repository_id',
                DB::raw("COALESCE(ai.authorized_form_of_name, CONCAT('Repository #', il.repository_id), 'Unknown') as repo_name"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN il.outcome = 'pass' THEN 1 ELSE 0 END) as passed"),
                DB::raw("SUM(CASE WHEN il.outcome != 'pass' THEN 1 ELSE 0 END) as failed")
            )
            ->whereNotNull('il.repository_id')
            ->groupBy('il.repository_id', 'ai.authorized_form_of_name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->pass_rate = $row->total > 0 ? round(($row->passed / $row->total) * 100, 1) : 0;

                return $row;
            })
            ->values()
            ->all();
    }

    public function getFormatBreakdown(): array
    {
        try {
            return DB::table('integrity_ledger as il')
                ->join('preservation_object_format as pof', 'il.digital_object_id', '=', 'pof.digital_object_id')
                ->select(
                    'pof.format_name',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN il.outcome = 'pass' THEN 1 ELSE 0 END) as passed"),
                    DB::raw("SUM(CASE WHEN il.outcome != 'pass' THEN 1 ELSE 0 END) as failed")
                )
                ->groupBy('pof.format_name')
                ->orderByDesc('total')
                ->limit(20)
                ->get()
                ->values()
                ->all();
        } catch (\Exception $e) {
            // preservation_object_format table may not exist
            return [];
        }
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
