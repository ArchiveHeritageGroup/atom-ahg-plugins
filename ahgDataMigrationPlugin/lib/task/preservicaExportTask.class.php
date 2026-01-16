<?php

/**
 * CLI task for exporting data to Preservica OPEX and PAX formats.
 *
 * Usage:
 *   php symfony preservica:export 123 --format=opex
 *   php symfony preservica:export 123 --format=xip --output=/path/to/exports
 *   php symfony preservica:export 123 --hierarchy
 *   php symfony preservica:export --repository=5
 *   php symfony preservica:export --ids=123,456,789
 */
class preservicaExportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('id', sfCommandArgument::OPTIONAL, 'Information object ID to export'),
        ]);

        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Export format: opex or xip', 'opex'),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_REQUIRED, 'Output directory'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_REQUIRED, 'Culture/language code', 'en'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_REQUIRED, 'Export all from repository ID'),
            new sfCommandOption('ids', null, sfCommandOption::PARAMETER_REQUIRED, 'Comma-separated list of IDs'),
            new sfCommandOption('hierarchy', null, sfCommandOption::PARAMETER_NONE, 'Export full hierarchy from ID'),
            new sfCommandOption('no-digital-objects', null, sfCommandOption::PARAMETER_NONE, 'Exclude digital objects'),
            new sfCommandOption('include-derivatives', null, sfCommandOption::PARAMETER_NONE, 'Include derivative files'),
            new sfCommandOption('no-package', null, sfCommandOption::PARAMETER_NONE, 'Do not create ZIP for PAX'),
            new sfCommandOption('security', null, sfCommandOption::PARAMETER_REQUIRED, 'Default security descriptor', 'open'),
            new sfCommandOption('max-depth', null, sfCommandOption::PARAMETER_REQUIRED, 'Maximum hierarchy depth', 10),
        ]);

        $this->namespace = 'preservica';
        $this->name = 'export';
        $this->briefDescription = 'Export data to Preservica OPEX or PAX format';
        $this->detailedDescription = <<<EOF
The [preservica:export|INFO] task exports archival descriptions and digital objects
from AtoM to Preservica OPEX XML files or PAX packages.

Supported formats:
  - OPEX (Open Preservation Exchange) XML files with Dublin Core metadata
  - PAX (Preservica Archive eXchange) ZIP packages with XIP metadata

Examples:
  [php symfony preservica:export 123|INFO]
  [php symfony preservica:export 123 --format=xip|INFO]
  [php symfony preservica:export 123 --hierarchy|INFO]
  [php symfony preservica:export --repository=5|INFO]
  [php symfony preservica:export --ids=123,456,789 --output=/path/to/exports|INFO]
  [php symfony preservica:export 123 --no-digital-objects|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $format = $options['format'];

        // Validate we have something to export
        if (empty($arguments['id']) && empty($options['repository']) && empty($options['ids'])) {
            throw new sfException('You must provide an ID, --repository, or --ids option');
        }

        $this->logSection('preservica', 'Starting Preservica export');
        $this->logSection('preservica', "Format: {$format}");

        // Load framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Build options array
        $exportOptions = [
            'include_digital_objects' => !$options['no-digital-objects'],
            'include_derivatives'     => $options['include-derivatives'],
            'include_children'        => $options['hierarchy'],
            'max_depth'               => (int) $options['max-depth'],
            'security_descriptor'     => $options['security'],
            'create_package'          => !$options['no-package'],
            'generate_checksums'      => true,
        ];

        // Create export service
        $service = new \ahgDataMigrationPlugin\Services\PreservicaExportService($format, $exportOptions);

        // Set culture
        $service->setCulture($options['culture']);

        // Set output directory
        if ($options['output']) {
            $service->setOutputDir($options['output']);
            $this->logSection('preservica', "Output: {$options['output']}");
        }

        $startTime = microtime(true);
        $exportedFiles = [];

        try {
            // Export by repository
            if ($options['repository']) {
                $this->logSection('preservica', "Exporting repository ID: {$options['repository']}");
                $path = $service->exportRepository((int) $options['repository']);
                $exportedFiles[] = $path;
            }
            // Export multiple IDs
            elseif ($options['ids']) {
                $ids = array_map('intval', explode(',', $options['ids']));
                $this->logSection('preservica', 'Exporting ' . count($ids) . ' records');
                $exportedFiles = $service->exportBatch($ids);
            }
            // Export single or hierarchy
            elseif ($arguments['id']) {
                $id = (int) $arguments['id'];
                if ($options['hierarchy']) {
                    $this->logSection('preservica', "Exporting hierarchy from ID: {$id}");
                    $path = $service->exportHierarchy($id);
                } else {
                    $this->logSection('preservica', "Exporting record ID: {$id}");
                    if ($format === 'xip') {
                        $path = $service->exportToPax($id);
                    } else {
                        $path = $service->exportToOpex($id);
                    }
                }
                $exportedFiles[] = $path;
            }
        } catch (\Exception $e) {
            $this->logSection('preservica', 'ERROR: ' . $e->getMessage(), null, 'ERROR');
            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $stats = $service->getStats();

        // Output results
        $this->logSection('preservica', '');
        $this->logSection('preservica', '=== Export Results ===');
        $this->logSection('preservica', "Total records:    {$stats['total']}");
        $this->logSection('preservica', "Exported:         {$stats['exported']}");
        $this->logSection('preservica', "Digital objects:  {$stats['digital_objects']}");
        $this->logSection('preservica', "Errors:           {$stats['errors']}");
        $this->logSection('preservica', "Time:             {$elapsed} seconds");

        // Output file locations
        if (!empty($exportedFiles)) {
            $this->logSection('preservica', '');
            $this->logSection('preservica', '=== Exported Files ===');
            foreach ($exportedFiles as $file) {
                $this->logSection('preservica', $file);
            }
        }

        // Output errors
        $errors = $service->getErrors();
        if (!empty($errors)) {
            $this->logSection('preservica', '');
            $this->logSection('preservica', '=== Errors ===');
            foreach ($errors as $error) {
                $objectId = $error['object_id'] ?? 'Unknown';
                $this->logSection('preservica', "[ID: {$objectId}] {$error['message']}", null, 'ERROR');
            }
        }

        $this->logSection('preservica', '');
        $this->logSection('preservica', 'Export completed!');

        return $stats['errors'] > 0 ? 1 : 0;
    }
}
