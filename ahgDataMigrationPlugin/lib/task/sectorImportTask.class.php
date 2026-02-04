<?php

/**
 * Base sector import task class.
 *
 * Provides common functionality for all sector-specific CSV import tasks.
 * Sector-specific tasks should extend this class and implement the abstract methods.
 */
abstract class sectorImportTask extends arBaseTask
{
    protected $namespace = 'sector';
    protected $briefDescription = 'Import CSV data with sector-specific validation';

    protected $culture = 'en';
    protected $repository = null;
    protected $mapping = [];
    protected $updateMode = 'skip'; // skip, update, merge
    protected $matchField = 'legacyId';
    protected $validateOnly = false;
    protected $validationService = null;

    protected $counters = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    /**
     * Get the sector code (e.g., 'archive', 'museum').
     */
    abstract protected function getSectorCode(): string;

    /**
     * Get the column mapping from source CSV to AtoM fields.
     */
    abstract protected function getColumnMap(): array;

    /**
     * Get required columns for this sector.
     */
    abstract protected function getRequiredColumns(): array;

    /**
     * Save sector-specific metadata for the imported object.
     */
    abstract protected function saveSectorMetadata(int $objectId, array $row): void;

    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('filename', sfCommandArgument::REQUIRED, 'CSV file to import'),
        ]);

        $this->addOptions([
            new sfCommandOption('validate-only', null, sfCommandOption::PARAMETER_NONE, 'Validate without importing'),
            new sfCommandOption('mapping', null, sfCommandOption::PARAMETER_REQUIRED, 'Mapping profile ID to use'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_REQUIRED, 'Target repository slug'),
            new sfCommandOption('update', null, sfCommandOption::PARAMETER_REQUIRED, 'Match field for updates (identifier, legacyId)', 'legacyId'),
            new sfCommandOption('update-mode', null, sfCommandOption::PARAMETER_REQUIRED, 'Update mode: skip, update, merge', 'skip'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_REQUIRED, 'Default culture for i18n fields', 'en'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Maximum rows to process', null),
            new sfCommandOption('skip', null, sfCommandOption::PARAMETER_REQUIRED, 'Number of rows to skip', 0),
        ]);
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $filename = $arguments['filename'];

        // Validate file exists
        if (!file_exists($filename)) {
            $this->log('File not found: '.$filename);

            return 1;
        }

        // Set options
        $this->culture = $options['culture'] ?? 'en';
        $this->validateOnly = $options['validate-only'] ?? false;
        $this->matchField = $options['update'] ?? 'legacyId';
        $this->updateMode = $options['update-mode'] ?? 'skip';

        // Load mapping if specified
        if (!empty($options['mapping'])) {
            $this->loadMapping((int) $options['mapping']);
        }

        // Set repository if specified
        if (!empty($options['repository'])) {
            $this->repository = $this->findRepository($options['repository']);
            if (!$this->repository) {
                $this->log('Repository not found: '.$options['repository']);

                return 1;
            }
        }

        // Initialize validation service
        $this->validationService = new \ahgDataMigrationPlugin\Services\ValidationService(
            $this->getSectorCode(),
            [
                'checkDatabase' => !$this->validateOnly,
            ]
        );

        // Process the file
        $this->log(sprintf('Processing %s for sector: %s', basename($filename), $this->getSectorCode()));

        if ($this->validateOnly) {
            return $this->runValidation($filename);
        }

        return $this->runImport($filename, $options);
    }

    /**
     * Run validation only (no import).
     */
    protected function runValidation(string $filename): int
    {
        $this->log('Running validation only (no import)...');

        $report = $this->validationService->validateOnly($filename, $this->mapping);

        // Output summary
        $this->log('');
        $this->log('=== Validation Results ===');
        $this->log(sprintf('Total rows: %d', $report->getTotalRows()));
        $this->log(sprintf('Valid rows: %d', $report->getValidRows()));
        $this->log(sprintf('Errors: %d', $report->getErrorCount()));
        $this->log(sprintf('Warnings: %d', $report->getWarningCount()));

        if (!$report->isValid()) {
            $this->log('');
            $this->log('Errors found:');
            foreach ($report->formatErrors(20) as $error) {
                $this->log('  '.$error);
            }
        }

        return $report->isValid() ? 0 : 1;
    }

    /**
     * Run the import process.
     */
    protected function runImport(string $filename, array $options): int
    {
        $handle = fopen($filename, 'r');
        if (!$handle) {
            $this->log('Failed to open file');

            return 1;
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            $this->log('Failed to read CSV header');
            fclose($handle);

            return 1;
        }
        $header = array_map('trim', $header);

        // Apply mapping
        $columnMap = array_merge($this->getColumnMap(), $this->mapping);

        $limit = !empty($options['limit']) ? (int) $options['limit'] : PHP_INT_MAX;
        $skip = (int) ($options['skip'] ?? 0);

        $rowNumber = 1;
        while (false !== ($row = fgetcsv($handle)) && $this->counters['total'] < $limit) {
            ++$rowNumber;

            if ($rowNumber <= $skip + 1) {
                continue;
            }

            ++$this->counters['total'];

            try {
                // Combine with header
                $data = [];
                foreach ($header as $i => $col) {
                    $value = trim($row[$i] ?? '');
                    $targetCol = $columnMap[$col] ?? $col;
                    $data[$targetCol] = $value;
                }

                // Process the row
                $result = $this->processRow($data, $rowNumber);

                if ('created' === $result) {
                    ++$this->counters['imported'];
                } elseif ('updated' === $result) {
                    ++$this->counters['updated'];
                } else {
                    ++$this->counters['skipped'];
                }
            } catch (Exception $e) {
                ++$this->counters['errors'];
                $this->log(sprintf('Row %d: Error - %s', $rowNumber, $e->getMessage()));
            }

            // Progress output every 100 rows
            if (0 === $this->counters['total'] % 100) {
                $this->log(sprintf('Processed %d rows...', $this->counters['total']));
            }
        }

        fclose($handle);

        // Output summary
        $this->log('');
        $this->log('=== Import Summary ===');
        $this->log(sprintf('Total rows processed: %d', $this->counters['total']));
        $this->log(sprintf('Records created: %d', $this->counters['imported']));
        $this->log(sprintf('Records updated: %d', $this->counters['updated']));
        $this->log(sprintf('Records skipped: %d', $this->counters['skipped']));
        $this->log(sprintf('Errors: %d', $this->counters['errors']));

        return 0 === $this->counters['errors'] ? 0 : 1;
    }

    /**
     * Process a single row.
     *
     * @return string 'created', 'updated', or 'skipped'
     */
    protected function processRow(array $data, int $rowNumber): string
    {
        // Check for existing record
        $existingId = $this->findExisting($data);

        if (null !== $existingId) {
            if ('skip' === $this->updateMode) {
                return 'skipped';
            }

            return $this->updateRecord($existingId, $data);
        }

        return $this->createRecord($data);
    }

    /**
     * Find existing record by match field.
     */
    protected function findExisting(array $data): ?int
    {
        $matchValue = $data[$this->matchField] ?? null;

        if (empty($matchValue)) {
            return null;
        }

        if ('legacyId' === $this->matchField) {
            $keymap = Illuminate\Database\Capsule\Manager::table('keymap')
                ->where('source_name', 'legacyId')
                ->where('source_id', $matchValue)
                ->first()
            ;

            return $keymap ? (int) $keymap->target_id : null;
        }

        if ('identifier' === $this->matchField) {
            $record = Illuminate\Database\Capsule\Manager::table('information_object')
                ->where('identifier', $matchValue)
                ->first()
            ;

            return $record ? (int) $record->id : null;
        }

        return null;
    }

    /**
     * Create a new record.
     *
     * @return string 'created'
     */
    protected function createRecord(array $data): string
    {
        // Create information object
        $io = new QubitInformationObject();
        $io->parentId = $this->getParentId($data);
        $io->identifier = $data['identifier'] ?? null;
        $io->repositoryId = $this->repository ? $this->repository->id : null;
        $io->levelOfDescriptionId = $this->getLevelOfDescriptionId($data);
        $io->culture = $this->culture;

        // Set i18n fields
        $this->setI18nFields($io, $data);

        $io->save();

        // Save legacy ID to keymap
        if (!empty($data['legacyId'])) {
            $this->saveKeymap($io->id, $data['legacyId']);
        }

        // Create events (dates, creators)
        $this->createEvents($io->id, $data);

        // Create access points
        $this->createAccessPoints($io->id, $data);

        // Save sector-specific metadata
        $this->saveSectorMetadata($io->id, $data);

        return 'created';
    }

    /**
     * Update an existing record.
     *
     * @return string 'updated'
     */
    protected function updateRecord(int $id, array $data): string
    {
        $io = QubitInformationObject::getById($id);
        if (!$io) {
            throw new Exception("Record not found: {$id}");
        }

        // Update fields based on update mode
        if ('merge' === $this->updateMode) {
            // Only update empty fields
            if (empty($io->identifier) && !empty($data['identifier'])) {
                $io->identifier = $data['identifier'];
            }
            // ... etc
        } else {
            // Full update
            $io->identifier = $data['identifier'] ?? $io->identifier;
            $this->setI18nFields($io, $data);
        }

        $io->save();

        // Update sector-specific metadata
        $this->saveSectorMetadata($io->id, $data);

        return 'updated';
    }

    /**
     * Set i18n fields on the information object.
     */
    protected function setI18nFields(QubitInformationObject $io, array $data): void
    {
        $io->title = $data['title'] ?? null;
        $io->extentAndMedium = $data['extentAndMedium'] ?? $data['extent'] ?? null;
        $io->scopeAndContent = $data['scopeAndContent'] ?? $data['description'] ?? null;
        $io->archivalHistory = $data['archivalHistory'] ?? null;
        $io->acquisition = $data['acquisition'] ?? null;
        $io->accessConditions = $data['accessConditions'] ?? null;
        $io->reproductionConditions = $data['reproductionConditions'] ?? null;
        $io->physicalCharacteristics = $data['physicalCharacteristics'] ?? null;
        $io->findingAids = $data['findingAids'] ?? null;
        $io->arrangement = $data['arrangement'] ?? null;
    }

    /**
     * Get parent ID from data.
     */
    protected function getParentId(array $data): int
    {
        $parentId = $data['parentId'] ?? null;

        if (empty($parentId)) {
            return QubitInformationObject::ROOT_ID;
        }

        // Try to find parent by legacy ID
        $keymap = Illuminate\Database\Capsule\Manager::table('keymap')
            ->where('source_name', 'legacyId')
            ->where('source_id', $parentId)
            ->first()
        ;

        if ($keymap) {
            return (int) $keymap->target_id;
        }

        // Try by identifier
        $parent = Illuminate\Database\Capsule\Manager::table('information_object')
            ->where('identifier', $parentId)
            ->first()
        ;

        return $parent ? (int) $parent->id : QubitInformationObject::ROOT_ID;
    }

    /**
     * Get level of description term ID.
     */
    protected function getLevelOfDescriptionId(array $data): ?int
    {
        $level = $data['levelOfDescription'] ?? $data['level'] ?? null;

        if (empty($level)) {
            return null;
        }

        $term = Illuminate\Database\Capsule\Manager::table('term_i18n')
            ->join('term', 'term_i18n.id', '=', 'term.id')
            ->where('term.taxonomy_id', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->whereRaw('LOWER(term_i18n.name) = ?', [strtolower($level)])
            ->first()
        ;

        return $term ? (int) $term->id : null;
    }

    /**
     * Save legacy ID to keymap.
     */
    protected function saveKeymap(int $targetId, string $legacyId): void
    {
        Illuminate\Database\Capsule\Manager::table('keymap')->insert([
            'source_name' => 'legacyId',
            'source_id' => $legacyId,
            'target_id' => $targetId,
            'target_name' => 'information_object',
        ]);
    }

    /**
     * Create events (dates, creators).
     */
    protected function createEvents(int $objectId, array $data): void
    {
        // Create creation event
        $dateRange = $data['dateRange'] ?? $data['date'] ?? null;
        $creators = $data['creators'] ?? $data['creator'] ?? null;

        if ($dateRange || $creators) {
            $event = new QubitEvent();
            $event->objectId = $objectId;
            $event->typeId = QubitTerm::CREATION_ID;
            $event->date = $dateRange;
            $event->startDate = $data['dateStart'] ?? null;
            $event->endDate = $data['dateEnd'] ?? null;
            $event->culture = $this->culture;

            if ($creators) {
                $actor = $this->findOrCreateActor($creators);
                if ($actor) {
                    $event->actorId = $actor->id;
                }
            }

            $event->save();
        }
    }

    /**
     * Find or create an actor.
     */
    protected function findOrCreateActor(string $name): ?QubitActor
    {
        // Try to find existing actor
        $existing = Illuminate\Database\Capsule\Manager::table('actor_i18n')
            ->where('authorized_form_of_name', $name)
            ->first()
        ;

        if ($existing) {
            return QubitActor::getById($existing->id);
        }

        // Create new actor
        $actor = new QubitActor();
        $actor->authorizedFormOfName = $name;
        $actor->culture = $this->culture;
        $actor->save();

        return $actor;
    }

    /**
     * Create access points from data.
     */
    protected function createAccessPoints(int $objectId, array $data): void
    {
        // Subject access points
        $subjects = $data['subjectAccessPoints'] ?? $data['subjects'] ?? null;
        if ($subjects) {
            $this->createTermRelations($objectId, $subjects, QubitTaxonomy::SUBJECT_ID);
        }

        // Place access points
        $places = $data['placeAccessPoints'] ?? $data['places'] ?? null;
        if ($places) {
            $this->createTermRelations($objectId, $places, QubitTaxonomy::PLACE_ID);
        }

        // Genre access points
        $genres = $data['genreAccessPoints'] ?? $data['genres'] ?? null;
        if ($genres) {
            $this->createTermRelations($objectId, $genres, QubitTaxonomy::GENRE_ID);
        }
    }

    /**
     * Create term relations for access points.
     */
    protected function createTermRelations(int $objectId, string $terms, int $taxonomyId): void
    {
        $termList = array_filter(array_map('trim', explode('|', $terms)));

        foreach ($termList as $termName) {
            $term = $this->findOrCreateTerm($termName, $taxonomyId);
            if ($term) {
                $relation = new QubitObjectTermRelation();
                $relation->objectId = $objectId;
                $relation->termId = $term->id;
                $relation->save();
            }
        }
    }

    /**
     * Find or create a term.
     */
    protected function findOrCreateTerm(string $name, int $taxonomyId): ?QubitTerm
    {
        // Try to find existing term
        $existing = Illuminate\Database\Capsule\Manager::table('term_i18n')
            ->join('term', 'term_i18n.id', '=', 'term.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', $name)
            ->first()
        ;

        if ($existing) {
            return QubitTerm::getById($existing->id);
        }

        // Create new term
        $term = new QubitTerm();
        $term->taxonomyId = $taxonomyId;
        $term->name = $name;
        $term->culture = $this->culture;
        $term->save();

        return $term;
    }

    /**
     * Load mapping from database.
     */
    protected function loadMapping(int $mappingId): void
    {
        $mapping = Illuminate\Database\Capsule\Manager::table('atom_data_mapping')
            ->where('id', $mappingId)
            ->first()
        ;

        if (!$mapping) {
            $this->log('Warning: Mapping profile not found: '.$mappingId);

            return;
        }

        $fieldMappings = json_decode($mapping->field_mappings, true);
        if (!is_array($fieldMappings)) {
            return;
        }

        foreach ($fieldMappings as $field) {
            if (isset($field['source']) && isset($field['target'])) {
                $this->mapping[$field['source']] = $field['target'];
            }
        }
    }

    /**
     * Find repository by slug.
     */
    protected function findRepository(string $slug): ?QubitRepository
    {
        $slugRecord = Illuminate\Database\Capsule\Manager::table('slug')
            ->where('slug', $slug)
            ->first()
        ;

        if (!$slugRecord) {
            return null;
        }

        return QubitRepository::getById($slugRecord->object_id);
    }

    /**
     * Log a message.
     */
    public function log($message): void
    {
        $this->logSection($this->getSectorCode(), $message);
    }
}
