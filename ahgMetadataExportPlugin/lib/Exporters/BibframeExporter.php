<?php

/**
 * BibframeExporter - BIBFRAME (Bibliographic Framework) Exporter
 *
 * Exports bibliographic descriptions to BIBFRAME 2.0 RDF format.
 *
 * @see https://www.loc.gov/bibframe/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class BibframeExporter extends AbstractRdfExporter
{
    /**
     * BIBFRAME namespaces
     */
    public const NS_BF = 'http://id.loc.gov/ontologies/bibframe/';
    public const NS_BFLC = 'http://id.loc.gov/ontologies/bflc/';

    /**
     * {@inheritdoc}
     */
    protected function initializePrefixes(): void
    {
        $this->prefixes = [
            'bf' => self::NS_BF,
            'bflc' => self::NS_BFLC,
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
            'madsrdf' => 'http://www.loc.gov/mads/rdf/v1#',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContext(): void
    {
        $this->context = [
            'bf' => self::NS_BF,
            'bflc' => self::NS_BFLC,
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'bibframe';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'BIBFRAME';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Libraries';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildGraph($resource): array
    {
        $graph = [];

        // Build Work
        $work = $this->buildWork($resource);

        // Build Instance
        $instance = $this->buildInstance($resource);

        // Build Items (one for each digital object)
        $items = [];
        if ($this->options['includeDigitalObjects']) {
            $digitalObjects = $this->getDigitalObjects($resource);
            foreach ($digitalObjects as $do) {
                $items[] = $this->buildItem($do, $resource);
            }
        }

        // Combine into graph
        $graph = $work;
        $graph['bf:hasInstance'] = $instance;

        if (!empty($items)) {
            $graph['bf:hasInstance']['bf:hasItem'] = $items;
        }

        return $graph;
    }

    /**
     * Build BIBFRAME Work
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function buildWork($resource): array
    {
        $work = [
            '@id' => $this->createUri($resource, 'work'),
            '@type' => 'bf:Work',
        ];

        // Work type based on level
        $level = $this->getLevelOfDescription($resource);
        $workType = $this->getWorkType($level);
        if ('bf:Work' !== $workType) {
            $work['@type'] = [$work['@type'], $workType];
        }

        // Title
        $title = $this->getValue($resource, 'title');
        if ($title) {
            $work['bf:title'] = [
                '@type' => 'bf:Title',
                'bf:mainTitle' => $title,
            ];
        }

        // Contribution (creators)
        $this->addContributions($work, $resource);

        // Subject
        $this->addSubjectsBibframe($work, $resource);

        // Note (scope and content)
        $scopeContent = $this->getValue($resource, 'scopeAndContent');
        if ($scopeContent) {
            $work['bf:summary'] = [
                '@type' => 'bf:Summary',
                'rdfs:label' => $scopeContent,
            ];
        }

        // Language
        $this->addLanguagesBibframe($work, $resource);

        // Origin info (dates)
        $this->addOriginInfo($work, $resource);

        // Genre/Form
        if ($level) {
            $work['bf:genreForm'] = [
                '@type' => 'bf:GenreForm',
                'rdfs:label' => $level,
            ];
        }

        // Classification
        $identifier = $this->getIdentifier($resource);
        if ($identifier) {
            $work['bf:classification'] = [
                '@type' => 'bf:ClassificationLcc',
                'bf:classificationPortion' => $identifier,
            ];
        }

        return $work;
    }

    /**
     * Build BIBFRAME Instance
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function buildInstance($resource): array
    {
        $instance = [
            '@id' => $this->createUri($resource, 'instance'),
            '@type' => 'bf:Instance',
            'bf:instanceOf' => ['@id' => $this->createUri($resource, 'work')],
        ];

        // Title (same as work)
        $title = $this->getValue($resource, 'title');
        if ($title) {
            $instance['bf:title'] = [
                '@type' => 'bf:Title',
                'bf:mainTitle' => $title,
            ];
        }

        // Provision activity (publication/production)
        $this->addProvisionActivity($instance, $resource);

        // Extent
        $extent = $this->getValue($resource, 'extentAndMedium');
        if ($extent) {
            $instance['bf:extent'] = [
                '@type' => 'bf:Extent',
                'rdfs:label' => $extent,
            ];
        }

        // Identifier
        $identifier = $this->getIdentifier($resource);
        if ($identifier) {
            $instance['bf:identifiedBy'] = [
                '@type' => 'bf:Local',
                'rdf:value' => $identifier,
            ];
        }

        // Note (arrangement)
        $arrangement = $this->getValue($resource, 'arrangement');
        if ($arrangement) {
            $instance['bf:arrangement'] = [
                '@type' => 'bf:Arrangement',
                'rdfs:label' => $arrangement,
            ];
        }

        // Use and access policy
        $this->addUsageAndAccessPolicy($instance, $resource);

        return $instance;
    }

    /**
     * Build BIBFRAME Item
     *
     * @param mixed $digitalObject
     * @param mixed $parentResource
     *
     * @return array
     */
    protected function buildItem($digitalObject, $parentResource): array
    {
        $item = [
            '@id' => $this->baseUri.'/item/'.($digitalObject->id ?? uniqid()),
            '@type' => 'bf:Item',
            'bf:itemOf' => ['@id' => $this->createUri($parentResource, 'instance')],
        ];

        // Held by
        $repo = $this->getRepository($parentResource);
        if ($repo && $repo['name']) {
            $item['bf:heldBy'] = [
                '@type' => 'bf:Agent',
                'rdfs:label' => $repo['name'],
            ];
        }

        // Electronic locator
        if (isset($digitalObject->path)) {
            $item['bf:electronicLocator'] = [
                '@id' => rtrim($this->baseUri, '/').'/'.ltrim($digitalObject->path, '/'),
            ];
        }

        // Note (filename)
        if (isset($digitalObject->name)) {
            $item['bf:note'] = [
                '@type' => 'bf:Note',
                'rdfs:label' => $digitalObject->name,
            ];
        }

        return $item;
    }

    /**
     * Add contributions (creators)
     *
     * @param array $work
     * @param mixed $resource
     */
    protected function addContributions(array &$work, $resource): void
    {
        $creators = $this->getCreators($resource);

        if (empty($creators)) {
            return;
        }

        $work['bf:contribution'] = [];

        foreach ($creators as $index => $creator) {
            if (!$creator['name']) {
                continue;
            }

            $contribution = [
                '@type' => 'bf:Contribution',
                'bf:agent' => [
                    '@type' => $this->mapAgentType($creator['type'] ?? null),
                    'rdfs:label' => $creator['name'],
                ],
            ];

            // First creator is primary
            if (0 === $index) {
                $contribution['bf:role'] = [
                    '@type' => 'bf:Role',
                    'rdfs:label' => 'creator',
                ];
            } else {
                $contribution['bf:role'] = [
                    '@type' => 'bf:Role',
                    'rdfs:label' => 'contributor',
                ];
            }

            $work['bf:contribution'][] = $contribution;
        }
    }

    /**
     * Map creator type to BIBFRAME agent type
     *
     * @param string|null $type
     *
     * @return string
     */
    protected function mapAgentType(?string $type): string
    {
        switch ($type) {
            case 'Corporate body':
            case 'corporate':
                return 'bf:Organization';
            case 'Family':
            case 'family':
                return 'bf:Family';
            case 'Person':
            case 'person':
            default:
                return 'bf:Person';
        }
    }

    /**
     * Add subjects
     *
     * @param array $work
     * @param mixed $resource
     */
    protected function addSubjectsBibframe(array &$work, $resource): void
    {
        $subjects = $this->getSubjects($resource);
        $places = $this->getPlaces($resource);

        if (empty($subjects) && empty($places)) {
            return;
        }

        $work['bf:subject'] = [];

        foreach ($subjects as $subject) {
            $work['bf:subject'][] = [
                '@type' => 'bf:Topic',
                'rdfs:label' => $subject,
            ];
        }

        foreach ($places as $place) {
            $work['bf:subject'][] = [
                '@type' => 'bf:Place',
                'rdfs:label' => $place,
            ];
        }
    }

    /**
     * Add languages
     *
     * @param array $work
     * @param mixed $resource
     */
    protected function addLanguagesBibframe(array &$work, $resource): void
    {
        if (!method_exists($resource, 'getLanguage')) {
            return;
        }

        $languages = $resource->getLanguage();
        if (empty($languages)) {
            return;
        }

        $work['bf:language'] = [];

        foreach ($languages as $lang) {
            $langNode = [
                '@type' => 'bf:Language',
            ];

            if (isset($lang->code)) {
                $langNode['@id'] = 'http://id.loc.gov/vocabulary/languages/'.$lang->code;
            }

            if (isset($lang->name)) {
                $langNode['rdfs:label'] = $lang->name;
            }

            $work['bf:language'][] = $langNode;
        }
    }

    /**
     * Add origin info (dates)
     *
     * @param array $work
     * @param mixed $resource
     */
    protected function addOriginInfo(array &$work, $resource): void
    {
        $dateRange = $this->getDateRange($resource);

        if (!$dateRange['display'] && !$dateRange['start']) {
            return;
        }

        $work['bf:originDate'] = $dateRange['display'] ?? $dateRange['start'];
    }

    /**
     * Add provision activity
     *
     * @param array $instance
     * @param mixed $resource
     */
    protected function addProvisionActivity(array &$instance, $resource): void
    {
        $dateRange = $this->getDateRange($resource);
        $places = $this->getPlaces($resource);
        $repo = $this->getRepository($resource);

        $provisionActivity = [
            '@type' => 'bf:ProvisionActivity',
        ];

        $hasContent = false;

        // Date
        if ($dateRange['display'] || $dateRange['start']) {
            $provisionActivity['bf:date'] = $dateRange['display'] ?? $dateRange['start'];
            $hasContent = true;
        }

        // Place
        if (!empty($places)) {
            $provisionActivity['bf:place'] = [
                '@type' => 'bf:Place',
                'rdfs:label' => $places[0],
            ];
            $hasContent = true;
        }

        // Agent (repository as producer for archival materials)
        if ($repo && $repo['name']) {
            $provisionActivity['bf:agent'] = [
                '@type' => 'bf:Agent',
                'rdfs:label' => $repo['name'],
            ];
            $hasContent = true;
        }

        if ($hasContent) {
            $instance['bf:provisionActivity'] = $provisionActivity;
        }
    }

    /**
     * Add usage and access policy
     *
     * @param array $instance
     * @param mixed $resource
     */
    protected function addUsageAndAccessPolicy(array &$instance, $resource): void
    {
        $accessConditions = $this->getValue($resource, 'accessConditions');
        $reproConditions = $this->getValue($resource, 'reproductionConditions');

        if ($accessConditions) {
            $instance['bf:usageAndAccessPolicy'] = [
                '@type' => 'bf:UsePolicy',
                'rdfs:label' => $accessConditions,
            ];
        }

        if ($reproConditions) {
            if (isset($instance['bf:usageAndAccessPolicy'])) {
                // Make it an array if we already have one
                $existing = $instance['bf:usageAndAccessPolicy'];
                $instance['bf:usageAndAccessPolicy'] = [
                    $existing,
                    [
                        '@type' => 'bf:UsePolicy',
                        'rdfs:label' => $reproConditions,
                    ],
                ];
            } else {
                $instance['bf:usageAndAccessPolicy'] = [
                    '@type' => 'bf:UsePolicy',
                    'rdfs:label' => $reproConditions,
                ];
            }
        }
    }

    /**
     * Get BIBFRAME work type based on level
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function getWorkType(?string $level): string
    {
        $map = [
            'Fonds' => 'bf:Collection',
            'Collection' => 'bf:Collection',
            'Series' => 'bf:Collection',
            'File' => 'bf:Work',
            'Item' => 'bf:Work',
        ];

        return $map[$level] ?? 'bf:Work';
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
                    $display = $dateObj->getDate(['culture' => 'en']);
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

        if (method_exists($resource, 'getDigitalObjects')) {
            return $resource->getDigitalObjects()->toArray() ?? [];
        }

        return [];
    }
}
