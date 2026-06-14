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

    // ========================================================================
    // CSV IMPORT
    // ========================================================================

    /**
     * Bulk import library items from a CSV file.
     *
     * Supported columns (header row required):
     *   title, author, isbn, issn, doi, lccn, oclc_number,
     *   publisher, publication_date, publication_place, edition_statement,
     *   material_type, language, call_number, dewey_decimal, classification_number,
     *   pagination, physical_details, description, subjects,
     *   barcode, copy_count, location
     *
     * If 'isbn' is present and matches an existing item, the item is updated.
     * Otherwise a new library item is created.
     *
     * @param string $filePath  Path to the CSV file
     * @param int|null $repositoryId  AtoM repository ID
     * @param array $options    Options: dry_run (bool), delimiter (string), enclosure (string)
     * @return array{imported: int, skipped: int, errors: string[], results: array}
     */
    public function importCsv(string $filePath, ?int $repositoryId = null, array $options = []): array
    {
        $dry_run = !empty($options['dry_run']);
        $delimiter = $options['delimiter'] ?? ';';
        $enclosure = $options['enclosure'] ?? '"';

        if (!file_exists($filePath)) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['File not found: ' . $filePath],
                'results'  => [],
            ];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['Could not open file: ' . $filePath],
                'results'  => [],
            ];
        }

        // Read header row
        $headers = fgetcsv($handle, 0, $delimiter, $enclosure);
        if ($headers === false || empty($headers)) {
            fclose($handle);
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['CSV file has no header row or is empty'],
                'results'  => [],
            ];
        }

        // Normalize headers: trim, lowercase, map aliases
        $normalizedHeaders = [];
        $aliasMap = [
            'title'              => ['title', 'main_title', 'bib_title'],
            'author'             => ['author', 'authors', 'creator', 'creators', 'name'],
            'isbn'              => ['isbn', 'isbn_13', 'isbn10'],
            'issn'              => ['issn'],
            'doi'               => ['doi'],
            'lccn'              => ['lccn'],
            'oclc_number'       => ['oclc', 'oclc_number'],
            'publisher'         => ['publisher', 'publishers'],
            'publication_date'  => ['publication_date', 'pub_date', 'year', 'date', 'date_of_publication'],
            'publication_place' => ['publication_place', 'pub_place', 'place', 'place_of_publication'],
            'edition_statement' => ['edition', 'edition_statement', 'edition_info'],
            'material_type'     => ['material_type', 'type', 'format', 'mat_type'],
            'language'          => ['language', 'lang'],
            'call_number'       => ['call_number', 'callnumber', 'shelfmark'],
            'dewey_decimal'     => ['dewey_decimal', 'ddc', 'dewey'],
            'classification_number' => ['classification_number', 'classification'],
            'pagination'        => ['pagination', 'pages', 'page_count', 'extent'],
            'physical_details'  => ['physical_details', 'physical_description', 'physical_form'],
            'description'       => ['description', 'abstract', 'summary', 'notes'],
            'subjects'          => ['subjects', 'subject', 'keywords', 'keyword'],
            'barcode'           => ['barcode', 'item_barcode'],
            'copy_count'        => ['copy_count', 'copies', 'number_of_copies'],
            'location'          => ['location', 'shelf_location', 'holding_location'],
        ];

        foreach ($headers as $col) {
            $colNorm = strtolower(trim($col));
            $found = false;
            foreach ($aliasMap as $target => $aliases) {
                if (in_array($colNorm, $aliases, true)) {
                    $normalizedHeaders[$target] = array_search($col, $headers, true);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $normalizedHeaders[$colNorm] = array_search($col, $headers, true);
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $results = [];
        $rowNum = 1; // header is row 1

        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
            $rowNum++;
            $lineErrors = [];

            // Map row values by normalized header index
            $values = function (string $key) use ($row, $normalizedHeaders): ?string {
                if (!isset($normalizedHeaders[$key])) {
                    return null;
                }
                $idx = $normalizedHeaders[$key];
                if (!isset($row[$idx]) || trim($row[$idx]) === '') {
                    return null;
                }
                return trim($row[$idx]);
            };

            $title = $values('title');
            if (empty($title)) {
                $lineErrors[] = "Row $rowNum: Missing title";
                $skipped++;
                $errors = array_merge($errors, $lineErrors);
                continue;
            }

            // Build parsed record
            $parsed = [
                'title'              => $title,
                'isbn'              => $values('isbn') ?? $values('isbn_13'),
                'issn'              => $values('issn'),
                'lccn'              => $values('lccn'),
                'oclc_number'       => $values('oclc_number'),
                'publisher'         => $values('publisher'),
                'publication_date'  => $values('publication_date'),
                'publication_place' => $values('publication_place'),
                'edition_statement' => $values('edition_statement'),
                'material_type'     => $values('material_type') ?: 'book',
                'pagination'        => $values('pagination'),
                'physical_details'  => $values('physical_details'),
                'description'       => $values('description'),
                'call_number'       => $values('call_number'),
                'dewey_decimal'     => $values('dewey_decimal'),
                'classification_number' => $values('classification_number'),
            ];

            // Authors / creators
            $authorRaw = $values('author');
            if ($authorRaw) {
                $authors = array_filter(array_map('trim', explode(';', $authorRaw)));
                $parsed['creators'] = [];
                foreach ($authors as $i => $name) {
                    $parsed['creators'][] = [
                        'name'      => $name,
                        'role'      => 'aut',
                        'is_primary' => ($i === 0),
                    ];
                }
            }

            // Subjects (semicolon-separated)
            $subjectsRaw = $values('subjects');
            if ($subjectsRaw) {
                $subjectNames = array_filter(array_map('trim', explode(';', $subjectsRaw)));
                $parsed['subjects'] = [];
                foreach ($subjectNames as $i => $name) {
                    $parsed['subjects'][] = [
                        'heading'      => $name,
                        'heading_type' => 'topical',
                        'source'       => 'lcsh',
                    ];
                }
            }

            // DOI
            $doiRaw = $values('doi');
            if ($doiRaw) {
                $parsed['doi'] = preg_replace('#^https?://(?:dx\.)?doi\.org/#i', '', $doiRaw);
            }

            if ($dry_run) {
                $results[] = [
                    'row'     => $rowNum,
                    'action'  => 'create_or_update',
                    'title'   => $title,
                    'isbn'    => $parsed['isbn'] ?? null,
                    'errors'  => [],
                ];
                $imported++;
                continue;
            }

            try {
                $itemId = $this->importParsedRecord($parsed, $repositoryId);

                // Create copies if specified
                $copyCount = (int) ($values('copy_count') ?? 1);
                $barcode = $values('barcode');
                $location = $values('location');

                for ($c = 0; $c < $copyCount; $c++) {
                    $copyBarcode = $barcode
                        ? $barcode . ($copyCount > 1 ? '-' . ($c + 1) : '')
                        : null;

                    DB::table('library_copy')->insert([
                        'library_item_id'    => $itemId,
                        'barcode'            => $copyBarcode,
                        'copy_status'        => 'available',
                        'home_location'      => $location,
                        'created_at'         => date('Y-m-d H:i:s'),
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ]);
                }

                $results[] = [
                    'row'     => $rowNum,
                    'action'  => 'imported',
                    'item_id' => $itemId,
                    'title'   => $title,
                ];
                $imported++;

            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row $rowNum: " . $e->getMessage();
                $results[] = [
                    'row'    => $rowNum,
                    'action' => 'error',
                    'title'  => $title,
                    'error'  => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        $this->logger->info('CSV import complete', [
            'file'     => basename($filePath),
            'imported' => $imported,
            'skipped'  => $skipped,
            'dry_run'  => $dry_run,
        ]);

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'results'  => $results,
        ];
    }

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
     * Import binary MARC21 (ISO 2709) records from a .mrc file.
     *
     * Splits the stream into records, decodes each with Marc21DecoderService,
     * maps it through the SAME column/creator/subject logic used for MARCXML
     * (parseDecodedRecord mirrors parseMarcXmlRecord), then persists via
     * importParsedRecord — so binary and MARCXML import share one create path.
     *
     * @param string   $filePath      Path to a binary MARC21 (.mrc) file.
     * @param int|null $repositoryId  Optional repository to attach records to.
     * @return array{imported:int,skipped:int,errors:string[]}
     */
    public function importMarc21(string $filePath, ?int $repositoryId = null): array
    {
        if (!file_exists($filePath)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['File not found: ' . $filePath]];
        }

        $raw = file_get_contents($filePath);
        if ($raw === false || strlen($raw) < 24) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Empty or invalid MARC21 file']];
        }

        $decoder = new Marc21DecoderService();
        $records = $decoder->splitRecords($raw);
        if (empty($records)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['No MARC21 records found']];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($records as $i => $blob) {
            try {
                $decoded = $decoder->decode($blob);
                $parsed  = $this->parseDecodedRecord($decoded);
                if (empty($parsed['title'])) {
                    $skipped++;
                    $errors[] = 'Record ' . ($i + 1) . ': Missing title (245$a)';
                    continue;
                }

                $this->importParsedRecord($parsed, $repositoryId);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = 'Record ' . ($i + 1) . ': ' . $e->getMessage();
            }
        }

        $this->logger->info('MARC21 binary import complete', [
            'file'     => basename($filePath),
            'imported' => $imported,
            'skipped'  => $skipped,
        ]);

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Map a decoded binary MARC record (Marc21DecoderService::decode output)
     * into the same data array shape as parseMarcXmlRecord(). Reuses the
     * MARC_MAP / AUTHOR_TAGS / SUBJECT_TAGS constants and the detect/normalize
     * helpers so the binary path stays consistent with MARCXML import.
     */
    protected function parseDecodedRecord(array $rec): array
    {
        $data = [];
        $creators = [];
        $subjects = [];

        $leader = (string) ($rec['leader'] ?? '');
        $data['material_type'] = $this->detectMaterialType($leader);

        // Preserve control fields for round-trip export (#111).
        if ($leader !== '') {
            $data['marc_leader'] = $leader;
        }
        if (!empty($rec['control']['005'])) {
            $data['marc_005'] = $rec['control']['005'];
        }
        if (!empty($rec['control']['008'])) {
            $data['marc_008'] = $rec['control']['008'];
        }

        $has082 = false;
        $has050 = false;

        foreach (($rec['data'] ?? []) as $field) {
            $tag = (string) ($field['tag'] ?? '');

            if ($tag === '082') {
                $has082 = true;
            }
            if ($tag === '050') {
                $has050 = true;
            }

            // Title (245)
            if ($tag === '245') {
                $title = (string) (Marc21DecoderService::subfield($field, 'a') ?? '');
                $subtitle = Marc21DecoderService::subfield($field, 'b');
                if ($subtitle) {
                    $title .= ' : ' . $subtitle;
                }
                $data['title'] = rtrim($title, ' /');
                continue;
            }

            // RDA carrier / content type (336$a / 337$a / 338$a)
            if ($tag === '336' && empty($data['content_type'])) {
                $v = Marc21DecoderService::subfield($field, 'a');
                if ($v) {
                    $data['content_type'] = rtrim($v, ' .,;:/');
                }
                continue;
            }
            if ($tag === '337' && empty($data['carrier_type'])) {
                $v = Marc21DecoderService::subfield($field, 'a');
                if ($v) {
                    $data['carrier_type'] = rtrim($v, ' .,;:/');
                }
                continue;
            }
            if ($tag === '338' && empty($data['instance_type'])) {
                $v = Marc21DecoderService::subfield($field, 'a');
                if ($v) {
                    $data['instance_type'] = rtrim($v, ' .,;:/');
                }
                continue;
            }

            // Mapped columns
            if (isset(self::MARC_MAP[$tag])) {
                foreach (self::MARC_MAP[$tag] as $subCode => $column) {
                    $val = Marc21DecoderService::subfield($field, $subCode);
                    if ($val && empty($data[$column])) {
                        $data[$column] = rtrim($val, ' .,;:/');
                    }
                }
            }

            // Authors / creators
            if (isset(self::AUTHOR_TAGS[$tag])) {
                $name = Marc21DecoderService::subfield($field, 'a');
                if ($name) {
                    $role = Marc21DecoderService::subfield($field, 'e') ?: self::AUTHOR_TAGS[$tag];
                    $creators[] = [
                        'name'       => rtrim($name, ' .,'),
                        'role'       => $this->normalizeRole($role),
                        'is_primary' => ($tag === '100' || $tag === '110'),
                    ];
                }
            }

            // Subjects (6XX)
            if (isset(self::SUBJECT_TAGS[$tag])) {
                $heading = Marc21DecoderService::subfield($field, 'a');
                if ($heading) {
                    $subdivisions = [];
                    foreach (['x', 'y', 'z', 'v'] as $subCode) {
                        $subVal = Marc21DecoderService::subfield($field, $subCode);
                        if ($subVal) {
                            $subdivisions[] = rtrim($subVal, ' .');
                        }
                    }
                    $subjects[] = [
                        'heading'      => rtrim($heading, ' .'),
                        'heading_type' => self::SUBJECT_TAGS[$tag],
                        'source'       => $this->detectSubjectSource((string) ($field['ind2'] ?? ' ')),
                        'subdivisions' => $subdivisions,
                    ];
                }
            }
        }

        if (!empty($data['classification_number'])) {
            $data['classification_scheme'] = $has082 ? 'dewey' : ($has050 ? 'lcc' : null);
        }

        $data['creators'] = $creators;
        $data['subjects'] = $subjects;

        return $data;
    }

    /**
     * Detect existing library items that conflict with a parsed record by any
     * standard identifier — ISBN (020), ISSN (022), OCLC (035), LCCN (010) —
     * so the importer can warn / offer a merge instead of silently duplicating
     * (#111). Returns [existing library_item.id => [matched identifier columns]].
     *
     * @return array<int,string[]>
     */
    public function findConflicts(array $parsed): array
    {
        $conflicts = [];
        foreach (['isbn', 'issn', 'lccn', 'oclc_number'] as $col) {
            if (empty($parsed[$col])) {
                continue;
            }
            foreach (DB::table('library_item')->where($col, $parsed[$col])->pluck('id')->all() as $id) {
                $conflicts[(int) $id][] = $col;
            }
        }

        return array_map(fn ($cols) => array_values(array_unique($cols)), $conflicts);
    }

    /**
     * Static convenience: decode a single binary MARC21 record (ISO 2709) into
     * the parsed data array (title, mapped columns, creators[], subjects[], RDA).
     *
     * This is the bridge Z3950Service::importResults() expects, and the parser
     * CopyCataloguing uses for Z39.50 result rows. Reuses the binary decoder and
     * the shared parseDecodedRecord mapping (single source of truth).
     */
    public static function parseMarc21(string $raw): array
    {
        require_once __DIR__ . '/Marc21DecoderService.php';

        $decoder = new Marc21DecoderService();
        $decoded = $decoder->decode($raw);

        return (new self())->parseDecodedRecord($decoded);
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
                'content_type', 'carrier_type', 'instance_type',
                'marc_leader', 'marc_005', 'marc_008',
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
                'content_type', 'carrier_type', 'instance_type',
                'marc_leader', 'marc_005', 'marc_008',
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
     * Export library items as binary MARC21 (ISO 2709) — a concatenation of
     * records suitable for a .mrc file. Reuses the same field map as the XML
     * export via buildRecordFields().
     */
    public function exportMarc21(array $itemIds = []): string
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

        $out = '';
        foreach ($items as $item) {
            $out .= $this->recordToIso2709($this->buildRecordFields($item));
        }

        return $out;
    }

    /**
     * Build a structured MARC record (leader + control + data fields) from a
     * library_item. Mirrors writeRecordXml()'s field map.
     *
     * @return array{leader:string,control:array<int,array{0:string,1:string}>,data:array<int,array{tag:string,ind1:string,ind2:string,subfields:array<string,string>}>}
     */
    protected function buildRecordFields(object $item): array
    {
        $leader = !empty($item->marc_leader) ? $item->marc_leader : $this->buildLeader($item->material_type);

        $control = [];
        if (!empty($item->marc_005)) {
            $control[] = ['005', (string) $item->marc_005];
        }
        $control[] = ['008', !empty($item->marc_008) ? (string) $item->marc_008 : $this->build008($item)];

        $data = [];
        $df = function (string $tag, string $i1, string $i2, array $subs) use (&$data): void {
            $subs = array_filter($subs, static fn ($v) => $v !== null && $v !== '');
            if ($subs !== []) {
                $data[] = ['tag' => $tag, 'ind1' => $i1, 'ind2' => $i2, 'subfields' => $subs];
            }
        };

        $df('020', ' ', ' ', ['a' => $item->isbn ?? '']);
        $df('022', ' ', ' ', ['a' => $item->issn ?? '']);
        $df('010', ' ', ' ', ['a' => $item->lccn ?? '']);

        if (!empty($item->classification_number)) {
            $tag = (($item->classification_scheme ?? '') === 'dewey') ? '082' : '050';
            $df($tag, '0', '4', ['a' => $item->classification_number, 'b' => $item->cutter_number ?? '']);
        }
        $df('099', ' ', ' ', ['a' => $item->call_number ?? '']);

        $creators = DB::table('library_item_creator')->where('library_item_id', $item->id)->orderBy('sort_order')->get();
        foreach ($creators as $i => $creator) {
            $subs = ['a' => $creator->name];
            if ($creator->role && $creator->role !== 'author') {
                $subs['e'] = $creator->role;
            }
            $df(($i === 0) ? '100' : '700', '1', ' ', $subs);
        }

        $df('245', '1', '0', ['a' => $item->title ?? 'Untitled']);
        $df('250', ' ', ' ', ['a' => $item->edition_statement ?? '']);
        $df('264', ' ', '1', ['a' => $item->publication_place ?? '', 'b' => $item->publisher ?? '', 'c' => $item->publication_date ?? '']);
        $df('300', ' ', ' ', ['a' => $item->pagination ?? '', 'b' => $item->physical_details ?? '', 'c' => $item->dimensions ?? '']);
        $df('490', '0', ' ', ['a' => $item->series_title ?? '', 'v' => $item->series_number ?? '']);
        $df('500', ' ', ' ', ['a' => $item->general_note ?? '']);
        $df('504', ' ', ' ', ['a' => $item->bibliography_note ?? '']);
        $df('520', ' ', ' ', ['a' => $item->summary ?? '']);

        $subjects = DB::table('library_item_subject')->where('library_item_id', $item->id)->orderBy('sort_order')->get();
        foreach ($subjects as $subject) {
            $df($this->getSubjectTag($subject->heading_type), ' ', $this->getSubjectSourceIndicator($subject->source), ['a' => $subject->heading]);
        }

        return ['leader' => $leader, 'control' => $control, 'data' => $data];
    }

    /**
     * Serialize a structured record to ISO 2709 (binary MARC). Byte-accurate:
     * directory + leader length/base-address are computed in bytes (UTF-8 safe).
     */
    protected function recordToIso2709(array $rec): string
    {
        $FT = "\x1E"; // field terminator
        $RT = "\x1D"; // record terminator
        $SF = "\x1F"; // subfield delimiter

        $dir = '';
        $fields = '';
        $pos = 0;

        $append = function (string $tag, string $content) use (&$dir, &$fields, &$pos, $FT): void {
            $f = $content . $FT;
            $len = strlen($f); // bytes
            $dir .= $tag . str_pad((string) $len, 4, '0', STR_PAD_LEFT) . str_pad((string) $pos, 5, '0', STR_PAD_LEFT);
            $fields .= $f;
            $pos += $len;
        };

        foreach ($rec['control'] as [$tag, $val]) {
            $append($tag, $val);
        }
        foreach ($rec['data'] as $d) {
            $content = $d['ind1'] . $d['ind2'];
            foreach ($d['subfields'] as $code => $v) {
                $content .= $SF . $code . $v;
            }
            $append($d['tag'], $content);
        }

        $dir .= $FT;
        $base = 24 + strlen($dir);
        $body = $fields . $RT;
        $recLen = $base + strlen($body);

        // Normalise the 24-byte leader, stamping record length (0-4) + base
        // address of data (12-16); keep the other positions from the leader.
        $leader = str_pad(substr((string) $rec['leader'], 0, 24), 24);
        $leader = str_pad((string) $recLen, 5, '0', STR_PAD_LEFT)
            . substr($leader, 5, 7)
            . str_pad((string) $base, 5, '0', STR_PAD_LEFT)
            . substr($leader, 17, 7);

        return $leader . $dir . $body;
    }

    /**
     * Write a single MARC record to XML.
     */
    protected function writeRecordXml(\XMLWriter $xml, object $item): void
    {
        $xml->startElement('record');

        // Leader — preserved from import when available, else regenerated (#111).
        $leader = !empty($item->marc_leader) ? $item->marc_leader : $this->buildLeader($item->material_type);
        $xml->writeElement('leader', $leader);

        // Control fields — round-trip preserved 005/008 when present (#111).
        if (!empty($item->marc_005)) {
            $xml->startElement('controlfield');
            $xml->writeAttribute('tag', '005');
            $xml->text($item->marc_005);
            $xml->endElement();
        }

        $xml->startElement('controlfield');
        $xml->writeAttribute('tag', '008');
        $xml->text(!empty($item->marc_008) ? $item->marc_008 : $this->build008($item));
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
