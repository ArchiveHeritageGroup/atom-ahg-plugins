<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Migration Plan Service
 *
 * Handles migration plan CRUD operations, batch execution tracking,
 * and coordination of format migration workflows.
 */
class MigrationPlanService
{
    private MigrationPathwayService $pathwayService;

    public function __construct()
    {
        require_once __DIR__.'/MigrationPathwayService.php';
        $this->pathwayService = new MigrationPathwayService();
    }

    // =========================================
    // PLAN CRUD OPERATIONS
    // =========================================

    /**
     * Get all migration plans with summary info.
     *
     * @param array $filters Optional filters: status, source_puid, target_puid
     *
     * @return array
     */
    public function getPlans(array $filters = []): array
    {
        $query = DB::table('preservation_migration_plan as mp')
            ->leftJoin('preservation_migration_pathway as pw', 'mp.pathway_id', '=', 'pw.id')
            ->leftJoin('preservation_format as sf', 'mp.source_puid', '=', 'sf.puid')
            ->leftJoin('preservation_format as tf', 'mp.target_puid', '=', 'tf.puid')
            ->select([
                'mp.*',
                'pw.migration_tool',
                'pw.quality_impact',
                'sf.format_name as source_format_name',
                'sf.mime_type as source_mime_type',
                'tf.format_name as target_format_name',
                'tf.mime_type as target_mime_type',
            ]);

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('mp.status', $filters['status']);
            } else {
                $query->where('mp.status', $filters['status']);
            }
        }

        if (!empty($filters['source_puid'])) {
            $query->where('mp.source_puid', $filters['source_puid']);
        }

        if (!empty($filters['target_puid'])) {
            $query->where('mp.target_puid', $filters['target_puid']);
        }

        $query->orderByRaw("FIELD(mp.status, 'in_progress', 'approved', 'draft', 'completed', 'cancelled', 'failed')")
            ->orderBy('mp.created_at', 'desc');

        return $query->get()->toArray();
    }

    /**
     * Get a specific migration plan by ID.
     *
     * @param int $planId
     *
     * @return object|null
     */
    public function getPlan(int $planId): ?object
    {
        $plan = DB::table('preservation_migration_plan as mp')
            ->leftJoin('preservation_migration_pathway as pw', 'mp.pathway_id', '=', 'pw.id')
            ->leftJoin('preservation_format as sf', 'mp.source_puid', '=', 'sf.puid')
            ->leftJoin('preservation_format as tf', 'mp.target_puid', '=', 'tf.puid')
            ->where('mp.id', $planId)
            ->select([
                'mp.*',
                'pw.migration_tool',
                'pw.migration_command',
                'pw.quality_impact',
                'pw.fidelity_score',
                'sf.format_name as source_format_name',
                'sf.mime_type as source_mime_type',
                'tf.format_name as target_format_name',
                'tf.mime_type as target_mime_type',
                'tf.is_preservation_format',
            ])
            ->first();

        return $plan;
    }

    /**
     * Create a new migration plan.
     *
     * @param array $data Plan data
     *
     * @return int New plan ID
     */
    public function createPlan(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        // Validate source and target PUIDs
        $sourcePuid = $data['source_puid'] ?? null;
        $targetPuid = $data['target_puid'] ?? null;

        if (!$sourcePuid || !$targetPuid) {
            throw new InvalidArgumentException('Source and target PUIDs are required');
        }

        // Get pathway if not specified
        $pathwayId = $data['pathway_id'] ?? null;
        if (!$pathwayId) {
            $pathway = $this->pathwayService->getRecommendedPathway($sourcePuid);
            if ($pathway && $pathway->target_puid === $targetPuid) {
                $pathwayId = $pathway->id;
            }
        }

        $planId = DB::table('preservation_migration_plan')->insertGetId([
            'name' => $data['name'] ?? "Migration from $sourcePuid to $targetPuid",
            'description' => $data['description'] ?? null,
            'source_puid' => $sourcePuid,
            'target_puid' => $targetPuid,
            'pathway_id' => $pathwayId,
            'status' => 'draft',
            'scope_type' => $data['scope_type'] ?? 'all',
            'scope_criteria' => isset($data['scope_criteria']) ? json_encode($data['scope_criteria']) : null,
            'keep_originals' => $data['keep_originals'] ?? 1,
            'create_preservation_copies' => $data['create_preservation_copies'] ?? 1,
            'run_fixity_after' => $data['run_fixity_after'] ?? 1,
            'max_concurrent' => $data['max_concurrent'] ?? 5,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $now,
        ]);

        return $planId;
    }

    /**
     * Update a migration plan.
     *
     * @param int   $planId
     * @param array $data
     *
     * @return bool
     */
    public function updatePlan(int $planId, array $data): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return false;
        }

        // Only allow updates to draft plans (except status changes)
        if (!in_array($plan->status, ['draft', 'approved']) && !isset($data['status'])) {
            throw new RuntimeException('Cannot modify plan in status: '.$plan->status);
        }

        $updateData = [];

        $allowedFields = [
            'name', 'description', 'pathway_id', 'scope_type', 'scope_criteria',
            'keep_originals', 'create_preservation_copies', 'run_fixity_after',
            'max_concurrent', 'scheduled_start',
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'scope_criteria' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a migration plan.
     *
     * @param int $planId
     *
     * @return bool
     */
    public function deletePlan(int $planId): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return false;
        }

        // Only allow deletion of draft or cancelled plans
        if (!in_array($plan->status, ['draft', 'cancelled', 'failed'])) {
            throw new RuntimeException('Cannot delete plan in status: '.$plan->status);
        }

        // Delete plan objects first (cascade should handle this, but be explicit)
        DB::table('preservation_migration_plan_object')
            ->where('plan_id', $planId)
            ->delete();

        return DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->delete() > 0;
    }

    // =========================================
    // PLAN WORKFLOW
    // =========================================

    /**
     * Approve a migration plan for execution.
     *
     * @param int $planId
     * @param int $approvedBy User ID
     *
     * @return bool
     */
    public function approvePlan(int $planId, int $approvedBy): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan || $plan->status !== 'draft') {
            return false;
        }

        // Populate the plan with objects to migrate
        $this->populatePlanObjects($planId);

        $now = date('Y-m-d H:i:s');

        return DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => $now,
            ]) > 0;
    }

    /**
     * Start executing a migration plan.
     *
     * @param int $planId
     *
     * @return bool
     */
    public function startPlan(int $planId): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan || $plan->status !== 'approved') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        return DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->update([
                'status' => 'in_progress',
                'started_at' => $now,
            ]) > 0;
    }

    /**
     * Complete a migration plan.
     *
     * @param int $planId
     *
     * @return bool
     */
    public function completePlan(int $planId): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan || $plan->status !== 'in_progress') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Check if all objects are processed
        $remaining = DB::table('preservation_migration_plan_object')
            ->where('plan_id', $planId)
            ->whereIn('status', ['pending', 'queued', 'processing'])
            ->count();

        if ($remaining > 0) {
            return false;
        }

        return DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->update([
                'status' => 'completed',
                'completed_at' => $now,
            ]) > 0;
    }

    /**
     * Cancel a migration plan.
     *
     * @param int    $planId
     * @param string $reason
     *
     * @return bool
     */
    public function cancelPlan(int $planId, string $reason = ''): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return false;
        }

        if (in_array($plan->status, ['completed', 'cancelled'])) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Cancel any pending/queued objects
        DB::table('preservation_migration_plan_object')
            ->where('plan_id', $planId)
            ->whereIn('status', ['pending', 'queued'])
            ->update([
                'status' => 'skipped',
                'error_message' => 'Plan cancelled: '.$reason,
                'updated_at' => $now,
            ]);

        return DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->update([
                'status' => 'cancelled',
                'completed_at' => $now,
            ]) > 0;
    }

    // =========================================
    // PLAN OBJECT MANAGEMENT
    // =========================================

    /**
     * Populate plan with objects matching the scope criteria.
     *
     * @param int $planId
     *
     * @return int Number of objects added
     */
    public function populatePlanObjects(int $planId): int
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');

        // Build query for objects with the source format
        $query = DB::table('preservation_object_format as pof')
            ->join('digital_object as do', 'pof.digital_object_id', '=', 'do.id')
            ->where('pof.puid', $plan->source_puid)
            ->select([
                'do.id as digital_object_id',
                'do.path',
                'do.byte_size',
            ]);

        // Apply scope criteria
        if ($plan->scope_criteria) {
            $criteria = json_decode($plan->scope_criteria, true);

            if (!empty($criteria['repository_id'])) {
                $query->join('information_object as io', 'do.information_object_id', '=', 'io.id')
                    ->where('io.repository_id', $criteria['repository_id']);
            }

            if (!empty($criteria['collection_id'])) {
                $query->join('information_object as io', 'do.information_object_id', '=', 'io.id')
                    ->where('io.lft', '>=', DB::raw("(SELECT lft FROM information_object WHERE id = {$criteria['collection_id']})"))
                    ->where('io.rgt', '<=', DB::raw("(SELECT rgt FROM information_object WHERE id = {$criteria['collection_id']})"));
            }
        }

        $objects = $query->get();
        $added = 0;
        $totalSize = 0;

        foreach ($objects as $obj) {
            // Check if already in plan
            $exists = DB::table('preservation_migration_plan_object')
                ->where('plan_id', $planId)
                ->where('digital_object_id', $obj->digital_object_id)
                ->exists();

            if (!$exists) {
                // Get current checksum if available
                $checksum = DB::table('preservation_checksum')
                    ->where('digital_object_id', $obj->digital_object_id)
                    ->where('algorithm', 'sha256')
                    ->value('checksum_value');

                DB::table('preservation_migration_plan_object')->insert([
                    'plan_id' => $planId,
                    'digital_object_id' => $obj->digital_object_id,
                    'status' => 'pending',
                    'source_path' => $obj->path,
                    'source_size' => $obj->byte_size,
                    'source_checksum' => $checksum,
                    'created_at' => $now,
                ]);

                ++$added;
                $totalSize += $obj->byte_size ?? 0;
            }
        }

        // Update plan totals
        DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->update([
                'total_objects' => DB::raw('total_objects + '.$added),
                'original_size_bytes' => DB::raw('original_size_bytes + '.$totalSize),
            ]);

        return $added;
    }

    /**
     * Get objects in a migration plan.
     *
     * @param int    $planId
     * @param string $status  Optional status filter
     * @param int    $limit   Limit results
     * @param int    $offset  Offset
     *
     * @return array
     */
    public function getPlanObjects(int $planId, ?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $query = DB::table('preservation_migration_plan_object as mpo')
            ->join('digital_object as do', 'mpo.digital_object_id', '=', 'do.id')
            ->where('mpo.plan_id', $planId)
            ->select([
                'mpo.*',
                'do.name as object_name',
                'do.path as current_path',
            ]);

        if ($status) {
            $query->where('mpo.status', $status);
        }

        return $query->orderBy('mpo.id', 'asc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    /**
     * Get next batch of objects to process.
     *
     * @param int $planId
     * @param int $batchSize
     *
     * @return array
     */
    public function getNextBatch(int $planId, int $batchSize = 10): array
    {
        $now = date('Y-m-d H:i:s');

        // Get pending objects and mark as queued
        $objects = DB::table('preservation_migration_plan_object')
            ->where('plan_id', $planId)
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->limit($batchSize)
            ->get();

        if ($objects->isEmpty()) {
            return [];
        }

        $ids = $objects->pluck('id')->toArray();

        DB::table('preservation_migration_plan_object')
            ->whereIn('id', $ids)
            ->update([
                'status' => 'queued',
                'queued_at' => $now,
                'updated_at' => $now,
            ]);

        // Update plan queued count
        DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->increment('objects_queued', count($ids));

        return $objects->toArray();
    }

    /**
     * Update object status after processing.
     *
     * @param int   $objectId Plan object ID
     * @param array $result   Processing result
     *
     * @return bool
     */
    public function updateObjectStatus(int $objectId, array $result): bool
    {
        $now = date('Y-m-d H:i:s');

        $planObject = DB::table('preservation_migration_plan_object')
            ->where('id', $objectId)
            ->first();

        if (!$planObject) {
            return false;
        }

        $status = $result['success'] ? 'completed' : 'failed';
        $updateData = [
            'status' => $status,
            'completed_at' => $now,
            'duration_ms' => $result['duration_ms'] ?? null,
            'updated_at' => $now,
        ];

        if ($result['success']) {
            $updateData['output_path'] = $result['output_path'] ?? null;
            $updateData['output_size'] = $result['output_size'] ?? null;
            $updateData['output_checksum'] = $result['output_checksum'] ?? null;
        } else {
            $updateData['error_message'] = $result['error'] ?? 'Unknown error';
            $updateData['retry_count'] = DB::raw('retry_count + 1');
        }

        DB::table('preservation_migration_plan_object')
            ->where('id', $objectId)
            ->update($updateData);

        // Update plan counters
        $planId = $planObject->plan_id;
        $incrementField = $result['success'] ? 'objects_succeeded' : 'objects_failed';

        DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->increment('objects_processed');

        DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->increment($incrementField);

        if ($result['success'] && isset($result['output_size'])) {
            DB::table('preservation_migration_plan')
                ->where('id', $planId)
                ->increment('converted_size_bytes', $result['output_size']);
        }

        return true;
    }

    // =========================================
    // PLAN REPORTING
    // =========================================

    /**
     * Get plan progress summary.
     *
     * @param int $planId
     *
     * @return array
     */
    public function getPlanProgress(int $planId): array
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return [];
        }

        // Get status breakdown
        $statusBreakdown = DB::table('preservation_migration_plan_object')
            ->where('plan_id', $planId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($statusBreakdown);
        $completed = ($statusBreakdown['completed'] ?? 0);
        $failed = ($statusBreakdown['failed'] ?? 0);
        $processed = $completed + $failed + ($statusBreakdown['skipped'] ?? 0);

        $percentComplete = $total > 0 ? round(($processed / $total) * 100, 1) : 0;
        $successRate = $processed > 0 ? round(($completed / $processed) * 100, 1) : 0;

        return [
            'plan_id' => $planId,
            'plan_name' => $plan->name,
            'status' => $plan->status,
            'total_objects' => $total,
            'status_breakdown' => $statusBreakdown,
            'percent_complete' => $percentComplete,
            'success_rate' => $successRate,
            'original_size_bytes' => $plan->original_size_bytes,
            'converted_size_bytes' => $plan->converted_size_bytes,
            'size_difference' => $plan->converted_size_bytes - $plan->original_size_bytes,
            'started_at' => $plan->started_at,
            'elapsed_time' => $plan->started_at ? time() - strtotime($plan->started_at) : null,
        ];
    }

    /**
     * Get migration statistics across all plans.
     *
     * @return array
     */
    public function getOverallStats(): array
    {
        $totalPlans = DB::table('preservation_migration_plan')->count();

        $byStatus = DB::table('preservation_migration_plan')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $completedPlans = DB::table('preservation_migration_plan')
            ->where('status', 'completed')
            ->get();

        $totalConverted = 0;
        $totalSucceeded = 0;
        $totalFailed = 0;
        $totalSizeOriginal = 0;
        $totalSizeConverted = 0;

        foreach ($completedPlans as $plan) {
            $totalConverted += $plan->total_objects;
            $totalSucceeded += $plan->objects_succeeded;
            $totalFailed += $plan->objects_failed;
            $totalSizeOriginal += $plan->original_size_bytes;
            $totalSizeConverted += $plan->converted_size_bytes;
        }

        return [
            'total_plans' => $totalPlans,
            'plans_by_status' => $byStatus,
            'total_objects_converted' => $totalConverted,
            'total_succeeded' => $totalSucceeded,
            'total_failed' => $totalFailed,
            'overall_success_rate' => $totalConverted > 0 ? round(($totalSucceeded / $totalConverted) * 100, 1) : 0,
            'total_original_size' => $totalSizeOriginal,
            'total_converted_size' => $totalSizeConverted,
            'storage_saved' => $totalSizeOriginal - $totalSizeConverted,
        ];
    }

    /**
     * Get failed objects from a plan for review/retry.
     *
     * @param int $planId
     *
     * @return array
     */
    public function getFailedObjects(int $planId): array
    {
        return DB::table('preservation_migration_plan_object as mpo')
            ->join('digital_object as do', 'mpo.digital_object_id', '=', 'do.id')
            ->where('mpo.plan_id', $planId)
            ->where('mpo.status', 'failed')
            ->select([
                'mpo.*',
                'do.name as object_name',
            ])
            ->orderBy('mpo.retry_count', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Reset failed objects for retry.
     *
     * @param int   $planId
     * @param array $objectIds Optional specific object IDs to reset
     *
     * @return int Number of objects reset
     */
    public function resetFailedObjects(int $planId, array $objectIds = []): int
    {
        $query = DB::table('preservation_migration_plan_object')
            ->where('plan_id', $planId)
            ->where('status', 'failed');

        if (!empty($objectIds)) {
            $query->whereIn('id', $objectIds);
        }

        $count = $query->count();

        $query->update([
            'status' => 'pending',
            'error_message' => null,
            'output_path' => null,
            'output_size' => null,
            'output_checksum' => null,
            'queued_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'duration_ms' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Update plan counters
        DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->decrement('objects_processed', $count);

        DB::table('preservation_migration_plan')
            ->where('id', $planId)
            ->decrement('objects_failed', $count);

        return $count;
    }
}
