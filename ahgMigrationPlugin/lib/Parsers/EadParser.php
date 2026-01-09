<?php
namespace AhgMigration\Parsers;

class EadParser implements ParserInterface
{
    protected array $headers = [];
    protected int $rowCount = 0;
    protected ?\DOMDocument $dom = null;
    protected ?\DOMXPath $xpath = null;
    
    const EAD_NS = 'urn:isbn:1-931666-22-9';
    const EAD3_NS = 'http://ead3.archivists.org/schema/';
    
    protected bool $isEad3 = false;
    protected string $nsPrefix = 'ead';
    
    public function parse(string $filePath): \Generator
    {
        $this->loadDocument($filePath);
        
        // First yield collection-level record from archdesc
        $archdesc = $this->xpath->query('//' . $this->nsPrefix . ':archdesc')->item(0);
        if (!$archdesc) {
            // Try without namespace
            $archdesc = $this->xpath->query('//archdesc')->item(0);
        }
        
        if ($archdesc) {
            $rowNumber = 1;
            $collectionData = $this->extractArchdesc($archdesc);
            
            yield [
                'row_number' => $rowNumber,
                'data' => $collectionData,
                'hierarchy_level' => 0,
                'parent_id' => null
            ];
            
            // Yield component records recursively
            foreach ($this->parseComponents($archdesc, $rowNumber, 1, $collectionData['identifier'] ?? null) as $component) {
                yield $component;
            }
            
            $this->rowCount = $rowNumber;
        }
    }
    
    protected function parseComponents(\DOMNode $parent, int &$rowNumber, int $level, ?string $parentId): \Generator
    {
        // Find child components (c, c01, c02, etc.)
        $componentQueries = [
            './/' . $this->nsPrefix . ':dsc/' . $this->nsPrefix . ':c',
            './' . $this->nsPrefix . ':c',
            './' . $this->nsPrefix . ':c01',
            './' . $this->nsPrefix . ':c02',
            './' . $this->nsPrefix . ':c03',
            './' . $this->nsPrefix . ':c04',
            './' . $this->nsPrefix . ':c05',
            // Without namespace
            './dsc/c', './c', './c01', './c02', './c03'
        ];
        
        $components = new \DOMNodeList();
        foreach ($componentQueries as $query) {
            $result = $this->xpath->query($query, $parent);
            if ($result && $result->length > 0) {
                $components = $result;
                break;
            }
        }
        
        foreach ($components as $component) {
            $rowNumber++;
            $data = $this->extractComponent($component);
            $componentId = $data['identifier'] ?? $data['componentId'] ?? "row_{$rowNumber}";
            
            yield [
                'row_number' => $rowNumber,
                'data' => $data,
                'hierarchy_level' => $level,
                'parent_id' => $parentId
            ];
            
            // Recursively parse child components
            foreach ($this->parseComponents($component, $rowNumber, $level + 1, $componentId) as $child) {
                yield $child;
            }
        }
    }
    
    protected function extractArchdesc(\DOMNode $archdesc): array
    {
        $data = ['levelOfDescription' => 'Fonds'];
        
        // EAD ID
        $eadid = $this->queryFirst('//ead:eadid | //eadid');
        if ($eadid) {
            $data['eadId'] = trim($eadid->textContent);
            $data['identifier'] = $data['eadId'];
        }
        
        // Level attribute
        $level = $archdesc->getAttribute('level');
        if ($level) {
            $data['levelOfDescription'] = $this->mapLevel($level);
        }
        
        // DID elements
        $did = $this->queryFirst('./' . $this->nsPrefix . ':did | ./did', $archdesc);
        if ($did) {
            $data = array_merge($data, $this->extractDid($did));
        }
        
        // Notes
        $data = array_merge($data, $this->extractNotes($archdesc));
        
        // Access points
        $data = array_merge($data, $this->extractAccessPoints($archdesc));
        
        return $data;
    }
    
    protected function extractComponent(\DOMNode $component): array
    {
        $data = [];
        
        // Get level and id attributes
        $level = $component->getAttribute('level');
        if ($level) {
            $data['levelOfDescription'] = $this->mapLevel($level);
        }
        
        $id = $component->getAttribute('id');
        if ($id) {
            $data['componentId'] = $id;
        }
        
        // DID elements
        $did = $this->queryFirst('./' . $this->nsPrefix . ':did | ./did', $component);
        if ($did) {
            $data = array_merge($data, $this->extractDid($did));
        }
        
        // Notes
        $data = array_merge($data, $this->extractNotes($component));
        
        // Access points
        $data = array_merge($data, $this->extractAccessPoints($component));
        
        // Digital objects (dao)
        $daos = $this->xpath->query('.//' . $this->nsPrefix . ':dao | .//dao', $component);
        if ($daos && $daos->length > 0) {
            $data['digitalObjects'] = [];
            foreach ($daos as $dao) {
                $href = $dao->getAttribute('xlink:href') ?: $dao->getAttribute('href') ?: $dao->getAttribute('actuate');
                if ($href) {
                    $data['digitalObjects'][] = [
                        'uri' => $href,
                        'title' => $dao->getAttribute('xlink:title') ?: $dao->getAttribute('title') ?: ''
                    ];
                }
            }
        }
        
        return $data;
    }
    
    protected function extractDid(\DOMNode $did): array
    {
        $data = [];
        $ns = $this->nsPrefix;
        
        // Simple text fields
        $fields = [
            'unitid' => 'identifier',
            'unittitle' => 'title',
            'abstract' => 'abstract',
            'physloc' => 'physicalStorage',
            'materialspec' => 'materialSpecification',
            'repository/corpname' => 'repository'
        ];
        
        foreach ($fields as $element => $field) {
            $node = $this->queryFirst(".//{$ns}:{$element} | .//{$element}", $did);
            if ($node) {
                $data[$field] = trim($node->textContent);
                $this->addHeader($field);
            }
        }
        
        // Unit date (can be multiple, has attributes)
        $dates = $this->xpath->query(".//{$ns}:unitdate | .//unitdate", $did);
        if ($dates && $dates->length > 0) {
            $dateNode = $dates->item(0);
            $data['dateExpression'] = trim($dateNode->textContent);
            $this->addHeader('dateExpression');
            
            $normal = $dateNode->getAttribute('normal');
            if ($normal) {
                $this->parseDateNormal($normal, $data);
            }
        }
        
        // Physical description / extent
        $physdesc = $this->queryFirst(".//{$ns}:physdesc | .//physdesc", $did);
        if ($physdesc) {
            $extent = $this->queryFirst(".//{$ns}:extent | .//extent", $physdesc);
            $data['extent'] = $extent ? trim($extent->textContent) : trim($physdesc->textContent);
            $this->addHeader('extent');
        }
        
        // Language
        $langmaterial = $this->queryFirst(".//{$ns}:langmaterial | .//langmaterial", $did);
        if ($langmaterial) {
            $language = $this->queryFirst(".//{$ns}:language | .//language", $langmaterial);
            $data['language'] = $language ? trim($language->textContent) : trim($langmaterial->textContent);
            $this->addHeader('language');
        }
        
        // Origination (creators)
        $originations = $this->xpath->query(".//{$ns}:origination | .//origination", $did);
        if ($originations && $originations->length > 0) {
            $data['creators'] = [];
            foreach ($originations as $orig) {
                $creator = ['name' => trim($orig->textContent), 'type' => 'name'];
                
                // Check for specific name types
                foreach (['persname' => 'person', 'corpname' => 'corporate', 'famname' => 'family'] as $el => $type) {
                    $nameNode = $this->queryFirst(".//{$ns}:{$el} | .//{$el}", $orig);
                    if ($nameNode) {
                        $creator['name'] = trim($nameNode->textContent);
                        $creator['type'] = $type;
                        break;
                    }
                }
                
                $data['creators'][] = $creator;
            }
            $this->addHeader('creators');
        }
        
        // Containers
        $containers = $this->xpath->query(".//{$ns}:container | .//container", $did);
        if ($containers && $containers->length > 0) {
            $data['containers'] = [];
            foreach ($containers as $c) {
                $data['containers'][] = [
                    'type' => $c->getAttribute('type') ?: 'box',
                    'value' => trim($c->textContent)
                ];
            }
            $this->addHeader('containers');
        }
        
        return $data;
    }
    
    protected function extractNotes(\DOMNode $context): array
    {
        $data = [];
        $ns = $this->nsPrefix;
        
        $noteFields = [
            'scopecontent' => 'scopeAndContent',
            'arrangement' => 'arrangement',
            'bioghist' => 'biographicalHistory',
            'accessrestrict' => 'accessConditions',
            'userestrict' => 'reproductionConditions',
            'custodhist' => 'custodialHistory',
            'acqinfo' => 'acquisition',
            'processinfo' => 'archivistNote',
            'relatedmaterial' => 'relatedMaterial',
            'separatedmaterial' => 'separatedMaterial',
            'otherfindaid' => 'findingAids',
            'bibliography' => 'bibliography',
            'odd' => 'generalNote',
            'prefercite' => 'citation',
            'appraisal' => 'appraisal',
            'accruals' => 'accruals'
        ];
        
        foreach ($noteFields as $element => $field) {
            $nodes = $this->xpath->query(".//{$ns}:{$element} | .//{$element}", $context);
            if ($nodes && $nodes->length > 0) {
                $content = [];
                foreach ($nodes as $node) {
                    // Get paragraph content
                    $paras = $this->xpath->query(".//{$ns}:p | .//p", $node);
                    if ($paras && $paras->length > 0) {
                        foreach ($paras as $p) {
                            $content[] = trim($p->textContent);
                        }
                    } else {
                        $content[] = trim($node->textContent);
                    }
                }
                $data[$field] = implode("\n\n", array_filter($content));
                $this->addHeader($field);
            }
        }
        
        return $data;
    }
    
    protected function extractAccessPoints(\DOMNode $context): array
    {
        $data = [];
        $ns = $this->nsPrefix;
        
        $accessPointTypes = [
            'subject' => 'subjects',
            'geogname' => 'places',
            'genreform' => 'genres',
            'persname' => 'persons',
            'corpname' => 'corporations',
            'famname' => 'families',
            'occupation' => 'occupations',
            'function' => 'functions'
        ];
        
        // Look in controlaccess
        $controlaccess = $this->queryFirst(".//{$ns}:controlaccess | .//controlaccess", $context);
        $searchContext = $controlaccess ?: $context;
        
        foreach ($accessPointTypes as $element => $field) {
            $nodes = $this->xpath->query(".//{$ns}:{$element} | .//{$element}", $searchContext);
            if ($nodes && $nodes->length > 0) {
                $data[$field] = [];
                foreach ($nodes as $node) {
                    $term = trim($node->textContent);
                    if ($term) {
                        $data[$field][] = [
                            'term' => $term,
                            'source' => $node->getAttribute('source') ?: '',
                            'authfilenumber' => $node->getAttribute('authfilenumber') ?: ''
                        ];
                    }
                }
                if (!empty($data[$field])) {
                    $this->addHeader($field);
                }
            }
        }
        
        return $data;
    }
    
    protected function parseDateNormal(string $normal, array &$data): void
    {
        // Handle ISO date ranges: 1900/1950 or 1900-01-01/1950-12-31
        if (strpos($normal, '/') !== false) {
            [$start, $end] = explode('/', $normal, 2);
            $data['startDate'] = $this->normalizeDate($start);
            $data['endDate'] = $this->normalizeDate($end);
        } else {
            $data['startDate'] = $this->normalizeDate($normal);
        }
        $this->addHeader('startDate');
        $this->addHeader('endDate');
    }
    
    protected function normalizeDate(string $date): string
    {
        // Already in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        // YYYY format
        if (preg_match('/^\d{4}$/', $date)) {
            return $date . '-01-01';
        }
        // YYYY-MM format
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            return $date . '-01';
        }
        return $date;
    }
    
    protected function mapLevel(string $level): string
    {
        $mapping = [
            'collection' => 'Collection',
            'fonds' => 'Fonds',
            'subfonds' => 'Sub-fonds',
            'recordgrp' => 'Record group',
            'subgrp' => 'Sub-group',
            'series' => 'Series',
            'subseries' => 'Sub-series',
            'file' => 'File',
            'item' => 'Item',
            'otherlevel' => 'Part',
            'class' => 'Class'
        ];
        
        return $mapping[strtolower($level)] ?? ucfirst($level);
    }
    
    protected function loadDocument(string $filePath): void
    {
        $this->dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$this->dom->load($filePath)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \RuntimeException("Cannot parse EAD: " . ($errors[0]->message ?? 'Unknown error'));
        }
        
        $this->xpath = new \DOMXPath($this->dom);
        
        // Detect EAD version and register namespaces
        $root = $this->dom->documentElement;
        if ($root) {
            $ns = $root->namespaceURI;
            if ($ns) {
                if (strpos($ns, 'ead3') !== false) {
                    $this->isEad3 = true;
                    $this->xpath->registerNamespace('ead', self::EAD3_NS);
                } else {
                    $this->xpath->registerNamespace('ead', $ns);
                }
            } else {
                // No namespace - register EAD2002 as default
                $this->xpath->registerNamespace('ead', self::EAD_NS);
            }
        }
        
        // Register xlink namespace
        $this->xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
    }
    
    protected function queryFirst(string $xpath, ?\DOMNode $context = null): ?\DOMNode
    {
        $result = $this->xpath->query($xpath, $context);
        return ($result && $result->length > 0) ? $result->item(0) : null;
    }
    
    protected function addHeader(string $field): void
    {
        if (!in_array($field, $this->headers)) {
            $this->headers[] = $field;
        }
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function getRowCount(): int
    {
        return $this->rowCount;
    }
    
    public function getFormat(): string
    {
        return 'ead';
    }
    
    public function validate(string $filePath): array
    {
        $errors = [];
        
        if (!file_exists($filePath)) {
            return ["File not found: $filePath"];
        }
        
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        
        if (!$dom->load($filePath)) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = "XML Error (line {$error->line}): " . trim($error->message);
            }
            libxml_clear_errors();
            return $errors;
        }
        
        // Check for EAD structure
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ead', self::EAD_NS);
        $xpath->registerNamespace('ead3', self::EAD3_NS);
        
        $hasEad = $xpath->query('//ead:ead | //ead3:ead | //ead')->length > 0;
        if (!$hasEad) {
            $errors[] = "Not a valid EAD file: missing <ead> root element";
        }
        
        $hasArchdesc = $xpath->query('//ead:archdesc | //ead3:archdesc | //archdesc')->length > 0;
        if (!$hasArchdesc) {
            $errors[] = "EAD file missing <archdesc> element";
        }
        
        return $errors;
    }
    
    public function getSample(string $filePath, int $count = 5): array
    {
        $samples = [];
        $i = 0;
        
        foreach ($this->parse($filePath) as $record) {
            $samples[] = $record;
            $i++;
            if ($i >= $count) {
                break;
            }
        }
        
        return [
            'headers' => $this->headers,
            'records' => $samples,
            'format' => 'ead',
            'isEad3' => $this->isEad3
        ];
    }
}
