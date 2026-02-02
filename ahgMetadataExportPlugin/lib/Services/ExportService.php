<?php

/**
 * ExportService - Factory and orchestration for metadata exports
 *
 * Provides a unified interface for accessing all exporters and
 * coordinating exports across multiple formats.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Services
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Services;

use AhgMetadataExport\Contracts\ExporterInterface;
use AhgMetadataExport\Exporters\BibframeExporter;
use AhgMetadataExport\Exporters\Ead3Exporter;
use AhgMetadataExport\Exporters\EbucoreExporter;
use AhgMetadataExport\Exporters\LidoExporter;
use AhgMetadataExport\Exporters\Marc21Exporter;
use AhgMetadataExport\Exporters\PbcoreExporter;
use AhgMetadataExport\Exporters\PremisExporter;
use AhgMetadataExport\Exporters\RicoExporter;
use AhgMetadataExport\Exporters\VraCoreExporter;
use Illuminate\Database\Capsule\Manager as DB;

class ExportService
{
    /**
     * @var array Registered exporters [format => class]
     */
    protected $exporterClasses = [
        'ead3' => Ead3Exporter::class,
        'rico' => RicoExporter::class,
        'lido' => LidoExporter::class,
        'marc21' => Marc21Exporter::class,
        'bibframe' => BibframeExporter::class,
        'vra-core' => VraCoreExporter::class,
        'pbcore' => PbcoreExporter::class,
        'ebucore' => EbucoreExporter::class,
        'premis' => PremisExporter::class,
    ];

    /**
     * @var array Cached exporter instances
     */
    protected $exporters = [];

    /**
     * @var string Base URI for identifiers
     */
    protected $baseUri;

    /**
     * Constructor
     *
     * @param string|null $baseUri
     */
    public function __construct(?string $baseUri = null)
    {
        $this->baseUri = $baseUri ?? \sfConfig::get('app_siteBaseUrl', 'https://example.org');
    }

    /**
     * Get exporter for specified format
     *
     * @param string $format Format code (e.g., 'ead3', 'rico', 'lido')
     *
     * @return ExporterInterface
     *
     * @throws \InvalidArgumentException If format is not supported
     */
    public function getExporter(string $format): ExporterInterface
    {
        $format = strtolower($format);

        // Return cached instance if available
        if (isset($this->exporters[$format])) {
            return $this->exporters[$format];
        }

        // Check if format is supported
        if (!isset($this->exporterClasses[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        // Load the exporter class file
        $this->loadExporterClass($format);

        // Create and cache instance
        $class = $this->exporterClasses[$format];
        $this->exporters[$format] = new $class($this->baseUri);

        return $this->exporters[$format];
    }

    /**
     * Load exporter class file
     *
     * @param string $format
     */
    protected function loadExporterClass(string $format): void
    {
        $classMap = [
            'ead3' => 'Ead3Exporter',
            'rico' => 'RicoExporter',
            'lido' => 'LidoExporter',
            'marc21' => 'Marc21Exporter',
            'bibframe' => 'BibframeExporter',
            'vra-core' => 'VraCoreExporter',
            'pbcore' => 'PbcoreExporter',
            'ebucore' => 'EbucoreExporter',
            'premis' => 'PremisExporter',
        ];

        if (isset($classMap[$format])) {
            $file = dirname(__DIR__).'/Exporters/'.$classMap[$format].'.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Get all available formats
     *
     * @return array
     */
    public function getAvailableFormats(): array
    {
        return \ahgMetadataExportPluginConfiguration::getFormats();
    }

    /**
     * Get available formats for a specific sector
     *
     * @param string $sector
     *
     * @return array
     */
    public function getFormatsForSector(string $sector): array
    {
        return \ahgMetadataExportPluginConfiguration::getFormatsBySector($sector);
    }

    /**
     * Check if a format is available
     *
     * @param string $format
     *
     * @return bool
     */
    public function isFormatAvailable(string $format): bool
    {
        return isset($this->exporterClasses[strtolower($format)]);
    }

    /**
     * Export resource to specified format
     *
     * @param mixed  $resource The resource to export
     * @param string $format   Export format
     * @param array  $options  Export options
     *
     * @return string Exported content
     */
    public function export($resource, string $format, array $options = []): string
    {
        $exporter = $this->getExporter($format);

        return $exporter->export($resource, $options);
    }

    /**
     * Export resource to file
     *
     * @param mixed  $resource The resource to export
     * @param string $format   Export format
     * @param string $path     Output file path
     * @param array  $options  Export options
     *
     * @return bool True on success
     */
    public function exportToFile($resource, string $format, string $path, array $options = []): bool
    {
        $exporter = $this->getExporter($format);

        return $exporter->exportToFile($resource, $path, $options);
    }

    /**
     * Export resource to multiple formats
     *
     * @param mixed  $resource The resource to export
     * @param array  $formats  Array of format codes
     * @param array  $options  Export options
     *
     * @return array [format => content]
     */
    public function exportMultiple($resource, array $formats, array $options = []): array
    {
        $results = [];

        foreach ($formats as $format) {
            try {
                $results[$format] = $this->export($resource, $format, $options);
            } catch (\Exception $e) {
                $results[$format] = [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Export to all available formats
     *
     * @param mixed $resource
     * @param array $options
     *
     * @return array [format => content]
     */
    public function exportAll($resource, array $options = []): array
    {
        return $this->exportMultiple($resource, array_keys($this->exporterClasses), $options);
    }

    /**
     * Batch export multiple resources
     *
     * @param array  $resources
     * @param string $format
     * @param array  $options
     *
     * @return \Generator
     */
    public function exportBatch(array $resources, string $format, array $options = []): \Generator
    {
        $exporter = $this->getExporter($format);

        return $exporter->exportBatch($resources, $options);
    }

    /**
     * Generate filename for export
     *
     * @param mixed  $resource
     * @param string $format
     *
     * @return string
     */
    public function generateFilename($resource, string $format): string
    {
        $exporter = $this->getExporter($format);

        $slug = $resource->slug ?? $resource->identifier ?? $resource->id ?? 'export';
        $extension = $exporter->getFileExtension();

        // Sanitize slug for filename
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $slug);

        return sprintf('%s_%s_%s.%s', $format, $slug, date('Ymd_His'), $extension);
    }

    /**
     * Get MIME type for format
     *
     * @param string $format
     *
     * @return string
     */
    public function getMimeType(string $format): string
    {
        $exporter = $this->getExporter($format);

        return $exporter->getMimeType();
    }

    /**
     * Get file extension for format
     *
     * @param string $format
     *
     * @return string
     */
    public function getFileExtension(string $format): string
    {
        $exporter = $this->getExporter($format);

        return $exporter->getFileExtension();
    }

    /**
     * Log export to database
     *
     * @param string   $format
     * @param string   $resourceType
     * @param int|null $resourceId
     * @param string   $filePath
     * @param int      $fileSize
     * @param int|null $userId
     */
    public function logExport(
        string $format,
        string $resourceType,
        ?int $resourceId,
        string $filePath = '',
        int $fileSize = 0,
        ?int $userId = null
    ): void {
        try {
            // Check if table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'metadata_export_log'");
            if (empty($tableExists)) {
                return;
            }

            DB::table('metadata_export_log')->insert([
                'format_code' => $format,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Log failure silently - don't interrupt export
            error_log('Failed to log export: '.$e->getMessage());
        }
    }

    /**
     * Get export statistics
     *
     * @param string|null $format Filter by format
     * @param int         $days   Number of days to include
     *
     * @return array
     */
    public function getExportStats(?string $format = null, int $days = 30): array
    {
        try {
            $query = DB::table('metadata_export_log')
                ->where('created_at', '>=', date('Y-m-d', strtotime("-{$days} days")));

            if ($format) {
                $query->where('format_code', $format);
            }

            $total = $query->count();

            $byFormat = DB::table('metadata_export_log')
                ->select('format_code', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', date('Y-m-d', strtotime("-{$days} days")))
                ->groupBy('format_code')
                ->get();

            return [
                'total' => $total,
                'by_format' => $byFormat,
                'days' => $days,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'by_format' => [],
                'days' => $days,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Register custom exporter
     *
     * @param string $format       Format code
     * @param string $exporterClass Fully qualified class name
     */
    public function registerExporter(string $format, string $exporterClass): void
    {
        $this->exporterClasses[strtolower($format)] = $exporterClass;

        // Clear cached instance if exists
        unset($this->exporters[strtolower($format)]);
    }

    /**
     * Get formats supported by resource type
     *
     * @param string $resourceType
     *
     * @return array
     */
    public function getFormatsForResourceType(string $resourceType): array
    {
        $supported = [];

        foreach ($this->exporterClasses as $format => $class) {
            try {
                $exporter = $this->getExporter($format);
                if ($exporter->supportsResourceType($resourceType)) {
                    $supported[] = $format;
                }
            } catch (\Exception $e) {
                // Skip if exporter cannot be loaded
            }
        }

        return $supported;
    }

    /**
     * Get service instance (singleton pattern)
     *
     * @return self
     */
    public static function getInstance(): self
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }
}
