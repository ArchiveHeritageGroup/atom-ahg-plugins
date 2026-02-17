<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AssertionService - Knowledge Graph Assertion Management
 *
 * Implements a knowledge graph of structured claims (subject -> predicate -> object)
 * with evidence tracking, conflict detection, and D3.js graph visualization.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class AssertionService
{
    // =========================================================================
    // ASSERTION CRUD
    // =========================================================================

    /**
     * Create a new assertion (structured claim: subject -> predicate -> object).
     *
     * @param int $researcherId The researcher creating the assertion
     * @param array $data Keys: project_id, subject_type, subject_id, subject_label, predicate,
     *                     object_value, object_type, object_id, object_label,
     *                     assertion_type (biographical|chronological|spatial|relational|attributive),
     *                     confidence (0-100)
     * @return int Assertion ID
     */
    public function createAssertion(int $researcherId, array $data): int
    {
        $assertionId = DB::table('research_assertion')->insertGetId([
            'researcher_id' => $researcherId,
            'project_id' => $data['project_id'] ?? null,
            'subject_type' => $data['subject_type'],
            'subject_id' => (int) $data['subject_id'],
            'subject_label' => $data['subject_label'] ?? null,
            'predicate' => $data['predicate'],
            'object_value' => $data['object_value'] ?? null,
            'object_type' => $data['object_type'] ?? null,
            'object_id' => isset($data['object_id']) ? (int) $data['object_id'] : null,
            'object_label' => $data['object_label'] ?? null,
            'assertion_type' => $data['assertion_type'] ?? 'attributive',
            'confidence' => isset($data['confidence']) ? min(100, max(0, (float) $data['confidence'])) : null,
            'status' => 'proposed',
            'version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEvent(
            $researcherId,
            $data['project_id'] ?? null,
            'assertion_created',
            'assertion',
            $assertionId,
            $data['subject_label'] . ' ' . $data['predicate'] . ' ' . ($data['object_label'] ?? $data['object_value'] ?? '')
        );

        return $assertionId;
    }

    /**
     * Get an assertion by ID with its evidence array.
     *
     * @param int $id The assertion ID
     * @return object|null The assertion with evidence, or null if not found
     */
    public function getAssertion(int $id): ?object
    {
        $assertion = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->leftJoin('research_project as p', 'a.project_id', '=', 'p.id')
            ->where('a.id', $id)
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email',
                'p.title as project_title'
            )
            ->first();

        if (!$assertion) {
            return null;
        }

        $assertion->evidence = DB::table('research_assertion_evidence as e')
            ->leftJoin('research_researcher as r', 'e.added_by', '=', 'r.id')
            ->where('e.assertion_id', $id)
            ->select(
                'e.*',
                'r.first_name as added_by_first_name',
                'r.last_name as added_by_last_name'
            )
            ->orderBy('e.created_at')
            ->get()
            ->toArray();

        $assertion->evidence_count = count($assertion->evidence);
        $assertion->supporting_count = count(array_filter($assertion->evidence, fn($e) => $e->relationship === 'supports'));
        $assertion->refuting_count = count(array_filter($assertion->evidence, fn($e) => $e->relationship === 'refutes'));

        return $assertion;
    }

    /**
     * Get assertions for a project with filters.
     *
     * @param int $projectId The project ID
     * @param array $filters Keys: assertion_type, status, subject_type, predicate
     * @return array List of assertions
     */
    public function getProjectAssertions(int $projectId, array $filters = []): array
    {
        $query = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.project_id', $projectId)
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            );

        if (!empty($filters['assertion_type'])) {
            $query->where('a.assertion_type', $filters['assertion_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('a.status', $filters['status']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('a.subject_type', $filters['subject_type']);
        }

        if (!empty($filters['predicate'])) {
            $query->where('a.predicate', $filters['predicate']);
        }

        $assertions = $query->orderBy('a.updated_at', 'desc')->get()->toArray();

        // Attach evidence counts to each assertion
        foreach ($assertions as &$assertion) {
            $counts = DB::table('research_assertion_evidence')
                ->where('assertion_id', $assertion->id)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN relationship = 'supports' THEN 1 ELSE 0 END) as supporting")
                ->selectRaw("SUM(CASE WHEN relationship = 'refutes' THEN 1 ELSE 0 END) as refuting")
                ->first();

            $assertion->evidence_count = (int) ($counts->total ?? 0);
            $assertion->supporting_count = (int) ($counts->supporting ?? 0);
            $assertion->refuting_count = (int) ($counts->refuting ?? 0);
        }

        return $assertions;
    }

    /**
     * Update an assertion (bumps version number).
     *
     * @param int $id The assertion ID
     * @param array $data Fields to update
     * @return bool Success status
     */
    public function updateAssertion(int $id, array $data): bool
    {
        $allowed = [
            'subject_type', 'subject_id', 'subject_label', 'predicate',
            'object_value', 'object_type', 'object_id', 'object_label',
            'assertion_type', 'confidence',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        if (isset($updateData['confidence'])) {
            $updateData['confidence'] = min(100, max(0, (float) $updateData['confidence']));
        }

        // Bump version on every update
        $updateData['version'] = DB::raw('version + 1');
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_assertion')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Update assertion status with reviewer tracking.
     *
     * Logs assertion_verified or assertion_disputed events.
     *
     * @param int $id The assertion ID
     * @param string $status New status (proposed|verified|disputed|retracted)
     * @param int $reviewerId The reviewer making the status change
     * @return bool Success status
     */
    public function updateStatus(int $id, string $status, int $reviewerId): bool
    {
        $validStatuses = ['proposed', 'verified', 'disputed', 'retracted'];

        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $assertion = DB::table('research_assertion')->where('id', $id)->first();

        if (!$assertion) {
            return false;
        }

        $updated = DB::table('research_assertion')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;

        if ($updated) {
            $eventType = match ($status) {
                'verified' => 'assertion_verified',
                'disputed' => 'assertion_disputed',
                'retracted' => 'assertion_retracted',
                default => 'assertion_status_changed',
            };

            $this->logEvent(
                $reviewerId,
                $assertion->project_id,
                $eventType,
                'assertion',
                $id,
                ($assertion->subject_label ?? '') . ' ' . $assertion->predicate . ' ' . ($assertion->object_label ?? $assertion->object_value ?? '')
            );
        }

        return $updated;
    }

    // =========================================================================
    // EVIDENCE MANAGEMENT
    // =========================================================================

    /**
     * Add evidence to an assertion.
     *
     * @param int $assertionId The assertion to attach evidence to
     * @param array $data Keys: source_type, source_id, selector_json, relationship (supports|refutes), note
     * @return int Evidence ID
     */
    public function addEvidence(int $assertionId, array $data): int
    {
        $assertion = DB::table('research_assertion')->where('id', $assertionId)->first();

        if (!$assertion) {
            throw new \RuntimeException('Assertion not found');
        }

        $selectorJson = null;
        if (isset($data['selector_json'])) {
            $selectorJson = is_string($data['selector_json'])
                ? $data['selector_json']
                : json_encode($data['selector_json']);
        }

        $evidenceId = DB::table('research_assertion_evidence')->insertGetId([
            'assertion_id' => $assertionId,
            'source_type' => $data['source_type'],
            'source_id' => (int) $data['source_id'],
            'selector_json' => $selectorJson,
            'relationship' => $data['relationship'] ?? 'supports',
            'note' => $data['note'] ?? null,
            'added_by' => (int) $data['added_by'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update the assertion's updated_at timestamp
        DB::table('research_assertion')
            ->where('id', $assertionId)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);

        $this->logEvent(
            (int) $data['added_by'],
            $assertion->project_id,
            'evidence_added',
            'assertion_evidence',
            $evidenceId,
            $data['relationship'] . ' evidence for assertion #' . $assertionId
        );

        return $evidenceId;
    }

    /**
     * Remove evidence from an assertion.
     *
     * @param int $evidenceId The evidence ID to remove
     * @return bool Success status
     */
    public function removeEvidence(int $evidenceId): bool
    {
        $evidence = DB::table('research_assertion_evidence')
            ->where('id', $evidenceId)
            ->first();

        if (!$evidence) {
            return false;
        }

        $deleted = DB::table('research_assertion_evidence')
            ->where('id', $evidenceId)
            ->delete() > 0;

        if ($deleted) {
            // Update the parent assertion's timestamp
            DB::table('research_assertion')
                ->where('id', $evidence->assertion_id)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
        }

        return $deleted;
    }

    // =========================================================================
    // CONFLICT DETECTION
    // =========================================================================

    /**
     * Detect conflicting assertions (same subject+predicate with different object values).
     *
     * Finds other assertions that share the same subject_type, subject_id, and predicate
     * but have a different object_value. Retracted assertions are excluded.
     *
     * @param int $assertionId The assertion to check for conflicts
     * @return array List of conflicting assertions
     */
    public function detectConflicts(int $assertionId): array
    {
        $assertion = DB::table('research_assertion')
            ->where('id', $assertionId)
            ->first();

        if (!$assertion) {
            return [];
        }

        $query = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.subject_type', $assertion->subject_type)
            ->where('a.subject_id', $assertion->subject_id)
            ->where('a.predicate', $assertion->predicate)
            ->where('a.id', '!=', $assertionId)
            ->where('a.status', '!=', 'retracted')
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            );

        // Only flag as conflict if the object value is different
        if ($assertion->object_value !== null) {
            $query->where(function ($q) use ($assertion) {
                $q->where('a.object_value', '!=', $assertion->object_value)
                    ->orWhereNull('a.object_value');
            });
        }

        // If the source assertion references an object by ID, also consider ID-based conflicts
        if ($assertion->object_id !== null) {
            $query->where(function ($q) use ($assertion) {
                $q->where('a.object_id', '!=', $assertion->object_id)
                    ->orWhereNull('a.object_id');
            });
        }

        $conflicts = $query->orderBy('a.confidence', 'desc')
            ->orderBy('a.updated_at', 'desc')
            ->get()
            ->toArray();

        // Attach evidence counts
        foreach ($conflicts as &$conflict) {
            $conflict->evidence_count = DB::table('research_assertion_evidence')
                ->where('assertion_id', $conflict->id)
                ->count();
        }

        return $conflicts;
    }

    // =========================================================================
    // QUERYING
    // =========================================================================

    /**
     * Get all assertions about a specific subject.
     *
     * @param string $subjectType The subject entity type (e.g. 'actor', 'information_object')
     * @param int $subjectId The subject entity ID
     * @return array List of assertions about this subject
     */
    public function getSubjectAssertions(string $subjectType, int $subjectId): array
    {
        $assertions = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->leftJoin('research_project as p', 'a.project_id', '=', 'p.id')
            ->where('a.subject_type', $subjectType)
            ->where('a.subject_id', $subjectId)
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'p.title as project_title'
            )
            ->orderBy('a.predicate')
            ->orderBy('a.updated_at', 'desc')
            ->get()
            ->toArray();

        foreach ($assertions as &$assertion) {
            $counts = DB::table('research_assertion_evidence')
                ->where('assertion_id', $assertion->id)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN relationship = 'supports' THEN 1 ELSE 0 END) as supporting")
                ->selectRaw("SUM(CASE WHEN relationship = 'refutes' THEN 1 ELSE 0 END) as refuting")
                ->first();

            $assertion->evidence_count = (int) ($counts->total ?? 0);
            $assertion->supporting_count = (int) ($counts->supporting ?? 0);
            $assertion->refuting_count = (int) ($counts->refuting ?? 0);
        }

        return $assertions;
    }

    /**
     * Full-text search across assertions.
     *
     * Searches subject_label, predicate, object_value, and object_label fields.
     *
     * @param string $query Search query string
     * @param array $filters Keys: project_id, assertion_type, status
     * @return array List of matching assertions
     */
    public function searchAssertions(string $query, array $filters = []): array
    {
        $search = '%' . $query . '%';

        $dbQuery = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->leftJoin('research_project as p', 'a.project_id', '=', 'p.id')
            ->where(function ($q) use ($search) {
                $q->where('a.subject_label', 'like', $search)
                    ->orWhere('a.predicate', 'like', $search)
                    ->orWhere('a.object_value', 'like', $search)
                    ->orWhere('a.object_label', 'like', $search);
            })
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'p.title as project_title'
            );

        if (!empty($filters['project_id'])) {
            $dbQuery->where('a.project_id', $filters['project_id']);
        }

        if (!empty($filters['assertion_type'])) {
            $dbQuery->where('a.assertion_type', $filters['assertion_type']);
        }

        if (!empty($filters['status'])) {
            $dbQuery->where('a.status', $filters['status']);
        }

        $results = $dbQuery->orderBy('a.updated_at', 'desc')
            ->limit(200)
            ->get()
            ->toArray();

        foreach ($results as &$result) {
            $result->evidence_count = DB::table('research_assertion_evidence')
                ->where('assertion_id', $result->id)
                ->count();
        }

        return $results;
    }

    // =========================================================================
    // KNOWLEDGE GRAPH VISUALIZATION
    // =========================================================================

    /**
     * Build a knowledge graph for D3.js visualization.
     *
     * Returns nodes and edges suitable for a force-directed graph.
     * Each unique subject and object becomes a node, each assertion becomes an edge.
     *
     * @param int $projectId The project ID
     * @return array ['nodes' => [...], 'edges' => [...]]
     *               Each node: {id, type, label, group}
     *               Each edge: {source, target, label, type, status, confidence, id}
     */
    public function getAssertionGraph(int $projectId): array
    {
        $assertions = DB::table('research_assertion')
            ->where('project_id', $projectId)
            ->where('status', '!=', 'retracted')
            ->get()
            ->toArray();

        $nodes = [];
        $edges = [];
        $nodeIndex = [];

        // Assign type groups for visual clustering in D3.js
        $typeGroups = [
            'actor' => 1,
            'information_object' => 2,
            'repository' => 3,
            'term' => 4,
            'place' => 5,
            'event' => 6,
            'date' => 7,
            'concept' => 8,
        ];

        foreach ($assertions as $assertion) {
            // Build subject node key
            $subjectKey = $assertion->subject_type . ':' . $assertion->subject_id;

            if (!isset($nodeIndex[$subjectKey])) {
                $nodeIndex[$subjectKey] = count($nodes);
                $nodes[] = [
                    'id' => $subjectKey,
                    'type' => $assertion->subject_type,
                    'label' => $assertion->subject_label ?? ($assertion->subject_type . ' #' . $assertion->subject_id),
                    'group' => $typeGroups[$assertion->subject_type] ?? 0,
                ];
            }

            // Build object node key
            // Objects may be entity references (with object_type + object_id) or literal values
            if ($assertion->object_type && $assertion->object_id) {
                $objectKey = $assertion->object_type . ':' . $assertion->object_id;
            } else {
                // Literal value node - use a hash to deduplicate identical values
                $objectKey = 'value:' . md5($assertion->object_value ?? '');
            }

            if (!isset($nodeIndex[$objectKey])) {
                $nodeIndex[$objectKey] = count($nodes);

                if ($assertion->object_type && $assertion->object_id) {
                    $nodes[] = [
                        'id' => $objectKey,
                        'type' => $assertion->object_type,
                        'label' => $assertion->object_label ?? ($assertion->object_type . ' #' . $assertion->object_id),
                        'group' => $typeGroups[$assertion->object_type] ?? 0,
                    ];
                } else {
                    $nodes[] = [
                        'id' => $objectKey,
                        'type' => 'literal',
                        'label' => $assertion->object_label ?? mb_substr($assertion->object_value ?? '', 0, 80),
                        'group' => 9,
                    ];
                }
            }

            // Build edge
            $edges[] = [
                'source' => $subjectKey,
                'target' => $objectKey,
                'label' => $assertion->predicate,
                'type' => $assertion->assertion_type,
                'status' => $assertion->status,
                'confidence' => $assertion->confidence !== null ? (float) $assertion->confidence : null,
                'id' => (int) $assertion->id,
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'stats' => [
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'assertion_types' => array_values(array_unique(array_column($edges, 'type'))),
                'entity_types' => array_values(array_unique(array_column($nodes, 'type'))),
            ],
        ];
    }

    // =========================================================================
    // EVENT LOGGING
    // =========================================================================

    /**
     * Log a canonical event to the research activity log.
     *
     * @param int $researcherId The researcher performing the action
     * @param int|null $projectId The related project (if any)
     * @param string $type The activity type
     * @param string $entityType The entity type being acted upon
     * @param int $entityId The entity ID
     * @param string|null $title Optional descriptive title for the event
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
