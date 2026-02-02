<?php

/**
 * AbstractRdfExporter - Base class for RDF/Linked Data exporters
 *
 * Provides common RDF generation functionality for formats like:
 * RIC-O, CIDOC-CRM, BIBFRAME
 *
 * Supports multiple output formats: JSON-LD, Turtle, RDF/XML, N-Triples
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

use AhgMetadataExport\Contracts\ExporterInterface;

abstract class AbstractRdfExporter implements ExporterInterface
{
    /**
     * Output format constants
     */
    public const FORMAT_JSONLD = 'jsonld';
    public const FORMAT_TURTLE = 'turtle';
    public const FORMAT_RDFXML = 'rdfxml';
    public const FORMAT_NTRIPLES = 'ntriples';

    /**
     * @var array RDF graph as associative array
     */
    protected $graph = [];

    /**
     * @var array JSON-LD context
     */
    protected $context = [];

    /**
     * @var string Base URI for identifiers
     */
    protected $baseUri = '';

    /**
     * @var string Output format (jsonld, turtle, rdfxml, ntriples)
     */
    protected $outputFormat = self::FORMAT_JSONLD;

    /**
     * @var array Export options
     */
    protected $options = [];

    /**
     * @var array Namespace prefixes [prefix => uri]
     */
    protected $prefixes = [];

    /**
     * Constructor
     *
     * @param string|null $baseUri Base URI for identifiers
     */
    public function __construct(?string $baseUri = null)
    {
        $this->baseUri = $baseUri ?? \sfConfig::get('app_siteBaseUrl', 'https://example.org');
        $this->initializePrefixes();
        $this->initializeContext();
    }

    /**
     * Initialize namespace prefixes
     * Override in subclasses to define format-specific prefixes
     */
    abstract protected function initializePrefixes(): void;

    /**
     * Initialize JSON-LD context
     * Override in subclasses to define format-specific context
     */
    abstract protected function initializeContext(): void;

    /**
     * Build the RDF graph for a resource
     *
     * @param mixed $resource
     *
     * @return array Graph as associative array
     */
    abstract protected function buildGraph($resource): array;

    /**
     * {@inheritdoc}
     */
    public function export($resource, array $options = []): string
    {
        $this->options = $this->validateOptions($options);
        $this->graph = [];

        if (isset($options['outputFormat'])) {
            $this->setOutputFormat($options['outputFormat']);
        }

        $graph = $this->buildGraph($resource);

        return $this->serializeGraph($graph);
    }

    /**
     * {@inheritdoc}
     */
    public function exportBatch(array $resources, array $options = []): \Generator
    {
        foreach ($resources as $resource) {
            yield $this->export($resource, $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exportToFile($resource, string $path, array $options = []): bool
    {
        $content = $this->export($resource, $options);

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return false !== file_put_contents($path, $content);
    }

    /**
     * Set the output format
     *
     * @param string $format One of: jsonld, turtle, rdfxml, ntriples
     *
     * @return self
     */
    public function setOutputFormat(string $format): self
    {
        $validFormats = [
            self::FORMAT_JSONLD,
            self::FORMAT_TURTLE,
            self::FORMAT_RDFXML,
            self::FORMAT_NTRIPLES,
        ];

        if (!in_array($format, $validFormats, true)) {
            throw new \InvalidArgumentException("Invalid output format: {$format}");
        }

        $this->outputFormat = $format;

        return $this;
    }

    /**
     * Get current output format
     *
     * @return string
     */
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimeType(): string
    {
        switch ($this->outputFormat) {
            case self::FORMAT_JSONLD:
                return 'application/ld+json';
            case self::FORMAT_TURTLE:
                return 'text/turtle';
            case self::FORMAT_RDFXML:
                return 'application/rdf+xml';
            case self::FORMAT_NTRIPLES:
                return 'application/n-triples';
            default:
                return 'application/ld+json';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension(): string
    {
        switch ($this->outputFormat) {
            case self::FORMAT_JSONLD:
                return 'jsonld';
            case self::FORMAT_TURTLE:
                return 'ttl';
            case self::FORMAT_RDFXML:
                return 'rdf';
            case self::FORMAT_NTRIPLES:
                return 'nt';
            default:
                return 'jsonld';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedResourceTypes(): array
    {
        return ['QubitInformationObject'];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResourceType(string $type): bool
    {
        return in_array($type, $this->getSupportedResourceTypes(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(): array
    {
        return [
            'includeDigitalObjects' => true,
            'includeDrafts' => false,
            'includeChildren' => true,
            'maxDepth' => 0, // 0 = unlimited
            'prettyPrint' => true,
            'outputFormat' => self::FORMAT_JSONLD,
            'includeContext' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateOptions(array $options): array
    {
        $defaults = $this->getDefaultOptions();
        $validated = array_merge($defaults, $options);

        // Ensure boolean options are boolean
        foreach (['includeDigitalObjects', 'includeDrafts', 'includeChildren', 'prettyPrint', 'includeContext'] as $key) {
            if (isset($validated[$key])) {
                $validated[$key] = (bool) $validated[$key];
            }
        }

        // Ensure maxDepth is integer
        $validated['maxDepth'] = (int) ($validated['maxDepth'] ?? 0);

        return $validated;
    }

    /**
     * Serialize graph to output format
     *
     * @param array $graph
     *
     * @return string
     */
    protected function serializeGraph(array $graph): string
    {
        switch ($this->outputFormat) {
            case self::FORMAT_TURTLE:
                return $this->toTurtle($graph);
            case self::FORMAT_RDFXML:
                return $this->toRdfXml($graph);
            case self::FORMAT_NTRIPLES:
                return $this->toNTriples($graph);
            case self::FORMAT_JSONLD:
            default:
                return $this->toJsonLd($graph);
        }
    }

    /**
     * Serialize to JSON-LD
     *
     * @param array $graph
     *
     * @return string
     */
    protected function toJsonLd(array $graph): string
    {
        $output = [];

        if ($this->options['includeContext'] && !empty($this->context)) {
            $output['@context'] = $this->context;
        }

        // Merge graph data
        foreach ($graph as $key => $value) {
            $output[$key] = $value;
        }

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($this->options['prettyPrint']) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($output, $flags);
    }

    /**
     * Serialize to Turtle
     *
     * @param array $graph
     *
     * @return string
     */
    protected function toTurtle(array $graph): string
    {
        $output = [];

        // Add prefixes
        foreach ($this->prefixes as $prefix => $uri) {
            $output[] = "@prefix {$prefix}: <{$uri}> .";
        }
        $output[] = '';

        // Convert graph to Turtle triples
        $triples = $this->graphToTriples($graph);
        foreach ($triples as $triple) {
            $output[] = $this->formatTurtleTriple($triple);
        }

        return implode("\n", $output);
    }

    /**
     * Serialize to RDF/XML
     *
     * @param array $graph
     *
     * @return string
     */
    protected function toRdfXml(array $graph): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = $this->options['prettyPrint'];

        $rdf = $dom->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF');
        $dom->appendChild($rdf);

        // Add namespace declarations
        foreach ($this->prefixes as $prefix => $uri) {
            $rdf->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, $uri);
        }

        // Convert graph to RDF/XML
        $this->graphToRdfXml($graph, $rdf, $dom);

        return $dom->saveXML();
    }

    /**
     * Serialize to N-Triples
     *
     * @param array $graph
     *
     * @return string
     */
    protected function toNTriples(array $graph): string
    {
        $output = [];
        $triples = $this->graphToTriples($graph);

        foreach ($triples as $triple) {
            $output[] = $this->formatNTriple($triple);
        }

        return implode("\n", $output);
    }

    /**
     * Convert graph array to list of triples
     *
     * @param array  $graph
     * @param string $subject
     *
     * @return array Array of [subject, predicate, object] triples
     */
    protected function graphToTriples(array $graph, string $subject = ''): array
    {
        $triples = [];

        // Get subject URI
        if (empty($subject)) {
            $subject = $graph['@id'] ?? $this->baseUri.'/'.uniqid();
        }

        foreach ($graph as $predicate => $value) {
            // Skip JSON-LD keywords except @type
            if ('@' === $predicate[0] && '@type' !== $predicate) {
                continue;
            }

            // Handle @type
            if ('@type' === $predicate) {
                $predicate = 'rdf:type';
                $triples[] = [$subject, $predicate, ['@id' => $this->expandType($value)]];

                continue;
            }

            // Handle array values
            if (is_array($value)) {
                // Check if it's a single object or array of objects
                if (isset($value['@id']) || isset($value['@value'])) {
                    $triples[] = [$subject, $predicate, $value];
                } else {
                    foreach ($value as $item) {
                        $triples[] = [$subject, $predicate, $item];
                    }
                }
            } else {
                $triples[] = [$subject, $predicate, $value];
            }
        }

        return $triples;
    }

    /**
     * Format a triple as Turtle
     *
     * @param array $triple [subject, predicate, object]
     *
     * @return string
     */
    protected function formatTurtleTriple(array $triple): string
    {
        [$subject, $predicate, $object] = $triple;

        $subj = $this->formatTurtleUri($subject);
        $pred = $this->formatTurtlePredicate($predicate);
        $obj = $this->formatTurtleObject($object);

        return "{$subj} {$pred} {$obj} .";
    }

    /**
     * Format URI for Turtle
     *
     * @param string $uri
     *
     * @return string
     */
    protected function formatTurtleUri(string $uri): string
    {
        // Check if it matches a prefix
        foreach ($this->prefixes as $prefix => $nsUri) {
            if (0 === strpos($uri, $nsUri)) {
                return $prefix.':'.substr($uri, strlen($nsUri));
            }
        }

        return '<'.$uri.'>';
    }

    /**
     * Format predicate for Turtle
     *
     * @param string $predicate
     *
     * @return string
     */
    protected function formatTurtlePredicate(string $predicate): string
    {
        // Already prefixed
        if (false !== strpos($predicate, ':')) {
            return $predicate;
        }

        return '<'.$predicate.'>';
    }

    /**
     * Format object for Turtle
     *
     * @param mixed $object
     *
     * @return string
     */
    protected function formatTurtleObject($object): string
    {
        if (is_array($object)) {
            if (isset($object['@id'])) {
                return $this->formatTurtleUri($object['@id']);
            }
            if (isset($object['@value'])) {
                $value = '"'.addslashes($object['@value']).'"';
                if (isset($object['@type'])) {
                    $value .= '^^'.$this->formatTurtleUri($object['@type']);
                }
                if (isset($object['@language'])) {
                    $value .= '@'.$object['@language'];
                }

                return $value;
            }
        }

        if (is_string($object)) {
            // Check if it looks like a URI
            if (0 === strpos($object, 'http://') || 0 === strpos($object, 'https://')) {
                return '<'.$object.'>';
            }

            return '"'.addslashes($object).'"';
        }

        if (is_bool($object)) {
            return $object ? '"true"^^xsd:boolean' : '"false"^^xsd:boolean';
        }

        if (is_int($object)) {
            return '"'.$object.'"^^xsd:integer';
        }

        if (is_float($object)) {
            return '"'.$object.'"^^xsd:decimal';
        }

        return '""';
    }

    /**
     * Format a triple as N-Triple
     *
     * @param array $triple
     *
     * @return string
     */
    protected function formatNTriple(array $triple): string
    {
        [$subject, $predicate, $object] = $triple;

        $subj = $this->formatNTripleUri($subject);
        $pred = $this->formatNTripleUri($this->expandPredicate($predicate));
        $obj = $this->formatNTripleObject($object);

        return "{$subj} {$pred} {$obj} .";
    }

    /**
     * Format URI for N-Triples
     *
     * @param string $uri
     *
     * @return string
     */
    protected function formatNTripleUri(string $uri): string
    {
        // Expand prefixed URIs
        foreach ($this->prefixes as $prefix => $nsUri) {
            if (0 === strpos($uri, $prefix.':')) {
                $uri = $nsUri.substr($uri, strlen($prefix) + 1);
                break;
            }
        }

        return '<'.$uri.'>';
    }

    /**
     * Format object for N-Triples
     *
     * @param mixed $object
     *
     * @return string
     */
    protected function formatNTripleObject($object): string
    {
        if (is_array($object)) {
            if (isset($object['@id'])) {
                return $this->formatNTripleUri($object['@id']);
            }
            if (isset($object['@value'])) {
                $value = '"'.$this->escapeNTriple($object['@value']).'"';
                if (isset($object['@type'])) {
                    $value .= '^^'.$this->formatNTripleUri($object['@type']);
                }
                if (isset($object['@language'])) {
                    $value .= '@'.$object['@language'];
                }

                return $value;
            }
        }

        if (is_string($object)) {
            if (0 === strpos($object, 'http://') || 0 === strpos($object, 'https://')) {
                return '<'.$object.'>';
            }

            return '"'.$this->escapeNTriple($object).'"';
        }

        return '"'.$this->escapeNTriple((string) $object).'"';
    }

    /**
     * Escape string for N-Triples
     *
     * @param string $value
     *
     * @return string
     */
    protected function escapeNTriple(string $value): string
    {
        return addcslashes($value, "\\\"\n\r\t");
    }

    /**
     * Convert graph to RDF/XML DOM elements
     *
     * @param array        $graph
     * @param \DOMElement  $parent
     * @param \DOMDocument $dom
     */
    protected function graphToRdfXml(array $graph, \DOMElement $parent, \DOMDocument $dom): void
    {
        // Create Description element
        $type = $graph['@type'] ?? 'rdf:Description';
        $typeExpanded = $this->expandType($type);

        // Find prefix for type
        $typeElement = null;
        foreach ($this->prefixes as $prefix => $uri) {
            if (0 === strpos($typeExpanded, $uri)) {
                $localName = substr($typeExpanded, strlen($uri));
                $typeElement = $dom->createElementNS($uri, $prefix.':'.$localName);
                break;
            }
        }

        if (!$typeElement) {
            $typeElement = $dom->createElementNS(
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdf:Description'
            );
        }

        // Add rdf:about
        if (isset($graph['@id'])) {
            $typeElement->setAttributeNS(
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdf:about',
                $graph['@id']
            );
        }

        $parent->appendChild($typeElement);

        // Add properties
        foreach ($graph as $predicate => $value) {
            if ('@' === $predicate[0]) {
                continue;
            }

            $this->addRdfXmlProperty($typeElement, $dom, $predicate, $value);
        }
    }

    /**
     * Add property to RDF/XML element
     *
     * @param \DOMElement  $parent
     * @param \DOMDocument $dom
     * @param string       $predicate
     * @param mixed        $value
     */
    protected function addRdfXmlProperty(\DOMElement $parent, \DOMDocument $dom, string $predicate, $value): void
    {
        // Find namespace for predicate
        $ns = null;
        $localName = $predicate;

        foreach ($this->prefixes as $prefix => $uri) {
            if (0 === strpos($predicate, $prefix.':')) {
                $ns = $uri;
                $localName = substr($predicate, strlen($prefix) + 1);
                $predicate = $prefix.':'.$localName;
                break;
            }
        }

        // Handle array values
        if (is_array($value) && !isset($value['@id']) && !isset($value['@value'])) {
            foreach ($value as $item) {
                $this->addRdfXmlProperty($parent, $dom, $predicate, $item);
            }

            return;
        }

        // Create element
        $element = $ns
            ? $dom->createElementNS($ns, $predicate)
            : $dom->createElement($predicate);

        // Set value
        if (is_array($value)) {
            if (isset($value['@id'])) {
                $element->setAttributeNS(
                    'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                    'rdf:resource',
                    $value['@id']
                );
            } elseif (isset($value['@value'])) {
                $element->appendChild($dom->createTextNode($value['@value']));
                if (isset($value['@language'])) {
                    $element->setAttributeNS(
                        'http://www.w3.org/XML/1998/namespace',
                        'xml:lang',
                        $value['@language']
                    );
                }
            }
        } else {
            $element->appendChild($dom->createTextNode((string) $value));
        }

        $parent->appendChild($element);
    }

    /**
     * Expand prefixed type to full URI
     *
     * @param string $type
     *
     * @return string
     */
    protected function expandType(string $type): string
    {
        foreach ($this->prefixes as $prefix => $uri) {
            if (0 === strpos($type, $prefix.':')) {
                return $uri.substr($type, strlen($prefix) + 1);
            }
        }

        return $type;
    }

    /**
     * Expand prefixed predicate to full URI
     *
     * @param string $predicate
     *
     * @return string
     */
    protected function expandPredicate(string $predicate): string
    {
        return $this->expandType($predicate);
    }

    /**
     * Create URI for resource
     *
     * @param mixed  $resource
     * @param string $type Optional type suffix
     *
     * @return string
     */
    protected function createUri($resource, string $type = ''): string
    {
        $id = $resource->slug ?? $resource->id ?? uniqid();

        $uri = rtrim($this->baseUri, '/').'/';
        if ($type) {
            $uri .= $type.'/';
        }
        $uri .= $id;

        return $uri;
    }

    /**
     * Get text value from resource property
     *
     * AtoM uses __call magic methods for i18n properties, so method_exists() returns false.
     * We try calling the getter directly within try-catch.
     *
     * @param mixed  $resource
     * @param string $property
     * @param string $culture
     *
     * @return string|null
     */
    protected function getValue($resource, string $property, string $culture = 'en'): ?string
    {
        $getter = 'get'.ucfirst($property);

        // Try with culture options first (AtoM i18n pattern)
        try {
            $value = $resource->{$getter}(['culture' => $culture]);
            if (is_string($value) && '' !== $value) {
                return $value;
            }
        } catch (\Exception $e) {
            // Ignore, try without options
        }

        // Try without culture options
        try {
            $value = $resource->{$getter}();
            if (is_string($value) && '' !== $value) {
                return $value;
            }
            if (is_object($value) && method_exists($value, '__toString')) {
                $str = (string) $value;
                if ('' !== $str) {
                    return $str;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * Add literal property to graph
     *
     * @param array       $node
     * @param string      $predicate
     * @param string|null $value
     * @param string|null $language
     * @param string|null $datatype
     */
    protected function addLiteral(array &$node, string $predicate, ?string $value, ?string $language = null, ?string $datatype = null): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $literal = ['@value' => $value];
        if ($language) {
            $literal['@language'] = $language;
        }
        if ($datatype) {
            $literal['@type'] = $datatype;
        }

        if (isset($node[$predicate])) {
            if (!is_array($node[$predicate]) || isset($node[$predicate]['@value'])) {
                $node[$predicate] = [$node[$predicate]];
            }
            $node[$predicate][] = $literal;
        } else {
            $node[$predicate] = $literal;
        }
    }

    /**
     * Add resource reference to graph
     *
     * @param array  $node
     * @param string $predicate
     * @param string $uri
     */
    protected function addResource(array &$node, string $predicate, string $uri): void
    {
        if (empty($uri)) {
            return;
        }

        $reference = ['@id' => $uri];

        if (isset($node[$predicate])) {
            if (!is_array($node[$predicate]) || isset($node[$predicate]['@id'])) {
                $node[$predicate] = [$node[$predicate]];
            }
            $node[$predicate][] = $reference;
        } else {
            $node[$predicate] = $reference;
        }
    }

    /**
     * Get level of description
     *
     * @param mixed $resource
     *
     * @return string|null
     */
    protected function getLevelOfDescription($resource): ?string
    {
        try {
            if (method_exists($resource, 'getLevelOfDescription')) {
                $level = $resource->getLevelOfDescription();
                if (is_object($level) && method_exists($level, '__toString')) {
                    return (string) $level;
                }
                if (is_string($level)) {
                    return $level;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * Get identifier
     *
     * @param mixed $resource
     *
     * @return string
     */
    protected function getIdentifier($resource): string
    {
        try {
            if (method_exists($resource, 'getIdentifier')) {
                $id = $resource->getIdentifier();
                if ($id) {
                    return $id;
                }
            }
            if (isset($resource->id)) {
                return (string) $resource->id;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 'unknown';
    }

    /**
     * Get creators for resource
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getCreators($resource): array
    {
        $creators = [];

        try {
            if (method_exists($resource, 'getCreators')) {
                $creatorObjects = $resource->getCreators();
                foreach ($creatorObjects as $creator) {
                    $name = null;
                    if (method_exists($creator, 'getAuthorizedFormOfName')) {
                        $name = $creator->getAuthorizedFormOfName(['culture' => 'en']);
                    }
                    $creators[] = [
                        'name' => $name,
                        'type' => method_exists($creator, 'getEntityType') ? (string) $creator->getEntityType() : null,
                        'id' => $creator->id ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $creators;
    }

    /**
     * Get subjects for resource
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getSubjects($resource): array
    {
        $subjects = [];

        try {
            if (method_exists($resource, 'getSubjectAccessPoints')) {
                $points = $resource->getSubjectAccessPoints();
                foreach ($points as $point) {
                    if (method_exists($point, 'getTerm')) {
                        $term = $point->getTerm();
                        if ($term && method_exists($term, 'getName')) {
                            $subjects[] = $term->getName(['culture' => 'en']);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return array_filter($subjects);
    }

    /**
     * Get places for resource
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getPlaces($resource): array
    {
        $places = [];

        try {
            if (method_exists($resource, 'getPlaceAccessPoints')) {
                $points = $resource->getPlaceAccessPoints();
                foreach ($points as $point) {
                    if (method_exists($point, 'getTerm')) {
                        $term = $point->getTerm();
                        if ($term && method_exists($term, 'getName')) {
                            $places[] = $term->getName(['culture' => 'en']);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return array_filter($places);
    }

    /**
     * Get repository for resource
     *
     * @param mixed $resource
     *
     * @return array|null
     */
    protected function getRepository($resource): ?array
    {
        try {
            if (method_exists($resource, 'getRepository')) {
                $repo = $resource->getRepository();
                if ($repo) {
                    $name = null;
                    if (method_exists($repo, 'getAuthorizedFormOfName')) {
                        $name = $repo->getAuthorizedFormOfName(['culture' => 'en']);
                    }
                    return [
                        'name' => $name,
                        'identifier' => method_exists($repo, 'getIdentifier') ? $repo->getIdentifier() : null,
                        'id' => $repo->id ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }
}
