<?php

/**
 * Metadata Export CLI Task
 *
 * Exports archival descriptions to various metadata standards.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage task
 * @author     The Archive and Heritage Group (Pty) Ltd
 */
class metadataExportTask extends arBaseTask
{
    /**
     * @var array Available export formats
     */
    protected $formats = [
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

    /**
     * Configure the task
     */
    protected function configure()
    {
        $this->addArguments([
        ]);

        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Export format (ead3, rico, lido, marc21, bibframe, vra-core, pbcore, ebucore, premis, all)', null),
            new sfCommandOption('slug', null, sfCommandOption::PARAMETER_OPTIONAL, 'Slug of the record to export', null),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'ID of the record to export', null),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID to export all records from', null),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_REQUIRED, 'Output directory', '/tmp'),
            new sfCommandOption('include-digital-objects', null, sfCommandOption::PARAMETER_NONE, 'Include digital objects in export'),
            new sfCommandOption('include-drafts', null, sfCommandOption::PARAMETER_NONE, 'Include draft records'),
            new sfCommandOption('include-children', null, sfCommandOption::PARAMETER_NONE, 'Include child records'),
            new sfCommandOption('max-depth', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum hierarchy depth (0 = unlimited)', 0),
            new sfCommandOption('rdf-format', null, sfCommandOption::PARAMETER_OPTIONAL, 'RDF output format (jsonld, turtle, rdfxml, ntriples)', 'jsonld'),
            new sfCommandOption('list', null, sfCommandOption::PARAMETER_NONE, 'List available formats'),
            new sfCommandOption('preview', null, sfCommandOption::PARAMETER_NONE, 'Preview output without saving to file'),
        ]);

        $this->namespace = 'metadata';
        $this->name = 'export';
        $this->briefDescription = 'Export archival descriptions to various metadata standards';
        $this->detailedDescription = <<<'EOF'
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

  List available formats:
    php symfony metadata:export --list

  Export single record to EAD3:
    php symfony metadata:export --format=ead3 --slug=my-fonds --output=/exports/

  Export record to multiple formats:
    php symfony metadata:export --format=all --slug=my-fonds --output=/exports/

  Export all records from a repository:
    php symfony metadata:export --format=rico --repository=5 --output=/exports/

  Preview without saving:
    php symfony metadata:export --format=lido --slug=my-item --preview

  Export RIC-O as Turtle:
    php symfony metadata:export --format=rico --slug=my-fonds --rdf-format=turtle --output=/exports/
EOF;
    }

    /**
     * Execute the task
     *
     * @param array $arguments
     * @param array $options
     *
     * @return int
     */
    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        // List formats
        if ($options['list']) {
            $this->listFormats();

            return 0;
        }

        // Validate format
        if (!$options['format']) {
            $this->logSection('error', 'Please specify a format with --format option. Use --list to see available formats.');

            return 1;
        }

        // Load plugin autoloader
        $this->loadPluginAutoloader();

        // Initialize export service
        $exportService = new \AhgMetadataExport\Services\ExportService();

        // Validate format
        $format = strtolower($options['format']);
        if ('all' !== $format && !$exportService->isFormatAvailable($format)) {
            $this->logSection('error', "Unknown format: {$format}. Use --list to see available formats.");

            return 1;
        }

        // Build export options
        $exportOptions = [
            'includeDigitalObjects' => $options['include-digital-objects'],
            'includeDrafts' => $options['include-drafts'],
            'includeChildren' => $options['include-children'],
            'maxDepth' => (int) $options['max-depth'],
            'prettyPrint' => true,
        ];

        // Add RDF format for RDF exporters
        if (in_array($format, ['rico', 'bibframe'], true)) {
            $exportOptions['outputFormat'] = $options['rdf-format'];
        }

        // Get resources to export
        $resources = $this->getResources($options);

        if (empty($resources)) {
            $this->logSection('error', 'No resources found to export.');

            return 1;
        }

        $this->logSection('info', sprintf('Found %d resource(s) to export', count($resources)));

        // Determine formats to export
        $formats = 'all' === $format ? array_keys($this->formats) : [$format];

        // Export each resource
        $successCount = 0;
        $errorCount = 0;

        foreach ($resources as $resource) {
            $resourceTitle = $resource->title ?? $resource->slug ?? $resource->id;
            $this->logSection('export', "Processing: {$resourceTitle}");

            foreach ($formats as $fmt) {
                try {
                    $content = $exportService->export($resource, $fmt, $exportOptions);

                    if ($options['preview']) {
                        $this->log("\n--- {$fmt} ---\n");
                        $this->log($content);
                        $this->log("\n");
                    } else {
                        $filename = $exportService->generateFilename($resource, $fmt);
                        $outputPath = rtrim($options['output'], '/').'/'.$filename;

                        // Ensure output directory exists
                        $outputDir = dirname($outputPath);
                        if (!is_dir($outputDir)) {
                            mkdir($outputDir, 0755, true);
                        }

                        file_put_contents($outputPath, $content);
                        $this->logSection('success', "Exported {$fmt}: {$outputPath}");

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
                    $this->logSection('error', "Failed to export {$fmt}: ".$e->getMessage());
                    ++$errorCount;
                }
            }
        }

        // Summary
        $this->log('');
        $this->logSection('summary', "Export complete: {$successCount} successful, {$errorCount} failed");

        return 0 === $errorCount ? 0 : 1;
    }

    /**
     * List available formats
     */
    protected function listFormats(): void
    {
        $this->log('');
        $this->logSection('info', 'Available export formats:');
        $this->log('');

        foreach ($this->formats as $code => $description) {
            $this->log(sprintf('  %-12s %s', $code, $description));
        }

        $this->log('');
        $this->log('  all          Export to all formats');
        $this->log('');
    }

    /**
     * Get resources to export based on options
     *
     * @param array $options
     *
     * @return array
     */
    protected function getResources(array $options): array
    {
        $resources = [];

        // By slug
        if ($options['slug']) {
            $resource = QubitInformationObject::getBySlug($options['slug']);
            if ($resource) {
                $resources[] = $resource;
            } else {
                $this->logSection('warning', "Resource not found with slug: {$options['slug']}");
            }
        }
        // By ID
        elseif ($options['id']) {
            $resource = QubitInformationObject::getById($options['id']);
            if ($resource) {
                $resources[] = $resource;
            } else {
                $this->logSection('warning', "Resource not found with ID: {$options['id']}");
            }
        }
        // By repository
        elseif ($options['repository']) {
            $criteria = new Criteria();
            $criteria->add(QubitInformationObject::REPOSITORY_ID, $options['repository']);
            $criteria->add(QubitInformationObject::PARENT_ID, QubitInformationObject::ROOT_ID);

            $results = QubitInformationObject::get($criteria);
            foreach ($results as $resource) {
                $resources[] = $resource;
            }

            if (empty($resources)) {
                $this->logSection('warning', "No top-level resources found for repository ID: {$options['repository']}");
            }
        }

        return $resources;
    }

    /**
     * Load plugin autoloader
     */
    protected function loadPluginAutoloader(): void
    {
        $pluginDir = sfConfig::get('sf_plugins_dir').'/ahgMetadataExportPlugin';

        // Register autoloader for plugin classes
        spl_autoload_register(function ($class) use ($pluginDir) {
            $prefix = 'AhgMetadataExport\\';
            if (0 === strpos($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $pluginDir.'/lib/'.str_replace('\\', '/', $relativeClass).'.php';
                if (file_exists($file)) {
                    require_once $file;

                    return true;
                }
            }

            return false;
        });

        // Load base classes
        require_once $pluginDir.'/lib/Contracts/ExporterInterface.php';
        require_once $pluginDir.'/lib/Exporters/AbstractXmlExporter.php';
        require_once $pluginDir.'/lib/Exporters/AbstractRdfExporter.php';
        require_once $pluginDir.'/lib/Services/ExportService.php';
    }
}
