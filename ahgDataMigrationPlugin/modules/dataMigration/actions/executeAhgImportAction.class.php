<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Execute AHG Extended Import - imports records with AHG plugin integration.
 *
 * This action imports records and creates associated:
 * - Provenance events (ahgProvenancePlugin)
 * - Extended rights (ahgExtendedRightsPlugin)
 * - Security classifications (ahgSecurityClearancePlugin)
 *
 * Supports hierarchical import using two-pass method:
 * - Pass 1: Create all records with temporary parent
 * - Pass 2: Link parent-child relationships using legacyId/parentId
 * - Pass 3: Rebuild nested set (lft/rgt)
 */
class dataMigrationExecuteAhgImportAction extends AhgController
{
    protected $importService;
    protected $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'provenance_created' => 0,
        'rights_created' => 0,
        'security_set' => 0,
        'hierarchy_linked' => 0,
    ];

    /** @var array Map of legacyId => newObjectId for hierarchy linking */
    protected $refMap = [];

    /** @var array Records that need parent linking (have parentId) */
    protected $pendingLinks = [];
    /** @var array Pending digital objects to create after import */
    protected $pendingDigitalObjects = [];

    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Get session data
        $this->filepath = $this->getUser()->getAttribute('migration_file');
        $this->filename = $this->getUser()->getAttribute('migration_filename');
        $this->detection = $this->getUser()->getAttribute('migration_detection');
        $this->mapping = $this->getUser()->getAttribute('migration_mapping');

        if (!$this->filepath || !file_exists($this->filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Check if this is confirmation or execution
        if ($request->isMethod('post') && $request->getParameter('confirm') === 'yes') {
            $this->executeImport($request);
        }

        // Show confirmation page
        $this->rowCount = count($this->detection['rows'] ?? []);
        $this->hasAhgFields = $this->detectAhgFields();
    }

    /**
     * Detect which AHG fields are mapped
     */
    protected function detectAhgFields(): array
    {
        $ahgFields = [];
        $mapping = $this->mapping ?? [];

        foreach ($mapping as $field) {
            $atomField = $field['atom_field'] ?? '';
            if (strpos($atomField, 'ahg') === 0 || in_array($atomField, ['Filename', 'digitalObjectChecksum'])) {
                $ahgFields[] = $atomField;
            }
        }

        return array_unique($ahgFields);
    }

    /**
     * Execute the import with AHG plugin integration - TWO-PASS HIERARCHY
     */
    protected function executeImport($request)
    {
        // Load required services
        $pluginPath = $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgDataMigrationPlugin';
        if (file_exists($pluginPath . '/lib/Services/PreservicaImportService.php')) {
            require_once $pluginPath . '/lib/Services/PreservicaImportService.php';
        }
        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Get import options
        $repositoryId = $request->getParameter('repository_id');
        $defaultParentId = $request->getParameter('parent_id') ?: QubitInformationObject::ROOT_ID;
        $culture = $request->getParameter('culture', 'en');
        $updateExisting = $request->getParameter('update_existing') === '1';

        // Transform data using mapping
        $rows = $this->detection['rows'] ?? [];
        $headers = $this->detection['headers'] ?? [];
        $records = $this->transformToAhgRecords($rows, $headers, $this->mapping);

        // ================================================================
        // STEP 1: Build hierarchy map from legacyId/parentId
        // ================================================================
        $recordsByLegacyId = [];
        $childrenOf = []; // parentLegacyId => [childLegacyIds]
        
        foreach ($records as $index => $record) {
            // Get legacyId - try multiple field names
            $legacyId = $record['legacyId'] ?? $record['LegacyId'] ?? $record['identifier'] ?? 'record_' . $index;
            
            // Store record by its legacyId
            $record['_index'] = $index;
            $record['_legacyId'] = $legacyId;
            $recordsByLegacyId[$legacyId] = $record;
            
            // Get parentId
            $parentId = $record['parentId'] ?? $record['ParentId'] ?? $record['parent_id'] ?? null;
            $record['_parentLegacyId'] = $parentId;
            $recordsByLegacyId[$legacyId] = $record;
            
            // Build children map
            if ($parentId) {
                if (!isset($childrenOf[$parentId])) {
                    $childrenOf[$parentId] = [];
                }
                $childrenOf[$parentId][] = $legacyId;
            }
        }

        // ================================================================
        // STEP 2: Sort records - parents before children (topological sort)
        // ================================================================
        $sortedRecords = [];
        $processed = [];
        
        // Helper function to add record and its descendants
        $addRecordWithDescendants = function($legacyId) use (&$addRecordWithDescendants, &$sortedRecords, &$processed, &$recordsByLegacyId, &$childrenOf) {
            if (isset($processed[$legacyId])) {
                return;
            }
            $processed[$legacyId] = true;
            
            if (isset($recordsByLegacyId[$legacyId])) {
                $sortedRecords[] = $recordsByLegacyId[$legacyId];
            }
            
            // Add children
            if (isset($childrenOf[$legacyId])) {
                foreach ($childrenOf[$legacyId] as $childLegacyId) {
                    $addRecordWithDescendants($childLegacyId);
                }
            }
        };
        
        // Start with root records (no parent or parent not in this import)
        foreach ($recordsByLegacyId as $legacyId => $record) {
            $parentId = $record['_parentLegacyId'] ?? null;
            // Is root if no parent, or parent not in this import set
            if (!$parentId || !isset($recordsByLegacyId[$parentId])) {
                $addRecordWithDescendants($legacyId);
            }
        }
        
        // Add any remaining records (circular references or orphans)
        foreach ($recordsByLegacyId as $legacyId => $record) {
            if (!isset($processed[$legacyId])) {
                $sortedRecords[] = $record;
            }
        }

        // ================================================================
        // STEP 3: Create records in order - parent's ID is known when creating child
        // ================================================================
        foreach ($sortedRecords as $record) {
            try {
                $legacyId = $record['_legacyId'];
                $parentLegacyId = $record['_parentLegacyId'] ?? null;
                
                // Determine actual parent_id
                $actualParentId = $defaultParentId;
                if ($parentLegacyId && isset($this->refMap[$parentLegacyId])) {
                    $actualParentId = $this->refMap[$parentLegacyId];
                }
                
                // Create the record with correct parent
                $objectId = $this->importAhgRecord($record, $repositoryId, $actualParentId, $culture, $updateExisting);
                
                // Store in reference map for children to find
                if ($objectId) {
                    $this->refMap[$legacyId] = $objectId;
                    $this->stats['imported']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Row " . (($record['_index'] ?? 0) + 2) . ": " . $e->getMessage();
                $this->stats['skipped']++;
            }
        }

        // ================================================================
        // STEP 4: Rebuild nested set for proper lft/rgt values
        // ================================================================
        $this->rebuildFullNestedSet();

        // Store stats and redirect to results
        // Process pending digital objects after all records are created and nested set is rebuilt
        $this->processPendingDigitalObjects();
        
        $this->getUser()->setAttribute('ahg_import_stats', $this->stats);
        $this->redirect(['module' => 'dataMigration', 'action' => 'ahgImportResults']);
    }
     
    protected function linkParentChildRelationships($defaultParentId)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        foreach ($this->pendingLinks as $link) {
            $objectId = $link['objectId'];
            $parentLegacyId = $link['parentLegacyId'];

            // Look up parent's actual ID from reference map
            $parentObjectId = $this->refMap[$parentLegacyId] ?? null;

            if ($parentObjectId) {
                // Update the record's parent_id
                $DB::table('information_object')
                    ->where('id', $objectId)
                    ->update(['parent_id' => $parentObjectId]);

                $this->stats['hierarchy_linked']++;
            } else {
                // Parent not found in this import - check if it exists in database by identifier
                $existingParent = $DB::table('information_object')
                    ->where('identifier', $parentLegacyId)
                    ->first();

                if ($existingParent) {
                    $DB::table('information_object')
                        ->where('id', $objectId)
                        ->update(['parent_id' => $existingParent->id]);

                    $this->stats['hierarchy_linked']++;
                } else {
                    // Parent not found - leave under default parent
                    $this->stats['errors'][] = "Parent '$parentLegacyId' not found for object ID $objectId";
                }
            }
        }
    }

    /**
     * Rebuild the entire nested set tree (lft/rgt values)
     */
    protected function rebuildFullNestedSet()
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Start from root and rebuild recursively
        $this->rebuildNestedSetRecursive(QubitInformationObject::ROOT_ID, 1);
    }

    /**
     * Recursively rebuild nested set values
     */
    protected function rebuildNestedSetRecursive($parentId, $left): int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $right = $left + 1;

        // Get all children of this parent
        $children = $DB::table('information_object')
            ->where('parent_id', $parentId)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            $right = $this->rebuildNestedSetRecursive($childId, $right);
        }

        // Update this node's lft and rgt
        if ($parentId != QubitInformationObject::ROOT_ID) {
            $DB::table('information_object')
                ->where('id', $parentId)
                ->update([
                    'lft' => $left,
                    'rgt' => $right,
                ]);
        } else {
            // Update root node
            $DB::table('information_object')
                ->where('id', QubitInformationObject::ROOT_ID)
                ->update([
                    'lft' => 1,
                    'rgt' => $right,
                ]);
        }

        return $right + 1;
    }

    /**
     * Transform source data to AHG record format
     */
    protected function transformToAhgRecords(array $rows, array $headers, array $mapping): array
    {
        $records = [];
        foreach ($rows as $row) {
            $record = [
                '_ahg_fields' => [],
            ];

            // ================================================================
            // CRITICAL: Extract legacyId and parentId for hierarchy
            // ================================================================
            foreach ($headers as $index => $header) {
                $headerLower = strtolower($header);
                $value = isset($row[$index]) ? trim($row[$index]) : '';

                // Capture hierarchy fields regardless of mapping
                if (in_array($header, ['LegacyId', 'legacyId', 'legacy_id', 'ID', 'RecordId', 'record_id']) || in_array($headerLower, ['legacyid', 'legacy_id', 'recordid', 'record_id'])) {
                    $record['legacyId'] = $value;
                }
                if (in_array($header, ['ParentId', 'parentId', 'parent_id', 'Parent', 'ParentRef']) || in_array($headerLower, ['parentid', 'parent_id', 'parent', 'parentref'])) {
                    $record['parentId'] = $value;
                }
            }

            // Pass through digital object fields
            foreach ($headers as $index => $header) {
                if ((strpos($header, '_digitalObject') === 0 || $header === 'digitalObjectPath' || $header === 'Filename') && isset($row[$index]) && trim($row[$index]) !== '') {
                    $record[$header] = trim($row[$index]);
                }
            }

            // Pass through ahg* source fields
            foreach ($headers as $index => $header) {
                if (strpos($header, 'ahg') === 0 && isset($row[$index]) && trim($row[$index]) !== '') {
                    $record['_ahg_fields'][$header] = trim($row[$index]);
                }
            }

            // Process mapping
            foreach ($mapping as $fieldConfig) {
                if (empty($fieldConfig['include'])) {
                    continue;
                }
                $sourceField = $fieldConfig['source_field'] ?? '';
                $atomField = $fieldConfig['atom_field'] ?? '';
                $constantValue = $fieldConfig['constant_value'] ?? '';

                if (empty($atomField)) {
                    continue;
                }

                // Get value
                $value = '';
                if (!empty($constantValue)) {
                    $value = $constantValue;
                } elseif (!empty($sourceField)) {
                    $sourceIndex = array_search($sourceField, $headers);
                    if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                        $value = trim($row[$sourceIndex]);
                    }
                }

                if ($value === '') {
                    continue;
                }

                // Capture legacyId/parentId from mapping as well
                if (in_array(strtolower($atomField), ['legacyid', 'legacy_id'])) {
                    $record['legacyId'] = $value;
                }
                if (in_array(strtolower($atomField), ['parentid', 'parent_id'])) {
                    $record['parentId'] = $value;
                }

                // Separate AHG fields from standard fields
                if (strpos($atomField, 'ahg') === 0) {
                    $record['_ahg_fields'][$atomField] = $value;
                } else {
                    $record[$atomField] = $value;
                }

                // Also process ahg_field mapping (AHG Extended column)
                $ahgField = $fieldConfig['ahg_field'] ?? '';
                if (!empty($ahgField) && $value !== '') {
                    $record['_ahg_fields'][$ahgField] = $value;
                }
            }

            // Use identifier as legacyId if not set
            if (empty($record['legacyId']) && !empty($record['identifier'])) {
                $record['legacyId'] = $record['identifier'];
            }

            if (!empty($record['title']) || !empty($record['identifier'])) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Import a single AHG record - returns objectId
     */
    protected function importAhgRecord(array $record, $repositoryId, $parentId, $culture, $updateExisting): ?int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check for existing record
        $existingId = null;
        $legacyId = $record['legacyId'] ?? null;

        if ($updateExisting && !empty($legacyId)) {
            $existing = $DB::table('keymap')
                ->where('source_name', 'migration')
                ->where('source_id', $legacyId)
                ->first();
            if ($existing) {
                $existingId = $existing->target_id;
            }
        }

        if ($existingId) {
            $this->updateRecord($existingId, $record, $culture);
            $this->stats['updated']++;
            $objectId = $existingId;
        } else {
            $objectId = $this->createRecord($record, $repositoryId, $parentId, $culture);
            $this->stats['created']++;
        }

        // Process AHG extended fields
        $ahgFields = $record['_ahg_fields'] ?? [];
        if (!empty($ahgFields)) {
            $this->processAhgFields($objectId, $ahgFields, $culture);
        }

        // Process digital object if path provided
        $this->processDigitalObject($objectId, $record);

        // Store in keymap for future updates
        if (!empty($legacyId) && !$existingId) {
            $DB::table('keymap')->insert([
                'source_name' => 'migration',
                'source_id' => $legacyId,
                'target_id' => $objectId,
                'target_name' => 'information_object',
            ]);
        }

        return $objectId;
    }

    /**
     * Create new information object record
     * Note: parent_id is set to default here, will be updated in Pass 2
     */
    protected function createRecord(array $record, $repositoryId, $parentId, $culture): int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Create object
        $objectId = $DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Generate slug
        $slug = $this->generateSlug($record['title'] ?? $record['identifier'] ?? 'record-' . $objectId);

        // Resolve repository: if CSV has a text name, look it up; else use form dropdown
        $resolvedRepoId = $repositoryId;
        $repoName = $record['repository'] ?? null;
        if (!empty($repoName) && !is_numeric($repoName)) {
            $resolvedRepoId = $this->resolveRepositoryByName($repoName, $culture) ?: $repositoryId;
        } elseif (!empty($repoName) && is_numeric($repoName)) {
            $resolvedRepoId = (int) $repoName;
        }

        // Create information object - lft/rgt will be set in Pass 3
        $DB::table('information_object')->insert([
            'id' => $objectId,
            'identifier' => $record['identifier'] ?? $record['legacyId'] ?? null,
            'level_of_description_id' => $this->getLevelOfDescriptionId($record['levelOfDescription'] ?? 'Item'),
            'repository_id' => $resolvedRepoId ?: null,
            'parent_id' => $parentId ?: QubitInformationObject::ROOT_ID,
            'lft' => 0,
            'rgt' => 0,
            'source_culture' => $culture,
            'display_standard_id' => $this->getDisplayStandardId($this->getUser()->getAttribute('migration_target_type', 'archives')),
        ]);

        // Create slug
        $DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);

        // Create i18n
        $DB::table('information_object_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'title' => $record['title'] ?? null,
            'extent_and_medium' => $record['extentAndMedium'] ?? null,
            'archival_history' => $record['archivalHistory'] ?? null,
            'acquisition' => $record['acquisition'] ?? null,
            'scope_and_content' => $record['scopeAndContent'] ?? null,
            'arrangement' => $record['arrangement'] ?? null,
            'access_conditions' => $record['accessConditions'] ?? null,
            'reproduction_conditions' => $record['reproductionConditions'] ?? null,
            'physical_characteristics' => $record['physicalCharacteristics'] ?? null,
            'finding_aids' => $record['findingAids'] ?? null,
            'location_of_originals' => $record['locationOfOriginals'] ?? null,
            'location_of_copies' => $record['locationOfCopies'] ?? null,
            'related_units_of_description' => $record['relatedUnitsOfDescription'] ?? null,
        ]);

        // Set publication status
        $this->setPublicationStatus($objectId);

        // Create date/event from dateRange field
        $this->createDateEvent($objectId, $record, $culture);

        // Set GLAM/DAM type in display_object_config
        $sector = $this->getUser()->getAttribute('migration_target_type', 'archives');
        $glamType = ($sector === 'archives') ? 'archive' : $sector;
        $DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => $glamType, 'updated_at' => date('Y-m-d H:i:s')]
        );

        // Save sector-specific metadata
        $this->saveSectorMetadata($objectId, $record, $sector);

        return $objectId;
    }

    /**
     * Update existing record
     */
    protected function updateRecord($objectId, array $record, $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Update information_object
        $updateData = [];
        if (!empty($record['identifier'])) {
            $updateData['identifier'] = $record['identifier'];
        }
        if (!empty($record['levelOfDescription'])) {
            $updateData['level_of_description_id'] = $this->getLevelOfDescriptionId($record['levelOfDescription']);
        }

        if (!empty($updateData)) {
            $DB::table('information_object')
                ->where('id', $objectId)
                ->update($updateData);
        }

        // Update i18n
        $i18nData = array_filter([
            'title' => $record['title'] ?? null,
            'extent_and_medium' => $record['extentAndMedium'] ?? null,
            'archival_history' => $record['archivalHistory'] ?? null,
            'acquisition' => $record['acquisition'] ?? null,
            'scope_and_content' => $record['scopeAndContent'] ?? null,
            'arrangement' => $record['arrangement'] ?? null,
            'access_conditions' => $record['accessConditions'] ?? null,
            'reproduction_conditions' => $record['reproductionConditions'] ?? null,
            'physical_characteristics' => $record['physicalCharacteristics'] ?? null,
            'finding_aids' => $record['findingAids'] ?? null,
        ], function ($v) {
            return $v !== null;
        });

        if (!empty($i18nData)) {
            $DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->update($i18nData);
        }
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug($title): string
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'record';
        }

        // Truncate to 245 chars max (slug column is VARCHAR(255), leave room for -N suffix)
        if (strlen($slug) > 245) {
            $slug = substr($slug, 0, 245);
            $slug = rtrim($slug, '-');
        }

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while ($DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get level of description term ID
     */
    protected function getLevelOfDescriptionId($level): ?int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $levelMap = [
            'fonds' => 'Fonds',
            'subfonds' => 'Subfonds',
            'collection' => 'Collection',
            'series' => 'Series',
            'subseries' => 'Subseries',
            'file' => 'File',
            'item' => 'Item',
            'piece' => 'Piece',
        ];

        $normalizedLevel = $levelMap[strtolower($level)] ?? $level;

        $term = $DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.name', $normalizedLevel)
            ->first();

        return $term ? $term->id : null;
    }

    /**
     * Get display standard ID for sector
     */
    protected function getDisplayStandardId($sector): ?int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $codeMap = [
            'archives' => 'isad',
            'museum' => 'museum',
            'library' => 'library',
            'gallery' => 'gallery',
            'dam' => 'dam',
        ];

        $code = $codeMap[strtolower($sector)] ?? 'isad';

        $term = $DB::table('term')
            ->where('taxonomy_id', 70) // Information object templates
            ->where('code', $code)
            ->first();

        return $term ? $term->id : null;
    }

    /**
     * Set publication status
     */
    protected function setPublicationStatus($objectId)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Get default status from settings
        $defaultStatus = QubitTerm::PUBLICATION_STATUS_DRAFT_ID;

        $DB::table('status')->insert([
            'object_id' => $objectId,
            'type_id' => QubitTerm::STATUS_TYPE_PUBLICATION_ID,
            'status_id' => $defaultStatus,
        ]);
    }

    /**
     * Process AHG extended fields (provenance, rights, security, condition)
     */
    protected function processAhgFields($objectId, array $ahgFields, $culture)
    {
        // Process Provenance fields
        $this->processProvenanceFields($objectId, $ahgFields, $culture);

        // Process Rights fields
        $this->processRightsFields($objectId, $ahgFields);

        // Process Security fields
        $this->processSecurityFields($objectId, $ahgFields);

        // Process Condition fields
        $this->processConditionFields($objectId, $ahgFields, $culture);
    }

    /**
     * Process provenance fields - creates provenance_event records
     */
    protected function processProvenanceFields($objectId, array $ahgFields, $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if ahgProvenancePlugin table exists
        if (!$this->tableExists('provenance_record')) {
            return;
        }

        // Get or create provenance record
        $provenanceRecord = $DB::table('provenance_record')
            ->where('information_object_id', $objectId)
            ->first();

        if (!$provenanceRecord) {
            $provenanceRecordId = $DB::table('provenance_record')->insertGetId([
                'information_object_id' => $objectId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $provenanceRecordId = $provenanceRecord->id;
        }

        // Check for pipe-delimited multiple events
        $eventTypes = $this->parseMultiValue($ahgFields['ahgProvenanceEventType'] ?? '');
        $eventDates = $this->parseMultiValue($ahgFields['ahgProvenanceEventDate'] ?? '');
        $eventAgents = $this->parseMultiValue($ahgFields['ahgProvenanceEventAgent'] ?? '');
        $eventDescriptions = $this->parseMultiValue($ahgFields['ahgProvenanceEventDescription'] ?? '');
        $eventPlaces = $this->parseMultiValue($ahgFields['ahgProvenanceEventPlace'] ?? '');

        $eventCount = max(count($eventTypes), count($eventDates), count($eventAgents), count($eventDescriptions));

        if ($eventCount > 0) {
            // Get current max sort_order
            $maxSort = $DB::table('provenance_event')
                ->where('provenance_record_id', $provenanceRecordId)
                ->max('sort_order') ?? 0;

            for ($i = 0; $i < $eventCount; $i++) {
                $eventType = $eventTypes[$i] ?? 'good';
                $eventDate = $eventDates[$i] ?? null;
                $eventAgent = $eventAgents[$i] ?? null;
                $eventDescription = $eventDescriptions[$i] ?? null;
                $eventPlace = $eventPlaces[$i] ?? null;

                // Get or create agent
                $agentId = null;
                if ($eventAgent) {
                    $agentId = $this->getOrCreateProvenanceAgent($eventAgent);
                }

                $maxSort++;

                $DB::table('provenance_event')->insert([
                    'provenance_record_id' => $provenanceRecordId,
                    'event_type' => $this->mapEventType($eventType),
                    'event_date' => $this->parseDate($eventDate),
                    'event_date_text' => $eventDate,
                    'description' => $eventDescription,
                    'place' => $eventPlace,
                    'agent_id' => $agentId,
                    'sort_order' => $maxSort,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $this->stats['provenance_created']++;
            }
        }
    }

    /**
     * Parse pipe-delimited multi-value field
     */
    protected function parseMultiValue($value): array
    {
        if (empty($value)) {
            return [];
        }
        return array_map('trim', explode('|', $value));
    }

    /**
     * Map event type to valid enum value
     */
    protected function mapEventType($type): string
    {
        $typeMap = [
            'creation' => 'creation',
            'created' => 'creation',
            'acquisition' => 'acquisition',
            'acquired' => 'acquisition',
            'transfer' => 'transfer',
            'transferred' => 'transfer',
            'donation' => 'donation',
            'donated' => 'donation',
            'purchase' => 'purchase',
            'purchased' => 'purchase',
            'inheritance' => 'inheritance',
            'inherited' => 'inheritance',
            'deposit' => 'deposit',
            'deposited' => 'deposit',
            'loan' => 'loan',
            'loaned' => 'loan',
            'return' => 'return',
            'returned' => 'return',
            'conservation' => 'conservation',
            'digitisation' => 'digitization',
            'digitization' => 'digitization',
            'appraisal' => 'appraisal',
            'description' => 'description',
            'exhibition' => 'exhibition',
            'discovery' => 'other',
            'receipt' => 'acquisition',
            'migration' => 'other',
        ];

        return $typeMap[strtolower($type)] ?? 'other';
    }

    /**
     * Get or create provenance agent
     */
    protected function getOrCreateProvenanceAgent($name): int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $existing = $DB::table('provenance_agent')->where('name', $name)->first();
        if ($existing) {
            return $existing->id;
        }

        return $DB::table('provenance_agent')->insertGetId([
            'name' => $name,
            'agent_type' => 'person',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Parse date string to Y-m-d format
     */
    protected function parseDate($dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        // Try various formats
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y', 'd-m-Y', 'j F Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        // If just a year
        if (preg_match('/^\d{4}$/', $dateStr)) {
            return $dateStr . '-01-01';
        }

        return null;
    }

    /**
     * Process rights fields
     */
    protected function processRightsFields($objectId, array $ahgFields)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if extended_rights table exists
        if (!$this->tableExists('extended_rights')) {
            return;
        }

        $rightsBasis = $ahgFields['ahgRightsBasis'] ?? null;
        $copyrightStatus = $ahgFields['ahgCopyrightStatus'] ?? null;
        $rightsHolder = $ahgFields['ahgRightsHolder'] ?? null;
        $rightsNote = $ahgFields['ahgRightsNote'] ?? null;
        $licenseTerms = $ahgFields['ahgLicenseTerms'] ?? null;

        if ($rightsBasis || $copyrightStatus || $rightsHolder) {
            $DB::table('extended_rights')->updateOrInsert(
                ['information_object_id' => $objectId],
                [
                    'rights_basis' => $rightsBasis,
                    'copyright_status' => $copyrightStatus,
                    'rights_holder' => $rightsHolder,
                    'rights_note' => $rightsNote,
                    'license_terms' => $licenseTerms,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            $this->stats['rights_created']++;
        }
    }

    /**
     * Process security classification fields
     */
    /**
     * Process security classification fields
     */
    protected function processSecurityFields($objectId, array $ahgFields)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if object_security_classification table exists
        if (!$this->tableExists('object_security_classification')) {
            return;
        }

        $classification = $ahgFields['ahgSecurityClassification'] ?? null;
        $declassifyDate = $ahgFields['ahgSecurityDeclassifyDate'] ?? null;
        $reason = $ahgFields['ahgSecurityReason'] ?? null;

        if ($classification) {
            // Look up classification_id from security_classification table
            $classificationId = $this->getSecurityClassificationId($classification);

            if ($classificationId) {
                $DB::table('object_security_classification')->updateOrInsert(
                    ['object_id' => $objectId],
                    [
                        'classification_id' => $classificationId,
                        'declassify_date' => $this->parseDate($declassifyDate),
                        'reason' => $reason,
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'active' => 1,
                    ]
                );
                $this->stats['security_set']++;
            }
        }
    }

    /**
     * Get security classification ID from code/name
     */
    protected function getSecurityClassificationId($level): ?int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $levelMap = [
            'open' => 'PUBLIC',
            'public' => 'PUBLIC',
            'unclassified' => 'PUBLIC',
            'internal' => 'INTERNAL',
            'restricted' => 'RESTRICTED',
            'confidential' => 'CONFIDENTIAL',
            'secret' => 'SECRET',
            'top secret' => 'TOP_SECRET',
            'top_secret' => 'TOP_SECRET',
        ];

        $code = $levelMap[strtolower($level)] ?? strtoupper($level);

        $record = $DB::table('security_classification')
            ->where('code', $code)
            ->first();

        return $record ? $record->id : null;
    }


    /**
     * Process condition assessment fields
     */
    protected function processConditionFields($objectId, array $ahgFields, $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if condition_report table exists
        if (!$this->tableExists('condition_report')) {
            return;
        }

        // Parse pipe-delimited multiple condition reports
        $ratings = $this->parseMultiValue($ahgFields['ahgConditionOverallRating'] ?? '');
        $summaries = $this->parseMultiValue($ahgFields['ahgConditionSummary'] ?? '');
        $dates = $this->parseMultiValue($ahgFields['ahgConditionAssessmentDate'] ?? '');
        $assessors = $this->parseMultiValue($ahgFields['ahgConditionAssessor'] ?? '');
        $recommendations = $this->parseMultiValue($ahgFields['ahgConditionRecommendations'] ?? '');
        $priorities = $this->parseMultiValue($ahgFields['ahgConditionPriority'] ?? '');
        $contexts = $this->parseMultiValue($ahgFields['ahgConditionContext'] ?? '');

        $reportCount = max(count($ratings), count($summaries), count($dates), 1);

        if (!empty($ratings) || !empty($summaries)) {
            for ($i = 0; $i < $reportCount; $i++) {
                $rating = $ratings[$i] ?? null;
                $summary = $summaries[$i] ?? null;
                $date = $dates[$i] ?? date('Y-m-d');
                $assessor = $assessors[$i] ?? null;
                $recommendation = $recommendations[$i] ?? null;
                $priority = $priorities[$i] ?? null;
                $context = $contexts[$i] ?? 'routine';

                if ($rating || $summary) {
                    $DB::table('condition_report')->insert([
                        'information_object_id' => $objectId,
                        'overall_rating' => $this->mapConditionRating($rating),
                        'summary' => $summary,
                        'assessment_date' => $this->parseDate($date),
                        'assessor_user_id' => $assessor,
                        'recommendations' => $recommendation,
                        'priority' => $this->mapConditionPriority($priority),
                        'context' => $this->mapConditionContext($context),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    /**
     * Map condition rating to valid enum
     */
    protected function mapConditionRating($rating): string
    {
        $ratingMap = [
            'excellent' => 'excellent',
            'good' => 'good',
            'fair' => 'fair',
            'poor' => 'poor',
            'critical' => 'unacceptable',
            'unacceptable' => 'unacceptable',
        ];

        return $ratingMap[strtolower($rating ?? '')] ?? 'good';
    }

    /**
     * Map condition priority to valid enum
     */
    protected function mapConditionPriority($priority): string
    {
        $priorityMap = [
            'urgent' => 'urgent',
            'high' => 'high',
            'medium' => 'normal',
            'low' => 'low',
            'normal' => 'normal',
        ];

        return $priorityMap[strtolower($priority ?? '')] ?? 'normal';
    }

    /**
     * Map condition context to valid enum
     */
    protected function mapConditionContext($context): string
    {
        $contextMap = [
            'acquisition' => 'acquisition',
            'loan_in' => 'loan_in',
            'loan_out' => 'loan_out',
            'exhibition' => 'exhibition',
            'storage' => 'storage',
            'conservation' => 'conservation',
            'routine' => 'routine',
            'general' => 'routine',
        ];

        return $contextMap[strtolower($context ?? '')] ?? 'routine';
    }

    /**
     * Process digital object
     */
    protected function processDigitalObject($objectId, array $record)
    {
        $path = $record["digitalObjectPath"] ?? $record["Filename"] ?? $record["_digitalObjectPath"] ?? null;
        
        if (empty($path)) {
            return;
        }
        
        // Check if file exists
        $fullPath = $path;
        if (!file_exists($fullPath)) {
            $fullPath = $this->config("sf_upload_dir") . "/" . ltrim($path, "/");
        }
        
        if (!file_exists($fullPath)) {
            $this->stats["errors"][] = "Digital object not found: $path";
            return;
        }
        
        // Store for later processing (after all records created and Propel can see them)
        $this->pendingDigitalObjects[] = [
            "objectId" => $objectId,
            "path" => $fullPath,
        ];
    }
    
    /**
     * Process all pending digital objects after import is complete
     */
    protected function processPendingDigitalObjects()
    {
        if (empty($this->pendingDigitalObjects)) {
            return;
        }
        
        error_log("Processing " . count($this->pendingDigitalObjects) . " pending digital objects");
        $DB = \Illuminate\Database\Capsule\Manager::class;
        
        foreach ($this->pendingDigitalObjects as $pending) {
            $objectId = $pending["objectId"];
            $fullPath = $pending["path"];
            
            try {
                // Get file info
                $filename = basename($fullPath);
                $filesize = filesize($fullPath);
                $mimeType = mime_content_type($fullPath);
                $checksum = sha1_file($fullPath);
                
                // Determine media type
                $mediaTypeId = $this->getMediaTypeId($mimeType);
                
                // Get repository slug for path
                $repoSlug = "null";
                $io = $DB::table("information_object")->where("id", $objectId)->first();
                if ($io && $io->repository_id) {
                    $repo = $DB::table("slug")->where("object_id", $io->repository_id)->first();
                    if ($repo) {
                        $repoSlug = $repo->slug;
                    }
                }
                
                // Create upload directory structure
                $checksumPath = implode("/", str_split(substr($checksum, 0, 4)));
                $relativePath = "/uploads/r/" . $repoSlug . "/" . $checksumPath . "/";
                $absolutePath = $this->config("sf_web_dir") . $relativePath;
                
                if (!is_dir($absolutePath)) {
                    mkdir($absolutePath, 0755, true);
                }
                
                // Copy file to destination
                $destFile = $absolutePath . $filename;
                if (!copy($fullPath, $destFile)) {
                    throw new \Exception("Failed to copy file to $destFile");
                }
                
                // Create object record first
                $doObjectId = $DB::table("object")->insertGetId([
                    "class_name" => "QubitDigitalObject",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]);
                
                // Create digital_object record
                $DB::table("digital_object")->insert([
                    "id" => $doObjectId,
                    "object_id" => $objectId,
                    "usage_id" => QubitTerm::MASTER_ID,
                    "mime_type" => $mimeType,
                    "media_type_id" => $mediaTypeId,
                    "name" => $filename,
                    "path" => $relativePath,
                    "byte_size" => $filesize,
                    "checksum" => $checksum,
                    "checksum_type" => "sha1",
                ]);
                
                error_log("SUCCESS: Digital object created for IO $objectId (DO ID: $doObjectId)");
                
            } catch (\Exception $e) {
                $this->stats["errors"][] = "Digital object error for $objectId: " . $e->getMessage();
                error_log("Digital object error for $objectId: " . $e->getMessage());
            }
        }
    }
    
    protected function getMediaTypeId($mimeType)
    {
        if (strpos($mimeType, "image/") === 0) {
            return QubitTerm::IMAGE_ID;
        } elseif (strpos($mimeType, "audio/") === 0) {
            return QubitTerm::AUDIO_ID;
        } elseif (strpos($mimeType, "video/") === 0) {
            return QubitTerm::VIDEO_ID;
        } elseif ($mimeType === "application/pdf") {
            return QubitTerm::TEXT_ID;
        }
        return QubitTerm::OTHER_ID;
    }

    /**
     * Save sector-specific metadata
     */
    protected function saveSectorMetadata($objectId, array $record, $sector)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        switch ($sector) {
            case 'museum':
                if ($this->tableExists('museum_metadata')) {
                    $DB::table('museum_metadata')->updateOrInsert(
                        ['information_object_id' => $objectId],
                        [
                            'object_type' => $record['objectType'] ?? null,
                            'object_name' => $record['objectName'] ?? null,
                            'materials' => $record['materials'] ?? $record['material'] ?? null,
                            'techniques' => $record['techniques'] ?? $record['technique'] ?? null,
                            'dimensions_display' => $record['dimensions'] ?? $record['extentAndMedium'] ?? null,
                            'inscription' => $record['inscription'] ?? null,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
                break;

            case 'library':
                if ($this->tableExists('library_metadata')) {
                    $DB::table('library_metadata')->updateOrInsert(
                        ['information_object_id' => $objectId],
                        [
                            'isbn' => $record['isbn'] ?? null,
                            'issn' => $record['issn'] ?? null,
                            'call_number' => $record['callNumber'] ?? null,
                            'edition' => $record['edition'] ?? null,
                            'publisher' => $record['publisher'] ?? null,
                            'publication_place' => $record['publicationPlace'] ?? null,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
                break;

            case 'gallery':
                if ($this->tableExists('gallery_metadata')) {
                    $DB::table('gallery_metadata')->updateOrInsert(
                        ['information_object_id' => $objectId],
                        [
                            'medium' => $record['medium'] ?? null,
                            'support' => $record['support'] ?? null,
                            'dimensions_display' => $record['dimensions'] ?? null,
                            'edition_number' => $record['editionNumber'] ?? null,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
                break;

            case 'dam':
                if ($this->tableExists('dam_iptc_metadata')) {
                    $iptcData = array_filter([
                        'asset_type' => $record['assetType'] ?? $record['asset_type'] ?? null,
                        'genre' => $record['genre'] ?? null,
                        'creator' => $record['creator'] ?? $record['iptcCreator'] ?? null,
                        'headline' => $record['headline'] ?? null,
                        'caption' => $record['caption'] ?? $record['description'] ?? null,
                        'keywords' => $record['keywords'] ?? null,
                        'credit_line' => $record['creditLine'] ?? $record['credit_line'] ?? null,
                        'source' => $record['source'] ?? null,
                        'copyright_notice' => $record['copyrightNotice'] ?? $record['copyright_notice'] ?? null,
                        'rights_usage_terms' => $record['rightsUsageTerms'] ?? $record['rights_usage_terms'] ?? null,
                        'city' => $record['city'] ?? null,
                        'state_province' => $record['stateProvince'] ?? $record['state_province'] ?? null,
                        'country' => $record['country'] ?? null,
                        'country_code' => $record['countryCode'] ?? $record['country_code'] ?? null,
                        'color_space' => $record['colorSpace'] ?? $record['color_space'] ?? null,
                        'production_company' => $record['productionCompany'] ?? $record['production_company'] ?? null,
                        'audio_language' => $record['audioLanguage'] ?? $record['audio_language'] ?? null,
                    ], function ($v) { return $v !== null; });

                    if (!empty($iptcData)) {
                        $iptcData['updated_at'] = date('Y-m-d H:i:s');
                        $existing = $DB::table('dam_iptc_metadata')->where('object_id', $objectId)->first();
                        if ($existing) {
                            $DB::table('dam_iptc_metadata')->where('object_id', $objectId)->update($iptcData);
                        } else {
                            $iptcData['object_id'] = $objectId;
                            $iptcData['created_at'] = date('Y-m-d H:i:s');
                            $DB::table('dam_iptc_metadata')->insert($iptcData);
                        }
                    }
                }
                break;
        }
    }

    /**
     * Resolve repository ID from text name (case-insensitive partial match)
     */
    protected function resolveRepositoryByName($name, $culture = 'en'): ?int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Cache lookups to avoid repeated queries for same repository name
        static $cache = [];
        $cacheKey = strtolower(trim($name));

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        // Exact match first
        $repo = $DB::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', $culture)
            ->whereRaw('LOWER(actor_i18n.authorized_form_of_name) = ?', [$cacheKey])
            ->select('repository.id')
            ->first();

        if (!$repo) {
            // Partial match (LIKE)
            $repo = $DB::table('repository')
                ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
                ->where('actor_i18n.culture', $culture)
                ->whereRaw('LOWER(actor_i18n.authorized_form_of_name) LIKE ?', ['%' . $cacheKey . '%'])
                ->select('repository.id')
                ->first();
        }

        $id = $repo ? (int) $repo->id : null;
        $cache[$cacheKey] = $id;

        return $id;
    }

    /**
     * Create date/event from dateRange, eventDates, or date field in CSV
     */
    protected function createDateEvent($objectId, array $record, $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Try multiple field names for dates
        $dateStr = $record['dateRange'] ?? $record['eventDates'] ?? $record['date'] ?? $record['dates'] ?? null;

        if (empty($dateStr)) {
            return;
        }

        // Create actor placeholder for event (required by AtoM schema)
        $actorId = $DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $DB::table('actor')->insert([
            'id' => $actorId,
            'entity_type_id' => null,
            'parent_id' => QubitActor::ROOT_ID,
            'source_culture' => $culture,
            'lft' => 0,
            'rgt' => 0,
        ]);

        // Create event (type 111 = Creation)
        $eventObjectId = $DB::table('object')->insertGetId([
            'class_name' => 'QubitEvent',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Parse start/end dates from the string
        $startDate = $this->parseDate($dateStr);
        $endDate = null;

        // Check for date range patterns like "1986-1990" or "1986 - 1990"
        if (preg_match('/^(\d{4})\s*[-–]\s*(\d{4})$/', trim($dateStr), $m)) {
            $startDate = $m[1] . '-01-01';
            $endDate = $m[2] . '-12-31';
        }

        $DB::table('event')->insert([
            'id' => $eventObjectId,
            'type_id' => 111, // Creation
            'information_object_id' => $objectId,
            'actor_id' => $actorId,
            'source_culture' => $culture,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $DB::table('event_i18n')->insert([
            'id' => $eventObjectId,
            'culture' => $culture,
            'date' => trim($dateStr),
        ]);
    }

    /**
     * Check if a table exists
     */
    protected function tableExists($tableName): bool
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        try {
            $DB::table($tableName)->limit(1)->first();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
