<?php

/**
 * AbstractXmlExporter - Base class for XML-based metadata exporters
 *
 * Provides common XML generation functionality for formats like:
 * EAD3, LIDO, MARC21, VRA Core, PBCore, EBUCore, PREMIS
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

use AhgMetadataExport\Contracts\ExporterInterface;

abstract class AbstractXmlExporter implements ExporterInterface
{
    /**
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * @var array XML namespaces [prefix => uri]
     */
    protected $namespaces = [];

    /**
     * @var string Primary namespace URI
     */
    protected $primaryNamespace = '';

    /**
     * @var string Primary namespace prefix
     */
    protected $primaryPrefix = '';

    /**
     * @var array Export options
     */
    protected $options = [];

    /**
     * @var string Base URI for identifiers
     */
    protected $baseUri = '';

    /**
     * Constructor
     *
     * @param string|null $baseUri Base URI for identifiers
     */
    public function __construct(?string $baseUri = null)
    {
        $this->baseUri = $baseUri ?? \sfConfig::get('app_siteBaseUrl', 'https://example.org');
        $this->initializeNamespaces();
    }

    /**
     * Initialize namespaces for this format
     * Override in subclasses to define format-specific namespaces
     */
    abstract protected function initializeNamespaces(): void;

    /**
     * Build the XML document for a resource
     *
     * @param mixed $resource
     *
     * @return \DOMDocument
     */
    abstract protected function buildDocument($resource): \DOMDocument;

    /**
     * {@inheritdoc}
     */
    public function export($resource, array $options = []): string
    {
        $this->options = $this->validateOptions($options);
        $this->initializeDom();

        $doc = $this->buildDocument($resource);

        return $this->formatOutput($doc);
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
     * {@inheritdoc}
     */
    public function getMimeType(): string
    {
        return 'application/xml';
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension(): string
    {
        return 'xml';
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
            'validateOutput' => false,
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
        foreach (['includeDigitalObjects', 'includeDrafts', 'includeChildren', 'prettyPrint', 'validateOutput'] as $key) {
            if (isset($validated[$key])) {
                $validated[$key] = (bool) $validated[$key];
            }
        }

        // Ensure maxDepth is integer
        $validated['maxDepth'] = (int) ($validated['maxDepth'] ?? 0);

        return $validated;
    }

    /**
     * Initialize DOM document
     */
    protected function initializeDom(): void
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = $this->options['prettyPrint'] ?? true;
    }

    /**
     * Create element with optional value and namespace
     *
     * @param string      $name      Element name (can include prefix:)
     * @param string|null $value     Element text content
     * @param string|null $namespace Namespace URI (or null for primary namespace)
     *
     * @return \DOMElement
     */
    protected function createElement(string $name, ?string $value = null, ?string $namespace = null): \DOMElement
    {
        $ns = $namespace ?? $this->primaryNamespace;

        if ($ns) {
            $element = $this->dom->createElementNS($ns, $name);
        } else {
            $element = $this->dom->createElement($name);
        }

        if (null !== $value && '' !== $value) {
            $element->appendChild($this->dom->createTextNode($this->escapeXml($value)));
        }

        return $element;
    }

    /**
     * Create element with CDATA content
     *
     * @param string $name  Element name
     * @param string $value CDATA content
     *
     * @return \DOMElement
     */
    protected function createCdataElement(string $name, string $value): \DOMElement
    {
        $element = $this->createElement($name);
        $element->appendChild($this->dom->createCDATASection($value));

        return $element;
    }

    /**
     * Add attribute to element
     *
     * @param \DOMElement $element
     * @param string      $name
     * @param string      $value
     * @param string|null $namespace
     */
    protected function addAttribute(\DOMElement $element, string $name, string $value, ?string $namespace = null): void
    {
        if ($namespace) {
            $element->setAttributeNS($namespace, $name, $value);
        } else {
            $element->setAttribute($name, $value);
        }
    }

    /**
     * Add namespace declaration to root element
     *
     * @param \DOMElement $root
     * @param string      $prefix
     * @param string      $uri
     */
    protected function addNamespace(\DOMElement $root, string $prefix, string $uri): void
    {
        if ($prefix) {
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, $uri);
        } else {
            $root->setAttribute('xmlns', $uri);
        }
    }

    /**
     * Escape XML special characters
     *
     * @param string $value
     *
     * @return string
     */
    protected function escapeXml(string $value): string
    {
        // Remove control characters except tab, newline, carriage return
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format and validate output
     *
     * @param \DOMDocument $doc
     *
     * @return string
     */
    protected function formatOutput(\DOMDocument $doc): string
    {
        $xml = $doc->saveXML();

        // Use Qubit::tidyXml() if available and validation requested
        if ($this->options['validateOutput'] && class_exists('Qubit') && method_exists('Qubit', 'tidyXml')) {
            $xml = \Qubit::tidyXml($xml);
        }

        return $xml;
    }

    /**
     * Get text value from resource property, handling i18n
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
            // Handle objects with __toString
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
     * Get resource identifier
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
            if (method_exists($resource, 'getSlug')) {
                $slug = $resource->getSlug();
                if ($slug) {
                    return $slug;
                }
            }
            if (property_exists($resource, 'id') && $resource->id) {
                return (string) $resource->id;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 'unknown';
    }

    /**
     * Get resource URI
     *
     * @param mixed $resource
     *
     * @return string
     */
    protected function getResourceUri($resource): string
    {
        $slug = $this->getIdentifier($resource);
        try {
            if (method_exists($resource, 'getSlug')) {
                $slug = $resource->getSlug() ?? $slug;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return rtrim($this->baseUri, '/').'/'.ltrim($slug, '/');
    }

    /**
     * Format date for XML
     *
     * @param mixed  $date
     * @param string $format
     *
     * @return string|null
     */
    protected function formatDate($date, string $format = 'Y-m-d'): ?string
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof \DateTime) {
            return $date->format($format);
        }

        if (is_string($date)) {
            try {
                $dt = new \DateTime($date);

                return $dt->format($format);
            } catch (\Exception $e) {
                return $date; // Return as-is if not parseable
            }
        }

        return null;
    }

    /**
     * Build child hierarchy recursively
     *
     * @param mixed        $parent
     * @param \DOMElement  $parentElement
     * @param int          $depth
     * @param callable     $buildChild Function to build child element
     */
    protected function buildHierarchy($parent, \DOMElement $parentElement, int $depth, callable $buildChild): void
    {
        if (!$this->options['includeChildren']) {
            return;
        }

        $maxDepth = $this->options['maxDepth'];
        if ($maxDepth > 0 && $depth >= $maxDepth) {
            return;
        }

        $children = $this->getChildren($parent);
        foreach ($children as $child) {
            $childElement = $buildChild($child, $depth + 1);
            if ($childElement) {
                $parentElement->appendChild($childElement);
            }
        }
    }

    /**
     * Get child resources
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getChildren($resource): array
    {
        try {
            // For QubitInformationObject
            if (method_exists($resource, 'getChildren')) {
                $children = $resource->getChildren();
                if (method_exists($children, 'toArray')) {
                    return $children->toArray() ?? [];
                }

                return is_array($children) ? $children : [];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return [];
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
     * Get digital objects for resource
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getDigitalObjects($resource): array
    {
        if (!$this->options['includeDigitalObjects']) {
            return [];
        }

        try {
            if (method_exists($resource, 'getDigitalObjects')) {
                $objects = $resource->getDigitalObjects();
                if (method_exists($objects, 'toArray')) {
                    return $objects->toArray() ?? [];
                }

                return is_array($objects) ? $objects : [];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return [];
    }

    /**
     * Get date range from resource
     *
     * @param mixed $resource
     *
     * @return array [start, end, display]
     */
    protected function getDateRange($resource): array
    {
        $start = null;
        $end = null;
        $display = null;

        // Try events for date range (primary method for AtoM)
        if (method_exists($resource, 'getDates')) {
            try {
                $dates = $resource->getDates();
                foreach ($dates as $dateObj) {
                    if (property_exists($dateObj, 'startDate') && $dateObj->startDate) {
                        $start = $dateObj->startDate;
                    }
                    if (property_exists($dateObj, 'endDate') && $dateObj->endDate) {
                        $end = $dateObj->endDate;
                    }
                    // Get display date from event
                    if (method_exists($dateObj, 'getDate')) {
                        $display = $dateObj->getDate(['culture' => 'en']);
                    }
                    break; // Use first date
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        return [
            'start' => $start,
            'end' => $end,
            'display' => $display,
        ];
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
                    $type = null;
                    if (method_exists($creator, 'getEntityType')) {
                        $type = (string) $creator->getEntityType();
                    }
                    $creators[] = [
                        'name' => $name,
                        'type' => $type,
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
                            $name = $term->getName(['culture' => 'en']);
                            if ($name) {
                                $subjects[] = $name;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $subjects;
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
                            $name = $term->getName(['culture' => 'en']);
                            if ($name) {
                                $places[] = $name;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $places;
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
                    $identifier = null;
                    if (method_exists($repo, 'getIdentifier')) {
                        $identifier = $repo->getIdentifier();
                    }

                    return [
                        'name' => $name,
                        'identifier' => $identifier,
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
