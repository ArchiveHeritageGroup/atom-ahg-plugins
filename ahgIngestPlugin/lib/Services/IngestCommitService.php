<?php

namespace AhgIngestPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class IngestCommitService
{
    protected IngestService $ingestService;

    public function __construct()
    {
        $this->ingestService = new IngestService();
    }

    // ─── Job Management ─────────────────────────────────────────────────

    public function startJob(int $sessionId): int
    {
        $rowCount = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('is_excluded', 0)
            ->where('is_valid', 1)
            ->count();

        $jobId = DB::table('ingest_job')->insertGetId([
            'session_id' => $sessionId,
            'status' => 'queued',
            'total_rows' => $rowCount,
            'processed_rows' => 0,
            'created_records' => 0,
            'created_dos' => 0,
            'error_count' => 0,
            'error_log' => json_encode([]),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->ingestService->updateSessionStatus($sessionId, 'commit');

        return $jobId;
    }

    public function getJobStatus(int $jobId): ?object
    {
        return DB::table('ingest_job')->where('id', $jobId)->first();
    }

    public function getJobBySession(int $sessionId): ?object
    {
        return DB::table('ingest_job')
            ->where('session_id', $sessionId)
            ->orderByDesc('id')
            ->first();
    }

    // ─── Record Creation ────────────────────────────────────────────────

    public function executeJob(int $jobId): void
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return;
        }

        $session = $this->ingestService->getSession($job->session_id);
        if (!$session) {
            return;
        }

        // Mark running
        DB::table('ingest_job')->where('id', $jobId)->update([
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->where('is_excluded', 0)
            ->where('is_valid', 1)
            ->orderBy('row_number')
            ->get();

        // Build legacyId → created AtoM ID map for hierarchy
        $legacyToAtomId = [];
        $errors = [];
        $createdRecords = 0;
        $createdDOs = 0;

        foreach ($rows as $row) {
            try {
                $result = $this->processRow($jobId, $row, $session, $legacyToAtomId);

                if ($result) {
                    if (!empty($result['atom_id'])) {
                        $createdRecords++;
                        if (!empty($row->legacy_id)) {
                            $legacyToAtomId[$row->legacy_id] = $result['atom_id'];
                        }
                    }
                    if (!empty($result['do_id'])) {
                        $createdDOs++;
                    }
                }

                DB::table('ingest_job')->where('id', $jobId)->update([
                    'processed_rows' => DB::raw('processed_rows + 1'),
                    'created_records' => $createdRecords,
                    'created_dos' => $createdDOs,
                ]);
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $row->row_number,
                    'error' => $e->getMessage(),
                ];

                DB::table('ingest_job')->where('id', $jobId)->update([
                    'processed_rows' => DB::raw('processed_rows + 1'),
                    'error_count' => DB::raw('error_count + 1'),
                    'error_log' => json_encode($errors),
                ]);
            }
        }

        // Post-commit: processing, derivatives, packaging, indexing

        // Virus scan (before anything else)
        if ($session->process_virus_scan) {
            try {
                $this->runVirusScan($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'virus_scan', 'error' => $e->getMessage()];
            }
        }

        try {
            $this->generateDerivatives($jobId);
        } catch (\Throwable $e) {
            $errors[] = ['stage' => 'derivatives', 'error' => $e->getMessage()];
        }

        // OCR
        if ($session->process_ocr) {
            try {
                $this->runOcr($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'ocr', 'error' => $e->getMessage()];
            }
        }

        // NER
        if ($session->process_ner) {
            try {
                $this->runNer($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'ner', 'error' => $e->getMessage()];
            }
        }

        // Summarize
        if ($session->process_summarize) {
            try {
                $this->runSummarize($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'summarize', 'error' => $e->getMessage()];
            }
        }

        // Spell check
        if ($session->process_spellcheck) {
            try {
                $this->runSpellcheck($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'spellcheck', 'error' => $e->getMessage()];
            }
        }

        // Format identification
        if ($session->process_format_id) {
            try {
                $this->runFormatIdentification($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'format_id', 'error' => $e->getMessage()];
            }
        }

        // Face detection
        if ($session->process_face_detect) {
            try {
                $this->runFaceDetection($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'face_detect', 'error' => $e->getMessage()];
            }
        }

        // Translation
        if ($session->process_translate) {
            try {
                $this->runTranslation($jobId, $session);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'translate', 'error' => $e->getMessage()];
            }
        }

        if ($session->output_generate_sip) {
            try {
                $this->buildSipPackage($jobId);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'sip', 'error' => $e->getMessage()];
            }
        }

        if ($session->output_generate_aip) {
            try {
                $this->buildAipPackage($jobId);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'aip', 'error' => $e->getMessage()];
            }
        }

        if ($session->output_generate_dip) {
            try {
                $this->buildDipPackage($jobId);
            } catch (\Throwable $e) {
                $errors[] = ['stage' => 'dip', 'error' => $e->getMessage()];
            }
        }

        try {
            $this->updateSearchIndex($jobId);
        } catch (\Throwable $e) {
            $errors[] = ['stage' => 'indexing', 'error' => $e->getMessage()];
        }

        // Generate manifest
        $manifestPath = $this->generateManifest($jobId);

        // Apply security classification if configured
        if (!empty($session->security_classification_id)) {
            $this->applySecurityClassification($jobId, $session->security_classification_id);
        }

        // Mark complete
        $finalStatus = empty($errors) ? 'completed' : (count($errors) < $createdRecords ? 'completed' : 'failed');

        DB::table('ingest_job')->where('id', $jobId)->update([
            'status' => $finalStatus,
            'error_log' => json_encode($errors),
            'manifest_path' => $manifestPath,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->ingestService->updateSessionStatus($session->id, $finalStatus === 'completed' ? 'completed' : 'failed');

        // Audit trail
        $this->logAudit('ingest_commit', [
            'session_id' => $session->id,
            'job_id' => $jobId,
            'records_created' => $createdRecords,
            'dos_created' => $createdDOs,
            'errors' => count($errors),
        ]);
    }

    public function processRow(int $jobId, object $row, object $session, array $legacyToAtomId): ?array
    {
        $enriched = json_decode($row->enriched_data, true) ?: [];
        if (empty($enriched)) {
            return null;
        }

        // Resolve parent ID
        $parentId = $this->resolveParentId($row, $session, $legacyToAtomId);

        // Create information_object via AtoM's object model
        $atomId = $this->createInformationObject($enriched, $parentId, $session);

        if (!$atomId) {
            throw new \RuntimeException("Failed to create record for row {$row->row_number}");
        }

        $doId = null;

        // Import digital object if present
        if (!empty($row->digital_object_path) && $row->digital_object_matched && file_exists($row->digital_object_path)) {
            $doId = $this->importDigitalObject($atomId, $row->digital_object_path, $session);
        }

        // Update row with created IDs
        DB::table('ingest_row')->where('id', $row->id)->update([
            'created_atom_id' => $atomId,
            'created_do_id' => $doId,
        ]);

        return ['atom_id' => $atomId, 'do_id' => $doId];
    }

    protected function resolveParentId(object $row, object $session, array $legacyToAtomId): int
    {
        // Default: root object (1) for top-level placement
        $rootId = \QubitInformationObject::ROOT_ID ?? 1;

        switch ($session->parent_placement) {
            case 'existing':
                return $session->parent_id ?: $rootId;

            case 'csv_hierarchy':
                if (!empty($row->parent_id_ref)) {
                    // Check if parent was already created in this batch
                    if (isset($legacyToAtomId[$row->parent_id_ref])) {
                        return $legacyToAtomId[$row->parent_id_ref];
                    }
                    // Check if it's a slug in AtoM
                    $slugRow = DB::table('slug')
                        ->where('slug', $row->parent_id_ref)
                        ->first();
                    if ($slugRow) {
                        return $slugRow->object_id;
                    }
                }
                return $session->parent_id ?: $rootId;

            case 'new':
                // Create a new parent if not yet created
                if ($session->parent_id) {
                    return $session->parent_id;
                }
                // First row triggers creation of the new parent
                $newParentId = $this->createInformationObject([
                    'title' => $session->new_parent_title ?: $session->title,
                    'levelOfDescription' => $session->new_parent_level ?: 'Fonds',
                    'publicationStatus' => 'Draft',
                    'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                ], $rootId, $session);

                // Cache so subsequent rows use the same parent
                DB::table('ingest_session')->where('id', $session->id)->update([
                    'parent_id' => $newParentId,
                ]);
                // Also update in-memory object
                $session->parent_id = $newParentId;

                return $newParentId;

            default: // top_level
                return $session->parent_id ?: $rootId;
        }
    }

    protected function createInformationObject(array $data, int $parentId, object $session): ?int
    {
        // Use AtoM's Propel model for proper nested-set handling
        $io = new \QubitInformationObject();
        $io->parentId = $parentId;

        if (!empty($session->repository_id)) {
            $io->repositoryId = $session->repository_id;
        }

        // Map standard columns
        $directFields = [
            'identifier', 'title', 'alternateTitle', 'extentAndMedium',
            'archivalHistory', 'acquisition', 'scopeAndContent', 'appraisal',
            'accruals', 'arrangement', 'accessConditions', 'reproductionConditions',
            'physicalCharacteristics', 'findingAids', 'relatedUnitsOfDescription',
            'locationOfOriginals', 'locationOfCopies', 'rules',
            'descriptionIdentifier', 'revisionHistory', 'sources',
        ];

        foreach ($directFields as $field) {
            if (!empty($data[$field])) {
                $io->{$field} = $data[$field];
            }
        }

        // Level of description
        if (!empty($data['levelOfDescription'])) {
            $term = DB::table('term_i18n')
                ->join('term', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID ?? 34)
                ->where('term_i18n.name', $data['levelOfDescription'])
                ->first();
            if ($term) {
                $io->levelOfDescriptionId = $term->id;
            }
        }

        // Description status
        if (!empty($data['descriptionStatus'])) {
            $term = DB::table('term_i18n')
                ->join('term', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', \QubitTaxonomy::DESCRIPTION_STATUS_ID ?? 44)
                ->where('term_i18n.name', $data['descriptionStatus'])
                ->first();
            if ($term) {
                $io->descriptionStatusId = $term->id;
            }
        }

        // Publication status
        $pubStatus = $data['publicationStatus'] ?? 'Draft';
        $statusId = ($pubStatus === 'Published')
            ? (\QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID ?? 160)
            : (\QubitTerm::PUBLICATION_STATUS_DRAFT_ID ?? 159);

        // Culture
        $culture = $data['culture'] ?? 'en';
        $io->sourceCulture = $culture;

        try {
            $io->save();
        } catch (\Throwable $e) {
            // If save partially succeeded (record created but post-save hook like
            // OpenSearch indexing failed), continue with the ID we got
            if (!$io->id) {
                throw new \RuntimeException("Failed to save IO: " . $e->getMessage());
            }
            // Record exists — OpenSearch can be re-indexed later
        }

        if (!$io->id) {
            return null;
        }

        // Set publication status via status table
        try {
            $status = new \QubitStatus();
            $status->objectId = $io->id;
            $status->typeId = \QubitTerm::STATUS_TYPE_PUBLICATION_ID ?? 158;
            $status->statusId = $statusId;
            $status->save();
        } catch (\Throwable $e) {
            // Non-fatal
        }

        // Access points (pipe-delimited)
        $this->createAccessPoints($io->id, $data);

        // Events (creators, dates)
        $this->createEvents($io->id, $data);

        return $io->id;
    }

    protected function createAccessPoints(int $ioId, array $data): void
    {
        $apMap = [
            'subjectAccessPoints' => \QubitTaxonomy::SUBJECT_ID ?? 35,
            'placeAccessPoints' => \QubitTaxonomy::PLACE_ID ?? 42,
            'genreAccessPoints' => \QubitTaxonomy::GENRE_ID ?? 78,
        ];

        foreach ($apMap as $field => $taxonomyId) {
            if (empty($data[$field])) {
                continue;
            }

            $values = array_map('trim', explode('|', $data[$field]));
            foreach ($values as $value) {
                if (empty($value)) {
                    continue;
                }

                // Find or create term
                $term = DB::table('term_i18n')
                    ->join('term', 'term.id', '=', 'term_i18n.id')
                    ->where('term.taxonomy_id', $taxonomyId)
                    ->where('term_i18n.name', $value)
                    ->first();

                $termId = $term ? $term->id : null;

                if (!$termId) {
                    // Create new term via Propel
                    try {
                        $newTerm = new \QubitTerm();
                        $newTerm->taxonomyId = $taxonomyId;
                        $newTerm->parentId = \QubitTerm::ROOT_ID ?? 110;
                        $newTerm->name = $value;
                        $newTerm->sourceCulture = 'en';
                        $newTerm->save();
                        $termId = $newTerm->id;
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                // Create object_term_relation
                try {
                    $relation = new \QubitObjectTermRelation();
                    $relation->objectId = $ioId;
                    $relation->termId = $termId;
                    $relation->save();
                } catch (\Throwable $e) {
                    // Duplicate or constraint error — skip
                }
            }
        }

        // Name access points → actor relations
        if (!empty($data['nameAccessPoints'])) {
            $names = array_map('trim', explode('|', $data['nameAccessPoints']));
            foreach ($names as $name) {
                if (empty($name)) {
                    continue;
                }
                $actor = DB::table('actor_i18n')
                    ->where('authorized_form_of_name', $name)
                    ->first();

                if ($actor) {
                    try {
                        $relation = new \QubitRelation();
                        $relation->subjectId = $ioId;
                        $relation->objectId = $actor->id;
                        $relation->typeId = \QubitTerm::NAME_ACCESS_POINT_ID ?? 519;
                        $relation->save();
                    } catch (\Throwable $e) {
                        // skip
                    }
                }
            }
        }
    }

    protected function createEvents(int $ioId, array $data): void
    {
        // Creators
        if (!empty($data['creators'])) {
            $creators = array_map('trim', explode('|', $data['creators']));
            $dates = !empty($data['creatorDates']) ? array_map('trim', explode('|', $data['creatorDates'])) : [];
            $starts = !empty($data['creatorDatesStart']) ? array_map('trim', explode('|', $data['creatorDatesStart'])) : [];
            $ends = !empty($data['creatorDatesEnd']) ? array_map('trim', explode('|', $data['creatorDatesEnd'])) : [];

            foreach ($creators as $i => $creatorName) {
                if (empty($creatorName)) {
                    continue;
                }

                // Find or create actor
                $actor = DB::table('actor_i18n')
                    ->where('authorized_form_of_name', $creatorName)
                    ->first();
                $actorId = $actor ? $actor->id : null;

                if (!$actorId) {
                    try {
                        $newActor = new \QubitActor();
                        $newActor->parentId = \QubitActor::ROOT_ID ?? 3;
                        $newActor->authorizedFormOfName = $creatorName;
                        $newActor->sourceCulture = 'en';
                        $newActor->save();
                        $actorId = $newActor->id;
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                try {
                    $event = new \QubitEvent();
                    $event->informationObjectId = $ioId;
                    $event->actorId = $actorId;
                    $event->typeId = \QubitTerm::CREATION_ID ?? 111;
                    if (!empty($dates[$i])) {
                        $event->date = $dates[$i];
                    }
                    if (!empty($starts[$i])) {
                        $event->startDate = $starts[$i];
                    }
                    if (!empty($ends[$i])) {
                        $event->endDate = $ends[$i];
                    }
                    $event->sourceCulture = 'en';
                    $event->save();
                } catch (\Throwable $e) {
                    // skip
                }
            }
        }

        // Creation dates without named creators
        if (empty($data['creators']) && !empty($data['creationDates'])) {
            $dates = array_map('trim', explode('|', $data['creationDates']));
            $starts = !empty($data['creationDatesStart']) ? array_map('trim', explode('|', $data['creationDatesStart'])) : [];
            $ends = !empty($data['creationDatesEnd']) ? array_map('trim', explode('|', $data['creationDatesEnd'])) : [];

            foreach ($dates as $i => $dateStr) {
                try {
                    $event = new \QubitEvent();
                    $event->informationObjectId = $ioId;
                    $event->typeId = \QubitTerm::CREATION_ID ?? 111;
                    $event->date = $dateStr;
                    if (!empty($starts[$i])) {
                        $event->startDate = $starts[$i];
                    }
                    if (!empty($ends[$i])) {
                        $event->endDate = $ends[$i];
                    }
                    $event->sourceCulture = 'en';
                    $event->save();
                } catch (\Throwable $e) {
                    // skip
                }
            }
        }
    }

    protected function importDigitalObject(int $ioId, string $filePath, object $session): ?int
    {
        // Use AtoM's CLI for reliable digital object import with proper
        // path structure and derivative generation
        $atomRoot = \sfConfig::get('sf_root_dir');
        $slug = DB::table('slug')->where('object_id', $ioId)->value('slug');

        if (!$slug) {
            return null;
        }

        // Use AtoM's digitalobject:load command if available,
        // otherwise fall back to Propel model
        try {
            $do = new \QubitDigitalObject();
            $do->objectId = $ioId;
            $do->usageId = \QubitTerm::MASTER_ID ?? 140;
            $do->assets = [new \QubitAsset($filePath)];
            $do->save();

            return $do->id;
        } catch (\Throwable $e) {
            // Propel save may partially succeed (DO created, OpenSearch hook crashed)
            // Check if DO was actually created
            if (isset($do->id) && $do->id) {
                return $do->id;
            }

            // Check DB for a DO that was created for this IO
            $existingDo = DB::table('digital_object')
                ->where('object_id', $ioId)
                ->first();
            if ($existingDo) {
                return $existingDo->id;
            }

            // Last resort: manual import matching AtoM's path structure
            return $this->manualImportDigitalObject($ioId, $filePath, $slug, $session);
        }
    }

    /**
     * Manual digital object import matching AtoM's expected path structure.
     */
    private function manualImportDigitalObject(int $ioId, string $filePath, string $slug, object $session): ?int
    {
        $webDir = \sfConfig::get('sf_web_dir');

        // AtoM stores under: uploads/r/<repository-slug>/<checksum-path>/
        $repoSlug = 'default';
        if (!empty($session->repository_id)) {
            $found = DB::table('slug')
                ->where('object_id', $session->repository_id)
                ->value('slug');
            if ($found) {
                $repoSlug = $found;
            }
        }

        $checksum = hash_file('sha256', $filePath);
        // AtoM uses checksum chars as subdirectories: /a/b/c/<full-checksum>/
        $hashPath = substr($checksum, 0, 1) . '/'
                  . substr($checksum, 1, 1) . '/'
                  . substr($checksum, 2, 1) . '/'
                  . $checksum;

        $uploadsDir = $webDir . '/uploads/r/' . $repoSlug . '/' . $hashPath;
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0755, true);
        }

        $destPath = $uploadsDir . '/' . basename($filePath);
        if (!copy($filePath, $destPath)) {
            return null;
        }

        $relativePath = str_replace($webDir, '', $uploadsDir) . '/';

        // Create object row for DO
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitDigitalObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('digital_object')->insert([
            'id' => $objectId,
            'object_id' => $ioId,
            'usage_id' => \QubitTerm::MASTER_ID ?? 140,
            'name' => basename($filePath),
            'path' => $relativePath,
            'mime_type' => mime_content_type($filePath) ?: 'application/octet-stream',
            'byte_size' => filesize($filePath),
            'checksum_type' => 'sha256',
            'checksum' => $checksum,
        ]);

        // Generate derivatives using AtoM CLI
        $atomRoot = \sfConfig::get('sf_root_dir');
        $ioSlug = DB::table('slug')->where('object_id', $ioId)->value('slug');
        if ($ioSlug) {
            $cmd = sprintf(
                'php %s/symfony digitalobject:regen-derivatives --slug=%s --force 2>&1',
                escapeshellarg($atomRoot),
                escapeshellarg($ioSlug)
            );
            @exec($cmd);
        }

        return $objectId;
    }

    // ─── Post-Commit Operations ─────────────────────────────────────────

    public function generateDerivatives(int $jobId): void
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return;
        }

        $session = $this->ingestService->getSession($job->session_id);
        if (!$session) {
            return;
        }

        if (!$session->derivative_thumbnails && !$session->derivative_reference) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_do_id')
            ->get();

        $atomRoot = \sfConfig::get('sf_root_dir');

        foreach ($rows as $row) {
            $slug = DB::table('slug')->where('object_id', $row->created_atom_id)->value('slug');
            if (!$slug) {
                continue;
            }

            try {
                $cmd = sprintf(
                    'php %s/symfony digitalobject:regen-derivatives --slug=%s --force 2>&1',
                    escapeshellarg($atomRoot),
                    escapeshellarg($slug)
                );
                exec($cmd, $output, $returnCode);
            } catch (\Throwable $e) {
                // Non-fatal, continue with next
            }
        }
    }

    public function buildSipPackage(int $jobId): ?int
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return null;
        }

        $session = $this->ingestService->getSession($job->session_id);

        // Try to use PreservationService if available
        if (class_exists('\AhgPreservationPlugin\PreservationService')) {
            try {
                $preservationService = new \AhgPreservationPlugin\PreservationService();

                $rows = DB::table('ingest_row')
                    ->where('session_id', $session->id)
                    ->whereNotNull('created_atom_id')
                    ->get();

                $objectIds = [];
                foreach ($rows as $row) {
                    $objectIds[] = $row->created_atom_id;
                }

                // Generate checksums for all ingested objects
                foreach ($objectIds as $oid) {
                    $preservationService->generateChecksums($oid, ['sha256']);
                }

                // Log preservation event
                $preservationService->logEvent('ingest_sip', [
                    'session_id' => $session->id,
                    'object_count' => count($objectIds),
                ]);

                return count($objectIds);
            } catch (\Throwable $e) {
                // PreservationService not available or error
            }
        }

        // Fallback: create a simple SIP manifest
        $sipPath = $session->output_sip_path ?: (\sfConfig::get('sf_upload_dir') . '/sip');
        if (!is_dir($sipPath)) {
            @mkdir($sipPath, 0755, true);
        }

        $sipManifest = $sipPath . '/sip_' . $session->id . '_' . date('Ymd_His') . '.json';
        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        $sipData = [
            'type' => 'SIP',
            'session_id' => $session->id,
            'created_at' => date('c'),
            'objects' => [],
        ];

        foreach ($rows as $row) {
            $sipData['objects'][] = [
                'atom_id' => $row->created_atom_id,
                'title' => $row->title,
                'checksum' => $row->checksum_sha256,
                'digital_object' => $row->digital_object_path,
            ];
        }

        file_put_contents($sipManifest, json_encode($sipData, JSON_PRETTY_PRINT));

        DB::table('ingest_job')->where('id', $jobId)->update([
            'sip_package_id' => $session->id,
        ]);

        return $session->id;
    }

    public function buildAipPackage(int $jobId): ?int
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return null;
        }

        $session = $this->ingestService->getSession($job->session_id);
        $aipPath = $session->output_aip_path ?: (\sfConfig::get('sf_upload_dir') . '/aip');
        if (!is_dir($aipPath)) {
            @mkdir($aipPath, 0755, true);
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        // AIP: long-term archival package with preservation metadata + original files
        $aipDir = $aipPath . '/aip_' . $session->id . '_' . date('Ymd_His');
        @mkdir($aipDir, 0755, true);
        @mkdir($aipDir . '/metadata', 0755, true);
        @mkdir($aipDir . '/objects', 0755, true);

        $aipManifest = [
            'type' => 'AIP',
            'session_id' => $session->id,
            'created_at' => date('c'),
            'standard' => $session->standard,
            'sector' => $session->sector,
            'objects' => [],
        ];

        foreach ($rows as $row) {
            $entry = [
                'atom_id' => $row->created_atom_id,
                'title' => $row->title,
                'level' => $row->level_of_description,
                'checksum_sha256' => $row->checksum_sha256,
                'metadata' => json_decode($row->enriched_data, true),
            ];

            // Copy original digital object into AIP objects/ directory
            if (!empty($row->digital_object_path) && file_exists($row->digital_object_path)) {
                $destFile = $aipDir . '/objects/' . basename($row->digital_object_path);
                @copy($row->digital_object_path, $destFile);
                $entry['original_file'] = basename($row->digital_object_path);
                $entry['mime_type'] = mime_content_type($row->digital_object_path);
                $entry['file_size'] = filesize($row->digital_object_path);
            }

            // Copy extracted metadata if available
            if (!empty($row->metadata_extracted)) {
                $metaFile = $aipDir . '/metadata/' . ($row->legacy_id ?: $row->row_number) . '_metadata.json';
                file_put_contents($metaFile, $row->metadata_extracted);
                $entry['metadata_file'] = basename($metaFile);
            }

            $aipManifest['objects'][] = $entry;
        }

        // Write AIP manifest
        file_put_contents($aipDir . '/manifest.json', json_encode($aipManifest, JSON_PRETTY_PRINT));

        // Write METS-like preservation info
        $premis = [
            'package_type' => 'AIP',
            'creation_date' => date('c'),
            'creator' => 'ahgIngestPlugin',
            'object_count' => count($rows),
            'fixity_algorithm' => 'SHA-256',
            'preservation_level' => 'full',
        ];
        file_put_contents($aipDir . '/metadata/premis.json', json_encode($premis, JSON_PRETTY_PRINT));

        DB::table('ingest_job')->where('id', $jobId)->update([
            'aip_package_id' => $session->id,
        ]);

        return $session->id;
    }

    public function buildDipPackage(int $jobId): ?int
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return null;
        }

        $session = $this->ingestService->getSession($job->session_id);
        $dipPath = $session->output_dip_path ?: (\sfConfig::get('sf_upload_dir') . '/dip');
        if (!is_dir($dipPath)) {
            @mkdir($dipPath, 0755, true);
        }

        $dipManifest = $dipPath . '/dip_' . $session->id . '_' . date('Ymd_His') . '.json';
        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        $dipData = [
            'type' => 'DIP',
            'session_id' => $session->id,
            'created_at' => date('c'),
            'objects' => [],
        ];

        foreach ($rows as $row) {
            $slug = DB::table('slug')->where('object_id', $row->created_atom_id)->value('slug');
            $dipData['objects'][] = [
                'atom_id' => $row->created_atom_id,
                'slug' => $slug,
                'title' => $row->title,
                'access_url' => '/' . ($slug ?: $row->created_atom_id),
            ];
        }

        file_put_contents($dipManifest, json_encode($dipData, JSON_PRETTY_PRINT));

        DB::table('ingest_job')->where('id', $jobId)->update([
            'dip_package_id' => $session->id,
        ]);

        return $session->id;
    }

    public function updateSearchIndex(int $jobId): void
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $job->session_id)
            ->whereNotNull('created_atom_id')
            ->get();

        foreach ($rows as $row) {
            try {
                $io = \QubitInformationObject::getById($row->created_atom_id);
                if ($io) {
                    \QubitSearch::getInstance()->update($io);
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    // ─── Manifest ───────────────────────────────────────────────────────

    public function generateManifest(int $jobId): string
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        $session = $this->ingestService->getSession($job->session_id);

        $manifestDir = \sfConfig::get('sf_upload_dir') . '/ingest/manifests';
        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0755, true);
        }

        $manifestPath = $manifestDir . '/manifest_' . $job->session_id . '_' . date('Ymd_His') . '.csv';

        $rows = DB::table('ingest_row')
            ->where('session_id', $job->session_id)
            ->orderBy('row_number')
            ->get();

        $handle = fopen($manifestPath, 'w');
        fputcsv($handle, [
            'row_number', 'legacy_id', 'title', 'level_of_description',
            'created_atom_id', 'created_do_id', 'slug', 'is_excluded', 'is_valid',
        ]);

        foreach ($rows as $row) {
            $slug = null;
            if ($row->created_atom_id) {
                $slug = DB::table('slug')->where('object_id', $row->created_atom_id)->value('slug');
            }

            fputcsv($handle, [
                $row->row_number,
                $row->legacy_id,
                $row->title,
                $row->level_of_description,
                $row->created_atom_id,
                $row->created_do_id,
                $slug,
                $row->is_excluded,
                $row->is_valid,
            ]);
        }

        fclose($handle);

        return $manifestPath;
    }

    // ─── Rollback ───────────────────────────────────────────────────────

    public function rollback(int $jobId): int
    {
        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        if (!$job) {
            return 0;
        }

        $session = $this->ingestService->getSession($job->session_id);

        $rows = DB::table('ingest_row')
            ->where('session_id', $job->session_id)
            ->whereNotNull('created_atom_id')
            ->orderByDesc('row_number') // Reverse order to handle children first
            ->get();

        $deleted = 0;

        foreach ($rows as $row) {
            try {
                // Delete digital object first
                if ($row->created_do_id) {
                    $do = \QubitDigitalObject::getById($row->created_do_id);
                    if ($do) {
                        $do->delete();
                    }
                }

                // Delete information object
                $io = \QubitInformationObject::getById($row->created_atom_id);
                if ($io) {
                    // Remove from search index
                    try {
                        \QubitSearch::getInstance()->delete($io);
                    } catch (\Throwable $e) {
                        // Non-fatal
                    }

                    $io->delete();
                    $deleted++;
                }

                // Clear created IDs
                DB::table('ingest_row')->where('id', $row->id)->update([
                    'created_atom_id' => null,
                    'created_do_id' => null,
                ]);
            } catch (\Throwable $e) {
                // Log but continue
            }
        }

        // Update job status
        DB::table('ingest_job')->where('id', $jobId)->update([
            'status' => 'cancelled',
        ]);

        $this->ingestService->updateSessionStatus($job->session_id, 'cancelled');

        // Audit trail
        $this->logAudit('ingest_rollback', [
            'session_id' => $job->session_id,
            'job_id' => $jobId,
            'records_deleted' => $deleted,
        ]);

        return $deleted;
    }

    // ─── Security Classification ────────────────────────────────────────

    protected function applySecurityClassification(int $jobId, int $classificationId): void
    {
        if (!class_exists('\AtomExtensions\Services\SecurityClearanceService')) {
            return;
        }

        $job = DB::table('ingest_job')->where('id', $jobId)->first();
        $rows = DB::table('ingest_row')
            ->where('session_id', $job->session_id)
            ->whereNotNull('created_atom_id')
            ->get();

        $userId = DB::table('ingest_session')
            ->where('id', $job->session_id)
            ->value('user_id');

        foreach ($rows as $row) {
            try {
                \AtomExtensions\Services\SecurityClearanceService::classifyObject(
                    $row->created_atom_id,
                    $classificationId,
                    ['inherit_to_children' => 1],
                    $userId
                );
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    // ─── AI & Processing ────────────────────────────────────────────────

    protected function runVirusScan(int $jobId, object $session): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgPreservationPlugin';
        $serviceFile = $pluginDir . '/lib/PreservationService.php';
        if (!file_exists($serviceFile)) {
            return;
        }
        require_once $serviceFile;

        if (!class_exists('PreservationService')) {
            return;
        }

        $svc = new \PreservationService();
        if (!$svc->isClamAvAvailable()) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_do_id')
            ->get();

        foreach ($rows as $row) {
            try {
                $result = $svc->scanForVirus($row->created_do_id, true, 'ingest');
                if (($result['status'] ?? '') === 'infected') {
                    DB::table('ingest_validation')->insert([
                        'session_id' => $session->id,
                        'row_number' => $row->row_number,
                        'severity' => 'error',
                        'field_name' => 'digitalObjectPath',
                        'message' => 'VIRUS DETECTED: ' . ($result['threat_name'] ?? 'unknown') . ' — file quarantined',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $e) {
                // Non-fatal per file
            }
        }
    }

    protected function runOcr(int $jobId, object $session): void
    {
        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_do_id')
            ->get();

        foreach ($rows as $row) {
            if (empty($row->digital_object_path) || !file_exists($row->digital_object_path)) {
                continue;
            }

            $mime = mime_content_type($row->digital_object_path);
            $text = null;

            try {
                if (strpos($mime, 'image/') === 0) {
                    $text = shell_exec('tesseract ' . escapeshellarg($row->digital_object_path) . ' stdout 2>/dev/null');
                } elseif ($mime === 'application/pdf') {
                    $text = shell_exec('pdftotext -enc UTF-8 ' . escapeshellarg($row->digital_object_path) . ' - 2>/dev/null');
                }
            } catch (\Throwable $e) {
                continue;
            }

            if (!empty(trim($text ?? ''))) {
                // Store OCR text in enriched_data
                $enriched = json_decode($row->enriched_data, true) ?: [];
                $enriched['_ocr_text'] = trim($text);
                DB::table('ingest_row')->where('id', $row->id)->update([
                    'enriched_data' => json_encode($enriched),
                ]);

                // Also store in iiif_ocr_text if available
                try {
                    if (DB::getSchemaBuilder()->hasTable('iiif_ocr_text')) {
                        DB::table('iiif_ocr_text')->updateOrInsert(
                            ['information_object_id' => $row->created_atom_id],
                            ['ocr_text' => trim($text), 'source' => 'ingest_ocr', 'updated_at' => date('Y-m-d H:i:s')]
                        );
                    }
                } catch (\Throwable $e) {
                    // Table may not exist
                }
            }
        }
    }

    protected function runNer(int $jobId, object $session): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
        $serviceFile = $pluginDir . '/lib/Services/NerService.php';
        if (!file_exists($serviceFile)) {
            return;
        }
        require_once $serviceFile;

        if (!class_exists('ahgNerService')) {
            return;
        }

        $svc = new \ahgNerService();

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        foreach ($rows as $row) {
            $enriched = json_decode($row->enriched_data, true) ?: [];
            // Build text from title + scope + archival history + OCR
            $text = implode('. ', array_filter([
                $enriched['title'] ?? '',
                $enriched['scopeAndContent'] ?? '',
                $enriched['archivalHistory'] ?? '',
                $enriched['_ocr_text'] ?? '',
            ]));

            if (strlen(trim($text)) < 20) {
                continue;
            }

            try {
                $result = $svc->extract($text);
                if (!empty($result['entities'])) {
                    // Create access points from extracted entities
                    foreach ($result['entities'] as $entity) {
                        $type = $entity['type'] ?? '';
                        $value = $entity['text'] ?? '';
                        if (empty($value)) continue;

                        $taxonomyId = null;
                        if ($type === 'PERSON' || $type === 'ORG') {
                            // Name access point via relation
                            $this->createNameAccessPoint($row->created_atom_id, $value);
                        } elseif ($type === 'GPE') {
                            $taxonomyId = \QubitTaxonomy::PLACE_ID ?? 42;
                        }

                        if ($taxonomyId) {
                            $this->createTermAccessPoint($row->created_atom_id, $value, $taxonomyId);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal per row
            }
        }
    }

    protected function createNameAccessPoint(int $ioId, string $name): void
    {
        $actor = DB::table('actor_i18n')->where('authorized_form_of_name', $name)->first();
        if ($actor) {
            try {
                $relation = new \QubitRelation();
                $relation->subjectId = $ioId;
                $relation->objectId = $actor->id;
                $relation->typeId = \QubitTerm::NAME_ACCESS_POINT_ID ?? 519;
                $relation->save();
            } catch (\Throwable $e) {
                // Duplicate
            }
        }
    }

    protected function createTermAccessPoint(int $ioId, string $value, int $taxonomyId): void
    {
        $term = DB::table('term_i18n')
            ->join('term', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', $value)
            ->first();

        $termId = $term ? $term->id : null;

        if (!$termId) {
            try {
                $newTerm = new \QubitTerm();
                $newTerm->taxonomyId = $taxonomyId;
                $newTerm->parentId = \QubitTerm::ROOT_ID ?? 110;
                $newTerm->name = $value;
                $newTerm->sourceCulture = 'en';
                $newTerm->save();
                $termId = $newTerm->id;
            } catch (\Throwable $e) {
                return;
            }
        }

        try {
            $relation = new \QubitObjectTermRelation();
            $relation->objectId = $ioId;
            $relation->termId = $termId;
            $relation->save();
        } catch (\Throwable $e) {
            // Duplicate
        }
    }

    protected function runSummarize(int $jobId, object $session): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
        $serviceFile = $pluginDir . '/lib/Services/NerService.php';
        if (!file_exists($serviceFile)) {
            return;
        }
        require_once $serviceFile;

        if (!class_exists('ahgNerService')) {
            return;
        }

        $svc = new \ahgNerService();
        if (!$svc->isSummarizerAvailable()) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        foreach ($rows as $row) {
            $enriched = json_decode($row->enriched_data, true) ?: [];
            $text = $enriched['scopeAndContent'] ?? $enriched['_ocr_text'] ?? '';

            if (strlen(trim($text)) < 100) {
                continue;
            }

            try {
                $result = $svc->summarize($text);
                if (!empty($result['summary'])) {
                    // Store summary as scope and content if it was empty
                    if (empty($enriched['scopeAndContent'])) {
                        DB::table('information_object_i18n')
                            ->where('id', $row->created_atom_id)
                            ->update(['scope_and_content' => $result['summary']]);
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    protected function runSpellcheck(int $jobId, object $session): void
    {
        $aspell = trim(shell_exec('which aspell 2>/dev/null') ?? '');
        if (empty($aspell)) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        foreach ($rows as $row) {
            $enriched = json_decode($row->enriched_data, true) ?: [];
            $fieldsToCheck = ['title', 'scopeAndContent', 'archivalHistory'];
            $allErrors = [];

            foreach ($fieldsToCheck as $field) {
                $text = $enriched[$field] ?? '';
                if (strlen(trim($text)) < 5) continue;

                $proc = proc_open(
                    'aspell -a --lang=en 2>/dev/null',
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes
                );
                if (!is_resource($proc)) continue;

                fwrite($pipes[0], $text);
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);

                // Parse aspell output for misspelled words
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/^& (\S+)/', $line, $m)) {
                        $allErrors[] = ['field' => $field, 'word' => $m[1]];
                    }
                }
            }

            if (!empty($allErrors)) {
                // Store spellcheck results as validation warnings
                $words = array_column(array_slice($allErrors, 0, 10), 'word');
                DB::table('ingest_validation')->insert([
                    'session_id' => $session->id,
                    'row_number' => $row->row_number,
                    'severity' => 'warning',
                    'field_name' => 'spellcheck',
                    'message' => 'Possible misspellings: ' . implode(', ', $words),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    protected function runFormatIdentification(int $jobId, object $session): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgPreservationPlugin';
        $serviceFile = $pluginDir . '/lib/PreservationService.php';
        if (!file_exists($serviceFile)) {
            return;
        }
        require_once $serviceFile;

        if (!class_exists('PreservationService')) {
            return;
        }

        $svc = new \PreservationService();
        if (!$svc->isSiegfriedAvailable()) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_do_id')
            ->get();

        foreach ($rows as $row) {
            try {
                $svc->identifyFormat($row->created_do_id, true);
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    protected function runFaceDetection(int $jobId, object $session): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
        $serviceFile = $pluginDir . '/lib/Services/ahgFaceDetectionService.php';
        if (!file_exists($serviceFile)) {
            return;
        }
        require_once $serviceFile;

        if (!class_exists('ahgFaceDetectionService')) {
            return;
        }

        $svc = new \ahgFaceDetectionService();

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_do_id')
            ->get();

        foreach ($rows as $row) {
            if (empty($row->digital_object_path) || !file_exists($row->digital_object_path)) {
                continue;
            }

            $mime = mime_content_type($row->digital_object_path);
            if (strpos($mime, 'image/') !== 0) {
                continue;
            }

            try {
                $faces = $svc->detectFaces($row->digital_object_path);
                if (!empty($faces)) {
                    $matched = $svc->matchToAuthorities($faces, $row->digital_object_path);
                    if (!empty($matched)) {
                        $svc->linkFacesToInformationObject($matched, $row->created_atom_id);
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    protected function runTranslation(int $jobId, object $session): void
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
        $serviceFile = $pluginDir . '/lib/Services/JobQueueService.php';
        if (!file_exists($serviceFile)) {
            return;
        }
        require_once $serviceFile;

        $targetLang = $session->process_translate_lang ?: 'af';

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_atom_id')
            ->get();

        foreach ($rows as $row) {
            $enriched = json_decode($row->enriched_data, true) ?: [];
            $title = $enriched['title'] ?? '';
            $scope = $enriched['scopeAndContent'] ?? '';

            if (empty(trim($title)) && empty(trim($scope))) {
                continue;
            }

            // Use Python Argos Translate via CLI
            $fieldsToTranslate = [
                'title' => $title,
                'scope_and_content' => $scope,
            ];

            foreach ($fieldsToTranslate as $dbField => $text) {
                if (empty(trim($text))) continue;

                $escaped = escapeshellarg($text);
                $cmd = "python3 -c \"
import sys
try:
    from argostranslate import translate
    t = translate.get_translation_from_codes('en', '{$targetLang}')
    if t: print(t.translate(sys.argv[1]))
    else: print('')
except: print('')
\" {$escaped} 2>/dev/null";

                $translated = trim(shell_exec($cmd) ?? '');
                if (!empty($translated) && $translated !== $text) {
                    // Store as i18n record for target culture
                    try {
                        DB::table('information_object_i18n')->updateOrInsert(
                            ['id' => $row->created_atom_id, 'culture' => $targetLang],
                            [$dbField => $translated]
                        );
                    } catch (\Throwable $e) {
                        // Non-fatal
                    }
                }
            }
        }
    }

    // ─── Audit Trail ────────────────────────────────────────────────────

    protected function logAudit(string $action, array $data): void
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('audit_trail')) {
                DB::table('audit_trail')->insert([
                    'user_id' => $data['user_id'] ?? null,
                    'action' => $action,
                    'entity_type' => 'ingest_session',
                    'entity_id' => $data['session_id'] ?? null,
                    'details' => json_encode($data),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            // Audit trail table may not exist
        }
    }
}
