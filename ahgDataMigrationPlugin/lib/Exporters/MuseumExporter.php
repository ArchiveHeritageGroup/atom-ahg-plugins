<?php

namespace ahgDataMigrationPlugin\Exporters;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Museum (Spectrum 5.0) CSV exporter for AtoM import.
 */
class MuseumExporter extends BaseExporter
{
    public function getSectorCode(): string
    {
        return 'museum';
    }

    /**
     * Override to add museum-specific metadata.
     */
    protected function loadRecordFromDatabase(int $id): ?array
    {
        $record = parent::loadRecordFromDatabase($id);

        if (null === $record) {
            return null;
        }

        // Map archives fields to museum fields
        $record['objectNumber'] = $record['identifier'] ?? null;
        $record['objectName'] = $record['title'] ?? null;
        $record['briefDescription'] = $record['scope_and_content'] ?? null;
        $record['objectProductionDate'] = $record['dateRange'] ?? null;
        $record['objectProductionPerson'] = $record['creators'] ?? null;
        $record['objectHistoryNote'] = $record['archival_history'] ?? null;
        $record['materials'] = $record['extent_and_medium'] ?? null;
        $record['condition'] = $record['physical_characteristics'] ?? null;
        $record['comments'] = $record['notes'] ?? null;

        // Load museum-specific metadata if table exists
        $museumMeta = $this->loadMuseumMetadata($id);
        if ($museumMeta) {
            $record = array_merge($record, $museumMeta);
        }

        // Load physical object properties
        $properties = $this->loadPhysicalObjectProperties($id);
        if ($properties) {
            $record = array_merge($record, $properties);
        }

        return $record;
    }

    /**
     * Load museum-specific metadata from custom table.
     */
    protected function loadMuseumMetadata(int $id): ?array
    {
        // Check if museum_metadata table exists
        try {
            $meta = DB::table('museum_metadata')
                ->where('information_object_id', $id)
                ->first();

            if (!$meta) {
                return null;
            }

            return [
                'objectName' => $meta->object_name ?? null,
                'numberOfObjects' => $meta->number_of_objects ?? null,
                'technique' => $meta->technique ?? null,
                'dimensions' => $meta->dimensions ?? null,
                'inscriptions' => $meta->inscription ?? null,
                'acquisitionMethod' => $meta->acquisition_method ?? null,
                'acquisitionDate' => $meta->acquisition_date ?? null,
                'acquisitionSource' => $meta->acquisition_source ?? null,
                'currentLocation' => $meta->current_location ?? null,
                'normalLocation' => $meta->normal_location ?? null,
                'conditionNote' => $meta->condition_note ?? null,
            ];
        } catch (\Exception $e) {
            // Table doesn't exist, return null
            return null;
        }
    }

    /**
     * Load physical object properties.
     */
    protected function loadPhysicalObjectProperties(int $id): ?array
    {
        // Check for physical object information stored as properties
        $props = DB::table('property as p')
            ->join('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id')
                    ->where('pi.culture', '=', $this->culture);
            })
            ->where('p.object_id', $id)
            ->select('p.name', 'pi.value')
            ->get();

        if ($props->isEmpty()) {
            return null;
        }

        $result = [];
        foreach ($props as $prop) {
            // Map property names to museum columns
            $name = $prop->name;
            if ('dimensions' === $name) {
                $result['dimensions'] = $prop->value;
            } elseif ('materials' === $name || 'material' === $name) {
                $result['materials'] = $prop->value;
            } elseif ('technique' === $name) {
                $result['technique'] = $prop->value;
            }
        }

        return !empty($result) ? $result : null;
    }

    public function getColumns(): array
    {
        return [
            'legacyId',
            'parentId',
            'objectNumber',
            'otherNumber',
            'objectName',
            'title',
            'numberOfObjects',
            'briefDescription',
            'comments',
            'distinguishingFeatures',
            'objectProductionPerson',
            'objectProductionPersonRole',
            'objectProductionOrganisation',
            'objectProductionOrganisationRole',
            'objectProductionDate',
            'objectProductionPlace',
            'objectProductionNote',
            'technique',
            'materials',
            'dimensions',
            'dimensionType',
            'dimensionValue',
            'dimensionUnit',
            'inscriptions',
            'objectHistoryNote',
            'associatedPerson',
            'associatedOrganisation',
            'associatedDate',
            'associatedPlace',
            'associatedEvent',
            'ownershipHistory',
            'acquisitionMethod',
            'acquisitionDate',
            'acquisitionSource',
            'acquisitionReason',
            'currentLocation',
            'normalLocation',
            'locationDate',
            'conditionDate',
            'condition',
            'conditionNote',
            'subjectAccessPoints',
            'placeAccessPoints',
            'nameAccessPoints',
            'digitalObjectPath',
            'digitalObjectURI',
        ];
    }

    public function mapRecord(array $record, bool $includeCustom = false): array
    {
        $mapping = [
            'legacy_id' => 'legacyId',
            'legacyId' => 'legacyId',
            'parent_id' => 'parentId',
            'parentId' => 'parentId',
            'object_number' => 'objectNumber',
            'objectNumber' => 'objectNumber',
            'accession_number' => 'objectNumber',
            'identifier' => 'objectNumber',
            'other_number' => 'otherNumber',
            'otherNumber' => 'otherNumber',
            'object_name' => 'objectName',
            'objectName' => 'objectName',
            'title' => 'title',
            'name' => 'title',
            'number_of_objects' => 'numberOfObjects',
            'numberOfObjects' => 'numberOfObjects',
            'quantity' => 'numberOfObjects',
            'brief_description' => 'briefDescription',
            'briefDescription' => 'briefDescription',
            'description' => 'briefDescription',
            'scope_and_content' => 'briefDescription',
            'comments' => 'comments',
            'notes' => 'comments',
            'distinguishing_features' => 'distinguishingFeatures',
            'distinguishingFeatures' => 'distinguishingFeatures',
            'object_production_person' => 'objectProductionPerson',
            'objectProductionPerson' => 'objectProductionPerson',
            'maker' => 'objectProductionPerson',
            'creator' => 'objectProductionPerson',
            'artist' => 'objectProductionPerson',
            'object_production_person_role' => 'objectProductionPersonRole',
            'objectProductionPersonRole' => 'objectProductionPersonRole',
            'maker_role' => 'objectProductionPersonRole',
            'object_production_organisation' => 'objectProductionOrganisation',
            'objectProductionOrganisation' => 'objectProductionOrganisation',
            'manufacturer' => 'objectProductionOrganisation',
            'object_production_organisation_role' => 'objectProductionOrganisationRole',
            'objectProductionOrganisationRole' => 'objectProductionOrganisationRole',
            'object_production_date' => 'objectProductionDate',
            'objectProductionDate' => 'objectProductionDate',
            'date_made' => 'objectProductionDate',
            'production_date' => 'objectProductionDate',
            'date' => 'objectProductionDate',
            'object_production_place' => 'objectProductionPlace',
            'objectProductionPlace' => 'objectProductionPlace',
            'place_made' => 'objectProductionPlace',
            'production_place' => 'objectProductionPlace',
            'object_production_note' => 'objectProductionNote',
            'objectProductionNote' => 'objectProductionNote',
            'technique' => 'technique',
            'materials' => 'materials',
            'material' => 'materials',
            'medium' => 'materials',
            'dimensions' => 'dimensions',
            'measurement' => 'dimensions',
            'dimension_type' => 'dimensionType',
            'dimensionType' => 'dimensionType',
            'dimension_value' => 'dimensionValue',
            'dimensionValue' => 'dimensionValue',
            'dimension_unit' => 'dimensionUnit',
            'dimensionUnit' => 'dimensionUnit',
            'inscriptions' => 'inscriptions',
            'inscription' => 'inscriptions',
            'marks' => 'inscriptions',
            'object_history_note' => 'objectHistoryNote',
            'objectHistoryNote' => 'objectHistoryNote',
            'history' => 'objectHistoryNote',
            'provenance' => 'objectHistoryNote',
            'associated_person' => 'associatedPerson',
            'associatedPerson' => 'associatedPerson',
            'associated_organisation' => 'associatedOrganisation',
            'associatedOrganisation' => 'associatedOrganisation',
            'associated_date' => 'associatedDate',
            'associatedDate' => 'associatedDate',
            'associated_place' => 'associatedPlace',
            'associatedPlace' => 'associatedPlace',
            'associated_event' => 'associatedEvent',
            'associatedEvent' => 'associatedEvent',
            'ownership_history' => 'ownershipHistory',
            'ownershipHistory' => 'ownershipHistory',
            'acquisition_method' => 'acquisitionMethod',
            'acquisitionMethod' => 'acquisitionMethod',
            'acquisition_date' => 'acquisitionDate',
            'acquisitionDate' => 'acquisitionDate',
            'acquisition_source' => 'acquisitionSource',
            'acquisitionSource' => 'acquisitionSource',
            'donor' => 'acquisitionSource',
            'acquisition_reason' => 'acquisitionReason',
            'acquisitionReason' => 'acquisitionReason',
            'current_location' => 'currentLocation',
            'currentLocation' => 'currentLocation',
            'location' => 'currentLocation',
            'normal_location' => 'normalLocation',
            'normalLocation' => 'normalLocation',
            'location_date' => 'locationDate',
            'locationDate' => 'locationDate',
            'condition_date' => 'conditionDate',
            'conditionDate' => 'conditionDate',
            'condition' => 'condition',
            'condition_note' => 'conditionNote',
            'conditionNote' => 'conditionNote',
            'subjects' => 'subjectAccessPoints',
            'subjectAccessPoints' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'placeAccessPoints' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'nameAccessPoints' => 'nameAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digitalObjectPath' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'digitalObjectURI' => 'digitalObjectURI',
            'image' => 'digitalObjectPath',
        ];

        $result = [];
        $columns = $this->getColumns();

        foreach ($record as $key => $value) {
            $targetKey = $mapping[$key] ?? $key;
            if (in_array($targetKey, $columns) || $includeCustom) {
                $result[$targetKey] = $value;
            }
        }

        return $result;
    }
}
