<?php

namespace ahgDataMigrationPlugin\Parsers;

/**
 * Parser for Preservica PAX (Preservica Archive eXchange) format.
 * 
 * PAX is Preservica's package format for exporting content. It's essentially
 * a ZIP archive containing:
 * - metadata/ folder with XIP (XML Information Package) files
 * - content/ folder with actual files
 * 
 * XIP Structure:
 * - XIP (root)
 *   - InformationObject (intellectual entity)
 *   - Representation (manifestation)
 *   - ContentObject (file container)
 *   - Generation (version)
 *   - Bitstream (actual file reference)
 */
class PaxParser
{
    protected string $filepath;
    protected ?string $extractPath = null;
    protected ?\DOMDocument $xipDom = null;
    protected ?\DOMXPath $xpath = null;
    protected array $namespaces = [
        'xip' => 'http://preservica.com/XIP/v6.0',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
    ];

    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * Extract PAX archive and load XIP metadata.
     */
    public function extract(): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->filepath) !== true) {
            throw new \Exception("Cannot open PAX archive: {$this->filepath}");
        }

        // Create temp directory
        $this->extractPath = sys_get_temp_dir() . '/pax_' . uniqid();
        mkdir($this->extractPath, 0755, true);

        $zip->extractTo($this->extractPath);
        $zip->close();

        // Find and load XIP file
        $xipFiles = glob($this->extractPath . '/metadata/*.xml');
        if (empty($xipFiles)) {
            $xipFiles = glob($this->extractPath . '/*.xml');
        }

        if (!empty($xipFiles)) {
            $this->loadXip($xipFiles[0]);
            return true;
        }

        return false;
    }

    /**
     * Load XIP XML file.
     */
    protected function loadXip(string $xipPath): void
    {
        $this->xipDom = new \DOMDocument();
        $this->xipDom->load($xipPath);

        $this->xpath = new \DOMXPath($this->xipDom);
        
        // Auto-detect namespace
        $root = $this->xipDom->documentElement;
        if ($root && $root->namespaceURI) {
            $this->namespaces['xip'] = $root->namespaceURI;
        }

        foreach ($this->namespaces as $prefix => $uri) {
            $this->xpath->registerNamespace($prefix, $uri);
        }
    }

    /**
     * Parse PAX and return all information objects.
     */
    public function parse(): array
    {
        if (!$this->extractPath) {
            $this->extract();
        }

        $records = [];

        // Get all InformationObjects (intellectual entities)
        $ios = $this->xpath->query('//xip:InformationObject');
        
        foreach ($ios as $io) {
            $record = $this->parseInformationObject($io);
            if (!empty($record)) {
                $records[] = $record;
            }
        }

        // If no XIP structure, try flat metadata
        if (empty($records)) {
            $records = $this->parseFlatStructure();
        }

        return $records;
    }

    /**
     * Parse a single InformationObject element.
     */
    protected function parseInformationObject(\DOMElement $io): array
    {
        $record = [];

        // Reference (unique ID)
        $ref = $this->xpath->query('xip:Ref', $io);
        if ($ref->length > 0) {
            $record['legacyId'] = $ref->item(0)->textContent;
        }

        // Title
        $title = $this->xpath->query('xip:Title', $io);
        if ($title->length > 0) {
            $record['title'] = $title->item(0)->textContent;
        }

        // Description
        $desc = $this->xpath->query('xip:Description', $io);
        if ($desc->length > 0) {
            $record['scopeAndContent'] = $desc->item(0)->textContent;
        }

        // Security Tag
        $security = $this->xpath->query('xip:SecurityTag', $io);
        if ($security->length > 0) {
            $record['accessConditions'] = $security->item(0)->textContent;
        }

        // Parent Reference
        $parent = $this->xpath->query('xip:Parent', $io);
        if ($parent->length > 0) {
            $record['parentId'] = $parent->item(0)->textContent;
        }

        // Custom Type (maps to level)
        $customType = $this->xpath->query('xip:CustomType', $io);
        if ($customType->length > 0) {
            $record['levelOfDescription'] = $this->mapCustomType($customType->item(0)->textContent);
        }

        // Metadata fragments (embedded Dublin Core, etc.)
        $metadata = $this->xpath->query('xip:Metadata/xip:Content', $io);
        if ($metadata->length > 0) {
            $dcData = $this->parseDublinCore($metadata->item(0));
            $record = array_merge($record, $dcData);
        }

        // Get associated content
        $ioRef = $record['legacyId'] ?? '';
        if ($ioRef) {
            $content = $this->getContentForIO($ioRef);
            if (!empty($content)) {
                $record['digitalObjectPath'] = $content['path'];
                if (!empty($content['filename'])) {
                    $record['_originalFilename'] = $content['filename'];
                }
            }
        }

        return $record;
    }

    /**
     * Parse Dublin Core metadata from content node.
     */
    protected function parseDublinCore(\DOMElement $node): array
    {
        $data = [];
        
        $dcElements = [
            'title' => 'title',
            'creator' => 'creators',
            'subject' => 'subjectAccessPoints',
            'description' => 'scopeAndContent',
            'publisher' => 'repository',
            'contributor' => 'nameAccessPoints',
            'date' => 'dateRange',
            'type' => 'levelOfDescription',
            'format' => 'extentAndMedium',
            'identifier' => 'identifier',
            'source' => 'locationOfOriginals',
            'language' => 'language',
            'coverage' => 'placeAccessPoints',
            'rights' => 'reproductionConditions',
        ];

        foreach ($dcElements as $dc => $atom) {
            $elements = $this->xpath->query(".//dc:{$dc}", $node);
            if ($elements->length > 0) {
                if ($elements->length === 1) {
                    $value = $elements->item(0)->textContent;
                } else {
                    $values = [];
                    foreach ($elements as $el) {
                        $values[] = $el->textContent;
                    }
                    $value = implode('|', $values);
                }
                
                // Don't overwrite existing values
                if (!isset($data[$atom])) {
                    $data[$atom] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Map Preservica CustomType to AtoM level of description.
     */
    protected function mapCustomType(string $type): string
    {
        $mapping = [
            'Fonds' => 'Fonds',
            'Collection' => 'Collection',
            'Series' => 'Series',
            'SubSeries' => 'Sub-series',
            'Sub-Series' => 'Sub-series',
            'File' => 'File',
            'Folder' => 'File',
            'Item' => 'Item',
            'Asset' => 'Item',
            'Document' => 'Item',
        ];

        return $mapping[$type] ?? 'Item';
    }

    /**
     * Get content file path for an InformationObject.
     */
    protected function getContentForIO(string $ioRef): array
    {
        // Find Representation for this IO
        $reps = $this->xpath->query("//xip:Representation[xip:InformationObject='{$ioRef}']");
        
        foreach ($reps as $rep) {
            // Find ContentObjects for this Representation
            $repRef = $this->xpath->query('xip:Ref', $rep);
            if ($repRef->length === 0) continue;
            
            $repRefValue = $repRef->item(0)->textContent;
            
            // Find ContentObject
            $cos = $this->xpath->query("//xip:ContentObject[xip:Parent='{$repRefValue}']");
            
            foreach ($cos as $co) {
                // Find Generation
                $coRef = $this->xpath->query('xip:Ref', $co);
                if ($coRef->length === 0) continue;
                
                $coRefValue = $coRef->item(0)->textContent;
                
                // Find Bitstream
                $bitstreams = $this->xpath->query("//xip:Bitstream[xip:ContentObject='{$coRefValue}']");
                
                foreach ($bitstreams as $bs) {
                    $filename = $this->xpath->query('xip:Filename', $bs);
                    if ($filename->length > 0) {
                        $fname = $filename->item(0)->textContent;
                        
                        // Find actual file in content directory
                        $contentPath = $this->findContentFile($fname);
                        if ($contentPath) {
                            return [
                                'path' => $contentPath,
                                'filename' => $fname,
                            ];
                        }
                    }
                }
            }
        }

        return [];
    }

    /**
     * Find content file in extracted archive.
     */
    protected function findContentFile(string $filename): ?string
    {
        $searchPaths = [
            $this->extractPath . '/content/' . $filename,
            $this->extractPath . '/Content/' . $filename,
            $this->extractPath . '/' . $filename,
        ];

        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Recursive search
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->extractPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Parse flat structure (no XIP, just files with metadata).
     */
    protected function parseFlatStructure(): array
    {
        $records = [];
        
        // Look for any XML metadata files
        $metaFiles = glob($this->extractPath . '/**/*.xml', GLOB_BRACE);
        
        foreach ($metaFiles as $metaFile) {
            $dom = new \DOMDocument();
            if (@$dom->load($metaFile)) {
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
                
                // Try to extract DC metadata
                $title = $xpath->query('//dc:title');
                if ($title->length > 0) {
                    $record = [
                        'title' => $title->item(0)->textContent,
                        '_metaFile' => basename($metaFile),
                    ];
                    
                    // Get other DC elements
                    $dcElements = ['creator', 'description', 'date', 'identifier', 'subject'];
                    foreach ($dcElements as $el) {
                        $nodes = $xpath->query("//dc:{$el}");
                        if ($nodes->length > 0) {
                            $record[$el] = $nodes->item(0)->textContent;
                        }
                    }
                    
                    $records[] = $record;
                }
            }
        }

        return $records;
    }

    /**
     * Convert parsed PAX data to AtoM-compatible records.
     */
    public function toAtomRecords(): array
    {
        $parsed = $this->parse();
        $records = [];

        foreach ($parsed as $item) {
            // Already mapped in parseInformationObject
            $records[] = $item;
        }

        return $records;
    }

    /**
     * Cleanup extracted files.
     */
    public function cleanup(): void
    {
        if ($this->extractPath && is_dir($this->extractPath)) {
            $this->recursiveDelete($this->extractPath);
        }
    }

    protected function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir . '/' . $object;
                    if (is_dir($path)) {
                        $this->recursiveDelete($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
