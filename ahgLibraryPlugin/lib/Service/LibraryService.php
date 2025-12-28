<?php

declare(strict_types=1);

/**
 * LibraryService
 *
 * Business logic layer for library operations
 * Handles validation, ISBN lookup, ES indexing, and circulation
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class LibraryService
{
    protected static ?LibraryService $instance = null;
    protected LibraryRepository $repository;
    protected Logger $logger;
    protected string $culture;

    // Creator role constants with MARC relator codes
    public const ROLES = [
        'author' => ['label' => 'Author', 'code' => 'aut'],
        'editor' => ['label' => 'Editor', 'code' => 'edt'],
        'translator' => ['label' => 'Translator', 'code' => 'trl'],
        'illustrator' => ['label' => 'Illustrator', 'code' => 'ill'],
        'compiler' => ['label' => 'Compiler', 'code' => 'com'],
        'contributor' => ['label' => 'Contributor', 'code' => 'ctb'],
        'author_of_introduction' => ['label' => 'Author of introduction', 'code' => 'aui'],
        'author_of_afterword' => ['label' => 'Author of afterword', 'code' => 'aft'],
        'photographer' => ['label' => 'Photographer', 'code' => 'pht'],
        'composer' => ['label' => 'Composer', 'code' => 'cmp'],
    ];

    // Subject source constants
    public const SUBJECT_SOURCES = [
        'lcsh' => 'Library of Congress Subject Headings',
        'mesh' => 'Medical Subject Headings',
        'aat' => 'Art & Architecture Thesaurus',
        'fast' => 'Faceted Application of Subject Terminology',
        'local' => 'Local Subject Headings',
    ];

    // Subject types
    public const SUBJECT_TYPES = [
        'topical' => 'Topical Subject',
        'personal' => 'Personal Name',
        'corporate' => 'Corporate Name',
        'geographic' => 'Geographic Name',
        'genre' => 'Genre/Form',
        'meeting' => 'Meeting/Conference',
    ];

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? sfContext::getInstance()->getUser()->getCulture() ?? 'en';
        $this->repository = LibraryRepository::getInstance($this->culture);
        $this->initLogger();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(?string $culture = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($culture);
        }
        return self::$instance;
    }

    /**
     * Initialize logger
     */
    protected function initLogger(): void
    {
        $this->logger = new Logger('library');
        $logPath = sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    /**
     * Get library item by information object ID
     */
    public function getByObjectId(int $objectId): ?LibraryItem
    {
        return $this->repository->findByObjectId($objectId);
    }

    /**
     * Get library item by ID
     */
    public function getById(int $id): ?LibraryItem
    {
        return $this->repository->find($id);
    }

    /**
     * Save library item with validation
     */
    public function save(int $objectId, array $data): LibraryItem
    {
        // Validate data
        $errors = $this->validate($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        // Clean identifiers
        if (!empty($data['isbn'])) {
            $data['isbn'] = $this->cleanIsbn($data['isbn']);
        }
        if (!empty($data['issn'])) {
            $data['issn'] = $this->cleanIssn($data['issn']);
        }

        // Find existing or create new
        $item = $this->repository->findByObjectId($objectId);

        if (!$item) {
            $item = new LibraryItem();
            $item->information_object_id = $objectId;
        }

        // Map data to item
        $this->mapDataToItem($item, $data);

        // Save
        $item = $this->repository->save($item);

        // Update Elasticsearch
        $this->updateSearchIndex($objectId, $item);

        $this->logger->info('Library item saved', [
            'id' => $item->id,
            'object_id' => $objectId,
        ]);

        return $item;
    }

    /**
     * Delete library item
     */
    public function delete(int $objectId): bool
    {
        $deleted = $this->repository->deleteByObjectId($objectId);

        if ($deleted) {
            $this->logger->info('Library item deleted', ['object_id' => $objectId]);
        }

        return $deleted;
    }

    /**
     * Validate library item data
     */
    public function validate(array $data): array
    {
        $errors = [];

        // ISBN validation
        if (!empty($data['isbn']) && !$this->validateIsbn($data['isbn'])) {
            $errors[] = 'Invalid ISBN format or check digit';
        }

        // ISSN validation
        if (!empty($data['issn']) && !$this->validateIssn($data['issn'])) {
            $errors[] = 'Invalid ISSN format or check digit';
        }

        // Material type validation
        if (!empty($data['material_type'])) {
            $validTypes = array_keys(LibraryItem::getMaterialTypes());
            if (!in_array($data['material_type'], $validTypes)) {
                $errors[] = 'Invalid material type';
            }
        }

        // Classification scheme validation
        if (!empty($data['classification_scheme'])) {
            $validSchemes = array_keys(LibraryItem::getClassificationSchemes());
            if (!in_array($data['classification_scheme'], $validSchemes)) {
                $errors[] = 'Invalid classification scheme';
            }
        }

        return $errors;
    }

    /**
     * Validate ISBN (10 or 13)
     */
    public function validateIsbn(string $isbn): bool
    {
        $isbn = $this->cleanIsbn($isbn);

        if (strlen($isbn) === 10) {
            return $this->validateIsbn10($isbn);
        }

        if (strlen($isbn) === 13) {
            return $this->validateIsbn13($isbn);
        }

        return false;
    }

    /**
     * Validate ISBN-10
     */
    protected function validateIsbn10(string $isbn): bool
    {
        if (!preg_match('/^[0-9]{9}[0-9X]$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }

        $check = $isbn[9];
        $checkDigit = $check === 'X' ? 10 : (int) $check;

        return ($sum + $checkDigit) % 11 === 0;
    }

    /**
     * Validate ISBN-13
     */
    protected function validateIsbn13(string $isbn): bool
    {
        if (!preg_match('/^[0-9]{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int) $isbn[12] === $checkDigit;
    }

    /**
     * Validate ISSN
     */
    public function validateIssn(string $issn): bool
    {
        $issn = $this->cleanIssn($issn);

        if (!preg_match('/^[0-9]{7}[0-9X]$/', $issn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $issn[$i] * (8 - $i);
        }

        $check = $issn[7];
        $checkDigit = $check === 'X' ? 10 : (int) $check;

        return ($sum + $checkDigit) % 11 === 0;
    }

    /**
     * Clean ISBN (remove hyphens, spaces)
     */
    public function cleanIsbn(string $isbn): string
    {
        return preg_replace('/[^0-9X]/', '', strtoupper($isbn));
    }

    /**
     * Clean ISSN
     */
    public function cleanIssn(string $issn): string
    {
        return preg_replace('/[^0-9X]/', '', strtoupper($issn));
    }

    /**
     * Format ISBN for display
     */
    public function formatIsbn(string $isbn): string
    {
        $isbn = $this->cleanIsbn($isbn);

        if (strlen($isbn) === 13) {
            return sprintf(
                '%s-%s-%s-%s-%s',
                substr($isbn, 0, 3),
                substr($isbn, 3, 1),
                substr($isbn, 4, 4),
                substr($isbn, 8, 4),
                substr($isbn, 12, 1)
            );
        }

        if (strlen($isbn) === 10) {
            return sprintf(
                '%s-%s-%s-%s',
                substr($isbn, 0, 1),
                substr($isbn, 1, 4),
                substr($isbn, 5, 4),
                substr($isbn, 9, 1)
            );
        }

        return $isbn;
    }

    /**
     * Format ISSN for display
     */
    public function formatIssn(string $issn): string
    {
        $issn = $this->cleanIssn($issn);

        if (strlen($issn) === 8) {
            return substr($issn, 0, 4) . '-' . substr($issn, 4, 4);
        }

        return $issn;
    }

    /**
     * Convert ISBN-10 to ISBN-13
     */
    public function isbn10To13(string $isbn10): ?string
    {
        $isbn10 = $this->cleanIsbn($isbn10);

        if (strlen($isbn10) !== 10) {
            return null;
        }

        // Prepend 978 and remove old check digit
        $isbn13 = '978' . substr($isbn10, 0, 9);

        // Calculate new check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn13[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $isbn13 . $checkDigit;
    }

    /**
     * Map form data to LibraryItem
     */
    protected function mapDataToItem(LibraryItem $item, array $data): void
    {
        $fields = [
            'material_type', 'call_number', 'classification_scheme', 'classification_number',
            'cutter_number', 'shelf_location', 'copy_number', 'volume_designation',
            'isbn', 'issn', 'lccn', 'oclc_number', 'doi', 'barcode',
            'edition', 'edition_statement', 'publisher', 'publication_place',
            'publication_date', 'copyright_date', 'printing',
            'pagination', 'dimensions', 'physical_details', 'accompanying_material',
            'series_title', 'series_number', 'series_issn', 'subseries_title',
            'general_note', 'bibliography_note', 'contents_note', 'summary',
            'target_audience', 'system_requirements', 'binding_note',
            'frequency', 'former_frequency', 'numbering_peculiarities',
            'publication_start_date', 'publication_end_date', 'publication_status',
            'total_copies', 'available_copies', 'circulation_status',
            'cataloging_source', 'cataloging_rules', 'encoding_level',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $item->{$field} = $data[$field];
            }
        }

        // Handle related data
        if (isset($data['creators'])) {
            $item->creators = $data['creators'];
        }

        if (isset($data['subjects'])) {
            $item->subjects = $data['subjects'];
        }

        if (isset($data['copies'])) {
            $item->copies = $data['copies'];
        }

        if (isset($data['serial_holdings'])) {
            $item->serial_holdings = $data['serial_holdings'];
        }
    }

    /**
     * Update Elasticsearch index
     */
    protected function updateSearchIndex(int $objectId, LibraryItem $item): void
    {
        try {
            $esDoc = [
                'library_material_type' => $item->material_type,
                'library_call_number' => $item->call_number,
                'library_isbn' => $item->isbn,
                'library_issn' => $item->issn,
                'library_publisher' => $item->publisher,
                'library_publication_date' => $item->publication_date,
                'library_series_title' => $item->series_title,
                'library_edition' => $item->edition,
                'library_circulation_status' => $item->circulation_status,
            ];

            // Add creator names
            if (!empty($item->creators)) {
                $esDoc['library_creators'] = array_column($item->creators, 'name');
                $esDoc['library_primary_creator'] = $item->getPrimaryCreator();
            }

            // Add subject headings
            if (!empty($item->subjects)) {
                $esDoc['library_subjects'] = array_column($item->subjects, 'heading');
            }

            // Update via Elasticsearch client
            if (class_exists('arElasticSearchPlugin')) {
                $client = arElasticSearchPlugin::getInstance()->client;
                $client->update([
                    'index' => arElasticSearchPlugin::getInstance()->index->getName(),
                    'id' => $objectId,
                    'body' => [
                        'doc' => $esDoc,
                        'doc_as_upsert' => true,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to update ES index', [
                'object_id' => $objectId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Search library items
     */
    public function search(array $params = []): array
    {
        return $this->repository->search($params);
    }

    /**
     * Get library statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Lookup ISBN via Open Library API
     */
    public function lookupIsbn(string $isbn): ?array
    {
        $isbn = $this->cleanIsbn($isbn);

        $url = sprintf(
            'https://openlibrary.org/api/books?bibkeys=ISBN:%s&format=json&jscmd=data',
            urlencode($isbn)
        );

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'AtoM Library Plugin/1.0',
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);
            $key = 'ISBN:' . $isbn;

            if (empty($data[$key])) {
                return null;
            }

            $book = $data[$key];

            // Map to our format
            return [
                'title' => $book['title'] ?? null,
                'authors' => array_map(
                    fn($a) => ['name' => $a['name'], 'role' => 'author'],
                    $book['authors'] ?? []
                ),
                'publishers' => array_map(
                    fn($p) => $p['name'],
                    $book['publishers'] ?? []
                ),
                'publish_date' => $book['publish_date'] ?? null,
                'number_of_pages' => $book['number_of_pages'] ?? null,
                'subjects' => array_map(
                    fn($s) => ['heading' => $s['name'], 'heading_type' => 'topical'],
                    $book['subjects'] ?? []
                ),
                'cover_url' => $book['cover']['medium'] ?? $book['cover']['small'] ?? null,
                'identifiers' => $book['identifiers'] ?? [],
            ];
        } catch (\Exception $e) {
            $this->logger->warning('ISBN lookup failed', [
                'isbn' => $isbn,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get form options
     */
    public function getFormOptions(): array
    {
        return [
            'material_types' => LibraryItem::getMaterialTypes(),
            'classification_schemes' => LibraryItem::getClassificationSchemes(),
            'circulation_statuses' => LibraryItem::getCirculationStatuses(),
            'cataloging_rules' => LibraryItem::getCatalogingRules(),
            'frequencies' => LibraryItem::getFrequencies(),
            'creator_roles' => array_map(fn($r) => $r['label'], self::ROLES),
            'subject_sources' => self::SUBJECT_SOURCES,
            'subject_types' => self::SUBJECT_TYPES,
        ];
    }

    /**
     * Get MARC relator code for role
     */
    public function getRelatorCode(string $role): ?string
    {
        return self::ROLES[$role]['code'] ?? null;
    }

    /**
     * Generate citation
     */
    public function generateCitation(LibraryItem $item, string $title, string $style = 'apa'): string
    {
        $author = $item->getPrimaryCreator() ?? '';
        $date = $item->publication_date ?? '';
        $publisher = $item->publisher ?? '';
        $place = $item->publication_place ?? '';

        switch ($style) {
            case 'mla':
                $parts = array_filter([
                    $author ? $author . '.' : null,
                    $title ? '<em>' . htmlspecialchars($title) . '</em>.' : null,
                    $publisher ? $publisher . ',' : null,
                    $date ? $date . '.' : null,
                ]);
                return implode(' ', $parts);

            case 'chicago':
                $parts = array_filter([
                    $author ? $author . '.' : null,
                    $title ? '<em>' . htmlspecialchars($title) . '</em>.' : null,
                    $place && $publisher ? $place . ': ' . $publisher . ',' : null,
                    $date ? $date . '.' : null,
                ]);
                return implode(' ', $parts);

            case 'apa':
            default:
                $parts = array_filter([
                    $author,
                    $date ? '(' . $date . ').' : null,
                    $title ? '<em>' . htmlspecialchars($title) . '</em>.' : null,
                    $place && $publisher ? $place . ': ' . $publisher . '.' : ($publisher ? $publisher . '.' : null),
                ]);
                return implode(' ', $parts);
        }
    }
}
