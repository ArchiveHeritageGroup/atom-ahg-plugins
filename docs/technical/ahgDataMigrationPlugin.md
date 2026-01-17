# ahgDataMigrationPlugin - Technical Documentation

**Plugin Version:** 1.2.0  
**Last Updated:** 2026-01-17  
**Framework:** AtoM AHG Framework (Laravel Query Builder + Symfony 1.x)

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Directory Structure](#2-directory-structure)
3. [Database Schema](#3-database-schema)
4. [Core Components](#4-core-components)
5. [Parsers](#5-parsers)
6. [Preservica Integration](#6-preservica-integration)
7. [Sector Definitions](#7-sector-definitions)
8. [CLI Tasks](#8-cli-tasks)
9. [Gearman Jobs](#9-gearman-jobs)
10. [Extending the Plugin](#10-extending-the-plugin)

---

## 1. Architecture Overview
```
┌─────────────────────────────────────────────────────────────────┐
│                        Web UI / CLI                             │
├─────────────────────────────────────────────────────────────────┤
│                   ahgDataMigrationActions                       │
│              (Upload, Map, Preview, Import)                     │
├─────────────────────────────────────────────────────────────────┤
│  MigrationService  │  PreservicaImportService  │  RightsImportService  │
├─────────────────────────────────────────────────────────────────┤
│  ParserFactory  │  Parsers (CSV, Excel, OPEX, PAX)              │
├─────────────────────────────────────────────────────────────────┤
│  SourceDetector  │  Mappings (Field Definitions)                │
├─────────────────────────────────────────────────────────────────┤
│  Sectors (Archives, Museum, Library, Gallery, DAM)              │
├─────────────────────────────────────────────────────────────────┤
│           Laravel Query Builder (Illuminate\Database)           │
├─────────────────────────────────────────────────────────────────┤
│                      MySQL Database                             │
└─────────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Upload** → File received, stored in `/uploads/migrations/`
2. **Detect** → SourceDetector identifies format and source system
3. **Parse** → ParserFactory creates appropriate parser
4. **Map** → Field mappings applied from `atom_data_mapping`
5. **Transform** → Transformations applied (trim, date format, etc.)
6. **Import** → Records created in AtoM database
7. **Post-process** → Slugs generated, nested set calculated, rights imported

---

## 2. Directory Structure
```
atom-ahg-plugins/ahgDataMigrationPlugin/
├── config/
│   ├── ahgDataMigrationPluginConfiguration.class.php
│   └── routing.yml
├── data/
│   ├── install.sql
│   └── mappings/
│       └── defaults/
│           ├── archivesspace_resources.json
│           ├── archivesspace_agents.json
│           ├── vernon_museum.json
│           ├── preservica_opex.json
│           └── preservica_xip.json
├── lib/
│   ├── Mappings/
│   │   └── PreservicaMapping.php
│   ├── Parsers/
│   │   ├── CsvParser.php
│   │   ├── ExcelParser.php
│   │   ├── OpexParser.php
│   │   ├── PaxParser.php
│   │   └── ParserFactory.php
│   ├── Sectors/
│   │   ├── SectorFactory.php
│   │   ├── ArchivesSector.php
│   │   ├── MuseumSector.php
│   │   ├── LibrarySector.php
│   │   ├── GallerySector.php
│   │   └── DamSector.php
│   ├── Services/
│   │   ├── MigrationService.php
│   │   ├── PreservicaImportService.php
│   │   ├── PreservicaExportService.php
│   │   └── RightsImportService.php
│   ├── SourceDetector.php
│   └── task/
│       ├── migrationImportTask.class.php
│       ├── preservicaImportTask.class.php
│       ├── preservicaExportTask.class.php
│       └── preservicaInfoTask.class.php
├── modules/
│   └── ahgDataMigration/
│       ├── actions/
│       │   └── actions.class.php
│       └── templates/
│           ├── indexSuccess.php
│           ├── mapSuccess.php
│           ├── previewSuccess.php
│           └── jobsSuccess.php
└── css/
    └── data-migration.css
```

---

## 3. Database Schema

### atom_data_mapping

Stores field mapping configurations.
```sql
CREATE TABLE IF NOT EXISTS atom_data_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    source_type VARCHAR(100),
    target_type VARCHAR(50),
    field_mappings JSON,
    transformations JSON,
    default_values JSON,
    is_system TINYINT(1) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE SET NULL
);
```

**Field Descriptions:**
- `source_type` - Source system identifier (archivesspace, vernon, preservica_opex)
- `target_type` - Target sector (ARCHIVES, MUSEUM, LIBRARY, GALLERY, DAM)
- `field_mappings` - JSON object `{"source_field": "target_field"}`
- `transformations` - JSON object `{"field": "transform_type"}`
- `default_values` - JSON object `{"field": "default_value"}`

### atom_data_migration_job

Tracks background import jobs.
```sql
CREATE TABLE IF NOT EXISTS atom_data_migration_job (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mapping_id INT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    created_records INT DEFAULT 0,
    updated_records INT DEFAULT 0,
    skipped_records INT DEFAULT 0,
    error_count INT DEFAULT 0,
    status ENUM('queued','running','completed','failed','cancelled') DEFAULT 'queued',
    error_log JSON,
    options JSON,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mapping_id) REFERENCES atom_data_mapping(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE SET NULL
);
```

### atom_data_migration_log

Audit log for individual record imports.
```sql
CREATE TABLE IF NOT EXISTS atom_data_migration_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT,
    record_id INT,
    legacy_id VARCHAR(255),
    action ENUM('create','update','skip','error'),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES atom_data_migration_job(id) ON DELETE CASCADE
);
```

---

## 4. Core Components

### SourceDetector.php

Auto-detects source system from file content.
```php
class SourceDetector
{
    public function detect(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        return match($extension) {
            'opex' => ['format' => 'opex', 'source' => 'preservica_opex'],
            'pax', 'zip' => $this->detectPaxOrZip($filePath),
            'csv' => $this->detectCsvSource($filePath),
            'xlsx', 'xls' => $this->detectExcelSource($filePath),
            'xml' => $this->detectXmlSource($filePath),
            default => ['format' => 'unknown', 'source' => 'unknown']
        };
    }
    
    protected function detectCsvSource(string $filePath): array
    {
        $headers = $this->getCsvHeaders($filePath);
        
        // ArchivesSpace detection
        if (in_array('ead_id', $headers) || in_array('resource_type', $headers)) {
            return ['format' => 'csv', 'source' => 'archivesspace'];
        }
        
        // Vernon CMS detection
        if (in_array('object_number', $headers) || in_array('accession_number', $headers)) {
            return ['format' => 'csv', 'source' => 'vernon'];
        }
        
        // Generic CSV
        return ['format' => 'csv', 'source' => 'generic'];
    }
}
```

### MigrationService.php

Main orchestration service for imports.
```php
namespace ahgDataMigrationPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class MigrationService
{
    protected $mapping;
    protected $parser;
    protected $sector;
    protected $options = [];
    protected $stats = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    public function import(string $filePath, int $mappingId, array $options = []): array
    {
        $this->loadMapping($mappingId);
        $this->initParser($filePath);
        $this->initSector();
        $this->options = $options;
        
        $records = $this->parser->parse($filePath);
        $this->stats['total'] = count($records);
        
        // Build hierarchy map for parent resolution
        $hierarchyMap = $this->buildHierarchyMap($records);
        
        foreach ($records as $record) {
            try {
                $this->processRecord($record, $hierarchyMap);
            } catch (\Exception $e) {
                $this->logError($record, $e->getMessage());
            }
        }
        
        return $this->stats;
    }

    protected function processRecord(array $data, array $hierarchyMap): void
    {
        // Apply field mappings
        $mapped = $this->applyMappings($data);
        
        // Apply transformations
        $transformed = $this->applyTransformations($mapped);
        
        // Apply defaults
        $final = $this->applyDefaults($transformed);
        
        // Resolve parent ID
        if (!empty($final['parentId'])) {
            $final['parent_id'] = $hierarchyMap[$final['parentId']] ?? null;
        }
        
        // Create or update record
        $this->saveRecord($final);
    }

    protected function saveRecord(array $data): int
    {
        // Check for existing record (update mode)
        if ($this->options['update'] ?? false) {
            $existing = $this->findExisting($data);
            if ($existing) {
                return $this->updateRecord($existing, $data);
            }
        }
        
        // Create new information_object
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Insert information_object
        DB::table('information_object')->insert([
            'id' => $objectId,
            'identifier' => $data['identifier'] ?? null,
            'level_of_description_id' => $this->resolveLevelId($data['levelOfDescription']),
            'repository_id' => $data['repository_id'] ?? $this->options['repository'] ?? null,
            'parent_id' => $data['parent_id'] ?? QubitInformationObject::ROOT_ID,
            'source_culture' => $data['culture'] ?? 'en',
        ]);
        
        // Insert i18n data
        DB::table('information_object_i18n')->insert([
            'id' => $objectId,
            'culture' => $data['culture'] ?? 'en',
            'title' => $data['title'],
            'scope_and_content' => $data['scopeAndContent'] ?? null,
            // ... other i18n fields
        ]);
        
        // Generate slug
        $this->generateSlug($objectId, $data['title']);
        
        // Calculate nested set (lft/rgt)
        $this->updateNestedSet($objectId, $data['parent_id'] ?? QubitInformationObject::ROOT_ID);
        
        // Set publication status
        $this->setPublicationStatus($objectId);
        
        $this->stats['created']++;
        return $objectId;
    }
}
```

---

## 5. Parsers

### ParserFactory.php
```php
class ParserFactory
{
    public static function create(string $format): ParserInterface
    {
        return match($format) {
            'csv' => new CsvParser(),
            'xlsx', 'xls' => new ExcelParser(),
            'opex' => new OpexParser(),
            'pax', 'xip' => new PaxParser(),
            default => throw new \InvalidArgumentException("Unknown format: $format")
        };
    }
}
```

### OpexParser.php

Parses Preservica OPEX XML format with full rights extraction.
```php
class OpexParser implements ParserInterface
{
    protected $namespaces = [
        'opex' => 'http://www.openpreservationexchange.org/opex/v1.2',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
        'mods' => 'http://www.loc.gov/mods/v3',
        'ead' => 'urn:isbn:1-931666-22-9',
    ];

    public function parse(string $filePath): array
    {
        $xml = simplexml_load_file($filePath);
        foreach ($this->namespaces as $prefix => $uri) {
            $xml->registerXPathNamespace($prefix, $uri);
        }
        
        $records = [];
        
        // Parse folders
        foreach ($xml->xpath('//opex:Folder') as $folder) {
            $records[] = $this->parseFolder($folder);
        }
        
        // Parse assets
        foreach ($xml->xpath('//opex:Asset') as $asset) {
            $records[] = $this->parseAsset($asset);
        }
        
        return $records;
    }

    protected function parseFolder(\SimpleXMLElement $folder): array
    {
        $record = [
            'legacyId' => (string)$folder['id'],
            'title' => (string)$folder->Title,
            'levelOfDescription' => 'Series',
        ];
        
        // Extract Dublin Core
        $this->extractDublinCore($folder, $record);
        
        // Extract rights
        $record['rights'] = $this->extractRights($folder);
        
        // Extract provenance/history
        $record['provenance'] = $this->extractProvenance($folder);
        
        return $record;
    }

    protected function extractRights(\SimpleXMLElement $element): array
    {
        $rights = [];
        
        // SecurityDescriptor
        $security = $element->xpath('.//opex:SecurityDescriptor');
        if (!empty($security)) {
            $rights[] = [
                'type' => 'access',
                'basis' => 'policy',
                'value' => (string)$security[0],
            ];
        }
        
        // dc:rights
        $dcRights = $element->xpath('.//dc:rights');
        foreach ($dcRights as $r) {
            $rights[] = [
                'type' => 'copyright',
                'basis' => 'copyright',
                'value' => (string)$r,
            ];
        }
        
        // dcterms:license
        $license = $element->xpath('.//dcterms:license');
        foreach ($license as $l) {
            $rights[] = [
                'type' => 'license',
                'basis' => 'license',
                'value' => (string)$l,
            ];
        }
        
        // MODS accessCondition
        $mods = $element->xpath('.//mods:accessCondition');
        foreach ($mods as $m) {
            $rights[] = [
                'type' => (string)$m['type'] ?: 'access',
                'basis' => 'statute',
                'value' => (string)$m,
            ];
        }
        
        // EAD userestrict/accessrestrict
        foreach (['userestrict', 'accessrestrict'] as $tag) {
            $ead = $element->xpath(".//ead:$tag");
            foreach ($ead as $e) {
                $rights[] = [
                    'type' => $tag === 'userestrict' ? 'use' : 'access',
                    'basis' => 'policy',
                    'value' => (string)$e->p,
                ];
            }
        }
        
        return $rights;
    }

    protected function extractProvenance(\SimpleXMLElement $element): array
    {
        $provenance = [];
        
        $history = $element->xpath('.//opex:History/opex:Event');
        foreach ($history as $event) {
            $provenance[] = [
                'date' => (string)$event->Date,
                'type' => (string)$event->Type,
                'agent' => (string)$event->Agent,
                'description' => (string)$event->Description,
            ];
        }
        
        return $provenance;
    }
}
```

---

## 6. Preservica Integration

### PreservicaImportService.php

Handles full Preservica import workflow.
```php
class PreservicaImportService
{
    protected $parser;
    protected $rightsService;
    protected $provenanceService;
    protected $stats = [];

    public function import(string $filePath, array $options = []): array
    {
        $format = $this->detectFormat($filePath);
        $this->parser = ParserFactory::create($format);
        
        $records = $this->parser->parse($filePath);
        
        foreach ($records as $record) {
            $objectId = $this->createRecord($record, $options);
            
            // Import rights
            if (!empty($record['rights'])) {
                $this->rightsService->importRights($objectId, $record['rights']);
            }
            
            // Import provenance
            if (!empty($record['provenance'])) {
                $this->provenanceService->importEvents($objectId, $record['provenance']);
            }
            
            // Handle digital objects (PAX only)
            if (!empty($record['digitalObjects'])) {
                $this->importDigitalObjects($objectId, $record['digitalObjects']);
            }
        }
        
        return $this->stats;
    }
}
```

### PreservicaExportService.php

Exports AtoM records to Preservica formats.
```php
class PreservicaExportService
{
    public function exportOpex(int $objectId, array $options = []): string
    {
        $record = $this->loadRecord($objectId);
        
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $opex = $xml->createElementNS(
            'http://www.openpreservationexchange.org/opex/v1.2',
            'opex:OPEXMetadata'
        );
        
        // Add Dublin Core
        $this->addDublinCore($opex, $record);
        
        // Add rights
        $this->addRights($opex, $record);
        
        // Add history/provenance
        $this->addHistory($opex, $record);
        
        // Include children if hierarchy requested
        if ($options['hierarchy'] ?? false) {
            $this->addChildren($opex, $objectId);
        }
        
        $xml->appendChild($opex);
        return $xml->saveXML();
    }

    public function exportPax(int $objectId, array $options = []): string
    {
        // Create temporary directory
        $tempDir = sys_get_temp_dir() . '/pax_' . uniqid();
        mkdir($tempDir);
        
        // Export metadata
        $metadata = $this->exportXip($objectId, $options);
        file_put_contents("$tempDir/metadata.xml", $metadata);
        
        // Copy digital objects
        $this->copyDigitalObjects($objectId, "$tempDir/content");
        
        // Create ZIP
        $zipPath = "/uploads/exports/preservica/{$objectId}.pax";
        $this->createZip($tempDir, $zipPath);
        
        // Cleanup
        $this->removeDirectory($tempDir);
        
        return $zipPath;
    }
}
```

---

## 7. Sector Definitions

Each sector defines its target fields.

### ArchivesSector.php
```php
class ArchivesSector implements SectorInterface
{
    public function getFields(): array
    {
        return [
            'legacyId' => ['required' => true],
            'parentId' => ['required' => false],
            'title' => ['required' => true],
            'identifier' => ['required' => false],
            'levelOfDescription' => ['required' => true],
            'repository' => ['required' => false],
            'scopeAndContent' => ['required' => false],
            'arrangement' => ['required' => false],
            'extentAndMedium' => ['required' => false],
            'dateRange' => ['required' => false],
            'creators' => ['required' => false, 'multivalue' => true],
            'subjectAccessPoints' => ['required' => false, 'multivalue' => true],
            'placeAccessPoints' => ['required' => false, 'multivalue' => true],
            'nameAccessPoints' => ['required' => false, 'multivalue' => true],
            'genreAccessPoints' => ['required' => false, 'multivalue' => true],
            'digitalObjectPath' => ['required' => false],
            'digitalObjectURI' => ['required' => false],
        ];
    }

    public function getLevelMappings(): array
    {
        return [
            'fonds' => QubitTerm::FONDS_ID,
            'collection' => QubitTerm::COLLECTION_ID,
            'series' => QubitTerm::SERIES_ID,
            'subseries' => QubitTerm::SUBSERIES_ID,
            'file' => QubitTerm::FILE_ID,
            'item' => QubitTerm::ITEM_ID,
        ];
    }
}
```

### MuseumSector.php
```php
class MuseumSector implements SectorInterface
{
    public function getFields(): array
    {
        return [
            // Core fields
            'legacyId' => ['required' => true],
            'title' => ['required' => true],
            'objectNumber' => ['required' => false],
            'accessionNumber' => ['required' => false],
            
            // CCO/CDWA fields
            'objectType' => ['required' => false],
            'materials' => ['required' => false],
            'techniques' => ['required' => false],
            'measurements' => ['required' => false],
            'inscriptions' => ['required' => false],
            'condition' => ['required' => false],
            
            // Spectrum fields
            'acquisitionMethod' => ['required' => false],
            'acquisitionDate' => ['required' => false],
            'currentLocation' => ['required' => false],
            'normalLocation' => ['required' => false],
        ];
    }
}
```

---

## 8. CLI Tasks

### migrationImportTask.class.php
```php
class migrationImportTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('file', sfCommandArgument::REQUIRED, 'File to import'),
        ]);
        
        $this->addOptions([
            new sfCommandOption('mapping', null, sfCommandOption::PARAMETER_REQUIRED, 'Mapping ID or name'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture code', 'en'),
            new sfCommandOption('update', null, sfCommandOption::PARAMETER_NONE, 'Update existing records'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without importing'),
            new sfCommandOption('list-mappings', null, sfCommandOption::PARAMETER_NONE, 'List available mappings'),
        ]);
        
        $this->namespace = 'migration';
        $this->name = 'import';
        $this->briefDescription = 'Import records using field mappings';
    }

    protected function execute($arguments = [], $options = [])
    {
        if ($options['list-mappings']) {
            return $this->listMappings();
        }
        
        $service = new MigrationService();
        $stats = $service->import(
            $arguments['file'],
            $this->resolveMapping($options['mapping']),
            [
                'repository' => $options['repository'],
                'culture' => $options['culture'],
                'update' => $options['update'],
                'dry_run' => $options['dry-run'],
            ]
        );
        
        $this->logSection('import', sprintf(
            'Complete: %d total, %d created, %d updated, %d errors',
            $stats['total'], $stats['created'], $stats['updated'], $stats['errors']
        ));
    }
}
```

### preservicaImportTask.class.php
```php
class preservicaImportTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('source', sfCommandArgument::REQUIRED, 'OPEX file or PAX package'),
        ]);
        
        $this->addOptions([
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Format: opex or xip', 'opex'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID'),
            new sfCommandOption('parent', null, sfCommandOption::PARAMETER_OPTIONAL, 'Parent object ID'),
            new sfCommandOption('update', null, sfCommandOption::PARAMETER_NONE, 'Update existing records'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without importing'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_NONE, 'Batch import directory'),
        ]);
        
        $this->namespace = 'preservica';
        $this->name = 'import';
        $this->briefDescription = 'Import from Preservica OPEX or PAX format';
    }
}
```

---

## 9. Gearman Jobs

### DataMigrationJob.class.php

Background job for large imports.
```php
class DataMigrationJob extends arBaseJob
{
    public function run($payload)
    {
        $jobId = $payload['job_id'];
        
        // Update job status
        DB::table('atom_data_migration_job')
            ->where('id', $jobId)
            ->update(['status' => 'running', 'started_at' => now()]);
        
        try {
            $job = DB::table('atom_data_migration_job')->find($jobId);
            $options = json_decode($job->options, true);
            
            $service = new MigrationService();
            $service->setProgressCallback(function($processed, $total) use ($jobId) {
                DB::table('atom_data_migration_job')
                    ->where('id', $jobId)
                    ->update(['processed_records' => $processed]);
            });
            
            $stats = $service->import($job->file_path, $job->mapping_id, $options);
            
            // Update job completion
            DB::table('atom_data_migration_job')
                ->where('id', $jobId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'created_records' => $stats['created'],
                    'updated_records' => $stats['updated'],
                    'skipped_records' => $stats['skipped'],
                    'error_count' => $stats['errors'],
                ]);
                
        } catch (\Exception $e) {
            DB::table('atom_data_migration_job')
                ->where('id', $jobId)
                ->update([
                    'status' => 'failed',
                    'error_log' => json_encode(['message' => $e->getMessage()]),
                ]);
        }
    }
}
```

---

## 10. Extending the Plugin

### Adding a New Source System

1. **Update SourceDetector.php:**
```php
protected function detectCsvSource(string $filePath): array
{
    $headers = $this->getCsvHeaders($filePath);
    
    // Add detection for new system
    if (in_array('my_system_field', $headers)) {
        return ['format' => 'csv', 'source' => 'my_system'];
    }
    // ...
}
```

2. **Create default mapping JSON:**
```json
// data/mappings/defaults/my_system.json
{
    "name": "My System Import",
    "source_type": "my_system",
    "target_type": "ARCHIVES",
    "field_mappings": {
        "my_id": "legacyId",
        "my_title": "title",
        "my_description": "scopeAndContent"
    }
}
```

3. **Register mapping in install.sql:**
```sql
INSERT INTO atom_data_mapping (name, source_type, target_type, field_mappings, is_system)
VALUES ('My System Import', 'my_system', 'ARCHIVES', '{"my_id":"legacyId",...}', 1);
```

### Adding a New Parser

1. **Create parser class:**
```php
// lib/Parsers/MyFormatParser.php
class MyFormatParser implements ParserInterface
{
    public function parse(string $filePath): array
    {
        // Parse your format
        return $records;
    }
}
```

2. **Register in ParserFactory:**
```php
return match($format) {
    // ...existing parsers
    'myformat' => new MyFormatParser(),
};
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.2.0 | 2026-01-17 | Preservica OPEX/PAX, rights import, provenance, Gearman jobs |
| 1.1.0 | 2026-01-10 | Sector-specific CSV exporters |
| 1.0.0 | 2025-12-15 | Initial release |

---

## Related Plugins

- **ahgRightsPlugin** - Rights management (used for OPEX rights import)
- **ahgProvenancePlugin** - Provenance tracking (used for OPEX history import)
- **ahgOaisPlugin** - OAIS preservation (native SIP/AIP/DIP)

---

## Support

- **Documentation:** https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/docs/
- **Issues:** https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues
- **Contact:** support@theahg.co.za

---

## 11. Digital Object Import

### How It Works

Digital objects are imported from Preservica packages using two methods:

#### Method 1: Native AtoM (Default) - `generate_derivatives: true`

Uses `QubitDigitalObject` class which automatically:
- Creates master file record
- Generates thumbnail (150px)
- Generates reference image (480px)
- Applies watermarks if configured
```php
$digitalObject = new \QubitDigitalObject();
$digitalObject->informationObjectId = $objectId;
$digitalObject->usageId = \QubitTerm::MASTER_ID;
$digitalObject->createDerivatives = true;
$digitalObject->assets[] = new \QubitAsset($filePath);
$digitalObject->save();
```

#### Method 2: Direct DB Insert - `generate_derivatives: false`

Faster for large batch imports but skips derivative generation:
- Copies master file to uploads
- Creates `digital_object` record directly
- Optional: Queue derivative generation via Gearman

### CLI Options

| Option | Description |
|--------|-------------|
| `--no-digital-objects` | Skip digital object import entirely |
| `--no-derivatives` | Import masters but skip thumbnail/reference generation |
| `--queue-derivatives` | Queue derivative generation as background job |
| `--no-checksums` | Skip SHA256 checksum verification |

### File Resolution

The importer looks for digital objects in this order:
1. `{basePath}/{filename}` - Direct path
2. `{basePath}/content/{filename}` - PAX content directory

### Checksum Verification

When `verify_checksums: true` (default):
- Extracts expected checksum from `Fixity` or `Checksum` field
- Computes SHA256 of actual file
- Fails import if mismatch

### Upload Path Structure

Files are copied to AtoM's standard structure:
```
/uploads/r/{XX}/{digitalObjectId}_{filename}
```
Where `{XX}` is first 2 characters of MD5 hash of the ID.

### Performance Recommendations

| Scenario | Recommended Options |
|----------|---------------------|
| Small import (<100 files) | Default (generate_derivatives: true) |
| Large import (100-1000) | `--no-derivatives --queue-derivatives` |
| Very large (>1000) | `--no-derivatives` then run `digitalobject:regen-derivatives` |

### Supported File Types

AtoM generates derivatives for:
- Images: JPG, PNG, GIF, TIFF, BMP
- Documents: PDF (first page thumbnail)
- Audio: MP3, WAV, OGG (waveform)
- Video: MP4, AVI, MOV (frame grab)

3D models use ahg3DModelPlugin for Blender-based thumbnails.
