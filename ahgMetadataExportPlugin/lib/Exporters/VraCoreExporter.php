<?php

/**
 * VraCoreExporter - VRA Core 4 (Visual Resources Association) Exporter
 *
 * Exports visual resource descriptions to VRA Core 4 XML format.
 * Supports both work and image records.
 *
 * @see https://www.loc.gov/standards/vracore/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class VraCoreExporter extends AbstractXmlExporter
{
    /**
     * VRA Core namespace
     */
    public const NS_VRA = 'http://www.vraweb.org/vracore4.htm';

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_VRA;
        $this->primaryPrefix = 'vra';
        $this->namespaces = [
            'vra' => self::NS_VRA,
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'vra-core';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'VRA Core 4';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Visual';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDocument($resource): \DOMDocument
    {
        // Create root VRA element
        $vra = $this->dom->createElementNS(self::NS_VRA, 'vra:vra');
        $this->dom->appendChild($vra);

        // Add namespace declarations
        $this->addNamespace($vra, 'xsi', $this->namespaces['xsi']);
        $vra->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_VRA.' http://www.loc.gov/standards/vracore/vra.xsd'
        );

        // Build work element (the cultural/artistic object)
        $work = $this->buildWork($resource);
        $vra->appendChild($work);

        // Build image elements for digital objects
        if ($this->options['includeDigitalObjects']) {
            $digitalObjects = $this->getDigitalObjects($resource);
            foreach ($digitalObjects as $do) {
                $image = $this->buildImage($do, $resource);
                $vra->appendChild($image);
            }
        }

        return $this->dom;
    }

    /**
     * Build work element
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildWork($resource): \DOMElement
    {
        $work = $this->dom->createElementNS(self::NS_VRA, 'vra:work');

        $workId = 'w_'.$this->getIdentifier($resource);
        $work->setAttribute('id', $workId);
        $work->setAttribute('refid', $this->getIdentifier($resource));

        // Agent set (creators)
        $this->addAgentSet($work, $resource);

        // Date set
        $this->addDateSet($work, $resource);

        // Description set
        $this->addDescriptionSet($work, $resource);

        // Location set
        $this->addLocationSet($work, $resource);

        // Measurements set
        $this->addMeasurementsSet($work, $resource);

        // Rights set
        $this->addRightsSet($work, $resource);

        // Subject set
        $this->addSubjectSet($work, $resource);

        // Title set
        $this->addTitleSet($work, $resource);

        // Work type set
        $this->addWorkTypeSet($work, $resource);

        return $work;
    }

    /**
     * Build image element for a digital object
     *
     * @param mixed $digitalObject
     * @param mixed $parentResource
     *
     * @return \DOMElement
     */
    protected function buildImage($digitalObject, $parentResource): \DOMElement
    {
        $image = $this->dom->createElementNS(self::NS_VRA, 'vra:image');

        $imageId = 'i_'.($digitalObject->id ?? uniqid());
        $workId = 'w_'.$this->getIdentifier($parentResource);

        $image->setAttribute('id', $imageId);
        $image->setAttribute('refid', (string) ($digitalObject->id ?? 'unknown'));

        // Relation to work
        $relationSet = $this->dom->createElementNS(self::NS_VRA, 'vra:relationSet');
        $image->appendChild($relationSet);

        $relation = $this->dom->createElementNS(self::NS_VRA, 'vra:relation');
        $relation->setAttribute('type', 'imageOf');
        $relation->setAttribute('relids', $workId);
        $relation->appendChild($this->dom->createTextNode($this->getValue($parentResource, 'title') ?? 'Work'));
        $relationSet->appendChild($relation);

        // Title set (filename)
        if (isset($digitalObject->name)) {
            $titleSet = $this->dom->createElementNS(self::NS_VRA, 'vra:titleSet');
            $image->appendChild($titleSet);

            $title = $this->dom->createElementNS(self::NS_VRA, 'vra:title');
            $title->setAttribute('type', 'descriptive');
            $title->appendChild($this->dom->createTextNode($digitalObject->name));
            $titleSet->appendChild($title);
        }

        // Measurements set (dimensions)
        if (isset($digitalObject->width) && isset($digitalObject->height)) {
            $measurementsSet = $this->dom->createElementNS(self::NS_VRA, 'vra:measurementsSet');
            $image->appendChild($measurementsSet);

            $measurements = $this->dom->createElementNS(self::NS_VRA, 'vra:measurements');
            $measurements->setAttribute('type', 'width');
            $measurements->setAttribute('unit', 'px');
            $measurements->appendChild($this->dom->createTextNode((string) $digitalObject->width));
            $measurementsSet->appendChild($measurements);

            $measurements = $this->dom->createElementNS(self::NS_VRA, 'vra:measurements');
            $measurements->setAttribute('type', 'height');
            $measurements->setAttribute('unit', 'px');
            $measurements->appendChild($this->dom->createTextNode((string) $digitalObject->height));
            $measurementsSet->appendChild($measurements);
        }

        // Description (mime type / format)
        if (isset($digitalObject->mimeType)) {
            $descriptionSet = $this->dom->createElementNS(self::NS_VRA, 'vra:descriptionSet');
            $image->appendChild($descriptionSet);

            $description = $this->dom->createElementNS(self::NS_VRA, 'vra:description');
            $description->appendChild($this->dom->createTextNode('Format: '.$digitalObject->mimeType));
            $descriptionSet->appendChild($description);
        }

        // Location (URL)
        if (isset($digitalObject->path)) {
            $locationSet = $this->dom->createElementNS(self::NS_VRA, 'vra:locationSet');
            $image->appendChild($locationSet);

            $location = $this->dom->createElementNS(self::NS_VRA, 'vra:location');
            $location->setAttribute('type', 'repository');
            $locationSet->appendChild($location);

            $refid = $this->dom->createElementNS(self::NS_VRA, 'vra:refid');
            $refid->setAttribute('type', 'URI');
            $refid->appendChild($this->dom->createTextNode(
                rtrim($this->baseUri, '/').'/'.ltrim($digitalObject->path, '/')
            ));
            $location->appendChild($refid);
        }

        return $image;
    }

    /**
     * Add agent set (creators)
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addAgentSet(\DOMElement $work, $resource): void
    {
        $creators = $this->getCreators($resource);

        if (empty($creators)) {
            return;
        }

        $agentSet = $this->dom->createElementNS(self::NS_VRA, 'vra:agentSet');
        $work->appendChild($agentSet);

        foreach ($creators as $creator) {
            if (!$creator['name']) {
                continue;
            }

            $agent = $this->dom->createElementNS(self::NS_VRA, 'vra:agent');
            $agentSet->appendChild($agent);

            // Name
            $name = $this->dom->createElementNS(self::NS_VRA, 'vra:name');
            $name->setAttribute('type', 'personal');
            $name->appendChild($this->dom->createTextNode($creator['name']));
            $agent->appendChild($name);

            // Role
            $role = $this->dom->createElementNS(self::NS_VRA, 'vra:role');
            $role->appendChild($this->dom->createTextNode('creator'));
            $agent->appendChild($role);
        }
    }

    /**
     * Add date set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addDateSet(\DOMElement $work, $resource): void
    {
        $dateRange = $this->getDateRange($resource);

        if (!$dateRange['display'] && !$dateRange['start']) {
            return;
        }

        $dateSet = $this->dom->createElementNS(self::NS_VRA, 'vra:dateSet');
        $work->appendChild($dateSet);

        $date = $this->dom->createElementNS(self::NS_VRA, 'vra:date');
        $date->setAttribute('type', 'creation');
        $dateSet->appendChild($date);

        if ($dateRange['start']) {
            $earliestDate = $this->dom->createElementNS(self::NS_VRA, 'vra:earliestDate');
            $earliestDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['start'], 'Y-m-d')));
            $date->appendChild($earliestDate);
        }

        if ($dateRange['end']) {
            $latestDate = $this->dom->createElementNS(self::NS_VRA, 'vra:latestDate');
            $latestDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['end'], 'Y-m-d')));
            $date->appendChild($latestDate);
        } elseif ($dateRange['start']) {
            $latestDate = $this->dom->createElementNS(self::NS_VRA, 'vra:latestDate');
            $latestDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['start'], 'Y-m-d')));
            $date->appendChild($latestDate);
        }
    }

    /**
     * Add description set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addDescriptionSet(\DOMElement $work, $resource): void
    {
        $scopeContent = $this->getValue($resource, 'scopeAndContent');

        if (!$scopeContent) {
            return;
        }

        $descriptionSet = $this->dom->createElementNS(self::NS_VRA, 'vra:descriptionSet');
        $work->appendChild($descriptionSet);

        $description = $this->dom->createElementNS(self::NS_VRA, 'vra:description');
        $description->appendChild($this->dom->createTextNode($scopeContent));
        $descriptionSet->appendChild($description);
    }

    /**
     * Add location set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addLocationSet(\DOMElement $work, $resource): void
    {
        $repo = $this->getRepository($resource);
        $places = $this->getPlaces($resource);

        if ((!$repo || !$repo['name']) && empty($places)) {
            return;
        }

        $locationSet = $this->dom->createElementNS(self::NS_VRA, 'vra:locationSet');
        $work->appendChild($locationSet);

        // Repository location
        if ($repo && $repo['name']) {
            $location = $this->dom->createElementNS(self::NS_VRA, 'vra:location');
            $location->setAttribute('type', 'repository');
            $locationSet->appendChild($location);

            $name = $this->dom->createElementNS(self::NS_VRA, 'vra:name');
            $name->setAttribute('type', 'corporate');
            $name->appendChild($this->dom->createTextNode($repo['name']));
            $location->appendChild($name);

            // Add refid (identifier)
            $refid = $this->dom->createElementNS(self::NS_VRA, 'vra:refid');
            $refid->setAttribute('type', 'accession');
            $refid->appendChild($this->dom->createTextNode($this->getIdentifier($resource)));
            $location->appendChild($refid);
        }

        // Geographic locations
        foreach ($places as $place) {
            $location = $this->dom->createElementNS(self::NS_VRA, 'vra:location');
            $location->setAttribute('type', 'creation');
            $locationSet->appendChild($location);

            $name = $this->dom->createElementNS(self::NS_VRA, 'vra:name');
            $name->setAttribute('type', 'geographic');
            $name->appendChild($this->dom->createTextNode($place));
            $location->appendChild($name);
        }
    }

    /**
     * Add measurements set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addMeasurementsSet(\DOMElement $work, $resource): void
    {
        $extent = $this->getValue($resource, 'extentAndMedium');

        if (!$extent) {
            return;
        }

        $measurementsSet = $this->dom->createElementNS(self::NS_VRA, 'vra:measurementsSet');
        $work->appendChild($measurementsSet);

        // Display element for free-text extent
        $display = $this->dom->createElementNS(self::NS_VRA, 'vra:display');
        $display->appendChild($this->dom->createTextNode($extent));
        $measurementsSet->appendChild($display);
    }

    /**
     * Add rights set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addRightsSet(\DOMElement $work, $resource): void
    {
        $accessConditions = $this->getValue($resource, 'accessConditions');
        $reproConditions = $this->getValue($resource, 'reproductionConditions');

        if (!$accessConditions && !$reproConditions) {
            return;
        }

        $rightsSet = $this->dom->createElementNS(self::NS_VRA, 'vra:rightsSet');
        $work->appendChild($rightsSet);

        if ($accessConditions) {
            $rights = $this->dom->createElementNS(self::NS_VRA, 'vra:rights');
            $rights->setAttribute('type', 'publicDomain');
            $rightsSet->appendChild($rights);

            $text = $this->dom->createElementNS(self::NS_VRA, 'vra:text');
            $text->appendChild($this->dom->createTextNode($accessConditions));
            $rights->appendChild($text);
        }

        if ($reproConditions) {
            $rights = $this->dom->createElementNS(self::NS_VRA, 'vra:rights');
            $rights->setAttribute('type', 'copyrighted');
            $rightsSet->appendChild($rights);

            $text = $this->dom->createElementNS(self::NS_VRA, 'vra:text');
            $text->appendChild($this->dom->createTextNode($reproConditions));
            $rights->appendChild($text);
        }
    }

    /**
     * Add subject set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addSubjectSet(\DOMElement $work, $resource): void
    {
        $subjects = $this->getSubjects($resource);

        if (empty($subjects)) {
            return;
        }

        $subjectSet = $this->dom->createElementNS(self::NS_VRA, 'vra:subjectSet');
        $work->appendChild($subjectSet);

        foreach ($subjects as $subject) {
            $subjectEl = $this->dom->createElementNS(self::NS_VRA, 'vra:subject');
            $subjectSet->appendChild($subjectEl);

            $term = $this->dom->createElementNS(self::NS_VRA, 'vra:term');
            $term->setAttribute('type', 'descriptiveTopic');
            $term->appendChild($this->dom->createTextNode($subject));
            $subjectEl->appendChild($term);
        }
    }

    /**
     * Add title set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addTitleSet(\DOMElement $work, $resource): void
    {
        $title = $this->getValue($resource, 'title');

        if (!$title) {
            return;
        }

        $titleSet = $this->dom->createElementNS(self::NS_VRA, 'vra:titleSet');
        $work->appendChild($titleSet);

        $titleEl = $this->dom->createElementNS(self::NS_VRA, 'vra:title');
        $titleEl->setAttribute('type', 'descriptive');
        $titleEl->setAttribute('pref', 'true');
        $titleEl->appendChild($this->dom->createTextNode($title));
        $titleSet->appendChild($titleEl);
    }

    /**
     * Add work type set
     *
     * @param \DOMElement $work
     * @param mixed       $resource
     */
    protected function addWorkTypeSet(\DOMElement $work, $resource): void
    {
        $level = $this->getLevelOfDescription($resource);

        $worktypeSet = $this->dom->createElementNS(self::NS_VRA, 'vra:worktypeSet');
        $work->appendChild($worktypeSet);

        $worktype = $this->dom->createElementNS(self::NS_VRA, 'vra:worktype');
        $worktype->appendChild($this->dom->createTextNode($this->mapLevelToWorkType($level)));
        $worktypeSet->appendChild($worktype);
    }

    /**
     * Map level of description to VRA work type
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function mapLevelToWorkType(?string $level): string
    {
        $map = [
            'Fonds' => 'collection',
            'Collection' => 'collection',
            'Series' => 'series',
            'File' => 'album',
            'Item' => 'image',
        ];

        return $map[$level] ?? 'image';
    }
}
