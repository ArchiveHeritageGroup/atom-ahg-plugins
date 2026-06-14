<?php

class ahgMetadataExportPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'GLAM Metadata Export: Unified export framework supporting 10 metadata standards across GLAM sectors';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'metadataExport';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * Available export formats [code => [name, sector, mime, extension, resourceType]].
     * Keys must match ExportService::$exporterClasses. (Previously referenced but
     * undefined — the index UI fataled; defining it here fixes that + lists all formats.)
     */
    public static function getFormats(): array
    {
        return [
            'ead3' => ['name' => 'EAD3', 'sector' => 'Archives', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'rico' => ['name' => 'Records in Contexts (RiC-O)', 'sector' => 'Archives', 'mime' => 'application/rdf+xml', 'extension' => 'rdf', 'resourceType' => 'QubitInformationObject'],
            'mods' => ['name' => 'MODS 3.7', 'sector' => 'Libraries', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'marc21' => ['name' => 'MARC21', 'sector' => 'Libraries', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'bibframe' => ['name' => 'BIBFRAME', 'sector' => 'Libraries', 'mime' => 'application/rdf+xml', 'extension' => 'rdf', 'resourceType' => 'QubitInformationObject'],
            'mets' => ['name' => 'METS', 'sector' => 'Archives', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'eac-cpf' => ['name' => 'EAC-CPF', 'sector' => 'Archives', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitActor'],
            'lido' => ['name' => 'LIDO', 'sector' => 'Museums', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'vra-core' => ['name' => 'VRA Core', 'sector' => 'Galleries', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'pbcore' => ['name' => 'PBCore', 'sector' => 'Audiovisual', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'ebucore' => ['name' => 'EBUCore', 'sector' => 'Audiovisual', 'mime' => 'application/rdf+xml', 'extension' => 'rdf', 'resourceType' => 'QubitInformationObject'],
            'premis' => ['name' => 'PREMIS', 'sector' => 'Preservation', 'mime' => 'application/xml', 'extension' => 'xml', 'resourceType' => 'QubitInformationObject'],
            'schema-org' => ['name' => 'Schema.org', 'sector' => 'Web', 'mime' => 'application/ld+json', 'extension' => 'jsonld', 'resourceType' => 'QubitInformationObject'],
        ];
    }
}
