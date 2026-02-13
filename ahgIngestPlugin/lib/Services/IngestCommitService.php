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
            } catch (\Exception $e) {
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

        // Post-commit: derivatives, packaging, indexing
        try {
            $this->generateDerivatives($jobId);
        } catch (\Exception $e) {
            $errors[] = ['stage' => 'derivatives', 'error' => $e->getMessage()];
        }

        if ($session->output_generate_sip) {
            try {
                $this->buildSipPackage($jobId);
            } catch (\Exception $e) {
                $errors[] = ['stage' => 'sip', 'error' => $e->getMessage()];
            }
        }

        if ($session->output_generate_dip) {
            try {
                $this->buildDipPackage($jobId);
            } catch (\Exception $e) {
                $errors[] = ['stage' => 'dip', 'error' => $e->getMessage()];
            }
        }

        try {
            $this->updateSearchIndex($jobId);
        } catch (\Exception $e) {
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
                    'culture' => 'en',
                ], $rootId, $session);

                // Cache so subsequent rows use the same parent
                DB::table('ingest_session')->where('id', $session->id)->update([
                    'parent_id' => $newParentId,
                ]);

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
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to save IO: " . $e->getMessage());
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
        } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // Create object_term_relation
                try {
                    $relation = new \QubitObjectTermRelation();
                    $relation->objectId = $ioId;
                    $relation->termId = $termId;
                    $relation->save();
                } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
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
                } catch (\Exception $e) {
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
                } catch (\Exception $e) {
                    // skip
                }
            }
        }
    }

    protected function importDigitalObject(int $ioId, string $filePath, object $session): ?int
    {
        try {
            $do = new \QubitDigitalObject();
            $do->informationObjectId = $ioId;
            $do->usageId = \QubitTerm::MASTER_ID ?? 166;
            $do->assets = [new \QubitAsset($filePath)];
            $do->save();

            return $do->id;
        } catch (\Exception $e) {
            // Fallback: copy file to uploads and link
            $uploadsDir = \sfConfig::get('sf_upload_dir') . '/r/' . sprintf('%09d', $ioId);
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0755, true);
            }

            $destPath = $uploadsDir . '/' . basename($filePath);
            if (copy($filePath, $destPath)) {
                // Create DO record directly
                $objectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitDigitalObject',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('digital_object')->insert([
                    'id' => $objectId,
                    'information_object_id' => $ioId,
                    'usage_id' => \QubitTerm::MASTER_ID ?? 166,
                    'name' => basename($filePath),
                    'path' => str_replace(\sfConfig::get('sf_web_dir'), '', $destPath),
                    'mime_type' => mime_content_type($filePath),
                    'byte_size' => filesize($filePath),
                    'checksum_type' => 'sha256',
                    'checksum' => hash_file('sha256', $filePath),
                ]);

                return $objectId;
            }
        }

        return null;
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

        // Only if thumbnails or reference images requested
        if (!$session->derivative_thumbnails && !$session->derivative_reference) {
            return;
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $session->id)
            ->whereNotNull('created_do_id')
            ->get();

        foreach ($rows as $row) {
            try {
                $do = \QubitDigitalObject::getById($row->created_do_id);
                if ($do) {
                    if ($session->derivative_thumbnails) {
                        $do->createThumbnail();
                    }
                    if ($session->derivative_reference) {
                        $do->createReferenceImage();
                    }
                }
            } catch (\Exception $e) {
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
            } catch (\Exception $e) {
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
            } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
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
            } catch (\Exception $e) {
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
            } catch (\Exception $e) {
                // Non-fatal
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
        } catch (\Exception $e) {
            // Audit trail table may not exist
        }
    }
}
