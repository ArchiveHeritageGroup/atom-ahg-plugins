<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ExtractionOrchestrationService - AI Extraction Job Orchestration
 *
 * Manages extraction jobs (OCR, NER, summarize, translate, spellcheck,
 * face detection, form extraction), stores results, creates validation
 * queue entries, and manages document templates.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class ExtractionOrchestrationService
{
    // =========================================================================
    // JOB MANAGEMENT
    // =========================================================================

    /**
     * Create a new extraction job.
     *
     * @param int $projectId The research project ID
     * @param int|null $collectionId Optional collection to scope extraction
     * @param int $researcherId The researcher requesting extraction
     * @param string $type One of: ocr, ner, summarize, translate, spellcheck, face_detection, form_extraction
     * @param array $params Job parameters (language, model, etc.)
     * @return int Job ID
     */
    public function createJob(int $projectId, ?int $collectionId, int $researcherId, string $type, array $params = []): int
    {
        $totalItems = 0;

        if ($collectionId !== null) {
            $totalItems = DB::table('research_collection_item')
                ->where('collection_id', $collectionId)
                ->count();
        }

        $jobId = DB::table('research_extraction_job')->insertGetId([
            'project_id' => $projectId,
            'collection_id' => $collectionId,
            'researcher_id' => $researcherId,
            'extraction_type' => $type,
            'parameters_json' => !empty($params) ? json_encode($params) : null,
            'status' => 'queued',
            'progress' => 0,
            'total_items' => $totalItems,
            'processed_items' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logEvent(
            $researcherId,
            $projectId,
            'extraction_queued',
            'extraction_job',
            $jobId,
            $type . ' extraction queued'
        );

        return $jobId;
    }

    /**
     * Get a job by ID with progress info.
     *
     * @param int $id The job ID
     * @return object|null The job with researcher and collection details, or null
     */
    public function getJob(int $id): ?object
    {
        return DB::table('research_extraction_job as j')
            ->leftJoin('research_researcher as r', 'j.researcher_id', '=', 'r.id')
            ->leftJoin('research_collection as c', 'j.collection_id', '=', 'c.id')
            ->where('j.id', $id)
            ->select(
                'j.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email',
                'c.name as collection_name'
            )
            ->first();
    }

    /**
     * Get all jobs for a project.
     *
     * @param int $projectId The project ID
     * @return array List of jobs with researcher info, newest first
     */
    public function getProjectJobs(int $projectId): array
    {
        return DB::table('research_extraction_job as j')
            ->leftJoin('research_researcher as r', 'j.researcher_id', '=', 'r.id')
            ->where('j.project_id', $projectId)
            ->select(
                'j.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('j.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Update job progress (processed items count).
     *
     * @param int $jobId The job ID
     * @param int $processed Number of items processed so far
     */
    public function updateJobProgress(int $jobId, int $processed): void
    {
        $job = DB::table('research_extraction_job')
            ->where('id', $jobId)
            ->first();

        if (!$job) {
            return;
        }

        $progress = 0;
        if ($job->total_items > 0) {
            $progress = (int) min(100, round(($processed / $job->total_items) * 100));
        }

        DB::table('research_extraction_job')
            ->where('id', $jobId)
            ->update([
                'processed_items' => $processed,
                'progress' => $progress,
            ]);
    }

    /**
     * Mark job as completed.
     *
     * @param int $jobId The job ID
     */
    public function completeJob(int $jobId): void
    {
        $now = date('Y-m-d H:i:s');

        DB::table('research_extraction_job')
            ->where('id', $jobId)
            ->update([
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => $now,
            ]);

        $job = DB::table('research_extraction_job')
            ->where('id', $jobId)
            ->first();

        if ($job) {
            $this->logEvent(
                $job->researcher_id,
                $job->project_id,
                'extraction_completed',
                'extraction_job',
                $jobId,
                $job->extraction_type . ' extraction completed (' . $job->processed_items . '/' . $job->total_items . ' items)'
            );
        }
    }

    /**
     * Mark job as failed.
     *
     * @param int $jobId The job ID
     * @param string $error Error message describing the failure
     */
    public function failJob(int $jobId, string $error): void
    {
        DB::table('research_extraction_job')
            ->where('id', $jobId)
            ->update([
                'status' => 'failed',
                'error_log' => $error,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
    }

    // =========================================================================
    // RESULT STORAGE
    // =========================================================================

    /**
     * Store an extraction result and create validation queue entry.
     *
     * @param int $jobId The extraction job ID
     * @param int $objectId The archival object ID that was processed
     * @param string $type One of: entity, summary, translation, transcription, form_field, face
     * @param array $data The extracted data
     * @param float $confidence Confidence score 0.0-1.0
     * @param string $modelVersion Version of the model/service used
     * @param string $inputHash SHA-256 hash of the input data
     * @return int Result ID
     */
    public function storeResult(int $jobId, int $objectId, string $type, array $data, float $confidence, string $modelVersion, string $inputHash): int
    {
        $resultId = DB::table('research_extraction_result')->insertGetId([
            'job_id' => $jobId,
            'object_id' => $objectId,
            'result_type' => $type,
            'data_json' => json_encode($data),
            'confidence' => $confidence,
            'model_version' => $modelVersion,
            'input_hash' => $inputHash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Get researcher_id from the parent job
        $job = DB::table('research_extraction_job')
            ->where('id', $jobId)
            ->first();

        if ($job) {
            // Auto-create validation queue entry with status='pending'
            DB::table('research_validation_queue')->insert([
                'result_id' => $resultId,
                'researcher_id' => $job->researcher_id,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $resultId;
    }

    /**
     * Get results for a job with optional filters.
     *
     * @param int $jobId The extraction job ID
     * @param array $filters Keys: result_type, min_confidence
     * @return array List of extraction results
     */
    public function getJobResults(int $jobId, array $filters = []): array
    {
        $query = DB::table('research_extraction_result')
            ->where('job_id', $jobId);

        if (!empty($filters['result_type'])) {
            $query->where('result_type', $filters['result_type']);
        }

        if (isset($filters['min_confidence'])) {
            $query->where('confidence', '>=', (float) $filters['min_confidence']);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // DOCUMENT TEMPLATES
    // =========================================================================

    /**
     * Get all document templates.
     *
     * @return array List of document templates ordered by name
     */
    public function getDocumentTemplates(): array
    {
        return DB::table('research_document_template')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Create a document template.
     *
     * @param array $data Template data (name, document_type, description, fields_json, created_by)
     * @return int The new template ID
     */
    public function createDocumentTemplate(array $data): int
    {
        return DB::table('research_document_template')->insertGetId([
            'name' => $data['name'],
            'document_type' => $data['document_type'],
            'description' => $data['description'] ?? null,
            'fields_json' => isset($data['fields_json']) ? (is_array($data['fields_json']) ? json_encode($data['fields_json']) : $data['fields_json']) : null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a document template.
     *
     * @param int $id The template ID
     * @param array $data Fields to update
     * @return bool True if the template was updated
     */
    public function updateDocumentTemplate(int $id, array $data): bool
    {
        $allowed = ['name', 'document_type', 'description', 'fields_json', 'created_by'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (isset($updateData['fields_json']) && is_array($updateData['fields_json'])) {
            $updateData['fields_json'] = json_encode($updateData['fields_json']);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_document_template')
            ->where('id', $id)
            ->update($updateData) >= 0;
    }

    /**
     * Delete a document template.
     *
     * @param int $id The template ID
     * @return bool True if the template was deleted
     */
    public function deleteDocumentTemplate(int $id): bool
    {
        return DB::table('research_document_template')
            ->where('id', $id)
            ->delete() > 0;
    }

    // =========================================================================
    // EVENT LOGGING
    // =========================================================================

    /**
     * Log a canonical event to research_activity_log.
     *
     * @param int $researcherId The researcher ID
     * @param int|null $projectId The project ID
     * @param string $type Activity type (extraction_queued, extraction_completed, etc.)
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
