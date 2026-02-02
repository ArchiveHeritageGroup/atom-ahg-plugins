<?php

/**
 * LidoExporter - LIDO (Lightweight Information Describing Objects) Exporter
 *
 * Exports museum object descriptions to LIDO 1.1 XML format.
 *
 * @see http://www.lido-schema.org/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class LidoExporter extends AbstractXmlExporter
{
    /**
     * LIDO namespace
     */
    public const NS_LIDO = 'http://www.lido-schema.org';

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_LIDO;
        $this->primaryPrefix = 'lido';
        $this->namespaces = [
            'lido' => self::NS_LIDO,
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'gml' => 'http://www.opengis.net/gml',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'lido';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'LIDO';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Museums';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDocument($resource): \DOMDocument
    {
        // Create root wrapper element
        $lidoWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:lidoWrap');
        $this->dom->appendChild($lidoWrap);

        // Add namespace declarations
        $this->addNamespace($lidoWrap, 'xsi', $this->namespaces['xsi']);
        $this->addNamespace($lidoWrap, 'gml', $this->namespaces['gml']);
        $lidoWrap->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_LIDO.' http://www.lido-schema.org/schema/v1.1/lido-v1.1.xsd'
        );

        // Build LIDO record
        $lido = $this->buildLidoRecord($resource);
        $lidoWrap->appendChild($lido);

        // If including children, add them as separate LIDO records
        if ($this->options['includeChildren']) {
            $children = $this->getChildren($resource);
            foreach ($children as $child) {
                $childLido = $this->buildLidoRecord($child);
                $lidoWrap->appendChild($childLido);
            }
        }

        return $this->dom;
    }

    /**
     * Build a single LIDO record
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildLidoRecord($resource): \DOMElement
    {
        $lido = $this->dom->createElementNS(self::NS_LIDO, 'lido:lido');

        // LIDO Record ID
        $lidoRecID = $this->dom->createElementNS(self::NS_LIDO, 'lido:lidoRecID');
        $lidoRecID->setAttribute('lido:type', 'local');
        $lidoRecID->appendChild($this->dom->createTextNode($this->getIdentifier($resource)));
        $lido->appendChild($lidoRecID);

        // Category (object classification)
        $category = $this->buildCategory($resource);
        if ($category) {
            $lido->appendChild($category);
        }

        // Descriptive metadata
        $descriptiveMetadata = $this->buildDescriptiveMetadata($resource);
        $lido->appendChild($descriptiveMetadata);

        // Administrative metadata
        $administrativeMetadata = $this->buildAdministrativeMetadata($resource);
        $lido->appendChild($administrativeMetadata);

        return $lido;
    }

    /**
     * Build category element
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildCategory($resource): ?\DOMElement
    {
        $level = $this->getLevelOfDescription($resource);
        if (!$level) {
            $level = 'Object';
        }

        $category = $this->dom->createElementNS(self::NS_LIDO, 'lido:category');

        $conceptID = $this->dom->createElementNS(self::NS_LIDO, 'lido:conceptID');
        $conceptID->setAttribute('lido:type', 'URI');
        $conceptID->appendChild($this->dom->createTextNode('http://www.cidoc-crm.org/cidoc-crm/E22_Man-Made_Object'));
        $category->appendChild($conceptID);

        $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
        $term->appendChild($this->dom->createTextNode($level));
        $category->appendChild($term);

        return $category;
    }

    /**
     * Build descriptive metadata section
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildDescriptiveMetadata($resource): \DOMElement
    {
        $descriptiveMetadata = $this->dom->createElementNS(self::NS_LIDO, 'lido:descriptiveMetadata');
        $descriptiveMetadata->setAttribute('xml:lang', 'en');

        // Object classification wrapper
        $objectClassificationWrap = $this->buildObjectClassificationWrap($resource);
        $descriptiveMetadata->appendChild($objectClassificationWrap);

        // Object identification wrapper
        $objectIdentificationWrap = $this->buildObjectIdentificationWrap($resource);
        $descriptiveMetadata->appendChild($objectIdentificationWrap);

        // Event wrapper (production, acquisition, etc.)
        $eventWrap = $this->buildEventWrap($resource);
        if ($eventWrap) {
            $descriptiveMetadata->appendChild($eventWrap);
        }

        // Object relation wrapper (related objects)
        $objectRelationWrap = $this->buildObjectRelationWrap($resource);
        if ($objectRelationWrap) {
            $descriptiveMetadata->appendChild($objectRelationWrap);
        }

        return $descriptiveMetadata;
    }

    /**
     * Build object classification wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildObjectClassificationWrap($resource): \DOMElement
    {
        $objectClassificationWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectClassificationWrap');

        // Object work type wrapper
        $objectWorkTypeWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectWorkTypeWrap');
        $objectClassificationWrap->appendChild($objectWorkTypeWrap);

        // Object work type
        $objectWorkType = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectWorkType');
        $objectWorkTypeWrap->appendChild($objectWorkType);

        // Get object type from level of description or other field
        $level = $this->getLevelOfDescription($resource);
        $typeValue = $this->mapLevelToObjectType($level);

        $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
        $term->appendChild($this->dom->createTextNode($typeValue));
        $objectWorkType->appendChild($term);

        // Classification wrapper (subjects)
        $classificationWrap = $this->buildClassificationWrap($resource);
        if ($classificationWrap) {
            $objectClassificationWrap->appendChild($classificationWrap);
        }

        return $objectClassificationWrap;
    }

    /**
     * Build classification wrapper (subjects)
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildClassificationWrap($resource): ?\DOMElement
    {
        $subjects = $this->getSubjects($resource);

        if (empty($subjects)) {
            return null;
        }

        $classificationWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:classificationWrap');

        foreach ($subjects as $subject) {
            $classification = $this->dom->createElementNS(self::NS_LIDO, 'lido:classification');
            $classification->setAttribute('lido:type', 'subject');

            $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
            $term->appendChild($this->dom->createTextNode($subject));
            $classification->appendChild($term);

            $classificationWrap->appendChild($classification);
        }

        return $classificationWrap;
    }

    /**
     * Build object identification wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildObjectIdentificationWrap($resource): \DOMElement
    {
        $objectIdentificationWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectIdentificationWrap');

        // Title wrapper
        $titleWrap = $this->buildTitleWrap($resource);
        $objectIdentificationWrap->appendChild($titleWrap);

        // Inscriptions wrapper (if any)
        $inscriptionsWrap = $this->buildInscriptionsWrap($resource);
        if ($inscriptionsWrap) {
            $objectIdentificationWrap->appendChild($inscriptionsWrap);
        }

        // Repository wrapper
        $repositoryWrap = $this->buildRepositoryWrap($resource);
        if ($repositoryWrap) {
            $objectIdentificationWrap->appendChild($repositoryWrap);
        }

        // Object description wrapper (scope and content)
        $objectDescriptionWrap = $this->buildObjectDescriptionWrap($resource);
        if ($objectDescriptionWrap) {
            $objectIdentificationWrap->appendChild($objectDescriptionWrap);
        }

        // Object measurements wrapper
        $objectMeasurementsWrap = $this->buildObjectMeasurementsWrap($resource);
        if ($objectMeasurementsWrap) {
            $objectIdentificationWrap->appendChild($objectMeasurementsWrap);
        }

        return $objectIdentificationWrap;
    }

    /**
     * Build title wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildTitleWrap($resource): \DOMElement
    {
        $titleWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:titleWrap');

        $titleSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:titleSet');
        $titleWrap->appendChild($titleSet);

        $title = $this->getValue($resource, 'title');
        $appellationValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:appellationValue');
        $appellationValue->setAttribute('lido:pref', 'preferred');
        $appellationValue->appendChild($this->dom->createTextNode($title ?? 'Untitled'));
        $titleSet->appendChild($appellationValue);

        return $titleWrap;
    }

    /**
     * Build inscriptions wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildInscriptionsWrap($resource): ?\DOMElement
    {
        // Check for inscription data (could be in notes or other field)
        $inscription = $this->getValue($resource, 'inscription') ?? $this->getValue($resource, 'notes');

        if (!$inscription) {
            return null;
        }

        $inscriptionsWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:inscriptionsWrap');

        $inscriptions = $this->dom->createElementNS(self::NS_LIDO, 'lido:inscriptions');
        $inscriptionsWrap->appendChild($inscriptions);

        $inscriptionTranscription = $this->dom->createElementNS(self::NS_LIDO, 'lido:inscriptionTranscription');
        $inscriptionTranscription->appendChild($this->dom->createTextNode($inscription));
        $inscriptions->appendChild($inscriptionTranscription);

        return $inscriptionsWrap;
    }

    /**
     * Build repository wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildRepositoryWrap($resource): ?\DOMElement
    {
        $repo = $this->getRepository($resource);

        if (!$repo || !$repo['name']) {
            return null;
        }

        $repositoryWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:repositoryWrap');

        $repositorySet = $this->dom->createElementNS(self::NS_LIDO, 'lido:repositorySet');
        $repositorySet->setAttribute('lido:type', 'current');
        $repositoryWrap->appendChild($repositorySet);

        // Repository name
        $repositoryName = $this->dom->createElementNS(self::NS_LIDO, 'lido:repositoryName');
        $repositorySet->appendChild($repositoryName);

        $legalBodyName = $this->dom->createElementNS(self::NS_LIDO, 'lido:legalBodyName');
        $repositoryName->appendChild($legalBodyName);

        $appellationValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:appellationValue');
        $appellationValue->appendChild($this->dom->createTextNode($repo['name']));
        $legalBodyName->appendChild($appellationValue);

        // Work ID (repository identifier)
        if ($repo['identifier']) {
            $workID = $this->dom->createElementNS(self::NS_LIDO, 'lido:workID');
            $workID->setAttribute('lido:type', 'inventory number');
            $workID->appendChild($this->dom->createTextNode($this->getIdentifier($resource)));
            $repositorySet->appendChild($workID);
        }

        return $repositoryWrap;
    }

    /**
     * Build object description wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildObjectDescriptionWrap($resource): ?\DOMElement
    {
        $description = $this->getValue($resource, 'scopeAndContent');

        if (!$description) {
            return null;
        }

        $objectDescriptionWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectDescriptionWrap');

        $objectDescriptionSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectDescriptionSet');
        $objectDescriptionWrap->appendChild($objectDescriptionSet);

        $descriptiveNoteValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:descriptiveNoteValue');
        $descriptiveNoteValue->appendChild($this->dom->createTextNode($description));
        $objectDescriptionSet->appendChild($descriptiveNoteValue);

        return $objectDescriptionWrap;
    }

    /**
     * Build object measurements wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildObjectMeasurementsWrap($resource): ?\DOMElement
    {
        $extent = $this->getValue($resource, 'extentAndMedium');

        if (!$extent) {
            return null;
        }

        $objectMeasurementsWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectMeasurementsWrap');

        $objectMeasurementsSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectMeasurementsSet');
        $objectMeasurementsWrap->appendChild($objectMeasurementsSet);

        $displayObjectMeasurements = $this->dom->createElementNS(self::NS_LIDO, 'lido:displayObjectMeasurements');
        $displayObjectMeasurements->appendChild($this->dom->createTextNode($extent));
        $objectMeasurementsSet->appendChild($displayObjectMeasurements);

        return $objectMeasurementsWrap;
    }

    /**
     * Build event wrapper (production, etc.)
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildEventWrap($resource): ?\DOMElement
    {
        $eventWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventWrap');
        $hasEvents = false;

        // Production event (creator + date)
        $productionEvent = $this->buildProductionEvent($resource);
        if ($productionEvent) {
            $eventWrap->appendChild($productionEvent);
            $hasEvents = true;
        }

        // Acquisition event (if repository info)
        $acquisitionEvent = $this->buildAcquisitionEvent($resource);
        if ($acquisitionEvent) {
            $eventWrap->appendChild($acquisitionEvent);
            $hasEvents = true;
        }

        return $hasEvents ? $eventWrap : null;
    }

    /**
     * Build production event
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildProductionEvent($resource): ?\DOMElement
    {
        $creators = $this->getCreators($resource);
        $dateRange = $this->getDateRange($resource);

        if (empty($creators) && !$dateRange['display'] && !$dateRange['start']) {
            return null;
        }

        $eventSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventSet');

        $event = $this->dom->createElementNS(self::NS_LIDO, 'lido:event');
        $eventSet->appendChild($event);

        // Event type
        $eventType = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventType');
        $event->appendChild($eventType);

        $eventTypeTerm = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
        $eventTypeTerm->appendChild($this->dom->createTextNode('Production'));
        $eventType->appendChild($eventTypeTerm);

        // Actors (creators)
        foreach ($creators as $creator) {
            if ($creator['name']) {
                $eventActor = $this->buildEventActor($creator);
                $event->appendChild($eventActor);
            }
        }

        // Date
        if ($dateRange['display'] || $dateRange['start']) {
            $eventDate = $this->buildEventDate($dateRange);
            $event->appendChild($eventDate);
        }

        // Places
        $places = $this->getPlaces($resource);
        foreach ($places as $place) {
            $eventPlace = $this->buildEventPlace($place);
            $event->appendChild($eventPlace);
        }

        return $eventSet;
    }

    /**
     * Build event actor
     *
     * @param array $creator
     *
     * @return \DOMElement
     */
    protected function buildEventActor(array $creator): \DOMElement
    {
        $eventActor = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventActor');

        $actorInRole = $this->dom->createElementNS(self::NS_LIDO, 'lido:actorInRole');
        $eventActor->appendChild($actorInRole);

        // Actor
        $actor = $this->dom->createElementNS(self::NS_LIDO, 'lido:actor');
        $actorInRole->appendChild($actor);

        // Actor name
        $nameActorSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:nameActorSet');
        $actor->appendChild($nameActorSet);

        $appellationValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:appellationValue');
        $appellationValue->appendChild($this->dom->createTextNode($creator['name']));
        $nameActorSet->appendChild($appellationValue);

        // Role
        $roleActor = $this->dom->createElementNS(self::NS_LIDO, 'lido:roleActor');
        $actorInRole->appendChild($roleActor);

        $roleTerm = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
        $roleTerm->appendChild($this->dom->createTextNode('creator'));
        $roleActor->appendChild($roleTerm);

        return $eventActor;
    }

    /**
     * Build event date
     *
     * @param array $dateRange
     *
     * @return \DOMElement
     */
    protected function buildEventDate(array $dateRange): \DOMElement
    {
        $eventDate = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventDate');

        // Display date
        if ($dateRange['display']) {
            $displayDate = $this->dom->createElementNS(self::NS_LIDO, 'lido:displayDate');
            $displayDate->appendChild($this->dom->createTextNode($dateRange['display']));
            $eventDate->appendChild($displayDate);
        }

        // Date range
        $date = $this->dom->createElementNS(self::NS_LIDO, 'lido:date');
        $eventDate->appendChild($date);

        if ($dateRange['start']) {
            $earliestDate = $this->dom->createElementNS(self::NS_LIDO, 'lido:earliestDate');
            $earliestDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['start'], 'Y-m-d')));
            $date->appendChild($earliestDate);
        }

        if ($dateRange['end']) {
            $latestDate = $this->dom->createElementNS(self::NS_LIDO, 'lido:latestDate');
            $latestDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['end'], 'Y-m-d')));
            $date->appendChild($latestDate);
        } elseif ($dateRange['start']) {
            // If no end date, use start date
            $latestDate = $this->dom->createElementNS(self::NS_LIDO, 'lido:latestDate');
            $latestDate->appendChild($this->dom->createTextNode($this->formatDate($dateRange['start'], 'Y-m-d')));
            $date->appendChild($latestDate);
        }

        return $eventDate;
    }

    /**
     * Build event place
     *
     * @param string $place
     *
     * @return \DOMElement
     */
    protected function buildEventPlace(string $place): \DOMElement
    {
        $eventPlace = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventPlace');

        $placeEl = $this->dom->createElementNS(self::NS_LIDO, 'lido:place');
        $eventPlace->appendChild($placeEl);

        $namePlaceSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:namePlaceSet');
        $placeEl->appendChild($namePlaceSet);

        $appellationValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:appellationValue');
        $appellationValue->appendChild($this->dom->createTextNode($place));
        $namePlaceSet->appendChild($appellationValue);

        return $eventPlace;
    }

    /**
     * Build acquisition event
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildAcquisitionEvent($resource): ?\DOMElement
    {
        $repo = $this->getRepository($resource);
        if (!$repo || !$repo['name']) {
            return null;
        }

        $eventSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventSet');

        $event = $this->dom->createElementNS(self::NS_LIDO, 'lido:event');
        $eventSet->appendChild($event);

        // Event type
        $eventType = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventType');
        $event->appendChild($eventType);

        $eventTypeTerm = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
        $eventTypeTerm->appendChild($this->dom->createTextNode('Acquisition'));
        $eventType->appendChild($eventTypeTerm);

        // Repository as actor
        $eventActor = $this->dom->createElementNS(self::NS_LIDO, 'lido:eventActor');
        $event->appendChild($eventActor);

        $actorInRole = $this->dom->createElementNS(self::NS_LIDO, 'lido:actorInRole');
        $eventActor->appendChild($actorInRole);

        $actor = $this->dom->createElementNS(self::NS_LIDO, 'lido:actor');
        $actorInRole->appendChild($actor);

        $nameActorSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:nameActorSet');
        $actor->appendChild($nameActorSet);

        $appellationValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:appellationValue');
        $appellationValue->appendChild($this->dom->createTextNode($repo['name']));
        $nameActorSet->appendChild($appellationValue);

        return $eventSet;
    }

    /**
     * Build object relation wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildObjectRelationWrap($resource): ?\DOMElement
    {
        $subjects = $this->getSubjects($resource);

        if (empty($subjects)) {
            return null;
        }

        $objectRelationWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:objectRelationWrap');

        // Subject wrapper
        $subjectWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:subjectWrap');
        $objectRelationWrap->appendChild($subjectWrap);

        foreach ($subjects as $subject) {
            $subjectSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:subjectSet');
            $subjectWrap->appendChild($subjectSet);

            $subjectEl = $this->dom->createElementNS(self::NS_LIDO, 'lido:subject');
            $subjectSet->appendChild($subjectEl);

            $subjectConcept = $this->dom->createElementNS(self::NS_LIDO, 'lido:subjectConcept');
            $subjectEl->appendChild($subjectConcept);

            $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
            $term->appendChild($this->dom->createTextNode($subject));
            $subjectConcept->appendChild($term);
        }

        return $objectRelationWrap;
    }

    /**
     * Build administrative metadata section
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildAdministrativeMetadata($resource): \DOMElement
    {
        $administrativeMetadata = $this->dom->createElementNS(self::NS_LIDO, 'lido:administrativeMetadata');
        $administrativeMetadata->setAttribute('xml:lang', 'en');

        // Rights work wrapper
        $rightsWorkWrap = $this->buildRightsWorkWrap($resource);
        if ($rightsWorkWrap) {
            $administrativeMetadata->appendChild($rightsWorkWrap);
        }

        // Record wrapper
        $recordWrap = $this->buildRecordWrap($resource);
        $administrativeMetadata->appendChild($recordWrap);

        // Resource wrapper (digital objects)
        if ($this->options['includeDigitalObjects']) {
            $resourceWrap = $this->buildResourceWrap($resource);
            if ($resourceWrap) {
                $administrativeMetadata->appendChild($resourceWrap);
            }
        }

        return $administrativeMetadata;
    }

    /**
     * Build rights work wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildRightsWorkWrap($resource): ?\DOMElement
    {
        $accessConditions = $this->getValue($resource, 'accessConditions');
        $reproConditions = $this->getValue($resource, 'reproductionConditions');

        if (!$accessConditions && !$reproConditions) {
            return null;
        }

        $rightsWorkWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:rightsWorkWrap');

        if ($accessConditions) {
            $rightsWorkSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:rightsWorkSet');
            $rightsWorkWrap->appendChild($rightsWorkSet);

            $rightsType = $this->dom->createElementNS(self::NS_LIDO, 'lido:rightsType');
            $rightsWorkSet->appendChild($rightsType);

            $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
            $term->appendChild($this->dom->createTextNode('access'));
            $rightsType->appendChild($term);

            $creditLine = $this->dom->createElementNS(self::NS_LIDO, 'lido:creditLine');
            $creditLine->appendChild($this->dom->createTextNode($accessConditions));
            $rightsWorkSet->appendChild($creditLine);
        }

        if ($reproConditions) {
            $rightsWorkSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:rightsWorkSet');
            $rightsWorkWrap->appendChild($rightsWorkSet);

            $rightsType = $this->dom->createElementNS(self::NS_LIDO, 'lido:rightsType');
            $rightsWorkSet->appendChild($rightsType);

            $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
            $term->appendChild($this->dom->createTextNode('reproduction'));
            $rightsType->appendChild($term);

            $creditLine = $this->dom->createElementNS(self::NS_LIDO, 'lido:creditLine');
            $creditLine->appendChild($this->dom->createTextNode($reproConditions));
            $rightsWorkSet->appendChild($creditLine);
        }

        return $rightsWorkWrap;
    }

    /**
     * Build record wrapper
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildRecordWrap($resource): \DOMElement
    {
        $recordWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:recordWrap');

        // Record ID
        $recordID = $this->dom->createElementNS(self::NS_LIDO, 'lido:recordID');
        $recordID->setAttribute('lido:type', 'local');
        $recordID->appendChild($this->dom->createTextNode($this->getIdentifier($resource)));
        $recordWrap->appendChild($recordID);

        // Record type
        $recordType = $this->dom->createElementNS(self::NS_LIDO, 'lido:recordType');
        $recordWrap->appendChild($recordType);

        $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
        $term->appendChild($this->dom->createTextNode('item'));
        $recordType->appendChild($term);

        // Record source
        $repo = $this->getRepository($resource);
        $recordSource = $this->dom->createElementNS(self::NS_LIDO, 'lido:recordSource');
        $recordWrap->appendChild($recordSource);

        $legalBodyName = $this->dom->createElementNS(self::NS_LIDO, 'lido:legalBodyName');
        $recordSource->appendChild($legalBodyName);

        $appellationValue = $this->dom->createElementNS(self::NS_LIDO, 'lido:appellationValue');
        $appellationValue->appendChild($this->dom->createTextNode($repo['name'] ?? 'Archive'));
        $legalBodyName->appendChild($appellationValue);

        // Record info link (URL to AtoM record)
        $recordInfoLink = $this->dom->createElementNS(self::NS_LIDO, 'lido:recordInfoLink');
        $recordInfoLink->appendChild($this->dom->createTextNode($this->getResourceUri($resource)));
        $recordWrap->appendChild($recordInfoLink);

        return $recordWrap;
    }

    /**
     * Build resource wrapper (digital objects)
     *
     * @param mixed $resource
     *
     * @return \DOMElement|null
     */
    protected function buildResourceWrap($resource): ?\DOMElement
    {
        $digitalObjects = $this->getDigitalObjects($resource);

        if (empty($digitalObjects)) {
            return null;
        }

        $resourceWrap = $this->dom->createElementNS(self::NS_LIDO, 'lido:resourceWrap');

        foreach ($digitalObjects as $do) {
            $resourceSet = $this->dom->createElementNS(self::NS_LIDO, 'lido:resourceSet');
            $resourceWrap->appendChild($resourceSet);

            // Resource ID
            $resourceID = $this->dom->createElementNS(self::NS_LIDO, 'lido:resourceID');
            $resourceID->setAttribute('lido:type', 'local');
            $resourceID->appendChild($this->dom->createTextNode((string) ($do->id ?? 'unknown')));
            $resourceSet->appendChild($resourceID);

            // Resource representation
            $resourceRepresentation = $this->dom->createElementNS(self::NS_LIDO, 'lido:resourceRepresentation');
            $resourceRepresentation->setAttribute('lido:type', 'image_master');
            $resourceSet->appendChild($resourceRepresentation);

            // Link resource
            if (isset($do->path)) {
                $linkResource = $this->dom->createElementNS(self::NS_LIDO, 'lido:linkResource');
                $linkResource->appendChild($this->dom->createTextNode(
                    rtrim($this->baseUri, '/').'/'.ltrim($do->path, '/')
                ));
                $resourceRepresentation->appendChild($linkResource);
            }

            // Resource type
            $resourceType = $this->dom->createElementNS(self::NS_LIDO, 'lido:resourceType');
            $resourceSet->appendChild($resourceType);

            $term = $this->dom->createElementNS(self::NS_LIDO, 'lido:term');
            $term->appendChild($this->dom->createTextNode($do->mimeType ?? 'image'));
            $resourceType->appendChild($term);

            // Resource description (filename)
            if (isset($do->name)) {
                $resourceDescription = $this->dom->createElementNS(self::NS_LIDO, 'lido:resourceDescription');
                $resourceDescription->setAttribute('lido:type', 'caption');
                $resourceDescription->appendChild($this->dom->createTextNode($do->name));
                $resourceSet->appendChild($resourceDescription);
            }
        }

        return $resourceWrap;
    }

    /**
     * Map level of description to LIDO object type
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
            'File' => 'file',
            'Item' => 'object',
        ];

        return $map[$level] ?? 'object';
    }
}
