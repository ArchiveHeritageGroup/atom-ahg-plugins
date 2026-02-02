<?php

/**
 * PremisExporter - PREMIS (Preservation Metadata Implementation Strategies) Exporter
 *
 * Exports digital preservation metadata to PREMIS 3.0 XML format.
 *
 * @see https://www.loc.gov/standards/premis/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class PremisExporter extends AbstractXmlExporter
{
    /**
     * PREMIS namespace
     */
    public const NS_PREMIS = 'http://www.loc.gov/premis/v3';

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_PREMIS;
        $this->primaryPrefix = 'premis';
        $this->namespaces = [
            'premis' => self::NS_PREMIS,
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xlink' => 'http://www.w3.org/1999/xlink',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'premis';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'PREMIS';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Preservation';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedResourceTypes(): array
    {
        return ['QubitInformationObject', 'QubitDigitalObject'];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDocument($resource): \DOMDocument
    {
        // Create root element
        $premis = $this->dom->createElementNS(self::NS_PREMIS, 'premis:premis');
        $this->dom->appendChild($premis);

        // Add namespace declarations
        $premis->setAttribute('version', '3.0');
        $this->addNamespace($premis, 'xsi', $this->namespaces['xsi']);
        $this->addNamespace($premis, 'xlink', $this->namespaces['xlink']);
        $premis->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_PREMIS.' https://www.loc.gov/standards/premis/premis.xsd'
        );

        // Get digital objects
        $digitalObjects = $this->getDigitalObjectsForResource($resource);

        // Build PREMIS objects for each digital object
        foreach ($digitalObjects as $do) {
            $object = $this->buildObject($do, $resource);
            if ($object) {
                $premis->appendChild($object);
            }
        }

        // Build events
        $events = $this->buildEvents($resource, $digitalObjects);
        foreach ($events as $event) {
            $premis->appendChild($event);
        }

        // Build agents
        $agents = $this->buildAgents($resource);
        foreach ($agents as $agent) {
            $premis->appendChild($agent);
        }

        // Build rights
        $rights = $this->buildRights($resource);
        foreach ($rights as $right) {
            $premis->appendChild($right);
        }

        return $this->dom;
    }

    /**
     * Build PREMIS object element for a digital object
     *
     * @param mixed $digitalObject
     * @param mixed $parentResource
     *
     * @return \DOMElement
     */
    protected function buildObject($digitalObject, $parentResource): \DOMElement
    {
        $object = $this->dom->createElementNS(self::NS_PREMIS, 'premis:object');
        $object->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:type',
            'premis:file'
        );

        // Object identifier
        $objectIdentifier = $this->buildObjectIdentifier($digitalObject);
        $object->appendChild($objectIdentifier);

        // Object characteristics
        $objectCharacteristics = $this->buildObjectCharacteristics($digitalObject);
        $object->appendChild($objectCharacteristics);

        // Original name
        $originalName = $this->getOriginalName($digitalObject);
        if ($originalName) {
            $originalNameEl = $this->dom->createElementNS(self::NS_PREMIS, 'premis:originalName');
            $originalNameEl->appendChild($this->dom->createTextNode($originalName));
            $object->appendChild($originalNameEl);
        }

        // Storage
        $storage = $this->buildStorage($digitalObject);
        if ($storage) {
            $object->appendChild($storage);
        }

        // Significant properties
        $this->addSignificantProperties($object, $digitalObject);

        // Relationship to intellectual entity
        $relationship = $this->buildRelationship($digitalObject, $parentResource);
        if ($relationship) {
            $object->appendChild($relationship);
        }

        // Linking rights statement
        $this->addLinkingRightsStatement($object, $parentResource);

        return $object;
    }

    /**
     * Build object identifier
     *
     * @param mixed $digitalObject
     *
     * @return \DOMElement
     */
    protected function buildObjectIdentifier($digitalObject): \DOMElement
    {
        $objectIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:objectIdentifier');

        // Identifier type
        $type = $this->dom->createElementNS(self::NS_PREMIS, 'premis:objectIdentifierType');
        $type->appendChild($this->dom->createTextNode('local'));
        $objectIdentifier->appendChild($type);

        // Identifier value
        $value = $this->dom->createElementNS(self::NS_PREMIS, 'premis:objectIdentifierValue');
        $identifier = $digitalObject->id ?? $digitalObject->slug ?? uniqid('do_');
        $value->appendChild($this->dom->createTextNode((string) $identifier));
        $objectIdentifier->appendChild($value);

        return $objectIdentifier;
    }

    /**
     * Build object characteristics
     *
     * @param mixed $digitalObject
     *
     * @return \DOMElement
     */
    protected function buildObjectCharacteristics($digitalObject): \DOMElement
    {
        $objectCharacteristics = $this->dom->createElementNS(self::NS_PREMIS, 'premis:objectCharacteristics');

        // Composition level (0 = single file)
        $compositionLevel = $this->dom->createElementNS(self::NS_PREMIS, 'premis:compositionLevel');
        $compositionLevel->appendChild($this->dom->createTextNode('0'));
        $objectCharacteristics->appendChild($compositionLevel);

        // Fixity (checksum)
        $fixity = $this->buildFixity($digitalObject);
        if ($fixity) {
            $objectCharacteristics->appendChild($fixity);
        }

        // Size
        $size = $this->getFileSize($digitalObject);
        if ($size) {
            $sizeEl = $this->dom->createElementNS(self::NS_PREMIS, 'premis:size');
            $sizeEl->appendChild($this->dom->createTextNode((string) $size));
            $objectCharacteristics->appendChild($sizeEl);
        }

        // Format
        $format = $this->buildFormat($digitalObject);
        $objectCharacteristics->appendChild($format);

        // Creating application
        $creatingApplication = $this->buildCreatingApplication($digitalObject);
        if ($creatingApplication) {
            $objectCharacteristics->appendChild($creatingApplication);
        }

        return $objectCharacteristics;
    }

    /**
     * Build fixity element (checksum)
     *
     * @param mixed $digitalObject
     *
     * @return \DOMElement|null
     */
    protected function buildFixity($digitalObject): ?\DOMElement
    {
        $checksum = $digitalObject->checksum ?? null;
        $checksumType = $digitalObject->checksumType ?? 'MD5';

        if (!$checksum) {
            return null;
        }

        $fixity = $this->dom->createElementNS(self::NS_PREMIS, 'premis:fixity');

        // Algorithm
        $messageDigestAlgorithm = $this->dom->createElementNS(self::NS_PREMIS, 'premis:messageDigestAlgorithm');
        $messageDigestAlgorithm->appendChild($this->dom->createTextNode(strtoupper($checksumType)));
        $fixity->appendChild($messageDigestAlgorithm);

        // Digest value
        $messageDigest = $this->dom->createElementNS(self::NS_PREMIS, 'premis:messageDigest');
        $messageDigest->appendChild($this->dom->createTextNode($checksum));
        $fixity->appendChild($messageDigest);

        // Originator
        $messageDigestOriginator = $this->dom->createElementNS(self::NS_PREMIS, 'premis:messageDigestOriginator');
        $messageDigestOriginator->appendChild($this->dom->createTextNode('AtoM'));
        $fixity->appendChild($messageDigestOriginator);

        return $fixity;
    }

    /**
     * Build format element
     *
     * @param mixed $digitalObject
     *
     * @return \DOMElement
     */
    protected function buildFormat($digitalObject): \DOMElement
    {
        $format = $this->dom->createElementNS(self::NS_PREMIS, 'premis:format');

        // Format designation
        $formatDesignation = $this->dom->createElementNS(self::NS_PREMIS, 'premis:formatDesignation');

        // Format name (MIME type)
        $mimeType = $digitalObject->mimeType ?? 'application/octet-stream';
        $formatName = $this->dom->createElementNS(self::NS_PREMIS, 'premis:formatName');
        $formatName->appendChild($this->dom->createTextNode($mimeType));
        $formatDesignation->appendChild($formatName);

        // Format version (if available)
        if (isset($digitalObject->formatVersion)) {
            $formatVersion = $this->dom->createElementNS(self::NS_PREMIS, 'premis:formatVersion');
            $formatVersion->appendChild($this->dom->createTextNode($digitalObject->formatVersion));
            $formatDesignation->appendChild($formatVersion);
        }

        $format->appendChild($formatDesignation);

        // Format registry (PRONOM if available)
        if (isset($digitalObject->puid)) {
            $formatRegistry = $this->dom->createElementNS(self::NS_PREMIS, 'premis:formatRegistry');

            $registryName = $this->dom->createElementNS(self::NS_PREMIS, 'premis:formatRegistryName');
            $registryName->appendChild($this->dom->createTextNode('PRONOM'));
            $formatRegistry->appendChild($registryName);

            $registryKey = $this->dom->createElementNS(self::NS_PREMIS, 'premis:formatRegistryKey');
            $registryKey->appendChild($this->dom->createTextNode($digitalObject->puid));
            $formatRegistry->appendChild($registryKey);

            $format->appendChild($formatRegistry);
        }

        return $format;
    }

    /**
     * Build creating application element
     *
     * @param mixed $digitalObject
     *
     * @return \DOMElement|null
     */
    protected function buildCreatingApplication($digitalObject): ?\DOMElement
    {
        $createdAt = $digitalObject->createdAt ?? $digitalObject->created_at ?? null;

        if (!$createdAt) {
            return null;
        }

        $creatingApplication = $this->dom->createElementNS(self::NS_PREMIS, 'premis:creatingApplication');

        // Application name
        $applicationName = $this->dom->createElementNS(self::NS_PREMIS, 'premis:creatingApplicationName');
        $applicationName->appendChild($this->dom->createTextNode('AtoM'));
        $creatingApplication->appendChild($applicationName);

        // Application version
        $applicationVersion = $this->dom->createElementNS(self::NS_PREMIS, 'premis:creatingApplicationVersion');
        $applicationVersion->appendChild($this->dom->createTextNode(qubitConfiguration::VERSION ?? '2.x'));
        $creatingApplication->appendChild($applicationVersion);

        // Date created
        $dateCreated = $this->dom->createElementNS(self::NS_PREMIS, 'premis:dateCreatedByApplication');
        $dateCreated->appendChild($this->dom->createTextNode($this->formatDate($createdAt, 'c')));
        $creatingApplication->appendChild($dateCreated);

        return $creatingApplication;
    }

    /**
     * Build storage element
     *
     * @param mixed $digitalObject
     *
     * @return \DOMElement|null
     */
    protected function buildStorage($digitalObject): ?\DOMElement
    {
        $path = $digitalObject->path ?? null;

        if (!$path) {
            return null;
        }

        $storage = $this->dom->createElementNS(self::NS_PREMIS, 'premis:storage');

        // Content location
        $contentLocation = $this->dom->createElementNS(self::NS_PREMIS, 'premis:contentLocation');

        $contentLocationType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:contentLocationType');
        $contentLocationType->appendChild($this->dom->createTextNode('filepath'));
        $contentLocation->appendChild($contentLocationType);

        $contentLocationValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:contentLocationValue');
        $contentLocationValue->appendChild($this->dom->createTextNode($path));
        $contentLocation->appendChild($contentLocationValue);

        $storage->appendChild($contentLocation);

        // Storage medium
        $storageMedium = $this->dom->createElementNS(self::NS_PREMIS, 'premis:storageMedium');
        $storageMedium->appendChild($this->dom->createTextNode('hard disk'));
        $storage->appendChild($storageMedium);

        return $storage;
    }

    /**
     * Add significant properties
     *
     * @param \DOMElement $object
     * @param mixed       $digitalObject
     */
    protected function addSignificantProperties(\DOMElement $object, $digitalObject): void
    {
        // Add image dimensions if available
        if (isset($digitalObject->width) && isset($digitalObject->height)) {
            $this->addSignificantProperty($object, 'image dimensions', "{$digitalObject->width}x{$digitalObject->height}");
        }

        // Add page count if available (for PDFs)
        if (isset($digitalObject->pageCount)) {
            $this->addSignificantProperty($object, 'page count', (string) $digitalObject->pageCount);
        }

        // Add duration if available (for audio/video)
        if (isset($digitalObject->duration)) {
            $this->addSignificantProperty($object, 'duration', $digitalObject->duration);
        }
    }

    /**
     * Add a single significant property
     *
     * @param \DOMElement $object
     * @param string      $type
     * @param string      $value
     */
    protected function addSignificantProperty(\DOMElement $object, string $type, string $value): void
    {
        $significantProperties = $this->dom->createElementNS(self::NS_PREMIS, 'premis:significantProperties');

        $propertyType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:significantPropertiesType');
        $propertyType->appendChild($this->dom->createTextNode($type));
        $significantProperties->appendChild($propertyType);

        $propertyValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:significantPropertiesValue');
        $propertyValue->appendChild($this->dom->createTextNode($value));
        $significantProperties->appendChild($propertyValue);

        $object->appendChild($significantProperties);
    }

    /**
     * Build relationship element
     *
     * @param mixed $digitalObject
     * @param mixed $parentResource
     *
     * @return \DOMElement|null
     */
    protected function buildRelationship($digitalObject, $parentResource): ?\DOMElement
    {
        if (!$parentResource || !isset($parentResource->id)) {
            return null;
        }

        $relationship = $this->dom->createElementNS(self::NS_PREMIS, 'premis:relationship');

        // Relationship type
        $relationshipType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:relationshipType');
        $relationshipType->appendChild($this->dom->createTextNode('structural'));
        $relationship->appendChild($relationshipType);

        // Relationship subtype
        $relationshipSubType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:relationshipSubType');
        $relationshipSubType->appendChild($this->dom->createTextNode('is representation of'));
        $relationship->appendChild($relationshipSubType);

        // Related object
        $relatedObjectIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:relatedObjectIdentifier');

        $relatedObjectIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:relatedObjectIdentifierType');
        $relatedObjectIdentifierType->appendChild($this->dom->createTextNode('local'));
        $relatedObjectIdentifier->appendChild($relatedObjectIdentifierType);

        $relatedObjectIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:relatedObjectIdentifierValue');
        $relatedObjectIdentifierValue->appendChild($this->dom->createTextNode((string) $parentResource->id));
        $relatedObjectIdentifier->appendChild($relatedObjectIdentifierValue);

        $relationship->appendChild($relatedObjectIdentifier);

        return $relationship;
    }

    /**
     * Add linking rights statement
     *
     * @param \DOMElement $object
     * @param mixed       $parentResource
     */
    protected function addLinkingRightsStatement(\DOMElement $object, $parentResource): void
    {
        // Check for access conditions
        $accessConditions = $this->getValue($parentResource, 'accessConditions');
        if (!$accessConditions) {
            return;
        }

        $linkingRightsStatementIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingRightsStatementIdentifier');

        $linkingRightsStatementIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingRightsStatementIdentifierType');
        $linkingRightsStatementIdentifierType->appendChild($this->dom->createTextNode('local'));
        $linkingRightsStatementIdentifier->appendChild($linkingRightsStatementIdentifierType);

        $linkingRightsStatementIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingRightsStatementIdentifierValue');
        $linkingRightsStatementIdentifierValue->appendChild($this->dom->createTextNode('rights_'.$parentResource->id));
        $linkingRightsStatementIdentifier->appendChild($linkingRightsStatementIdentifierValue);

        $object->appendChild($linkingRightsStatementIdentifier);
    }

    /**
     * Build preservation events
     *
     * @param mixed $resource
     * @param array $digitalObjects
     *
     * @return array
     */
    protected function buildEvents($resource, array $digitalObjects): array
    {
        $events = [];

        // Ingestion event
        $createdAt = $resource->createdAt ?? $resource->created_at ?? date('c');
        $events[] = $this->buildEvent('ingestion', $createdAt, 'Record ingested into the archival system', $digitalObjects);

        // Validation event (if checksum exists)
        foreach ($digitalObjects as $do) {
            if (isset($do->checksum)) {
                $events[] = $this->buildEvent('fixity check', date('c'), 'Checksum validated', [$do]);
                break;
            }
        }

        return $events;
    }

    /**
     * Build a single event element
     *
     * @param string $type
     * @param string $dateTime
     * @param string $detail
     * @param array  $linkedObjects
     *
     * @return \DOMElement
     */
    protected function buildEvent(string $type, string $dateTime, string $detail, array $linkedObjects): \DOMElement
    {
        $event = $this->dom->createElementNS(self::NS_PREMIS, 'premis:event');

        // Event identifier
        $eventIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventIdentifier');

        $eventIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventIdentifierType');
        $eventIdentifierType->appendChild($this->dom->createTextNode('local'));
        $eventIdentifier->appendChild($eventIdentifierType);

        $eventIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventIdentifierValue');
        $eventIdentifierValue->appendChild($this->dom->createTextNode('event_'.uniqid()));
        $eventIdentifier->appendChild($eventIdentifierValue);

        $event->appendChild($eventIdentifier);

        // Event type
        $eventType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventType');
        $eventType->appendChild($this->dom->createTextNode($type));
        $event->appendChild($eventType);

        // Event date/time
        $eventDateTime = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventDateTime');
        $eventDateTime->appendChild($this->dom->createTextNode($this->formatDate($dateTime, 'c')));
        $event->appendChild($eventDateTime);

        // Event detail
        $eventDetailInformation = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventDetailInformation');
        $eventDetail = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventDetail');
        $eventDetail->appendChild($this->dom->createTextNode($detail));
        $eventDetailInformation->appendChild($eventDetail);
        $event->appendChild($eventDetailInformation);

        // Event outcome
        $eventOutcomeInformation = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventOutcomeInformation');
        $eventOutcome = $this->dom->createElementNS(self::NS_PREMIS, 'premis:eventOutcome');
        $eventOutcome->appendChild($this->dom->createTextNode('success'));
        $eventOutcomeInformation->appendChild($eventOutcome);
        $event->appendChild($eventOutcomeInformation);

        // Linking agent
        $linkingAgentIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingAgentIdentifier');

        $linkingAgentIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingAgentIdentifierType');
        $linkingAgentIdentifierType->appendChild($this->dom->createTextNode('local'));
        $linkingAgentIdentifier->appendChild($linkingAgentIdentifierType);

        $linkingAgentIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingAgentIdentifierValue');
        $linkingAgentIdentifierValue->appendChild($this->dom->createTextNode('agent_software'));
        $linkingAgentIdentifier->appendChild($linkingAgentIdentifierValue);

        $linkingAgentRole = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingAgentRole');
        $linkingAgentRole->appendChild($this->dom->createTextNode('executing program'));
        $linkingAgentIdentifier->appendChild($linkingAgentRole);

        $event->appendChild($linkingAgentIdentifier);

        // Linking objects
        foreach ($linkedObjects as $do) {
            $linkingObjectIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingObjectIdentifier');

            $linkingObjectIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingObjectIdentifierType');
            $linkingObjectIdentifierType->appendChild($this->dom->createTextNode('local'));
            $linkingObjectIdentifier->appendChild($linkingObjectIdentifierType);

            $linkingObjectIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:linkingObjectIdentifierValue');
            $linkingObjectIdentifierValue->appendChild($this->dom->createTextNode((string) ($do->id ?? 'unknown')));
            $linkingObjectIdentifier->appendChild($linkingObjectIdentifierValue);

            $event->appendChild($linkingObjectIdentifier);
        }

        return $event;
    }

    /**
     * Build agents
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function buildAgents($resource): array
    {
        $agents = [];

        // Software agent (AtoM)
        $agents[] = $this->buildSoftwareAgent();

        // Repository agent
        $repo = $this->getRepository($resource);
        if ($repo && $repo['name']) {
            $agents[] = $this->buildOrganizationAgent($repo);
        }

        return $agents;
    }

    /**
     * Build software agent
     *
     * @return \DOMElement
     */
    protected function buildSoftwareAgent(): \DOMElement
    {
        $agent = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agent');

        // Agent identifier
        $agentIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentIdentifier');

        $agentIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentIdentifierType');
        $agentIdentifierType->appendChild($this->dom->createTextNode('local'));
        $agentIdentifier->appendChild($agentIdentifierType);

        $agentIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentIdentifierValue');
        $agentIdentifierValue->appendChild($this->dom->createTextNode('agent_software'));
        $agentIdentifier->appendChild($agentIdentifierValue);

        $agent->appendChild($agentIdentifier);

        // Agent name
        $agentName = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentName');
        $agentName->appendChild($this->dom->createTextNode('AtoM (Access to Memory)'));
        $agent->appendChild($agentName);

        // Agent type
        $agentType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentType');
        $agentType->appendChild($this->dom->createTextNode('software'));
        $agent->appendChild($agentType);

        // Agent version
        $agentVersion = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentVersion');
        $agentVersion->appendChild($this->dom->createTextNode(\qubitConfiguration::VERSION ?? '2.x'));
        $agent->appendChild($agentVersion);

        return $agent;
    }

    /**
     * Build organization agent
     *
     * @param array $repo
     *
     * @return \DOMElement
     */
    protected function buildOrganizationAgent(array $repo): \DOMElement
    {
        $agent = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agent');

        // Agent identifier
        $agentIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentIdentifier');

        $agentIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentIdentifierType');
        $agentIdentifierType->appendChild($this->dom->createTextNode('local'));
        $agentIdentifier->appendChild($agentIdentifierType);

        $agentIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentIdentifierValue');
        $agentIdentifierValue->appendChild($this->dom->createTextNode('agent_repository_'.($repo['id'] ?? 'unknown')));
        $agentIdentifier->appendChild($agentIdentifierValue);

        $agent->appendChild($agentIdentifier);

        // Agent name
        $agentName = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentName');
        $agentName->appendChild($this->dom->createTextNode($repo['name']));
        $agent->appendChild($agentName);

        // Agent type
        $agentType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:agentType');
        $agentType->appendChild($this->dom->createTextNode('organization'));
        $agent->appendChild($agentType);

        return $agent;
    }

    /**
     * Build rights statements
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function buildRights($resource): array
    {
        $rights = [];

        // Access conditions
        $accessConditions = $this->getValue($resource, 'accessConditions');
        if ($accessConditions) {
            $rights[] = $this->buildRightsStatement($resource, 'statute', $accessConditions);
        }

        // Reproduction conditions
        $reproConditions = $this->getValue($resource, 'reproductionConditions');
        if ($reproConditions) {
            $rights[] = $this->buildRightsStatement($resource, 'copyright', $reproConditions);
        }

        return $rights;
    }

    /**
     * Build a rights statement
     *
     * @param mixed  $resource
     * @param string $basis (copyright, license, statute, other)
     * @param string $note
     *
     * @return \DOMElement
     */
    protected function buildRightsStatement($resource, string $basis, string $note): \DOMElement
    {
        $rightsStatement = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsStatement');

        // Rights statement identifier
        $rightsStatementIdentifier = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsStatementIdentifier');

        $rightsStatementIdentifierType = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsStatementIdentifierType');
        $rightsStatementIdentifierType->appendChild($this->dom->createTextNode('local'));
        $rightsStatementIdentifier->appendChild($rightsStatementIdentifierType);

        $rightsStatementIdentifierValue = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsStatementIdentifierValue');
        $rightsStatementIdentifierValue->appendChild($this->dom->createTextNode('rights_'.$resource->id));
        $rightsStatementIdentifier->appendChild($rightsStatementIdentifierValue);

        $rightsStatement->appendChild($rightsStatementIdentifier);

        // Rights basis
        $rightsBasis = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsBasis');
        $rightsBasis->appendChild($this->dom->createTextNode($basis));
        $rightsStatement->appendChild($rightsBasis);

        // Rights granted
        $rightsGranted = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsGranted');

        $act = $this->dom->createElementNS(self::NS_PREMIS, 'premis:act');
        $act->appendChild($this->dom->createTextNode('use'));
        $rightsGranted->appendChild($act);

        $restriction = $this->dom->createElementNS(self::NS_PREMIS, 'premis:restriction');
        $restriction->appendChild($this->dom->createTextNode('conditional'));
        $rightsGranted->appendChild($restriction);

        // Rights granted note
        $rightsGrantedNote = $this->dom->createElementNS(self::NS_PREMIS, 'premis:rightsGrantedNote');
        $rightsGrantedNote->appendChild($this->dom->createTextNode($note));
        $rightsGranted->appendChild($rightsGrantedNote);

        $rightsStatement->appendChild($rightsGranted);

        return $rightsStatement;
    }

    /**
     * Get digital objects for resource
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getDigitalObjectsForResource($resource): array
    {
        // If resource is a digital object itself
        if ($resource instanceof \QubitDigitalObject || 'QubitDigitalObject' === get_class($resource)) {
            return [$resource];
        }

        // Get digital objects from information object
        return $this->getDigitalObjects($resource);
    }

    /**
     * Get original name from digital object
     *
     * @param mixed $digitalObject
     *
     * @return string|null
     */
    protected function getOriginalName($digitalObject): ?string
    {
        return $digitalObject->name ?? $digitalObject->filename ?? null;
    }

    /**
     * Get file size from digital object
     *
     * @param mixed $digitalObject
     *
     * @return int|null
     */
    protected function getFileSize($digitalObject): ?int
    {
        return $digitalObject->byteSize ?? $digitalObject->fileSize ?? null;
    }
}
