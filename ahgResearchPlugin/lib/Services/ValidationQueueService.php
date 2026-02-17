<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ValidationQueueService - AI Extraction Validation Queue Management
 *
 * Manages the human-in-the-loop validation workflow for AI extraction results.
 * Reviewers can accept, reject, or modify extraction results. Accepted entity
 * results are promoted to research assertions via the knowledge graph.
 *
 * Tables: research_validation_queue, research_extraction_result,
 *         research_extraction_job, research_assertion, research_activity_log
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class ValidationQueueService
{
    /**
     * Get paginated validation queue items.
     *
     * Joins validation_queue with extraction_result and extraction_job to
     * provide full context: object info, job type, confidence, data preview.
     *
     * @param int|null $researcherId Filter by researcher (null for all)
     * @param array    $filters      Keys: status (pending|accepted|rejected|modified), result_type
     * @param int      $page         Page number (1-based)
     * @param int      $limit        Items per page
     *
     * @return array ['items' => [...], 'total' => count, 'page' => page, 'limit' => limit]
     */
    public function getQueue(?int $researcherId = null, array $filters = [], int $page = 1, int $limit = 25): array
    {
        $query = DB::table('research_validation_queue as vq')
            ->join('research_extraction_result as er', 'vq.result_id', '=', 'er.id')
            ->join('research_extraction_job as ej', 'er.job_id', '=', 'ej.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('er.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('research_researcher as reviewer', 'vq.reviewer_id', '=', 'reviewer.id')
            ->select(
                'vq.id',
                'vq.result_id',
                'vq.researcher_id',
                'vq.status',
                'vq.reviewer_id',
                'vq.reviewed_at',
                'vq.notes',
                'vq.modified_data_json',
                'vq.created_at',
                'er.object_id',
                'er.result_type',
                'er.data_json',
                'er.confidence',
                'er.model_version',
                'er.job_id',
                'ej.extraction_type',
                'ej.project_id',
                'ioi.title as object_title',
                'reviewer.first_name as reviewer_first_name',
                'reviewer.last_name as reviewer_last_name'
            );

        if ($researcherId !== null) {
            $query->where('vq.researcher_id', $researcherId);
        }

        if (!empty($filters['status'])) {
            $query->where('vq.status', $filters['status']);
        }

        if (!empty($filters['result_type'])) {
            $query->where('er.result_type', $filters['result_type']);
        }

        if (!empty($filters['extraction_type'])) {
            $query->where('ej.extraction_type', $filters['extraction_type']);
        }

        if (isset($filters['min_confidence']) && $filters['min_confidence'] !== '' && $filters['min_confidence'] !== null) {
            $query->where('er.confidence', '>=', (float) $filters['min_confidence']);
        }

        $total = $query->count();

        $offset = ($page - 1) * $limit;

        $items = (clone $query)
            ->orderBy('vq.created_at', 'desc')
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

    /**
     * Get aggregate queue statistics.
     *
     * Returns counts by status (pending, accepted, rejected, modified),
     * plus average confidence of pending items.
     *
     * @param int|null $researcherId Filter by researcher (null for all)
     * @return array ['pending' => int, 'accepted' => int, 'rejected' => int, 'modified' => int, 'avg_confidence' => float|null]
     */
    public function getQueueStats(?int $researcherId = null): array
    {
        $baseQuery = DB::table('research_validation_queue as vq')
            ->join('research_extraction_result as er', 'vq.result_id', '=', 'er.id');

        if ($researcherId !== null) {
            $baseQuery->where('vq.researcher_id', $researcherId);
        }

        // Count by status
        $counts = (clone $baseQuery)
            ->selectRaw("
                SUM(CASE WHEN vq.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN vq.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
                SUM(CASE WHEN vq.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN vq.status = 'modified' THEN 1 ELSE 0 END) as modified_count
            ")
            ->first();

        // Average confidence of pending items
        $avgConfidence = (clone $baseQuery)
            ->where('vq.status', 'pending')
            ->whereNotNull('er.confidence')
            ->avg('er.confidence');

        return [
            'pending' => (int) ($counts->pending_count ?? 0),
            'accepted' => (int) ($counts->accepted_count ?? 0),
            'rejected' => (int) ($counts->rejected_count ?? 0),
            'modified' => (int) ($counts->modified_count ?? 0),
            'avg_confidence' => $avgConfidence !== null ? round((float) $avgConfidence, 4) : null,
        ];
    }

    /**
     * Get count of pending items.
     *
     * @param int|null $researcherId Filter by researcher (null for all)
     *
     * @return int Number of pending validation items
     */
    public function getPendingCount(?int $researcherId = null): int
    {
        $query = DB::table('research_validation_queue')
            ->where('status', 'pending');

        if ($researcherId !== null) {
            $query->where('researcher_id', $researcherId);
        }

        return $query->count();
    }

    /**
     * Get a specific extraction result with its validation status.
     *
     * Joins the result with its parent job and any existing validation_queue entry.
     *
     * @param int $resultId The extraction result ID
     *
     * @return object|null The result with job context and validation status, or null
     */
    public function getResult(int $resultId): ?object
    {
        return DB::table('research_extraction_result as er')
            ->join('research_extraction_job as ej', 'er.job_id', '=', 'ej.id')
            ->leftJoin('research_validation_queue as vq', 'vq.result_id', '=', 'er.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('er.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('research_researcher as reviewer', 'vq.reviewer_id', '=', 'reviewer.id')
            ->where('er.id', $resultId)
            ->select(
                'er.*',
                'ej.extraction_type',
                'ej.project_id',
                'ej.researcher_id as job_researcher_id',
                'ej.parameters_json as job_parameters_json',
                'vq.id as validation_id',
                'vq.status as validation_status',
                'vq.reviewer_id as validation_reviewer_id',
                'vq.reviewed_at as validation_reviewed_at',
                'vq.notes as validation_notes',
                'vq.modified_data_json as validation_modified_data_json',
                'ioi.title as object_title',
                'reviewer.first_name as reviewer_first_name',
                'reviewer.last_name as reviewer_last_name'
            )
            ->first();
    }

    /**
     * Accept a result - promotes entity results to assertions.
     *
     * Updates the validation_queue status to 'accepted', then for entity-type
     * results creates a research_assertion from the extracted entity data.
     * Logs a validation_accepted canonical event.
     *
     * @param int $resultId   The extraction result ID
     * @param int $reviewerId The reviewer's researcher ID
     *
     * @return bool True if the result was successfully accepted
     */
    public function acceptResult(int $resultId, int $reviewerId): bool
    {
        $now = date('Y-m-d H:i:s');

        $updated = DB::table('research_validation_queue')
            ->where('result_id', $resultId)
            ->where('status', 'pending')
            ->update([
                'status' => 'accepted',
                'reviewer_id' => $reviewerId,
                'reviewed_at' => $now,
            ]);

        if ($updated === 0) {
            return false;
        }

        // Get the result with job context for assertion creation
        $result = DB::table('research_extraction_result as er')
            ->join('research_extraction_job as ej', 'er.job_id', '=', 'ej.id')
            ->where('er.id', $resultId)
            ->select(
                'er.object_id',
                'er.result_type',
                'er.data_json',
                'er.confidence',
                'ej.project_id',
                'ej.researcher_id'
            )
            ->first();

        if (!$result) {
            return true;
        }

        // For entity-type results, create an assertion from the extracted entity
        if ($result->result_type === 'entity') {
            $this->createAssertionFromEntity($result, $reviewerId);
        }

        // Log validation_accepted event
        $this->logEvent(
            $result->researcher_id,
            $result->project_id,
            'validation_accepted',
            'extraction_result',
            $resultId,
            'Result #' . $resultId . ' accepted'
        );

        return true;
    }

    /**
     * Reject a result.
     *
     * Updates the validation_queue status to 'rejected' and records the reason.
     * Logs a validation_rejected canonical event.
     *
     * @param int    $resultId   The extraction result ID
     * @param int    $reviewerId The reviewer's researcher ID
     * @param string $reason     Rejection reason
     *
     * @return bool True if the result was successfully rejected
     */
    public function rejectResult(int $resultId, int $reviewerId, string $reason): bool
    {
        $now = date('Y-m-d H:i:s');

        $updated = DB::table('research_validation_queue')
            ->where('result_id', $resultId)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewer_id' => $reviewerId,
                'reviewed_at' => $now,
                'notes' => $reason,
            ]);

        if ($updated === 0) {
            return false;
        }

        // Get job context for event logging
        $result = DB::table('research_extraction_result as er')
            ->join('research_extraction_job as ej', 'er.job_id', '=', 'ej.id')
            ->where('er.id', $resultId)
            ->select('ej.project_id', 'ej.researcher_id')
            ->first();

        if ($result) {
            $this->logEvent(
                $result->researcher_id,
                $result->project_id,
                'validation_rejected',
                'extraction_result',
                $resultId,
                'Result #' . $resultId . ' rejected: ' . mb_substr($reason, 0, 200)
            );
        }

        return true;
    }

    /**
     * Modify a result (accept with changes).
     *
     * Sets the validation_queue status to 'modified' and stores the reviewer's
     * corrected data. A modified acceptance is still an acceptance event, so
     * validation_accepted is logged.
     *
     * @param int   $resultId     The extraction result ID
     * @param int   $reviewerId   The reviewer's researcher ID
     * @param array $modifiedData The reviewer's corrected version of the data
     *
     * @return bool True if the result was successfully modified
     */
    public function modifyResult(int $resultId, int $reviewerId, array $modifiedData): bool
    {
        $now = date('Y-m-d H:i:s');

        $updated = DB::table('research_validation_queue')
            ->where('result_id', $resultId)
            ->where('status', 'pending')
            ->update([
                'status' => 'modified',
                'modified_data_json' => json_encode($modifiedData),
                'reviewer_id' => $reviewerId,
                'reviewed_at' => $now,
            ]);

        if ($updated === 0) {
            return false;
        }

        // Get job context for event logging and assertion creation
        $result = DB::table('research_extraction_result as er')
            ->join('research_extraction_job as ej', 'er.job_id', '=', 'ej.id')
            ->where('er.id', $resultId)
            ->select(
                'er.object_id',
                'er.result_type',
                'er.confidence',
                'ej.project_id',
                'ej.researcher_id'
            )
            ->first();

        if (!$result) {
            return true;
        }

        // For entity-type results, create assertion from modified data
        if ($result->result_type === 'entity') {
            $modifiedResult = clone $result;
            $modifiedResult->data_json = json_encode($modifiedData);
            $this->createAssertionFromEntity($modifiedResult, $reviewerId);
        }

        // Modified acceptance is still acceptance
        $this->logEvent(
            $result->researcher_id,
            $result->project_id,
            'validation_accepted',
            'extraction_result',
            $resultId,
            'Result #' . $resultId . ' accepted with modifications'
        );

        return true;
    }

    /**
     * Bulk accept multiple results.
     *
     * Iterates through each result ID and calls acceptResult individually
     * to ensure assertion creation and event logging occur for each.
     *
     * @param array $resultIds  Array of extraction result IDs
     * @param int   $reviewerId The reviewer's researcher ID
     *
     * @return int Number of results successfully accepted
     */
    public function bulkAccept(array $resultIds, int $reviewerId): int
    {
        $accepted = 0;

        foreach ($resultIds as $resultId) {
            if ($this->acceptResult((int) $resultId, $reviewerId)) {
                $accepted++;
            }
        }

        return $accepted;
    }

    /**
     * Bulk reject multiple results.
     *
     * Iterates through each result ID and calls rejectResult individually
     * to ensure event logging occurs for each.
     *
     * @param array  $resultIds  Array of extraction result IDs
     * @param int    $reviewerId The reviewer's researcher ID
     * @param string $reason     Rejection reason applied to all
     *
     * @return int Number of results successfully rejected
     */
    public function bulkReject(array $resultIds, int $reviewerId, string $reason): int
    {
        $rejected = 0;

        foreach ($resultIds as $resultId) {
            if ($this->rejectResult((int) $resultId, $reviewerId, $reason)) {
                $rejected++;
            }
        }

        return $rejected;
    }

    /**
     * Get disagreements for a job (results where reviewers conflict).
     *
     * Finds results that have multiple validation_queue entries with differing
     * statuses (e.g., one accepted, one rejected by different reviewers), or
     * results with modified_data that indicates reviewer correction.
     *
     * @param int $jobId The extraction job ID
     *
     * @return array List of disagreement records with result and reviewer details
     */
    public function getDisagreements(int $jobId): array
    {
        // Get all results for this job that have been reviewed
        $results = DB::table('research_extraction_result as er')
            ->join('research_validation_queue as vq', 'vq.result_id', '=', 'er.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('er.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('er.job_id', $jobId)
            ->where('vq.status', '!=', 'pending')
            ->select(
                'er.id as result_id',
                'er.object_id',
                'er.result_type',
                'er.data_json',
                'er.confidence',
                'vq.id as validation_id',
                'vq.status as validation_status',
                'vq.reviewer_id',
                'vq.reviewed_at',
                'vq.notes',
                'vq.modified_data_json',
                'ioi.title as object_title'
            )
            ->orderBy('er.id')
            ->get();

        // Group by result_id to detect conflicts
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row->result_id][] = $row;
        }

        $disagreements = [];

        foreach ($grouped as $resultId => $reviews) {
            // Check for status conflicts: different reviewers gave different verdicts
            $statuses = array_unique(array_column($reviews, 'validation_status'));

            $hasConflict = false;

            if (count($statuses) > 1) {
                // Multiple different statuses for the same result
                $hasConflict = true;
            }

            // Check for modified results (reviewer corrected the AI output)
            foreach ($reviews as $review) {
                if ($review->validation_status === 'modified' && $review->modified_data_json !== null) {
                    $hasConflict = true;
                    break;
                }
            }

            if ($hasConflict) {
                $disagreements[] = [
                    'result_id' => $resultId,
                    'object_id' => $reviews[0]->object_id,
                    'object_title' => $reviews[0]->object_title,
                    'result_type' => $reviews[0]->result_type,
                    'data_json' => $reviews[0]->data_json,
                    'confidence' => $reviews[0]->confidence,
                    'reviews' => array_map(function ($r) {
                        return [
                            'validation_id' => $r->validation_id,
                            'status' => $r->validation_status,
                            'reviewer_id' => $r->reviewer_id,
                            'reviewed_at' => $r->reviewed_at,
                            'notes' => $r->notes,
                            'modified_data_json' => $r->modified_data_json,
                        ];
                    }, $reviews),
                ];
            }
        }

        return $disagreements;
    }

    /**
     * Create a research assertion from an accepted entity extraction result.
     *
     * Parses the entity data_json to build a subject-predicate-object assertion.
     * Entity data is expected to contain: entity_type, entity_value, and optionally
     * relationship, entity_label, confidence.
     *
     * @param object $result     The extraction result with object_id, data_json,
     *                           confidence, project_id, researcher_id
     * @param int    $reviewerId The reviewer who accepted the result
     *
     * @return int|null The assertion ID, or null if data was insufficient
     */
    private function createAssertionFromEntity(object $result, int $reviewerId): ?int
    {
        $data = json_decode($result->data_json ?? '{}', true);

        if (empty($data) || empty($data['entity_value'])) {
            return null;
        }

        $entityType = $data['entity_type'] ?? 'unknown';
        $entityValue = $data['entity_value'];
        $relationship = $data['relationship'] ?? $this->inferPredicate($entityType);

        // Map NER entity types to assertion types
        $assertionTypeMap = [
            'person' => 'biographical',
            'organization' => 'relational',
            'location' => 'spatial',
            'place' => 'spatial',
            'date' => 'chronological',
            'event' => 'chronological',
        ];

        $assertionType = $assertionTypeMap[strtolower($entityType)] ?? 'attributive';

        $assertionId = DB::table('research_assertion')->insertGetId([
            'researcher_id' => $reviewerId,
            'project_id' => $result->project_id ?? null,
            'subject_type' => 'information_object',
            'subject_id' => $result->object_id,
            'subject_label' => null,
            'predicate' => $relationship,
            'object_value' => $entityValue,
            'object_type' => $entityType,
            'object_id' => $data['entity_id'] ?? null,
            'object_label' => $data['entity_label'] ?? $entityValue,
            'assertion_type' => $assertionType,
            'status' => 'verified',
            'confidence' => $result->confidence ?? null,
            'version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Log assertion creation
        $this->logEvent(
            $reviewerId,
            $result->project_id ?? null,
            'assertion_created',
            'assertion',
            $assertionId,
            $relationship . ': ' . mb_substr($entityValue, 0, 200)
        );

        return $assertionId;
    }

    /**
     * Infer a predicate from the NER entity type.
     *
     * @param string $entityType The entity type (person, organization, location, date, etc.)
     *
     * @return string A human-readable predicate string
     */
    private function inferPredicate(string $entityType): string
    {
        $predicateMap = [
            'person' => 'mentions_person',
            'organization' => 'mentions_organization',
            'location' => 'references_location',
            'place' => 'references_location',
            'date' => 'references_date',
            'event' => 'references_event',
            'work' => 'references_work',
            'concept' => 'relates_to_concept',
        ];

        return $predicateMap[strtolower($entityType)] ?? 'has_extracted_entity';
    }

    /**
     * Log a canonical event to research_activity_log.
     *
     * @param int         $researcherId The researcher ID
     * @param int|null    $projectId    The project ID
     * @param string      $type         Activity type (validation_accepted, validation_rejected, etc.)
     * @param string      $entityType   Entity type
     * @param int         $entityId     Entity ID
     * @param string|null $title        Optional entity title
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
