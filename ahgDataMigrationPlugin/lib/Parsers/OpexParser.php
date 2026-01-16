<?php

namespace ahgDataMigrationPlugin\Parsers;

/**
 * Parser for Preservica OPEX (Open Preservation Exchange) XML format.
 * 
 * OPEX is Preservica's native format for ingesting content. It uses XML
 * metadata files (.opex) alongside content files to describe folder structures
 * and metadata.
 * 
 * Structure:
 * - opex:OPEXMetadata (root)
 *   - opex:Transfer (transfer info)
 *   - opex:Properties (folder/asset properties)
 *   - opex:DescriptiveMetadata (embedded metadata - DC, EAD, custom)
 */
class OpexParser
{
    protected string $filepath;
    protected ?\DOMDocument $dom = null;
    protected ?\DOMXPath $xpath = null;
    protected array $namespaces = [
        'opex' => 'http://www.openpreservationexchange.org/opex/v1.2',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
    ];

    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        $this->load();
    }

    protected function load(): void
    {
        $this->dom = new \DOMDocument();
        $this->dom->load($this->filepath);
        
        $this->xpath = new \DOMXPath($this->dom);
        foreach ($this->namespaces as $prefix => $uri) {
            $this->xpath->registerNamespace($prefix, $uri);
        }
    }

    /**
     * Parse OPEX file and return structured data.
     */
    public function parse(): array
    {
        $result = [
            'type' => $this->getAssetType(),
            'properties' => $this->getProperties(),
            'transfer' => $this->getTransferInfo(),
            'descriptive_metadata' => $this->getDescriptiveMetadata(),
            'files' => $this->getFiles(),
            'folders' => $this->getFolders(),
            'history' => $this->getHistory(),
        ];

        return $result;
    }

    /**
     * Get asset type (folder or asset).
     */
    protected function getAssetType(): string
    {
        $folders = $this->xpath->query('//opex:Folders');
        if ($folders->length > 0) {
            return 'folder';
        }
        return 'asset';
    }

    /**
     * Get properties from OPEX.
     */
    protected function getProperties(): array
    {
        $props = [];
        
        // Title
        $title = $this->xpath->query('//opex:Properties/opex:Title');
        if ($title->length > 0) {
            $props['title'] = $title->item(0)->textContent;
        }

        // Description
        $desc = $this->xpath->query('//opex:Properties/opex:Description');
        if ($desc->length > 0) {
            $props['description'] = $desc->item(0)->textContent;
        }

        // Security Tag
        $security = $this->xpath->query('//opex:Properties/opex:SecurityDescriptor');
        if ($security->length > 0) {
            $props['security'] = $security->item(0)->textContent;
        }

        // Identifiers
        $identifiers = $this->xpath->query('//opex:Properties/opex:Identifiers/opex:Identifier');
        foreach ($identifiers as $id) {
            $type = $id->getAttribute('type') ?: 'default';
            $props['identifiers'][$type] = $id->textContent;
        }

        return $props;
    }

    /**
     * Get transfer information.
     */
    protected function getTransferInfo(): array
    {
        $transfer = [];
        
        $sourceId = $this->xpath->query('//opex:Transfer/opex:SourceID');
        if ($sourceId->length > 0) {
            $transfer['source_id'] = $sourceId->item(0)->textContent;
        }

        $manifest = $this->xpath->query('//opex:Transfer/opex:Manifest');
        if ($manifest->length > 0) {
            $transfer['manifest'] = [];
            $files = $this->xpath->query('.//opex:File', $manifest->item(0));
            foreach ($files as $file) {
                $transfer['manifest'][] = [
                    'path' => $file->textContent,
                    'type' => $file->getAttribute('type') ?: 'content',
                    'size' => $file->getAttribute('size') ?: null,
                    'fixity' => $file->getAttribute('fixity') ?: null,
                ];
            }
        }

        return $transfer;
    }

    /**
     * Get descriptive metadata (Dublin Core, EAD, or custom).
     */
    protected function getDescriptiveMetadata(): array
    {
        $metadata = [];
        
        $descMeta = $this->xpath->query('//opex:DescriptiveMetadata');
        if ($descMeta->length === 0) {
            return $metadata;
        }

        $node = $descMeta->item(0);

        // Try Dublin Core
        $dcElements = [
            'title', 'creator', 'subject', 'description', 'publisher',
            'contributor', 'date', 'type', 'format', 'identifier',
            'source', 'language', 'relation', 'coverage', 'rights'
        ];

        foreach ($dcElements as $element) {
            $nodes = $this->xpath->query(".//dc:{$element}", $node);
            if ($nodes->length > 0) {
                if ($nodes->length === 1) {
                    $metadata[$element] = $nodes->item(0)->textContent;
                } else {
                    $metadata[$element] = [];
                    foreach ($nodes as $n) {
                        $metadata[$element][] = $n->textContent;
                    }
                }
            }
        }

        // Try DC Terms extensions
        $dcTerms = [
            'created', 'modified', 'dateAccepted', 'dateCopyrighted',
            'dateSubmitted', 'extent', 'medium', 'isPartOf', 'hasPart',
            'isReferencedBy', 'references', 'spatial', 'temporal',
            'accessRights', 'license', 'provenance'
        ];

        foreach ($dcTerms as $term) {
            $nodes = $this->xpath->query(".//dcterms:{$term}", $node);
            if ($nodes->length > 0) {
                $metadata[$term] = $nodes->item(0)->textContent;
            }
        }

        return $metadata;
    }

    /**
     * Get files listed in this OPEX.
     */
    protected function getFiles(): array
    {
        $files = [];
        
        $fileNodes = $this->xpath->query('//opex:Transfer/opex:Manifest/opex:Files/opex:File');
        foreach ($fileNodes as $file) {
            $files[] = [
                'path' => $file->textContent,
                'type' => $file->getAttribute('type') ?: 'content',
                'size' => $file->getAttribute('size') ?: null,
            ];
        }

        return $files;
    }

    /**
     * Get subfolders listed in this OPEX.
     */
    protected function getFolders(): array
    {
        $folders = [];
        
        $folderNodes = $this->xpath->query('//opex:Folders/opex:Folder');
        foreach ($folderNodes as $folder) {
            $folders[] = $folder->textContent;
        }

        return $folders;
    }

    /**
     * Convert parsed OPEX data to AtoM-compatible record.
     */
    public function toAtomRecord(): array
    {
        $data = $this->parse();
        $record = [];

        // Map properties
        if (!empty($data['properties']['title'])) {
            $record['title'] = $data['properties']['title'];
        }
        if (!empty($data['properties']['description'])) {
            $record['scopeAndContent'] = $data['properties']['description'];
        }
        if (!empty($data['properties']['identifiers'])) {
            $firstId = reset($data['properties']['identifiers']);
            $record['identifier'] = $firstId;
            $record['legacyId'] = $firstId;
        }

        // Map Dublin Core metadata
        $dcMapping = [
            'title' => 'title',
            'creator' => 'creators',
            'description' => 'scopeAndContent',
            'date' => 'dateRange',
            'subject' => 'subjectAccessPoints',
            'coverage' => 'placeAccessPoints',
            'identifier' => 'identifier',
            'language' => 'language',
            'rights' => 'accessConditions',
            'format' => 'extentAndMedium',
            'type' => 'levelOfDescription',
        ];

        foreach ($dcMapping as $dc => $atom) {
            if (!empty($data['descriptive_metadata'][$dc])) {
                $value = $data['descriptive_metadata'][$dc];
                if (is_array($value)) {
                    $value = implode('|', $value);
                }
                // Don't overwrite if already set from properties
                if (empty($record[$atom])) {
                    $record[$atom] = $value;
                }
            }
        }

        // DC Terms
        if (!empty($data['descriptive_metadata']['created'])) {
            $record['dateStart'] = $data['descriptive_metadata']['created'];
        }
        if (!empty($data['descriptive_metadata']['provenance'])) {
            $record['archivalHistory'] = $data['descriptive_metadata']['provenance'];
        }
        if (!empty($data['descriptive_metadata']['accessRights'])) {
            $record['accessConditions'] = $data['descriptive_metadata']['accessRights'];
        }

        // Set level based on type
        if ($data['type'] === 'folder' && empty($record['levelOfDescription'])) {
            $record['levelOfDescription'] = 'Series';
        } elseif (empty($record['levelOfDescription'])) {
            $record['levelOfDescription'] = 'Item';
        }

        // Files for digital object
        if (!empty($data['files'])) {
            $contentFiles = array_filter($data['files'], fn($f) => $f['type'] === 'content');
            if (!empty($contentFiles)) {
                $first = reset($contentFiles);
                $record['digitalObjectPath'] = $first['path'];
            }
        }

        // Add provenance events from History
        if (!empty($data['history'])) {
            $record['_provenance_events'] = $this->mapHistoryToProvenance($data['history']);
            $record['_preservica_history'] = $data['history']; // Keep raw for reference
        }

        return $record;
    }

    /**
     * Parse a directory of OPEX files recursively.
     */
    public static function parseDirectory(string $directory): array
    {
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'opex') {
                $parser = new self($file->getPathname());
                $record = $parser->toAtomRecord();
                
                // Add relative path for hierarchy
                $relativePath = str_replace($directory, '', $file->getPath());
                $record['_path'] = trim($relativePath, '/\\');
                $record['_filename'] = $file->getFilename();
                
                $records[] = $record;
            }
        }

        return $records;
    }
    /**
     * Get audit history/provenance events from OPEX.
     * 
     * OPEX History contains audit trail events that document
     * what has happened to the item in the producing system.
     */
    protected function getHistory(): array
    {
        $history = [];

        $historyNode = $this->xpath->query('//opex:History');
        if ($historyNode->length === 0) {
            // Try without namespace
            $historyNode = $this->xpath->query('//History');
        }

        if ($historyNode->length === 0) {
            return $history;
        }

        $events = $this->xpath->query('.//opex:Event | .//Event', $historyNode->item(0));
        foreach ($events as $event) {
            $eventData = [
                'date' => $event->getAttribute('date') ?: null,
                'user' => $event->getAttribute('user') ?: null,
                'type' => null,
                'action' => null,
                'detail' => null,
                'detail_json' => null,
            ];

            // Get Type
            $type = $this->xpath->query('.//opex:Type | .//Type', $event);
            if ($type->length > 0) {
                $eventData['type'] = $type->item(0)->textContent;
            }

            // Get Action
            $action = $this->xpath->query('.//opex:Action | .//Action', $event);
            if ($action->length > 0) {
                $eventData['action'] = $action->item(0)->textContent;
            }

            // Get Detail (may contain JSON)
            $detail = $this->xpath->query('.//opex:Detail | .//Detail', $event);
            if ($detail->length > 0) {
                $detailText = $detail->item(0)->textContent;
                $eventData['detail'] = $detailText;
                
                // Try to parse as JSON
                $decoded = json_decode($detailText, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $eventData['detail_json'] = $decoded;
                }
            }

            $history[] = $eventData;
        }

        // Sort by date
        usort($history, function($a, $b) {
            return strtotime($a['date'] ?? '1900-01-01') - strtotime($b['date'] ?? '1900-01-01');
        });

        return $history;
    }

    /**
     * Map OPEX History events to AtoM Provenance events.
     * 
     * Maps Preservica event types to provenance event types:
     * - Ingest → accessioning
     * - UpdateProperties → other (metadata update)
     * - Migrate → conservation
     * - Characterise → authentication
     * - Move → transfer
     * - Delete → deaccessioning
     */
    protected function mapHistoryToProvenance(array $history): array
    {
        $provenanceEvents = [];

        $typeMapping = [
            'Ingest' => 'accessioning',
            'IngestStart' => 'accessioning',
            'IngestComplete' => 'accessioning',
            'UpdateProperties' => 'other',
            'UpdateMetadata' => 'other',
            'Migrate' => 'conservation',
            'Migration' => 'conservation',
            'Characterise' => 'authentication',
            'Characterisation' => 'authentication',
            'Move' => 'transfer',
            'Copy' => 'other',
            'Delete' => 'deaccessioning',
            'Restore' => 'recovery',
            'Export' => 'other',
            'Import' => 'accessioning',
            'AddContent' => 'accessioning',
            'RemoveContent' => 'deaccessioning',
        ];

        foreach ($history as $event) {
            $eventType = $typeMapping[$event['type']] ?? 'other';
            
            $provenanceEvent = [
                'event_type' => $eventType,
                'event_date' => $event['date'] ? date('Y-m-d', strtotime($event['date'])) : null,
                'event_date_text' => $event['date'] ?? null,
                'date_certainty' => 'exact',
                'evidence_type' => 'documentary',
                'certainty' => 'certain',
                'notes' => sprintf(
                    "Preservica %s: %s%s",
                    $event['type'] ?? 'Event',
                    $event['action'] ?? '',
                    $event['user'] ? " (by {$event['user']})" : ''
                ),
                'source_reference' => 'Preservica OPEX History',
                '_preservica_type' => $event['type'],
                '_preservica_action' => $event['action'],
                '_preservica_user' => $event['user'],
                '_preservica_detail' => $event['detail'],
            ];

            // Extract agent from user field
            if (!empty($event['user'])) {
                $provenanceEvent['to_agent_name'] = $event['user'];
                $provenanceEvent['to_agent_type'] = 'person';
            }

            $provenanceEvents[] = $provenanceEvent;
        }

        return $provenanceEvents;
    }

}
