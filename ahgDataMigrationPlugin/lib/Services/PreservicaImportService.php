<?php

namespace ahgDataMigrationPlugin\Services;

use ahgDataMigrationPlugin\Parsers\OpexParser;
use ahgDataMigrationPlugin\Parsers\PaxParser;
use ahgDataMigrationPlugin\Mappings\PreservicaMapping;
use Illuminate\Database\Capsule\Manager as DB;
use ahgDataMigrationPlugin\Services\RightsImportService;

/**
 * Service for importing data from Preservica OPEX and XIP/PAX formats into AtoM.
 * 
 * Supports:
 * - OPEX XML files (single or batch)
 * - PAX packages (ZIP archives with XIP metadata)
 * - Digital object import with checksum verification
 */
class PreservicaImportService
{
    /** @var string Import source type: 'opex' or 'xip' */
    protected $sourceType;
    
    /** @var array Field mapping configuration */
    protected $fieldMapping;
    
    /** @var array Import options */
    protected $options;
    
    /** @var array Import statistics */
    protected $stats = [
        'total'     => 0,
        'imported'  => 0,
        'updated'   => 0,
        'skipped'   => 0,
        'errors'    => 0,
    ];
    
    /** @var array Error log */
    protected $errors = [];
    
    /** @var int|null Repository ID for imported records */
    protected $repositoryId;
    
    /** @var int|null Parent ID for top-level imports */
    protected $parentId;
    
    /** @var string Culture code */
    protected $culture = 'en';

    /**
     * Constructor.
     *
     * @param string $sourceType 'opex' or 'xip'
     * @param array  $options    Import options
     */
    public function __construct(string $sourceType = 'opex', array $options = [])
    {
        $this->sourceType = $sourceType;
        $this->options = array_merge([
            'update_existing'       => false,
            'match_field'           => 'legacyId',
            'import_digital_objects'=> true,
            'verify_checksums'      => true,
            'create_hierarchy'      => true,
            'default_level'         => 'Item',
            'default_status'        => 'Published',
            'dry_run'               => false,
            'generate_derivatives'  => true,
            'queue_derivatives'     => false,
        ], $options);
        
        // Load default mapping based on source type
        $this->fieldMapping = $sourceType === 'opex' 
            ? PreservicaMapping::getOpexToAtomMapping()
            : PreservicaMapping::getXipToAtomMapping();
    }

    /**
     * Set custom field mapping.
     */
    public function setFieldMapping(array $mapping): self
    {
        $this->fieldMapping = $mapping;
        return $this;
    }

    /**
     * Set repository for imported records.
     */
    public function setRepository(int $repositoryId): self
    {
        $this->repositoryId = $repositoryId;
        return $this;
    }

    /**
     * Set parent record for top-level imports.
     */
    public function setParent(int $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * Set culture/language code.
     */
    public function setCulture(string $culture): self
    {
        $this->culture = $culture;
        return $this;
    }

    /**
     * Import from OPEX XML file.
     *
     * @param string $filePath Path to OPEX XML file
     * @return array Import results
     */
    public function importOpexFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $parser = new OpexParser();
        $records = $parser->parse($filePath);
        
        return $this->importRecords($records, dirname($filePath));
    }

    /**
     * Import from PAX package (ZIP archive).
     *
     * @param string $packagePath Path to PAX ZIP file
     * @return array Import results
     */
    public function importPaxPackage(string $packagePath): array
    {
        if (!file_exists($packagePath)) {
            throw new \InvalidArgumentException("Package not found: {$packagePath}");
        }

        $parser = new PaxParser();
        $records = $parser->parse($packagePath);
        
        // PAX parser extracts to temp directory
        $extractPath = $parser->getExtractPath();
        
        $result = $this->importRecords($records, $extractPath);
        
        // Cleanup temp directory
        if ($extractPath && is_dir($extractPath)) {
            $this->removeDirectory($extractPath);
        }
        
        return $result;
    }

    /**
     * Import from directory containing OPEX files.
     *
     * @param string $directoryPath Path to directory
     * @return array Import results
     */
    public function importDirectory(string $directoryPath): array
    {
        if (!is_dir($directoryPath)) {
            throw new \InvalidArgumentException("Directory not found: {$directoryPath}");
        }

        $allRecords = [];
        
        // Find all OPEX files
        $files = glob($directoryPath . '/*.opex') ?: [];
        $xmlFiles = glob($directoryPath . '/*.xml') ?: [];
        $files = array_merge($files, $xmlFiles);
        
        foreach ($files as $file) {
            try {
                $parser = new OpexParser($file);
                $record = $parser->toAtomRecord();
                $allRecords[] = $record;
            } catch (\Exception $e) {
                $this->errors[] = [
                    'file'    => basename($file),
                    'message' => $e->getMessage(),
                ];
                $this->stats['errors']++;
            }
        }
        
        return $this->importRecords($allRecords, $directoryPath);
    }

    /**
     * Import parsed records into AtoM.
     *
     * @param array  $records    Parsed records
     * @param string $basePath   Base path for digital objects
     * @return array Import results
     */
    protected function importRecords(array $records, string $basePath = ''): array
    {
        $this->stats['total'] = count($records);
        
        // Build parent reference map for hierarchy
        $refMap = [];
        
        foreach ($records as $index => $record) {
            try {
                // Map fields
                $mapped = $this->mapFields($record);
                
                // Check for existing record
                $existingId = $this->findExistingRecord($mapped);
                
                if ($existingId && !$this->options['update_existing']) {
                    $this->stats['skipped']++;
                    continue;
                }
                
                if ($this->options['dry_run']) {
                    $this->stats['imported']++;
                    continue;
                }
                
                // Determine parent
                $parentId = $this->resolveParent($record, $refMap);
                
                // Create or update record
                if ($existingId) {
                    $objectId = $this->updateRecord($existingId, $mapped);
                    $this->stats['updated']++;
                } else {
                    $objectId = $this->createRecord($mapped, $parentId);
                    $this->stats['imported']++;
                }
                
                // Store ref for hierarchy building
                if (isset($record['Ref']) || isset($record['SourceID'])) {
                    $ref = $record['Ref'] ?? $record['SourceID'];
                    $refMap[$ref] = $objectId;
                }
                
                // Import digital object
                if ($this->options['import_digital_objects']) {
                    $this->importDigitalObject($objectId, $record, $basePath);
                }
                
            } catch (\Exception $e) {
                $this->errors[] = [
                    'index'   => $index,
                    'record'  => $record['Title'] ?? $record['dc:title'] ?? 'Unknown',
                    'message' => $e->getMessage(),
                ];
                $this->stats['errors']++;
            }
        }
        
        return [
            'success'   => $this->stats['errors'] === 0,
            'stats'     => $this->stats,
            'errors'    => $this->errors,
        ];
    }

    /**
     * Map source fields to AtoM fields.
     */
    protected function mapFields(array $record): array
    {
        $mapped = [];

        // Check if record is already mapped (has AtoM field names)
        if (isset($record["title"]) || isset($record["scopeAndContent"]) || isset($record["identifier"])) {
            // Record is already mapped (from toAtomRecord), pass through with defaults
            $mapped = $record;
            if (empty($mapped["levelOfDescription"])) {
                $mapped["levelOfDescription"] = $this->options["default_level"];
            }
            if (empty($mapped["culture"])) {
                $mapped["culture"] = $this->culture;
            }
            return $mapped;
        }

        
        foreach ($this->fieldMapping as $sourceField => $atomField) {
            if (isset($record[$sourceField]) && !empty($record[$sourceField])) {
                $value = $record[$sourceField];
                
                // Handle special mappings
                $value = $this->transformValue($atomField, $value);
                
                // Concatenate if field already has value
                if (isset($mapped[$atomField]) && !empty($mapped[$atomField])) {
                    $mapped[$atomField] .= "\n" . $value;
                } else {
                    $mapped[$atomField] = $value;
                }
            }
        }
        
        // Set defaults
        if (empty($mapped['levelOfDescription'])) {
            $mapped['levelOfDescription'] = $this->options['default_level'];
        }
        
        if (empty($mapped['culture'])) {
            $mapped['culture'] = $this->culture;
        }
        
        return $mapped;
    }

    /**
     * Transform values for specific fields.
     */
    protected function transformValue(string $field, $value): string
    {
        if (is_array($value)) {
            $value = implode(' | ', $value);
        }
        
        switch ($field) {
            case 'levelOfDescription':
                $levelMap = PreservicaMapping::getLevelMapping();
                return $levelMap[$value] ?? $value;
                
            case 'accessConditions':
                $securityMap = PreservicaMapping::getSecurityDescriptorMapping();
                return $securityMap[strtolower($value)] ?? $value;
                
            default:
                return (string) $value;
        }
    }

    /**
     * Find existing record by match field.
     */
    protected function findExistingRecord(array $mapped): ?int
    {
        $matchField = $this->options['match_field'];
        
        if (empty($mapped[$matchField])) {
            return null;
        }
        
        $matchValue = $mapped[$matchField];
        
        // Check by legacyId in keymap
        if ($matchField === 'legacyId') {
            $keymap = DB::table('keymap')
                ->where('source_name', 'preservica')
                ->where('source_id', $matchValue)
                ->first();
            
            if ($keymap) {
                return (int) $keymap->target_id;
            }
        }
        
        // Check by identifier
        if ($matchField === 'identifier') {
            $obj = DB::table('information_object')
                ->where('identifier', $matchValue)
                ->first();
            
            if ($obj) {
                return (int) $obj->id;
            }
        }
        
        return null;
    }

    /**
     * Resolve parent ID for hierarchical import.
     */
    protected function resolveParent(array $record, array $refMap): ?int
    {
        if (!$this->options['create_hierarchy']) {
            return $this->parentId;
        }
        
        // Check for parent reference
        $parentRef = $record['Parent'] ?? $record['ParentRef'] ?? null;
        
        if ($parentRef && isset($refMap[$parentRef])) {
            return $refMap[$parentRef];
        }
        
        return $this->parentId;
    }

    /**
     * Create new information object record.
     */
    protected function createRecord(array $mapped, ?int $parentId): int
    {
        // Get root object ID if no parent
        if (!$parentId) {
            $parentId = \QubitInformationObject::ROOT_ID;
        }
        
        // Get level of description term ID
        $levelId = $this->getLevelTermId($mapped['levelOfDescription'] ?? 'Item');
        
        // Create object
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Create information_object
        DB::table('information_object')->insert([
            'id'                        => $objectId,
            'identifier'                => $mapped['identifier'] ?? null,
            'level_of_description_id'   => $levelId,
            'parent_id'                 => $parentId,
            'repository_id'             => $this->repositoryId,
            'source_culture'            => $this->culture,
            'lft'                       => 0,
            'rgt'                       => 0,
        ]);
        
        // Create i18n record
        DB::table('information_object_i18n')->insert([
            'id'                        => $objectId,
            'culture'                   => $this->culture,
            'title'                     => $mapped['title'] ?? 'Untitled',
            'scope_and_content'         => $mapped['scopeAndContent'] ?? null,
            'extent_and_medium'         => $mapped['extentAndMedium'] ?? null,
            'archival_history'          => $mapped['archivalHistory'] ?? null,
            'access_conditions'         => $mapped['accessConditions'] ?? null,
            'reproduction_conditions'   => $mapped['reproductionConditions'] ?? null,
            'physical_characteristics'  => $mapped['physicalCharacteristics'] ?? null,
            'finding_aids'              => $mapped['findingAids'] ?? null,
            'location_of_originals'     => $mapped['locationOfOriginals'] ?? null,
            'location_of_copies'        => $mapped['locationOfCopies'] ?? null,
            'related_units_of_description' => $mapped['relatedUnitsOfDescription'] ?? null,
            'rules'                     => $mapped['rules'] ?? null,
            'revision_history'          => $mapped['revisionHistory'] ?? null,
        ]);
        
        // Store keymap for future matching
        if (!empty($mapped['legacyId'])) {
            DB::table('keymap')->insert([
                'source_name'   => 'preservica',
                'source_id'     => $mapped['legacyId'],
                'target_id'     => $objectId,
                'target_name'   => 'information_object',
            ]);
        }
        
        // Create access points
        $this->createAccessPoints($objectId, $mapped);
        
        // Create events (dates, creators)

        // Create provenance from OPEX History
        $this->createProvenance($objectId, $mapped);

        // Create rights records from OPEX SecurityDescriptor and dc:rights
        $this->createRights($objectId, $mapped);
        $this->createEvents($objectId, $mapped);
        

        // Create slug from title
        $this->createSlug($objectId, $mapped["title"] ?? "untitled");

        // Set publication status (default: Published = 160, type = 158)
        $this->setPublicationStatus($objectId, $mapped["publicationStatus"] ?? null);

        // Update nested set (lft/rgt) - append to end of tree
        $this->updateNestedSet($objectId, $parentId);
        return $objectId;
    }

    /**
     * Update existing information object record.
     */
    protected function updateRecord(int $objectId, array $mapped): int
    {
        // Update information_object
        $updateData = [];
        
        if (!empty($mapped['identifier'])) {
            $updateData['identifier'] = $mapped['identifier'];
        }
        
        if (!empty($mapped['levelOfDescription'])) {
            $updateData['level_of_description_id'] = $this->getLevelTermId($mapped['levelOfDescription']);
        }
        
        if (!empty($updateData)) {
            DB::table('information_object')
                ->where('id', $objectId)
                ->update($updateData);
        }
        
        // Update i18n
        $i18nData = [];
        $i18nFields = [
            'title', 'scopeAndContent', 'extentAndMedium', 'archivalHistory',
            'accessConditions', 'reproductionConditions', 'physicalCharacteristics',
        ];
        
        $fieldMap = [
            'scopeAndContent'       => 'scope_and_content',
            'extentAndMedium'       => 'extent_and_medium',
            'archivalHistory'       => 'archival_history',
            'accessConditions'      => 'access_conditions',
            'reproductionConditions'=> 'reproduction_conditions',
            'physicalCharacteristics'=> 'physical_characteristics',
        ];
        
        foreach ($i18nFields as $field) {
            if (!empty($mapped[$field])) {
                $dbField = $fieldMap[$field] ?? $field;
                $i18nData[$dbField] = $mapped[$field];
            }
        }
        
        if (!empty($i18nData)) {
            DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $this->culture)
                ->update($i18nData);
        }
        
        return $objectId;
    }

    /**
     * Import digital object for a record.
     */
    protected function importDigitalObject(int $objectId, array $record, string $basePath): void
    {
        $filename = $record['Filename'] ?? $record['File'] ?? $record['Bitstream'] ?? null;
        
        if (!$filename) {
            return;
        }
        
        // Resolve full path
        $filePath = $basePath . DIRECTORY_SEPARATOR . $filename;
        
        if (!file_exists($filePath)) {
            // Try in content subdirectory (PAX structure)
            $filePath = $basePath . '/content/' . $filename;
        }
        
        if (!file_exists($filePath)) {
            $this->errors[] = [
                'record'  => $record['Title'] ?? 'Unknown',
                'message' => "Digital object not found: {$filename}",
            ];
            return;
        }
        
        // Verify checksum if available
        if ($this->options['verify_checksums']) {
            $expectedChecksum = $record['Fixity'] ?? $record['Checksum'] ?? null;
            if ($expectedChecksum) {
                $actualChecksum = hash_file('sha256', $filePath);
                if (strtolower($actualChecksum) !== strtolower($expectedChecksum)) {
                    $this->errors[] = [
                        'record'  => $record['Title'] ?? 'Unknown',
                        'message' => "Checksum mismatch for: {$filename}",
                    ];
                    return;
                }
            }
        }
        
        // Create digital object using AtoM's method
        $this->createDigitalObject($objectId, $filePath, $filename);
    }

    /**
     * Create digital object record.
     */
    /**
     * Create digital object record with proper derivative generation.
     * Uses AtoM's native QubitDigitalObject for thumbnails and reference images.
     */
    protected function createDigitalObject(int $objectId, string $filePath, string $filename): void
    {
        // Option 1: Use AtoM's native QubitDigitalObject (generates derivatives)
        if ($this->options['generate_derivatives'] ?? true) {
            try {
                $digitalObject = new \QubitDigitalObject();
                $digitalObject->informationObjectId = $objectId;
                $digitalObject->usageId = \QubitTerm::MASTER_ID;
                $digitalObject->createDerivatives = true;
                
                // Create asset from file path
                $asset = new \QubitAsset($filePath);
                $digitalObject->assets[] = $asset;
                
                // Save triggers derivative creation (thumbnail, reference)
                $digitalObject->save();
                
                $this->stats['digital_objects'] = ($this->stats['digital_objects'] ?? 0) + 1;
                return;
            } catch (\Exception $e) {
                $this->errors[] = [
                    'record'  => $filename,
                    'message' => "Failed to create digital object with derivatives: " . $e->getMessage(),
                ];
                // Fall back to direct insert without derivatives
            }
        }
        
        // Option 2: Direct DB insert (no derivatives - faster for batch)
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileSize = filesize($filePath);
        $checksum = hash_file('sha256', $filePath);

        // Create object record
        $doId = DB::table('object')->insertGetId([
            'class_name' => 'QubitDigitalObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Copy file to uploads
        $uploadPath = $this->copyToUploads($filePath, $doId);

        // Create digital_object record
        DB::table('digital_object')->insert([
            'id'                    => $doId,
            'information_object_id' => $objectId,
            'usage_id'              => \QubitTerm::MASTER_ID,
            'mime_type'             => $mimeType,
            'byte_size'             => $fileSize,
            'checksum'              => $checksum,
            'checksum_type'         => 'sha256',
            'name'                  => $filename,
            'path'                  => $uploadPath,
            'sequence'              => 0,
        ]);
        
        // Queue derivative generation job if requested
        if ($this->options['queue_derivatives'] ?? false) {
            $this->queueDerivativeJob($doId);
        }
        
        $this->stats['digital_objects'] = ($this->stats['digital_objects'] ?? 0) + 1;
    }

    /**
     * Queue a Gearman job to generate derivatives later (for batch imports).
     */
    protected function queueDerivativeJob(int $digitalObjectId): void
    {
        try {
            // Use AtoM's job system
            $jobName = 'arGenerateDerivativesJob';
            
            // Create job via Gearman
            $client = new \GearmanClient();
            $client->addServer();
            
            $client->doBackground($jobName, json_encode([
                'digitalObjectId' => $digitalObjectId,
            ]));
        } catch (\Exception $e) {
            // Log but don't fail - derivatives can be generated later
            $this->errors[] = [
                'record'  => "DO-{$digitalObjectId}",
                'message' => "Failed to queue derivative job: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Copy file to AtoM uploads directory.
     */
    protected function copyToUploads(string $sourcePath, int $digitalObjectId): string
    {
        $uploadsDir = sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads');

        // Create directory structure (AtoM's standard r/XX pattern)
        $subdir = 'r/' . substr(md5($digitalObjectId), 0, 2);
        $targetDir = $uploadsDir . '/' . $subdir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = basename($sourcePath);
        $targetPath = $targetDir . '/' . $digitalObjectId . '_' . $filename;

        copy($sourcePath, $targetPath);

        return $subdir . '/' . $digitalObjectId . '_' . $filename;
    }

    /**
     * Create access points for a record.
     */
    protected function createAccessPoints(int $objectId, array $mapped): void
    {
        $accessPointTypes = [
            'subjectAccessPoints' => \QubitTaxonomy::SUBJECT_ID,
            'placeAccessPoints'   => \QubitTaxonomy::PLACE_ID,
            // 'nameAccessPoints' handled via actor relations
            'genreAccessPoints'   => \QubitTaxonomy::GENRE_ID,
        ];
        
        foreach ($accessPointTypes as $field => $taxonomyId) {
            if (empty($mapped[$field])) {
                continue;
            }
            
            $values = is_array($mapped[$field]) 
                ? $mapped[$field] 
                : explode('|', $mapped[$field]);
            
            foreach ($values as $value) {
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
                
                $termId = $this->findOrCreateTerm($value, $taxonomyId);
                
                // Create object_term_relation
                // Create object entry first
                $relationId = DB::table("object")->insertGetId([
                    "class_name" => "QubitObjectTermRelation",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]);
                DB::table("object_term_relation")->insert([
                    "id"        => $relationId,
                    "object_id" => $objectId,
                    'term_id'   => $termId,
                ]);
            }
        }
    }

    /**
     * Create events (dates, creators) for a record.
     */
    protected function createEvents(int $objectId, array $mapped): void
    {
        // Create creation event if we have creator or date
        if (!empty($mapped['creators']) || !empty($mapped['eventDates'])) {
            $eventId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            $actorId = null;
            if (!empty($mapped['creators'])) {
                $actorId = $this->findOrCreateActor($mapped['creators']);
            }
            
            DB::table('event')->insert([
                'id'                    => $eventId,
                'object_id' => $objectId,
                'type_id'               => \QubitTerm::CREATION_ID,
                'actor_id'              => $actorId,
                'source_culture'        => $this->culture,
            ]);
            
            if (!empty($mapped['eventDates'])) {
                DB::table('event_i18n')->insert([
                    'id'      => $eventId,
                    'culture' => $this->culture,
                    'date'    => $mapped['eventDates'],
                ]);
            }
        }
    }

    /**
     * Find or create term in taxonomy.
     */
    protected function findOrCreateTerm(string $name, int $taxonomyId): int
    {
        $existing = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', $name)
            ->where('term_i18n.culture', $this->culture)
            ->first();
        
        if ($existing) {
            return (int) $existing->id;
        }
        
        // Create new term
        $termId = DB::table('object')->insertGetId([
            'class_name' => 'QubitTerm',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('term')->insert([
            'id'            => $termId,
            'taxonomy_id'   => $taxonomyId,
            'source_culture'=> $this->culture,
            'lft'           => 0,
            'rgt'           => 0,
        ]);
        
        DB::table('term_i18n')->insert([
            'id'      => $termId,
            'culture' => $this->culture,
            'name'    => $name,
        ]);
        
        return $termId;
    }

    /**
     * Find or create actor.
     */
    protected function findOrCreateActor(string $name): int
    {
        $existing = DB::table('actor')
            ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.authorized_form_of_name', $name)
            ->where('actor_i18n.culture', $this->culture)
            ->first();
        
        if ($existing) {
            return (int) $existing->id;
        }
        
        // Create new actor
        $actorId = DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('actor')->insert([
            'id'            => $actorId,
            'source_culture'=> $this->culture,
            
            
        ]);
        
        DB::table('actor_i18n')->insert([
            'id'                        => $actorId,
            'culture'                   => $this->culture,
            'authorized_form_of_name'   => $name,
        ]);
        
        return $actorId;
    }

    /**
     * Get level of description term ID.
     */
    protected function getLevelTermId(string $level): int
    {
        $existing = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.name', $level)
            ->first();
        
        if ($existing) {
            return (int) $existing->id;
        }
        
        // Default to Item
        return \QubitTerm::ITEM_ID ?? 226;
    }

    /**
     * Remove directory recursively.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * Get import statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get import errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create provenance records from OPEX History events.
     * 
     * Maps Preservica audit events to provenance_record and provenance_event tables.
     */
    protected function createProvenance(int $objectId, array $mapped): void
    {
        // Check if ahgProvenancePlugin is enabled
        if (!in_array('ahgProvenancePlugin', \sfProjectConfiguration::getActive()->getPlugins())) {
            return;
        }

        // Check for provenance events from OPEX History
        $provenanceEvents = $mapped['_provenance_events'] ?? [];
        $provenanceSummary = $mapped['archivalHistory'] ?? null;

        if (empty($provenanceEvents) && empty($provenanceSummary)) {
            return;
        }

        try {
            // Create provenance record
            $recordId = DB::table('provenance_record')->insertGetId([
                'information_object_id' => $objectId,
                'acquisition_type' => $this->detectAcquisitionType($provenanceEvents),
                'certainty_level' => 'certain', // Preservica events are documented
                'research_status' => 'complete',
                'is_complete' => !empty($provenanceEvents) ? 1 : 0,
                'is_public' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Create i18n record with summary
            DB::table('provenance_record_i18n')->insert([
                'id' => $recordId,
                'culture' => $this->culture,
                'provenance_summary' => $provenanceSummary,
                'research_notes' => 'Imported from Preservica OPEX History',
            ]);

            // Create provenance events
            $sequence = 1;
            foreach ($provenanceEvents as $event) {
                // Create or find agent if specified
                $toAgentId = null;
                if (!empty($event['to_agent_name'])) {
                    $toAgentId = $this->findOrCreateAgent(
                        $event['to_agent_name'],
                        $event['to_agent_type'] ?? 'person'
                    );
                }

                $fromAgentId = null;
                if (!empty($event['from_agent_name'])) {
                    $fromAgentId = $this->findOrCreateAgent(
                        $event['from_agent_name'],
                        $event['from_agent_type'] ?? 'person'
                    );
                }

                DB::table('provenance_event')->insert([
                    'provenance_record_id' => $recordId,
                    'from_agent_id' => $fromAgentId,
                    'to_agent_id' => $toAgentId,
                    'event_type' => $event['event_type'] ?? 'other',
                    'event_date' => $event['event_date'] ?? null,
                    'event_date_text' => $event['event_date_text'] ?? null,
                    'date_certainty' => $event['date_certainty'] ?? 'exact',
                    'event_location' => $event['event_location'] ?? null,
                    'evidence_type' => $event['evidence_type'] ?? 'documentary',
                    'certainty' => $event['certainty'] ?? 'certain',
                    'sequence_number' => $sequence++,
                    'notes' => $event['notes'] ?? null,
                    'is_public' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            error_log("Created provenance record with " . count($provenanceEvents) . " events for object $objectId");

        } catch (\Exception $e) {
            error_log("Warning: Could not create provenance: " . $e->getMessage());
        }
    }

    /**
     * Detect acquisition type from provenance events.
     */
    protected function detectAcquisitionType(array $events): string
    {
        foreach ($events as $event) {
            $type = $event['event_type'] ?? '';
            switch ($type) {
                case 'donation':
                case 'gift':
                    return 'donation';
                case 'purchase':
                case 'sale':
                case 'auction':
                    return 'purchase';
                case 'bequest':
                case 'inheritance':
                    return 'bequest';
                case 'transfer':
                    return 'transfer';
                case 'loan_out':
                case 'loan_return':
                case 'deposit':
                    return 'loan';
                case 'accessioning':
                    return 'transfer';
            }
        }
        return 'unknown';
    }

    /**
     * Find or create a provenance agent.
     */
    protected function findOrCreateAgent(string $name, string $type = 'person'): int
    {
        // Check if agent exists
        $existing = DB::table('provenance_agent')
            ->where('name', $name)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Create new agent
        return DB::table('provenance_agent')->insertGetId([
            'name' => $name,
            'agent_type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }


    /**
     * Create rights records from OPEX metadata.
     * 
     * Uses RightsImportService to create proper PREMIS rights records
     * from SecurityDescriptor, dc:rights, dcterms:license, etc.
     */
    protected function createRights(int $objectId, array $mapped): void
    {
        // Check if ahgRightsPlugin is enabled
        if (!in_array('ahgRightsPlugin', \sfProjectConfiguration::getActive()->getPlugins())) {
            return;
        }

        // Check for rights data
        $rightsData = $mapped['_rights'] ?? [];
        
        // Also check direct fields
        $dcRights = $mapped['reproductionConditions'] ?? $rightsData['dcRights'] ?? null;
        $dcLicense = $rightsData['dcLicense'] ?? null;
        $securityDescriptor = $rightsData['securityDescriptor'] ?? null;
        $rightsHolder = $rightsData['dcRightsHolder'] ?? null;

        // Skip if no rights info
        if (empty($dcRights) && empty($dcLicense) && empty($securityDescriptor)) {
            return;
        }

        try {
            $rightsService = new RightsImportService();

            // Build rights data array for import
            $importData = [];

            // Set basis and status from parsed values
            if (!empty($rightsData['parsedBasis'])) {
                $importData['basis_id'] = $rightsData['parsedBasis'];
            }
            if (!empty($rightsData['parsedStatus'])) {
                $importData['copyright_status_id'] = $rightsData['parsedStatus'];
            }

            // Set notes
            if ($dcRights) {
                // Determine if it's copyright or license
                if (($importData['basis_id'] ?? null) === RightsImportService::BASIS_LICENSE) {
                    $importData['license_terms'] = $dcRights;
                } else {
                    $importData['copyright_note'] = $dcRights;
                }
            }

            if ($dcLicense) {
                $importData['license_terms'] = $dcLicense;
                $importData['basis_id'] = RightsImportService::BASIS_LICENSE;
            }

            // Set access level from security descriptor
            if (!empty($rightsData['accessLevel'])) {
                $importData['access_level'] = $rightsData['accessLevel'];
            }

            // Handle rights holder
            if ($rightsHolder) {
                $holderId = $rightsService->getOrCreateRightsHolder($rightsHolder);
                $importData['rights_holder_id'] = $holderId;
            }

            // Set default dates
            $importData['start_date'] = date('Y-m-d');

            // Create the rights record
            $rightsId = $rightsService->createRightsRecord($objectId, $importData);

            if ($rightsId) {
                // Log success if debugging
                error_log("Created rights record {$rightsId} for object {$objectId}");
            }

        } catch (\Exception $e) {
            error_log("Failed to create rights for object {$objectId}: " . $e->getMessage());
        }
    }


    /**
     * Create slug for information object.
     */
    protected function createSlug(int $objectId, string $title): void
    {
        // Generate slug from title
        $slug = $this->generateSlug($title);
        
        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
            'serial_number' => 0,
        ]);
    }

    /**
     * Generate URL-safe slug from title.
     */
    protected function generateSlug(string $title): string
    {
        // Convert to lowercase
        $slug = strtolower($title);
        
        // Replace accented characters
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        
        // Replace non-alphanumeric with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        // Limit length
        if (strlen($slug) > 200) {
            $slug = substr($slug, 0, 200);
            $slug = rtrim($slug, '-');
        }
        
        return $slug ?: 'untitled';
    }

    /**
     * Set publication status for information object.
     * 
     * @param int $objectId
     * @param string|null $status 'draft' or 'published' (default: use system default or published)
     */
    protected function setPublicationStatus(int $objectId, ?string $status = null): void
    {
        // Publication status type term ID
        $typeId = 158;
        
        // Determine status ID
        if ($status === 'draft') {
            $statusId = 159; // Draft
        } elseif ($status === 'published') {
            $statusId = 160; // Published
        } else {
            // Get default from system settings or use Published
            $statusId = $this->getDefaultPublicationStatus();
        }
        
        DB::table('status')->insert([
            'object_id' => $objectId,
            'type_id' => $typeId,
            'status_id' => $statusId,
            'serial_number' => 0,
        ]);
    }

    /**
     * Get default publication status from system settings.
     */
    protected function getDefaultPublicationStatus(): int
    {
        // Try to get from settings
        $setting = DB::table('setting')
            ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
            ->where('setting.name', 'defaultPubStatus')
            ->first();
        
        if ($setting && !empty($setting->value)) {
            return (int) $setting->value;
        }
        
        // Default to Published (160) if not set
        return 160;
    }

    /**
     * Update nested set values (lft/rgt) for information object.
     * Appends new record to the end of the parent's children.
     */
    protected function updateNestedSet(int $objectId, int $parentId): void
    {
        // Get parent's rgt value
        $parent = DB::table('information_object')
            ->where('id', $parentId)
            ->first();
        
        if (!$parent) {
            return;
        }
        
        $parentRgt = $parent->rgt;
        
        // Make room for new node (shift all nodes with lft/rgt >= parent.rgt by 2)
        DB::table('information_object')
            ->where('rgt', '>=', $parentRgt)
            ->increment('rgt', 2);
        
        DB::table('information_object')
            ->where('lft', '>', $parentRgt)
            ->increment('lft', 2);
        
        // Set new node's lft/rgt
        DB::table('information_object')
            ->where('id', $objectId)
            ->update([
                'lft' => $parentRgt,
                'rgt' => $parentRgt + 1,
            ]);
    }
}
