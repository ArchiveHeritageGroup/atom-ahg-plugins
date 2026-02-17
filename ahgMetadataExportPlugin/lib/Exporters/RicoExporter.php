<?php

/**
 * RicoExporter - RIC-O (Records in Contexts Ontology) Exporter
 *
 * Exports archival descriptions to RIC-O RDF format following the
 * ICA Records in Contexts standard.
 *
 * @see https://www.ica.org/standards/RiC/ontology
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class RicoExporter extends AbstractRdfExporter
{
    /**
     * RIC-O namespace
     */
    public const NS_RICO = 'https://www.ica.org/standards/RiC/ontology#';

    /**
     * Level of description mapping from ISAD(G) to RIC-O types
     */
    protected $levelMap = [
        'Fonds' => 'RecordSet',
        'fonds' => 'RecordSet',
        'Subfonds' => 'RecordSet',
        'subfonds' => 'RecordSet',
        'Collection' => 'RecordSet',
        'collection' => 'RecordSet',
        'Series' => 'RecordSet',
        'series' => 'RecordSet',
        'Subseries' => 'RecordSet',
        'subseries' => 'RecordSet',
        'File' => 'RecordSet',
        'file' => 'RecordSet',
        'Item' => 'Record',
        'item' => 'Record',
        'Part' => 'RecordPart',
        'Record group' => 'RecordSet',
    ];

    /**
     * {@inheritdoc}
     */
    protected function initializePrefixes(): void
    {
        $this->prefixes = [
            'rico' => self::NS_RICO,
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
            'owl' => 'http://www.w3.org/2002/07/owl#',
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'dc' => 'http://purl.org/dc/elements/1.1/',
            'dcterms' => 'http://purl.org/dc/terms/',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContext(): void
    {
        $this->context = [
            'rico' => self::NS_RICO,
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'rico';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'RIC-O';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Archives';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildGraph($resource): array
    {
        $graph = [];

        // Build main record/recordset
        $graph = $this->buildRecordNode($resource);

        // Add children if requested
        if ($this->options['includeChildren']) {
            $graph['rico:includesOrIncluded'] = $this->buildChildren($resource, 1);
        }

        return $graph;
    }

    /**
     * Build RIC-O record or recordset node
     *
     * @param mixed $resource
     * @param int   $depth
     *
     * @return array
     */
    protected function buildRecordNode($resource, int $depth = 0): array
    {
        $level = $this->getLevelOfDescription($resource);
        $type = $this->mapLevelToType($level);

        $node = [
            '@id' => $this->createUri($resource, 'record'),
            '@type' => 'rico:'.$type,
        ];

        // Identifier
        $identifier = $resource->identifier ?? $this->getIdentifier($resource);
        if ($identifier) {
            $node['rico:identifier'] = [
                '@type' => 'rico:Identifier',
                'rico:textualValue' => $identifier,
            ];
        }

        // Title
        $title = $this->getValue($resource, 'title');
        if ($title) {
            $node['rico:title'] = [
                '@type' => 'rico:Title',
                'rico:textualValue' => $title,
                'rico:isOrWasMainTitleOf' => ['@id' => $node['@id']],
            ];
        }

        // Name (alternative to title for display)
        if ($title) {
            $node['rico:name'] = $title;
        }

        // Description / Scope and Content
        $scopeContent = $this->getValue($resource, 'scopeAndContent');
        if ($scopeContent) {
            $node['rico:scopeAndContent'] = $scopeContent;
        }

        // Dates
        $this->addDates($node, $resource);

        // Extent
        $extent = $this->getValue($resource, 'extentAndMedium');
        if ($extent) {
            $node['rico:hasExtent'] = [
                '@type' => 'rico:RecordSetExtent',
                'rico:textualValue' => $extent,
            ];
        }

        // Creators
        $this->addCreators($node, $resource);

        // Access conditions
        $accessConditions = $this->getValue($resource, 'accessConditions');
        if ($accessConditions) {
            $node['rico:conditionsOfAccess'] = $accessConditions;
        }

        // Reproduction conditions
        $reproConditions = $this->getValue($resource, 'reproductionConditions');
        if ($reproConditions) {
            $node['rico:conditionsOfUse'] = $reproConditions;
        }

        // Language
        $this->addLanguages($node, $resource);

        // Repository (custodian)
        $this->addRepository($node, $resource);

        // Arrangement
        $arrangement = $this->getValue($resource, 'arrangement');
        if ($arrangement) {
            $node['rico:structure'] = $arrangement;
        }

        // Related materials
        $related = $this->getValue($resource, 'relatedUnitsOfDescription');
        if ($related) {
            $node['rico:isAssociatedWithRecord'] = [
                '@type' => 'rico:Record',
                'rdfs:comment' => $related,
            ];
        }

        // History (administrative/biographical)
        $this->addHistory($node, $resource);

        // Digital objects (instantiations)
        if ($this->options['includeDigitalObjects']) {
            $this->addInstantiations($node, $resource);
        }

        // Subjects
        $this->addSubjects($node, $resource);

        // Places
        $this->addPlacesRico($node, $resource);

        // Record state
        $status = null;
        try {
            $status = $resource->getPublicationStatus();
        } catch (\Exception $e) {
            // Ignore - publication status may not be available
        }
        if ($status) {
            $node['rico:hasRecordState'] = (string) $status;
        }

        return $node;
    }

    /**
     * Build children recursively
     *
     * @param mixed $resource
     * @param int   $depth
     *
     * @return array
     */
    protected function buildChildren($resource, int $depth): array
    {
        $children = [];

        // Check depth limit
        if ($this->options['maxDepth'] > 0 && $depth > $this->options['maxDepth']) {
            return $children;
        }

        $childResources = $this->getChildResources($resource);

        foreach ($childResources as $child) {
            // Check draft status
            if (!$this->options['includeDrafts']) {
                $status = null;
                try {
                    $status = $child->getPublicationStatus();
                } catch (\Exception $e) {
                    // Ignore - publication status may not be available
                }
                if ($status && 'Draft' === (string) $status) {
                    continue;
                }
            }

            $childNode = $this->buildRecordNode($child, $depth);

            // Add hierarchical relationship
            $childNode['rico:isOrWasIncludedIn'] = ['@id' => $this->createUri($resource, 'record')];

            // Recursively add grandchildren
            if ($this->options['includeChildren']) {
                $grandchildren = $this->buildChildren($child, $depth + 1);
                if (!empty($grandchildren)) {
                    $childNode['rico:includesOrIncluded'] = $grandchildren;
                }
            }

            $children[] = $childNode;
        }

        return $children;
    }

    /**
     * Add dates to node
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addDates(array &$node, $resource): void
    {
        $dateRange = $this->getDateRange($resource);

        if ($dateRange['start']) {
            $node['rico:beginningDate'] = [
                '@type' => 'rico:SingleDate',
                'rico:normalizedDateValue' => $this->formatDate($dateRange['start'], 'Y-m-d'),
            ];
        }

        if ($dateRange['end']) {
            $node['rico:endDate'] = [
                '@type' => 'rico:SingleDate',
                'rico:normalizedDateValue' => $this->formatDate($dateRange['end'], 'Y-m-d'),
            ];
        }

        // Date expression (display)
        if ($dateRange['display']) {
            $node['rico:date'] = [
                '@type' => 'rico:DateRange',
                'rico:expressedDate' => $dateRange['display'],
            ];
        }
    }

    /**
     * Add creators to node
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addCreators(array &$node, $resource): void
    {
        $creators = $this->getCreators($resource);

        foreach ($creators as $creator) {
            if (!$creator['name']) {
                continue;
            }

            $agentType = $this->mapCreatorType($creator['type'] ?? null);
            $agentUri = $this->createAgentUri($creator);

            $agentNode = [
                '@id' => $agentUri,
                '@type' => 'rico:'.$agentType,
                'rico:name' => $creator['name'],
            ];

            // Add creation relation
            if (!isset($node['rico:isOrWasCreatorOf'])) {
                $node['rico:isOrWasCreatorOf'] = [];
            }

            // Link agent to record
            $node['rico:hasOrHadCreator'][] = ['@id' => $agentUri];

            // Also embed agent details
            if (!isset($node['@graph'])) {
                $node['@graph'] = [];
            }
            $node['@graph'][] = $agentNode;
        }
    }

    /**
     * Map creator type to RIC-O agent type
     *
     * @param string|null $type
     *
     * @return string
     */
    protected function mapCreatorType(?string $type): string
    {
        switch ($type) {
            case 'Corporate body':
            case 'corporate':
                return 'CorporateBody';
            case 'Family':
            case 'family':
                return 'Family';
            case 'Person':
            case 'person':
            default:
                return 'Person';
        }
    }

    /**
     * Create URI for agent
     *
     * @param array $creator
     *
     * @return string
     */
    protected function createAgentUri(array $creator): string
    {
        if (isset($creator['id'])) {
            return $this->baseUri.'/agent/'.$creator['id'];
        }

        // Generate from name
        $slug = preg_replace('/[^a-zA-Z0-9]/', '_', $creator['name'] ?? 'agent');

        return $this->baseUri.'/agent/'.$slug;
    }

    /**
     * Add languages to node
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addLanguages(array &$node, $resource): void
    {
        if (method_exists($resource, 'getLanguage')) {
            $languages = $resource->getLanguage();
            foreach ($languages as $lang) {
                $langNode = [
                    '@type' => 'rico:Language',
                ];

                if (isset($lang->code)) {
                    $langNode['rico:identifier'] = $lang->code;
                }
                if (isset($lang->name)) {
                    $langNode['rico:name'] = $lang->name;
                }

                if (!isset($node['rico:hasOrHadLanguage'])) {
                    $node['rico:hasOrHadLanguage'] = [];
                }
                $node['rico:hasOrHadLanguage'][] = $langNode;
            }
        }
    }

    /**
     * Add repository to node
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addRepository(array &$node, $resource): void
    {
        $repo = $this->getRepository($resource);

        if (!$repo || !$repo['name']) {
            return;
        }

        $repoUri = $this->baseUri.'/repository/'.($repo['id'] ?? 'unknown');

        $node['rico:isOrWasHeldBy'] = [
            '@id' => $repoUri,
            '@type' => 'rico:CorporateBody',
            'rico:name' => $repo['name'],
        ];

        if ($repo['identifier']) {
            $node['rico:isOrWasHeldBy']['rico:identifier'] = $repo['identifier'];
        }
    }

    /**
     * Add history (administrative/biographical)
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addHistory(array &$node, $resource): void
    {
        // Get from creators
        $creators = $this->getCreators($resource);
        foreach ($creators as $creator) {
            if (isset($creator['id'])) {
                try {
                    $actor = \QubitActor::getById($creator['id']);
                    if ($actor) {
                        $history = $this->getValue($actor, 'history');
                        if ($history) {
                            $node['rico:history'] = $history;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }
    }

    /**
     * Add instantiations (digital objects)
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addInstantiations(array &$node, $resource): void
    {
        $digitalObjects = $this->getDigitalObjects($resource);

        if (empty($digitalObjects)) {
            return;
        }

        $node['rico:hasOrHadInstantiation'] = [];

        foreach ($digitalObjects as $do) {
            $instUri = $this->baseUri.'/instantiation/'.($do->id ?? uniqid());

            $instantiation = [
                '@id' => $instUri,
                '@type' => 'rico:Instantiation',
            ];

            // Name
            if (isset($do->name)) {
                $instantiation['rico:name'] = $do->name;
            }

            // Format
            if (isset($do->mimeType)) {
                $instantiation['rico:hasOrHadRepresentationType'] = $do->mimeType;
            }

            // Extent (file size)
            if (isset($do->byteSize)) {
                $instantiation['rico:hasExtent'] = [
                    '@type' => 'rico:InstantiationExtent',
                    'rico:textualValue' => $this->formatFileSize($do->byteSize),
                ];
            }

            // Identifier (checksum)
            if (isset($do->checksum)) {
                $instantiation['rico:identifier'] = [
                    '@type' => 'rico:Identifier',
                    'rico:textualValue' => $do->checksum,
                    'rico:type' => 'checksum',
                ];
            }

            // Link (URI)
            if (isset($do->path)) {
                $instantiation['rico:isAvailableAt'] = rtrim($this->baseUri, '/').'/'.ltrim($do->path, '/');
            }

            $node['rico:hasOrHadInstantiation'][] = $instantiation;
        }
    }

    /**
     * Add subjects to node
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addSubjects(array &$node, $resource): void
    {
        $subjects = $this->getSubjects($resource);

        if (empty($subjects)) {
            return;
        }

        $node['rico:hasSubject'] = [];

        foreach ($subjects as $subject) {
            $node['rico:hasSubject'][] = [
                '@type' => 'skos:Concept',
                'skos:prefLabel' => $subject,
            ];
        }
    }

    /**
     * Add places to node
     *
     * @param array $node
     * @param mixed $resource
     */
    protected function addPlacesRico(array &$node, $resource): void
    {
        $places = $this->getPlaces($resource);

        if (empty($places)) {
            return;
        }

        $node['rico:hasOrHadAllMembersWithPlace'] = [];

        foreach ($places as $place) {
            $node['rico:hasOrHadAllMembersWithPlace'][] = [
                '@type' => 'rico:Place',
                'rico:name' => $place,
            ];
        }
    }

    /**
     * Map ISAD(G) level to RIC-O type
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function mapLevelToType(?string $level): string
    {
        if (!$level) {
            return 'Record';
        }

        return $this->levelMap[$level] ?? 'Record';
    }

    /**
     * Get child resources
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getChildResources($resource): array
    {
        if (method_exists($resource, 'getChildren')) {
            return $resource->getChildren()->toArray() ?? [];
        }

        return [];
    }

    /**
     * Format file size for display
     *
     * @param int $bytes
     *
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            ++$i;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDateRange($resource): array
    {
        $start = null;
        $end = null;
        $display = null;

        try {
            $dates = $resource->getDates();
            foreach ($dates as $dateObj) {
                try {
                    $start = $dateObj->startDate;
                } catch (\Exception $e) {
                    // Ignore
                }
                try {
                    $end = $dateObj->endDate;
                } catch (\Exception $e) {
                    // Ignore
                }
                try {
                    $display = $dateObj->getDate(['culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()]);
                } catch (\Exception $e) {
                    // Ignore
                }
                break;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return [
            'start' => $start,
            'end' => $end,
            'display' => $display,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDigitalObjects($resource): array
    {
        if (!$this->options['includeDigitalObjects']) {
            return [];
        }

        try {
            $objects = $resource->getDigitalObjects();
            if (method_exists($objects, 'toArray')) {
                return $objects->toArray() ?? [];
            }
            return is_array($objects) ? $objects : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format date
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
                return $date;
            }
        }

        return null;
    }
}
