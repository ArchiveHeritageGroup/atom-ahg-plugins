<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SnapshotService - Research Snapshot Management
 *
 * Handles creation, comparison, and management of immutable research snapshots.
 * Snapshots capture the state of a collection at a point in time, enabling
 * researchers to track changes to archival records over time.
 *
 * Enhancement 1 (Issue 159): Truly reproducible snapshots with frozen immutability,
 * full-state hashing, hash verification, and stable citation IDs.
 *
 * @package ahgResearchPlugin
 * @version 3.0.0
 */
class SnapshotService
{
    // =========================================================================
    // SNAPSHOT CREATION
    // =========================================================================

    /**
     * Create a new snapshot.
     *
     * @param int $projectId The project ID
     * @param int $researcherId The researcher ID
     * @param array $data Snapshot data (title, description, query_state, rights_state, metadata)
     * @return int The new snapshot ID
     */
    public function createSnapshot(int $projectId, int $researcherId, array $data): int
    {
        $id = DB::table('research_snapshot')->insertGetId([
            'project_id' => $projectId,
            'researcher_id' => $researcherId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'query_state_json' => isset($data['query_state']) ? json_encode($data['query_state']) : null,
            'rights_state_json' => isset($data['rights_state']) ? json_encode($data['rights_state']) : null,
            'metadata_json' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'item_count' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Log canonical event
        $this->logEvent($researcherId, $projectId, 'snapshot_created', 'snapshot', $id, $data['title']);

        return $id;
    }

    /**
     * Freeze a collection as an immutable snapshot.
     * Copies all collection items into snapshot_item records, captures
     * rights_snapshot_json per item, computes full-state hash, and sets
     * status to 'frozen' with frozen_at timestamp.
     *
     * @param int $projectId The project ID
     * @param int $collectionId The collection ID to freeze
     * @param int $researcherId The researcher ID
     * @return int The new snapshot ID
     */
    public function freezeCollectionAsSnapshot(int $projectId, int $collectionId, int $researcherId): int
    {
        // Get collection info
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->first();

        if (!$collection) {
            throw new \RuntimeException('Collection not found: ' . $collectionId);
        }

        $now = date('Y-m-d H:i:s');

        // Create snapshot with frozen status
        $snapshotId = DB::table('research_snapshot')->insertGetId([
            'project_id' => $projectId,
            'researcher_id' => $researcherId,
            'title' => $collection->name . ' (Snapshot)',
            'description' => $collection->description,
            'item_count' => 0,
            'status' => 'frozen',
            'frozen_at' => $now,
            'created_at' => $now,
        ]);

        // Get all collection items
        $items = DB::table('research_collection_item')
            ->where('collection_id', $collectionId)
            ->orderBy('sort_order')
            ->get();

        $itemCount = 0;

        foreach ($items as $item) {
            // Look up the slug from the slug table
            $slugRow = DB::table('slug')
                ->where('object_id', $item->object_id)
                ->first();

            // Get current metadata version from information_object_i18n for hashing
            $i18n = DB::table('information_object_i18n')
                ->where('id', $item->object_id)
                ->where('culture', 'en')
                ->first();

            $metadataVersion = null;
            if ($i18n) {
                $metadataVersion = json_encode([
                    'title' => $i18n->title ?? null,
                    'scope_and_content' => $i18n->scope_and_content ?? null,
                    'extent_and_medium' => $i18n->extent_and_medium ?? null,
                ]);
            }

            // Capture rights snapshot from any existing rights data
            $rightsSnapshot = $this->captureItemRights($item->object_id);

            DB::table('research_snapshot_item')->insert([
                'snapshot_id' => $snapshotId,
                'object_id' => $item->object_id,
                'object_type' => $item->object_type ?? 'information_object',
                'culture' => $item->culture ?? 'en',
                'slug' => $slugRow->slug ?? null,
                'metadata_version_json' => $metadataVersion,
                'rights_snapshot_json' => $rightsSnapshot ? json_encode($rightsSnapshot) : null,
                'sort_order' => $item->sort_order ?? $itemCount,
                'created_at' => $now,
            ]);

            $itemCount++;
        }

        // Compute and store SHA-256 hash (includes full state)
        $hash = $this->computeHash($snapshotId);

        // Generate citation ID
        $shortHash = substr($hash, 0, 8);
        $citationId = sprintf('SNAP-%d-%d-%s', $projectId, $snapshotId, $shortHash);

        // Update item_count, hash, and citation_id on snapshot
        DB::table('research_snapshot')
            ->where('id', $snapshotId)
            ->update([
                'item_count' => $itemCount,
                'hash_sha256' => $hash,
                'citation_id' => $citationId,
            ]);

        // Log event
        $this->logEvent($researcherId, $projectId, 'snapshot_created', 'snapshot', $snapshotId, $collection->name . ' (Snapshot)');

        return $snapshotId;
    }

    // =========================================================================
    // SNAPSHOT RETRIEVAL
    // =========================================================================

    /**
     * Get a snapshot by ID with item count.
     *
     * @param int $id The snapshot ID
     * @return object|null The snapshot or null if not found
     */
    public function getSnapshot(int $id): ?object
    {
        $snapshot = DB::table('research_snapshot as s')
            ->leftJoin('research_researcher as r', 's.researcher_id', '=', 'r.id')
            ->where('s.id', $id)
            ->select(
                's.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email'
            )
            ->first();

        if ($snapshot) {
            $snapshot->actual_item_count = DB::table('research_snapshot_item')
                ->where('snapshot_id', $id)
                ->count();
        }

        return $snapshot;
    }

    /**
     * Get all snapshots for a project.
     *
     * @param int $projectId The project ID
     * @return array List of snapshots
     */
    public function getProjectSnapshots(int $projectId): array
    {
        $snapshots = DB::table('research_snapshot as s')
            ->leftJoin('research_researcher as r', 's.researcher_id', '=', 'r.id')
            ->where('s.project_id', $projectId)
            ->select(
                's.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('s.created_at', 'desc')
            ->get()
            ->toArray();

        foreach ($snapshots as &$snapshot) {
            $snapshot->actual_item_count = DB::table('research_snapshot_item')
                ->where('snapshot_id', $snapshot->id)
                ->count();
        }

        return $snapshots;
    }

    /**
     * Get paginated snapshot items.
     *
     * @param int $snapshotId The snapshot ID
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @return array Paginated result with items, total, page, limit
     */
    public function getSnapshotItems(int $snapshotId, int $page = 1, int $limit = 25): array
    {
        $total = DB::table('research_snapshot_item')
            ->where('snapshot_id', $snapshotId)
            ->count();

        $offset = ($page - 1) * $limit;

        $items = DB::table('research_snapshot_item as si')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('si.object_id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('si.snapshot_id', $snapshotId)
            ->select(
                'si.*',
                'i18n.title as current_title'
            )
            ->orderBy('si.sort_order')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    // =========================================================================
    // HASHING & VERIFICATION
    // =========================================================================

    /**
     * Compute SHA-256 hash of snapshot contents.
     * Hash includes: snapshot-level query_state_json, rights_state_json, metadata_json,
     * plus each item's object_id, object_type, metadata_version_json, and rights_snapshot_json,
     * all stably sorted by object_id ASC then object_type ASC.
     *
     * @param int $snapshotId The snapshot ID
     * @return string The SHA-256 HMAC hash
     */
    public function computeHash(int $snapshotId): string
    {
        // Get snapshot-level state
        $snapshot = DB::table('research_snapshot')
            ->where('id', $snapshotId)
            ->select('query_state_json', 'rights_state_json', 'metadata_json')
            ->first();

        // Build the hash payload starting with snapshot-level state
        $parts = [];
        $parts[] = 'qs:' . ($snapshot->query_state_json ?? '');
        $parts[] = 'rs:' . ($snapshot->rights_state_json ?? '');
        $parts[] = 'md:' . ($snapshot->metadata_json ?? '');

        // Get items sorted stably by object_id ASC, object_type ASC
        $items = DB::table('research_snapshot_item')
            ->where('snapshot_id', $snapshotId)
            ->orderBy('object_id', 'asc')
            ->orderBy('object_type', 'asc')
            ->select('object_id', 'object_type', 'metadata_version_json', 'rights_snapshot_json')
            ->get();

        foreach ($items as $item) {
            $parts[] = implode(':', [
                $item->object_id,
                $item->object_type ?? 'information_object',
                $item->metadata_version_json ?? '',
                $item->rights_snapshot_json ?? '',
            ]);
        }

        $payload = implode('|', $parts);

        return hash_hmac('sha256', $payload, 'research_snapshot');
    }

    /**
     * Verify a snapshot's hash against its stored hash.
     * Recomputes the hash from current snapshot data and compares.
     *
     * @param int $snapshotId The snapshot ID
     * @return array ['valid' => bool, 'stored' => string, 'computed' => string]
     */
    public function verifyHash(int $snapshotId): array
    {
        $snapshot = DB::table('research_snapshot')
            ->where('id', $snapshotId)
            ->first();

        if (!$snapshot) {
            return ['valid' => false, 'stored' => '', 'computed' => '', 'error' => 'Snapshot not found'];
        }

        $stored = $snapshot->hash_sha256 ?? '';
        $computed = $this->computeHash($snapshotId);

        return [
            'valid' => $stored === $computed,
            'stored' => $stored,
            'computed' => $computed,
        ];
    }

    /**
     * Compare two snapshots, returning added/removed/changed items.
     *
     * @param int $snapshotA The first (older) snapshot ID
     * @param int $snapshotB The second (newer) snapshot ID
     * @return array Comparison result with added, removed, and changed items
     */
    public function compareSnapshots(int $snapshotA, int $snapshotB): array
    {
        // Get items from snapshot A keyed by object_id
        $itemsA = DB::table('research_snapshot_item')
            ->where('snapshot_id', $snapshotA)
            ->get()
            ->keyBy('object_id')
            ->toArray();

        // Get items from snapshot B keyed by object_id
        $itemsB = DB::table('research_snapshot_item')
            ->where('snapshot_id', $snapshotB)
            ->get()
            ->keyBy('object_id')
            ->toArray();

        $objectIdsA = array_keys($itemsA);
        $objectIdsB = array_keys($itemsB);

        // Items in B but not in A (added)
        $addedIds = array_diff($objectIdsB, $objectIdsA);
        $added = [];
        foreach ($addedIds as $id) {
            $added[] = $itemsB[$id];
        }

        // Items in A but not in B (removed)
        $removedIds = array_diff($objectIdsA, $objectIdsB);
        $removed = [];
        foreach ($removedIds as $id) {
            $removed[] = $itemsA[$id];
        }

        // Items in both with different metadata_version_json (changed)
        $commonIds = array_intersect($objectIdsA, $objectIdsB);
        $changed = [];
        foreach ($commonIds as $id) {
            $metaA = $itemsA[$id]->metadata_version_json ?? null;
            $metaB = $itemsB[$id]->metadata_version_json ?? null;

            if ($metaA !== $metaB) {
                $changed[] = (object) [
                    'object_id' => $id,
                    'object_type' => $itemsB[$id]->object_type ?? 'information_object',
                    'slug' => $itemsB[$id]->slug ?? null,
                    'metadata_before' => $metaA,
                    'metadata_after' => $metaB,
                ];
            }
        }

        // Log the comparison event - use researcher from snapshot B
        $snapshotBRecord = DB::table('research_snapshot')
            ->where('id', $snapshotB)
            ->first();

        if ($snapshotBRecord) {
            $this->logEvent(
                $snapshotBRecord->researcher_id,
                $snapshotBRecord->project_id,
                'snapshot_compared',
                'snapshot',
                $snapshotB,
                'Compared snapshot #' . $snapshotA . ' with #' . $snapshotB
            );
        }

        return [
            'added' => array_values($added),
            'removed' => array_values($removed),
            'changed' => array_values($changed),
        ];
    }

    // =========================================================================
    // SNAPSHOT LIFECYCLE
    // =========================================================================

    /**
     * Archive a snapshot. Frozen snapshots cannot be archived.
     *
     * @param int $id The snapshot ID
     * @return bool Success status
     * @throws \RuntimeException If snapshot is frozen
     */
    public function archiveSnapshot(int $id): bool
    {
        $this->ensureNotFrozen($id);

        return DB::table('research_snapshot')
            ->where('id', $id)
            ->where('status', 'active')
            ->update(['status' => 'archived']) > 0;
    }

    /**
     * Delete a snapshot and its items. Frozen snapshots cannot be deleted.
     *
     * @param int $id The snapshot ID
     * @return bool Success status
     * @throws \RuntimeException If snapshot is frozen
     */
    public function deleteSnapshot(int $id): bool
    {
        $this->ensureNotFrozen($id);

        // Delete snapshot items first
        DB::table('research_snapshot_item')
            ->where('snapshot_id', $id)
            ->delete();

        // Delete the snapshot
        return DB::table('research_snapshot')
            ->where('id', $id)
            ->delete() > 0;
    }

    // =========================================================================
    // CITATION
    // =========================================================================

    /**
     * Generate a stable citation identifier for a snapshot.
     * Format: SNAP-{projectId}-{snapshotId}-{shortHash}
     *
     * @param int $snapshotId The snapshot ID
     * @return string The citation identifier
     */
    public function generateCitationId(int $snapshotId): string
    {
        $snapshot = DB::table('research_snapshot')
            ->where('id', $snapshotId)
            ->first();

        if (!$snapshot) {
            throw new \RuntimeException('Snapshot not found: ' . $snapshotId);
        }

        $hash = $snapshot->hash_sha256 ?: $this->computeHash($snapshotId);
        $shortHash = substr($hash, 0, 8);
        $citationId = sprintf('SNAP-%d-%d-%s', $snapshot->project_id, $snapshotId, $shortHash);

        // Store citation_id if not already set
        if (empty($snapshot->citation_id)) {
            DB::table('research_snapshot')
                ->where('id', $snapshotId)
                ->update(['citation_id' => $citationId]);
        }

        return $citationId;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Ensure a snapshot is not frozen. Throws if it is.
     *
     * @param int $id The snapshot ID
     * @throws \RuntimeException If snapshot status is 'frozen'
     */
    private function ensureNotFrozen(int $id): void
    {
        $snapshot = DB::table('research_snapshot')
            ->where('id', $id)
            ->first();

        if ($snapshot && $snapshot->status === 'frozen') {
            throw new \RuntimeException(
                'Cannot modify frozen snapshot #' . $id . '. Frozen snapshots are immutable for research integrity.'
            );
        }
    }

    /**
     * Capture current rights data for an object for snapshot preservation.
     *
     * @param int $objectId The object ID
     * @return array|null Rights data or null if none
     */
    private function captureItemRights(int $objectId): ?array
    {
        $rights = DB::table('relation')
            ->join('right_i18n as ri', 'relation.object_id', '=', 'ri.id')
            ->where('relation.subject_id', $objectId)
            ->where('relation.type_id', 159) // QubitTerm::RIGHT_ID
            ->where('ri.culture', 'en')
            ->select('ri.id', 'ri.rights_note', 'ri.copyright_note', 'ri.license_note')
            ->get()
            ->toArray();

        if (empty($rights)) {
            // Try ODRL policies
            $policies = DB::table('research_rights_policy')
                ->where('target_type', 'information_object')
                ->where('target_id', $objectId)
                ->select('policy_type', 'action_type', 'constraints_json')
                ->get()
                ->toArray();

            return !empty($policies) ? ['odrl' => $policies] : null;
        }

        return ['rights' => $rights];
    }

    // =========================================================================
    // EVENT LOGGING
    // =========================================================================

    /**
     * Log a canonical event to research_activity_log.
     *
     * @param int $researcherId The researcher ID
     * @param int|null $projectId The project ID
     * @param string $type Activity type (snapshot_created, snapshot_compared)
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param string|null $title Optional entity title
     */
    private function logEvent(int $researcherId, ?int $projectId, string $type, string $entityType, int $entityId, ?string $title = null): void
    {
        DB::table('research_activity_log')->insert([
            'researcher_id' => $researcherId,
            'project_id' => $projectId,
            'activity_type' => $type,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_title' => $title,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
