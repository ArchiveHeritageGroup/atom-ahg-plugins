<?php

/**
 * PbcoreExporter - PBCore (Public Broadcasting Metadata Dictionary) Exporter
 *
 * Exports media descriptions to PBCore 2.1 XML format.
 *
 * @see https://pbcore.org/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class PbcoreExporter extends AbstractXmlExporter
{
    /**
     * PBCore namespace
     */
    public const NS_PBCORE = 'http://www.pbcore.org/PBCore/PBCoreNamespace.html';

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_PBCORE;
        $this->primaryPrefix = 'pbcore';
        $this->namespaces = [
            'pbcore' => self::NS_PBCORE,
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'pbcore';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'PBCore';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Media';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDocument($resource): \DOMDocument
    {
        // Create root element
        $pbcoreDescriptionDocument = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreDescriptionDocument');
        $this->dom->appendChild($pbcoreDescriptionDocument);

        // Add namespace declarations
        $this->addNamespace($pbcoreDescriptionDocument, 'xsi', $this->namespaces['xsi']);
        $pbcoreDescriptionDocument->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_PBCORE.' https://pbcore.org/xsd/pbcore-2.1.xsd'
        );

        // Build the document content
        $this->buildDescriptionDocument($pbcoreDescriptionDocument, $resource);

        return $this->dom;
    }

    /**
     * Build description document content
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function buildDescriptionDocument(\DOMElement $parent, $resource): void
    {
        // Asset type
        $this->addAssetType($parent, $resource);

        // Asset date
        $this->addAssetDate($parent, $resource);

        // Identifier
        $this->addIdentifier($parent, $resource);

        // Title
        $this->addTitle($parent, $resource);

        // Subject
        $this->addSubjectsPbcore($parent, $resource);

        // Description
        $this->addDescription($parent, $resource);

        // Genre
        $this->addGenre($parent, $resource);

        // Coverage (place/time)
        $this->addCoverage($parent, $resource);

        // Creator
        $this->addCreator($parent, $resource);

        // Contributor
        $this->addContributor($parent, $resource);

        // Publisher
        $this->addPublisher($parent, $resource);

        // Rights
        $this->addRights($parent, $resource);

        // Instantiation (for each digital object)
        if ($this->options['includeDigitalObjects']) {
            $this->addInstantiations($parent, $resource);
        }
    }

    /**
     * Add asset type
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addAssetType(\DOMElement $parent, $resource): void
    {
        $level = $this->getLevelOfDescription($resource);
        $assetType = $this->mapLevelToAssetType($level);

        $pbcoreAssetType = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreAssetType');
        $pbcoreAssetType->appendChild($this->dom->createTextNode($assetType));
        $parent->appendChild($pbcoreAssetType);
    }

    /**
     * Add asset date
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addAssetDate(\DOMElement $parent, $resource): void
    {
        $dateRange = $this->getDateRange($resource);

        if ($dateRange['display'] || $dateRange['start']) {
            $pbcoreAssetDate = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreAssetDate');
            $pbcoreAssetDate->setAttribute('dateType', 'created');

            $dateValue = $dateRange['display'] ?? $this->formatDate($dateRange['start'], 'Y-m-d');
            $pbcoreAssetDate->appendChild($this->dom->createTextNode($dateValue));
            $parent->appendChild($pbcoreAssetDate);
        }
    }

    /**
     * Add identifier
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addIdentifier(\DOMElement $parent, $resource): void
    {
        $identifier = $this->getIdentifier($resource);

        $pbcoreIdentifier = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreIdentifier');
        $pbcoreIdentifier->setAttribute('source', 'local');
        $pbcoreIdentifier->appendChild($this->dom->createTextNode($identifier));
        $parent->appendChild($pbcoreIdentifier);
    }

    /**
     * Add title
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addTitle(\DOMElement $parent, $resource): void
    {
        $title = $this->getValue($resource, 'title');

        if ($title) {
            $pbcoreTitle = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreTitle');
            $pbcoreTitle->setAttribute('titleType', 'Main');
            $pbcoreTitle->appendChild($this->dom->createTextNode($title));
            $parent->appendChild($pbcoreTitle);
        }
    }

    /**
     * Add subjects
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addSubjectsPbcore(\DOMElement $parent, $resource): void
    {
        $subjects = $this->getSubjects($resource);

        foreach ($subjects as $subject) {
            $pbcoreSubject = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreSubject');
            $pbcoreSubject->setAttribute('subjectType', 'topic');
            $pbcoreSubject->appendChild($this->dom->createTextNode($subject));
            $parent->appendChild($pbcoreSubject);
        }
    }

    /**
     * Add description
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addDescription(\DOMElement $parent, $resource): void
    {
        $scopeContent = $this->getValue($resource, 'scopeAndContent');

        if ($scopeContent) {
            $pbcoreDescription = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreDescription');
            $pbcoreDescription->setAttribute('descriptionType', 'Abstract');
            $pbcoreDescription->appendChild($this->dom->createTextNode($scopeContent));
            $parent->appendChild($pbcoreDescription);
        }

        // Additional notes as description
        $notes = $this->getValue($resource, 'notes');
        if ($notes) {
            $pbcoreDescription = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreDescription');
            $pbcoreDescription->setAttribute('descriptionType', 'Notes');
            $pbcoreDescription->appendChild($this->dom->createTextNode($notes));
            $parent->appendChild($pbcoreDescription);
        }
    }

    /**
     * Add genre
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addGenre(\DOMElement $parent, $resource): void
    {
        $level = $this->getLevelOfDescription($resource);

        if ($level) {
            $pbcoreGenre = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreGenre');
            $pbcoreGenre->appendChild($this->dom->createTextNode($level));
            $parent->appendChild($pbcoreGenre);
        }
    }

    /**
     * Add coverage (place and time)
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addCoverage(\DOMElement $parent, $resource): void
    {
        // Spatial coverage (places)
        $places = $this->getPlaces($resource);
        foreach ($places as $place) {
            $pbcoreCoverage = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreCoverage');
            $parent->appendChild($pbcoreCoverage);

            $coverage = $this->dom->createElementNS(self::NS_PBCORE, 'coverage');
            $coverage->appendChild($this->dom->createTextNode($place));
            $pbcoreCoverage->appendChild($coverage);

            $coverageType = $this->dom->createElementNS(self::NS_PBCORE, 'coverageType');
            $coverageType->appendChild($this->dom->createTextNode('Spatial'));
            $pbcoreCoverage->appendChild($coverageType);
        }

        // Temporal coverage (dates)
        $dateRange = $this->getDateRange($resource);
        if ($dateRange['display'] || $dateRange['start']) {
            $pbcoreCoverage = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreCoverage');
            $parent->appendChild($pbcoreCoverage);

            $coverage = $this->dom->createElementNS(self::NS_PBCORE, 'coverage');
            $dateValue = $dateRange['display'] ?? $dateRange['start'];
            $coverage->appendChild($this->dom->createTextNode($dateValue));
            $pbcoreCoverage->appendChild($coverage);

            $coverageType = $this->dom->createElementNS(self::NS_PBCORE, 'coverageType');
            $coverageType->appendChild($this->dom->createTextNode('Temporal'));
            $pbcoreCoverage->appendChild($coverageType);
        }
    }

    /**
     * Add creator
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addCreator(\DOMElement $parent, $resource): void
    {
        $creators = $this->getCreators($resource);

        // Only first creator as main creator
        if (!empty($creators) && isset($creators[0]['name'])) {
            $pbcoreCreator = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreCreator');
            $parent->appendChild($pbcoreCreator);

            $creator = $this->dom->createElementNS(self::NS_PBCORE, 'creator');
            $creator->appendChild($this->dom->createTextNode($creators[0]['name']));
            $pbcoreCreator->appendChild($creator);

            $creatorRole = $this->dom->createElementNS(self::NS_PBCORE, 'creatorRole');
            $creatorRole->appendChild($this->dom->createTextNode('Creator'));
            $pbcoreCreator->appendChild($creatorRole);
        }
    }

    /**
     * Add contributor
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addContributor(\DOMElement $parent, $resource): void
    {
        $creators = $this->getCreators($resource);

        // Additional creators as contributors
        for ($i = 1; $i < count($creators); ++$i) {
            if (isset($creators[$i]['name'])) {
                $pbcoreContributor = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreContributor');
                $parent->appendChild($pbcoreContributor);

                $contributor = $this->dom->createElementNS(self::NS_PBCORE, 'contributor');
                $contributor->appendChild($this->dom->createTextNode($creators[$i]['name']));
                $pbcoreContributor->appendChild($contributor);

                $contributorRole = $this->dom->createElementNS(self::NS_PBCORE, 'contributorRole');
                $contributorRole->appendChild($this->dom->createTextNode('Contributor'));
                $pbcoreContributor->appendChild($contributorRole);
            }
        }
    }

    /**
     * Add publisher
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addPublisher(\DOMElement $parent, $resource): void
    {
        $repo = $this->getRepository($resource);

        if ($repo && $repo['name']) {
            $pbcorePublisher = $this->dom->createElementNS(self::NS_PBCORE, 'pbcorePublisher');
            $parent->appendChild($pbcorePublisher);

            $publisher = $this->dom->createElementNS(self::NS_PBCORE, 'publisher');
            $publisher->appendChild($this->dom->createTextNode($repo['name']));
            $pbcorePublisher->appendChild($publisher);

            $publisherRole = $this->dom->createElementNS(self::NS_PBCORE, 'publisherRole');
            $publisherRole->appendChild($this->dom->createTextNode('Repository'));
            $pbcorePublisher->appendChild($publisherRole);
        }
    }

    /**
     * Add rights
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addRights(\DOMElement $parent, $resource): void
    {
        $accessConditions = $this->getValue($resource, 'accessConditions');
        $reproConditions = $this->getValue($resource, 'reproductionConditions');

        if ($accessConditions || $reproConditions) {
            $pbcoreRightsSummary = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreRightsSummary');
            $parent->appendChild($pbcoreRightsSummary);

            if ($accessConditions) {
                $rightsSummary = $this->dom->createElementNS(self::NS_PBCORE, 'rightsSummary');
                $rightsSummary->appendChild($this->dom->createTextNode('Access: '.$accessConditions));
                $pbcoreRightsSummary->appendChild($rightsSummary);
            }

            if ($reproConditions) {
                $rightsSummary = $this->dom->createElementNS(self::NS_PBCORE, 'rightsSummary');
                $rightsSummary->appendChild($this->dom->createTextNode('Reproduction: '.$reproConditions));
                $pbcoreRightsSummary->appendChild($rightsSummary);
            }
        }
    }

    /**
     * Add instantiations (digital objects)
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addInstantiations(\DOMElement $parent, $resource): void
    {
        $digitalObjects = $this->getDigitalObjects($resource);

        foreach ($digitalObjects as $do) {
            $pbcoreInstantiation = $this->dom->createElementNS(self::NS_PBCORE, 'pbcoreInstantiation');
            $parent->appendChild($pbcoreInstantiation);

            // Instantiation identifier
            $instantiationIdentifier = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationIdentifier');
            $instantiationIdentifier->setAttribute('source', 'local');
            $instantiationIdentifier->appendChild($this->dom->createTextNode((string) ($do->id ?? 'unknown')));
            $pbcoreInstantiation->appendChild($instantiationIdentifier);

            // Instantiation date
            if (isset($do->createdAt)) {
                $instantiationDate = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationDate');
                $instantiationDate->appendChild($this->dom->createTextNode($this->formatDate($do->createdAt, 'Y-m-d')));
                $pbcoreInstantiation->appendChild($instantiationDate);
            }

            // Digital (true for all AtoM digital objects)
            $instantiationDigital = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationDigital');
            $instantiationDigital->appendChild($this->dom->createTextNode($do->mimeType ?? 'application/octet-stream'));
            $pbcoreInstantiation->appendChild($instantiationDigital);

            // Location
            if (isset($do->path)) {
                $instantiationLocation = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationLocation');
                $instantiationLocation->appendChild($this->dom->createTextNode(
                    rtrim($this->baseUri, '/').'/'.ltrim($do->path, '/')
                ));
                $pbcoreInstantiation->appendChild($instantiationLocation);
            }

            // Media type
            $instantiationMediaType = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationMediaType');
            $instantiationMediaType->appendChild($this->dom->createTextNode(
                $this->getMediaType($do->mimeType ?? '')
            ));
            $pbcoreInstantiation->appendChild($instantiationMediaType);

            // File size
            if (isset($do->byteSize)) {
                $instantiationFileSize = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationFileSize');
                $instantiationFileSize->setAttribute('unitsOfMeasure', 'bytes');
                $instantiationFileSize->appendChild($this->dom->createTextNode((string) $do->byteSize));
                $pbcoreInstantiation->appendChild($instantiationFileSize);
            }

            // Dimensions (for images/video)
            if (isset($do->width) && isset($do->height)) {
                $instantiationDimensions = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationDimensions');
                $instantiationDimensions->setAttribute('unitsOfMeasure', 'pixels');
                $instantiationDimensions->appendChild($this->dom->createTextNode("{$do->width}x{$do->height}"));
                $pbcoreInstantiation->appendChild($instantiationDimensions);
            }

            // Duration (for audio/video)
            if (isset($do->duration)) {
                $instantiationDuration = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationDuration');
                $instantiationDuration->appendChild($this->dom->createTextNode($do->duration));
                $pbcoreInstantiation->appendChild($instantiationDuration);
            }

            // Checksum
            if (isset($do->checksum)) {
                $instantiationEssenceTrack = $this->dom->createElementNS(self::NS_PBCORE, 'instantiationEssenceTrack');
                $pbcoreInstantiation->appendChild($instantiationEssenceTrack);

                $essenceTrackAnnotation = $this->dom->createElementNS(self::NS_PBCORE, 'essenceTrackAnnotation');
                $essenceTrackAnnotation->setAttribute('annotationType', 'checksum');
                $essenceTrackAnnotation->appendChild($this->dom->createTextNode($do->checksum));
                $instantiationEssenceTrack->appendChild($essenceTrackAnnotation);
            }
        }
    }

    /**
     * Map level to PBCore asset type
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function mapLevelToAssetType(?string $level): string
    {
        $map = [
            'Fonds' => 'Collection',
            'Collection' => 'Collection',
            'Series' => 'Series',
            'File' => 'Program',
            'Item' => 'Clip',
        ];

        return $map[$level] ?? 'Program';
    }

    /**
     * Get media type from MIME type
     *
     * @param string $mimeType
     *
     * @return string
     */
    protected function getMediaType(string $mimeType): string
    {
        if (0 === strpos($mimeType, 'video/')) {
            return 'Moving Image';
        }
        if (0 === strpos($mimeType, 'audio/')) {
            return 'Sound';
        }
        if (0 === strpos($mimeType, 'image/')) {
            return 'Static Image';
        }
        if (0 === strpos($mimeType, 'text/')) {
            return 'Text';
        }

        return 'Other';
    }
}
