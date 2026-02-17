<?php

namespace AhgFederation;

/**
 * Heritage Platform OAI-PMH Metadata Format
 *
 * Registers the oai_heritage metadata format with AtoM's OAI-PMH implementation.
 * This format provides rich archival metadata specific to heritage collections.
 */
class OaiHeritageMetadataFormat
{
    public const PREFIX = 'oai_heritage';
    public const NAMESPACE = 'https://heritage.example.org/oai/heritage/';
    public const SCHEMA = 'https://heritage.example.org/oai/heritage/heritage.xsd';

    /**
     * Register the Heritage metadata format with QubitOai
     */
    public static function register(): void
    {
        // Add Heritage format to the available formats
        // This is done by extending the getMetadataFormats method via monkey-patching
        // since QubitOai doesn't have an addMetadataFormat method

        // We achieve this by hooking into the OAI response templates
        // The actual format registration happens at template level
    }

    /**
     * Get the format definition
     */
    public static function getFormatDefinition(): array
    {
        return [
            'prefix' => self::PREFIX,
            'namespace' => self::NAMESPACE,
            'schema' => self::SCHEMA,
        ];
    }

    /**
     * Check if this format is requested
     */
    public static function isRequested(string $metadataPrefix): bool
    {
        return $metadataPrefix === self::PREFIX;
    }

    /**
     * Generate Heritage XML for an information object
     */
    public static function generateXml(\QubitInformationObject $resource, string $culture = 'en'): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startElementNs('heritage', 'record', self::NAMESPACE);
        $xml->writeAttributeNs('xsi', 'schemaLocation', 'http://www.w3.org/2001/XMLSchema-instance',
            self::NAMESPACE . ' ' . self::SCHEMA);

        // Identifier
        $xml->writeElement('heritage:identifier', $resource->getOaiIdentifier());

        // Title
        if ($resource->title) {
            $xml->writeElement('heritage:title', self::escapeXml($resource->getTitle(['culture' => $culture])));
        }

        // Alternative titles
        foreach ($resource->getAlternativeTitles() as $altTitle) {
            $xml->writeElement('heritage:alternativeTitle', self::escapeXml($altTitle));
        }

        // Description (Scope and Content)
        if ($resource->scopeAndContent) {
            $xml->writeElement('heritage:description', self::escapeXml($resource->getScopeAndContent(['culture' => $culture])));
        }

        // Repository
        if ($resource->repository) {
            $xml->startElement('heritage:repository');
            $xml->writeElement('heritage:name', self::escapeXml($resource->repository->getAuthorizedFormOfName(['culture' => $culture])));
            if ($resource->repository->identifier) {
                $xml->writeElement('heritage:identifier', self::escapeXml($resource->repository->identifier));
            }
            $xml->endElement();
        }

        // Level of description
        if ($resource->levelOfDescription) {
            $xml->writeElement('heritage:levelOfDescription', self::escapeXml($resource->levelOfDescription->getName(['culture' => $culture])));
        }

        // Dates
        self::writeDates($xml, $resource, $culture);

        // Extent
        if ($resource->extentAndMedium) {
            $xml->writeElement('heritage:extent', self::escapeXml($resource->getExtentAndMedium(['culture' => $culture])));
        }

        // Reference code
        if ($resource->identifier) {
            $xml->writeElement('heritage:referenceCode', self::escapeXml($resource->getInheritedReferenceCode()));
        }

        // Creators
        self::writeCreators($xml, $resource, $culture);

        // Subjects
        self::writeSubjects($xml, $resource, $culture);

        // Places
        self::writePlaces($xml, $resource, $culture);

        // Access conditions
        if ($resource->accessConditions) {
            $xml->writeElement('heritage:accessConditions', self::escapeXml($resource->getAccessConditions(['culture' => $culture])));
        }

        // Reproduction conditions
        if ($resource->reproductionConditions) {
            $xml->writeElement('heritage:reproductionConditions', self::escapeXml($resource->getReproductionConditions(['culture' => $culture])));
        }

        // Language
        foreach ($resource->language as $langCode) {
            $xml->writeElement('heritage:language', $langCode);
        }

        // Finding aids
        if ($resource->findingAids) {
            $xml->writeElement('heritage:findingAids', self::escapeXml($resource->getFindingAids(['culture' => $culture])));
        }

        // Related materials
        if ($resource->relatedUnitsOfDescription) {
            $xml->writeElement('heritage:relatedMaterials', self::escapeXml($resource->getRelatedUnitsOfDescription(['culture' => $culture])));
        }

        // Digital objects
        self::writeDigitalObjects($xml, $resource);

        // Provenance (archival history)
        if ($resource->archivalHistory) {
            $xml->writeElement('heritage:provenance', self::escapeXml($resource->getArchivalHistory(['culture' => $culture])));
        }

        // Source of acquisition
        if ($resource->acquisition) {
            $xml->writeElement('heritage:acquisition', self::escapeXml($resource->getAcquisition(['culture' => $culture])));
        }

        // Physical characteristics
        if ($resource->physicalCharacteristics) {
            $xml->writeElement('heritage:physicalCharacteristics', self::escapeXml($resource->getPhysicalCharacteristics(['culture' => $culture])));
        }

        // Arrangement
        if ($resource->arrangement) {
            $xml->writeElement('heritage:arrangement', self::escapeXml($resource->getArrangement(['culture' => $culture])));
        }

        // Notes
        self::writeNotes($xml, $resource, $culture);

        // Publication status
        $xml->writeElement('heritage:publicationStatus', $resource->getPublicationStatus()->status->getName(['culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()]));

        // Timestamps
        $xml->writeElement('heritage:createdAt', \QubitOai::getDate($resource->getCreatedAt()));
        $xml->writeElement('heritage:updatedAt', \QubitOai::getDate($resource->getUpdatedAt()));

        // Parent reference (for hierarchy)
        if ($resource->parent && $resource->parent->id !== \QubitInformationObject::ROOT_ID) {
            $xml->writeElement('heritage:parentIdentifier', $resource->parent->getOaiIdentifier());
        }

        // Collection root reference
        $collectionRoot = $resource->getCollectionRoot();
        if ($collectionRoot && $collectionRoot->id !== $resource->id) {
            $xml->writeElement('heritage:collectionIdentifier', $collectionRoot->getOaiIdentifier());
        }

        $xml->endElement(); // heritage:record

        return $xml->outputMemory();
    }

    /**
     * Write date elements
     */
    protected static function writeDates(\XMLWriter $xml, \QubitInformationObject $resource, string $culture): void
    {
        $events = $resource->getEvents();
        $hasDateElement = false;

        foreach ($events as $event) {
            if ($event->typeId && $event->type) {
                $eventType = $event->type->getName(['culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()]);

                $xml->startElement('heritage:date');
                $xml->writeAttribute('type', strtolower($eventType));

                if ($event->startDate) {
                    $xml->writeElement('heritage:start', $event->startDate);
                }
                if ($event->endDate) {
                    $xml->writeElement('heritage:end', $event->endDate);
                }
                if ($event->date) {
                    $xml->writeElement('heritage:display', self::escapeXml($event->getDate(['culture' => $culture])));
                }

                $xml->endElement();
                $hasDateElement = true;
            }
        }

        // Fallback to getDates if no events found
        if (!$hasDateElement) {
            $dates = $resource->getDates();
            if (!empty($dates)) {
                $xml->startElement('heritage:date');
                $xml->writeAttribute('type', 'creation');
                $xml->writeElement('heritage:display', self::escapeXml($dates));
                $xml->endElement();
            }
        }
    }

    /**
     * Write creator elements
     */
    protected static function writeCreators(\XMLWriter $xml, \QubitInformationObject $resource, string $culture): void
    {
        $creators = $resource->getCreators();
        foreach ($creators as $creator) {
            $xml->startElement('heritage:creator');
            $xml->writeElement('heritage:name', self::escapeXml($creator->getAuthorizedFormOfName(['culture' => $culture])));

            if ($creator->datesOfExistence) {
                $xml->writeElement('heritage:dates', self::escapeXml($creator->getDatesOfExistence(['culture' => $culture])));
            }

            // Actor type
            $entityTypeName = $creator->entityTypeId ? term_name($creator->entityTypeId, 'en') : '';
            if ($entityTypeName) {
                $xml->writeElement('heritage:type', self::escapeXml($entityTypeName));
            }

            $xml->endElement();
        }
    }

    /**
     * Write subject elements
     */
    protected static function writeSubjects(\XMLWriter $xml, \QubitInformationObject $resource, string $culture): void
    {
        // Subject access points
        foreach ($resource->getSubjectAccessPoints() as $subject) {
            $xml->startElement('heritage:subject');
            $xml->writeElement('heritage:term', self::escapeXml($subject->term->getName(['culture' => $culture])));

            if ($subject->term->taxonomy) {
                $xml->writeAttribute('taxonomy', self::escapeXml($subject->term->taxonomy->getName(['culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()])));
            }

            $xml->endElement();
        }

        // Genre access points
        foreach ($resource->getGenreAccessPoints() as $genre) {
            $xml->startElement('heritage:genre');
            $xml->writeElement('heritage:term', self::escapeXml($genre->term->getName(['culture' => $culture])));
            $xml->endElement();
        }
    }

    /**
     * Write place elements
     */
    protected static function writePlaces(\XMLWriter $xml, \QubitInformationObject $resource, string $culture): void
    {
        foreach ($resource->getPlaceAccessPoints() as $place) {
            $xml->startElement('heritage:place');
            $xml->writeElement('heritage:name', self::escapeXml($place->term->getName(['culture' => $culture])));
            $xml->endElement();
        }
    }

    /**
     * Write digital object elements
     */
    protected static function writeDigitalObjects(\XMLWriter $xml, \QubitInformationObject $resource): void
    {
        foreach ($resource->digitalObjectsRelatedByobjectId as $digitalObject) {
            $xml->startElement('heritage:digitalObject');

            // Reference URL
            $publicUrl = sfConfig::get('app_siteBaseUrl') . '/' . $resource->slug;
            $xml->writeElement('heritage:reference', $publicUrl);

            // Mime type
            if ($digitalObject->mimeType) {
                $xml->writeElement('heritage:mimeType', $digitalObject->mimeType);
            }

            // Byte size
            if ($digitalObject->byteSize) {
                $xml->writeElement('heritage:byteSize', $digitalObject->byteSize);
            }

            // Checksum
            if ($digitalObject->checksum) {
                $xml->writeElement('heritage:checksum', $digitalObject->checksum);
                $xml->writeElement('heritage:checksumType', $digitalObject->checksumType ?: 'md5');
            }

            // Media type
            if ($digitalObject->mediaTypeId) {
                $mediaTypeName = term_name($digitalObject->mediaTypeId, 'en');
                if ($mediaTypeName) {
                    $xml->writeElement('heritage:mediaType', $mediaTypeName);
                }
            }

            $xml->endElement();
        }
    }

    /**
     * Write note elements
     */
    protected static function writeNotes(\XMLWriter $xml, \QubitInformationObject $resource, string $culture): void
    {
        foreach ($resource->getNotes() as $note) {
            if ($note->content) {
                $xml->startElement('heritage:note');

                if ($note->type) {
                    $xml->writeAttribute('type', strtolower(str_replace(' ', '_', $note->type->getName(['culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()]))));
                }

                $xml->text(self::escapeXml($note->getContent(['culture' => $culture])));
                $xml->endElement();
            }
        }
    }

    /**
     * Escape XML special characters
     */
    protected static function escapeXml($text): string
    {
        if ($text === null) {
            return '';
        }
        return htmlspecialchars((string)$text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
