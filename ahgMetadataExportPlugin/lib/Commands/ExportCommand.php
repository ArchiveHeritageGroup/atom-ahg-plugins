<?php

namespace AtomFramework\Console\Commands\Metadata;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Export archival descriptions to various metadata standards.
 */
class ExportCommand extends BaseCommand
{
    protected string $name = 'metadata:export';
    protected string $description = 'Export archival descriptions to various metadata standards';
    protected string $detailedDescription = <<<'EOF'
    Export archival descriptions to various metadata standards including:
    - EAD3 (Encoded Archival Description 3)
    - RIC-O (Records in Contexts Ontology)
    - LIDO (Lightweight Information Describing Objects)
    - MARC21 (Machine-Readable Cataloging)
    - BIBFRAME (Bibliographic Framework)
    - VRA Core 4 (Visual Resources Association)
    - PBCore (Public Broadcasting Metadata)
    - EBUCore (European Broadcasting Union Core)
    - PREMIS (Preservation Metadata Implementation Strategies)

    Examples:
      php bin/atom metadata:export --list
      php bin/atom metadata:export --format=ead3 --slug=my-fonds --output=/exports/
      php bin/atom metadata:export --format=all --slug=my-fonds --output=/exports/
      php bin/atom metadata:export --format=rico --repository=5 --output=/exports/
      php bin/atom metadata:export --format=lido --slug=my-item --preview
      php bin/atom metadata:export --format=rico --slug=my-fonds --rdf-format=turtle --output=/exports/
    EOF;

    /**
     * @var array Available export formats
     */
    private array $formats = [
        'ead3' => 'EAD3 (Archives)',
        'rico' => 'RIC-O (Archives - Linked Data)',
        'lido' => 'LIDO (Museums)',
        'marc21' => 'MARC21 (Libraries)',
        'bibframe' => 'BIBFRAME (Libraries - Linked Data)',
        'vra-core' => 'VRA Core 4 (Visual Resources)',
        'pbcore' => 'PBCore (Media)',
        'ebucore' => 'EBUCore (Media)',
        'premis' => 'PREMIS (Preservation)',
    ];

    protected function configure(): void
    {
        $this->addOption('format', null, 'Export format (ead3, rico, lido, marc21, bibframe, vra-core, pbcore, ebucore, premis, all)');
        $this->addOption('slug', null, 'Slug of the record to export');
        $this->addOption('id', null, 'ID of the record to export');
        $this->addOption('repository', null, 'Repository ID to export all records from');
        $this->addOption('output', null, 'Output directory', '/tmp');
        $this->addOption('include-digital-objects', null, 'Include digital objects in export');
        $this->addOption('include-drafts', null, 'Include draft records');
        $this->addOption('include-children', null, 'Include child records');
        $this->addOption('max-depth', null, 'Maximum hierarchy depth (0 = unlimited)', '0');
        $this->addOption('rdf-format', null, 'RDF output format (jsonld, turtle, rdfxml, ntriples)', 'jsonld');
        $this->addOption('list', null, 'List available formats');
        $this->addOption('preview', null, 'Preview output without saving to file');
    }

    protected function handle(): int
    {
        // List formats
        if ($this->hasOption('list')) {
            $this->listFormats();

            return 0;
        }

        // Validate format
        $format = $this->option('format');
        if (!$format) {
            $this->error('Please specify a format with --format option. Use --list to see available formats.');

            return 1;
        }

        // Load plugin autoloader
        $this->loadPluginAutoloader();

        // Initialize export service
        $exportService = new \AhgMetadataExport\Services\ExportService();

        // Validate format
        $format = strtolower($format);
        if ('all' !== $format && !$exportService->isFormatAvailable($format)) {
            $this->error("Unknown format: {$format}. Use --list to see available formats.");

            return 1;
        }

        // Build export options
        $exportOptions = [
            'includeDigitalObjects' => $this->hasOption('include-digital-objects'),
            'includeDrafts' => $this->hasOption('include-drafts'),
            'includeChildren' => $this->hasOption('include-children'),
            'maxDepth' => (int) $this->option('max-depth'),
            'prettyPrint' => true,
        ];

        // Add RDF format for RDF exporters
        if (in_array($format, ['rico', 'bibframe'], true)) {
            $exportOptions['outputFormat'] = $this->option('rdf-format');
        }

        // Get resources to export
        $resources = $this->getResources();

        if (empty($resources)) {
            $this->error('No resources found to export.');

            return 1;
        }

        $this->info(sprintf('Found %d resource(s) to export', count($resources)));

        // Determine formats to export
        $formats = 'all' === $format ? array_keys($this->formats) : [$format];

        // Export each resource
        $successCount = 0;
        $errorCount = 0;

        foreach ($resources as $resource) {
            $resourceTitle = $resource->title ?? $resource->slug ?? $resource->id;
            $this->info("Processing: {$resourceTitle}");

            foreach ($formats as $fmt) {
                try {
                    $content = $exportService->export($resource, $fmt, $exportOptions);

                    if ($this->hasOption('preview')) {
                        $this->newline();
                        $this->bold("--- {$fmt} ---");
                        $this->line($content);
                        $this->newline();
                    } else {
                        $filename = $exportService->generateFilename($resource, $fmt);
                        $outputPath = rtrim($this->option('output'), '/') . '/' . $filename;

                        // Ensure output directory exists
                        $outputDir = dirname($outputPath);
                        if (!is_dir($outputDir)) {
                            mkdir($outputDir, 0755, true);
                        }

                        file_put_contents($outputPath, $content);
                        $this->success("Exported {$fmt}: {$outputPath}");

                        // Log export
                        $exportService->logExport(
                            $fmt,
                            get_class($resource),
                            $resource->id ?? null,
                            $outputPath,
                            strlen($content),
                            null
                        );
                    }

                    ++$successCount;
                } catch (\Exception $e) {
                    $this->error("Failed to export {$fmt}: " . $e->getMessage());
                    ++$errorCount;
                }
            }
        }

        // Summary
        $this->newline();
        $this->info("Export complete: {$successCount} successful, {$errorCount} failed");

        return 0 === $errorCount ? 0 : 1;
    }

    /**
     * List available formats.
     */
    private function listFormats(): void
    {
        $this->newline();
        $this->bold('Available export formats:');
        $this->newline();

        foreach ($this->formats as $code => $description) {
            $this->line(sprintf('  %-12s %s', $code, $description));
        }

        $this->newline();
        $this->line('  all          Export to all formats');
        $this->newline();
    }

    /**
     * Get resources to export based on options.
     *
     * @return array
     */
    private function getResources(): array
    {
        $resources = [];

        $slug = $this->option('slug');
        $id = $this->option('id');
        $repository = $this->option('repository');

        // By slug
        if ($slug !== null) {
            $resource = \QubitInformationObject::getBySlug($slug);
            if ($resource) {
                $resources[] = $resource;
            } else {
                $this->warning("Resource not found with slug: {$slug}");
            }
        }
        // By ID
        elseif ($id !== null) {
            $resource = \QubitInformationObject::getById($id);
            if ($resource) {
                $resources[] = $resource;
            } else {
                $this->warning("Resource not found with ID: {$id}");
            }
        }
        // By repository
        elseif ($repository !== null) {
            $criteria = new \Criteria();
            $criteria->add(\QubitInformationObject::REPOSITORY_ID, $repository);
            $criteria->add(\QubitInformationObject::PARENT_ID, \QubitInformationObject::ROOT_ID);

            $results = \QubitInformationObject::get($criteria);
            foreach ($results as $resource) {
                $resources[] = $resource;
            }

            if (empty($resources)) {
                $this->warning("No top-level resources found for repository ID: {$repository}");
            }
        }

        return $resources;
    }

    /**
     * Load plugin autoloader.
     */
    private function loadPluginAutoloader(): void
    {
        $pluginDir = $this->getPluginsRoot() . '/ahgMetadataExportPlugin';

        // Check if symlinked in plugins/ directory
        $symlinkDir = $this->getAtomRoot() . '/plugins/ahgMetadataExportPlugin';
        if (is_dir($symlinkDir)) {
            $pluginDir = $symlinkDir;
        }

        // Register autoloader for plugin classes
        spl_autoload_register(function ($class) use ($pluginDir) {
            $prefix = 'AhgMetadataExport\\';
            if (0 === strpos($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $pluginDir . '/lib/' . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;

                    return true;
                }
            }

            return false;
        });

        // Load base classes
        require_once $pluginDir . '/lib/Contracts/ExporterInterface.php';
        require_once $pluginDir . '/lib/Exporters/AbstractXmlExporter.php';
        require_once $pluginDir . '/lib/Exporters/AbstractRdfExporter.php';
        require_once $pluginDir . '/lib/Services/ExportService.php';
    }
}
