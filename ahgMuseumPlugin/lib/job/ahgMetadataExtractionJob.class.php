<?php

/**
 * Metadata Extraction Job
 *
 * Background job for extracting metadata from digital objects
 * Supports images, PDFs, Office docs, video, audio, and face detection
 *
 * @package    arMetadataExtractorPlugin
 * @subpackage lib/job
 * @author     Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgMetadataExtractionJob extends arBaseJob
{
    // Term IDs
    const TERM_CREATION_ID = 111;
    const TERM_NAME_ACCESS_POINT_ID = 177;

    // Taxonomy IDs
    const TAXONOMY_SUBJECT_ID = 35;

    // Root IDs
    const ROOT_TERM_SUBJECT_ID = 110;

    /**
     * @see arBaseJob::$requiredParameters
     */
    protected $requiredParameters = ['operation'];

    /**
     * Job name for display
     */
    protected $jobName = 'Metadata Extraction';

    /**
     * Execute the job
     */
    public function runJob($parameters)
    {
        $operation = $parameters['operation'];

        $this->info('Starting metadata extraction job: ' . $operation);

        switch ($operation) {
            case 'extract_single':
                return $this->extractSingle($parameters);

            case 'extract_bulk':
                return $this->extractBulk($parameters);

            case 'detect_faces':
                return $this->detectFaces($parameters);

            case 'index_authority_face':
                return $this->indexAuthorityFace($parameters);

            case 'match_faces':
                return $this->matchFaces($parameters);

            case 'reprocess_all':
                return $this->reprocessAll($parameters);

            default:
                $this->error('Unknown operation: ' . $operation);
                return false;
        }
    }

    /**
     * Extract metadata from a single digital object
     */
    protected function extractSingle($parameters)
    {
        $digitalObjectId = $parameters['digital_object_id'] ?? null;

        if (!$digitalObjectId) {
            $this->error('digital_object_id is required');
            return false;
        }

        $digitalObject = $this->getDigitalObject($digitalObjectId);

        if (!$digitalObject) {
            $this->error('Digital object not found: ' . $digitalObjectId);
            return false;
        }

        $filePath = $this->getDigitalObjectFilePath($digitalObject);

        if (!file_exists($filePath)) {
            $this->error('File not found: ' . $filePath);
            return false;
        }

        $this->info('Extracting metadata from: ' . basename($filePath));

        $startTime = microtime(true);

        // Extract metadata
        $extractor = new ahgUniversalMetadataExtractor($filePath);
        $metadata = $extractor->extractAll();

        $processingTime = (microtime(true) - $startTime) * 1000;

        // Store metadata
        $this->storeMetadata($digitalObjectId, $metadata, $extractor);

        // Auto-populate AtoM fields if enabled
        if ($this->getSetting('meta_auto_populate', true)) {
            $this->populateAtomFields($digitalObject, $metadata, $extractor);
        }

        // Face detection if enabled
        if ($this->getSetting('face_detect_enabled', false) && $extractor->getFileType() === 'image') {
            $this->processFaces($digitalObjectId, $filePath);
        }

        // Log extraction
        $this->logExtraction($digitalObjectId, $filePath, $extractor, 'success', $processingTime);

        $this->info('Metadata extraction complete');

        return true;
    }

    /**
     * Extract metadata from multiple digital objects
     */
    protected function extractBulk($parameters)
    {
        $filters = $parameters['filters'] ?? [];
        $limit = $parameters['limit'] ?? 1000;

        // Build query using Laravel
        $query = DB::table('digital_object as do')
            ->leftJoin('digital_object_metadata as dom', 'do.id', '=', 'dom.digital_object_id')
            ->whereNull('dom.id')
            ->select('do.id', 'do.path', 'do.mime_type');

        // Apply filters
        if (!empty($filters['mime_type'])) {
            $query->where('do.mime_type', 'LIKE', $filters['mime_type'] . '%');
        }

        if (!empty($filters['created_after'])) {
            $query->where('do.created_at', '>=', $filters['created_after']);
        }

        $objects = $query->orderBy('do.id')
            ->limit($limit)
            ->get();

        $total = count($objects);
        $this->info("Processing {$total} digital objects");

        $processed = 0;
        $errors = 0;

        foreach ($objects as $obj) {
            try {
                $filePath = $this->buildFilePath($obj->path);

                if (!file_exists($filePath)) {
                    $this->logExtraction($obj->id, $filePath, null, 'failed', 0, 'File not found');
                    $errors++;
                    continue;
                }

                $extractor = new ahgUniversalMetadataExtractor($filePath, $obj->mime_type);
                $metadata = $extractor->extractAll();

                $this->storeMetadata($obj->id, $metadata, $extractor);

                $processed++;

                // Update progress
                if ($processed % 10 == 0) {
                    if (method_exists($this->job, 'setStatusNote')) {
                        $this->job->setStatusNote(sprintf('Processed %d of %d', $processed, $total));
                    }
                    if (method_exists($this->job, 'save')) {
                        $this->job->save();
                    }
                }
            } catch (Exception $e) {
                $this->error('Error processing ' . $obj->id . ': ' . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Bulk extraction complete. Processed: {$processed}, Errors: {$errors}");

        return $errors < ($total * 0.1); // Success if less than 10% errors
    }

    /**
     * Detect faces in a digital object
     */
    protected function detectFaces($parameters)
    {
        $digitalObjectId = $parameters['digital_object_id'] ?? null;

        if (!$digitalObjectId) {
            $this->error('digital_object_id is required');
            return false;
        }

        $digitalObject = $this->getDigitalObject($digitalObjectId);

        if (!$digitalObject) {
            $this->error('Digital object not found');
            return false;
        }

        $filePath = $this->getDigitalObjectFilePath($digitalObject);

        return $this->processFaces($digitalObjectId, $filePath);
    }

    /**
     * Process faces in an image
     */
    protected function processFaces($digitalObjectId, $filePath)
    {
        $backend = $this->getSetting('face_detect_backend', 'local');

        $config = [
            'max_faces' => $this->getSetting('face_max_per_image', 20),
            'confidence_threshold' => $this->getSetting('face_confidence_threshold', 0.8),
            'save_face_crops' => $this->getSetting('face_save_crops', true),
        ];

        // AWS config
        if ($backend === 'aws_rekognition') {
            $config['aws_region'] = $this->getSetting('aws_rekognition_region', 'us-east-1');
            $config['aws_collection_id'] = $this->getSetting('aws_rekognition_collection', 'atom_faces');
        }

        // Azure config
        if ($backend === 'azure') {
            $config['azure_endpoint'] = $this->getSetting('azure_face_endpoint', '');
            $config['azure_key'] = $this->getSetting('azure_face_key', '');
        }

        $faceService = new ahgFaceDetectionService($backend, $config);

        $this->info('Detecting faces using backend: ' . $backend);

        // Detect faces
        $faces = $faceService->detectFaces($filePath);

        if (empty($faces)) {
            $this->info('No faces detected');
            return true;
        }

        $this->info(sprintf('Detected %d faces', count($faces)));

        // Match to authorities if enabled
        if ($this->getSetting('face_auto_match', true)) {
            $faces = $faceService->matchToAuthorities($faces, $filePath);
        }

        // Store face data
        $this->storeFaces($digitalObjectId, $faces);

        // Auto-link to information object if enabled
        if ($this->getSetting('face_auto_link', false)) {
            $digitalObject = $this->getDigitalObject($digitalObjectId);

            if ($digitalObject && $digitalObject->object_id) {
                $linked = $faceService->linkFacesToInformationObject($faces, $digitalObject->object_id);
                $this->info("Auto-linked {$linked} faces to authority records");
            }
        }

        // Log errors
        foreach ($faceService->getErrors() as $error) {
            $this->error($error);
        }

        return true;
    }

    /**
     * Index a face for an authority record
     */
    protected function indexAuthorityFace($parameters)
    {
        $authorityId = $parameters['authority_id'] ?? null;
        $imagePath = $parameters['image_path'] ?? null;
        $digitalObjectId = $parameters['digital_object_id'] ?? null;

        if (!$authorityId) {
            $this->error('authority_id is required');
            return false;
        }

        // Get image path from digital object if not provided
        if (!$imagePath && $digitalObjectId) {
            $digitalObject = $this->getDigitalObject($digitalObjectId);
            if ($digitalObject) {
                $imagePath = $this->getDigitalObjectFilePath($digitalObject);
            }
        }

        if (!$imagePath || !file_exists($imagePath)) {
            $this->error('Image not found');
            return false;
        }

        $backend = $this->getSetting('face_detect_backend', 'local');
        $faceService = new ahgFaceDetectionService($backend);

        $this->info('Indexing face for authority ID: ' . $authorityId);

        $success = $faceService->indexAuthorityFace($imagePath, $authorityId);

        if ($success) {
            $this->info('Face indexed successfully');
        } else {
            foreach ($faceService->getErrors() as $error) {
                $this->error($error);
            }
        }

        return $success;
    }

    /**
     * Match faces across all unmatched digital objects
     */
    protected function matchFaces($parameters)
    {
        // Get digital objects with unidentified faces using Laravel
        $objects = DB::table('digital_object_faces as dof')
            ->join('digital_object as do', 'dof.digital_object_id', '=', 'do.id')
            ->where('dof.is_identified', 0)
            ->distinct()
            ->select('dof.digital_object_id', 'do.path')
            ->get();

        $this->info(sprintf('Found %d digital objects with unidentified faces', count($objects)));

        $backend = $this->getSetting('face_detect_backend', 'local');
        $faceService = new ahgFaceDetectionService($backend, [
            'confidence_threshold' => $this->getSetting('face_confidence_threshold', 0.8),
        ]);

        $matched = 0;

        foreach ($objects as $obj) {
            // Get unmatched faces
            $faces = DB::table('digital_object_faces')
                ->where('digital_object_id', $obj->digital_object_id)
                ->where('is_identified', 0)
                ->get();

            foreach ($faces as $face) {
                if (!$face->face_image_path) {
                    continue;
                }

                // Search for matches
                $faceData = [
                    'bounding_box' => json_decode($face->bounding_box, true),
                ];

                $matches = $faceService->searchAuthorityFaces($face->face_image_path, $faceData);

                if (!empty($matches)) {
                    $bestMatch = $matches[0];

                    // Update face record
                    DB::table('digital_object_faces')
                        ->where('id', $face->id)
                        ->update([
                            'matched_actor_id' => $bestMatch['actor_id'],
                            'match_similarity' => $bestMatch['similarity'],
                            'alternative_matches' => json_encode(array_slice($matches, 1, 4)),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    $matched++;
                }
            }
        }

        $this->info("Face matching complete. Matched: {$matched}");

        return true;
    }

    /**
     * Reprocess all digital objects
     */
    protected function reprocessAll($parameters)
    {
        $clearExisting = $parameters['clear_existing'] ?? false;

        if ($clearExisting) {
            DB::table('digital_object_metadata')->truncate();
            DB::table('digital_object_faces')->truncate();
            $this->info('Cleared existing metadata');
        }

        // Set up bulk extraction
        return $this->extractBulk(['limit' => 10000]);
    }

    /**
     * Store extracted metadata in database
     */
    protected function storeMetadata($digitalObjectId, $metadata, $extractor)
    {
        $keyFields = $extractor->getKeyFields();
        $fileType = $extractor->getFileType();

        // Prepare consolidated data
        $consolidated = $metadata['consolidated'] ?? [];
        $image = $metadata['image'] ?? [];
        $gps = $metadata['gps'] ?? [];
        $pdf = $metadata['pdf'] ?? [];
        $office = $metadata['office'] ?? [];
        $video = $metadata['video'] ?? [];
        $audio = $metadata['audio'] ?? [];

        $data = [
            'digital_object_id' => $digitalObjectId,
            'file_type' => $fileType,
            'raw_metadata' => json_encode($metadata),
            'title' => $keyFields['title'],
            'creator' => $keyFields['creator'],
            'description' => $keyFields['description'],
            'keywords' => !empty($keyFields['keywords']) ? implode(', ', $keyFields['keywords']) : null,
            'copyright' => $keyFields['copyright'],
            'date_created' => $keyFields['date'],
            'image_width' => $image['width'] ?? null,
            'image_height' => $image['height'] ?? null,
            'camera_make' => $consolidated['camera']['make'] ?? null,
            'camera_model' => $consolidated['camera']['model'] ?? null,
            'gps_latitude' => $gps['latitude'] ?? null,
            'gps_longitude' => $gps['longitude'] ?? null,
            'gps_altitude' => $gps['altitude'] ?? null,
            'page_count' => $pdf['page_count'] ?? $office['pages'] ?? null,
            'word_count' => $office['words'] ?? null,
            'author' => $pdf['author'] ?? $office['creator'] ?? null,
            'application' => $pdf['creator'] ?? $office['application'] ?? null,
            'duration' => $video['duration'] ?? $audio['duration'] ?? null,
            'duration_formatted' => $video['duration_formatted'] ?? $audio['duration_formatted'] ?? null,
            'video_codec' => $video['video_codec'] ?? null,
            'audio_codec' => $video['audio_codec'] ?? $audio['audio_codec'] ?? null,
            'resolution' => $video['resolution'] ?? null,
            'frame_rate' => $video['frame_rate'] ?? null,
            'bitrate' => $video['bitrate'] ?? $audio['audio_bitrate'] ?? null,
            'sample_rate' => $audio['audio_sample_rate'] ?? null,
            'channels' => $audio['audio_channels'] ?? null,
            'artist' => $audio['artist'] ?? $video['artist'] ?? null,
            'album' => $audio['album'] ?? null,
            'track_number' => $audio['track'] ?? null,
            'genre' => $audio['genre'] ?? null,
            'year' => $audio['year'] ?? null,
            'extraction_method' => get_class($extractor),
        ];

        DB::table('digital_object_metadata')
            ->updateOrInsert(
                ['digital_object_id' => $digitalObjectId],
                array_merge($data, ['updated_at' => date('Y-m-d H:i:s')])
            );
    }

    /**
     * Populate AtoM fields from extracted metadata
     */
    protected function populateAtomFields($digitalObject, $metadata, $extractor)
    {
        $informationObjectId = $digitalObject->object_id;

        if (!$informationObjectId) {
            return;
        }

        $informationObject = $this->getInformationObject($informationObjectId);
        if (!$informationObject) {
            return;
        }

        $keyFields = $extractor->getKeyFields();
        $modified = false;

        // Title (only if empty)
        if (empty($informationObject->title) && !empty($keyFields['title'])) {
            DB::table('information_object_i18n')
                ->updateOrInsert(
                    ['id' => $informationObjectId, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                    ['title' => $keyFields['title']]
                );
            $modified = true;
        }

        // Date created
        if (!empty($keyFields['date'])) {
            // Check if date already exists
            $hasCreationDate = DB::table('event')
                ->where('information_object_id', $informationObjectId)
                ->where('type_id', self::TERM_CREATION_ID)
                ->exists();

            if (!$hasCreationDate) {
                // Parse and add date
                $dateStr = $keyFields['date'];

                // Try to normalize date format
                if (preg_match('/^(\d{4}):(\d{2}):(\d{2})/', $dateStr, $matches)) {
                    $dateStr = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                }

                // Create object entry for event
                $objectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitEvent',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('event')->insert([
                    'id' => $objectId,
                    'information_object_id' => $informationObjectId,
                    'type_id' => self::TERM_CREATION_ID,
                    'start_date' => $dateStr,
                ]);

                $modified = true;
            }
        }

        // Creator (as name access point)
        if (!empty($keyFields['creator'])) {
            $actorId = $this->findOrCreateActor($keyFields['creator']);

            // Check if relation exists
            $existingRelation = DB::table('relation')
                ->where('subject_id', $informationObjectId)
                ->where('object_id', $actorId)
                ->where('type_id', self::TERM_NAME_ACCESS_POINT_ID)
                ->exists();

            if (!$existingRelation) {
                // Create object entry for relation
                $objectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('relation')->insert([
                    'id' => $objectId,
                    'subject_id' => $informationObjectId,
                    'object_id' => $actorId,
                    'type_id' => self::TERM_NAME_ACCESS_POINT_ID,
                ]);

                $modified = true;
            }
        }

        // Keywords as subject access points
        if (!empty($keyFields['keywords'])) {
            foreach ($keyFields['keywords'] as $keyword) {
                $keyword = trim($keyword);
                if (empty($keyword)) {
                    continue;
                }

                $termId = $this->findOrCreateSubjectTerm($keyword);

                // Check if relation exists
                $exists = DB::table('object_term_relation')
                    ->where('object_id', $informationObjectId)
                    ->where('term_id', $termId)
                    ->exists();

                if (!$exists) {
                    DB::table('object_term_relation')->insert([
                        'object_id' => $informationObjectId,
                        'term_id' => $termId,
                    ]);

                    $modified = true;
                }
            }
        }

        // Physical characteristics (technical metadata)
        $techSummary = $extractor->formatSummary();
        if ($techSummary) {
            $currentI18n = DB::table('information_object_i18n')
                ->where('id', $informationObjectId)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->first();

            $current = $currentI18n ? $currentI18n->physical_characteristics : '';

            // Append if not already present
            if (strpos($current, '=== FILE INFO ===') === false) {
                DB::table('information_object_i18n')
                    ->updateOrInsert(
                        ['id' => $informationObjectId, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                        ['physical_characteristics' => trim($current . "\n\n" . $techSummary)]
                    );
                $modified = true;
            }
        }

        if ($modified) {
            // Update the object timestamp
            DB::table('object')
                ->where('id', $informationObjectId)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);

            $this->info('Updated information object fields');
        }
    }

    /**
     * Store detected faces in database
     */
    protected function storeFaces($digitalObjectId, $faces)
    {
        foreach ($faces as $index => $face) {
            $matched = !empty($face['matches']);
            $bestMatch = $matched ? $face['matches'][0] : null;

            DB::table('digital_object_faces')->insert([
                'digital_object_id' => $digitalObjectId,
                'face_index' => $index,
                'face_image_path' => $face['face_image'] ?? null,
                'bounding_box' => json_encode($face['bounding_box']),
                'confidence' => $face['confidence'],
                'matched_actor_id' => $bestMatch ? $bestMatch['actor_id'] : null,
                'match_similarity' => $bestMatch ? $bestMatch['similarity'] : null,
                'alternative_matches' => $matched && count($face['matches']) > 1
                    ? json_encode(array_slice($face['matches'], 1, 4)) : null,
                'attributes' => isset($face['attributes']) ? json_encode($face['attributes']) : null,
                'is_identified' => $matched ? 1 : 0,
                'identification_source' => $matched ? 'auto' : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Log extraction operation
     */
    protected function logExtraction($digitalObjectId, $filePath, $extractor, $status, $processingTime, $error = null)
    {
        try {
            DB::table('metadata_extraction_log')->insert([
                'digital_object_id' => $digitalObjectId,
                'file_path' => $filePath,
                'file_type' => $extractor ? $extractor->getFileType() : null,
                'file_size' => file_exists($filePath) ? filesize($filePath) : null,
                'operation' => 'extract',
                'status' => $status,
                'metadata_extracted' => $status === 'success' ? 1 : 0,
                'error_message' => $error,
                'processing_time_ms' => (int) $processingTime,
                'triggered_by' => 'job',
                'job_id' => $this->job->id ?? null,
                'user_id' => $this->job->userId ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // Don't fail the job if logging fails
            $this->error('Failed to log extraction: ' . $e->getMessage());
        }
    }

    /**
     * Get digital object by ID
     */
    protected function getDigitalObject(int $id): ?object
    {
        return DB::table('digital_object')
            ->where('id', $id)
            ->first();
    }

    /**
     * Get digital object file path
     */
    protected function getDigitalObjectFilePath(object $digitalObject): string
    {
        $uploadDir = sfConfig::get('sf_upload_dir');
        // Path in DB is stored as /uploads/r/... but actual file is at upload_dir/r/...
        $relativePath = str_replace('/uploads', '', $digitalObject->path);
        return $uploadDir . $relativePath . $digitalObject->name;
    }

    /**
     * Build file path from database path
     */
    protected function buildFilePath(string $dbPath): string
    {
        $uploadDir = sfConfig::get('sf_upload_dir');
        $relativePath = str_replace('/uploads', '', $dbPath);
        return $uploadDir . $relativePath;
    }

    /**
     * Get information object by ID
     */
    protected function getInformationObject(int $id): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $id)
            ->select('io.*', 'i18n.title', 'i18n.physical_characteristics')
            ->first();
    }

    /**
     * Find or create actor by name
     */
    protected function findOrCreateActor(string $name): int
    {
        // Search for existing actor
        $existing = DB::table('actor as a')
            ->join('actor_i18n as ai', 'a.id', '=', 'ai.id')
            ->where('ai.authorized_form_of_name', $name)
            ->select('a.id')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Create new actor
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('actor')->insert([
            'id' => $objectId,
            'parent_id' => 3, // ROOT_ACTOR_ID
        ]);

        DB::table('actor_i18n')->insert([
            'id' => $objectId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'authorized_form_of_name' => $name,
        ]);

        return $objectId;
    }

    /**
     * Find or create subject term
     */
    protected function findOrCreateSubjectTerm(string $name): int
    {
        // Search for existing term
        $existing = DB::table('term as t')
            ->join('term_i18n as ti', 't.id', '=', 'ti.id')
            ->where('t.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
            ->where('ti.name', $name)
            ->where('ti.culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->select('t.id')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Create new term
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitTerm',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('term')->insert([
            'id' => $objectId,
            'taxonomy_id' => self::TAXONOMY_SUBJECT_ID,
            'parent_id' => self::ROOT_TERM_SUBJECT_ID,
        ]);

        DB::table('term_i18n')->insert([
            'id' => $objectId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'name' => $name,
        ]);

        return $objectId;
    }

    /**
     * Get setting value
     */
    protected function getSetting($key, $default = null)
    {
        return ahgSettingsAction::getSetting($key, $default);
    }
}