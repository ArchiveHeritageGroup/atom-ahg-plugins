<?php

namespace ahgDataMigrationPlugin\Exporters;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Library (MARC/RDA) CSV exporter for AtoM import.
 */
class LibraryExporter extends BaseExporter
{
    public function getSectorCode(): string
    {
        return 'library';
    }

    /**
     * Override to add library-specific metadata.
     */
    protected function loadRecordFromDatabase(int $id): ?array
    {
        $record = parent::loadRecordFromDatabase($id);

        if (null === $record) {
            return null;
        }

        // Map base fields to library fields
        $record['titleProper'] = $record['title'] ?? null;
        $record['extent'] = $record['extent_and_medium'] ?? null;
        $record['summary'] = $record['scope_and_content'] ?? null;
        $record['note'] = $record['notes'] ?? null;
        $record['dateOfPublication'] = $record['dateRange'] ?? null;
        $record['statementOfResponsibility'] = $record['creators'] ?? null;

        // Load library-specific metadata if table exists
        $libraryMeta = $this->loadLibraryMetadata($id);
        if ($libraryMeta) {
            $record = array_merge($record, $libraryMeta);
        }

        // Load RAD/bibliographic properties
        $radProps = $this->loadRadProperties($id);
        if ($radProps) {
            $record = array_merge($record, $radProps);
        }

        return $record;
    }

    /**
     * Load library-specific metadata from custom table.
     */
    protected function loadLibraryMetadata(int $id): ?array
    {
        try {
            $meta = DB::table('library_metadata')
                ->where('information_object_id', $id)
                ->first();

            if (!$meta) {
                return null;
            }

            return [
                'isbn' => $meta->isbn ?? null,
                'issn' => $meta->issn ?? null,
                'callNumber' => $meta->call_number ?? null,
                'publisher' => $meta->publisher ?? null,
                'placeOfPublication' => $meta->place_of_publication ?? null,
                'editionStatement' => $meta->edition ?? null,
                'seriesTitle' => $meta->series ?? null,
                'language' => $meta->language ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Load RAD-specific properties.
     */
    protected function loadRadProperties(int $id): ?array
    {
        // Check for RAD template properties
        $radInfo = DB::table('information_object as io')
            ->join('rad_information_object as rad', 'io.id', '=', 'rad.id')
            ->where('io.id', $id)
            ->first();

        if (!$radInfo) {
            return null;
        }

        return [
            'titleProper' => $radInfo->title_proper ?? null,
            'parallelTitle' => $radInfo->parallel_title ?? null,
            'otherTitleInfo' => $radInfo->other_title_info ?? null,
            'statementOfResponsibility' => $radInfo->statement_of_responsibility ?? null,
            'editionStatement' => $radInfo->edition_statement ?? null,
        ];
    }

    public function getColumns(): array
    {
        return [
            'legacyId',
            'parentId',
            'identifier',
            'callNumber',
            'isbn',
            'issn',
            'title',
            'titleProper',
            'parallelTitle',
            'otherTitleInfo',
            'statementOfResponsibility',
            'editionStatement',
            'publisher',
            'placeOfPublication',
            'dateOfPublication',
            'copyrightDate',
            'extent',
            'dimensions',
            'seriesTitle',
            'seriesNumber',
            'language',
            'note',
            'generalNote',
            'tableOfContents',
            'summary',
            'subjectAccessPoints',
            'placeAccessPoints',
            'nameAccessPoints',
            'genreAccessPoints',
            'digitalObjectPath',
            'digitalObjectURI',
            'repository',
            'physicalLocation',
        ];
    }

    public function mapRecord(array $record): array
    {
        $mapping = [
            'legacy_id' => 'legacyId',
            'legacyId' => 'legacyId',
            'parent_id' => 'parentId',
            'parentId' => 'parentId',
            'identifier' => 'identifier',
            'barcode' => 'identifier',
            'call_number' => 'callNumber',
            'callNumber' => 'callNumber',
            'classification' => 'callNumber',
            'isbn' => 'isbn',
            'issn' => 'issn',
            'title' => 'title',
            'title_proper' => 'titleProper',
            'titleProper' => 'titleProper',
            'main_title' => 'titleProper',
            'parallel_title' => 'parallelTitle',
            'parallelTitle' => 'parallelTitle',
            'other_title_info' => 'otherTitleInfo',
            'otherTitleInfo' => 'otherTitleInfo',
            'subtitle' => 'otherTitleInfo',
            'statement_of_responsibility' => 'statementOfResponsibility',
            'statementOfResponsibility' => 'statementOfResponsibility',
            'author' => 'statementOfResponsibility',
            'creator' => 'statementOfResponsibility',
            'edition_statement' => 'editionStatement',
            'editionStatement' => 'editionStatement',
            'edition' => 'editionStatement',
            'publisher' => 'publisher',
            'place_of_publication' => 'placeOfPublication',
            'placeOfPublication' => 'placeOfPublication',
            'publication_place' => 'placeOfPublication',
            'date_of_publication' => 'dateOfPublication',
            'dateOfPublication' => 'dateOfPublication',
            'publication_date' => 'dateOfPublication',
            'date' => 'dateOfPublication',
            'year' => 'dateOfPublication',
            'copyright_date' => 'copyrightDate',
            'copyrightDate' => 'copyrightDate',
            'extent' => 'extent',
            'pages' => 'extent',
            'pagination' => 'extent',
            'physical_description' => 'extent',
            'dimensions' => 'dimensions',
            'size' => 'dimensions',
            'series_title' => 'seriesTitle',
            'seriesTitle' => 'seriesTitle',
            'series' => 'seriesTitle',
            'series_number' => 'seriesNumber',
            'seriesNumber' => 'seriesNumber',
            'volume' => 'seriesNumber',
            'language' => 'language',
            'note' => 'note',
            'notes' => 'note',
            'general_note' => 'generalNote',
            'generalNote' => 'generalNote',
            'table_of_contents' => 'tableOfContents',
            'tableOfContents' => 'tableOfContents',
            'contents' => 'tableOfContents',
            'summary' => 'summary',
            'abstract' => 'summary',
            'description' => 'summary',
            'scope_and_content' => 'summary',
            'subjects' => 'subjectAccessPoints',
            'subjectAccessPoints' => 'subjectAccessPoints',
            'subject' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'placeAccessPoints' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'nameAccessPoints' => 'nameAccessPoints',
            'genres' => 'genreAccessPoints',
            'genreAccessPoints' => 'genreAccessPoints',
            'genre' => 'genreAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digitalObjectPath' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'digitalObjectURI' => 'digitalObjectURI',
            'repository' => 'repository',
            'library' => 'repository',
            'physical_location' => 'physicalLocation',
            'physicalLocation' => 'physicalLocation',
            'shelf_location' => 'physicalLocation',
            'location' => 'physicalLocation',
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
