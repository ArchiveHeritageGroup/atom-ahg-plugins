<?php

/**
 * Archives (ISAD-G) CSV import task.
 *
 * Imports CSV files following ISAD(G) standard for archival descriptions.
 */
class archivesCsvImportTask extends sectorImportTask
{
    protected $name = 'archives-csv-import';
    protected $briefDescription = 'Import archives CSV data with ISAD-G validation';

    protected function getSectorCode(): string
    {
        return 'archive';
    }

    protected function getRequiredColumns(): array
    {
        return ['identifier', 'title'];
    }

    protected function getColumnMap(): array
    {
        return [
            'legacy_id' => 'legacyId',
            'parent_id' => 'parentId',
            'reference_code' => 'identifier',
            'level_of_description' => 'levelOfDescription',
            'level' => 'levelOfDescription',
            'extent_and_medium' => 'extentAndMedium',
            'extent' => 'extentAndMedium',
            'date_range' => 'dateRange',
            'dates' => 'dateRange',
            'date_start' => 'dateStart',
            'start_date' => 'dateStart',
            'date_end' => 'dateEnd',
            'end_date' => 'dateEnd',
            'creator' => 'creators',
            'admin_bio_history' => 'adminBioHistory',
            'biography' => 'adminBioHistory',
            'archival_history' => 'archivalHistory',
            'custodial_history' => 'archivalHistory',
            'immediate_source' => 'acquisition',
            'scope_and_content' => 'scopeAndContent',
            'description' => 'scopeAndContent',
            'access_conditions' => 'accessConditions',
            'reproduction_conditions' => 'reproductionConditions',
            'physical_characteristics' => 'physicalCharacteristics',
            'condition' => 'physicalCharacteristics',
            'finding_aids' => 'findingAids',
            'location_of_originals' => 'locationOfOriginals',
            'location_of_copies' => 'locationOfCopies',
            'related_units' => 'relatedUnitsOfDescription',
            'publication_note' => 'publicationNote',
            'general_note' => 'notes',
            'archivist_note' => 'archivistNote',
            'revision_history' => 'revisionHistory',
            'date_of_description' => 'dateOfDescription',
            'subjects' => 'subjectAccessPoints',
            'subject_access_points' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'place_access_points' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'name_access_points' => 'nameAccessPoints',
            'genres' => 'genreAccessPoints',
            'genre_access_points' => 'genreAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
        ];
    }

    protected function saveSectorMetadata(int $objectId, array $row): void
    {
        // Archives use the standard AtoM fields, no extra sector metadata table needed
        // Additional ISAD-G specific fields are stored in the information_object_i18n table
        // via the setI18nFields method in the parent class

        // If we need to store additional archive-specific data, we would do it here
        // For now, archives data maps directly to AtoM's native ISAD-G fields
    }
}
