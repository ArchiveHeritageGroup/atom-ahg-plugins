<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ReproducibilityService - Reproducibility Pack Generation
 *
 * Generates comprehensive reproducibility packs with provenance tracking
 * for research projects. Includes all data needed to reproduce or verify
 * research findings: snapshots, queries, assertions, extraction provenance.
 *
 * Also provides JSON-LD export and DOI minting delegation.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class ReproducibilityService
{
    // =========================================================================
    // REPRODUCIBILITY PACK GENERATION
    // =========================================================================

    /**
     * Generate a complete reproducibility pack for a project.
     *
     * Assembles all research artefacts with provenance metadata:
     * - Project metadata
     * - Snapshots with integrity hashes
     * - Saved queries/searches
     * - Assertions with full evidence chains
     * - Extraction jobs with provenance (model version, input hash, parameters)
     * - Pack-level SHA-256 integrity hash
     *
     * @param int $projectId The research project ID
     * @return array Complete reproducibility pack
     */
    public function generatePack(int $projectId): array
    {
        // 1. Get project with owner details
        $project = DB::table('research_project as p')
            ->leftJoin('research_researcher as r', 'p.owner_id', '=', 'r.id')
            ->where('p.id', $projectId)
            ->select(
                'p.*',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name',
                'r.email as owner_email',
                'r.orcid_id as owner_orcid',
                'r.institution as owner_institution'
            )
            ->first();

        if (!$project) {
            throw new \RuntimeException('Project not found: ' . $projectId);
        }

        $projectData = [
            'id' => (int) $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'project_type' => $project->project_type,
            'institution' => $project->institution,
            'supervisor' => $project->supervisor,
            'funding_source' => $project->funding_source,
            'grant_number' => $project->grant_number,
            'ethics_approval' => $project->ethics_approval,
            'status' => $project->status,
            'owner' => [
                'name' => trim(($project->owner_first_name ?? '') . ' ' . ($project->owner_last_name ?? '')),
                'email' => $project->owner_email,
                'orcid' => $project->owner_orcid,
                'institution' => $project->owner_institution,
            ],
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];

        // 2. Get all snapshots with hashes
        $snapshots = DB::table('research_snapshot')
            ->where('project_id', $projectId)
            ->orderBy('created_at')
            ->get()
            ->toArray();

        $snapshotData = [];
        foreach ($snapshots as $snapshot) {
            $itemCount = DB::table('research_snapshot_item')
                ->where('snapshot_id', $snapshot->id)
                ->count();

            $snapshotData[] = [
                'id' => (int) $snapshot->id,
                'title' => $snapshot->title,
                'description' => $snapshot->description,
                'hash_sha256' => $snapshot->hash_sha256,
                'item_count' => $itemCount,
                'status' => $snapshot->status,
                'query_state' => $snapshot->query_state_json ? json_decode($snapshot->query_state_json, true) : null,
                'rights_state' => $snapshot->rights_state_json ? json_decode($snapshot->rights_state_json, true) : null,
                'metadata' => $snapshot->metadata_json ? json_decode($snapshot->metadata_json, true) : null,
                'created_at' => $snapshot->created_at,
            ];
        }

        // 3. Get all saved searches/queries for the project
        $savedSearches = DB::table('research_saved_search')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get()
            ->toArray();

        $queryData = [];
        foreach ($savedSearches as $search) {
            $queryData[] = [
                'id' => (int) $search->id,
                'name' => $search->name,
                'description' => $search->description,
                'search_query' => $search->search_query,
                'search_filters' => $search->search_filters ? json_decode($search->search_filters, true) : null,
                'search_type' => $search->search_type,
                'total_results_at_save' => $search->total_results_at_save !== null ? (int) $search->total_results_at_save : null,
                'facets' => $search->facets ? json_decode($search->facets, true) : null,
                'created_at' => $search->created_at,
            ];
        }

        // 4. Get all assertions with evidence chains
        $assertions = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.project_id', $projectId)
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.orcid_id as researcher_orcid'
            )
            ->orderBy('a.id')
            ->get()
            ->toArray();

        $assertionData = [];
        foreach ($assertions as $assertion) {
            // Get evidence chain for this assertion
            $evidence = DB::table('research_assertion_evidence as e')
                ->leftJoin('research_researcher as r', 'e.added_by', '=', 'r.id')
                ->where('e.assertion_id', $assertion->id)
                ->select(
                    'e.*',
                    'r.first_name as added_by_first_name',
                    'r.last_name as added_by_last_name'
                )
                ->orderBy('e.created_at')
                ->get()
                ->toArray();

            $evidenceChain = [];
            foreach ($evidence as $ev) {
                $evidenceChain[] = [
                    'id' => (int) $ev->id,
                    'source_type' => $ev->source_type,
                    'source_id' => (int) $ev->source_id,
                    'selector' => $ev->selector_json ? json_decode($ev->selector_json, true) : null,
                    'relationship' => $ev->relationship,
                    'note' => $ev->note,
                    'added_by' => trim(($ev->added_by_first_name ?? '') . ' ' . ($ev->added_by_last_name ?? '')),
                    'created_at' => $ev->created_at,
                ];
            }

            $assertionData[] = [
                'id' => (int) $assertion->id,
                'subject_type' => $assertion->subject_type,
                'subject_id' => (int) $assertion->subject_id,
                'subject_label' => $assertion->subject_label,
                'predicate' => $assertion->predicate,
                'object_value' => $assertion->object_value,
                'object_type' => $assertion->object_type,
                'object_id' => $assertion->object_id !== null ? (int) $assertion->object_id : null,
                'object_label' => $assertion->object_label,
                'assertion_type' => $assertion->assertion_type,
                'status' => $assertion->status,
                'confidence' => $assertion->confidence !== null ? (float) $assertion->confidence : null,
                'version' => (int) $assertion->version,
                'researcher' => trim(($assertion->researcher_first_name ?? '') . ' ' . ($assertion->researcher_last_name ?? '')),
                'researcher_orcid' => $assertion->researcher_orcid,
                'evidence_chain' => $evidenceChain,
                'created_at' => $assertion->created_at,
                'updated_at' => $assertion->updated_at,
            ];
        }

        // 5. Get all extraction jobs with provenance
        $jobs = DB::table('research_extraction_job as j')
            ->leftJoin('research_researcher as r', 'j.researcher_id', '=', 'r.id')
            ->where('j.project_id', $projectId)
            ->select(
                'j.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('j.created_at')
            ->get()
            ->toArray();

        $extractionData = [];
        foreach ($jobs as $job) {
            // Get results with provenance metadata
            $results = DB::table('research_extraction_result')
                ->where('job_id', $job->id)
                ->select('id', 'object_id', 'result_type', 'confidence', 'model_version', 'input_hash', 'created_at')
                ->orderBy('id')
                ->get()
                ->toArray();

            $resultsSummary = [];
            foreach ($results as $result) {
                $resultsSummary[] = [
                    'id' => (int) $result->id,
                    'object_id' => (int) $result->object_id,
                    'result_type' => $result->result_type,
                    'confidence' => $result->confidence !== null ? (float) $result->confidence : null,
                    'model_version' => $result->model_version,
                    'input_hash' => $result->input_hash,
                    'created_at' => $result->created_at,
                ];
            }

            $extractionData[] = [
                'id' => (int) $job->id,
                'extraction_type' => $job->extraction_type,
                'parameters' => $job->parameters_json ? json_decode($job->parameters_json, true) : null,
                'status' => $job->status,
                'total_items' => (int) $job->total_items,
                'processed_items' => (int) $job->processed_items,
                'researcher' => trim(($job->researcher_first_name ?? '') . ' ' . ($job->researcher_last_name ?? '')),
                'results' => $resultsSummary,
                'created_at' => $job->created_at,
                'completed_at' => $job->completed_at,
            ];
        }

        // 6. Assemble the complete pack
        $generatedAt = date('Y-m-d H:i:s');

        $pack = [
            'project' => $projectData,
            'snapshots' => $snapshotData,
            'queries' => $queryData,
            'assertions' => $assertionData,
            'extractions' => $extractionData,
            'generated_at' => $generatedAt,
        ];

        // 7. Compute pack-level SHA-256 hash
        $packPayload = json_encode($pack, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $pack['pack_hash'] = hash('sha256', $packPayload);

        return $pack;
    }

    // =========================================================================
    // JSON-LD EXPORT
    // =========================================================================

    /**
     * Export a project as a Schema.org Dataset JSON-LD string.
     *
     * Uses RoCrateService::generateSchemaOrgDataset() if available,
     * otherwise builds a minimal JSON-LD representation directly.
     *
     * @param int $projectId The research project ID
     * @return string JSON-LD string
     */
    public function exportJsonLd(int $projectId): string
    {
        // Try to use RoCrateService if available
        try {
            $roCrateServicePath = dirname(__FILE__) . '/RoCrateService.php';
            if (file_exists($roCrateServicePath)) {
                require_once $roCrateServicePath;

                if (class_exists('RoCrateService', false)) {
                    $roCrateService = new \RoCrateService();
                    $dataset = $roCrateService->generateSchemaOrgDataset($projectId);

                    return json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }
        } catch (\Throwable $e) {
            // Fall through to minimal JSON-LD generation
        }

        // Minimal JSON-LD generation (fallback)
        $project = DB::table('research_project as p')
            ->leftJoin('research_researcher as r', 'p.owner_id', '=', 'r.id')
            ->where('p.id', $projectId)
            ->select(
                'p.*',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name',
                'r.email as owner_email',
                'r.orcid_id as owner_orcid'
            )
            ->first();

        if (!$project) {
            throw new \RuntimeException('Project not found: ' . $projectId);
        }

        $creator = [
            '@type' => 'Person',
            'name' => trim(($project->owner_first_name ?? '') . ' ' . ($project->owner_last_name ?? '')),
        ];

        if (!empty($project->owner_email)) {
            $creator['email'] = $project->owner_email;
        }

        if (!empty($project->owner_orcid)) {
            $creator['identifier'] = 'https://orcid.org/' . $project->owner_orcid;
        }

        // Count research artefacts for variableMeasured
        $snapshotCount = DB::table('research_snapshot')
            ->where('project_id', $projectId)
            ->count();

        $assertionCount = DB::table('research_assertion')
            ->where('project_id', $projectId)
            ->where('status', '!=', 'retracted')
            ->count();

        $collectionCount = DB::table('research_collection')
            ->where('project_id', $projectId)
            ->count();

        $dataset = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => $project->title,
            'description' => $project->description ?? '',
            'creator' => $creator,
            'dateCreated' => $project->created_at,
            'dateModified' => $project->updated_at,
            'variableMeasured' => [
                [
                    '@type' => 'PropertyValue',
                    'name' => 'snapshots',
                    'value' => $snapshotCount,
                ],
                [
                    '@type' => 'PropertyValue',
                    'name' => 'assertions',
                    'value' => $assertionCount,
                ],
                [
                    '@type' => 'PropertyValue',
                    'name' => 'collections',
                    'value' => $collectionCount,
                ],
            ],
        ];

        if ($project->start_date || $project->expected_end_date) {
            $temporal = $project->start_date ?? '';
            if ($project->expected_end_date) {
                $temporal .= '/' . $project->expected_end_date;
            }
            $dataset['temporalCoverage'] = $temporal;
        }

        return json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // =========================================================================
    // DOI MINTING
    // =========================================================================

    /**
     * Delegate DOI minting to ahgDoiPlugin if available.
     *
     * Checks if ahgDoiPlugin is installed and enabled via the atom_plugin table.
     * If available, builds metadata and delegates the minting request.
     * Logs a doi_minted event on success.
     *
     * @param int $projectId The research project ID
     * @return array ['success' => bool, 'doi' => string|null, 'url' => string|null, 'error' => string|null]
     */
    public function mintDoi(int $projectId): array
    {
        // Check if ahgDoiPlugin is installed and enabled
        $doiPlugin = DB::table('atom_plugin')
            ->where('name', 'ahgDoiPlugin')
            ->where('is_enabled', 1)
            ->first();

        if (!$doiPlugin) {
            return [
                'success' => false,
                'doi' => null,
                'url' => null,
                'error' => 'ahgDoiPlugin not installed',
            ];
        }

        // Get project details for DOI metadata
        $project = DB::table('research_project as p')
            ->leftJoin('research_researcher as r', 'p.owner_id', '=', 'r.id')
            ->where('p.id', $projectId)
            ->select(
                'p.*',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name',
                'r.email as owner_email',
                'r.orcid_id as owner_orcid',
                'r.institution as owner_institution'
            )
            ->first();

        if (!$project) {
            return [
                'success' => false,
                'doi' => null,
                'url' => null,
                'error' => 'Project not found: ' . $projectId,
            ];
        }

        // Build DOI metadata following DataCite schema
        $metadata = [
            'title' => $project->title,
            'description' => $project->description ?? '',
            'resource_type' => 'Dataset',
            'resource_type_general' => 'Dataset',
            'creators' => [
                [
                    'name' => trim(($project->owner_first_name ?? '') . ' ' . ($project->owner_last_name ?? '')),
                    'affiliation' => $project->owner_institution ?? $project->institution ?? '',
                    'orcid' => $project->owner_orcid ?? null,
                ],
            ],
            'publication_year' => date('Y', strtotime($project->created_at)),
            'dates' => [
                ['date' => $project->created_at, 'type' => 'Created'],
                ['date' => $project->updated_at, 'type' => 'Updated'],
            ],
        ];

        if (!empty($project->funding_source)) {
            $metadata['funding_references'] = [
                [
                    'funder_name' => $project->funding_source,
                    'award_number' => $project->grant_number ?? null,
                ],
            ];
        }

        // Attempt to load and call the DOI service
        try {
            $doiServicePath = '/usr/share/nginx/archive/atom-ahg-plugins/ahgDoiPlugin/lib/Services/DoiService.php';

            if (!file_exists($doiServicePath)) {
                return [
                    'success' => false,
                    'doi' => null,
                    'url' => null,
                    'error' => 'ahgDoiPlugin service file not found',
                ];
            }

            require_once $doiServicePath;

            if (!class_exists('DoiService', false)) {
                return [
                    'success' => false,
                    'doi' => null,
                    'url' => null,
                    'error' => 'DoiService class not found',
                ];
            }

            $doiService = new \DoiService();
            $result = $doiService->mint($metadata);

            if (!empty($result['doi'])) {
                // Log doi_minted event
                $this->logEvent(
                    (int) $project->owner_id,
                    $projectId,
                    'doi_minted',
                    'project',
                    $projectId,
                    'DOI minted: ' . $result['doi']
                );

                return [
                    'success' => true,
                    'doi' => $result['doi'],
                    'url' => $result['url'] ?? null,
                ];
            }

            return [
                'success' => false,
                'doi' => null,
                'url' => null,
                'error' => $result['error'] ?? 'DOI minting failed',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'doi' => null,
                'url' => null,
                'error' => 'DOI minting error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check DOI status for a project by querying activity log for doi_minted events.
     *
     * @param int $projectId The research project ID
     * @return object|null The latest doi_minted event or null if none found
     */
    public function getDoiStatus(int $projectId): ?object
    {
        return DB::table('research_activity_log')
            ->where('project_id', $projectId)
            ->where('activity_type', 'doi_minted')
            ->where('entity_type', 'project')
            ->where('entity_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->first();
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
