<?php

namespace AhgFederation;

/**
 * Federation Provenance Service
 *
 * Tracks the source and harvest history of federated records.
 * Uses AtoM's property table with 'federation' scope for source metadata
 * and the federation_harvest_log table for harvest history.
 */
class FederationProvenance
{
    public const SCOPE = 'federation';
    public const PROP_SOURCE_PEER_ID = 'source_peer_id';
    public const PROP_SOURCE_OAI_IDENTIFIER = 'source_oai_identifier';
    public const PROP_HARVEST_DATE = 'harvest_date';
    public const PROP_METADATA_FORMAT = 'metadata_format';

    /**
     * Record a harvest action for a record
     */
    public function recordHarvest(
        int $objectId,
        int $peerId,
        string $sourceIdentifier,
        string $metadataFormat,
        string $action
    ): void {
        \AhgCore\Core\AhgDb::init();

        // Store/update properties on the information object
        $this->setProperty($objectId, self::PROP_SOURCE_PEER_ID, (string)$peerId);
        $this->setProperty($objectId, self::PROP_SOURCE_OAI_IDENTIFIER, $sourceIdentifier);
        $this->setProperty($objectId, self::PROP_HARVEST_DATE, date('Y-m-d H:i:s'));
        $this->setProperty($objectId, self::PROP_METADATA_FORMAT, $metadataFormat);

        // Log the harvest action
        \Illuminate\Database\Capsule\Manager::table('federation_harvest_log')->insert([
            'peer_id' => $peerId,
            'information_object_id' => $objectId,
            'source_oai_identifier' => $sourceIdentifier,
            'harvest_date' => date('Y-m-d H:i:s'),
            'metadata_format' => $metadataFormat,
            'action' => $action,
        ]);
    }

    /**
     * Find an information object by its source peer and OAI identifier
     */
    public function findBySourceIdentifier(int $peerId, string $sourceIdentifier): ?\QubitInformationObject
    {
        \AhgCore\Core\AhgDb::init();

        // Find property with matching source identifier
        $property = \Illuminate\Database\Capsule\Manager::table('property')
            ->where('scope', self::SCOPE)
            ->where('name', self::PROP_SOURCE_OAI_IDENTIFIER)
            ->where('value', $sourceIdentifier)
            ->first();

        if (!$property) {
            return null;
        }

        // Verify peer ID matches
        $peerProperty = \Illuminate\Database\Capsule\Manager::table('property')
            ->where('object_id', $property->object_id)
            ->where('scope', self::SCOPE)
            ->where('name', self::PROP_SOURCE_PEER_ID)
            ->first();

        if (!$peerProperty || (int)$peerProperty->value !== $peerId) {
            return null;
        }

        return \QubitInformationObject::getById($property->object_id);
    }

    /**
     * Get provenance information for a record
     */
    public function getProvenance(int $objectId): ?array
    {
        \AhgCore\Core\AhgDb::init();

        $peerId = $this->getProperty($objectId, self::PROP_SOURCE_PEER_ID);
        if (!$peerId) {
            return null;
        }

        // Get peer info
        $peer = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->where('id', (int)$peerId)
            ->first();

        return [
            'sourcePeerId' => (int)$peerId,
            'sourcePeerName' => $peer ? $peer->name : 'Unknown',
            'sourcePeerUrl' => $peer ? $peer->base_url : null,
            'sourceOaiIdentifier' => $this->getProperty($objectId, self::PROP_SOURCE_OAI_IDENTIFIER),
            'harvestDate' => $this->getProperty($objectId, self::PROP_HARVEST_DATE),
            'metadataFormat' => $this->getProperty($objectId, self::PROP_METADATA_FORMAT),
        ];
    }

    /**
     * Get harvest history for a record
     */
    public function getHarvestHistory(int $objectId): array
    {
        \AhgCore\Core\AhgDb::init();

        $logs = \Illuminate\Database\Capsule\Manager::table('federation_harvest_log as l')
            ->leftJoin('federation_peer as p', 'l.peer_id', '=', 'p.id')
            ->where('l.information_object_id', $objectId)
            ->select([
                'l.*',
                'p.name as peer_name',
                'p.base_url as peer_url',
            ])
            ->orderBy('l.harvest_date', 'desc')
            ->get();

        return $logs->toArray();
    }

    /**
     * Check if a record was harvested from federation
     */
    public function isFederatedRecord(int $objectId): bool
    {
        return $this->getProperty($objectId, self::PROP_SOURCE_PEER_ID) !== null;
    }

    /**
     * Get all federated records from a specific peer
     */
    public function getRecordsFromPeer(int $peerId, int $limit = 100, int $offset = 0): array
    {
        \AhgCore\Core\AhgDb::init();

        $objectIds = \Illuminate\Database\Capsule\Manager::table('property')
            ->where('scope', self::SCOPE)
            ->where('name', self::PROP_SOURCE_PEER_ID)
            ->where('value', (string)$peerId)
            ->limit($limit)
            ->offset($offset)
            ->pluck('object_id');

        $records = [];
        foreach ($objectIds as $objectId) {
            $object = \QubitInformationObject::getById($objectId);
            if ($object) {
                $records[] = [
                    'object' => $object,
                    'provenance' => $this->getProvenance($objectId),
                ];
            }
        }

        return $records;
    }

    /**
     * Count records from a specific peer
     */
    public function countRecordsFromPeer(int $peerId): int
    {
        \AhgCore\Core\AhgDb::init();

        return \Illuminate\Database\Capsule\Manager::table('property')
            ->where('scope', self::SCOPE)
            ->where('name', self::PROP_SOURCE_PEER_ID)
            ->where('value', (string)$peerId)
            ->count();
    }

    /**
     * Remove federation provenance from a record
     */
    public function removeProvenance(int $objectId): void
    {
        \AhgCore\Core\AhgDb::init();

        \Illuminate\Database\Capsule\Manager::table('property')
            ->where('object_id', $objectId)
            ->where('scope', self::SCOPE)
            ->delete();
    }

    /**
     * Get federation statistics
     */
    public function getStatistics(): array
    {
        \AhgCore\Core\AhgDb::init();

        // Total federated records
        $totalRecords = \Illuminate\Database\Capsule\Manager::table('property')
            ->where('scope', self::SCOPE)
            ->where('name', self::PROP_SOURCE_PEER_ID)
            ->count();

        // Records by peer
        $recordsByPeer = \Illuminate\Database\Capsule\Manager::table('property as p')
            ->leftJoin('federation_peer as fp', 'p.value', '=', \Illuminate\Database\Capsule\Manager::raw('CAST(fp.id AS CHAR)'))
            ->where('p.scope', self::SCOPE)
            ->where('p.name', self::PROP_SOURCE_PEER_ID)
            ->select([
                'p.value as peer_id',
                'fp.name as peer_name',
                \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as record_count'),
            ])
            ->groupBy('p.value', 'fp.name')
            ->get()
            ->toArray();

        // Recent harvests
        $recentHarvests = \Illuminate\Database\Capsule\Manager::table('federation_harvest_log as l')
            ->leftJoin('federation_peer as p', 'l.peer_id', '=', 'p.id')
            ->select([
                'l.peer_id',
                'p.name as peer_name',
                'l.action',
                \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as count'),
                \Illuminate\Database\Capsule\Manager::raw('MAX(l.harvest_date) as last_harvest'),
            ])
            ->where('l.harvest_date', '>=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->groupBy('l.peer_id', 'p.name', 'l.action')
            ->get()
            ->toArray();

        return [
            'totalFederatedRecords' => $totalRecords,
            'recordsByPeer' => $recordsByPeer,
            'recentHarvests' => $recentHarvests,
        ];
    }

    /**
     * Set a property on an information object
     */
    protected function setProperty(int $objectId, string $name, string $value): void
    {
        // Check if property exists
        $existing = \Illuminate\Database\Capsule\Manager::table('property')
            ->where('object_id', $objectId)
            ->where('scope', self::SCOPE)
            ->where('name', $name)
            ->first();

        if ($existing) {
            \Illuminate\Database\Capsule\Manager::table('property')
                ->where('id', $existing->id)
                ->update(['value' => $value]);
        } else {
            \Illuminate\Database\Capsule\Manager::table('property')->insert([
                'object_id' => $objectId,
                'scope' => self::SCOPE,
                'name' => $name,
                'value' => $value,
            ]);
        }
    }

    /**
     * Get a property from an information object
     */
    protected function getProperty(int $objectId, string $name): ?string
    {
        $property = \Illuminate\Database\Capsule\Manager::table('property')
            ->where('object_id', $objectId)
            ->where('scope', self::SCOPE)
            ->where('name', $name)
            ->first();

        return $property ? $property->value : null;
    }
}
