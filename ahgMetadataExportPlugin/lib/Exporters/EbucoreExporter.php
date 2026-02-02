<?php

/**
 * EbucoreExporter - EBUCore (European Broadcasting Union) Exporter
 *
 * Exports media descriptions to EBUCore 1.10 XML format.
 *
 * @see https://tech.ebu.ch/MetadataEbuCore
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class EbucoreExporter extends AbstractXmlExporter
{
    /**
     * EBUCore namespace
     */
    public const NS_EBUCORE = 'urn:ebu:metadata-schema:ebucore';

    /**
     * Dublin Core namespace
     */
    public const NS_DC = 'http://purl.org/dc/elements/1.1/';

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_EBUCORE;
        $this->primaryPrefix = 'ebucore';
        $this->namespaces = [
            'ebucore' => self::NS_EBUCORE,
            'dc' => self::NS_DC,
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'ebucore';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'EBUCore';
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
        $ebuCoreMain = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:ebuCoreMain');
        $this->dom->appendChild($ebuCoreMain);

        // Add namespace declarations
        $this->addNamespace($ebuCoreMain, 'dc', self::NS_DC);
        $this->addNamespace($ebuCoreMain, 'xsi', $this->namespaces['xsi']);
        $ebuCoreMain->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_EBUCORE.' https://www.ebu.ch/metadata/schemas/EBUCore/ebucore.xsd'
        );

        // Build core metadata
        $coreMetadata = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:coreMetadata');
        $ebuCoreMain->appendChild($coreMetadata);

        // Add metadata elements
        $this->buildCoreMetadata($coreMetadata, $resource);

        return $this->dom;
    }

    /**
     * Build core metadata content
     *
     * @param \DOMElement $coreMetadata
     * @param mixed       $resource
     */
    protected function buildCoreMetadata(\DOMElement $coreMetadata, $resource): void
    {
        // Title
        $this->addTitle($coreMetadata, $resource);

        // Alternative title
        $this->addAlternativeTitle($coreMetadata, $resource);

        // Creator
        $this->addCreatorEbucore($coreMetadata, $resource);

        // Subject
        $this->addSubjectsEbucore($coreMetadata, $resource);

        // Description
        $this->addDescriptionEbucore($coreMetadata, $resource);

        // Publisher
        $this->addPublisherEbucore($coreMetadata, $resource);

        // Contributor
        $this->addContributorEbucore($coreMetadata, $resource);

        // Date
        $this->addDateEbucore($coreMetadata, $resource);

        // Type
        $this->addType($coreMetadata, $resource);

        // Format (digital objects)
        if ($this->options['includeDigitalObjects']) {
            $this->addFormat($coreMetadata, $resource);
        }

        // Identifier
        $this->addIdentifierEbucore($coreMetadata, $resource);

        // Language
        $this->addLanguageEbucore($coreMetadata, $resource);

        // Coverage
        $this->addCoverageEbucore($coreMetadata, $resource);

        // Rights
        $this->addRightsEbucore($coreMetadata, $resource);
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
            $titleEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:title');
            $parent->appendChild($titleEl);

            $dcTitle = $this->dom->createElementNS(self::NS_DC, 'dc:title');
            $dcTitle->appendChild($this->dom->createTextNode($title));
            $titleEl->appendChild($dcTitle);
        }
    }

    /**
     * Add alternative title
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addAlternativeTitle(\DOMElement $parent, $resource): void
    {
        $altTitle = $this->getValue($resource, 'alternateTitle');

        if ($altTitle) {
            $alternativeTitleEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:alternativeTitle');
            $parent->appendChild($alternativeTitleEl);

            $dcTitle = $this->dom->createElementNS(self::NS_DC, 'dc:title');
            $dcTitle->appendChild($this->dom->createTextNode($altTitle));
            $alternativeTitleEl->appendChild($dcTitle);
        }
    }

    /**
     * Add creator
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addCreatorEbucore(\DOMElement $parent, $resource): void
    {
        $creators = $this->getCreators($resource);

        foreach ($creators as $creator) {
            if (!$creator['name']) {
                continue;
            }

            $creatorEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:creator');
            $parent->appendChild($creatorEl);

            // Entity type
            $entityType = $this->mapEntityType($creator['type'] ?? null);
            $entityEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:'.$entityType);
            $creatorEl->appendChild($entityEl);

            // Contact details with name
            $contactDetails = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:contactDetails');
            $entityEl->appendChild($contactDetails);

            $name = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:name');
            $name->appendChild($this->dom->createTextNode($creator['name']));
            $contactDetails->appendChild($name);

            // Role
            $role = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:role');
            $creatorEl->appendChild($role);

            $roleType = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:typeLabel');
            $roleType->appendChild($this->dom->createTextNode('Creator'));
            $role->appendChild($roleType);
        }
    }

    /**
     * Add subjects
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addSubjectsEbucore(\DOMElement $parent, $resource): void
    {
        $subjects = $this->getSubjects($resource);

        foreach ($subjects as $subject) {
            $subjectEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:subject');
            $parent->appendChild($subjectEl);

            $dcSubject = $this->dom->createElementNS(self::NS_DC, 'dc:subject');
            $dcSubject->appendChild($this->dom->createTextNode($subject));
            $subjectEl->appendChild($dcSubject);
        }
    }

    /**
     * Add description
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addDescriptionEbucore(\DOMElement $parent, $resource): void
    {
        $scopeContent = $this->getValue($resource, 'scopeAndContent');

        if ($scopeContent) {
            $descriptionEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:description');
            $parent->appendChild($descriptionEl);

            $dcDescription = $this->dom->createElementNS(self::NS_DC, 'dc:description');
            $dcDescription->appendChild($this->dom->createTextNode($scopeContent));
            $descriptionEl->appendChild($dcDescription);
        }

        // Notes as additional description
        $notes = $this->getValue($resource, 'notes');
        if ($notes) {
            $descriptionEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:description');
            $descriptionEl->setAttribute('typeLabel', 'notes');
            $parent->appendChild($descriptionEl);

            $dcDescription = $this->dom->createElementNS(self::NS_DC, 'dc:description');
            $dcDescription->appendChild($this->dom->createTextNode($notes));
            $descriptionEl->appendChild($dcDescription);
        }
    }

    /**
     * Add publisher
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addPublisherEbucore(\DOMElement $parent, $resource): void
    {
        $repo = $this->getRepository($resource);

        if ($repo && $repo['name']) {
            $publisherEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:publisher');
            $parent->appendChild($publisherEl);

            $organisationDetails = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:organisationDetails');
            $publisherEl->appendChild($organisationDetails);

            $organisationName = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:organisationName');
            $organisationName->appendChild($this->dom->createTextNode($repo['name']));
            $organisationDetails->appendChild($organisationName);
        }
    }

    /**
     * Add contributor
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addContributorEbucore(\DOMElement $parent, $resource): void
    {
        $creators = $this->getCreators($resource);

        // Skip first (creator), add rest as contributors
        for ($i = 1; $i < count($creators); ++$i) {
            if (!isset($creators[$i]['name'])) {
                continue;
            }

            $contributorEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:contributor');
            $parent->appendChild($contributorEl);

            $entityType = $this->mapEntityType($creators[$i]['type'] ?? null);
            $entityEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:'.$entityType);
            $contributorEl->appendChild($entityEl);

            $contactDetails = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:contactDetails');
            $entityEl->appendChild($contactDetails);

            $name = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:name');
            $name->appendChild($this->dom->createTextNode($creators[$i]['name']));
            $contactDetails->appendChild($name);
        }
    }

    /**
     * Add date
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addDateEbucore(\DOMElement $parent, $resource): void
    {
        $dateRange = $this->getDateRange($resource);

        if ($dateRange['display'] || $dateRange['start']) {
            $dateEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:date');
            $parent->appendChild($dateEl);

            // Created date
            $created = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:created');
            $dateEl->appendChild($created);

            if ($dateRange['start']) {
                $startDate = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:startDate');
                $startDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['start'], 'Y-m-d')));
                $created->appendChild($startDate);
            }

            if ($dateRange['end']) {
                $endDate = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:endDate');
                $endDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['end'], 'Y-m-d')));
                $created->appendChild($endDate);
            }
        }
    }

    /**
     * Add type
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addType(\DOMElement $parent, $resource): void
    {
        $level = $this->getLevelOfDescription($resource);

        $typeEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:type');
        $parent->appendChild($typeEl);

        // Genre
        $genre = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:genre');
        $typeEl->appendChild($genre);

        $dcType = $this->dom->createElementNS(self::NS_DC, 'dc:type');
        $dcType->appendChild($this->dom->createTextNode($level ?? 'Item'));
        $genre->appendChild($dcType);

        // Object type
        $objectType = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:objectType');
        $typeEl->appendChild($objectType);

        $objectTypeLabel = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:typeLabel');
        $objectTypeLabel->appendChild($this->dom->createTextNode($this->mapLevelToObjectType($level)));
        $objectType->appendChild($objectTypeLabel);
    }

    /**
     * Add format (digital objects)
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addFormat(\DOMElement $parent, $resource): void
    {
        $digitalObjects = $this->getDigitalObjects($resource);

        foreach ($digitalObjects as $do) {
            $formatEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:format');
            $parent->appendChild($formatEl);

            // Medium
            $medium = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:medium');
            $formatEl->appendChild($medium);

            $mediumType = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:mediumTypeCode');
            $mediumType->appendChild($this->dom->createTextNode('Digital'));
            $medium->appendChild($mediumType);

            // MIME type
            if (isset($do->mimeType)) {
                $mimeType = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:mimeType');
                $formatEl->appendChild($mimeType);

                $typeLabel = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:typeLabel');
                $typeLabel->appendChild($this->dom->createTextNode($do->mimeType));
                $mimeType->appendChild($typeLabel);
            }

            // File size
            if (isset($do->byteSize)) {
                $fileSize = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:fileSize');
                $fileSize->appendChild($this->dom->createTextNode((string) $do->byteSize));
                $formatEl->appendChild($fileSize);
            }

            // Locator
            if (isset($do->path)) {
                $locator = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:locator');
                $locator->appendChild($this->dom->createTextNode(
                    rtrim($this->baseUri, '/').'/'.ltrim($do->path, '/')
                ));
                $formatEl->appendChild($locator);
            }

            // Filename
            if (isset($do->name)) {
                $fileName = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:fileName');
                $fileName->appendChild($this->dom->createTextNode($do->name));
                $formatEl->appendChild($fileName);
            }

            // Video/image format (dimensions)
            if (isset($do->width) && isset($do->height)) {
                $videoFormat = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:videoFormat');
                $formatEl->appendChild($videoFormat);

                $width = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:width');
                $width->setAttribute('unit', 'pixel');
                $width->appendChild($this->dom->createTextNode((string) $do->width));
                $videoFormat->appendChild($width);

                $height = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:height');
                $height->setAttribute('unit', 'pixel');
                $height->appendChild($this->dom->createTextNode((string) $do->height));
                $videoFormat->appendChild($height);
            }

            // Duration
            if (isset($do->duration)) {
                $duration = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:duration');
                $formatEl->appendChild($duration);

                $normalPlayTime = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:normalPlayTime');
                $normalPlayTime->appendChild($this->dom->createTextNode($do->duration));
                $duration->appendChild($normalPlayTime);
            }

            // Hash/checksum
            if (isset($do->checksum)) {
                $hash = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:hash');
                $formatEl->appendChild($hash);

                $hashValue = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:hashValue');
                $hashValue->appendChild($this->dom->createTextNode($do->checksum));
                $hash->appendChild($hashValue);

                $hashFunction = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:hashFunction');
                $hashFunction->appendChild($this->dom->createTextNode($do->checksumType ?? 'MD5'));
                $hash->appendChild($hashFunction);
            }
        }
    }

    /**
     * Add identifier
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addIdentifierEbucore(\DOMElement $parent, $resource): void
    {
        $identifier = $this->getIdentifier($resource);

        $identifierEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:identifier');
        $parent->appendChild($identifierEl);

        $dcIdentifier = $this->dom->createElementNS(self::NS_DC, 'dc:identifier');
        $dcIdentifier->appendChild($this->dom->createTextNode($identifier));
        $identifierEl->appendChild($dcIdentifier);

        // Identifier type
        $formatId = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:formatId');
        $formatId->setAttribute('formatLabel', 'local');
        $identifierEl->appendChild($formatId);
    }

    /**
     * Add language
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addLanguageEbucore(\DOMElement $parent, $resource): void
    {
        if (!method_exists($resource, 'getLanguage')) {
            return;
        }

        $languages = $resource->getLanguage();

        foreach ($languages as $lang) {
            $languageEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:language');
            $parent->appendChild($languageEl);

            $dcLanguage = $this->dom->createElementNS(self::NS_DC, 'dc:language');

            if (isset($lang->code)) {
                $dcLanguage->appendChild($this->dom->createTextNode($lang->code));
            } elseif (isset($lang->name)) {
                $dcLanguage->appendChild($this->dom->createTextNode($lang->name));
            }

            $languageEl->appendChild($dcLanguage);
        }
    }

    /**
     * Add coverage
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addCoverageEbucore(\DOMElement $parent, $resource): void
    {
        $places = $this->getPlaces($resource);

        foreach ($places as $place) {
            $coverageEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:coverage');
            $parent->appendChild($coverageEl);

            $spatial = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:spatial');
            $coverageEl->appendChild($spatial);

            $locationName = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:locationName');
            $locationName->appendChild($this->dom->createTextNode($place));
            $spatial->appendChild($locationName);
        }
    }

    /**
     * Add rights
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addRightsEbucore(\DOMElement $parent, $resource): void
    {
        $accessConditions = $this->getValue($resource, 'accessConditions');
        $reproConditions = $this->getValue($resource, 'reproductionConditions');

        if ($accessConditions || $reproConditions) {
            $rightsEl = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:rights');
            $parent->appendChild($rightsEl);

            // Rights holder (repository)
            $repo = $this->getRepository($resource);
            if ($repo && $repo['name']) {
                $rightsHolder = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:rightsHolder');
                $rightsEl->appendChild($rightsHolder);

                $organisationDetails = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:organisationDetails');
                $rightsHolder->appendChild($organisationDetails);

                $organisationName = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:organisationName');
                $organisationName->appendChild($this->dom->createTextNode($repo['name']));
                $organisationDetails->appendChild($organisationName);
            }

            // Rights expression (access conditions)
            if ($accessConditions) {
                $rightsExpression = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:rightsExpression');
                $rightsEl->appendChild($rightsExpression);

                $dcRights = $this->dom->createElementNS(self::NS_DC, 'dc:rights');
                $dcRights->appendChild($this->dom->createTextNode('Access: '.$accessConditions));
                $rightsExpression->appendChild($dcRights);
            }

            // Copyright statement (reproduction conditions)
            if ($reproConditions) {
                $copyrightStatement = $this->dom->createElementNS(self::NS_EBUCORE, 'ebucore:copyrightStatement');
                $copyrightStatement->appendChild($this->dom->createTextNode($reproConditions));
                $rightsEl->appendChild($copyrightStatement);
            }
        }
    }

    /**
     * Map creator type to EBUCore entity type
     *
     * @param string|null $type
     *
     * @return string
     */
    protected function mapEntityType(?string $type): string
    {
        switch ($type) {
            case 'Corporate body':
            case 'corporate':
                return 'organisationDetails';
            case 'Person':
            case 'person':
            default:
                return 'contactDetails';
        }
    }

    /**
     * Map level to object type
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function mapLevelToObjectType(?string $level): string
    {
        $map = [
            'Fonds' => 'collection',
            'Collection' => 'collection',
            'Series' => 'series',
            'File' => 'programme',
            'Item' => 'item',
        ];

        return $map[$level] ?? 'item';
    }
}
