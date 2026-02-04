<?php

/**
 * ahgMetadataExportPlugin Configuration
 *
 * GLAM Export Framework - Unified metadata export supporting 10+ standards
 * across Galleries, Libraries, Archives, Museums, and Digital Preservation.
 *
 * @package    ahgMetadataExportPlugin
 * @author     The Archive and Heritage Group (Pty) Ltd
 * @copyright  2024 The Archive and Heritage Group
 * @license    MIT
 */
class ahgMetadataExportPluginConfiguration extends sfPluginConfiguration
{
    /**
     * Plugin version
     */
    public const VERSION = '1.0.0';

    /**
     * Supported export formats
     */
    public const FORMATS = [
        'ead3' => [
            'name' => 'EAD3',
            'sector' => 'Archives',
            'output' => 'XML',
            'description' => 'Encoded Archival Description version 3',
        ],
        'rico' => [
            'name' => 'RIC-O',
            'sector' => 'Archives',
            'output' => 'RDF/JSON-LD',
            'description' => 'Records in Contexts Ontology',
        ],
        'lido' => [
            'name' => 'LIDO',
            'sector' => 'Museums',
            'output' => 'XML',
            'description' => 'Lightweight Information Describing Objects',
        ],
        'cidoc-crm' => [
            'name' => 'CIDOC-CRM',
            'sector' => 'Museums',
            'output' => 'RDF/JSON-LD',
            'description' => 'CIDOC Conceptual Reference Model',
        ],
        'marc21' => [
            'name' => 'MARC21',
            'sector' => 'Libraries',
            'output' => 'MARCXML',
            'description' => 'Machine-Readable Cataloging',
        ],
        'bibframe' => [
            'name' => 'BIBFRAME',
            'sector' => 'Libraries',
            'output' => 'RDF/JSON-LD',
            'description' => 'Bibliographic Framework',
        ],
        'vra-core' => [
            'name' => 'VRA Core 4',
            'sector' => 'Visual',
            'output' => 'XML',
            'description' => 'Visual Resources Association Core',
        ],
        'pbcore' => [
            'name' => 'PBCore',
            'sector' => 'Media',
            'output' => 'XML',
            'description' => 'Public Broadcasting Metadata Dictionary',
        ],
        'ebucore' => [
            'name' => 'EBUCore',
            'sector' => 'Media',
            'output' => 'XML',
            'description' => 'European Broadcasting Union Core Metadata',
        ],
        'premis' => [
            'name' => 'PREMIS',
            'sector' => 'Preservation',
            'output' => 'XML',
            'description' => 'Preservation Metadata Implementation Strategies',
        ],
        'schema-org' => [
            'name' => 'Schema.org',
            'sector' => 'Web/SEO',
            'output' => 'JSON-LD',
            'description' => 'Schema.org structured data for linked data and SEO',
        ],
    ];

    /**
     * Initialize plugin
     */
    public function initialize()
    {
        // Register autoloader for plugin classes
        $this->dispatcher->connect('context.load_factories', [$this, 'registerAutoloader']);

        // Enable routing
        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);

        // Register content negotiation filter
        $this->dispatcher->connect('context.load_factories', [$this, 'registerFilter']);
    }

    /**
     * Register content negotiation filter
     *
     * @param sfEvent $event
     */
    public function registerFilter(sfEvent $event)
    {
        $filterFile = dirname(__DIR__) . '/lib/Filters/LinkedDataContentNegotiationFilter.php';
        if (file_exists($filterFile)) {
            require_once $filterFile;
        }
    }

    /**
     * Register custom autoloader for plugin namespaced classes
     *
     * @param sfEvent $event
     */
    public function registerAutoloader(sfEvent $event)
    {
        $pluginDir = dirname(__DIR__);

        spl_autoload_register(function ($class) use ($pluginDir) {
            // Handle plugin classes
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
    }

    /**
     * Configure routing for export module
     *
     * @param sfEvent $event
     */
    public function configureRouting(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Export dashboard
        $routing->prependRoute(
            'metadata_export_index',
            new sfRoute(
                '/metadataexport',
                ['module' => 'metadataExport', 'action' => 'index']
            )
        );

        // Export preview
        $routing->prependRoute(
            'metadata_export_preview',
            new sfRoute(
                '/metadataexport/preview/:format/:slug',
                ['module' => 'metadataExport', 'action' => 'preview']
            )
        );

        // Export download
        $routing->prependRoute(
            'metadata_export_download',
            new sfRoute(
                '/metadataexport/download/:format/:slug',
                ['module' => 'metadataExport', 'action' => 'download']
            )
        );

        // Bulk export
        $routing->prependRoute(
            'metadata_export_bulk',
            new sfRoute(
                '/metadataexport/bulk/:format',
                ['module' => 'metadataExport', 'action' => 'bulk']
            )
        );

        // ===========================================
        // Linked Data / JSON-LD Routes
        // ===========================================

        // Linked data sitemap
        $routing->prependRoute(
            'linked_data_sitemap',
            new sfRoute(
                '/sitemap-ld.xml',
                ['module' => 'linkedData', 'action' => 'sitemap']
            )
        );

        // JSON-LD endpoint for records (with .jsonld extension)
        $routing->prependRoute(
            'linked_data_record_jsonld',
            new sfRoute(
                '/:slug.jsonld',
                ['module' => 'linkedData', 'action' => 'record'],
                ['slug' => '[a-z0-9-]+']
            )
        );

        // JSON-LD endpoint for repositories
        $routing->prependRoute(
            'linked_data_repository_jsonld',
            new sfRoute(
                '/repository/:slug.jsonld',
                ['module' => 'linkedData', 'action' => 'repository'],
                ['slug' => '[a-z0-9-]+']
            )
        );

        // JSON-LD endpoint for actors
        $routing->prependRoute(
            'linked_data_actor_jsonld',
            new sfRoute(
                '/actor/:slug.jsonld',
                ['module' => 'linkedData', 'action' => 'actor'],
                ['slug' => '[a-z0-9-]+']
            )
        );

        // Content negotiation endpoint
        $routing->prependRoute(
            'linked_data_negotiate',
            new sfRoute(
                '/linkeddata/:type/:slug',
                ['module' => 'linkedData', 'action' => 'negotiate'],
                ['type' => 'record|repository|actor', 'slug' => '[a-z0-9-]+']
            )
        );
    }

    /**
     * Get available formats
     *
     * @return array
     */
    public static function getFormats(): array
    {
        return self::FORMATS;
    }

    /**
     * Get format by code
     *
     * @param string $code
     *
     * @return array|null
     */
    public static function getFormat(string $code): ?array
    {
        return self::FORMATS[$code] ?? null;
    }

    /**
     * Get formats by sector
     *
     * @param string $sector
     *
     * @return array
     */
    public static function getFormatsBySector(string $sector): array
    {
        return array_filter(self::FORMATS, function ($format) use ($sector) {
            return $format['sector'] === $sector;
        });
    }
}
