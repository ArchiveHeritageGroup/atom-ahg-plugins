<?php

namespace ahgDataMigrationPlugin\Exporters;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Gallery (CCO/VRA) CSV exporter for AtoM import.
 */
class GalleryExporter extends BaseExporter
{
    public function getSectorCode(): string
    {
        return 'gallery';
    }

    /**
     * Override to add gallery-specific metadata.
     */
    protected function loadRecordFromDatabase(int $id): ?array
    {
        $record = parent::loadRecordFromDatabase($id);

        if (null === $record) {
            return null;
        }

        // Map base fields to gallery fields
        $record['objectNumber'] = $record['identifier'] ?? null;
        $record['creationDate'] = $record['dateRange'] ?? null;
        $record['creationDateEarliest'] = $record['dateStart'] ?? null;
        $record['creationDateLatest'] = $record['dateEnd'] ?? null;
        $record['creator'] = $record['creators'] ?? null;
        $record['subject'] = $record['scope_and_content'] ?? null;
        $record['provenance'] = $record['archival_history'] ?? null;
        $record['materials'] = $record['extent_and_medium'] ?? null;
        $record['conditionDescription'] = $record['physical_characteristics'] ?? null;

        // Load gallery-specific metadata if table exists
        $galleryMeta = $this->loadGalleryMetadata($id);
        if ($galleryMeta) {
            $record = array_merge($record, $galleryMeta);
        }

        return $record;
    }

    /**
     * Load gallery-specific metadata from custom table.
     */
    protected function loadGalleryMetadata(int $id): ?array
    {
        try {
            $meta = DB::table('gallery_metadata')
                ->where('information_object_id', $id)
                ->first();

            if (!$meta) {
                return null;
            }

            return [
                'workType' => $meta->work_type ?? null,
                'stylePeriod' => $meta->style_period ?? null,
                'culturalContext' => $meta->cultural_context ?? null,
                'technique' => $meta->technique ?? null,
                'measurements' => $meta->measurements ?? null,
                'inscriptions' => $meta->inscriptions ?? null,
                'stateEdition' => $meta->edition_number ?? null,
                'exhibitionHistory' => $meta->exhibition_history ?? null,
                'creditLine' => $meta->credit_line ?? null,
                'rights' => $meta->rights ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
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
