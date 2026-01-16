<?php

/**
 * CLI task for importing data from Preservica OPEX and PAX formats.
 *
 * Usage:
 *   php symfony preservica:import /path/to/file.opex
 *   php symfony preservica:import /path/to/package.pax --format=xip
 *   php symfony preservica:import /path/to/directory --batch
 *   php symfony preservica:import /path/to/file.opex --repository=5 --parent=123
 *   php symfony preservica:import /path/to/file.opex --dry-run
 */
class preservicaImportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('source', sfCommandArgument::REQUIRED, 'Path to OPEX file, PAX package, or directory'),
        ]);

        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Source format: opex or xip', 'opex'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_REQUIRED, 'Repository ID for imported records'),
            new sfCommandOption('parent', null, sfCommandOption::PARAMETER_REQUIRED, 'Parent information object ID'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_REQUIRED, 'Culture/language code', 'en'),
            new sfCommandOption('update', null, sfCommandOption::PARAMETER_NONE, 'Update existing records if found'),
            new sfCommandOption('match-field', null, sfCommandOption::PARAMETER_REQUIRED, 'Field to match existing records', 'legacyId'),
            new sfCommandOption('no-digital-objects', null, sfCommandOption::PARAMETER_NONE, 'Skip digital object import'),
            new sfCommandOption('no-checksums', null, sfCommandOption::PARAMETER_NONE, 'Skip checksum verification'),
            new sfCommandOption('no-hierarchy', null, sfCommandOption::PARAMETER_NONE, 'Import flat (ignore parent references)'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_NONE, 'Batch import all files in directory'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Simulate import without database changes'),
            new sfCommandOption('mapping', null, sfCommandOption::PARAMETER_REQUIRED, 'Custom mapping file (JSON)'),
        ]);

        $this->namespace = 'preservica';
        $this->name = 'import';
        $this->briefDescription = 'Import data from Preservica OPEX or PAX format';
        $this->detailedDescription = <<<EOF
The [preservica:import|INFO] task imports archival descriptions and digital objects
from Preservica OPEX XML files or PAX packages into AtoM.

Supported formats:
  - OPEX (Open Preservation Exchange) XML files
  - PAX (Preservica Archive eXchange) ZIP packages with XIP metadata

Examples:
  [php symfony preservica:import /path/to/file.opex|INFO]
  [php symfony preservica:import /path/to/package.pax --format=xip|INFO]
  [php symfony preservica:import /path/to/directory --batch|INFO]
  [php symfony preservica:import /path/to/file.opex --repository=5 --parent=123|INFO]
  [php symfony preservica:import /path/to/file.opex --dry-run|INFO]
  [php symfony preservica:import /path/to/file.opex --update --match-field=identifier|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $source = $arguments['source'];
        $format = $options['format'];

        // Validate source
        if (!file_exists($source)) {
            throw new sfException("Source not found: {$source}");
        }

        $this->logSection('preservica', 'Starting Preservica import');
        $this->logSection('preservica', "Source: {$source}");
        $this->logSection('preservica', "Format: {$format}");

        // Load framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Build options array
        $importOptions = [
            'update_existing'        => $options['update'],
            'match_field'            => $options['match-field'],
            'import_digital_objects' => !$options['no-digital-objects'],
            'verify_checksums'       => !$options['no-checksums'],
            'create_hierarchy'       => !$options['no-hierarchy'],
            'dry_run'                => $options['dry-run'],
        ];

        // Create import service
        $service = new \ahgDataMigrationPlugin\Services\PreservicaImportService($format, $importOptions);

        // Set repository
        if ($options['repository']) {
            $service->setRepository((int) $options['repository']);
            $this->logSection('preservica', "Repository ID: {$options['repository']}");
        }

        // Set parent
        if ($options['parent']) {
            $service->setParent((int) $options['parent']);
            $this->logSection('preservica', "Parent ID: {$options['parent']}");
        }

        // Set culture
        $service->setCulture($options['culture']);

        // Load custom mapping if provided
        if ($options['mapping']) {
            if (!file_exists($options['mapping'])) {
                throw new sfException("Mapping file not found: {$options['mapping']}");
            }
            $mapping = json_decode(file_get_contents($options['mapping']), true);
            if ($mapping && isset($mapping['fields'])) {
                $fieldMap = [];
                foreach ($mapping['fields'] as $field) {
                    if ($field['include']) {
                        $fieldMap[$field['source_field']] = $field['atom_field'];
                    }
                }
                $service->setFieldMapping($fieldMap);
                $this->logSection('preservica', "Using custom mapping: {$options['mapping']}");
            }
        }

        if ($options['dry-run']) {
            $this->logSection('preservica', 'DRY RUN MODE - No database changes will be made');
        }

        // Execute import based on source type
        $startTime = microtime(true);

        try {
            if (is_dir($source) || $options['batch']) {
                $result = $service->importDirectory($source);
            } elseif ($format === 'xip' || pathinfo($source, PATHINFO_EXTENSION) === 'pax') {
                $result = $service->importPaxPackage($source);
            } else {
                $result = $service->importOpexFile($source);
            }
        } catch (\Exception $e) {
            $this->logSection('preservica', 'ERROR: ' . $e->getMessage(), null, 'ERROR');
            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        // Output results
        $this->logSection('preservica', '');
        $this->logSection('preservica', '=== Import Results ===');
        $this->logSection('preservica', "Total records:  {$result['stats']['total']}");
        $this->logSection('preservica', "Imported:       {$result['stats']['imported']}");
        $this->logSection('preservica', "Updated:        {$result['stats']['updated']}");
        $this->logSection('preservica', "Skipped:        {$result['stats']['skipped']}");
        $this->logSection('preservica', "Errors:         {$result['stats']['errors']}");
        $this->logSection('preservica', "Time:           {$elapsed} seconds");

        // Output errors
        if (!empty($result['errors'])) {
            $this->logSection('preservica', '');
            $this->logSection('preservica', '=== Errors ===');
            foreach ($result['errors'] as $error) {
                $record = $error['record'] ?? $error['file'] ?? $error['index'] ?? 'Unknown';
                $this->logSection('preservica', "[{$record}] {$error['message']}", null, 'ERROR');
            }
        }

        if ($result['success']) {
            $this->logSection('preservica', '');
            $this->logSection('preservica', 'Import completed successfully!');

            if (!$options['dry-run']) {
                $this->logSection('preservica', '');
                $this->logSection('preservica', 'Remember to rebuild the search index:');
                $this->logSection('preservica', '  php symfony search:populate');
            }

            return 0;
        }

        return 1;
    }
}
