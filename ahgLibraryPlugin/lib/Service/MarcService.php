<?php

declare(strict_types=1);

/**
 * MarcService
 *
 * MARC 21 import and export for library records.
 * Handles MARC field mapping, ISO 2709 parsing, and MarcXML.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class MarcService
{
    protected static ?MarcService $instance = null;
    protected Logger $logger;

    /**
     * MARC 21 field mapping to library_item columns.
     * Format: MARC tag => [subfield => library_item column]
     */
    protected const MARC_MAP = [
        '020' => ['a' => 'isbn'],
        '022' => ['a' => 'issn'],
        '010' => ['a' => 'lccn'],
        '035' => ['a' => 'oclc_number'],
        '050' => ['a' => 'classification_number', 'b' => 'cutter_number'],
        '082' => ['a' => 'classification_number'],
        '090' => ['a' => 'call_number'],
        '099' => ['a' => 'call_number'],
        '250' => ['a' => 'edition_statement'],
        '260' => ['a' => 'publication_place', 'b' => 'publisher', 'c' => 'publication_date'],
        '264' => ['a' => 'publication_place', 'b' => 'publisher', 'c' => 'publication_date'],
        '300' => ['a' => 'pagination', 'b' => 'physical_details', 'c' => 'dimensions', 'e' => 'accompanying_material'],
        '310' => ['a' => 'frequency'],
        '362' => ['a' => 'numbering_peculiarities'],
        '440' => ['a' => 'series_title', 'v' => 'series_number', 'x' => 'series_issn'],
        '490' => ['a' => 'series_title', 'v' => 'series_number', 'x' => 'series_issn'],
        '500' => ['a' => 'general_note'],
        '504' => ['a' => 'bibliography_note'],
        '505' => ['a' => 'contents_note'],
        '520' => ['a' => 'summary'],
        '521' => ['a' => 'target_audience'],
        '538' => ['a' => 'system_requirements'],
        '563' => ['a' => 'binding_note'],
    ];

    /**
     * MARC author/creator field tags.
     */
    protected const AUTHOR_TAGS = [
        '100' => 'author',
        '110' => 'author',  // Corporate author
        '700' => 'contributor',
        '710' => 'contributor',  // Corporate contributor
    ];

    /**
     * MARC subject field tags.
     */
    protected const SUBJECT_TAGS = [
        '600' => 'personal',
        '610' => 'corporate',
        '611' => 'meeting',
        '630' => 'topical',
        '650' => 'topical',
        '651' => 'geographic',
        '655' => 'genre',
    ];

    public function __construct()
    {
        $this->initLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.marc');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // IMPORT (MarcXML)
    // ========================================================================

    /**
     * Import library records from a MarcXML file.
     *
     * @return array{imported: int, skipped: int, errors: array}
     */
    public function importMarcXml(string $filePath, ?int $repositoryId = null): array
    {
        if (!file_exists($filePath)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['File not found: ' . $filePath]];
        }

        $xml = @simplexml_load_file($filePath);
        if (!$xml) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Invalid XML file']];
        }

        // Handle namespaces
        $xml->registerXPathNamespace('marc', 'http://www.loc.gov/MARC21/slim');

        $records = $xml->xpath('//marc:record') ?: $xml->xpath('//record');
        if (empty($records)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['No MARC records found']];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($records as $i => $record) {
            try {
                $parsed = $this->parseMarcXmlRecord($record);
                if (empty($parsed['title'])) {
                    $skipped++;
                    $errors[] = "Record " . ($i + 1) . ": Missing title (245$a)";
                    continue;
                }

                $this->importParsedRecord($parsed, $repositoryId);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Record " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        $this->logger->info('MarcXML import complete', [
            'file'     => basename($filePath),
            'imported' => $imported,
            'skipped'  => $skipped,
        ]);

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Parse a single MarcXML <record> element into a data array.
     */
    protected function parseMarcXmlRecord(\SimpleXMLElement $record): array
    {
        $data = [];
        $creators = [];
        $subjects = [];

        // Register namespace for XPath
        $record->registerXPathNamespace('marc', 'http://www.loc.gov/MARC21/slim');

        // Get leader for material type detection
        $leader = (string) ($record->xpath('marc:leader')[0] ?? $record->xpath('leader')[0] ?? '');
        $data['material_type'] = $this->detectMaterialType($leader);

        // Process data fields
        $dataFields = $record->xpath('marc:datafield') ?: $record->xpath('datafield') ?: [];

        foreach ($dataFields as $field) {
            $tag = (string) $field['tag'];

            // Title (245)
            if ($tag === '245') {
                $data['title'] = $this->getSubfield($field, 'a');
                $subtitle = $this->getSubfield($field, 'b');
                if ($subtitle) {
                    $data['title'] .= ' : ' . $subtitle;
                }
                $data['title'] = rtrim($data['title'], ' /');
                continue;
            }

            // Mapped fields
            if (isset(self::MARC_MAP[$tag])) {
                foreach (self::MARC_MAP[$tag] as $subCode => $column) {
                    $val = $this->getSubfield($field, $subCode);
                    if ($val && empty($data[$column])) {
                        $data[$column] = rtrim($val, ' .,;:/');
                    }
                }
            }

            // Authors/Creators
            if (isset(self::AUTHOR_TAGS[$tag])) {
                $name = $this->getSubfield($field, 'a');
                if ($name) {
                    $role = $this->getSubfield($field, 'e') ?: self::AUTHOR_TAGS[$tag];
                    $creators[] = [
                        'name' => rtrim($name, ' .,'),
                        'role' => $this->normalizeRole($role),
                        'is_primary' => ($tag === '100' || $tag === '110'),
                    ];
                }
            }

            // Subjects
            if (isset(self::SUBJECT_TAGS[$tag])) {
                $heading = $this->getSubfield($field, 'a');
                if ($heading) {
                    $subdivisions = [];
                    foreach (['x', 'y', 'z', 'v'] as $subCode) {
                        $sub = $this->getSubfield($field, $subCode);
                        if ($sub) {
                            $subdivisions[] = rtrim($sub, ' .');
                        }
                    }

                    $source = $this->detectSubjectSource((string) $field['ind2']);

                    $subjects[] = [
                        'heading' => rtrim($heading, ' .'),
                        'heading_type' => self::SUBJECT_TAGS[$tag],
                        'source' => $source,
                        'subdivisions' => $subdivisions,
                    ];
                }
            }
        }

        // Classification scheme detection
        if (!empty($data['classification_number'])) {
            // If from 082 → Dewey, if from 050 → LCC
            $has082 = false;
            $has050 = false;
            foreach ($dataFields as $field) {
                $tag = (string) $field['tag'];
                if ($tag === '082') {
                    $has082 = true;
                }
                if ($tag === '050') {
                    $has050 = true;
                }
            }
            $data['classification_scheme'] = $has082 ? 'dewey' : ($has050 ? 'lcc' : null);
        }

        $data['creators'] = $creators;
        $data['subjects'] = $subjects;

        return $data;
    }

    /**
     * Import a parsed record into the database.
     */
    protected function importParsedRecord(array $parsed, ?int $repositoryId = null): int
    {
        $now = date('Y-m-d H:i:s');

        // Check for existing by ISBN
        $existingItem = null;
        if (!empty($parsed['isbn'])) {
            $existingItem = DB::table('library_item')
                ->where('isbn', $parsed['isbn'])
                ->first();
        }

        if ($existingItem) {
            // Update existing
            $itemId = $existingItem->id;
            $columns = array_intersect_key($parsed, array_flip([
                'material_type', 'call_number', 'classification_scheme', 'classification_number',
                'cutter_number', 'isbn', 'issn', 'lccn', 'oclc_number',
                'edition_statement', 'publisher', 'publication_place', 'publication_date',
                'pagination', 'dimensions', 'physical_details', 'accompanying_material',
                'series_title', 'series_number', 'series_issn',
                'general_note', 'bibliography_note', 'contents_note', 'summary',
                'target_audience', 'system_requirements', 'binding_note',
                'frequency', 'numbering_peculiarities',
            ]));
            $columns['updated_at'] = $now;
            DB::table('library_item')->where('id', $itemId)->update($columns);
        } else {
            // Create information_object first
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('information_object')->insert([
                'id' => $objectId,
                'parent_id' => 1, // Root
                'repository_id' => $repositoryId,
                'source_culture' => 'en',
            ]);

            // Set publication status via status table (type_id=158, status_id=159=Draft)
            $statusId = DB::table('object')->insertGetId([
                'class_name' => 'QubitStatus',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('status')->insert([
                'id' => $statusId,
                'object_id' => $objectId,
                'type_id' => 158,
                'status_id' => 159, // Draft
            ]);

            DB::table('information_object_i18n')->insert([
                'id' => $objectId,
                'culture' => 'en',
                'title' => $parsed['title'] ?? 'Untitled',
            ]);

            // Generate slug
            $slug = $this->generateSlug($parsed['title'] ?? 'untitled');
            DB::table('slug')->insert([
                'object_id' => $objectId,
                'slug' => $slug,
            ]);

            // Create library_item
            $itemData = array_intersect_key($parsed, array_flip([
                'material_type', 'call_number', 'classification_scheme', 'classification_number',
                'cutter_number', 'isbn', 'issn', 'lccn', 'oclc_number',
                'edition_statement', 'publisher', 'publication_place', 'publication_date',
                'pagination', 'dimensions', 'physical_details', 'accompanying_material',
                'series_title', 'series_number', 'series_issn',
                'general_note', 'bibliography_note', 'contents_note', 'summary',
                'target_audience', 'system_requirements', 'binding_note',
                'frequency', 'numbering_peculiarities',
            ]));

            $itemData['information_object_id'] = $objectId;
            $itemData['cataloging_rules'] = 'rda';
            $itemData['created_at'] = $now;
            $itemData['updated_at'] = $now;

            $itemId = DB::table('library_item')->insertGetId($itemData);
        }

        // Save creators
        if (!empty($parsed['creators'])) {
            // Remove old ones on reimport
            DB::table('library_item_creator')->where('library_item_id', $itemId)->delete();

            foreach ($parsed['creators'] as $sortOrder => $creator) {
                DB::table('library_item_creator')->insert([
                    'library_item_id' => $itemId,
                    'name' => $creator['name'],
                    'role' => $creator['role'],
                    'is_primary' => $creator['is_primary'] ? 1 : 0,
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        // Save subjects
        if (!empty($parsed['subjects'])) {
            DB::table('library_item_subject')->where('library_item_id', $itemId)->delete();

            foreach ($parsed['subjects'] as $sortOrder => $subject) {
                DB::table('library_item_subject')->insert([
                    'library_item_id' => $itemId,
                    'heading' => $subject['heading'],
                    'heading_type' => $subject['heading_type'],
                    'source' => $subject['source'],
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        return $itemId;
    }

    // ========================================================================
    // EXPORT (MarcXML)
    // ========================================================================

    /**
     * Export library items to MarcXML.
     *
     * @param int[] $itemIds  Empty = export all
     * @return string MarcXML content
     */
    public function exportMarcXml(array $itemIds = []): string
    {
        $query = DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            });

        if (!empty($itemIds)) {
            $query->whereIn('li.id', $itemIds);
        }

        $items = $query->select(['li.*', 'ioi.title'])->get();

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);

        $xml->startElement('collection');
        $xml->writeAttribute('xmlns', 'http://www.loc.gov/MARC21/slim');

        foreach ($items as $item) {
            $this->writeRecordXml($xml, $item);
        }

        $xml->endElement(); // collection
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Write a single MARC record to XML.
     */
    protected function writeRecordXml(\XMLWriter $xml, object $item): void
    {
        $xml->startElement('record');

        // Leader
        $leader = $this->buildLeader($item->material_type);
        $xml->writeElement('leader', $leader);

        // Control fields
        $xml->startElement('controlfield');
        $xml->writeAttribute('tag', '008');
        $xml->text($this->build008($item));
        $xml->endElement();

        // ISBN (020)
        if (!empty($item->isbn)) {
            $this->writeDataField($xml, '020', ' ', ' ', ['a' => $item->isbn]);
        }

        // ISSN (022)
        if (!empty($item->issn)) {
            $this->writeDataField($xml, '022', ' ', ' ', ['a' => $item->issn]);
        }

        // LCCN (010)
        if (!empty($item->lccn)) {
            $this->writeDataField($xml, '010', ' ', ' ', ['a' => $item->lccn]);
        }

        // Classification (050/082)
        if (!empty($item->classification_number)) {
            $tag = ($item->classification_scheme === 'dewey') ? '082' : '050';
            $subs = ['a' => $item->classification_number];
            if (!empty($item->cutter_number)) {
                $subs['b'] = $item->cutter_number;
            }
            $this->writeDataField($xml, $tag, '0', '4', $subs);
        }

        // Call number (099)
        if (!empty($item->call_number)) {
            $this->writeDataField($xml, '099', ' ', ' ', ['a' => $item->call_number]);
        }

        // Creators
        $creators = DB::table('library_item_creator')
            ->where('library_item_id', $item->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($creators as $i => $creator) {
            $tag = ($i === 0) ? '100' : '700';
            $subs = ['a' => $creator->name];
            if ($creator->role && $creator->role !== 'author') {
                $subs['e'] = $creator->role;
            }
            $this->writeDataField($xml, $tag, '1', ' ', $subs);
        }

        // Title (245)
        $titleSubs = ['a' => $item->title ?? 'Untitled'];
        $this->writeDataField($xml, '245', '1', '0', $titleSubs);

        // Edition (250)
        if (!empty($item->edition_statement)) {
            $this->writeDataField($xml, '250', ' ', ' ', ['a' => $item->edition_statement]);
        }

        // Publication (264)
        $pubSubs = [];
        if (!empty($item->publication_place)) {
            $pubSubs['a'] = $item->publication_place;
        }
        if (!empty($item->publisher)) {
            $pubSubs['b'] = $item->publisher;
        }
        if (!empty($item->publication_date)) {
            $pubSubs['c'] = $item->publication_date;
        }
        if (!empty($pubSubs)) {
            $this->writeDataField($xml, '264', ' ', '1', $pubSubs);
        }

        // Physical description (300)
        $physSubs = [];
        if (!empty($item->pagination)) {
            $physSubs['a'] = $item->pagination;
        }
        if (!empty($item->physical_details)) {
            $physSubs['b'] = $item->physical_details;
        }
        if (!empty($item->dimensions)) {
            $physSubs['c'] = $item->dimensions;
        }
        if (!empty($physSubs)) {
            $this->writeDataField($xml, '300', ' ', ' ', $physSubs);
        }

        // Series (490)
        if (!empty($item->series_title)) {
            $serSubs = ['a' => $item->series_title];
            if (!empty($item->series_number)) {
                $serSubs['v'] = $item->series_number;
            }
            $this->writeDataField($xml, '490', '0', ' ', $serSubs);
        }

        // Notes
        if (!empty($item->general_note)) {
            $this->writeDataField($xml, '500', ' ', ' ', ['a' => $item->general_note]);
        }
        if (!empty($item->bibliography_note)) {
            $this->writeDataField($xml, '504', ' ', ' ', ['a' => $item->bibliography_note]);
        }
        if (!empty($item->summary)) {
            $this->writeDataField($xml, '520', ' ', ' ', ['a' => $item->summary]);
        }

        // Subjects
        $subjects = DB::table('library_item_subject')
            ->where('library_item_id', $item->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($subjects as $subject) {
            $tag = $this->getSubjectTag($subject->heading_type);
            $ind2 = $this->getSubjectSourceIndicator($subject->source);
            $this->writeDataField($xml, $tag, ' ', $ind2, ['a' => $subject->heading]);
        }

        $xml->endElement(); // record
    }

    /**
     * Write a MARC data field with subfields.
     */
    protected function writeDataField(\XMLWriter $xml, string $tag, string $ind1, string $ind2, array $subfields): void
    {
        $xml->startElement('datafield');
        $xml->writeAttribute('tag', $tag);
        $xml->writeAttribute('ind1', $ind1);
        $xml->writeAttribute('ind2', $ind2);

        foreach ($subfields as $code => $value) {
            $xml->startElement('subfield');
            $xml->writeAttribute('code', (string) $code);
            $xml->text($value);
            $xml->endElement();
        }

        $xml->endElement();
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    protected function getSubfield(\SimpleXMLElement $field, string $code): ?string
    {
        $ns = $field->getNamespaces(true);
        $prefix = !empty($ns) ? 'marc:' : '';

        $subfields = $field->xpath($prefix . "subfield[@code='{$code}']");
        if (!empty($subfields)) {
            return trim((string) $subfields[0]);
        }

        // Fallback without namespace
        $subfields = $field->xpath("subfield[@code='{$code}']");
        return !empty($subfields) ? trim((string) $subfields[0]) : null;
    }

    protected function detectMaterialType(string $leader): string
    {
        if (strlen($leader) < 7) {
            return 'monograph';
        }

        $type = $leader[6];
        $bibLevel = $leader[7];

        if ($bibLevel === 's') {
            return 'serial';
        }
        if ($type === 'e' || $type === 'f') {
            return 'map';
        }
        if ($type === 'c' || $type === 'd') {
            return 'score';
        }
        if ($type === 't') {
            return 'manuscript';
        }

        return 'monograph';
    }

    protected function normalizeRole(string $role): string
    {
        $role = strtolower(rtrim($role, ' .,'));

        $map = [
            'author' => 'author',
            'ed.' => 'editor',
            'editor' => 'editor',
            'trans.' => 'translator',
            'translator' => 'translator',
            'ill.' => 'illustrator',
            'illustrator' => 'illustrator',
            'comp.' => 'compiler',
            'compiler' => 'compiler',
            'contributor' => 'contributor',
        ];

        return $map[$role] ?? 'contributor';
    }

    protected function detectSubjectSource(string $indicator2): string
    {
        return match ($indicator2) {
            '0' => 'lcsh',
            '1' => 'lcsh', // LC Children's
            '2' => 'mesh',
            '3' => 'aat',
            '5' => 'fast',
            default => 'local',
        };
    }

    protected function getSubjectTag(string $headingType): string
    {
        return match ($headingType) {
            'personal' => '600',
            'corporate' => '610',
            'meeting' => '611',
            'geographic' => '651',
            'genre' => '655',
            default => '650',
        };
    }

    protected function getSubjectSourceIndicator(string $source): string
    {
        return match ($source) {
            'lcsh' => '0',
            'mesh' => '2',
            'aat' => '3',
            'fast' => '7',
            default => '4',
        };
    }

    protected function buildLeader(string $materialType): string
    {
        $type = match ($materialType) {
            'serial' => 'as',
            'map' => 'em',
            'score' => 'cm',
            'manuscript' => 'tm',
            default => 'am',
        };

        // positions: 00-04(length), 05(status), 06(type), 07(bib), 08-09(blank), 10-11(indicator/subfield), 12-16(base), 17-19(encoding)
        return '00000n' . $type . ' a22     4500';
    }

    protected function build008(object $item): string
    {
        $date = date('ymd');
        $pubYear = substr($item->publication_date ?? '    ', 0, 4);
        $pubYear = str_pad($pubYear, 4);

        // Basic 008 — date entered, pub status, dates, country, language
        return $date . 's' . $pubYear . '    xx            000 0 eng d';
    }

    protected function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 200);

        // Ensure uniqueness
        $base = $slug;
        $i = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
