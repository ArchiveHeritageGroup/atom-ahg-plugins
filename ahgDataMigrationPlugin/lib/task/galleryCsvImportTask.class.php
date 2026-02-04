<?php

/**
 * Gallery (CCO) CSV import task.
 *
 * Imports CSV files following CCO (Cataloging Cultural Objects) standard for artwork.
 */
class galleryCsvImportTask extends sectorImportTask
{
    protected $name = 'gallery-csv-import';
    protected $briefDescription = 'Import gallery CSV data with CCO validation';

    protected function getSectorCode(): string
    {
        return 'gallery';
    }

    protected function getRequiredColumns(): array
    {
        return ['objectNumber', 'title'];
    }

    protected function getColumnMap(): array
    {
        return [
            'legacy_id' => 'legacyId',
            'parent_id' => 'parentId',
            'object_number' => 'objectNumber',
            'accession_number' => 'objectNumber',
            'work_type' => 'workType',
            'object_type' => 'workType',
            'medium' => 'workType',
            'title_type' => 'titleType',
            'artist' => 'creator',
            'maker' => 'creator',
            'author' => 'creator',
            'creator_role' => 'creatorRole',
            'artist_role' => 'creatorRole',
            'creation_date' => 'creationDate',
            'date' => 'creationDate',
            'date_made' => 'creationDate',
            'creation_date_earliest' => 'creationDateEarliest',
            'date_start' => 'creationDateEarliest',
            'creation_date_latest' => 'creationDateLatest',
            'date_end' => 'creationDateLatest',
            'creation_place' => 'creationPlace',
            'place_made' => 'creationPlace',
            'style_period' => 'stylePeriod',
            'period' => 'stylePeriod',
            'style' => 'stylePeriod',
            'cultural_context' => 'culturalContext',
            'culture' => 'culturalContext',
            'material' => 'materials',
            'dimensions' => 'measurements',
            'measurement_type' => 'measurementType',
            'measurement_unit' => 'measurementUnit',
            'measurement_value' => 'measurementValue',
            'description' => 'subject',
            'inscription' => 'inscriptions',
            'state_edition' => 'stateEdition',
            'edition' => 'stateEdition',
            'ownership_history' => 'provenance',
            'exhibition_history' => 'exhibitionHistory',
            'exhibitions' => 'exhibitionHistory',
            'bibliographic_references' => 'bibliographicReferences',
            'bibliography' => 'bibliographicReferences',
            'related_works' => 'relatedWorks',
            'condition_description' => 'conditionDescription',
            'condition' => 'conditionDescription',
            'treatment_history' => 'treatmentHistory',
            'conservation' => 'treatmentHistory',
            'credit_line' => 'creditLine',
            'credit' => 'creditLine',
            'copyright' => 'rights',
            'subjects' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'image' => 'digitalObjectPath',
        ];
    }

    /**
     * Override to map gallery fields to AtoM fields.
     */
    protected function setI18nFields(\QubitInformationObject $io, array $data): void
    {
        $io->title = $data['title'] ?? null;
        $io->identifier = $data['objectNumber'] ?? $data['identifier'] ?? null;
        $io->extentAndMedium = $this->formatExtent($data);
        $io->scopeAndContent = $data['subject'] ?? $data['description'] ?? null;
        $io->archivalHistory = $data['provenance'] ?? null;
        $io->physicalCharacteristics = $data['conditionDescription'] ?? null;
    }

    /**
     * Format extent field from gallery data.
     */
    protected function formatExtent(array $data): ?string
    {
        $parts = [];

        if (!empty($data['workType'])) {
            $parts[] = $data['workType'];
        }

        if (!empty($data['materials'])) {
            $parts[] = $data['materials'];
        }

        if (!empty($data['technique'])) {
            $parts[] = $data['technique'];
        }

        if (!empty($data['measurements'])) {
            $parts[] = $data['measurements'];
        }

        return !empty($parts) ? implode('; ', $parts) : null;
    }

    /**
     * Override to handle artwork-specific date handling.
     */
    protected function createEvents(int $objectId, array $data): void
    {
        // Create creation event with date range
        $creator = $data['creator'] ?? $data['artist'] ?? null;
        $dateDisplay = $data['creationDate'] ?? null;
        $dateStart = $data['creationDateEarliest'] ?? null;
        $dateEnd = $data['creationDateLatest'] ?? null;
        $place = $data['creationPlace'] ?? null;

        if ($creator || $dateDisplay || $dateStart) {
            $event = new \QubitEvent();
            $event->objectId = $objectId;
            $event->typeId = \QubitTerm::CREATION_ID;
            $event->date = $dateDisplay;
            $event->startDate = $dateStart;
            $event->endDate = $dateEnd;
            $event->culture = $this->culture;

            if ($creator) {
                $actor = $this->findOrCreateActor($creator);
                if ($actor) {
                    $event->actorId = $actor->id;
                }
            }

            $event->save();

            // Set creator role if specified
            if (!empty($data['creatorRole']) && isset($actor)) {
                // Store as property or note
            }
        }
    }

    protected function saveSectorMetadata(int $objectId, array $row): void
    {
        // Save gallery-specific metadata
        try {
            $exists = Illuminate\Database\Capsule\Manager::table('gallery_metadata')
                ->where('information_object_id', $objectId)
                ->exists()
            ;

            $metadata = [
                'information_object_id' => $objectId,
                'work_type' => $row['workType'] ?? null,
                'style_period' => $row['stylePeriod'] ?? null,
                'cultural_context' => $row['culturalContext'] ?? null,
                'technique' => $row['technique'] ?? null,
                'measurements' => $row['measurements'] ?? null,
                'inscriptions' => $row['inscriptions'] ?? null,
                'edition_number' => $row['stateEdition'] ?? null,
                'exhibition_history' => $row['exhibitionHistory'] ?? null,
                'credit_line' => $row['creditLine'] ?? null,
                'rights' => $row['rights'] ?? null,
            ];

            if ($exists) {
                Illuminate\Database\Capsule\Manager::table('gallery_metadata')
                    ->where('information_object_id', $objectId)
                    ->update($metadata)
                ;
            } else {
                Illuminate\Database\Capsule\Manager::table('gallery_metadata')
                    ->insert($metadata)
                ;
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip sector metadata
        }
    }
}
