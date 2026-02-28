<?php

use Illuminate\Database\Capsule\Manager as DB;

class IntegrityRetentionService
{
    // ------------------------------------------------------------------
    // Policy CRUD (Issue #189)
    // ------------------------------------------------------------------

    public function listPolicies(): array
    {
        return DB::table('integrity_retention_policy')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();
    }

    public function getPolicy(int $id): ?object
    {
        return DB::table('integrity_retention_policy')->where('id', $id)->first();
    }

    public function createPolicy(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('integrity_retention_policy')->insertGetId(array_merge([
            'name' => 'New Policy',
            'retention_period_days' => 0,
            'trigger_type' => 'ingest_date',
            'scope_type' => 'global',
            'repository_id' => null,
            'information_object_id' => null,
            'is_enabled' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $data));
    }

    public function updatePolicy(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('integrity_retention_policy')->where('id', $id)->update($data) > 0;
    }

    public function deletePolicy(int $id): bool
    {
        // Also remove disposition queue entries for this policy
        DB::table('integrity_disposition_queue')->where('policy_id', $id)->delete();

        return DB::table('integrity_retention_policy')->where('id', $id)->delete() > 0;
    }

    public function togglePolicy(int $id): bool
    {
        $policy = $this->getPolicy($id);
        if (!$policy) {
            return false;
        }

        return DB::table('integrity_retention_policy')
            ->where('id', $id)
            ->update([
                'is_enabled' => $policy->is_enabled ? 0 : 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // ------------------------------------------------------------------
    // Legal holds
    // ------------------------------------------------------------------

    public function placeHold(int $informationObjectId, string $reason, string $placedBy): int
    {
        $now = date('Y-m-d H:i:s');

        $holdId = DB::table('integrity_legal_hold')->insertGetId([
            'information_object_id' => $informationObjectId,
            'reason' => $reason,
            'placed_by' => $placedBy,
            'placed_at' => $now,
            'status' => 'active',
            'created_at' => $now,
        ]);

        // Block any matching disposition queue entries
        DB::table('integrity_disposition_queue')
            ->where('information_object_id', $informationObjectId)
            ->whereIn('status', ['eligible', 'pending_review'])
            ->update([
                'status' => 'held',
                'updated_at' => $now,
            ]);

        // Log to ledger
        $this->logToLedger($informationObjectId, 'legal_hold_placed', "Hold #{$holdId} placed by {$placedBy}: {$reason}");

        return $holdId;
    }

    public function releaseHold(int $holdId, string $releasedBy): bool
    {
        $hold = DB::table('integrity_legal_hold')->where('id', $holdId)->first();
        if (!$hold || $hold->status !== 'active') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        DB::table('integrity_legal_hold')->where('id', $holdId)->update([
            'released_by' => $releasedBy,
            'released_at' => $now,
            'status' => 'released',
        ]);

        // Re-evaluate disposition queue entries for this IO
        // Only revert to 'eligible' if no other active holds exist
        if (!$this->isUnderHold($hold->information_object_id)) {
            DB::table('integrity_disposition_queue')
                ->where('information_object_id', $hold->information_object_id)
                ->where('status', 'held')
                ->update([
                    'status' => 'eligible',
                    'updated_at' => $now,
                ]);
        }

        $this->logToLedger($hold->information_object_id, 'legal_hold_released', "Hold #{$holdId} released by {$releasedBy}");

        return true;
    }

    public function listHolds(array $filters = []): array
    {
        $query = DB::table('integrity_legal_hold');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('placed_at')
            ->limit(200)
            ->get()
            ->values()
            ->all();
    }

    public function isUnderHold(int $informationObjectId): bool
    {
        return DB::table('integrity_legal_hold')
            ->where('information_object_id', $informationObjectId)
            ->where('status', 'active')
            ->exists();
    }

    // ------------------------------------------------------------------
    // Disposition queue
    // ------------------------------------------------------------------

    public function scanEligible(?int $policyId = null): int
    {
        $query = DB::table('integrity_retention_policy')->where('is_enabled', 1);
        if ($policyId) {
            $query->where('id', $policyId);
        }
        $policies = $query->get();

        $totalQueued = 0;

        foreach ($policies as $policy) {
            if ($policy->retention_period_days <= 0) {
                continue; // Indefinite retention — never eligible
            }

            $cutoff = date('Y-m-d H:i:s', strtotime("-{$policy->retention_period_days} days"));

            // Build scope query for information objects
            $ioQuery = DB::table('information_object as io');

            // Apply trigger type
            switch ($policy->trigger_type) {
                case 'ingest_date':
                    $ioQuery->where('io.created_at', '<=', $cutoff);
                    break;
                case 'last_modified':
                    $ioQuery->where('io.updated_at', '<=', $cutoff);
                    break;
                case 'closure_date':
                    // Closure date is not a standard field — use created_at as fallback
                    $ioQuery->where('io.created_at', '<=', $cutoff);
                    break;
                case 'last_access':
                    $ioQuery->where('io.updated_at', '<=', $cutoff);
                    break;
            }

            // Apply scope
            switch ($policy->scope_type) {
                case 'repository':
                    if ($policy->repository_id) {
                        $ioQuery->where('io.repository_id', $policy->repository_id);
                    }
                    break;
                case 'hierarchy':
                    if ($policy->information_object_id) {
                        $parent = DB::table('information_object')
                            ->where('id', $policy->information_object_id)
                            ->first();
                        if ($parent) {
                            $ioQuery->where('io.lft', '>=', $parent->lft)
                                ->where('io.rgt', '<=', $parent->rgt);
                        }
                    }
                    break;
                // 'global' — no extra filter
            }

            // Exclude already-queued IOs for this policy
            $existingIos = DB::table('integrity_disposition_queue')
                ->where('policy_id', $policy->id)
                ->pluck('information_object_id')
                ->all();

            if (!empty($existingIos)) {
                $ioQuery->whereNotIn('io.id', $existingIos);
            }

            // Root IO (id=1) is always excluded
            $ioQuery->where('io.id', '>', 1);

            $eligibleIos = $ioQuery->select('io.id')->limit(10000)->get();

            $now = date('Y-m-d H:i:s');

            foreach ($eligibleIos as $io) {
                // Skip if under legal hold
                if ($this->isUnderHold($io->id)) {
                    continue;
                }

                // Get associated digital object if any
                $doId = DB::table('digital_object')
                    ->where('object_id', $io->id)
                    ->where('usage_id', 140)
                    ->value('id');

                DB::table('integrity_disposition_queue')->insert([
                    'policy_id' => $policy->id,
                    'information_object_id' => $io->id,
                    'digital_object_id' => $doId,
                    'status' => 'eligible',
                    'eligible_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $totalQueued++;
            }
        }

        return $totalQueued;
    }

    public function listDispositionQueue(array $filters = []): array
    {
        $query = DB::table('integrity_disposition_queue as dq')
            ->leftJoin('integrity_retention_policy as rp', 'dq.policy_id', '=', 'rp.id')
            ->select('dq.*', 'rp.name as policy_name');

        if (!empty($filters['status'])) {
            $query->where('dq.status', $filters['status']);
        }
        if (!empty($filters['policy_id'])) {
            $query->where('dq.policy_id', (int) $filters['policy_id']);
        }

        return $query->orderByDesc('dq.eligible_at')
            ->limit(200)
            ->get()
            ->values()
            ->all();
    }

    public function reviewDisposition(int $id, string $status, string $reviewedBy, ?string $notes = null): bool
    {
        $entry = DB::table('integrity_disposition_queue')->where('id', $id)->first();
        if (!$entry) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        DB::table('integrity_disposition_queue')->where('id', $id)->update([
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $now,
            'review_notes' => $notes,
            'updated_at' => $now,
        ]);

        $this->logToLedger($entry->information_object_id, "disposition_{$status}",
            "Disposition #{$id} {$status} by {$reviewedBy}" . ($notes ? ": {$notes}" : ''));

        return true;
    }

    public function processApprovedDispositions(): int
    {
        $approved = DB::table('integrity_disposition_queue')
            ->where('status', 'approved')
            ->get();

        $processed = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($approved as $entry) {
            // Check for active hold before proceeding
            if ($this->isUnderHold($entry->information_object_id)) {
                DB::table('integrity_disposition_queue')
                    ->where('id', $entry->id)
                    ->update(['status' => 'held', 'updated_at' => $now]);
                continue;
            }

            // Mark as disposed — NO actual deletion
            DB::table('integrity_disposition_queue')
                ->where('id', $entry->id)
                ->update(['status' => 'disposed', 'updated_at' => $now]);

            $this->logToLedger($entry->information_object_id, 'disposition_disposed',
                "Disposition #{$entry->id} marked as disposed (no actual deletion)");

            $processed++;
        }

        return $processed;
    }

    public function getDispositionStats(): array
    {
        return DB::table('integrity_disposition_queue')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();
    }

    // ------------------------------------------------------------------
    // Ledger integration
    // ------------------------------------------------------------------

    protected function logToLedger(int $informationObjectId, string $outcomeNote, string $detail): void
    {
        try {
            DB::table('integrity_ledger')->insert([
                'run_id' => null,
                'digital_object_id' => 0,
                'information_object_id' => $informationObjectId,
                'repository_id' => $this->resolveRepositoryId($informationObjectId),
                'outcome' => 'pass',
                'error_detail' => "[retention] {$outcomeNote}: {$detail}",
                'actor' => 'retention_service',
                'hostname' => gethostname() ?: null,
                'verified_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Non-fatal logging
        }
    }

    protected function resolveRepositoryId(?int $informationObjectId): ?int
    {
        if (!$informationObjectId) {
            return null;
        }

        return DB::table('information_object')
            ->where('id', $informationObjectId)
            ->value('repository_id') ?: null;
    }
}
