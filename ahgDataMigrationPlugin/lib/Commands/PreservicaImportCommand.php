<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for importing data from Preservica OPEX and PAX formats.
 */
class PreservicaImportCommand extends BaseCommand
{
    protected string $name = 'preservica:import';
    protected string $description = 'Import data from Preservica OPEX or PAX format';

    protected string $detailedDescription = <<<'EOF'
    Import archival descriptions and digital objects from Preservica OPEX XML
    files or PAX packages into AtoM.

    Supported formats:
      - OPEX (Open Preservation Exchange) XML files
      - PAX (Preservica Archive eXchange) ZIP packages with XIP metadata

    Examples:
      php bin/atom preservica:import /path/to/file.opex
      php bin/atom preservica:import /path/to/package.pax --format=xip
      php bin/atom preservica:import /path/to/directory --batch
      php bin/atom preservica:import /path/to/file.opex --repository=5 --parent=123
      php bin/atom preservica:import /path/to/file.opex --dry-run
      php bin/atom preservica:import /path/to/file.opex --update --match-field=identifier
    EOF;

    protected function configure(): void
    {
        $this->addArgument('source', 'Path to OPEX file, PAX package, or directory', true);
        $this->addOption('format', null, 'Source format: opex or xip', 'opex');
        $this->addOption('repository', null, 'Repository ID for imported records');
        $this->addOption('parent', null, 'Parent information object ID');
        $this->addOption('culture', null, 'Culture/language code', 'en');
        $this->addOption('update', null, 'Update existing records if found');
        $this->addOption('match-field', null, 'Field to match existing records', 'legacyId');
        $this->addOption('no-digital-objects', null, 'Skip digital object import');
        $this->addOption('no-checksums', null, 'Skip checksum verification');
        $this->addOption('no-hierarchy', null, 'Import flat (ignore parent references)');
        $this->addOption('batch', null, 'Batch import all files in directory');
        $this->addOption('dry-run', null, 'Simulate import without database changes');
        $this->addOption('no-derivatives', null, 'Skip derivative generation');
        $this->addOption('queue-derivatives', null, 'Queue derivative generation as background job');
        $this->addOption('mapping', null, 'Custom mapping file (JSON)');
    }

    protected function handle(): int
    {
        $source = $this->argument('source');
        $format = $this->option('format') ?? 'opex';

        if (!file_exists($source)) {
            $this->error("Source not found: {$source}");

            return 1;
        }

        $this->info('Starting Preservica import');
        $this->info("Source: {$source}");
        $this->info("Format: {$format}");

        $importOptions = [
            'update_existing' => $this->hasOption('update'),
            'match_field' => $this->option('match-field') ?? 'legacyId',
            'import_digital_objects' => !$this->hasOption('no-digital-objects'),
            'verify_checksums' => !$this->hasOption('no-checksums'),
            'create_hierarchy' => !$this->hasOption('no-hierarchy'),
            'dry_run' => $this->hasOption('dry-run'),
        ];

        $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
        $serviceFile = $pluginPath . '/lib/Services/PreservicaImportService.php';
        if (file_exists($serviceFile)) {
            require_once $serviceFile;
        }

        $service = new \ahgDataMigrationPlugin\Services\PreservicaImportService($format, $importOptions);

        if ($this->option('repository')) {
            $service->setRepository((int) $this->option('repository'));
            $this->info("Repository ID: " . $this->option('repository'));
        }

        if ($this->option('parent')) {
            $service->setParent((int) $this->option('parent'));
            $this->info("Parent ID: " . $this->option('parent'));
        }

        $service->setCulture($this->option('culture') ?? 'en');

        if ($this->option('mapping')) {
            $mappingFile = $this->option('mapping');
            if (!file_exists($mappingFile)) {
                $this->error("Mapping file not found: {$mappingFile}");

                return 1;
            }
            $mapping = json_decode(file_get_contents($mappingFile), true);
            if ($mapping && isset($mapping['fields'])) {
                $fieldMap = [];
                foreach ($mapping['fields'] as $field) {
                    if ($field['include']) {
                        $fieldMap[$field['source_field']] = $field['atom_field'];
                    }
                }
                $service->setFieldMapping($fieldMap);
                $this->info("Using custom mapping: {$mappingFile}");
            }
        }

        if ($this->hasOption('dry-run')) {
            $this->warning('DRY RUN MODE - No database changes will be made');
        }

        $startTime = microtime(true);

        try {
            if (is_dir($source) || $this->hasOption('batch')) {
                $result = $service->importDirectory($source);
            } elseif ($format === 'xip' || pathinfo($source, PATHINFO_EXTENSION) === 'pax') {
                $result = $service->importPaxPackage($source);
            } else {
                $result = $service->importOpexFile($source);
            }
        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());

            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newline();
        $this->info('=== Import Results ===');
        $this->line("Total records:  {$result['stats']['total']}");
        $this->line("Imported:       {$result['stats']['imported']}");
        $this->line("Updated:        {$result['stats']['updated']}");
        $this->line("Skipped:        {$result['stats']['skipped']}");
        $this->line("Errors:         {$result['stats']['errors']}");
        $this->line("Time:           {$elapsed} seconds");

        if (!empty($result['errors'])) {
            $this->newline();
            $this->error('=== Errors ===');
            foreach ($result['errors'] as $err) {
                $record = $err['record'] ?? $err['file'] ?? $err['index'] ?? 'Unknown';
                $this->error("[{$record}] {$err['message']}");
            }
        }

        if ($result['success']) {
            $this->newline();
            $this->success('Import completed successfully!');

            if (!$this->hasOption('dry-run')) {
                $this->newline();
                $this->comment('Remember to rebuild the search index:');
                $this->comment('  php symfony search:populate');
            }

            return 0;
        }

        return 1;
    }
}
