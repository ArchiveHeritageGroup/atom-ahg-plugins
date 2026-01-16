<?php

namespace ahgDataMigrationPlugin\Exporters;

/**
 * Gallery (CCO/VRA) CSV exporter for AtoM import.
 */
class GalleryExporter extends BaseExporter
{
    public function getSectorCode(): string
    {
        return 'gallery';
    }

    public function getColumns(): array
    {
        return [
            'legacyId',
            'parentId',
            'objectNumber',
            'workType',
            'title',
            'titleType',
            'creator',
            'creatorRole',
            'creationDate',
            'creationDateEarliest',
            'creationDateLatest',
            'creationPlace',
            'stylePeriod',
            'culturalContext',
            'materials',
            'technique',
            'measurements',
            'measurementType',
            'measurementUnit',
            'measurementValue',
            'subject',
            'inscriptions',
            'stateEdition',
            'provenance',
            'exhibitionHistory',
            'bibliographicReferences',
            'relatedWorks',
            'conditionDescription',
            'treatmentHistory',
            'creditLine',
            'rights',
            'subjectAccessPoints',
            'placeAccessPoints',
            'nameAccessPoints',
            'digitalObjectPath',
            'digitalObjectURI',
        ];
    }

    public function mapRecord(array $record): array
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
            'work_type' => 'workType',
            'workType' => 'workType',
            'object_type' => 'workType',
            'medium' => 'workType',
            'title' => 'title',
            'title_type' => 'titleType',
            'titleType' => 'titleType',
            'creator' => 'creator',
            'artist' => 'creator',
            'maker' => 'creator',
            'author' => 'creator',
            'creator_role' => 'creatorRole',
            'creatorRole' => 'creatorRole',
            'artist_role' => 'creatorRole',
            'creation_date' => 'creationDate',
            'creationDate' => 'creationDate',
            'date' => 'creationDate',
            'date_made' => 'creationDate',
            'creation_date_earliest' => 'creationDateEarliest',
            'creationDateEarliest' => 'creationDateEarliest',
            'date_start' => 'creationDateEarliest',
            'creation_date_latest' => 'creationDateLatest',
            'creationDateLatest' => 'creationDateLatest',
            'date_end' => 'creationDateLatest',
            'creation_place' => 'creationPlace',
            'creationPlace' => 'creationPlace',
            'place_made' => 'creationPlace',
            'style_period' => 'stylePeriod',
            'stylePeriod' => 'stylePeriod',
            'period' => 'stylePeriod',
            'style' => 'stylePeriod',
            'cultural_context' => 'culturalContext',
            'culturalContext' => 'culturalContext',
            'culture' => 'culturalContext',
            'materials' => 'materials',
            'material' => 'materials',
            'technique' => 'technique',
            'measurements' => 'measurements',
            'dimensions' => 'measurements',
            'measurement_type' => 'measurementType',
            'measurementType' => 'measurementType',
            'measurement_unit' => 'measurementUnit',
            'measurementUnit' => 'measurementUnit',
            'measurement_value' => 'measurementValue',
            'measurementValue' => 'measurementValue',
            'subject' => 'subject',
            'description' => 'subject',
            'inscriptions' => 'inscriptions',
            'inscription' => 'inscriptions',
            'state_edition' => 'stateEdition',
            'stateEdition' => 'stateEdition',
            'edition' => 'stateEdition',
            'provenance' => 'provenance',
            'ownership_history' => 'provenance',
            'exhibition_history' => 'exhibitionHistory',
            'exhibitionHistory' => 'exhibitionHistory',
            'exhibitions' => 'exhibitionHistory',
            'bibliographic_references' => 'bibliographicReferences',
            'bibliographicReferences' => 'bibliographicReferences',
            'bibliography' => 'bibliographicReferences',
            'related_works' => 'relatedWorks',
            'relatedWorks' => 'relatedWorks',
            'condition_description' => 'conditionDescription',
            'conditionDescription' => 'conditionDescription',
            'condition' => 'conditionDescription',
            'treatment_history' => 'treatmentHistory',
            'treatmentHistory' => 'treatmentHistory',
            'conservation' => 'treatmentHistory',
            'credit_line' => 'creditLine',
            'creditLine' => 'creditLine',
            'credit' => 'creditLine',
            'rights' => 'rights',
            'copyright' => 'rights',
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
        foreach ($record as $key => $value) {
            $targetKey = $mapping[$key] ?? $key;
            if (in_array($targetKey, $this->getColumns())) {
                $result[$targetKey] = $value;
            }
        }

        return $result;
    }
}
