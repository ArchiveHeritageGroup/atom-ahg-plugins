<?php

/**
 * Library (MARC/RDA) CSV import task.
 *
 * Imports CSV files following MARC/RDA standard for library materials.
 */
class libraryCsvImportTask extends sectorImportTask
{
    protected $name = 'library-csv-import';
    protected $briefDescription = 'Import library CSV data with MARC/RDA validation';

    protected function getSectorCode(): string
    {
        return 'library';
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
            'barcode' => 'identifier',
            'call_number' => 'callNumber',
            'classification' => 'callNumber',
            'title_proper' => 'titleProper',
            'main_title' => 'titleProper',
            'parallel_title' => 'parallelTitle',
            'other_title_info' => 'otherTitleInfo',
            'subtitle' => 'otherTitleInfo',
            'statement_of_responsibility' => 'statementOfResponsibility',
            'author' => 'statementOfResponsibility',
            'creator' => 'statementOfResponsibility',
            'edition_statement' => 'editionStatement',
            'edition' => 'editionStatement',
            'place_of_publication' => 'placeOfPublication',
            'publication_place' => 'placeOfPublication',
            'date_of_publication' => 'dateOfPublication',
            'publication_date' => 'dateOfPublication',
            'date' => 'dateOfPublication',
            'year' => 'dateOfPublication',
            'copyright_date' => 'copyrightDate',
            'pages' => 'extent',
            'pagination' => 'extent',
            'physical_description' => 'extent',
            'size' => 'dimensions',
            'series_title' => 'seriesTitle',
            'series' => 'seriesTitle',
            'series_number' => 'seriesNumber',
            'volume' => 'seriesNumber',
            'notes' => 'note',
            'general_note' => 'generalNote',
            'table_of_contents' => 'tableOfContents',
            'contents' => 'tableOfContents',
            'abstract' => 'summary',
            'description' => 'summary',
            'scope_and_content' => 'summary',
            'subjects' => 'subjectAccessPoints',
            'subject' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'genres' => 'genreAccessPoints',
            'genre' => 'genreAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'library' => 'repository',
            'physical_location' => 'physicalLocation',
            'shelf_location' => 'physicalLocation',
            'location' => 'physicalLocation',
        ];
    }

    /**
     * Override to map library fields to AtoM fields.
     */
    protected function setI18nFields(\QubitInformationObject $io, array $data): void
    {
        $io->title = $data['title'] ?? $data['titleProper'] ?? null;
        $io->identifier = $data['identifier'] ?? $data['callNumber'] ?? null;
        $io->extentAndMedium = $this->formatExtent($data);
        $io->scopeAndContent = $data['summary'] ?? $data['description'] ?? null;
        $io->findingAids = $data['tableOfContents'] ?? null;
    }

    /**
     * Format extent field from library data.
     */
    protected function formatExtent(array $data): ?string
    {
        $parts = [];

        if (!empty($data['extent'])) {
            $parts[] = $data['extent'];
        }

        if (!empty($data['dimensions'])) {
            $parts[] = $data['dimensions'];
        }

        return !empty($parts) ? implode('; ', $parts) : null;
    }

    /**
     * Override to create events with publication date.
     */
    protected function createEvents(int $objectId, array $data): void
    {
        // Create publication event
        $pubDate = $data['dateOfPublication'] ?? $data['date'] ?? null;
        $publisher = $data['publisher'] ?? null;
        $pubPlace = $data['placeOfPublication'] ?? null;

        if ($pubDate || $publisher) {
            $event = new \QubitEvent();
            $event->objectId = $objectId;
            $event->typeId = \QubitTerm::PUBLICATION_ID;
            $event->date = $pubDate;
            $event->culture = $this->culture;

            if ($publisher) {
                $actor = $this->findOrCreateActor($publisher);
                if ($actor) {
                    $event->actorId = $actor->id;
                }
            }

            $event->save();
        }

        // Also create author event if different from publisher
        $author = $data['statementOfResponsibility'] ?? $data['author'] ?? null;
        if ($author && $author !== $publisher) {
            parent::createEvents($objectId, ['creators' => $author, 'dateRange' => $pubDate]);
        }
    }

    protected function saveSectorMetadata(int $objectId, array $row): void
    {
        // Save library-specific metadata
        try {
            $exists = Illuminate\Database\Capsule\Manager::table('library_metadata')
                ->where('information_object_id', $objectId)
                ->exists()
            ;

            $metadata = [
                'information_object_id' => $objectId,
                'isbn' => $row['isbn'] ?? null,
                'issn' => $row['issn'] ?? null,
                'call_number' => $row['callNumber'] ?? null,
                'publisher' => $row['publisher'] ?? null,
                'place_of_publication' => $row['placeOfPublication'] ?? null,
                'edition' => $row['editionStatement'] ?? $row['edition'] ?? null,
                'series' => $row['seriesTitle'] ?? null,
                'language' => $row['language'] ?? null,
            ];

            if ($exists) {
                Illuminate\Database\Capsule\Manager::table('library_metadata')
                    ->where('information_object_id', $objectId)
                    ->update($metadata)
                ;
            } else {
                Illuminate\Database\Capsule\Manager::table('library_metadata')
                    ->insert($metadata)
                ;
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip sector metadata
        }
    }
}
