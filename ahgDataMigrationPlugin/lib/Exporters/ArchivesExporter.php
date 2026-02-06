<?php

namespace ahgDataMigrationPlugin\Exporters;

/**
 * Archives (ISAD-G) CSV exporter for AtoM import.
 */
class ArchivesExporter extends BaseExporter
{
    public function getSectorCode(): string
    {
        return 'archives';
    }

    public function getColumns(): array
    {
        return [
            'legacyId',
            'parentId',
            'identifier',
            'title',
            'levelOfDescription',
            'repository',
            'extentAndMedium',
            'dateRange',
            'dateStart',
            'dateEnd',
            'creators',
            'adminBioHistory',
            'archivalHistory',
            'acquisition',
            'scopeAndContent',
            'appraisal',
            'accruals',
            'arrangement',
            'accessConditions',
            'reproductionConditions',
            'language',
            'script',
            'physicalCharacteristics',
            'findingAids',
            'locationOfOriginals',
            'locationOfCopies',
            'relatedUnitsOfDescription',
            'publicationNote',
            'notes',
            'archivistNote',
            'rules',
            'revisionHistory',
            'dateOfDescription',
            'subjectAccessPoints',
            'placeAccessPoints',
            'nameAccessPoints',
            'genreAccessPoints',
            'digitalObjectPath',
            'digitalObjectURI',
        ];
    }

    public function mapRecord(array $record, bool $includeCustom = false): array
    {
        // Map common field names to AtoM column names
        $mapping = [
            'legacy_id' => 'legacyId',
            'legacyId' => 'legacyId',
            'parent_id' => 'parentId',
            'parentId' => 'parentId',
            'identifier' => 'identifier',
            'reference_code' => 'identifier',
            'title' => 'title',
            'level_of_description' => 'levelOfDescription',
            'levelOfDescription' => 'levelOfDescription',
            'level' => 'levelOfDescription',
            'repository' => 'repository',
            'extent_and_medium' => 'extentAndMedium',
            'extentAndMedium' => 'extentAndMedium',
            'extent' => 'extentAndMedium',
            'date_range' => 'dateRange',
            'dateRange' => 'dateRange',
            'dates' => 'dateRange',
            'date_start' => 'dateStart',
            'dateStart' => 'dateStart',
            'start_date' => 'dateStart',
            'date_end' => 'dateEnd',
            'dateEnd' => 'dateEnd',
            'end_date' => 'dateEnd',
            'creators' => 'creators',
            'creator' => 'creators',
            'admin_bio_history' => 'adminBioHistory',
            'adminBioHistory' => 'adminBioHistory',
            'biography' => 'adminBioHistory',
            'archival_history' => 'archivalHistory',
            'archivalHistory' => 'archivalHistory',
            'custodial_history' => 'archivalHistory',
            'acquisition' => 'acquisition',
            'immediate_source' => 'acquisition',
            'scope_and_content' => 'scopeAndContent',
            'scopeAndContent' => 'scopeAndContent',
            'description' => 'scopeAndContent',
            'appraisal' => 'appraisal',
            'accruals' => 'accruals',
            'arrangement' => 'arrangement',
            'access_conditions' => 'accessConditions',
            'accessConditions' => 'accessConditions',
            'reproduction_conditions' => 'reproductionConditions',
            'reproductionConditions' => 'reproductionConditions',
            'language' => 'language',
            'script' => 'script',
            'physical_characteristics' => 'physicalCharacteristics',
            'physicalCharacteristics' => 'physicalCharacteristics',
            'condition' => 'physicalCharacteristics',
            'finding_aids' => 'findingAids',
            'findingAids' => 'findingAids',
            'location_of_originals' => 'locationOfOriginals',
            'locationOfOriginals' => 'locationOfOriginals',
            'location_of_copies' => 'locationOfCopies',
            'locationOfCopies' => 'locationOfCopies',
            'related_units' => 'relatedUnitsOfDescription',
            'relatedUnitsOfDescription' => 'relatedUnitsOfDescription',
            'publication_note' => 'publicationNote',
            'publicationNote' => 'publicationNote',
            'notes' => 'notes',
            'general_note' => 'notes',
            'archivist_note' => 'archivistNote',
            'archivistNote' => 'archivistNote',
            'rules' => 'rules',
            'revision_history' => 'revisionHistory',
            'revisionHistory' => 'revisionHistory',
            'date_of_description' => 'dateOfDescription',
            'dateOfDescription' => 'dateOfDescription',
            'subjects' => 'subjectAccessPoints',
            'subjectAccessPoints' => 'subjectAccessPoints',
            'subject_access_points' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'placeAccessPoints' => 'placeAccessPoints',
            'place_access_points' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'nameAccessPoints' => 'nameAccessPoints',
            'name_access_points' => 'nameAccessPoints',
            'genres' => 'genreAccessPoints',
            'genreAccessPoints' => 'genreAccessPoints',
            'genre_access_points' => 'genreAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digitalObjectPath' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'digitalObjectURI' => 'digitalObjectURI',
        ];

        $result = [];
        $columns = $this->getColumns();

        foreach ($record as $key => $value) {
            $targetKey = $mapping[$key] ?? $key;

            // Include if it's a standard column OR if includeCustom is true
            if (in_array($targetKey, $columns) || $includeCustom) {
                $result[$targetKey] = $value;
            }
        }

        return $result;
    }
}
