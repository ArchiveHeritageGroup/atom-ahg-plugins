<?php

namespace AhgFederation;

/**
 * Federation Harvest Service
 *
 * Orchestrates the harvesting workflow including:
 * - Harvesting records from peer OAI-PMH repositories
 * - Importing records into the local AtoM instance
 * - Tracking provenance for harvested records
 */
class HarvestService
{
    protected HarvestClient $client;
    protected FederationProvenance $provenance;
    protected array $stats;

    /**
     * Preferred metadata formats in order of preference
     */
    protected array $preferredFormats = ['oai_heritage', 'oai_dc', 'oai_ead'];

    public function __construct()
    {
        $this->client = new HarvestClient();
        $this->provenance = new FederationProvenance();
        $this->resetStats();
    }

    /**
     * Reset harvest statistics
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'errors' => 0,
            'errorMessages' => [],
        ];
    }

    /**
     * Harvest records from a federation peer
     *
     * @param int $peerId The peer ID from federation_peer table
     * @param array $options Harvest options (from, until, set, metadataPrefix, fullHarvest)
     * @return HarvestResult
     */
    public function harvestPeer(int $peerId, array $options = []): HarvestResult
    {
        $this->resetStats();
        \AhgCore\Core\AhgDb::init();

        // Get peer configuration
        $peer = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->where('id', $peerId)
            ->first();

        if (!$peer) {
            throw new HarvestException("Peer not found: $peerId");
        }

        if (!$peer->is_active) {
            throw new HarvestException("Peer is not active: {$peer->name}");
        }

        // Determine metadata format
        $metadataPrefix = $options['metadataPrefix'] ?? null;
        if (!$metadataPrefix) {
            $metadataPrefix = $this->detectBestFormat($peer->base_url);
        }

        // Set date range for incremental harvest
        $from = null;
        $until = null;

        if (empty($options['fullHarvest'])) {
            // Incremental harvest - use last harvest date
            if ($peer->last_harvest_at) {
                $from = gmdate('Y-m-d\TH:i:s\Z', strtotime($peer->last_harvest_at));
            }
        }

        if (!empty($options['from'])) {
            $from = $options['from'];
        }
        if (!empty($options['until'])) {
            $until = $options['until'];
        }

        // Build harvest parameters
        $harvestParams = [
            'metadataPrefix' => $metadataPrefix,
        ];
        if ($from) {
            $harvestParams['from'] = $from;
        }
        if ($until) {
            $harvestParams['until'] = $until;
        }
        if (!empty($options['set'])) {
            $harvestParams['set'] = $options['set'];
        }

        // Start harvesting
        $startTime = microtime(true);

        try {
            foreach ($this->client->listRecords($peer->base_url, $harvestParams) as $oaiRecord) {
                $this->stats['total']++;

                try {
                    $this->processRecord($oaiRecord, $peerId, $metadataPrefix);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->stats['errorMessages'][] = $oaiRecord['header']['identifier'] . ': ' . $e->getMessage();
                }
            }

            // Update last harvest timestamp
            \Illuminate\Database\Capsule\Manager::table('federation_peer')
                ->where('id', $peerId)
                ->update([
                    'last_harvest_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

        } catch (HarvestException $e) {
            $this->stats['errors']++;
            $this->stats['errorMessages'][] = 'Harvest failed: ' . $e->getMessage();
        }

        $duration = microtime(true) - $startTime;

        return new HarvestResult(
            peerId: $peerId,
            peerName: $peer->name,
            metadataPrefix: $metadataPrefix,
            from: $from,
            until: $until,
            set: $options['set'] ?? null,
            stats: $this->stats,
            duration: $duration
        );
    }

    /**
     * Process a single harvested record
     */
    protected function processRecord(array $oaiRecord, int $peerId, string $metadataPrefix): void
    {
        $header = $oaiRecord['header'];
        $identifier = $header['identifier'];

        // Check if record was deleted
        if ($header['status'] === 'deleted') {
            $this->handleDeletedRecord($identifier, $peerId);
            return;
        }

        // Skip records without metadata
        if (empty($oaiRecord['metadata'])) {
            $this->stats['skipped']++;
            return;
        }

        // Check if we already have this record
        $existing = $this->provenance->findBySourceIdentifier($peerId, $identifier);

        if ($existing) {
            // Update existing record
            $this->updateRecord($oaiRecord, $existing, $peerId, $metadataPrefix);
        } else {
            // Import new record
            $this->importRecord($oaiRecord, $peerId, $metadataPrefix);
        }
    }

    /**
     * Import a new record from OAI harvest
     */
    public function importRecord(array $oaiRecord, int $peerId, string $metadataPrefix): \QubitInformationObject
    {
        $metadata = $oaiRecord['metadata'];
        $header = $oaiRecord['header'];

        // Create new information object
        $object = new \QubitInformationObject();
        $object->parentId = \QubitInformationObject::ROOT_ID;

        // Map metadata to object based on format
        $this->mapMetadataToObject($metadata, $object, $metadataPrefix);

        // Set publication status (draft by default for harvested records)
        $object->setPublicationStatus(\QubitTerm::PUBLICATION_STATUS_DRAFT_ID);

        // Save the object
        $object->save();

        // Record provenance
        $this->provenance->recordHarvest(
            objectId: $object->id,
            peerId: $peerId,
            sourceIdentifier: $header['identifier'],
            metadataFormat: $metadataPrefix,
            action: 'created'
        );

        $this->stats['created']++;

        return $object;
    }

    /**
     * Update an existing record from OAI harvest
     */
    public function updateRecord(array $oaiRecord, \QubitInformationObject $existing, int $peerId, string $metadataPrefix): void
    {
        $metadata = $oaiRecord['metadata'];
        $header = $oaiRecord['header'];

        // Map metadata to existing object
        $this->mapMetadataToObject($metadata, $existing, $metadataPrefix);

        // Save the object
        $existing->save();

        // Record provenance
        $this->provenance->recordHarvest(
            objectId: $existing->id,
            peerId: $peerId,
            sourceIdentifier: $header['identifier'],
            metadataFormat: $metadataPrefix,
            action: 'updated'
        );

        $this->stats['updated']++;
    }

    /**
     * Handle a deleted record
     */
    protected function handleDeletedRecord(string $identifier, int $peerId): void
    {
        $existing = $this->provenance->findBySourceIdentifier($peerId, $identifier);

        if ($existing) {
            // Mark as deleted in provenance log (don't actually delete)
            $this->provenance->recordHarvest(
                objectId: $existing->id,
                peerId: $peerId,
                sourceIdentifier: $identifier,
                metadataFormat: '',
                action: 'deleted'
            );

            $this->stats['deleted']++;
        } else {
            $this->stats['skipped']++;
        }
    }

    /**
     * Map metadata to QubitInformationObject based on format
     */
    protected function mapMetadataToObject(array $metadata, \QubitInformationObject $object, string $metadataPrefix): void
    {
        switch ($metadataPrefix) {
            case 'oai_heritage':
                $this->mapHeritageToObject($metadata, $object);
                break;

            case 'oai_dc':
                $this->mapDublinCoreToObject($metadata, $object);
                break;

            default:
                // For unknown formats, try Dublin Core mapping
                $this->mapDublinCoreToObject($metadata, $object);
        }
    }

    /**
     * Map Heritage format metadata to object
     */
    protected function mapHeritageToObject(array $metadata, \QubitInformationObject $object): void
    {
        // Title
        if (!empty($metadata['title'])) {
            $object->title = $metadata['title'];
        }

        // Description / Scope and Content
        if (!empty($metadata['description'])) {
            $object->scopeAndContent = $metadata['description'];
        }

        // Reference code
        if (!empty($metadata['referenceCode'])) {
            $object->identifier = $metadata['referenceCode'];
        }

        // Extent
        if (!empty($metadata['extent'])) {
            $object->extentAndMedium = $metadata['extent'];
        }

        // Access conditions
        if (!empty($metadata['accessConditions'])) {
            $object->accessConditions = $metadata['accessConditions'];
        }

        // Archival history / Provenance
        if (!empty($metadata['provenance'])) {
            $object->archivalHistory = $metadata['provenance'];
        }

        // Arrangement
        if (!empty($metadata['arrangement'])) {
            $object->arrangement = $metadata['arrangement'];
        }

        // Level of description
        if (!empty($metadata['levelOfDescription'])) {
            $level = $this->findOrCreateTerm($metadata['levelOfDescription'], \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID);
            if ($level) {
                $object->levelOfDescriptionId = $level->id;
            }
        }

        // Repository
        if (!empty($metadata['repository']['name'])) {
            $repo = $this->findOrCreateRepository($metadata['repository']['name']);
            if ($repo) {
                $object->repositoryId = $repo->id;
            }
        }

        // Dates
        if (!empty($metadata['dates'])) {
            foreach ($metadata['dates'] as $dateInfo) {
                if (!empty($dateInfo['display']) || !empty($dateInfo['start'])) {
                    $event = new \QubitEvent();
                    $event->informationObjectId = $object->id;
                    $event->typeId = $this->getEventTypeId($dateInfo['type'] ?? 'creation');

                    if (!empty($dateInfo['display'])) {
                        $event->date = $dateInfo['display'];
                    }
                    if (!empty($dateInfo['start'])) {
                        $event->startDate = $dateInfo['start'];
                    }
                    if (!empty($dateInfo['end'])) {
                        $event->endDate = $dateInfo['end'];
                    }

                    $object->eventsRelatedByobjectId[] = $event;
                }
            }
        }

        // Languages
        if (!empty($metadata['languages'])) {
            $object->language = $metadata['languages'];
        }
    }

    /**
     * Map Dublin Core metadata to object
     */
    protected function mapDublinCoreToObject(array $metadata, \QubitInformationObject $object): void
    {
        // Title
        if (!empty($metadata['title'])) {
            $object->title = is_array($metadata['title']) ? $metadata['title'][0] : $metadata['title'];
        }

        // Description
        if (!empty($metadata['description'])) {
            $description = is_array($metadata['description']) ? implode("\n\n", $metadata['description']) : $metadata['description'];
            $object->scopeAndContent = $description;
        }

        // Identifier
        if (!empty($metadata['identifier'])) {
            // Use the first non-URL identifier as reference code
            foreach ((array)$metadata['identifier'] as $identifier) {
                if (strpos($identifier, 'http') !== 0) {
                    $object->identifier = $identifier;
                    break;
                }
            }
        }

        // Creator
        if (!empty($metadata['creator'])) {
            foreach ((array)$metadata['creator'] as $creatorName) {
                $creator = $this->findOrCreateActor($creatorName);
                if ($creator) {
                    $event = new \QubitEvent();
                    $event->informationObjectId = $object->id;
                    $event->typeId = \QubitTerm::CREATION_ID;
                    $event->actorId = $creator->id;
                    $object->eventsRelatedByobjectId[] = $event;
                }
            }
        }

        // Date
        if (!empty($metadata['date'])) {
            $event = new \QubitEvent();
            $event->informationObjectId = $object->id;
            $event->typeId = \QubitTerm::CREATION_ID;
            $event->date = is_array($metadata['date']) ? $metadata['date'][0] : $metadata['date'];
            $object->eventsRelatedByobjectId[] = $event;
        }

        // Subject
        if (!empty($metadata['subject'])) {
            foreach ((array)$metadata['subject'] as $subjectTerm) {
                $term = $this->findOrCreateTerm($subjectTerm, \QubitTaxonomy::SUBJECT_ID);
                if ($term) {
                    $relation = new \QubitObjectTermRelation();
                    $relation->termId = $term->id;
                    $object->objectTermRelationsRelatedByobjectId[] = $relation;
                }
            }
        }

        // Language
        if (!empty($metadata['language'])) {
            $object->language = (array)$metadata['language'];
        }

        // Rights / Access conditions
        if (!empty($metadata['rights'])) {
            $rights = is_array($metadata['rights']) ? implode("\n", $metadata['rights']) : $metadata['rights'];
            $object->accessConditions = $rights;
        }

        // Format / Extent
        if (!empty($metadata['format'])) {
            $format = is_array($metadata['format']) ? implode("; ", $metadata['format']) : $metadata['format'];
            $object->extentAndMedium = $format;
        }

        // Coverage / Place
        if (!empty($metadata['coverage'])) {
            foreach ((array)$metadata['coverage'] as $placeName) {
                $term = $this->findOrCreateTerm($placeName, \QubitTaxonomy::PLACE_ID);
                if ($term) {
                    $relation = new \QubitObjectTermRelation();
                    $relation->termId = $term->id;
                    $object->objectTermRelationsRelatedByobjectId[] = $relation;
                }
            }
        }
    }

    /**
     * Detect best available metadata format from peer
     */
    protected function detectBestFormat(string $baseUrl): string
    {
        $formats = $this->client->listMetadataFormats($baseUrl);

        foreach ($this->preferredFormats as $preferred) {
            foreach ($formats as $format) {
                if ($format['metadataPrefix'] === $preferred) {
                    return $preferred;
                }
            }
        }

        // Default to oai_dc
        return 'oai_dc';
    }

    /**
     * Find or create a term in a taxonomy
     */
    protected function findOrCreateTerm(string $name, int $taxonomyId): ?\QubitTerm
    {
        if (empty($name)) {
            return null;
        }

        // Try to find existing term
        $criteria = new \Criteria();
        $criteria->addJoin(\QubitTerm::ID, \QubitTermI18n::ID);
        $criteria->add(\QubitTermI18n::NAME, $name);
        $criteria->add(\QubitTerm::TAXONOMY_ID, $taxonomyId);

        $term = \QubitTerm::getOne($criteria);

        if ($term) {
            return $term;
        }

        // Create new term
        $term = new \QubitTerm();
        $term->taxonomyId = $taxonomyId;
        $term->name = $name;
        $term->save();

        return $term;
    }

    /**
     * Find or create an actor
     */
    protected function findOrCreateActor(string $name): ?\QubitActor
    {
        if (empty($name)) {
            return null;
        }

        // Try to find existing actor
        $criteria = new \Criteria();
        $criteria->addJoin(\QubitActor::ID, \QubitActorI18n::ID);
        $criteria->add(\QubitActorI18n::AUTHORIZED_FORM_OF_NAME, $name);

        $actor = \QubitActor::getOne($criteria);

        if ($actor) {
            return $actor;
        }

        // Create new actor
        $actor = new \QubitActor();
        $actor->authorizedFormOfName = $name;
        $actor->save();

        return $actor;
    }

    /**
     * Find or create a repository
     */
    protected function findOrCreateRepository(string $name): ?\QubitRepository
    {
        if (empty($name)) {
            return null;
        }

        // Try to find existing repository
        $criteria = new \Criteria();
        $criteria->addJoin(\QubitRepository::ID, \QubitActorI18n::ID);
        $criteria->add(\QubitActorI18n::AUTHORIZED_FORM_OF_NAME, $name);

        $repo = \QubitRepository::getOne($criteria);

        if ($repo) {
            return $repo;
        }

        // Create new repository
        $repo = new \QubitRepository();
        $repo->authorizedFormOfName = $name;
        $repo->save();

        return $repo;
    }

    /**
     * Get event type ID from type string
     */
    protected function getEventTypeId(string $type): int
    {
        $typeMap = [
            'creation' => \QubitTerm::CREATION_ID,
            'accumulation' => \QubitTerm::ACCUMULATION_ID,
            'publication' => \QubitTerm::PUBLICATION_ID,
        ];

        return $typeMap[strtolower($type)] ?? \QubitTerm::CREATION_ID;
    }

    /**
     * Get harvest client
     */
    public function getClient(): HarvestClient
    {
        return $this->client;
    }

    /**
     * Get provenance service
     */
    public function getProvenance(): FederationProvenance
    {
        return $this->provenance;
    }
}

/**
 * Result of a harvest operation
 */
class HarvestResult
{
    public function __construct(
        public readonly int $peerId,
        public readonly string $peerName,
        public readonly string $metadataPrefix,
        public readonly ?string $from,
        public readonly ?string $until,
        public readonly ?string $set,
        public readonly array $stats,
        public readonly float $duration
    ) {}

    /**
     * Check if harvest was successful (no critical errors)
     */
    public function isSuccessful(): bool
    {
        return $this->stats['errors'] === 0 || $this->stats['created'] > 0 || $this->stats['updated'] > 0;
    }

    /**
     * Get summary string
     */
    public function getSummary(): string
    {
        return sprintf(
            'Harvested %d records from %s: %d created, %d updated, %d deleted, %d skipped, %d errors (%.2fs)',
            $this->stats['total'],
            $this->peerName,
            $this->stats['created'],
            $this->stats['updated'],
            $this->stats['deleted'],
            $this->stats['skipped'],
            $this->stats['errors'],
            $this->duration
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'peerId' => $this->peerId,
            'peerName' => $this->peerName,
            'metadataPrefix' => $this->metadataPrefix,
            'from' => $this->from,
            'until' => $this->until,
            'set' => $this->set,
            'stats' => $this->stats,
            'duration' => $this->duration,
            'successful' => $this->isSuccessful(),
            'summary' => $this->getSummary(),
        ];
    }
}
