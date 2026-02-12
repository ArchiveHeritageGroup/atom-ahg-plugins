<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for exporting data to Preservica OPEX and PAX formats.
 */
class PreservicaExportCommand extends BaseCommand
{
    protected string $name = 'preservica:export';
    protected string $description = 'Export data to Preservica OPEX or PAX format';

    protected string $detailedDescription = <<<'EOF'
    Export archival descriptions and digital objects from AtoM to Preservica
    OPEX XML files or PAX packages.

    Supported formats:
      - OPEX (Open Preservation Exchange) XML files with Dublin Core metadata
      - PAX (Preservica Archive eXchange) ZIP packages with XIP metadata

    Examples:
      php bin/atom preservica:export 123
      php bin/atom preservica:export 123 --format=xip
      php bin/atom preservica:export 123 --hierarchy
      php bin/atom preservica:export --repository=5
      php bin/atom preservica:export --ids=123,456,789 --output=/path/to/exports
      php bin/atom preservica:export 123 --no-digital-objects
    EOF;

    protected function configure(): void
    {
        $this->addArgument('id', 'Information object ID to export');
        $this->addOption('format', null, 'Export format: opex or xip', 'opex');
        $this->addOption('output', null, 'Output directory');
        $this->addOption('culture', null, 'Culture/language code', 'en');
        $this->addOption('repository', null, 'Export all from repository ID');
        $this->addOption('ids', null, 'Comma-separated list of IDs');
        $this->addOption('hierarchy', null, 'Export full hierarchy from ID');
        $this->addOption('no-digital-objects', null, 'Exclude digital objects');
        $this->addOption('include-derivatives', null, 'Include derivative files');
        $this->addOption('no-package', null, 'Do not create ZIP for PAX');
        $this->addOption('security', null, 'Default security descriptor', 'open');
        $this->addOption('max-depth', null, 'Maximum hierarchy depth', '10');
    }

    protected function handle(): int
    {
        $id = $this->argument('id');
        $format = $this->option('format') ?? 'opex';
        $repositoryOpt = $this->option('repository');
        $idsOpt = $this->option('ids');

        if (empty($id) && empty($repositoryOpt) && empty($idsOpt)) {
            $this->error('You must provide an ID, --repository, or --ids option');

            return 1;
        }

        $this->info('Starting Preservica export');
        $this->info("Format: {$format}");

        $exportOptions = [
            'include_digital_objects' => !$this->hasOption('no-digital-objects'),
            'include_derivatives' => $this->hasOption('include-derivatives'),
            'include_children' => $this->hasOption('hierarchy'),
            'max_depth' => (int) ($this->option('max-depth') ?? 10),
            'security_descriptor' => $this->option('security') ?? 'open',
            'create_package' => !$this->hasOption('no-package'),
            'generate_checksums' => true,
        ];

        $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
        $serviceFile = $pluginPath . '/lib/Services/PreservicaExportService.php';
        if (file_exists($serviceFile)) {
            require_once $serviceFile;
        }

        $service = new \ahgDataMigrationPlugin\Services\PreservicaExportService($format, $exportOptions);

        $service->setCulture($this->option('culture') ?? 'en');

        $outputDir = $this->option('output');
        if ($outputDir) {
            $service->setOutputDir($outputDir);
            $this->info("Output: {$outputDir}");
        }

        $startTime = microtime(true);
        $exportedFiles = [];

        try {
            if ($repositoryOpt) {
                $this->info("Exporting repository ID: {$repositoryOpt}");
                $path = $service->exportRepository((int) $repositoryOpt);
                $exportedFiles[] = $path;
            } elseif ($idsOpt) {
                $ids = array_map('intval', explode(',', $idsOpt));
                $this->info('Exporting ' . count($ids) . ' records');
                $exportedFiles = $service->exportBatch($ids);
            } elseif ($id) {
                $objectId = (int) $id;
                if ($this->hasOption('hierarchy')) {
                    $this->info("Exporting hierarchy from ID: {$objectId}");
                    $path = $service->exportHierarchy($objectId);
                } else {
                    $this->info("Exporting record ID: {$objectId}");
                    if ($format === 'xip') {
                        $path = $service->exportToPax($objectId);
                    } else {
                        $path = $service->exportToOpex($objectId);
                    }
                }
                $exportedFiles[] = $path;
            }
        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());

            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $stats = $service->getStats();

        $this->newline();
        $this->info('=== Export Results ===');
        $this->line("Total records:    {$stats['total']}");
        $this->line("Exported:         {$stats['exported']}");
        $this->line("Digital objects:  {$stats['digital_objects']}");
        $this->line("Errors:           {$stats['errors']}");
        $this->line("Time:             {$elapsed} seconds");

        if (!empty($exportedFiles)) {
            $this->newline();
            $this->info('=== Exported Files ===');
            foreach ($exportedFiles as $file) {
                $this->line($file);
            }
        }

        $errors = $service->getErrors();
        if (!empty($errors)) {
            $this->newline();
            $this->error('=== Errors ===');
            foreach ($errors as $err) {
                $objectId = $err['object_id'] ?? 'Unknown';
                $this->error("[ID: {$objectId}] {$err['message']}");
            }
        }

        $this->newline();
        $this->success('Export completed!');

        return $stats['errors'] > 0 ? 1 : 0;
    }
}
