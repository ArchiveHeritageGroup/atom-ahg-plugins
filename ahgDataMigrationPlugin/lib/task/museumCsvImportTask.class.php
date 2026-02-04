<?php

/**
 * Museum (Spectrum 5.0) CSV import task.
 *
 * Imports CSV files following Spectrum standard for museum collections.
 */
class museumCsvImportTask extends sectorImportTask
{
    protected $name = 'museum-csv-import';
    protected $briefDescription = 'Import museum CSV data with Spectrum validation';

    protected function getSectorCode(): string
    {
        return 'museum';
    }

    protected function getRequiredColumns(): array
    {
        return ['objectNumber', 'objectName'];
    }

    protected function getColumnMap(): array
    {
        return [
            'legacy_id' => 'legacyId',
            'parent_id' => 'parentId',
            'object_number' => 'objectNumber',
            'accession_number' => 'objectNumber',
            'object_name' => 'objectName',
            'name' => 'title',
            'number_of_objects' => 'numberOfObjects',
            'quantity' => 'numberOfObjects',
            'brief_description' => 'briefDescription',
            'description' => 'briefDescription',
            'scope_and_content' => 'briefDescription',
            'notes' => 'comments',
            'distinguishing_features' => 'distinguishingFeatures',
            'object_production_person' => 'objectProductionPerson',
            'maker' => 'objectProductionPerson',
            'creator' => 'objectProductionPerson',
            'artist' => 'objectProductionPerson',
            'maker_role' => 'objectProductionPersonRole',
            'object_production_organisation' => 'objectProductionOrganisation',
            'manufacturer' => 'objectProductionOrganisation',
            'object_production_date' => 'objectProductionDate',
            'date_made' => 'objectProductionDate',
            'production_date' => 'objectProductionDate',
            'date' => 'objectProductionDate',
            'object_production_place' => 'objectProductionPlace',
            'place_made' => 'objectProductionPlace',
            'production_place' => 'objectProductionPlace',
            'material' => 'materials',
            'medium' => 'materials',
            'measurement' => 'dimensions',
            'inscription' => 'inscriptions',
            'marks' => 'inscriptions',
            'history' => 'objectHistoryNote',
            'provenance' => 'objectHistoryNote',
            'ownership_history' => 'ownershipHistory',
            'acquisition_method' => 'acquisitionMethod',
            'acquisition_date' => 'acquisitionDate',
            'acquisition_source' => 'acquisitionSource',
            'donor' => 'acquisitionSource',
            'acquisition_reason' => 'acquisitionReason',
            'current_location' => 'currentLocation',
            'location' => 'currentLocation',
            'normal_location' => 'normalLocation',
            'condition_note' => 'conditionNote',
            'subjects' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'image' => 'digitalObjectPath',
        ];
    }

    /**
     * Override to map museum fields to AtoM fields.
     */
    protected function setI18nFields(\QubitInformationObject $io, array $data): void
    {
        // Map museum fields to AtoM equivalents
        $io->title = $data['title'] ?? $data['objectName'] ?? null;
        $io->identifier = $data['objectNumber'] ?? $data['identifier'] ?? null;
        $io->extentAndMedium = $this->formatExtent($data);
        $io->scopeAndContent = $data['briefDescription'] ?? $data['description'] ?? null;
        $io->archivalHistory = $data['objectHistoryNote'] ?? $data['provenance'] ?? null;
        $io->physicalCharacteristics = $data['condition'] ?? $data['conditionNote'] ?? null;
    }

    /**
     * Format extent field from museum data.
     */
    protected function formatExtent(array $data): ?string
    {
        $parts = [];

        if (!empty($data['numberOfObjects'])) {
            $parts[] = $data['numberOfObjects'].' object(s)';
        }

        if (!empty($data['materials'])) {
            $parts[] = 'Materials: '.$data['materials'];
        }

        if (!empty($data['dimensions'])) {
            $parts[] = 'Dimensions: '.$data['dimensions'];
        }

        return !empty($parts) ? implode('; ', $parts) : null;
    }

    protected function saveSectorMetadata(int $objectId, array $row): void
    {
        // Check if museum_metadata table exists and save extra fields
        try {
            $exists = Illuminate\Database\Capsule\Manager::table('museum_metadata')
                ->where('information_object_id', $objectId)
                ->exists()
            ;

            $metadata = [
                'information_object_id' => $objectId,
                'object_name' => $row['objectName'] ?? null,
                'number_of_objects' => $row['numberOfObjects'] ?? null,
                'technique' => $row['technique'] ?? null,
                'dimensions' => $row['dimensions'] ?? null,
                'inscription' => $row['inscriptions'] ?? null,
                'acquisition_method' => $row['acquisitionMethod'] ?? null,
                'acquisition_date' => $row['acquisitionDate'] ?? null,
                'acquisition_source' => $row['acquisitionSource'] ?? null,
                'current_location' => $row['currentLocation'] ?? null,
                'normal_location' => $row['normalLocation'] ?? null,
                'condition_note' => $row['conditionNote'] ?? null,
            ];

            if ($exists) {
                Illuminate\Database\Capsule\Manager::table('museum_metadata')
                    ->where('information_object_id', $objectId)
                    ->update($metadata)
                ;
            } else {
                Illuminate\Database\Capsule\Manager::table('museum_metadata')
                    ->insert($metadata)
                ;
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip sector metadata
        }
    }
}
