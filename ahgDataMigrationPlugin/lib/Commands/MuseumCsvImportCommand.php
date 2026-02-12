<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Museum (Spectrum 5.0) CSV import command.
 *
 * Imports CSV files following Spectrum standard for museum collections.
 */
class MuseumCsvImportCommand extends BaseCommand
{
    protected string $name = 'sector:museum-csv-import';
    protected string $description = 'Import museum CSV data with Spectrum validation';

    protected string $detailedDescription = <<<'EOF'
    Import museum CSV data following the Spectrum 5.0 standard into AtoM.

    Supports column mapping, validation-only mode, update/merge of existing
    records, and optional mapping profiles from the database.

    Examples:
      php bin/atom sector:museum-csv-import /path/to/file.csv
      php bin/atom sector:museum-csv-import /path/to/file.csv --validate-only
      php bin/atom sector:museum-csv-import /path/to/file.csv --repository=my-repo --culture=af
      php bin/atom sector:museum-csv-import /path/to/file.csv --update=identifier --update-mode=merge
      php bin/atom sector:museum-csv-import /path/to/file.csv --limit=100 --skip=10
    EOF;

    protected string $culture = 'en';
    protected ?object $repository = null;
    protected array $mapping = [];
    protected string $updateMode = 'skip';
    protected string $matchField = 'legacyId';
    protected bool $validateOnly = false;

    protected array $counters = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    protected function configure(): void
    {
        $this->addArgument('filename', 'CSV file to import', true);
        $this->addOption('validate-only', null, 'Validate without importing');
        $this->addOption('mapping', null, 'Mapping profile ID to use');
        $this->addOption('repository', null, 'Target repository slug');
        $this->addOption('update', null, 'Match field for updates (identifier, legacyId)', 'legacyId');
        $this->addOption('update-mode', null, 'Update mode: skip, update, merge', 'skip');
        $this->addOption('culture', null, 'Default culture for i18n fields', 'en');
        $this->addOption('limit', null, 'Maximum rows to process');
        $this->addOption('skip', null, 'Number of rows to skip', '0');
    }

    protected function handle(): int
    {
        $filename = $this->argument('filename');

        if (!file_exists($filename)) {
            $this->error("File not found: {$filename}");

            return 1;
        }

        $this->culture = $this->option('culture') ?? 'en';
        $this->validateOnly = $this->hasOption('validate-only');
        $this->matchField = $this->option('update') ?? 'legacyId';
        $this->updateMode = $this->option('update-mode') ?? 'skip';

        if ($this->option('mapping')) {
            $this->loadMapping((int) $this->option('mapping'));
        }

        if ($this->option('repository')) {
            $this->repository = $this->findRepository($this->option('repository'));
            if (!$this->repository) {
                $this->error('Repository not found: ' . $this->option('repository'));

                return 1;
            }
        }

        $this->info(sprintf('Processing %s for sector: museum', basename($filename)));

        if ($this->validateOnly) {
            return $this->runValidation($filename);
        }

        return $this->runImport($filename);
    }

    protected function getColumnMap(): array
    {
        return [
            'legacy_id' => 'legacyId',
            'parent_id' => 'parentId',
            'object_number' => 'objectNumber',
            'accession_number' => 'objectNumber',
            'object_name' => 'objectName',
            'name' => 'title',
            'number_of_objects' => 'numberOfObjects',
            'quantity' => 'numberOfObjects',
            'brief_description' => 'briefDescription',
            'description' => 'briefDescription',
            'scope_and_content' => 'briefDescription',
            'notes' => 'comments',
            'distinguishing_features' => 'distinguishingFeatures',
            'object_production_person' => 'objectProductionPerson',
            'maker' => 'objectProductionPerson',
            'creator' => 'objectProductionPerson',
            'artist' => 'objectProductionPerson',
            'maker_role' => 'objectProductionPersonRole',
            'object_production_organisation' => 'objectProductionOrganisation',
            'manufacturer' => 'objectProductionOrganisation',
            'object_production_date' => 'objectProductionDate',
            'date_made' => 'objectProductionDate',
            'production_date' => 'objectProductionDate',
            'date' => 'objectProductionDate',
            'object_production_place' => 'objectProductionPlace',
            'place_made' => 'objectProductionPlace',
            'production_place' => 'objectProductionPlace',
            'material' => 'materials',
            'medium' => 'materials',
            'measurement' => 'dimensions',
            'inscription' => 'inscriptions',
            'marks' => 'inscriptions',
            'history' => 'objectHistoryNote',
            'provenance' => 'objectHistoryNote',
            'ownership_history' => 'ownershipHistory',
            'acquisition_method' => 'acquisitionMethod',
            'acquisition_date' => 'acquisitionDate',
            'acquisition_source' => 'acquisitionSource',
            'donor' => 'acquisitionSource',
            'acquisition_reason' => 'acquisitionReason',
            'current_location' => 'currentLocation',
            'location' => 'currentLocation',
            'normal_location' => 'normalLocation',
            'condition_note' => 'conditionNote',
            'subjects' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'image' => 'digitalObjectPath',
        ];
    }

    protected function getRequiredColumns(): array
    {
        return ['objectNumber', 'objectName'];
    }

    protected function runValidation(string $filename): int
    {
        $this->info('Running validation only (no import)...');

        $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
        $serviceFile = $pluginPath . '/lib/Services/ValidationService.php';
        if (file_exists($serviceFile)) {
            require_once $serviceFile;
        }

        $service = new \ahgDataMigrationPlugin\Services\ValidationService(
            'museum',
            ['checkDatabase' => false]
        );

        $report = $service->validateOnly($filename, $this->mapping);

        $this->newline();
        $this->info('=== Validation Results ===');
        $this->line(sprintf('Total rows: %d', $report->getTotalRows()));
        $this->line(sprintf('Valid rows: %d', $report->getValidRows()));
        $this->line(sprintf('Errors: %d', $report->getErrorCount()));
        $this->line(sprintf('Warnings: %d', $report->getWarningCount()));

        if (!$report->isValid()) {
            $this->newline();
            $this->error('Errors found:');
            foreach ($report->formatErrors(20) as $err) {
                $this->line('  ' . $err);
            }
        }

        return $report->isValid() ? 0 : 1;
    }

    protected function runImport(string $filename): int
    {
        $handle = fopen($filename, 'r');
        if (!$handle) {
            $this->error('Failed to open file');

            return 1;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            $this->error('Failed to read CSV header');
            fclose($handle);

            return 1;
        }
        $header = array_map('trim', $header);

        $columnMap = array_merge($this->getColumnMap(), $this->mapping);

        $limitOpt = $this->option('limit');
        $limit = $limitOpt ? (int) $limitOpt : PHP_INT_MAX;
        $skip = (int) ($this->option('skip') ?? 0);

        $rowNumber = 1;
        while (false !== ($row = fgetcsv($handle)) && $this->counters['total'] < $limit) {
            ++$rowNumber;

            if ($rowNumber <= $skip + 1) {
                continue;
            }

            ++$this->counters['total'];

            try {
                $data = [];
                foreach ($header as $i => $col) {
                    $value = trim($row[$i] ?? '');
                    $targetCol = $columnMap[$col] ?? $col;
                    $data[$targetCol] = $value;
                }

                $result = $this->processRow($data, $rowNumber);

                if ('created' === $result) {
                    ++$this->counters['imported'];
                } elseif ('updated' === $result) {
                    ++$this->counters['updated'];
                } else {
                    ++$this->counters['skipped'];
                }
            } catch (\Exception $e) {
                ++$this->counters['errors'];
                $this->error(sprintf('Row %d: Error - %s', $rowNumber, $e->getMessage()));
            }

            if (0 === $this->counters['total'] % 100) {
                $this->info(sprintf('Processed %d rows...', $this->counters['total']));
            }
        }

        fclose($handle);

        $this->newline();
        $this->info('=== Import Summary ===');
        $this->line(sprintf('Total rows processed: %d', $this->counters['total']));
        $this->line(sprintf('Records created: %d', $this->counters['imported']));
        $this->line(sprintf('Records updated: %d', $this->counters['updated']));
        $this->line(sprintf('Records skipped: %d', $this->counters['skipped']));
        $this->line(sprintf('Errors: %d', $this->counters['errors']));

        return 0 === $this->counters['errors'] ? 0 : 1;
    }

    protected function processRow(array $data, int $rowNumber): string
    {
        $existingId = $this->findExisting($data);

        if (null !== $existingId) {
            if ('skip' === $this->updateMode) {
                return 'skipped';
            }

            return $this->updateRecord($existingId, $data);
        }

        return $this->createRecord($data);
    }

    protected function findExisting(array $data): ?int
    {
        $matchValue = $data[$this->matchField] ?? null;

        if (empty($matchValue)) {
            return null;
        }

        if ('legacyId' === $this->matchField) {
            $keymap = DB::table('keymap')
                ->where('source_name', 'legacyId')
                ->where('source_id', $matchValue)
                ->first();

            return $keymap ? (int) $keymap->target_id : null;
        }

        if ('identifier' === $this->matchField) {
            $record = DB::table('information_object')
                ->where('identifier', $matchValue)
                ->first();

            return $record ? (int) $record->id : null;
        }

        return null;
    }

    protected function createRecord(array $data): string
    {
        $io = new \QubitInformationObject();
        $io->parentId = $this->getParentId($data);
        $io->identifier = $data['objectNumber'] ?? $data['identifier'] ?? null;
        $io->repositoryId = $this->repository ? $this->repository->id : null;
        $io->levelOfDescriptionId = $this->getLevelOfDescriptionId($data);
        $io->culture = $this->culture;

        $this->setI18nFields($io, $data);

        $io->save();

        if (!empty($data['legacyId'])) {
            $this->saveKeymap($io->id, $data['legacyId']);
        }

        $this->createEvents($io->id, $data);
        $this->createAccessPoints($io->id, $data);
        $this->saveSectorMetadata($io->id, $data);

        return 'created';
    }

    protected function updateRecord(int $id, array $data): string
    {
        $io = \QubitInformationObject::getById($id);
        if (!$io) {
            throw new \Exception("Record not found: {$id}");
        }

        if ('merge' === $this->updateMode) {
            if (empty($io->identifier) && !empty($data['objectNumber'])) {
                $io->identifier = $data['objectNumber'];
            }
        } else {
            $io->identifier = $data['objectNumber'] ?? $io->identifier;
            $this->setI18nFields($io, $data);
        }

        $io->save();
        $this->saveSectorMetadata($io->id, $data);

        return 'updated';
    }

    protected function setI18nFields(\QubitInformationObject $io, array $data): void
    {
        $io->title = $data['title'] ?? $data['objectName'] ?? null;
        $io->identifier = $data['objectNumber'] ?? $data['identifier'] ?? null;
        $io->extentAndMedium = $this->formatExtent($data);
        $io->scopeAndContent = $data['briefDescription'] ?? $data['description'] ?? null;
        $io->archivalHistory = $data['objectHistoryNote'] ?? $data['provenance'] ?? null;
        $io->physicalCharacteristics = $data['condition'] ?? $data['conditionNote'] ?? null;
    }

    protected function formatExtent(array $data): ?string
    {
        $parts = [];

        if (!empty($data['numberOfObjects'])) {
            $parts[] = $data['numberOfObjects'] . ' object(s)';
        }

        if (!empty($data['materials'])) {
            $parts[] = 'Materials: ' . $data['materials'];
        }

        if (!empty($data['dimensions'])) {
            $parts[] = 'Dimensions: ' . $data['dimensions'];
        }

        return !empty($parts) ? implode('; ', $parts) : null;
    }

    protected function saveSectorMetadata(int $objectId, array $row): void
    {
        try {
            $exists = DB::table('museum_metadata')
                ->where('information_object_id', $objectId)
                ->exists();

            $metadata = [
                'information_object_id' => $objectId,
                'object_name' => $row['objectName'] ?? null,
                'number_of_objects' => $row['numberOfObjects'] ?? null,
                'technique' => $row['technique'] ?? null,
                'dimensions' => $row['dimensions'] ?? null,
                'inscription' => $row['inscriptions'] ?? null,
                'acquisition_method' => $row['acquisitionMethod'] ?? null,
                'acquisition_date' => $row['acquisitionDate'] ?? null,
                'acquisition_source' => $row['acquisitionSource'] ?? null,
                'current_location' => $row['currentLocation'] ?? null,
                'normal_location' => $row['normalLocation'] ?? null,
                'condition_note' => $row['conditionNote'] ?? null,
            ];

            if ($exists) {
                DB::table('museum_metadata')
                    ->where('information_object_id', $objectId)
                    ->update($metadata);
            } else {
                DB::table('museum_metadata')->insert($metadata);
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip sector metadata
        }
    }

    protected function getParentId(array $data): int
    {
        $parentId = $data['parentId'] ?? null;

        if (empty($parentId)) {
            return \QubitInformationObject::ROOT_ID;
        }

        $keymap = DB::table('keymap')
            ->where('source_name', 'legacyId')
            ->where('source_id', $parentId)
            ->first();

        if ($keymap) {
            return (int) $keymap->target_id;
        }

        $parent = DB::table('information_object')
            ->where('identifier', $parentId)
            ->first();

        return $parent ? (int) $parent->id : \QubitInformationObject::ROOT_ID;
    }

    protected function getLevelOfDescriptionId(array $data): ?int
    {
        $level = $data['levelOfDescription'] ?? $data['level'] ?? null;

        if (empty($level)) {
            return null;
        }

        $term = DB::table('term_i18n')
            ->join('term', 'term_i18n.id', '=', 'term.id')
            ->where('term.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->whereRaw('LOWER(term_i18n.name) = ?', [strtolower($level)])
            ->first();

        return $term ? (int) $term->id : null;
    }

    protected function saveKeymap(int $targetId, string $legacyId): void
    {
        DB::table('keymap')->insert([
            'source_name' => 'legacyId',
            'source_id' => $legacyId,
            'target_id' => $targetId,
            'target_name' => 'information_object',
        ]);
    }

    protected function createEvents(int $objectId, array $data): void
    {
        $dateRange = $data['objectProductionDate'] ?? $data['dateRange'] ?? $data['date'] ?? null;
        $creators = $data['objectProductionPerson'] ?? $data['creators'] ?? $data['creator'] ?? null;

        if ($dateRange || $creators) {
            $event = new \QubitEvent();
            $event->objectId = $objectId;
            $event->typeId = \QubitTerm::CREATION_ID;
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

    protected function findOrCreateActor(string $name): ?\QubitActor
    {
        $existing = DB::table('actor_i18n')
            ->where('authorized_form_of_name', $name)
            ->first();

        if ($existing) {
            return \QubitActor::getById($existing->id);
        }

        $actor = new \QubitActor();
        $actor->authorizedFormOfName = $name;
        $actor->culture = $this->culture;
        $actor->save();

        return $actor;
    }

    protected function createAccessPoints(int $objectId, array $data): void
    {
        $subjects = $data['subjectAccessPoints'] ?? $data['subjects'] ?? null;
        if ($subjects) {
            $this->createTermRelations($objectId, $subjects, \QubitTaxonomy::SUBJECT_ID);
        }

        $places = $data['placeAccessPoints'] ?? $data['places'] ?? null;
        if ($places) {
            $this->createTermRelations($objectId, $places, \QubitTaxonomy::PLACE_ID);
        }
    }

    protected function createTermRelations(int $objectId, string $terms, int $taxonomyId): void
    {
        $termList = array_filter(array_map('trim', explode('|', $terms)));

        foreach ($termList as $termName) {
            $term = $this->findOrCreateTerm($termName, $taxonomyId);
            if ($term) {
                $relation = new \QubitObjectTermRelation();
                $relation->objectId = $objectId;
                $relation->termId = $term->id;
                $relation->save();
            }
        }
    }

    protected function findOrCreateTerm(string $name, int $taxonomyId): ?\QubitTerm
    {
        $existing = DB::table('term_i18n')
            ->join('term', 'term_i18n.id', '=', 'term.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', $name)
            ->first();

        if ($existing) {
            return \QubitTerm::getById($existing->id);
        }

        $term = new \QubitTerm();
        $term->taxonomyId = $taxonomyId;
        $term->name = $name;
        $term->culture = $this->culture;
        $term->save();

        return $term;
    }

    protected function loadMapping(int $mappingId): void
    {
        $mapping = DB::table('atom_data_mapping')
            ->where('id', $mappingId)
            ->first();

        if (!$mapping) {
            $this->warning('Mapping profile not found: ' . $mappingId);

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

    protected function findRepository(string $slug): ?object
    {
        $slugRecord = DB::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$slugRecord) {
            return null;
        }

        return \QubitRepository::getById($slugRecord->object_id);
    }
}
