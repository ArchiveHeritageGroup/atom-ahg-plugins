<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * HypothesisService - Research Hypothesis Management
 *
 * Handles hypothesis creation, status tracking, evidence linking,
 * and evidence timeline retrieval for research projects.
 *
 * Tables: research_hypothesis, research_hypothesis_evidence
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class HypothesisService
{
    /**
     * Valid hypothesis statuses.
     */
    private const VALID_STATUSES = ['proposed', 'testing', 'supported', 'refuted'];

    /**
     * Valid evidence relationships.
     */
    private const VALID_RELATIONSHIPS = ['supports', 'refutes', 'neutral'];

    // =========================================================================
    // HYPOTHESIS MANAGEMENT
    // =========================================================================

    /**
     * Create a new hypothesis.
     *
     * @param int $projectId The research project ID
     * @param int $researcherId The researcher creating the hypothesis
     * @param array $data Keys: statement (required), tags (optional)
     * @return int The new hypothesis ID
     */
    public function createHypothesis(int $projectId, int $researcherId, array $data): int
    {
        $hypothesisId = DB::table('research_hypothesis')->insertGetId([
            'project_id' => $projectId,
            'researcher_id' => $researcherId,
            'statement' => $data['statement'],
            'status' => 'proposed',
            'evidence_count' => 0,
            'tags' => $data['tags'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEvent(
            $researcherId,
            $projectId,
            'hypothesis_created',
            'hypothesis',
            $hypothesisId,
            mb_substr($data['statement'], 0, 200)
        );

        return $hypothesisId;
    }

    /**
     * Get a hypothesis by ID with its evidence.
     *
     * @param int $id The hypothesis ID
     * @return object|null The hypothesis with attached evidence array, or null
     */
    public function getHypothesis(int $id): ?object
    {
        $hypothesis = DB::table('research_hypothesis as h')
            ->leftJoin('research_researcher as r', 'h.researcher_id', '=', 'r.id')
            ->where('h.id', $id)
            ->select(
                'h.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email'
            )
            ->first();

        if (!$hypothesis) {
            return null;
        }

        $hypothesis->evidence = DB::table('research_hypothesis_evidence as e')
            ->leftJoin('research_researcher as r', 'e.added_by', '=', 'r.id')
            ->where('e.hypothesis_id', $id)
            ->select(
                'e.*',
                'r.first_name as added_by_first_name',
                'r.last_name as added_by_last_name'
            )
            ->orderBy('e.created_at', 'desc')
            ->get()
            ->toArray();

        return $hypothesis;
    }

    /**
     * Get all hypotheses for a project.
     *
     * @param int $projectId The project ID
     * @return array List of hypotheses with researcher names
     */
    public function getProjectHypotheses(int $projectId): array
    {
        return DB::table('research_hypothesis as h')
            ->leftJoin('research_researcher as r', 'h.researcher_id', '=', 'r.id')
            ->where('h.project_id', $projectId)
            ->select(
                'h.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('h.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Update a hypothesis statement and tags.
     *
     * @param int $id The hypothesis ID
     * @param array $data Keys: statement, tags
     * @return bool Success status
     */
    public function updateHypothesis(int $id, array $data): bool
    {
        $allowed = ['statement', 'tags'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_hypothesis')
            ->where('id', $id)
            ->update($updateData) >= 0;
    }

    /**
     * Update hypothesis status.
     *
     * @param int $id The hypothesis ID
     * @param string $status One of: proposed, testing, supported, refuted
     * @return bool Success status
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        $hypothesis = DB::table('research_hypothesis')
            ->where('id', $id)
            ->first();

        if (!$hypothesis) {
            return false;
        }

        $updated = DB::table('research_hypothesis')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) >= 0;

        if ($updated) {
            $this->logEvent(
                $hypothesis->researcher_id,
                $hypothesis->project_id,
                'hypothesis_updated',
                'hypothesis',
                $id,
                mb_substr($hypothesis->statement, 0, 200)
            );
        }

        return $updated;
    }

    // =========================================================================
    // EVIDENCE MANAGEMENT
    // =========================================================================

    /**
     * Add evidence to a hypothesis.
     *
     * @param int $hypothesisId The hypothesis ID
     * @param array $data Keys: source_type, source_id, relationship (supports|refutes|neutral),
     *                     confidence (decimal 0-100), note, added_by (researcher ID)
     * @return int The new evidence ID
     */
    public function addEvidence(int $hypothesisId, array $data): int
    {
        $evidenceId = DB::table('research_hypothesis_evidence')->insertGetId([
            'hypothesis_id' => $hypothesisId,
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'relationship' => $data['relationship'],
            'confidence' => $data['confidence'] ?? null,
            'note' => $data['note'] ?? null,
            'added_by' => $data['added_by'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update evidence_count on the parent hypothesis with actual count
        $count = DB::table('research_hypothesis_evidence')
            ->where('hypothesis_id', $hypothesisId)
            ->count();

        DB::table('research_hypothesis')
            ->where('id', $hypothesisId)
            ->update([
                'evidence_count' => $count,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $evidenceId;
    }

    /**
     * Remove evidence from a hypothesis.
     *
     * @param int $evidenceId The evidence record ID
     * @return bool Success status
     */
    public function removeEvidence(int $evidenceId): bool
    {
        $evidence = DB::table('research_hypothesis_evidence')
            ->where('id', $evidenceId)
            ->first();

        if (!$evidence) {
            return false;
        }

        $hypothesisId = $evidence->hypothesis_id;

        $deleted = DB::table('research_hypothesis_evidence')
            ->where('id', $evidenceId)
            ->delete() > 0;

        if ($deleted) {
            // Recount evidence on the parent hypothesis
            $count = DB::table('research_hypothesis_evidence')
                ->where('hypothesis_id', $hypothesisId)
                ->count();

            DB::table('research_hypothesis')
                ->where('id', $hypothesisId)
                ->update([
                    'evidence_count' => $count,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return $deleted;
    }

    /**
     * Get evidence timeline for a hypothesis (chronological order).
     *
     * @param int $hypothesisId The hypothesis ID
     * @return array Evidence records with researcher names, oldest first
     */
    public function getEvidenceTimeline(int $hypothesisId): array
    {
        return DB::table('research_hypothesis_evidence as e')
            ->leftJoin('research_researcher as r', 'e.added_by', '=', 'r.id')
            ->where('e.hypothesis_id', $hypothesisId)
            ->select(
                'e.*',
                'r.first_name as added_by_first_name',
                'r.last_name as added_by_last_name'
            )
            ->orderBy('e.created_at', 'asc')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // DELETION
    // =========================================================================

    /**
     * Delete a hypothesis and all its evidence.
     *
     * @param int $id The hypothesis ID
     * @return bool Success status
     */
    public function deleteHypothesis(int $id): bool
    {
        // Delete all evidence records first
        DB::table('research_hypothesis_evidence')
            ->where('hypothesis_id', $id)
            ->delete();

        return DB::table('research_hypothesis')
            ->where('id', $id)
            ->delete() > 0;
    }

    // =========================================================================
    // ACTIVITY LOGGING
    // =========================================================================

    /**
     * Log a canonical event to the research activity log.
     *
     * @param int $researcherId The researcher performing the action
     * @param int|null $projectId The project ID (optional)
     * @param string $type The activity type (hypothesis_created, hypothesis_updated)
     * @param string $entityType The entity type (hypothesis)
     * @param int $entityId The entity ID
     * @param string|null $title Optional entity title for display
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
