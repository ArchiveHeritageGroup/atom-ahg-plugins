<?php

namespace ahgDataMigrationPlugin\Parsers;

/**
 * Enhanced Parser for Preservica OPEX (Open Preservation Exchange) XML format.
 *
 * OPEX is Preservica's native format for ingesting/exporting content.
 * 
 * Structure (all sections optional):
 * - opex:OPEXMetadata (root)
 *   - opex:Properties (title, description, security, identifiers)
 *   - opex:Transfer (sourceID, fixities, manifest)
 *   - opex:DescriptiveMetadata (multiple allowed - DC, MODS, EAD, custom)
 *   - opex:History (audit events - exported only, not ingested by Preservica)
 *   - opex:Relationships (links between objects)
 *
 * @see https://developers.preservica.com/documentation/open-preservation-exchange-opex
 */
class OpexParser
{
    protected string $filepath;
    protected ?\DOMDocument $dom = null;
    protected ?\DOMXPath $xpath = null;
    
    // Standard namespaces
    protected array $namespaces = [
        'opex' => 'http://www.openpreservationexchange.org/opex/v1.0',
        'opex12' => 'http://www.openpreservationexchange.org/opex/v1.2',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
        'oai_dc' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
        'mods' => 'http://www.loc.gov/mods/v3',
        'ead' => 'urn:isbn:1-931666-22-9',
        'premis' => 'http://www.loc.gov/premis/v3',
    ];

    // Rights basis mapping
    const BASIS_COPYRIGHT = 170;
    const BASIS_LICENSE = 171;
    const BASIS_STATUTE = 172;
    const BASIS_POLICY = 173;
    const BASIS_DONOR = 218;
    
    // Copyright status mapping
    const STATUS_UNDER_COPYRIGHT = 350;
    const STATUS_PUBLIC_DOMAIN = 351;
    const STATUS_UNKNOWN = 352;

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
        
        // Register standard namespaces
        foreach ($this->namespaces as $prefix => $uri) {
            $this->xpath->registerNamespace($prefix, $uri);
        }
        
        // Auto-detect and register namespaces from document
        $this->detectNamespaces();
    }

    /**
     * Auto-detect namespaces from document root.
     */
    protected function detectNamespaces(): void
    {
        $root = $this->dom->documentElement;
        if (!$root) return;

        // Check for OPEX version
        $ns = $root->namespaceURI;
        if ($ns && strpos($ns, 'opex') !== false) {
            $this->xpath->registerNamespace('opex', $ns);
        }

        // Scan for other namespaces in document
        $xpath = new \DOMXPath($this->dom);
        $nodes = $xpath->query('//*');
        foreach ($nodes as $node) {
            if ($node->namespaceURI && $node->prefix) {
                if (!isset($this->namespaces[$node->prefix])) {
                    $this->namespaces[$node->prefix] = $node->namespaceURI;
                    $this->xpath->registerNamespace($node->prefix, $node->namespaceURI);
                }
            }
        }
    }

    /**
     * Parse OPEX file and return structured data.
     */
    public function parse(): array
    {
        return [
            'type' => $this->getAssetType(),
            'properties' => $this->getProperties(),
            'transfer' => $this->getTransferInfo(),
            'descriptive_metadata' => $this->getAllDescriptiveMetadata(),
            'files' => $this->getFiles(),
            'folders' => $this->getFolders(),
            'history' => $this->getHistory(),
            'relationships' => $this->getRelationships(),
            'rights' => $this->extractRights(),
        ];
    }

    /**
     * Get asset type (folder or asset).
     */
    protected function getAssetType(): string
    {
        $folders = $this->xpath->query('//opex:Folders | //opex12:Folders | //Folders');
        return ($folders->length > 0) ? 'folder' : 'asset';
    }

    /**
     * Get properties from OPEX.
     */
    protected function getProperties(): array
    {
        $props = [];

        // Title
        $title = $this->queryFirst('//opex:Properties/opex:Title | //Properties/Title');
        if ($title) $props['title'] = $title;

        // Description
        $desc = $this->queryFirst('//opex:Properties/opex:Description | //Properties/Description');
        if ($desc) $props['description'] = $desc;

        // Security Descriptor (critical for rights)
        $security = $this->queryFirst('//opex:Properties/opex:SecurityDescriptor | //Properties/SecurityDescriptor');
        if ($security) $props['securityDescriptor'] = $security;

        // Identifiers
        $identifiers = $this->xpath->query('//opex:Properties/opex:Identifiers/opex:Identifier | //Properties/Identifiers/Identifier');
        if ($identifiers->length > 0) {
            $props['identifiers'] = [];
            foreach ($identifiers as $id) {
                $type = $id->getAttribute('type') ?: 'default';
                $props['identifiers'][$type] = $id->textContent;
            }
        }

        return $props;
    }

    /**
     * Get transfer information including fixities.
     */
    protected function getTransferInfo(): array
    {
        $transfer = [];

        // SourceID
        $sourceId = $this->queryFirst('//opex:Transfer/opex:SourceID | //Transfer/SourceID');
        if ($sourceId) $transfer['sourceId'] = $sourceId;

        // Original Filename
        $origFilename = $this->queryFirst('//opex:Transfer/opex:OriginalFilename | //Transfer/OriginalFilename');
        if ($origFilename) $transfer['originalFilename'] = $origFilename;

        // Fixities
        $fixities = $this->xpath->query('//opex:Transfer/opex:Fixities/opex:Fixity | //Transfer/Fixities/Fixity');
        if ($fixities->length > 0) {
            $transfer['fixities'] = [];
            foreach ($fixities as $fixity) {
                $transfer['fixities'][] = [
                    'type' => $fixity->getAttribute('type'),
                    'value' => $fixity->getAttribute('value'),
                    'path' => $fixity->getAttribute('path') ?: null, // For PAX
                ];
            }
        }

        // Manifest
        $manifest = $this->xpath->query('//opex:Transfer/opex:Manifest | //Transfer/Manifest');
        if ($manifest->length > 0) {
            $transfer['manifest'] = [
                'folders' => [],
                'files' => [],
            ];
            
            // Folders
            $folders = $this->xpath->query('.//opex:Folder | .//Folder', $manifest->item(0));
            foreach ($folders as $folder) {
                $transfer['manifest']['folders'][] = $folder->textContent;
            }
            
            // Files
            $files = $this->xpath->query('.//opex:File | .//File', $manifest->item(0));
            foreach ($files as $file) {
                $transfer['manifest']['files'][] = [
                    'path' => $file->textContent,
                    'type' => $file->getAttribute('type') ?: 'content',
                    'size' => $file->getAttribute('size') ?: null,
                ];
            }
        }

        return $transfer;
    }

    /**
     * Get ALL descriptive metadata blocks (multiple allowed).
     * Each block can contain different schemas (DC, MODS, EAD, custom).
     */
    protected function getAllDescriptiveMetadata(): array
    {
        $allMetadata = [
            'dublin_core' => [],
            'mods' => [],
            'ead' => [],
            'custom' => [],
            'merged' => [], // Merged view for easy access
        ];

        $descMetaNodes = $this->xpath->query('//opex:DescriptiveMetadata | //DescriptiveMetadata');
        
        foreach ($descMetaNodes as $node) {
            // Try Dublin Core (oai_dc:dc or direct dc: elements)
            $dc = $this->parseDublinCore($node);
            if (!empty($dc)) {
                $allMetadata['dublin_core'][] = $dc;
                $allMetadata['merged'] = array_merge($allMetadata['merged'], $dc);
            }

            // Try MODS
            $mods = $this->parseMods($node);
            if (!empty($mods)) {
                $allMetadata['mods'][] = $mods;
                $allMetadata['merged'] = array_merge($allMetadata['merged'], $mods);
            }

            // Try EAD
            $ead = $this->parseEad($node);
            if (!empty($ead)) {
                $allMetadata['ead'][] = $ead;
                $allMetadata['merged'] = array_merge($allMetadata['merged'], $ead);
            }

            // Custom/unknown schemas - extract all child elements
            $custom = $this->parseCustomMetadata($node);
            if (!empty($custom)) {
                $allMetadata['custom'][] = $custom;
            }
        }

        return $allMetadata;
    }

    /**
     * Parse Dublin Core elements.
     */
    protected function parseDublinCore(\DOMNode $context): array
    {
        $dc = [];
        
        $elements = [
            'title', 'creator', 'subject', 'description', 'publisher',
            'contributor', 'date', 'type', 'format', 'identifier',
            'source', 'language', 'relation', 'coverage', 'rights'
        ];

        foreach ($elements as $element) {
            $nodes = $this->xpath->query(".//dc:{$element}", $context);
            if ($nodes->length > 0) {
                $dc["dc:{$element}"] = ($nodes->length === 1) 
                    ? $nodes->item(0)->textContent 
                    : $this->nodesToArray($nodes);
            }
        }

        // DC Terms extensions
        $terms = [
            'created', 'modified', 'dateAccepted', 'dateCopyrighted',
            'dateSubmitted', 'extent', 'medium', 'isPartOf', 'hasPart',
            'isReferencedBy', 'references', 'spatial', 'temporal',
            'accessRights', 'license', 'provenance', 'rightsHolder'
        ];

        foreach ($terms as $term) {
            $nodes = $this->xpath->query(".//dcterms:{$term}", $context);
            if ($nodes->length > 0) {
                $dc["dcterms:{$term}"] = $nodes->item(0)->textContent;
            }
        }

        return $dc;
    }

    /**
     * Parse MODS elements.
     */
    protected function parseMods(\DOMNode $context): array
    {
        $mods = [];
        
        $modsRoot = $this->xpath->query('.//mods:mods', $context);
        if ($modsRoot->length === 0) return $mods;

        $root = $modsRoot->item(0);

        // Title
        $title = $this->xpath->query('.//mods:titleInfo/mods:title', $root);
        if ($title->length > 0) $mods['mods:title'] = $title->item(0)->textContent;

        // Name/Creator
        $names = $this->xpath->query('.//mods:name', $root);
        foreach ($names as $name) {
            $namePart = $this->xpath->query('.//mods:namePart', $name);
            $role = $this->xpath->query('.//mods:role/mods:roleTerm', $name);
            if ($namePart->length > 0) {
                $roleText = ($role->length > 0) ? $role->item(0)->textContent : 'creator';
                $mods["mods:name:{$roleText}"][] = $namePart->item(0)->textContent;
            }
        }

        // Subject
        $subjects = $this->xpath->query('.//mods:subject/mods:topic', $root);
        if ($subjects->length > 0) {
            $mods['mods:subject'] = $this->nodesToArray($subjects);
        }

        // Origin info (dates, publisher)
        $dateCreated = $this->xpath->query('.//mods:originInfo/mods:dateCreated', $root);
        if ($dateCreated->length > 0) $mods['mods:dateCreated'] = $dateCreated->item(0)->textContent;

        $publisher = $this->xpath->query('.//mods:originInfo/mods:publisher', $root);
        if ($publisher->length > 0) $mods['mods:publisher'] = $publisher->item(0)->textContent;

        // Access condition (rights)
        $accessCondition = $this->xpath->query('.//mods:accessCondition', $root);
        if ($accessCondition->length > 0) {
            $mods['mods:accessCondition'] = $accessCondition->item(0)->textContent;
            $mods['mods:accessCondition:type'] = $accessCondition->item(0)->getAttribute('type');
        }

        // Abstract
        $abstract = $this->xpath->query('.//mods:abstract', $root);
        if ($abstract->length > 0) $mods['mods:abstract'] = $abstract->item(0)->textContent;

        // Language
        $language = $this->xpath->query('.//mods:language/mods:languageTerm', $root);
        if ($language->length > 0) $mods['mods:language'] = $language->item(0)->textContent;

        return $mods;
    }

    /**
     * Parse EAD elements.
     */
    protected function parseEad(\DOMNode $context): array
    {
        $ead = [];
        
        // Check for EAD root or component
        $eadRoot = $this->xpath->query('.//ead:ead | .//ead:c | .//ead:archdesc', $context);
        if ($eadRoot->length === 0) return $ead;

        $root = $eadRoot->item(0);

        // Unit title
        $unittitle = $this->xpath->query('.//ead:unittitle', $root);
        if ($unittitle->length > 0) $ead['ead:unittitle'] = $unittitle->item(0)->textContent;

        // Unit ID
        $unitid = $this->xpath->query('.//ead:unitid', $root);
        if ($unitid->length > 0) $ead['ead:unitid'] = $unitid->item(0)->textContent;

        // Unit date
        $unitdate = $this->xpath->query('.//ead:unitdate', $root);
        if ($unitdate->length > 0) $ead['ead:unitdate'] = $unitdate->item(0)->textContent;

        // Scope and content
        $scopecontent = $this->xpath->query('.//ead:scopecontent/ead:p', $root);
        if ($scopecontent->length > 0) $ead['ead:scopecontent'] = $this->nodesToArray($scopecontent, "\n");

        // Access restrictions (rights)
        $accessrestrict = $this->xpath->query('.//ead:accessrestrict/ead:p', $root);
        if ($accessrestrict->length > 0) $ead['ead:accessrestrict'] = $this->nodesToArray($accessrestrict, "\n");

        // Use restrictions (reproduction rights)
        $userestrict = $this->xpath->query('.//ead:userestrict/ead:p', $root);
        if ($userestrict->length > 0) $ead['ead:userestrict'] = $this->nodesToArray($userestrict, "\n");

        // Origination (creator)
        $origination = $this->xpath->query('.//ead:origination/*', $root);
        if ($origination->length > 0) $ead['ead:origination'] = $origination->item(0)->textContent;

        // Physical description
        $physdesc = $this->xpath->query('.//ead:physdesc', $root);
        if ($physdesc->length > 0) $ead['ead:physdesc'] = $physdesc->item(0)->textContent;

        // Custodial history
        $custodhist = $this->xpath->query('.//ead:custodhist/ead:p', $root);
        if ($custodhist->length > 0) $ead['ead:custodhist'] = $this->nodesToArray($custodhist, "\n");

        return $ead;
    }

    /**
     * Parse custom/unknown metadata schemas.
     */
    protected function parseCustomMetadata(\DOMNode $context): array
    {
        $custom = [];
        
        foreach ($context->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            
            // Skip known schemas
            $ns = $child->namespaceURI ?? '';
            if (strpos($ns, 'dc') !== false || 
                strpos($ns, 'mods') !== false || 
                strpos($ns, 'ead') !== false) {
                continue;
            }

            // Extract element name with namespace prefix
            $name = $child->prefix ? "{$child->prefix}:{$child->localName}" : $child->localName;
            
            // Get text content or recurse
            if ($child->childNodes->length === 1 && $child->firstChild->nodeType === XML_TEXT_NODE) {
                $custom[$name] = trim($child->textContent);
            } else {
                $custom[$name] = $this->parseCustomMetadata($child);
            }
        }

        return $custom;
    }

    /**
     * Extract rights information from all sources.
     */
    protected function extractRights(): array
    {
        $rights = [
            'securityDescriptor' => null,
            'accessLevel' => null,
            'dcRights' => null,
            'dcAccessRights' => null,
            'dcLicense' => null,
            'dcRightsHolder' => null,
            'modsAccessCondition' => null,
            'eadAccessRestrict' => null,
            'eadUseRestrict' => null,
            'parsedBasis' => null,
            'parsedStatus' => null,
        ];

        // From Properties
        $security = $this->queryFirst('//opex:Properties/opex:SecurityDescriptor | //Properties/SecurityDescriptor');
        if ($security) {
            $rights['securityDescriptor'] = $security;
            $rights['accessLevel'] = $this->mapSecurityDescriptor($security);
        }

        // From Dublin Core
        $dcRights = $this->queryFirst('//dc:rights');
        if ($dcRights) {
            $rights['dcRights'] = $dcRights;
            $parsed = $this->parseRightsStatement($dcRights);
            $rights['parsedBasis'] = $parsed['basis_id'] ?? null;
            $rights['parsedStatus'] = $parsed['copyright_status_id'] ?? null;
        }

        $dcAccessRights = $this->queryFirst('//dcterms:accessRights');
        if ($dcAccessRights) $rights['dcAccessRights'] = $dcAccessRights;

        $dcLicense = $this->queryFirst('//dcterms:license');
        if ($dcLicense) $rights['dcLicense'] = $dcLicense;

        $dcRightsHolder = $this->queryFirst('//dcterms:rightsHolder');
        if ($dcRightsHolder) $rights['dcRightsHolder'] = $dcRightsHolder;

        // From MODS
        $modsAccess = $this->queryFirst('//mods:accessCondition');
        if ($modsAccess) $rights['modsAccessCondition'] = $modsAccess;

        // From EAD
        $eadAccess = $this->queryFirst('//ead:accessrestrict//ead:p');
        if ($eadAccess) $rights['eadAccessRestrict'] = $eadAccess;

        $eadUse = $this->queryFirst('//ead:userestrict//ead:p');
        if ($eadUse) $rights['eadUseRestrict'] = $eadUse;

        return $rights;
    }

    /**
     * Map SecurityDescriptor to access level and acts.
     */
    protected function mapSecurityDescriptor(string $descriptor): array
    {
        $descriptorLower = strtolower(trim($descriptor));
        
        $mapping = [
            'open' => ['level' => 'open', 'restriction' => 0, 'acts' => ['display', 'disseminate', 'discover']],
            'public' => ['level' => 'open', 'restriction' => 0, 'acts' => ['display', 'disseminate', 'discover']],
            'closed' => ['level' => 'closed', 'restriction' => 1, 'acts' => []],
            'private' => ['level' => 'closed', 'restriction' => 1, 'acts' => []],
            'restricted' => ['level' => 'restricted', 'restriction' => 1, 'acts' => ['discover']],
            'internal' => ['level' => 'restricted', 'restriction' => 1, 'acts' => ['discover']],
            'confidential' => ['level' => 'restricted', 'restriction' => 1, 'acts' => []],
        ];

        return $mapping[$descriptorLower] ?? ['level' => 'unknown', 'restriction' => 0, 'acts' => ['display']];
    }

    /**
     * Parse a rights statement string to determine basis and status.
     */
    protected function parseRightsStatement(string $statement): array
    {
        $rights = [];
        $statementLower = strtolower(trim($statement));
        
        // Public Domain
        if (strpos($statementLower, 'public domain') !== false ||
            preg_match('/\bcc0\b/i', $statement) ||
            strpos($statementLower, 'no known copyright') !== false) {
            return [
                'basis_id' => self::BASIS_COPYRIGHT,
                'copyright_status_id' => self::STATUS_PUBLIC_DOMAIN,
            ];
        }
        
        // Creative Commons
        if (preg_match('/cc[- ]?(by|nc|nd|sa)/i', $statement) ||
            strpos($statementLower, 'creative commons') !== false) {
            return ['basis_id' => self::BASIS_LICENSE];
        }
        
        // Copyright
        if (strpos($statementLower, 'copyright') !== false ||
            strpos($statement, '©') !== false ||
            preg_match('/\(c\)\s*\d{4}/i', $statement) ||
            strpos($statementLower, 'all rights reserved') !== false) {
            return [
                'basis_id' => self::BASIS_COPYRIGHT,
                'copyright_status_id' => self::STATUS_UNDER_COPYRIGHT,
            ];
        }
        
        // License
        if (strpos($statementLower, 'licen') !== false) {
            return ['basis_id' => self::BASIS_LICENSE];
        }
        
        // Default
        return [
            'basis_id' => self::BASIS_COPYRIGHT,
            'copyright_status_id' => self::STATUS_UNKNOWN,
        ];
    }

    /**
     * Get relationships from OPEX.
     */
    protected function getRelationships(): array
    {
        $relationships = [];

        $relNodes = $this->xpath->query('//opex:Relationships/opex:Relationship | //Relationships/Relationship');
        foreach ($relNodes as $rel) {
            $type = $this->xpath->query('.//opex:Type | .//Type', $rel);
            $object = $this->xpath->query('.//opex:Object | .//Object', $rel);
            
            if ($type->length > 0 && $object->length > 0) {
                $relationships[] = [
                    'type' => $type->item(0)->textContent,
                    'object' => $object->item(0)->textContent,
                ];
            }
        }

        return $relationships;
    }

    /**
     * Get files listed in manifest.
     */
    protected function getFiles(): array
    {
        $files = [];

        $fileNodes = $this->xpath->query('//opex:Transfer/opex:Manifest/opex:Files/opex:File | //Transfer/Manifest/Files/File');
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
     * Get subfolders listed in manifest.
     */
    protected function getFolders(): array
    {
        $folders = [];

        $folderNodes = $this->xpath->query('//opex:Transfer/opex:Manifest/opex:Folders/opex:Folder | //Transfer/Manifest/Folders/Folder');
        foreach ($folderNodes as $folder) {
            $folders[] = $folder->textContent;
        }

        return $folders;
    }

    /**
     * Get audit history/provenance events from OPEX.
     */
    protected function getHistory(): array
    {
        $history = [];

        $historyNode = $this->xpath->query('//opex:History | //History');
        if ($historyNode->length === 0) return $history;

        $events = $this->xpath->query('.//opex:Event | .//Event', $historyNode->item(0));
        foreach ($events as $event) {
            $eventData = [
                'date' => $event->getAttribute('date') ?: null,
                'user' => $event->getAttribute('user') ?: null,
                'type' => $this->queryFirst('.//opex:Type | .//Type', $event),
                'action' => $this->queryFirst('.//opex:Action | .//Action', $event),
                'detail' => null,
                'detail_json' => null,
            ];

            $detail = $this->xpath->query('.//opex:Detail | .//Detail', $event);
            if ($detail->length > 0) {
                $detailText = $detail->item(0)->textContent;
                $eventData['detail'] = $detailText;
                $decoded = json_decode($detailText, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $eventData['detail_json'] = $decoded;
                }
            }

            $history[] = $eventData;
        }

        usort($history, fn($a, $b) => strtotime($a['date'] ?? '1900-01-01') - strtotime($b['date'] ?? '1900-01-01'));

        return $history;
    }

    /**
     * Convert parsed OPEX data to AtoM-compatible record.
     */
    public function toAtomRecord(): array
    {
        $data = $this->parse();
        $record = [];

        // Properties
        if (!empty($data['properties']['title'])) {
            $record['title'] = $data['properties']['title'];
        }
        if (!empty($data['properties']['description'])) {
            $record['scopeAndContent'] = $data['properties']['description'];
        }
        if (!empty($data['properties']['identifiers'])) {
            $firstId = reset($data['properties']['identifiers']);
            $record['identifier'] = $firstId;
            $record['legacyId'] = $data['transfer']['sourceId'] ?? $firstId;
            $record['alternativeIdentifiers'] = $data['properties']['identifiers'];
        }

        // Merged descriptive metadata
        $meta = $data['descriptive_metadata']['merged'] ?? [];
        
        // Map Dublin Core
        $dcMapping = [
            'dc:title' => 'title',
            'dc:creator' => 'creators',
            'dc:description' => 'scopeAndContent',
            'dc:date' => 'eventDates',
            'dc:subject' => 'subjectAccessPoints',
            'dc:coverage' => 'placeAccessPoints',
            'dc:identifier' => 'identifier',
            'dc:language' => 'language',
            'dc:format' => 'extentAndMedium',
            'dc:type' => 'levelOfDescription',
            'dc:publisher' => 'repository',
            'dc:source' => 'locationOfOriginals',
            'dc:relation' => 'relatedUnitsOfDescription',
            'dcterms:created' => 'eventDates',
            'dcterms:provenance' => 'archivalHistory',
            'dcterms:extent' => 'extentAndMedium',
            'dcterms:spatial' => 'placeAccessPoints',
            'dcterms:temporal' => 'eventDates',
        ];

        foreach ($dcMapping as $dc => $atom) {
            if (!empty($meta[$dc]) && empty($record[$atom])) {
                $value = is_array($meta[$dc]) ? implode('|', $meta[$dc]) : $meta[$dc];
                $record[$atom] = $value;
            }
        }

        // Map MODS
        $modsMapping = [
            'mods:title' => 'title',
            'mods:abstract' => 'scopeAndContent',
            'mods:dateCreated' => 'eventDates',
            'mods:publisher' => 'repository',
            'mods:language' => 'language',
            'mods:subject' => 'subjectAccessPoints',
        ];

        foreach ($modsMapping as $mods => $atom) {
            if (!empty($meta[$mods]) && empty($record[$atom])) {
                $value = is_array($meta[$mods]) ? implode('|', $meta[$mods]) : $meta[$mods];
                $record[$atom] = $value;
            }
        }

        // Map EAD
        $eadMapping = [
            'ead:unittitle' => 'title',
            'ead:unitid' => 'identifier',
            'ead:unitdate' => 'eventDates',
            'ead:scopecontent' => 'scopeAndContent',
            'ead:origination' => 'creators',
            'ead:physdesc' => 'extentAndMedium',
            'ead:custodhist' => 'archivalHistory',
        ];

        foreach ($eadMapping as $ead => $atom) {
            if (!empty($meta[$ead]) && empty($record[$atom])) {
                $value = is_array($meta[$ead]) ? implode("\n", $meta[$ead]) : $meta[$ead];
                $record[$atom] = $value;
            }
        }

        // Rights - text fields
        $rights = $data['rights'];
        if (!empty($rights['dcAccessRights'])) {
            $record['accessConditions'] = $rights['dcAccessRights'];
        } elseif (!empty($rights['eadAccessRestrict'])) {
            $record['accessConditions'] = $rights['eadAccessRestrict'];
        } elseif (!empty($rights['modsAccessCondition'])) {
            $record['accessConditions'] = $rights['modsAccessCondition'];
        } elseif (!empty($rights['securityDescriptor'])) {
            $record['accessConditions'] = ucfirst($rights['securityDescriptor']);
        }

        if (!empty($rights['dcRights'])) {
            $record['reproductionConditions'] = $rights['dcRights'];
        } elseif (!empty($rights['dcLicense'])) {
            $record['reproductionConditions'] = $rights['dcLicense'];
        } elseif (!empty($rights['eadUseRestrict'])) {
            $record['reproductionConditions'] = $rights['eadUseRestrict'];
        }

        // Rights - structured data for rights tables
        $record['_rights'] = [
            'securityDescriptor' => $rights['securityDescriptor'],
            'accessLevel' => $rights['accessLevel'],
            'dcRights' => $rights['dcRights'],
            'dcLicense' => $rights['dcLicense'],
            'dcRightsHolder' => $rights['dcRightsHolder'],
            'parsedBasis' => $rights['parsedBasis'],
            'parsedStatus' => $rights['parsedStatus'],
        ];

        // Level of description
        if ($data['type'] === 'folder' && empty($record['levelOfDescription'])) {
            $record['levelOfDescription'] = 'Series';
        } elseif (empty($record['levelOfDescription'])) {
            $record['levelOfDescription'] = 'Item';
        }

        // Digital object path
        if (!empty($data['files'])) {
            $contentFiles = array_filter($data['files'], fn($f) => $f['type'] === 'content');
            if (!empty($contentFiles)) {
                $first = reset($contentFiles);
                $record['digitalObjectPath'] = $first['path'];
            }
        }

        // Transfer info
        if (!empty($data['transfer']['sourceId'])) {
            $record['legacyId'] = $data['transfer']['sourceId'];
        }
        if (!empty($data['transfer']['fixities'])) {
            $record['_fixities'] = $data['transfer']['fixities'];
        }

        // History/Provenance
        if (!empty($data['history'])) {
            $record['_provenance_events'] = $this->mapHistoryToProvenance($data['history']);
            $record['_preservica_history'] = $data['history'];
        }

        // Relationships
        if (!empty($data['relationships'])) {
            $record['_relationships'] = $data['relationships'];
        }


        // =====================================================
        // AHG EXTENDED FIELDS - Flattened for CSV export
        // =====================================================
        
        // Digital Object fields (for CSV mapping)
        if (!empty($data['files'])) {
            $contentFiles = array_filter($data['files'], fn($f) => $f['type'] === 'content');
            if (!empty($contentFiles)) {
                $first = reset($contentFiles);
                $record['Filename'] = basename($first['path'] ?? '');
                $record['digitalObjectPath'] = $first['path'] ?? '';
                $record['digitalObjectChecksum'] = $first['fixity'] ?? '';
                $record['digitalObjectMimeType'] = $first['format'] ?? '';
                $record['digitalObjectSize'] = $first['size'] ?? '';
            }
            // All files as pipe-separated list
            $allFiles = array_map(fn($f) => basename($f['path'] ?? ''), $data['files']);
            $record['allFilenames'] = implode('|', array_filter($allFiles));
        }

        // Security Classification (AHG)
        if (!empty($rights['securityDescriptor'])) {
            $record['ahgSecurityClassification'] = $rights['securityDescriptor'];
        }
        if (!empty($rights['accessLevel'])) {
            $record['ahgAccessLevel'] = $rights['accessLevel'];
        }

        // Rights - Flattened text for CSV (AHG Extended Rights)
        $rightsText = [];
        if (!empty($rights['dcRights'])) {
            $rightsText[] = "Rights: " . $rights['dcRights'];
        }
        if (!empty($rights['dcLicense'])) {
            $rightsText[] = "License: " . $rights['dcLicense'];
        }
        if (!empty($rights['dcRightsHolder'])) {
            $rightsText[] = "Rights Holder: " . $rights['dcRightsHolder'];
        }
        if (!empty($rights['dcAccessRights'])) {
            $rightsText[] = "Access: " . $rights['dcAccessRights'];
        }
        if (!empty($rightsText)) {
            $record['ahgRightsStatement'] = implode(' | ', $rightsText);
        }

        // Rights basis and status (for ahgRightsPlugin)
        if (!empty($rights['parsedBasis'])) {
            $record['ahgRightsBasis'] = $rights['parsedBasis'];
        }
        if (!empty($rights['parsedStatus'])) {
            $record['ahgCopyrightStatus'] = $rights['parsedStatus'];
        }

        // Provenance - Flattened text for CSV (AHG Provenance)
        if (!empty($data['history'])) {
            $provenanceText = [];
            foreach ($data['history'] as $event) {
                $eventStr = ($event['date'] ?? 'Unknown date') . ': ';
                $eventStr .= ($event['type'] ?? 'Event');
                if (!empty($event['action'])) {
                    $eventStr .= ' - ' . $event['action'];
                }
                if (!empty($event['user'])) {
                    $eventStr .= ' (by ' . $event['user'] . ')';
                }
                $provenanceText[] = $eventStr;
            }
            $record['ahgProvenanceHistory'] = implode(' || ', $provenanceText);
            $record['ahgProvenanceEventCount'] = count($data['history']);
        }

        // Provenance - First and last events (useful for date ranges)
        if (!empty($data['history'])) {
            $sorted = $data['history'];
            usort($sorted, fn($a, $b) => strtotime($a['date'] ?? '1900-01-01') - strtotime($b['date'] ?? '1900-01-01'));
            $first = reset($sorted);
            $last = end($sorted);
            if ($first) {
                $record['ahgProvenanceFirstDate'] = $first['date'] ?? '';
                $record['ahgProvenanceFirstEvent'] = $first['type'] ?? '';
            }
            if ($last && $last !== $first) {
                $record['ahgProvenanceLastDate'] = $last['date'] ?? '';
                $record['ahgProvenanceLastEvent'] = $last['type'] ?? '';
            }
        }

        // Relationships - Flattened
        if (!empty($data['relationships'])) {
            $relText = [];
            foreach ($data['relationships'] as $rel) {
                $relStr = ($rel['type'] ?? 'related') . ': ' . ($rel['target'] ?? 'unknown');
                $relText[] = $relStr;
            }
            $record['ahgRelationships'] = implode(' | ', $relText);
        }

        // Transfer/Ingest metadata
        if (!empty($data['transfer'])) {
            $record['Transfer_SourceID'] = $data['transfer']['sourceId'] ?? '';
            $record['Transfer_Manifest'] = isset($data['transfer']['manifest']) 
                ? json_encode($data['transfer']['manifest']) 
                : '';
            if (!empty($data['transfer']['fixities'])) {
                $fixityText = [];
                foreach ($data['transfer']['fixities'] as $fix) {
                    $fixityText[] = ($fix['algorithm'] ?? 'unknown') . ':' . ($fix['value'] ?? '');
                }
                $record['Transfer_Fixities'] = implode(' | ', $fixityText);
            }
        }

        return $record;
    }

    /**
     * Map OPEX History events to AtoM Provenance events.
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
            'Modified' => 'other',
            'Migrate' => 'conservation',
            'Migration' => 'conservation',
            'Characterise' => 'authentication',
            'Characterisation' => 'authentication',
            'Re-Characterise' => 'authentication',
            'Move' => 'transfer',
            'Copy' => 'other',
            'Link' => 'other',
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

            if (!empty($event['user'])) {
                $provenanceEvent['to_agent_name'] = $event['user'];
                $provenanceEvent['to_agent_type'] = 'person';
            }

            $provenanceEvents[] = $provenanceEvent;
        }

        return $provenanceEvents;
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
                try {
                    $parser = new self($file->getPathname());
                    $record = $parser->toAtomRecord();
                    $record['_path'] = trim(str_replace($directory, '', $file->getPath()), '/\\');
                    $record['_filename'] = $file->getFilename();
                    $records[] = $record;
                } catch (\Exception $e) {
                    error_log("Failed to parse OPEX: {$file->getPathname()} - " . $e->getMessage());
                }
            }
        }

        return $records;
    }

    // Helper methods

    protected function queryFirst(string $xpath, ?\DOMNode $context = null): ?string
    {
        $nodes = $this->xpath->query($xpath, $context);
        return ($nodes->length > 0) ? trim($nodes->item(0)->textContent) : null;
    }

    protected function nodesToArray(\DOMNodeList $nodes, string $separator = '|'): string|array
    {
        $values = [];
        foreach ($nodes as $node) {
            $values[] = trim($node->textContent);
        }
        return count($values) === 1 ? $values[0] : $values;
    }
}
