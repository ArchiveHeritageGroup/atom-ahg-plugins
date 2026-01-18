<?php

/**
 * Execute AHG Extended Import - imports records with AHG plugin integration.
 * 
 * This action imports records and creates associated:
 * - Provenance events (ahgProvenancePlugin)
 * - Extended rights (ahgExtendedRightsPlugin)
 * - Security classifications (ahgSecurityClearancePlugin)
 */
class dataMigrationExecuteAhgImportAction extends sfAction
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
    ];

    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
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
     * Execute the import with AHG plugin integration
     */
    protected function executeImport($request)
    {
        // Load required services
        $pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgDataMigrationPlugin';
        require_once $pluginPath . '/lib/Services/PreservicaImportService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Get import options
        $repositoryId = $request->getParameter('repository_id');
        $parentId = $request->getParameter('parent_id');
        $culture = $request->getParameter('culture', 'en');
        $updateExisting = $request->getParameter('update_existing') === '1';

        // Transform data using mapping
        $rows = $this->detection['rows'] ?? [];
        $headers = $this->detection['headers'] ?? [];
        $records = $this->transformToAhgRecords($rows, $headers, $this->mapping);

        // Process each record
        foreach ($records as $index => $record) {
            try {
                $this->importAhgRecord($record, $repositoryId, $parentId, $culture, $updateExisting);
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->stats['skipped']++;
            }
        }

        // Store stats and redirect to results
        $this->getUser()->setAttribute('ahg_import_stats', $this->stats);
        $this->redirect(['module' => 'dataMigration', 'action' => 'ahgImportResults']);
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
            
            // Pass through all ahg* source fields directly

            // Also pass through digital object fields
            foreach ($headers as $index => $header) {
                if ((strpos($header, '_digitalObject') === 0 || $header === 'digitalObjectPath' || $header === 'Filename') && isset($row[$index]) && trim($row[$index]) !== '') {
                    $record[$header] = trim($row[$index]);
                }
            }

            // Also pass through digital object fields
            foreach ($headers as $index => $header) {
                if ((strpos($header, '_digitalObject') === 0 || $header === 'digitalObjectPath' || $header === 'Filename') && isset($row[$index]) && trim($row[$index]) !== '') {
                    $record[$header] = trim($row[$index]);
                }
            }
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
            if (!empty($record['title']) || !empty($record['identifier'])) {
                $records[] = $record;
            }
        }
        return $records;
    }

    protected function importAhgRecord(array $record, $repositoryId, $parentId, $culture, $updateExisting)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check for existing record
        $existingId = null;
        if ($updateExisting && !empty($record['legacyId'])) {
            $existing = $DB::table('keymap')
                ->where('source_name', 'migration')
                ->where('source_id', $record['legacyId'])
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
        $digitalObjectPath = $record["_digitalObjectPath"] ?? $record["digitalObjectPath"] ?? null;
        if (!empty($digitalObjectPath) && file_exists($digitalObjectPath)) {
            $this->createDigitalObject($objectId, $digitalObjectPath);
        }

        return $objectId;
    }

    /**
     * Create a new information object
     */

    /**
     * Get display_standard_id for a sector by looking up taxonomy 70
     */
    protected function getDisplayStandardId(string $sector): ?int
    {
        // Map target_type to display standard code
        $sectorToCode = [
            'archives' => 'isad',
            'museum'   => 'museum',
            'library'  => 'library',
            'gallery'  => 'gallery',
            'dam'      => 'dam',
        ];
        
        $code = $sectorToCode[$sector] ?? 'isad';
        
        return \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode($code) ?? 353;
    }

    protected function createRecord(array $record, $repositoryId, $parentId, $culture): int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Get parent for nested set
        $parent = null;
        if ($parentId) {
            $parent = $DB::table('information_object')->where('id', $parentId)->first();
        }

        // Create object
        $objectId = $DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Generate slug
        $slug = $this->generateSlug($record['title'] ?? $record['identifier'] ?? 'record-' . $objectId);

        // Create information object
        $DB::table('information_object')->insert([
            'id' => $objectId,
            'identifier' => $record['identifier'] ?? null,
            'level_of_description_id' => $this->getLevelOfDescriptionId($record['levelOfDescription'] ?? 'Item'),
            'repository_id' => $repositoryId ?: null,
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

        // Set GLAM/DAM type in display_object_config
        $sector = $this->getUser()->getAttribute('migration_target_type', 'archives');
        $glamType = ($sector === 'archives') ? 'archive' : $sector;
        $DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => $glamType, 'updated_at' => date('Y-m-d H:i:s')]
        );


        // Save sector-specific metadata
        $this->saveSectorMetadata($objectId, $record, $sector);
        // Fix nested set
        $this->rebuildNestedSet($objectId, $parentId ?: QubitInformationObject::ROOT_ID);

        // Create keymap for legacy ID
        if (!empty($record['legacyId'])) {
            $DB::table('keymap')->insert([
                'source_name' => 'migration',
                'source_id' => $record['legacyId'],
                'target_id' => $objectId,
                'target_name' => 'information_object',
            ]);
        }

        return $objectId;
    }

    /**
     * Update existing record
     */
    protected function updateRecord(int $objectId, array $record, string $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Update i18n fields
        $DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->update([
                'title' => $record['title'] ?? null,
                'extent_and_medium' => $record['extentAndMedium'] ?? null,
                'scope_and_content' => $record['scopeAndContent'] ?? null,
            ]);

        // Update object timestamp
        $DB::table('object')
            ->where('id', $objectId)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Process AHG extended fields (provenance, rights, security)
     */
    protected function processAhgFields(int $objectId, array $ahgFields, string $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Security Classification
        if (!empty($ahgFields['ahgSecurityClassification'])) {
            $this->setSecurityClassification($objectId, $ahgFields['ahgSecurityClassification']);
            $this->stats['security_set']++;
        }

        // Provenance
        if (!empty($ahgFields['ahgProvenanceEventDates']) || !empty($ahgFields['ahgProvenanceHistory'])) {
            $this->createProvenanceRecord($objectId, $ahgFields, $culture);
            $this->stats['provenance_created']++;
        }

        // Rights
        if (!empty($ahgFields['ahgRightsStatement']) || !empty($ahgFields['ahgRightsBasis'])) {
            $this->createRightsRecord($objectId, $ahgFields, $culture);
        }

        // Condition
        if (!empty($ahgFields['ahgConditionOverallRating']) || !empty($ahgFields['ahgConditionSummary'])) {
            $this->createConditionRecord($objectId, $ahgFields, $culture);
            $this->stats['condition_created'] = ($this->stats['condition_created'] ?? 0) + 1;
        }
    }

    /**
     * Set security classification using ahgSecurityClearancePlugin
     */
    protected function setSecurityClassification(int $objectId, string $classification)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Map classification to level
        $levelMap = [
            'public' => 1,
            'internal' => 2,
            'confidential' => 3,
            'secret' => 4,
            'top secret' => 5,
        ];

        $level = $levelMap[strtolower($classification)] ?? 1;

        // Check if table exists
        if (!$DB::schema()->hasTable('security_clearance')) {
            return;
        }

        // Upsert security clearance
        $existing = $DB::table('security_clearance')
            ->where('object_id', $objectId)
            ->first();

        if ($existing) {
            $DB::table('security_clearance')
                ->where('object_id', $objectId)
                ->update([
                    'clearance_level' => $level,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            $DB::table('security_clearance')->insert([
                'object_id' => $objectId,
                'clearance_level' => $level,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Create provenance record using ahgProvenancePlugin
     */
    protected function createProvenanceRecord(int $objectId, array $ahgFields, string $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if table exists
        if (!$DB::schema()->hasTable('provenance_record')) {
            return;
        }

        // Create provenance record
        $recordId = $DB::table('provenance_record')->insertGetId([
            'information_object_id' => $objectId,
            'acquisition_type' => 'transfer',
            'is_complete' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create i18n
        $DB::table('provenance_record_i18n')->insert([
            'id' => $recordId,
            'culture' => $culture,
            'provenance_summary' => $ahgFields['ahgProvenanceHistory'] ?? null,
        ]);

        // Parse pipe-delimited event fields
        $dates = !empty($ahgFields['ahgProvenanceEventDates']) ? explode('|', $ahgFields['ahgProvenanceEventDates']) : [];
        $types = !empty($ahgFields['ahgProvenanceEventTypes']) ? explode('|', $ahgFields['ahgProvenanceEventTypes']) : [];
        $descriptions = !empty($ahgFields['ahgProvenanceEventDescriptions']) ? explode('|', $ahgFields['ahgProvenanceEventDescriptions']) : [];
        $agents = !empty($ahgFields['ahgProvenanceEventAgents']) ? explode('|', $ahgFields['ahgProvenanceEventAgents']) : [];

        $count = max(count($dates), count($types), count($descriptions), count($agents));

        // Create events
        for ($i = 0; $i < $count; $i++) {
            $date = trim($dates[$i] ?? '');
            $type = trim($types[$i] ?? '');
            $desc = trim($descriptions[$i] ?? '');
            $agent = trim($agents[$i] ?? '');

            // Skip if no meaningful data
            if (empty($date) && empty($type)) continue;

            // Map event type to valid enum
            $eventType = $this->mapProvenanceEventType($type);

            // Find or create agent
            $agentId = null;
            if (!empty($agent)) {
                $agentId = $this->findOrCreateProvenanceAgent($agent);
            }

            // Insert event
            $eventId = $DB::table('provenance_event')->insertGetId([
                'provenance_record_id' => $recordId,
                'to_agent_id' => $agentId,
                'event_type' => $eventType,
                'event_date' => $date ?: null,
                'sequence_number' => $i + 1,
                'sort_order' => $i + 1,
                'is_public' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Create event i18n for description
            if (!empty($desc)) {
                $DB::table('provenance_event_i18n')->insert([
                    'id' => $eventId,
                    'culture' => $culture,
                    'event_description' => $desc,
                ]);
            }
        }
    }

    protected function mapProvenanceEventType(string $type): string
    {
        $typeMap = [
            'creation' => 'creation',
            'acquisition' => 'accessioning',
            'digitization' => 'other',
            'digitisation' => 'other',
            'migration' => 'other',
            'classification' => 'other',
            'declassification' => 'other',
            'transfer' => 'transfer',
            'donation' => 'donation',
            'purchase' => 'purchase',
            'bequest' => 'bequest',
            'gift' => 'gift',
            'sale' => 'sale',
            'auction' => 'auction',
            'inheritance' => 'inheritance',
            'conservation' => 'conservation',
            'restoration' => 'restoration',
            'appraisal' => 'appraisal',
            'discovery' => 'discovery',
        ];
        return $typeMap[strtolower(trim($type))] ?? 'other';
    }

    protected function findOrCreateProvenanceAgent(string $name): int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if agent exists
        $existing = $DB::table('provenance_agent')->where('name', $name)->first();
        if ($existing) {
            return $existing->id;
        }

        // Create new agent
        return $DB::table('provenance_agent')->insertGetId([
            'name' => $name,
            'agent_type' => 'organization',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function createRightsRecord(int $objectId, array $ahgFields, string $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if table exists
        if (!$DB::schema()->hasTable('ahg_rights_statement')) {
            return;
        }

        // Map rights basis
        $basisMap = [
            'copyright' => 170,
            'license' => 171,
            'statute' => 172,
            'other' => 173,
        ];

        $basis = $basisMap[strtolower($ahgFields['ahgRightsBasis'] ?? '')] ?? 170;

        // Create rights statement
        $DB::table('ahg_rights_statement')->insert([
            'information_object_id' => $objectId,
            'rights_basis_id' => $basis,
            'rights_statement' => $ahgFields['ahgRightsStatement'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Create condition report using ahgConditionPlugin
     * Handles pipe-delimited values for multiple condition reports
     */
    protected function createConditionRecord(int $objectId, array $ahgFields, string $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Check if table exists
        if (!$DB::schema()->hasTable('condition_report')) {
            return;
        }

        // Parse pipe-delimited fields for multiple condition reports
        $ratings = !empty($ahgFields['ahgConditionOverallRating']) ? explode('|', $ahgFields['ahgConditionOverallRating']) : [];
        $summaries = !empty($ahgFields['ahgConditionSummary']) ? explode('|', $ahgFields['ahgConditionSummary']) : [];
        $recommendations = !empty($ahgFields['ahgConditionRecommendations']) ? explode('|', $ahgFields['ahgConditionRecommendations']) : [];
        $priorities = !empty($ahgFields['ahgConditionPriority']) ? explode('|', $ahgFields['ahgConditionPriority']) : [];
        $contexts = !empty($ahgFields['ahgConditionContext']) ? explode('|', $ahgFields['ahgConditionContext']) : [];
        $assessmentDates = !empty($ahgFields['ahgConditionAssessmentDate']) ? explode('|', $ahgFields['ahgConditionAssessmentDate']) : [];
        $nextCheckDates = !empty($ahgFields['ahgConditionNextCheckDate']) ? explode('|', $ahgFields['ahgConditionNextCheckDate']) : [];
        $envNotes = !empty($ahgFields['ahgConditionEnvironmentalNotes']) ? explode('|', $ahgFields['ahgConditionEnvironmentalNotes']) : [];
        $handlingNotes = !empty($ahgFields['ahgConditionHandlingNotes']) ? explode('|', $ahgFields['ahgConditionHandlingNotes']) : [];
        $displayNotes = !empty($ahgFields['ahgConditionDisplayNotes']) ? explode('|', $ahgFields['ahgConditionDisplayNotes']) : [];
        $storageNotes = !empty($ahgFields['ahgConditionStorageNotes']) ? explode('|', $ahgFields['ahgConditionStorageNotes']) : [];

        // Determine count from longest array
        $count = max(
            count($ratings), count($summaries), count($recommendations),
            count($priorities), count($contexts), count($assessmentDates),
            count($nextCheckDates), count($envNotes), count($handlingNotes),
            count($displayNotes), count($storageNotes), 1
        );

        // Create condition reports
        for ($i = 0; $i < $count; $i++) {
            $rating = $this->mapConditionRating(trim($ratings[$i] ?? 'good'));
            $priority = $this->mapConditionPriority(trim($priorities[$i] ?? 'normal'));
            $context = $this->mapConditionContext(trim($contexts[$i] ?? 'routine'));
            $assessDate = trim($assessmentDates[$i] ?? '') ?: date('Y-m-d');
            $nextDate = trim($nextCheckDates[$i] ?? '') ?: null;

            $DB::table('condition_report')->insert([
                'information_object_id' => $objectId,
                'assessment_date' => $assessDate,
                'context' => $context,
                'overall_rating' => $rating,
                'summary' => trim($summaries[$i] ?? '') ?: null,
                'recommendations' => trim($recommendations[$i] ?? '') ?: null,
                'priority' => $priority,
                'next_check_date' => $nextDate,
                'environmental_notes' => trim($envNotes[$i] ?? '') ?: null,
                'handling_notes' => trim($handlingNotes[$i] ?? '') ?: null,
                'display_notes' => trim($displayNotes[$i] ?? '') ?: null,
                'storage_notes' => trim($storageNotes[$i] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    protected function mapConditionRating(string $rating): string
    {
        $map = [
            'excellent' => 'excellent',
            'good' => 'good',
            'fair' => 'fair',
            'poor' => 'poor',
            'unacceptable' => 'unacceptable',
            '5' => 'excellent',
            '4' => 'good',
            '3' => 'fair',
            '2' => 'poor',
            '1' => 'unacceptable',
        ];
        return $map[strtolower($rating)] ?? 'good';
    }

    protected function mapConditionPriority(string $priority): string
    {
        $map = [
            'low' => 'low',
            'normal' => 'normal',
            'high' => 'high',
            'urgent' => 'urgent',
            '1' => 'low',
            '2' => 'normal',
            '3' => 'high',
            '4' => 'urgent',
        ];
        return $map[strtolower($priority)] ?? 'normal';
    }

    protected function mapConditionContext(string $context): string
    {
        $validContexts = [
            'acquisition', 'loan_out', 'loan_in', 'loan_return',
            'exhibition', 'storage', 'conservation', 'routine',
            'incident', 'insurance', 'deaccession'
        ];
        $ctx = strtolower(str_replace(' ', '_', $context));
        return in_array($ctx, $validContexts) ? $ctx : 'routine';
    }

    /**
     * Generate URL-safe slug
     */
    protected function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 250);

        // Ensure uniqueness
        $DB = \Illuminate\Database\Capsule\Manager::class;
        $baseSlug = $slug;
        $counter = 1;

        while ($DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get level of description ID
     */
    protected function getLevelOfDescriptionId(string $level): ?int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $term = $DB::table('term_i18n')
            ->join('term', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', 34) // Level of description taxonomy
            ->where('term_i18n.name', $level)
            ->first();

        return $term->id ?? null;
    }

    /**
     * Set publication status
     */
    protected function setPublicationStatus(int $objectId)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $DB::table('status')->insert([
            'object_id' => $objectId,
            'type_id' => 158, // Publication status type
            'status_id' => 160, // Draft
        ]);
    }

    /**
     * Rebuild nested set for object
     */
    protected function rebuildNestedSet(int $objectId, int $parentId)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Get parent's rgt value
        $parent = $DB::table('information_object')->where('id', $parentId)->first();
        
        if (!$parent) {
            return;
        }

        $parentRgt = $parent->rgt;

        // Shift existing nodes
        $DB::table('information_object')
            ->where('rgt', '>=', $parentRgt)
            ->increment('rgt', 2);

        $DB::table('information_object')
            ->where('lft', '>', $parentRgt)
            ->increment('lft', 2);

        // Set this object's lft/rgt
        $DB::table('information_object')
            ->where('id', $objectId)
            ->update([
                'lft' => $parentRgt,
                'rgt' => $parentRgt + 1,
            ]);
    }

    /**
     * Save sector-specific metadata to appropriate tables
     */
    protected function saveSectorMetadata(int $objectId, array $record, string $sector)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        switch ($sector) {
            case 'museum':
                $this->saveMuseumMetadata($objectId, $record);
                break;
            case 'library':
                $this->saveLibraryMetadata($objectId, $record);
                break;
            case 'gallery':
                $this->saveGalleryMetadata($objectId, $record);
                break;
            case 'dam':
                $this->saveDamMetadata($objectId, $record);
                break;
            // archives uses standard information_object fields
        }
    }

    /**
     * Save museum-specific metadata to museum_metadata table
     */
    protected function saveMuseumMetadata(int $objectId, array $record)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $data = [
            'object_id' => $objectId,
            'object_type' => $record['objectType'] ?? null,
            'classification' => $record['classification'] ?? null,
            'materials' => $record['extentAndMedium'] ?? $record['materials'] ?? null,
            'techniques' => $record['physicalCharacteristics'] ?? $record['techniques'] ?? null,
            'dimensions' => $record['dimensions'] ?? null,
            'measurements' => $record['measurements'] ?? null,
            'inscriptions' => $record['inscriptions'] ?? null,
            'condition_notes' => $record['conditionNotes'] ?? null,
            'condition_term' => $record['conditionStatus'] ?? null,
            'condition_date' => $record['conditionDate'] ?? null,
            'provenance' => $record['archivalHistory'] ?? $record['provenance'] ?? null,
            'style_period' => $record['stylePeriod'] ?? null,
            'cultural_context' => $record['culturalContext'] ?? null,
            'current_location' => $record['currentLocation'] ?? null,
            'creation_place' => $record['creationPlace'] ?? $record['placeAccessPoints'] ?? null,
            'creator_identity' => $record['eventActors'] ?? null,
            'creator_role' => $record['eventTypes'] ?? null,
            'creation_date_display' => $record['eventDates'] ?? null,
            'cataloger_name' => $record['catalogerName'] ?? null,
            'cataloging_date' => !empty($record['catalogingDate']) ? $record['catalogingDate'] : date('Y-m-d'),
        ];

        // Remove null values
        $data = array_filter($data, fn($v) => $v !== null);

        if (count($data) > 1) { // More than just object_id
            $DB::table('museum_metadata')->updateOrInsert(
                ['object_id' => $objectId],
                $data
            );
        }
    }

    /**
     * Save library-specific metadata (placeholder - implement when library_metadata table exists)
     */
    protected function saveLibraryMetadata(int $objectId, array $record)
    {
        // TODO: Implement when ahgLibraryPlugin has metadata table
        // For now, library uses standard information_object fields
    }

    /**
     * Save gallery-specific metadata (placeholder - implement when gallery_metadata table exists)
     */
    protected function saveGalleryMetadata(int $objectId, array $record)
    {
        // TODO: Implement when ahgGalleryPlugin has metadata table
        // For now, gallery uses standard information_object fields
    }

    /**
     * Save DAM-specific metadata (placeholder - implement when dam_metadata table exists)
     */
    protected function saveDamMetadata(int $objectId, array $record)
    {
        // TODO: Implement when ahgDAMPlugin has metadata table
        // For now, DAM uses standard information_object fields
    }



    /**
     * Create digital object from file path
     * Hybrid approach: copy file manually, use QubitDigitalObject for DB insert
     */
    protected function createDigitalObject(int $objectId, string $sourcePath): ?int
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            $this->stats["errors"][] = "Digital object file not found or not readable: " . $sourcePath;
            return null;
        }

        try {
            $DB = \Illuminate\Database\Capsule\Manager::class;
            
            $filename = basename($sourcePath);
            $mimeType = mime_content_type($sourcePath);
            $fileSize = filesize($sourcePath);

            // Determine media type
            $mediaTypeId = QubitTerm::IMAGE_ID;
            if (strpos($mimeType, 'audio/') === 0) {
                $mediaTypeId = QubitTerm::AUDIO_ID;
            } elseif (strpos($mimeType, 'video/') === 0) {
                $mediaTypeId = QubitTerm::VIDEO_ID;
            } elseif ($mimeType === 'application/pdf' || strpos($mimeType, 'text/') === 0) {
                $mediaTypeId = QubitTerm::TEXT_ID;
            }

            // Get repository slug for path
            $infoObject = $DB::table('information_object')->where('id', $objectId)->first();
            $repoSlug = 'null';
            if ($infoObject && $infoObject->repository_id) {
                $repoSlugRow = $DB::table('slug')->where('object_id', $infoObject->repository_id)->first();
                if ($repoSlugRow) {
                    $repoSlug = $repoSlugRow->slug;
                }
            }

            // Generate checksum and path
            $checksum = sha1_file($sourcePath);
            $pathSegment = implode('/', str_split(substr($checksum, 0, 9), 3));
            $relativePath = 'uploads/r/' . $repoSlug . '/' . $pathSegment;
            $absolutePath = sfConfig::get('sf_web_dir') . '/' . $relativePath;

            // Create directory
            if (!is_dir($absolutePath)) {
                mkdir($absolutePath, 0755, true);
            }

            // Copy master file
            $destFile = $absolutePath . '/' . $filename;
            if (!copy($sourcePath, $destFile)) {
                $this->stats["errors"][] = "Failed to copy digital object: " . $sourcePath;
                return null;
            }

            // Create object record for digital object
            $digitalObjectId = $DB::table('object')->insertGetId([
                'class_name' => 'QubitDigitalObject',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Insert digital_object record
            $DB::table('digital_object')->insert([
                'id' => $digitalObjectId,
                'object_id' => $objectId,
                'usage_id' => QubitTerm::MASTER_ID,
                'media_type_id' => $mediaTypeId,
                'mime_type' => $mimeType,
                'byte_size' => $fileSize,
                'name' => $filename,
                'path' => $relativePath . '/',
                'checksum' => $checksum,
                'checksum_type' => 'sha1',
                'sequence' => 0,
            ]);

            // Generate derivatives (thumbnail and reference)
            $this->generateDerivativesForFile($digitalObjectId, $destFile, $relativePath, $mimeType, $filename);

            if (!isset($this->stats["digital_objects_created"])) {
                $this->stats["digital_objects_created"] = 0;
            }
            $this->stats["digital_objects_created"]++;

            return $digitalObjectId;

        } catch (Exception $e) {
            $this->stats["errors"][] = "Digital object creation failed for object $objectId: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Generate thumbnail and reference derivatives for an image
     */
    protected function generateDerivativesForFile(int $parentId, string $masterPath, string $relativePath, string $mimeType, string $filename): void
    {
        // Only generate derivatives for images
        if (strpos($mimeType, 'image/') !== 0) {
            return;
        }

        $DB = \Illuminate\Database\Capsule\Manager::class;
        $absolutePath = sfConfig::get('sf_web_dir') . '/' . $relativePath;

        // Get image dimensions
        $imageInfo = @getimagesize($masterPath);
        if (!$imageInfo) {
            return;
        }

        $origWidth = $imageInfo[0];
        $origHeight = $imageInfo[1];

        // Derivative sizes
        $derivatives = [
            ['usage_id' => QubitTerm::THUMBNAIL_ID, 'max' => 150, 'suffix' => '_142'],
            ['usage_id' => QubitTerm::REFERENCE_ID, 'max' => 480, 'suffix' => '_141'],
        ];

        foreach ($derivatives as $deriv) {
            // Calculate new dimensions
            $ratio = min($deriv['max'] / $origWidth, $deriv['max'] / $origHeight);
            if ($ratio >= 1) {
                $newWidth = $origWidth;
                $newHeight = $origHeight;
            } else {
                $newWidth = (int)($origWidth * $ratio);
                $newHeight = (int)($origHeight * $ratio);
            }

            // Create resized image
            $srcImage = @imagecreatefromstring(file_get_contents($masterPath));
            if (!$srcImage) {
                continue;
            }

            $dstImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
            }

            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            // Generate derivative filename
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $derivFilename = $baseName . $deriv['suffix'] . '.jpg';
            $derivPath = $absolutePath . '/' . $derivFilename;

            // Save as JPEG
            imagejpeg($dstImage, $derivPath, 85);
            imagedestroy($srcImage);
            imagedestroy($dstImage);

            // Create object record
            $derivObjectId = $DB::table('object')->insertGetId([
                'class_name' => 'QubitDigitalObject',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Insert derivative record
            $DB::table('digital_object')->insert([
                'id' => $derivObjectId,
                'object_id' => null,
                'parent_id' => $parentId,
                'usage_id' => $deriv['usage_id'],
                'media_type_id' => QubitTerm::IMAGE_ID,
                'mime_type' => 'image/jpeg',
                'byte_size' => filesize($derivPath),
                'name' => $derivFilename,
                'path' => $relativePath . '/',
                'sequence' => 0,
            ]);
        }
    }

}