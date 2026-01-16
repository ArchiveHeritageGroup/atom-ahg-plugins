<?php

namespace ahgDataMigrationPlugin\Services;

use ahgDataMigrationPlugin\Mappings\PreservicaMapping;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service for exporting AtoM data to Preservica OPEX and XIP/PAX formats.
 * 
 * Supports:
 * - OPEX XML export (single records or batch)
 * - PAX package creation (ZIP with XIP metadata + digital objects)
 * - SIP generation for Preservica ingest
 */
class PreservicaExportService
{
    /** @var string Export format: 'opex' or 'xip' */
    protected $format;
    
    /** @var array Field mapping configuration */
    protected $fieldMapping;
    
    /** @var array Export options */
    protected $options;
    
    /** @var array Export statistics */
    protected $stats = [
        'total'         => 0,
        'exported'      => 0,
        'skipped'       => 0,
        'errors'        => 0,
        'digital_objects' => 0,
    ];
    
    /** @var array Error log */
    protected $errors = [];
    
    /** @var string Culture code */
    protected $culture = 'en';
    
    /** @var string Output directory */
    protected $outputDir;
    
    /** @var \XMLWriter XML writer instance */
    protected $xml;

    /**
     * Constructor.
     *
     * @param string $format  'opex' or 'xip'
     * @param array  $options Export options
     */
    public function __construct(string $format = 'opex', array $options = [])
    {
        $this->format = $format;
        $this->options = array_merge([
            'include_digital_objects'   => true,
            'include_derivatives'       => false,
            'include_children'          => true,
            'max_depth'                 => 10,
            'security_descriptor'       => 'open',
            'generate_checksums'        => true,
            'checksum_algorithm'        => 'SHA-256',
            'create_package'            => true,  // Create ZIP for PAX
            'dublin_core_only'          => false, // Use only DC elements
        ], $options);
        
        // Load mapping based on format
        $this->fieldMapping = $format === 'opex'
            ? PreservicaMapping::getAtomToOpexMapping()
            : PreservicaMapping::getAtomToXipMapping();
    }

    /**
     * Set custom field mapping.
     */
    public function setFieldMapping(array $mapping): self
    {
        $this->fieldMapping = $mapping;
        return $this;
    }

    /**
     * Set culture/language code.
     */
    public function setCulture(string $culture): self
    {
        $this->culture = $culture;
        return $this;
    }

    /**
     * Set output directory.
     */
    public function setOutputDir(string $dir): self
    {
        $this->outputDir = $dir;
        return $this;
    }

    /**
     * Export single information object to OPEX.
     *
     * @param int $objectId Information object ID
     * @return string Path to generated OPEX file
     */
    public function exportToOpex(int $objectId): string
    {
        $record = $this->loadRecord($objectId);
        
        if (!$record) {
            throw new \InvalidArgumentException("Record not found: {$objectId}");
        }
        
        $this->ensureOutputDir();
        
        $filename = $this->generateFilename($record, 'opex');
        $filepath = $this->outputDir . '/' . $filename;
        
        $this->writeOpexFile($record, $filepath);
        
        $this->stats['exported']++;
        
        return $filepath;
    }

    /**
     * Export single information object to XIP/PAX package.
     *
     * @param int $objectId Information object ID
     * @return string Path to generated PAX package
     */
    public function exportToPax(int $objectId): string
    {
        $record = $this->loadRecord($objectId);
        
        if (!$record) {
            throw new \InvalidArgumentException("Record not found: {$objectId}");
        }
        
        $this->ensureOutputDir();
        
        // Create temp directory for package contents
        $packageName = $this->generateFilename($record, '');
        $tempDir = $this->outputDir . '/' . $packageName;
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Create content directory
        $contentDir = $tempDir . '/content';
        if (!is_dir($contentDir)) {
            mkdir($contentDir, 0755, true);
        }
        
        // Write XIP metadata
        $xipPath = $tempDir . '/metadata.xml';
        $this->writeXipFile($record, $xipPath, $contentDir);
        
        // Create ZIP package
        if ($this->options['create_package']) {
            $zipPath = $this->outputDir . '/' . $packageName . '.pax';
            $this->createZipPackage($tempDir, $zipPath);
            
            // Cleanup temp directory
            $this->removeDirectory($tempDir);
            
            $this->stats['exported']++;
            return $zipPath;
        }
        
        $this->stats['exported']++;
        return $tempDir;
    }

    /**
     * Export multiple records (batch export).
     *
     * @param array $objectIds Array of information object IDs
     * @return array Paths to generated files
     */
    public function exportBatch(array $objectIds): array
    {
        $this->stats['total'] = count($objectIds);
        $exportedFiles = [];
        
        foreach ($objectIds as $objectId) {
            try {
                if ($this->format === 'opex') {
                    $path = $this->exportToOpex($objectId);
                } else {
                    $path = $this->exportToPax($objectId);
                }
                $exportedFiles[] = $path;
            } catch (\Exception $e) {
                $this->errors[] = [
                    'object_id' => $objectId,
                    'message'   => $e->getMessage(),
                ];
                $this->stats['errors']++;
            }
        }
        
        return $exportedFiles;
    }

    /**
     * Export hierarchy starting from a record.
     *
     * @param int $objectId Root information object ID
     * @return string Path to generated package
     */
    public function exportHierarchy(int $objectId): string
    {
        $this->ensureOutputDir();
        
        // Load root and all descendants
        $records = $this->loadHierarchy($objectId);
        $this->stats['total'] = count($records);
        
        if ($this->format === 'opex') {
            return $this->exportHierarchyOpex($records);
        } else {
            return $this->exportHierarchyPax($records);
        }
    }

    /**
     * Export repository (all holdings).
     *
     * @param int $repositoryId Repository ID
     * @return string Path to generated package
     */
    public function exportRepository(int $repositoryId): string
    {
        $objectIds = DB::table('information_object')
            ->where('repository_id', $repositoryId)
            ->whereNull('parent_id')
            ->orWhere('parent_id', \QubitInformationObject::ROOT_ID)
            ->pluck('id')
            ->toArray();
        
        $allRecords = [];
        foreach ($objectIds as $objectId) {
            $records = $this->loadHierarchy($objectId);
            $allRecords = array_merge($allRecords, $records);
        }
        
        $this->stats['total'] = count($allRecords);
        
        if ($this->format === 'opex') {
            return $this->exportHierarchyOpex($allRecords);
        } else {
            return $this->exportHierarchyPax($allRecords);
        }
    }

    /**
     * Load information object with all related data.
     */
    protected function loadRecord(int $objectId): ?array
    {
        $obj = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                     ->where('i18n.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n as level', function ($join) {
                $join->on('io.level_of_description_id', '=', 'level.id')
                     ->where('level.culture', '=', $this->culture);
            })
            ->leftJoin('repository as repo', 'io.repository_id', '=', 'repo.id')
            ->leftJoin('repository_i18n as repo_i18n', function ($join) {
                $join->on('repo.id', '=', 'repo_i18n.id')
                     ->where('repo_i18n.culture', '=', $this->culture);
            })
            ->where('io.id', $objectId)
            ->select([
                'io.id',
                'io.identifier',
                'io.parent_id',
                'io.lft',
                'io.rgt',
                'i18n.title',
                'i18n.scope_and_content as scopeAndContent',
                'i18n.extent_and_medium as extentAndMedium',
                'i18n.archival_history as archivalHistory',
                'i18n.access_conditions as accessConditions',
                'i18n.reproduction_conditions as reproductionConditions',
                'i18n.physical_characteristics as physicalCharacteristics',
                'i18n.finding_aids as findingAids',
                'i18n.location_of_originals as locationOfOriginals',
                'i18n.location_of_copies as locationOfCopies',
                'i18n.related_units_of_description as relatedUnitsOfDescription',
                'i18n.rules',
                'i18n.revision_history as revisionHistory',
                'level.name as levelOfDescription',
                'repo_i18n.authorized_form_of_name as repository',
            ])
            ->first();
        
        if (!$obj) {
            return null;
        }
        
        $record = (array) $obj;
        
        // Load events (dates, creators)
        $record['events'] = $this->loadEvents($objectId);
        
        // Load access points
        $record['subjects'] = $this->loadAccessPoints($objectId, \QubitTaxonomy::SUBJECT_ID);
        $record['places'] = $this->loadAccessPoints($objectId, \QubitTaxonomy::PLACE_ID);
        $record['names'] = $this->loadAccessPoints($objectId, \QubitTaxonomy::NAME_ACCESS_POINT_ID);
        $record['genres'] = $this->loadAccessPoints($objectId, \QubitTaxonomy::GENRE_ID);
        
        // Load digital objects
        $record['digitalObjects'] = $this->loadDigitalObjects($objectId);
        
        // Load creators from events
        $record['creators'] = $this->loadCreators($objectId);
        
        return $record;
    }

    /**
     * Load events for a record.
     */
    protected function loadEvents(int $objectId): array
    {
        return DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                     ->where('ei.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n as type', function ($join) {
                $join->on('e.type_id', '=', 'type.id')
                     ->where('type.culture', '=', $this->culture);
            })
            ->leftJoin('actor_i18n as actor', function ($join) {
                $join->on('e.actor_id', '=', 'actor.id')
                     ->where('actor.culture', '=', $this->culture);
            })
            ->where('e.information_object_id', $objectId)
            ->select([
                'e.id',
                'e.start_date',
                'e.end_date',
                'ei.date as dateDisplay',
                'type.name as eventType',
                'actor.authorized_form_of_name as actorName',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Load access points by taxonomy.
     */
    protected function loadAccessPoints(int $objectId, int $taxonomyId): array
    {
        return DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                     ->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', $taxonomyId)
            ->pluck('ti.name')
            ->toArray();
    }

    /**
     * Load digital objects for a record.
     */
    protected function loadDigitalObjects(int $objectId): array
    {
        return DB::table('digital_object')
            ->where('information_object_id', $objectId)
            ->select([
                'id',
                'name',
                'path',
                'mime_type',
                'byte_size',
                'checksum',
                'checksum_type',
                'usage_id',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Load creators for a record.
     */
    protected function loadCreators(int $objectId): array
    {
        return DB::table('event as e')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('e.actor_id', '=', 'ai.id')
                     ->where('ai.culture', '=', $this->culture);
            })
            ->where('e.information_object_id', $objectId)
            ->where('e.type_id', \QubitTerm::CREATION_ID)
            ->pluck('ai.authorized_form_of_name')
            ->toArray();
    }

    /**
     * Load hierarchy of records.
     */
    protected function loadHierarchy(int $objectId, int $depth = 0): array
    {
        if ($depth > $this->options['max_depth']) {
            return [];
        }
        
        $records = [];
        
        $record = $this->loadRecord($objectId);
        if ($record) {
            $record['_depth'] = $depth;
            $records[] = $record;
            
            if ($this->options['include_children']) {
                $children = DB::table('information_object')
                    ->where('parent_id', $objectId)
                    ->orderBy('lft')
                    ->pluck('id')
                    ->toArray();
                
                foreach ($children as $childId) {
                    $childRecords = $this->loadHierarchy($childId, $depth + 1);
                    $records = array_merge($records, $childRecords);
                }
            }
        }
        
        return $records;
    }

    /**
     * Write OPEX XML file.
     */
    protected function writeOpexFile(array $record, string $filepath): void
    {
        $this->xml = new \XMLWriter();
        $this->xml->openUri($filepath);
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->xml->setIndent(true);
        $this->xml->setIndentString('  ');
        
        // Root element
        $this->xml->startElement('opex:OPEXMetadata');
        $this->xml->writeAttribute('xmlns:opex', 'http://www.openpreservationexchange.org/opex/v1.2');
        $this->xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $this->xml->writeAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
        
        // Transfer element
        $this->xml->startElement('opex:Transfer');
        
        // Source ID
        $this->xml->writeElement('opex:SourceID', $record['id']);
        
        // Fixities (for digital objects)
        if (!empty($record['digitalObjects']) && $this->options['generate_checksums']) {
            $this->xml->startElement('opex:Fixities');
            foreach ($record['digitalObjects'] as $do) {
                $this->xml->startElement('opex:Fixity');
                $this->xml->writeAttribute('type', $do->checksum_type ?? 'SHA-256');
                $this->xml->writeAttribute('value', $do->checksum ?? '');
                $this->xml->endElement(); // Fixity
            }
            $this->xml->endElement(); // Fixities
        }
        
        $this->xml->endElement(); // Transfer
        
        // Properties
        $this->xml->startElement('opex:Properties');
        
        // Title
        $this->xml->writeElement('opex:Title', $record['title'] ?? '');
        
        // Description
        if (!empty($record['scopeAndContent'])) {
            $this->xml->writeElement('opex:Description', $record['scopeAndContent']);
        }
        
        // Security Descriptor
        $this->xml->writeElement('opex:SecurityDescriptor', $this->mapSecurityDescriptor($record));
        
        // Identifiers
        $this->xml->startElement('opex:Identifiers');
        $this->xml->startElement('opex:Identifier');
        $this->xml->writeAttribute('type', 'AtoM_ID');
        $this->xml->text($record['id']);
        $this->xml->endElement();
        if (!empty($record['identifier'])) {
            $this->xml->startElement('opex:Identifier');
            $this->xml->writeAttribute('type', 'Reference');
            $this->xml->text($record['identifier']);
            $this->xml->endElement();
        }
        $this->xml->endElement(); // Identifiers
        
        $this->xml->endElement(); // Properties
        
        // Descriptive Metadata (Dublin Core)
        $this->xml->startElement('opex:DescriptiveMetadata');
        $this->writeDublinCore($record);
        $this->xml->endElement(); // DescriptiveMetadata
        
        $this->xml->endElement(); // OPEXMetadata
        
        $this->xml->endDocument();
        $this->xml->flush();
    }

    /**
     * Write Dublin Core metadata block.
     */
    protected function writeDublinCore(array $record): void
    {
        $this->xml->startElement('dc:record');
        $this->xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $this->xml->writeAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
        
        // dc:title
        if (!empty($record['title'])) {
            $this->xml->writeElement('dc:title', $record['title']);
        }
        
        // dc:identifier
        if (!empty($record['identifier'])) {
            $this->xml->writeElement('dc:identifier', $record['identifier']);
        }
        
        // dc:creator
        foreach ($record['creators'] ?? [] as $creator) {
            $this->xml->writeElement('dc:creator', $creator);
        }
        
        // dc:date (from events)
        foreach ($record['events'] ?? [] as $event) {
            if (!empty($event->dateDisplay)) {
                $this->xml->writeElement('dc:date', $event->dateDisplay);
            }
        }
        
        // dc:description
        if (!empty($record['scopeAndContent'])) {
            $this->xml->writeElement('dc:description', $record['scopeAndContent']);
        }
        
        // dc:type (level of description)
        if (!empty($record['levelOfDescription'])) {
            $this->xml->writeElement('dc:type', $record['levelOfDescription']);
        }
        
        // dc:format (extent)
        if (!empty($record['extentAndMedium'])) {
            $this->xml->writeElement('dc:format', $record['extentAndMedium']);
        }
        
        // dc:language
        $this->xml->writeElement('dc:language', $this->culture);
        
        // dc:subject
        foreach ($record['subjects'] ?? [] as $subject) {
            $this->xml->writeElement('dc:subject', $subject);
        }
        
        // dc:coverage (places)
        foreach ($record['places'] ?? [] as $place) {
            $this->xml->writeElement('dc:coverage', $place);
        }
        
        // dc:contributor (names)
        foreach ($record['names'] ?? [] as $name) {
            $this->xml->writeElement('dc:contributor', $name);
        }
        
        // dc:publisher (repository)
        if (!empty($record['repository'])) {
            $this->xml->writeElement('dc:publisher', $record['repository']);
        }
        
        // dc:rights
        if (!empty($record['reproductionConditions'])) {
            $this->xml->writeElement('dc:rights', $record['reproductionConditions']);
        }
        
        // dcterms:accessRights
        if (!empty($record['accessConditions'])) {
            $this->xml->writeElement('dcterms:accessRights', $record['accessConditions']);
        }
        
        // dcterms:provenance
        if (!empty($record['archivalHistory'])) {
            $this->xml->writeElement('dcterms:provenance', $record['archivalHistory']);
        }
        
        // dcterms:extent
        if (!empty($record['extentAndMedium'])) {
            $this->xml->writeElement('dcterms:extent', $record['extentAndMedium']);
        }
        
        $this->xml->endElement(); // dc:record
    }

    /**
     * Write XIP XML file.
     */
    protected function writeXipFile(array $record, string $filepath, string $contentDir): void
    {
        $this->xml = new \XMLWriter();
        $this->xml->openUri($filepath);
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->xml->setIndent(true);
        $this->xml->setIndentString('  ');
        
        // Root XIP element
        $this->xml->startElement('XIP');
        $this->xml->writeAttribute('xmlns', 'http://preservica.com/XIP/v6.0');
        
        // Structural Object (the archival description)
        $this->xml->startElement('StructuralObject');
        
        $this->xml->writeElement('Ref', 'SO_' . $record['id']);
        $this->xml->writeElement('Title', $record['title'] ?? 'Untitled');
        
        if (!empty($record['scopeAndContent'])) {
            $this->xml->writeElement('Description', $record['scopeAndContent']);
        }
        
        $this->xml->writeElement('SecurityTag', $this->mapSecurityDescriptor($record));
        
        // Parent reference
        if (!empty($record['parent_id']) && $record['parent_id'] != \QubitInformationObject::ROOT_ID) {
            $this->xml->writeElement('Parent', 'SO_' . $record['parent_id']);
        }
        
        $this->xml->endElement(); // StructuralObject
        
        // Content Objects (digital objects)
        foreach ($record['digitalObjects'] ?? [] as $index => $do) {
            $this->writeContentObject($do, $record, $index, $contentDir);
        }
        
        // Embedded Dublin Core
        $this->xml->startElement('Metadata');
        $this->xml->writeAttribute('schemaUri', 'http://purl.org/dc/elements/1.1/');
        $this->writeDublinCore($record);
        $this->xml->endElement(); // Metadata
        
        $this->xml->endElement(); // XIP
        
        $this->xml->endDocument();
        $this->xml->flush();
    }

    /**
     * Write Content Object (digital object) to XIP.
     */
    protected function writeContentObject($do, array $record, int $index, string $contentDir): void
    {
        $this->xml->startElement('ContentObject');
        
        $this->xml->writeElement('Ref', 'CO_' . $do->id);
        $this->xml->writeElement('Title', $do->name ?? 'Digital Object ' . ($index + 1));
        $this->xml->writeElement('SecurityTag', $this->mapSecurityDescriptor($record));
        $this->xml->writeElement('Parent', 'SO_' . $record['id']);
        
        $this->xml->endElement(); // ContentObject
        
        // Representation
        $this->xml->startElement('Representation');
        $this->xml->writeElement('Ref', 'REP_' . $do->id);
        $this->xml->writeElement('Name', 'Preservation');
        $this->xml->writeElement('Type', 'Preservation');
        $this->xml->writeElement('ContentObject', 'CO_' . $do->id);
        $this->xml->endElement(); // Representation
        
        // Generation (the actual file)
        $this->xml->startElement('Generation');
        $this->xml->writeElement('Ref', 'GEN_' . $do->id);
        $this->xml->writeElement('Original', 'true');
        $this->xml->writeElement('Active', 'true');
        
        // Bitstreams
        $this->xml->startElement('Bitstreams');
        $this->xml->startElement('Bitstream');
        $this->xml->writeElement('Filename', $do->name);
        $this->xml->writeElement('PhysicalSize', $do->byte_size ?? 0);
        
        // Fixity
        if (!empty($do->checksum)) {
            $this->xml->startElement('Fixities');
            $this->xml->startElement('Fixity');
            $this->xml->writeElement('FixityAlgorithmRef', $do->checksum_type ?? 'SHA256');
            $this->xml->writeElement('FixityValue', $do->checksum);
            $this->xml->endElement(); // Fixity
            $this->xml->endElement(); // Fixities
        }
        
        $this->xml->endElement(); // Bitstream
        $this->xml->endElement(); // Bitstreams
        
        $this->xml->writeElement('Representation', 'REP_' . $do->id);
        $this->xml->endElement(); // Generation
        
        // Copy digital object file to content directory
        if ($this->options['include_digital_objects'] && !empty($do->path)) {
            $this->copyDigitalObject($do, $contentDir);
        }
    }

    /**
     * Copy digital object to export content directory.
     */
    protected function copyDigitalObject($do, string $contentDir): void
    {
        $uploadsDir = sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads');
        $sourcePath = $uploadsDir . '/' . $do->path;
        
        if (file_exists($sourcePath)) {
            $targetPath = $contentDir . '/' . $do->name;
            copy($sourcePath, $targetPath);
            $this->stats['digital_objects']++;
        }
    }

    /**
     * Export hierarchy as OPEX.
     */
    protected function exportHierarchyOpex(array $records): string
    {
        $this->ensureOutputDir();
        
        $timestamp = date('Ymd_His');
        $exportDir = $this->outputDir . '/opex_export_' . $timestamp;
        mkdir($exportDir, 0755, true);
        
        foreach ($records as $record) {
            try {
                $filename = $this->generateFilename($record, 'opex');
                $filepath = $exportDir . '/' . $filename;
                $this->writeOpexFile($record, $filepath);
                $this->stats['exported']++;
            } catch (\Exception $e) {
                $this->errors[] = [
                    'object_id' => $record['id'],
                    'message'   => $e->getMessage(),
                ];
                $this->stats['errors']++;
            }
        }
        
        // Create manifest
        $this->writeManifest($exportDir, $records);
        
        return $exportDir;
    }

    /**
     * Export hierarchy as PAX.
     */
    protected function exportHierarchyPax(array $records): string
    {
        $this->ensureOutputDir();
        
        $timestamp = date('Ymd_His');
        $packageName = 'pax_export_' . $timestamp;
        $tempDir = $this->outputDir . '/' . $packageName;
        
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/content', 0755, true);
        
        // Write combined XIP with all records
        $xipPath = $tempDir . '/metadata.xml';
        $this->writeMultipleXip($records, $xipPath, $tempDir . '/content');
        
        // Create ZIP
        if ($this->options['create_package']) {
            $zipPath = $this->outputDir . '/' . $packageName . '.pax';
            $this->createZipPackage($tempDir, $zipPath);
            $this->removeDirectory($tempDir);
            return $zipPath;
        }
        
        return $tempDir;
    }

    /**
     * Write multiple records to single XIP file.
     */
    protected function writeMultipleXip(array $records, string $filepath, string $contentDir): void
    {
        $this->xml = new \XMLWriter();
        $this->xml->openUri($filepath);
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->xml->setIndent(true);
        $this->xml->setIndentString('  ');
        
        $this->xml->startElement('XIP');
        $this->xml->writeAttribute('xmlns', 'http://preservica.com/XIP/v6.0');
        
        // Write all Structural Objects first
        foreach ($records as $record) {
            $this->xml->startElement('StructuralObject');
            $this->xml->writeElement('Ref', 'SO_' . $record['id']);
            $this->xml->writeElement('Title', $record['title'] ?? 'Untitled');
            
            if (!empty($record['scopeAndContent'])) {
                $this->xml->writeElement('Description', $record['scopeAndContent']);
            }
            
            $this->xml->writeElement('SecurityTag', $this->mapSecurityDescriptor($record));
            
            if (!empty($record['parent_id']) && $record['parent_id'] != \QubitInformationObject::ROOT_ID) {
                $this->xml->writeElement('Parent', 'SO_' . $record['parent_id']);
            }
            
            $this->xml->endElement(); // StructuralObject
            $this->stats['exported']++;
        }
        
        // Write Content Objects
        foreach ($records as $record) {
            foreach ($record['digitalObjects'] ?? [] as $index => $do) {
                $this->writeContentObject($do, $record, $index, $contentDir);
            }
        }
        
        // Write Dublin Core for each
        foreach ($records as $record) {
            $this->xml->startElement('Metadata');
            $this->xml->writeAttribute('schemaUri', 'http://purl.org/dc/elements/1.1/');
            $this->xml->writeAttribute('ref', 'SO_' . $record['id']);
            $this->writeDublinCore($record);
            $this->xml->endElement();
        }
        
        $this->xml->endElement(); // XIP
        $this->xml->endDocument();
        $this->xml->flush();
    }

    /**
     * Write manifest file.
     */
    protected function writeManifest(string $exportDir, array $records): void
    {
        $manifest = [
            'export_date'   => date('Y-m-d H:i:s'),
            'format'        => $this->format,
            'total_records' => count($records),
            'records'       => [],
        ];
        
        foreach ($records as $record) {
            $manifest['records'][] = [
                'id'            => $record['id'],
                'identifier'    => $record['identifier'] ?? null,
                'title'         => $record['title'] ?? null,
                'level'         => $record['levelOfDescription'] ?? null,
                'digital_objects' => count($record['digitalObjects'] ?? []),
            ];
        }
        
        file_put_contents(
            $exportDir . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Map AtoM access conditions to Preservica security descriptor.
     */
    protected function mapSecurityDescriptor(array $record): string
    {
        $conditions = strtolower($record['accessConditions'] ?? '');
        
        if (strpos($conditions, 'closed') !== false) {
            return 'closed';
        }
        if (strpos($conditions, 'restricted') !== false) {
            return 'restricted';
        }
        
        return $this->options['security_descriptor'];
    }

    /**
     * Generate filename for export.
     */
    protected function generateFilename(array $record, string $extension): string
    {
        $base = $record['identifier'] ?? ('record_' . $record['id']);
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);
        
        if ($extension) {
            return $base . '.' . $extension;
        }
        
        return $base;
    }

    /**
     * Ensure output directory exists.
     */
    protected function ensureOutputDir(): void
    {
        if (!$this->outputDir) {
            $this->outputDir = sfConfig::get('sf_data_dir', '/usr/share/nginx/archive/data') . '/exports';
        }
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Create ZIP package.
     */
    protected function createZipPackage(string $sourceDir, string $zipPath): void
    {
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create ZIP file: {$zipPath}");
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
    }

    /**
     * Remove directory recursively.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * Get export statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get export errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
