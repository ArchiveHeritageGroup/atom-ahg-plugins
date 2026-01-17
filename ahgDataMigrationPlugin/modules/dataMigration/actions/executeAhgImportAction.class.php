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

        return $objectId;
    }

    /**
     * Create a new information object
     */
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
            $this->stats['rights_created']++;
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
}
