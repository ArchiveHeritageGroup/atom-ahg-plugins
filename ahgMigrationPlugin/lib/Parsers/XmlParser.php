<?php
namespace AhgMigration\Parsers;

class XmlParser implements ParserInterface
{
    protected array $headers = [];
    protected int $rowCount = 0;
    protected string $recordXPath = '//record';
    protected array $fieldXPaths = [];
    protected array $namespaces = [];
    protected ?\DOMDocument $dom = null;
    protected ?\DOMXPath $xpath = null;
    
    public function __construct(string $recordXPath = '//record', array $fieldXPaths = [])
    {
        $this->recordXPath = $recordXPath;
        $this->fieldXPaths = $fieldXPaths;
    }
    
    public function parse(string $filePath): \Generator
    {
        $this->loadDocument($filePath);
        
        $records = $this->xpath->query($this->recordXPath);
        if ($records === false) {
            throw new \RuntimeException("Invalid XPath: {$this->recordXPath}");
        }
        
        $rowNumber = 0;
        foreach ($records as $record) {
            $rowNumber++;
            $data = $this->extractFields($record);
            
            yield [
                'row_number' => $rowNumber,
                'data' => $data
            ];
        }
        
        $this->rowCount = $rowNumber;
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
        return 'xml';
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
            'format' => 'xml',
            'recordXPath' => $this->recordXPath
        ];
    }
    
    protected function loadDocument(string $filePath): void
    {
        $this->dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$this->dom->load($filePath)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \RuntimeException("Cannot parse XML: " . ($errors[0]->message ?? 'Unknown error'));
        }
        
        $this->xpath = new \DOMXPath($this->dom);
        $this->registerNamespaces();
    }
    
    protected function registerNamespaces(): void
    {
        // Auto-detect namespaces from document
        $root = $this->dom->documentElement;
        if ($root) {
            // Default namespace
            if ($root->namespaceURI) {
                $this->xpath->registerNamespace('ns', $root->namespaceURI);
            }
            
            // Check for common namespaces in attributes
            foreach ($root->attributes as $attr) {
                if (strpos($attr->nodeName, 'xmlns:') === 0) {
                    $prefix = substr($attr->nodeName, 6);
                    $this->xpath->registerNamespace($prefix, $attr->nodeValue);
                    $this->namespaces[$prefix] = $attr->nodeValue;
                }
            }
        }
        
        // Register common namespaces
        $common = [
            'dc' => 'http://purl.org/dc/elements/1.1/',
            'dcterms' => 'http://purl.org/dc/terms/',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
        
        foreach ($common as $prefix => $uri) {
            if (!isset($this->namespaces[$prefix])) {
                $this->xpath->registerNamespace($prefix, $uri);
            }
        }
    }
    
    protected function extractFields(\DOMNode $record): array
    {
        $data = [];
        
        if (!empty($this->fieldXPaths)) {
            // Use configured XPaths
            foreach ($this->fieldXPaths as $fieldName => $xpath) {
                $nodes = $this->xpath->query($xpath, $record);
                if ($nodes && $nodes->length > 0) {
                    $data[$fieldName] = $this->getNodeValue($nodes);
                    if (!in_array($fieldName, $this->headers)) {
                        $this->headers[] = $fieldName;
                    }
                }
            }
        } else {
            // Auto-extract all child elements
            foreach ($record->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $fieldName = $child->localName ?: $child->nodeName;
                    $value = trim($child->textContent);
                    
                    if (!isset($data[$fieldName])) {
                        $data[$fieldName] = $value;
                    } else {
                        // Handle multiple values
                        if (!is_array($data[$fieldName])) {
                            $data[$fieldName] = [$data[$fieldName]];
                        }
                        $data[$fieldName][] = $value;
                    }
                    
                    if (!in_array($fieldName, $this->headers)) {
                        $this->headers[] = $fieldName;
                    }
                }
            }
        }
        
        return $data;
    }
    
    protected function getNodeValue(\DOMNodeList $nodes): string|array
    {
        if ($nodes->length === 1) {
            return trim($nodes->item(0)->textContent);
        }
        
        $values = [];
        foreach ($nodes as $node) {
            $values[] = trim($node->textContent);
        }
        return $values;
    }
    
    // Configuration setters
    public function setRecordXPath(string $xpath): self { $this->recordXPath = $xpath; return $this; }
    public function setFieldXPaths(array $xpaths): self { $this->fieldXPaths = $xpaths; return $this; }
    public function addNamespace(string $prefix, string $uri): self { 
        $this->namespaces[$prefix] = $uri; 
        if ($this->xpath) {
            $this->xpath->registerNamespace($prefix, $uri);
        }
        return $this; 
    }
}
