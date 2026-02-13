<?php

namespace AhgIngestPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class IngestService
{
    // ─── AtoM CSV Field Definitions ─────────────────────────────────────

    /**
     * Standard columns accepted by AtoM CSV import, per standard.
     */
    public static function getTargetFields(string $standard = 'isadg'): array
    {
        $common = [
            'legacyId', 'parentId', 'qubitParentSlug', 'identifier',
            'title', 'levelOfDescription', 'extentAndMedium',
            'repository', 'archivalHistory', 'acquisition',
            'scopeAndContent', 'appraisal', 'accruals',
            'arrangement', 'accessConditions', 'reproductionConditions',
            'physicalCharacteristics', 'findingAids', 'relatedUnitsOfDescription',
            'locationOfOriginals', 'locationOfCopies', 'rules',
            'descriptionIdentifier', 'descriptionStatus', 'publicationStatus',
            'levelOfDetail', 'revisionHistory', 'sources',
            'culture', 'alternateTitle',
            'digitalObjectPath', 'digitalObjectURI', 'digitalObjectChecksum',
            'subjectAccessPoints', 'placeAccessPoints', 'nameAccessPoints',
            'genreAccessPoints', 'creators', 'creatorDates',
            'creatorDatesStart', 'creatorDatesEnd', 'creatorDateNotes',
            'creationDates', 'creationDatesStart', 'creationDatesEnd',
            'eventActors', 'eventTypes', 'eventDates',
            'eventStartDates', 'eventEndDates', 'eventPlaces',
            'physicalObjectName', 'physicalObjectLocation', 'physicalObjectType',
            'accessionNumber', 'copyrightStatus', 'copyrightExpires', 'copyrightHolder',
        ];

        $extras = [];

        if ($standard === 'rad') {
            $extras = [
                'radOtherTitleInformation', 'radTitleStatementOfResponsibility',
                'radStatementOfProjection', 'radStatementOfCoordinates',
                'radEdition', 'radStatementOfScaleCartographic',
            ];
        } elseif ($standard === 'dacs') {
            $extras = [
                'unitDates', 'unitDateActuated',
            ];
        } elseif ($standard === 'dc') {
            $extras = [
                'type', 'format', 'language', 'relation', 'coverage',
                'contributor', 'publisher', 'rights', 'date',
            ];
        } elseif ($standard === 'spectrum') {
            $extras = [
                'objectNumber', 'objectName', 'objectType',
                'materialComponent', 'technique', 'dimension',
                'inscription', 'condition', 'completeness',
            ];
        } elseif ($standard === 'cco') {
            $extras = [
                'workType', 'measurements', 'materialsTechniques',
                'stylePeriod', 'culturalContext',
            ];
        }

        return array_merge($common, $extras);
    }

    /**
     * Required fields per standard.
     */
    public static function getRequiredFields(string $standard = 'isadg'): array
    {
        $base = ['title', 'levelOfDescription'];

        if ($standard === 'isadg') {
            $base[] = 'identifier';
        } elseif ($standard === 'dc') {
            $base = ['title'];
        }

        return $base;
    }

    // ─── Session Management ─────────────────────────────────────────────

    public function createSession(int $userId, array $config): int
    {
        return DB::table('ingest_session')->insertGetId([
            'user_id' => $userId,
            'title' => $config['title'] ?? null,
            'sector' => $config['sector'] ?? 'archive',
            'standard' => $config['standard'] ?? 'isadg',
            'repository_id' => $config['repository_id'] ?? null,
            'parent_id' => $config['parent_id'] ?? null,
            'parent_placement' => $config['parent_placement'] ?? 'top_level',
            'new_parent_title' => $config['new_parent_title'] ?? null,
            'new_parent_level' => $config['new_parent_level'] ?? null,
            'output_create_records' => $config['output_create_records'] ?? 1,
            'output_generate_sip' => $config['output_generate_sip'] ?? 0,
            'output_generate_dip' => $config['output_generate_dip'] ?? 0,
            'output_sip_path' => $config['output_sip_path'] ?? null,
            'output_dip_path' => $config['output_dip_path'] ?? null,
            'derivative_thumbnails' => $config['derivative_thumbnails'] ?? 1,
            'derivative_reference' => $config['derivative_reference'] ?? 1,
            'derivative_normalize_format' => $config['derivative_normalize_format'] ?? null,
            'security_classification_id' => $config['security_classification_id'] ?? null,
            'status' => 'configure',
            'config' => json_encode($config),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateSession(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('ingest_session')->where('id', $id)->update($data);
    }

    public function getSession(int $id): ?object
    {
        return DB::table('ingest_session')->where('id', $id)->first();
    }

    public function getSessions(int $userId, ?string $status = null): array
    {
        $q = DB::table('ingest_session')->where('user_id', $userId);
        if ($status) {
            $q->where('status', $status);
        }

        return $q->orderByDesc('updated_at')->get()->toArray();
    }

    public function updateSessionStatus(int $id, string $status): void
    {
        DB::table('ingest_session')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── File Handling ──────────────────────────────────────────────────

    public function processUpload(int $sessionId, array $fileInfo): int
    {
        $ext = strtolower(pathinfo($fileInfo['original_name'], PATHINFO_EXTENSION));
        $typeMap = ['csv' => 'csv', 'zip' => 'zip', 'xml' => 'ead'];
        $fileType = $typeMap[$ext] ?? 'csv';

        $fileId = DB::table('ingest_file')->insertGetId([
            'session_id' => $sessionId,
            'file_type' => $fileType,
            'original_name' => $fileInfo['original_name'],
            'stored_path' => $fileInfo['stored_path'],
            'file_size' => $fileInfo['file_size'] ?? 0,
            'mime_type' => $fileInfo['mime_type'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Auto-detect CSV format
        if ($fileType === 'csv') {
            $detection = $this->detectCsvFormat($fileInfo['stored_path']);
            DB::table('ingest_file')->where('id', $fileId)->update([
                'row_count' => $detection['row_count'],
                'delimiter' => $detection['delimiter'],
                'encoding' => $detection['encoding'],
                'headers' => json_encode($detection['headers']),
            ]);
        } elseif ($fileType === 'zip') {
            $extractDir = dirname($fileInfo['stored_path']) . '/extracted_' . $sessionId;
            $this->extractZip($fileId, $extractDir);
        }

        return $fileId;
    }

    public function detectCsvFormat(string $filePath): array
    {
        $result = [
            'delimiter' => ',',
            'encoding' => 'UTF-8',
            'headers' => [],
            'row_count' => 0,
            'sample_rows' => [],
        ];

        if (!file_exists($filePath)) {
            return $result;
        }

        $content = file_get_contents($filePath, false, null, 0, 8192);

        // Detect encoding
        $detected = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        $result['encoding'] = $detected ?: 'UTF-8';

        // Convert if needed
        if ($result['encoding'] !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $result['encoding']);
        }

        // Detect delimiter
        $delimiters = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        $firstLine = strtok($content, "\n");
        foreach ($delimiters as $d => &$count) {
            $count = substr_count($firstLine, $d);
        }
        arsort($delimiters);
        $result['delimiter'] = array_key_first($delimiters);

        // Parse headers and count rows
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $headers = fgetcsv($handle, 0, $result['delimiter']);
            if ($headers) {
                $result['headers'] = array_map('trim', $headers);
            }

            $rowCount = 0;
            $samples = [];
            while (($row = fgetcsv($handle, 0, $result['delimiter'])) !== false) {
                $rowCount++;
                if ($rowCount <= 10) {
                    $samples[] = $row;
                }
            }
            $result['row_count'] = $rowCount;
            $result['sample_rows'] = $samples;
            fclose($handle);
        }

        return $result;
    }

    public function extractZip(int $fileId, string $extractTo): array
    {
        $file = DB::table('ingest_file')->where('id', $fileId)->first();
        if (!$file) {
            return [];
        }

        if (!is_dir($extractTo)) {
            mkdir($extractTo, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->stored_path) !== true) {
            return [];
        }

        $zip->extractTo($extractTo);
        $zip->close();

        DB::table('ingest_file')->where('id', $fileId)->update([
            'extracted_path' => $extractTo,
        ]);

        return $this->scanDirectory($extractTo);
    }

    public function scanDirectory(string $dirPath): array
    {
        $files = [];
        if (!is_dir($dirPath)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $files[] = [
                'path' => $file->getPathname(),
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'type' => $file->getExtension(),
                'mime' => mime_content_type($file->getPathname()),
            ];
        }

        return $files;
    }

    public function getFiles(int $sessionId): array
    {
        return DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->get()
            ->toArray();
    }

    // ─── Row Parsing ────────────────────────────────────────────────────

    public function parseRows(int $sessionId): int
    {
        $file = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->where('file_type', 'csv')
            ->first();

        if (!$file || !file_exists($file->stored_path)) {
            return 0;
        }

        // Clear existing rows
        DB::table('ingest_row')->where('session_id', $sessionId)->delete();

        $delimiter = $file->delimiter ?: ',';
        $headers = json_decode($file->headers, true) ?: [];
        $handle = fopen($file->stored_path, 'r');
        if (!$handle) {
            return 0;
        }

        // Skip header row
        fgetcsv($handle, 0, $delimiter);

        $rowNum = 0;
        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            $data = [];
            foreach ($headers as $i => $header) {
                $data[$header] = $cols[$i] ?? '';
            }

            DB::table('ingest_row')->insert([
                'session_id' => $sessionId,
                'row_number' => $rowNum,
                'legacy_id' => $data['legacyId'] ?? $data['legacy_id'] ?? null,
                'parent_id_ref' => $data['parentId'] ?? $data['parent_id'] ?? $data['qubitParentSlug'] ?? null,
                'level_of_description' => $data['levelOfDescription'] ?? $data['level_of_description'] ?? null,
                'title' => $data['title'] ?? null,
                'data' => json_encode($data),
                'digital_object_path' => $data['digitalObjectPath'] ?? $data['digital_object_path'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        fclose($handle);

        return $rowNum;
    }

    // ─── Mapping ────────────────────────────────────────────────────────

    public function autoMapColumns(int $sessionId, string $standard = 'isadg'): array
    {
        $file = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->where('file_type', 'csv')
            ->first();

        if (!$file) {
            return [];
        }

        $sourceHeaders = json_decode($file->headers, true) ?: [];
        $targetFields = self::getTargetFields($standard);

        // Clear existing mappings
        DB::table('ingest_mapping')->where('session_id', $sessionId)->delete();

        // Known aliases (source variant → AtoM field)
        $aliases = [
            'legacy_id' => 'legacyId',
            'legacyid' => 'legacyId',
            'parent_id' => 'parentId',
            'parentid' => 'parentId',
            'parent_slug' => 'qubitParentSlug',
            'level_of_description' => 'levelOfDescription',
            'levelofdescription' => 'levelOfDescription',
            'level' => 'levelOfDescription',
            'extent_and_medium' => 'extentAndMedium',
            'extent' => 'extentAndMedium',
            'scope_and_content' => 'scopeAndContent',
            'scope' => 'scopeAndContent',
            'description' => 'scopeAndContent',
            'archival_history' => 'archivalHistory',
            'custodial_history' => 'archivalHistory',
            'access_conditions' => 'accessConditions',
            'conditions_of_access' => 'accessConditions',
            'reproduction_conditions' => 'reproductionConditions',
            'conditions_of_reproduction' => 'reproductionConditions',
            'finding_aids' => 'findingAids',
            'publication_status' => 'publicationStatus',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'digital_object' => 'digitalObjectPath',
            'filename' => 'digitalObjectPath',
            'file_path' => 'digitalObjectPath',
            'subject_access_points' => 'subjectAccessPoints',
            'subjects' => 'subjectAccessPoints',
            'place_access_points' => 'placeAccessPoints',
            'places' => 'placeAccessPoints',
            'name_access_points' => 'nameAccessPoints',
            'names' => 'nameAccessPoints',
            'genre_access_points' => 'genreAccessPoints',
            'genres' => 'genreAccessPoints',
            'creator' => 'creators',
            'date' => 'creationDates',
            'creation_date' => 'creationDates',
            'start_date' => 'creationDatesStart',
            'end_date' => 'creationDatesEnd',
            'accession_number' => 'accessionNumber',
            'copyright_status' => 'copyrightStatus',
            'physical_location' => 'physicalObjectLocation',
            'storage_location' => 'physicalObjectLocation',
            'alternate_title' => 'alternateTitle',
            'ref_code' => 'identifier',
            'reference_code' => 'identifier',
            'ref' => 'identifier',
        ];

        $mappings = [];
        $order = 0;

        foreach ($sourceHeaders as $source) {
            $order++;
            $sourceLower = strtolower(trim($source));
            $sourceNorm = str_replace([' ', '-', '_'], '', $sourceLower);
            $target = null;
            $confidence = 'none';

            // Exact match
            if (in_array($source, $targetFields, true)) {
                $target = $source;
                $confidence = 'exact';
            }

            // Alias match
            if (!$target && isset($aliases[$sourceLower])) {
                $target = $aliases[$sourceLower];
                $confidence = 'exact';
            }

            // Normalized alias match
            if (!$target && isset($aliases[$sourceNorm])) {
                $target = $aliases[$sourceNorm];
                $confidence = 'fuzzy';
            }

            // Case-insensitive target match
            if (!$target) {
                foreach ($targetFields as $tf) {
                    if (strtolower($tf) === $sourceLower || str_replace('_', '', $sourceLower) === strtolower($tf)) {
                        $target = $tf;
                        $confidence = 'fuzzy';
                        break;
                    }
                }
            }

            $mapId = DB::table('ingest_mapping')->insertGetId([
                'session_id' => $sessionId,
                'source_column' => $source,
                'target_field' => $target,
                'is_ignored' => $target ? 0 : 1,
                'sort_order' => $order,
            ]);

            $mappings[] = [
                'id' => $mapId,
                'source_column' => $source,
                'target_field' => $target,
                'confidence' => $confidence,
                'is_ignored' => $target ? 0 : 1,
            ];
        }

        return $mappings;
    }

    public function saveMappings(int $sessionId, array $mappings): void
    {
        foreach ($mappings as $map) {
            if (isset($map['id'])) {
                DB::table('ingest_mapping')->where('id', $map['id'])->update([
                    'target_field' => $map['target_field'] ?? null,
                    'is_ignored' => $map['is_ignored'] ?? 0,
                    'default_value' => $map['default_value'] ?? null,
                    'transform' => $map['transform'] ?? null,
                ]);
            }
        }
    }

    public function getMappings(int $sessionId): array
    {
        return DB::table('ingest_mapping')
            ->where('session_id', $sessionId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Load a saved mapping profile from ahgDataMigrationPlugin's atom_data_mapping table.
     */
    public function loadMappingProfile(int $sessionId, int $mappingId): void
    {
        try {
            $profile = DB::table('atom_data_mapping')->where('id', $mappingId)->first();
        } catch (\Exception $e) {
            return; // Table may not exist if DataMigration plugin not installed
        }

        if (!$profile || !$profile->field_mappings) {
            return;
        }

        $fieldMappings = json_decode($profile->field_mappings, true);
        if (!is_array($fieldMappings)) {
            return;
        }

        // Apply profile mappings to current session
        $existing = $this->getMappings($sessionId);
        foreach ($existing as $map) {
            foreach ($fieldMappings as $fm) {
                $srcMatch = ($fm['source'] ?? '') === $map->source_column
                    || strtolower($fm['source'] ?? '') === strtolower($map->source_column);
                if ($srcMatch && !empty($fm['target'])) {
                    DB::table('ingest_mapping')->where('id', $map->id)->update([
                        'target_field' => $fm['target'],
                        'is_ignored' => 0,
                        'default_value' => $fm['default'] ?? null,
                        'transform' => $fm['transform'] ?? null,
                    ]);
                    break;
                }
            }
        }
    }

    public function getSavedMappingProfiles(): array
    {
        try {
            return DB::table('atom_data_mapping')
                ->orderBy('name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return []; // Table may not exist
        }
    }

    // ─── Enrichment ─────────────────────────────────────────────────────

    public function enrichRows(int $sessionId): void
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return;
        }

        $mappings = $this->getMappings($sessionId);
        $mappingLookup = [];
        foreach ($mappings as $m) {
            if ($m->target_field && !$m->is_ignored) {
                $mappingLookup[$m->source_column] = $m;
            }
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->orderBy('row_number')
            ->get();

        foreach ($rows as $row) {
            $data = json_decode($row->data, true) ?: [];
            $enriched = [];

            // Apply mappings: remap source columns to target fields
            foreach ($data as $sourceCol => $value) {
                if (isset($mappingLookup[$sourceCol])) {
                    $map = $mappingLookup[$sourceCol];
                    $targetField = $map->target_field;
                    $val = $value;

                    // Apply default value if empty
                    if (empty($val) && !empty($map->default_value)) {
                        $val = $map->default_value;
                    }

                    // Apply transforms
                    if (!empty($map->transform) && !empty($val)) {
                        $val = $this->applyTransform($val, $map->transform);
                    }

                    $enriched[$targetField] = $val;
                }
            }

            // Auto-generate defaults
            if (empty($enriched['culture'])) {
                $enriched['culture'] = 'en';
            }
            if (empty($enriched['publicationStatus'])) {
                $enriched['publicationStatus'] = 'Draft';
            }

            DB::table('ingest_row')->where('id', $row->id)->update([
                'enriched_data' => json_encode($enriched),
                'title' => $enriched['title'] ?? $row->title,
                'level_of_description' => $enriched['levelOfDescription'] ?? $row->level_of_description,
                'legacy_id' => $enriched['legacyId'] ?? $row->legacy_id,
                'parent_id_ref' => $enriched['parentId'] ?? $enriched['qubitParentSlug'] ?? $row->parent_id_ref,
            ]);
        }
    }

    protected function applyTransform(string $value, string $transform): string
    {
        switch ($transform) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'trim':
                return trim($value);
            case 'titlecase':
                return ucwords(strtolower($value));
            case 'date_iso':
                $ts = strtotime($value);
                return $ts ? date('Y-m-d', $ts) : $value;
            case 'strip_html':
                return strip_tags($value);
            default:
                return $value;
        }
    }

    public function extractFileMetadata(int $sessionId): void
    {
        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->whereNotNull('digital_object_path')
            ->where('digital_object_path', '!=', '')
            ->get();

        foreach ($rows as $row) {
            $filePath = $row->digital_object_path;

            // Try to resolve relative paths from extracted ZIP
            if (!file_exists($filePath)) {
                $file = DB::table('ingest_file')
                    ->where('session_id', $sessionId)
                    ->whereNotNull('extracted_path')
                    ->first();
                if ($file && $file->extracted_path) {
                    $candidate = $file->extracted_path . '/' . $filePath;
                    if (file_exists($candidate)) {
                        $filePath = $candidate;
                    }
                }
            }

            if (!file_exists($filePath)) {
                continue;
            }

            $metadata = null;
            if (class_exists('\AtomFramework\Helpers\EmbeddedMetadataParser')) {
                $metadata = \AtomFramework\Helpers\EmbeddedMetadataParser::extract($filePath);
            }

            // Generate checksum
            $checksum = hash_file('sha256', $filePath);

            DB::table('ingest_row')->where('id', $row->id)->update([
                'metadata_extracted' => $metadata ? json_encode($metadata) : null,
                'checksum_sha256' => $checksum,
                'digital_object_matched' => 1,
            ]);
        }
    }

    public function matchDigitalObjects(int $sessionId, string $strategy = 'filename'): int
    {
        $file = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->whereNotNull('extracted_path')
            ->first();

        if (!$file || !$file->extracted_path) {
            return 0;
        }

        $availableFiles = $this->scanDirectory($file->extracted_path);
        $fileIndex = [];
        foreach ($availableFiles as $f) {
            $key = strtolower($f['name']);
            $fileIndex[$key] = $f['path'];
            // Also index without extension
            $noExt = strtolower(pathinfo($f['name'], PATHINFO_FILENAME));
            $fileIndex[$noExt] = $f['path'];
        }

        $matched = 0;
        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->get();

        foreach ($rows as $row) {
            $doPath = $row->digital_object_path;
            if (empty($doPath)) {
                // Try matching by legacyId or title
                if ($strategy === 'legacyId' && $row->legacy_id) {
                    $key = strtolower($row->legacy_id);
                } elseif ($strategy === 'title' && $row->title) {
                    $key = strtolower(preg_replace('/[^a-z0-9]/', '', strtolower($row->title)));
                } else {
                    continue;
                }
            } else {
                $key = strtolower(basename($doPath));
            }

            if (isset($fileIndex[$key])) {
                DB::table('ingest_row')->where('id', $row->id)->update([
                    'digital_object_path' => $fileIndex[$key],
                    'digital_object_matched' => 1,
                ]);
                $matched++;
            } elseif (isset($fileIndex[strtolower(pathinfo($key, PATHINFO_FILENAME))])) {
                DB::table('ingest_row')->where('id', $row->id)->update([
                    'digital_object_path' => $fileIndex[strtolower(pathinfo($key, PATHINFO_FILENAME))],
                    'digital_object_matched' => 1,
                ]);
                $matched++;
            }
        }

        return $matched;
    }

    // ─── Validation ─────────────────────────────────────────────────────

    public function validateSession(int $sessionId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['total' => 0, 'valid' => 0, 'warnings' => 0, 'errors' => 0];
        }

        // Clear previous validations
        DB::table('ingest_validation')->where('session_id', $sessionId)->delete();

        $standard = $session->standard;
        $required = self::getRequiredFields($standard);

        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('is_excluded', 0)
            ->get();

        $stats = ['total' => count($rows), 'valid' => 0, 'warnings' => 0, 'errors' => 0];

        // Track legacyIds for duplicate detection
        $legacyIds = [];
        $checksums = [];

        foreach ($rows as $row) {
            $enriched = json_decode($row->enriched_data, true) ?: [];
            $rowErrors = 0;
            $rowWarnings = 0;

            // Required field checks
            foreach ($required as $field) {
                if (empty($enriched[$field])) {
                    $this->addValidation($sessionId, $row->row_number, 'error', $field,
                        "Required field '{$field}' is empty");
                    $rowErrors++;
                }
            }

            // Level of description validation
            if (!empty($enriched['levelOfDescription'])) {
                $validLevels = [
                    'Fonds', 'Subfonds', 'Collection', 'Series', 'Subseries',
                    'File', 'Item', 'Part', 'Class', 'Sub-item',
                ];
                $lvl = $enriched['levelOfDescription'];
                if (!in_array($lvl, $validLevels, true) && !in_array(ucfirst(strtolower($lvl)), $validLevels, true)) {
                    $this->addValidation($sessionId, $row->row_number, 'warning', 'levelOfDescription',
                        "Level of description '{$lvl}' may not be recognized");
                    $rowWarnings++;
                }
            }

            // Date format validation
            foreach (['creationDatesStart', 'creationDatesEnd', 'eventStartDates', 'eventEndDates'] as $dateField) {
                if (!empty($enriched[$dateField])) {
                    $val = $enriched[$dateField];
                    if (!preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $val) && !strtotime($val)) {
                        $this->addValidation($sessionId, $row->row_number, 'warning', $dateField,
                            "Date '{$val}' may not be in a recognized format (YYYY-MM-DD preferred)");
                        $rowWarnings++;
                    }
                }
            }

            // Hierarchy: parentId references must exist as legacyId within the batch
            if (!empty($row->parent_id_ref) && $session->parent_placement === 'csv_hierarchy') {
                // We'll validate parent references in a second pass below
            }

            // Duplicate legacyId detection
            if (!empty($row->legacy_id)) {
                if (isset($legacyIds[$row->legacy_id])) {
                    $this->addValidation($sessionId, $row->row_number, 'error', 'legacyId',
                        "Duplicate legacyId '{$row->legacy_id}' (also on row {$legacyIds[$row->legacy_id]})");
                    $rowErrors++;
                } else {
                    $legacyIds[$row->legacy_id] = $row->row_number;
                }
            }

            // Duplicate checksum detection
            if (!empty($row->checksum_sha256)) {
                if (isset($checksums[$row->checksum_sha256])) {
                    $this->addValidation($sessionId, $row->row_number, 'warning', 'digitalObjectPath',
                        "Duplicate file checksum (same file as row {$checksums[$row->checksum_sha256]})");
                    $rowWarnings++;
                } else {
                    $checksums[$row->checksum_sha256] = $row->row_number;
                }
            }

            // Digital object file existence
            if (!empty($row->digital_object_path) && !$row->digital_object_matched) {
                $this->addValidation($sessionId, $row->row_number, 'warning', 'digitalObjectPath',
                    "Digital object file not found: " . basename($row->digital_object_path));
                $rowWarnings++;
            }

            // Update row validity
            $isValid = ($rowErrors === 0) ? 1 : 0;
            DB::table('ingest_row')->where('id', $row->id)->update(['is_valid' => $isValid]);

            if ($rowErrors > 0) {
                $stats['errors'] += $rowErrors;
            } elseif ($rowWarnings > 0) {
                $stats['warnings'] += $rowWarnings;
            }

            if ($isValid) {
                $stats['valid']++;
            }
        }

        // Second pass: validate parent references
        if ($session->parent_placement === 'csv_hierarchy') {
            foreach ($rows as $row) {
                if (!empty($row->parent_id_ref) && !isset($legacyIds[$row->parent_id_ref])) {
                    // Check if it exists in AtoM already
                    $exists = DB::table('slug')->where('slug', $row->parent_id_ref)->exists()
                        || DB::table('information_object')
                            ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                            ->where('information_object_i18n.title', $row->parent_id_ref)
                            ->exists();

                    if (!$exists) {
                        $this->addValidation($sessionId, $row->row_number, 'error', 'parentId',
                            "Parent reference '{$row->parent_id_ref}' not found in batch or AtoM");
                        $stats['errors']++;
                    }
                }
            }
        }

        return $stats;
    }

    protected function addValidation(int $sessionId, int $rowNumber, string $severity, ?string $field, string $message): void
    {
        DB::table('ingest_validation')->insert([
            'session_id' => $sessionId,
            'row_number' => $rowNumber,
            'severity' => $severity,
            'field_name' => $field,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getValidationErrors(int $sessionId, ?string $severity = null): array
    {
        $q = DB::table('ingest_validation')->where('session_id', $sessionId);
        if ($severity) {
            $q->where('severity', $severity);
        }

        return $q->orderBy('row_number')->orderBy('severity')->get()->toArray();
    }

    public function excludeRow(int $sessionId, int $rowNumber, bool $exclude = true): void
    {
        DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->update(['is_excluded' => $exclude ? 1 : 0]);
    }

    public function fixRow(int $sessionId, int $rowNumber, string $field, $value): void
    {
        $row = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->first();

        if (!$row) {
            return;
        }

        $enriched = json_decode($row->enriched_data, true) ?: [];
        $enriched[$field] = $value;

        $update = ['enriched_data' => json_encode($enriched)];

        if ($field === 'title') {
            $update['title'] = $value;
        } elseif ($field === 'levelOfDescription') {
            $update['level_of_description'] = $value;
        } elseif ($field === 'legacyId') {
            $update['legacy_id'] = $value;
        }

        DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->update($update);
    }

    // ─── Preview ────────────────────────────────────────────────────────

    public function buildHierarchyTree(int $sessionId): array
    {
        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->orderBy('row_number')
            ->get()
            ->toArray();

        // Build lookup by legacyId
        $byLegacy = [];
        foreach ($rows as $row) {
            if (!empty($row->legacy_id)) {
                $byLegacy[$row->legacy_id] = $row;
            }
        }

        // Build tree
        $tree = [];
        $nodeMap = [];

        foreach ($rows as $row) {
            $node = [
                'row_number' => $row->row_number,
                'title' => $row->title ?: '[Untitled]',
                'level' => $row->level_of_description ?: '',
                'legacy_id' => $row->legacy_id,
                'is_valid' => $row->is_valid,
                'is_excluded' => $row->is_excluded,
                'has_do' => !empty($row->digital_object_path) && $row->digital_object_matched,
                'children' => [],
            ];

            $parentRef = $row->parent_id_ref;

            if (!empty($parentRef) && isset($byLegacy[$parentRef])) {
                // Has a parent within the batch
                $parentRow = $byLegacy[$parentRef]->row_number;
                if (isset($nodeMap[$parentRow])) {
                    $nodeMap[$parentRow]['children'][] = &$node;
                } else {
                    $tree[] = &$node;
                }
            } else {
                $tree[] = &$node;
            }

            $nodeMap[$row->row_number] = &$node;
            unset($node);
        }

        return $tree;
    }

    public function getPreviewRow(int $sessionId, int $rowNumber): ?object
    {
        return DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->first();
    }

    public function getRowCount(int $sessionId): int
    {
        return DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('is_excluded', 0)
            ->count();
    }

    // ─── Templates ──────────────────────────────────────────────────────

    public function generateCsvTemplate(string $sector, string $standard = 'isadg'): string
    {
        $fields = self::getTargetFields($standard);

        // Add sector-specific fields at the start
        $sectorFields = [];
        if ($sector === 'museum' || $sector === 'gallery') {
            $sectorFields = ['objectNumber', 'objectName', 'artist', 'medium', 'dimensions'];
        } elseif ($sector === 'library') {
            $sectorFields = ['isbn', 'author', 'publisher', 'callNumber'];
        } elseif ($sector === 'dam') {
            $sectorFields = ['assetId', 'assetType', 'resolution', 'colorSpace'];
        }

        $allFields = array_unique(array_merge($sectorFields, $fields));

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $allFields);
        // Add one empty example row
        fputcsv($handle, array_fill(0, count($allFields), ''));
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    // ─── Cleanup ────────────────────────────────────────────────────────

    public function deleteSession(int $id): void
    {
        // Delete uploaded files from disk
        $files = DB::table('ingest_file')->where('session_id', $id)->get();
        foreach ($files as $file) {
            if ($file->stored_path && file_exists($file->stored_path)) {
                @unlink($file->stored_path);
            }
            if ($file->extracted_path && is_dir($file->extracted_path)) {
                $this->removeDirectory($file->extracted_path);
            }
        }

        // Cascade delete from DB
        DB::table('ingest_validation')->where('session_id', $id)->delete();
        DB::table('ingest_mapping')->where('session_id', $id)->delete();
        DB::table('ingest_row')->where('session_id', $id)->delete();
        DB::table('ingest_file')->where('session_id', $id)->delete();
        DB::table('ingest_job')->where('session_id', $id)->delete();
        DB::table('ingest_session')->where('id', $id)->delete();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
