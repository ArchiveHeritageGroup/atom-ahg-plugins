<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ExportService — exports library items to CSV, BibTeX, and RIS formats.
 *
 * @package ahgLibraryPlugin\Service
 */
class ExportService
{
    protected static ?ExportService $instance = null;
    protected string $culture;

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? (\sfContext::hasInstance()
            ? \sfContext::getInstance()->getUser()->getCulture()
            : 'en');
    }

    public static function getInstance(?string $culture = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($culture);
        }
        return self::$instance;
    }

    // ========================================================================
    // Public API
    // ========================================================================

    /**
     * Export library items to the requested format.
     *
     * @param string $format  csv | bibtex | ris
     * @param array $params   Search / filter params passed to LibraryRepository::search()
     * @param int $limit      Max rows (0 = unlimited)
     * @return array{content: string, filename: string, mime: string}
     */
    public function export(string $format, array $params = [], int $limit = 0): array
    {
        $items = $this->fetchItems($params, $limit);

        return match (strtolower($format)) {
            'csv'    => $this->toCsv($items),
            'bibtex' => $this->toBibtex($items),
            'ris'    => $this->toRis($items),
            default  => throw new \InvalidArgumentException("Unsupported export format: $format"),
        };
    }

    /**
     * Fetch raw library item rows for export.
     *
     * @return \Illuminate\Support\Collection
     */
    public function fetchItems(array $params = [], int $limit = 0)
    {
        $db = DB::connection();

        $query = $db->table('library_item as li')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('library_item_creator as lic', function ($j) {
                $j->on('li.id', '=', 'lic.library_item_id')
                  ->where('lic.is_primary', '=', 1);
            })
            ->leftJoin('object_term_relation as otl_subject', function ($j) {
                $j->on('io.id', '=', 'otl_subject.object_id')
                  ->where('otl_subject.type_id', '=', function ($sq) {
                      $sq->from('term', 't')
                         ->join('taxonomy as tx', 't.taxonomy_id', '=', 'tx.id')
                         ->where('tx.name', 'subjects')
                         ->select('t.id')
                         ->limit(1);
                  });
            })
            ->leftJoin('term as sub_term', 'otl_subject.term_id', '=', 'sub_term.id')
            ->leftJoin('term_i18n as sub_ti', 'sub_term.id', '=', 'sub_ti.id')
            ->where('io.source_standard', 'library')
            ->select([
                'ioi.title',
                'li.isbn',
                'li.issn',
                'li.doi',
                'li.lccn',
                'li.oclc_number',
                'li.barcode',
                'li.call_number',
                'li.dewey_decimal',
                'li.classification_number',
                'li.publisher',
                'li.publication_date',
                'li.publication_place',
                'li.edition_statement',
                'li.material_type',
                'li.language',
                'li.description',
                'li.pagination',
                'lic.name as primary_creator',
                DB::raw('GROUP_CONCAT(DISTINCT sub_ti.name ORDER BY sub_ti.name SEPARATOR "; ") as subjects'),
                'ioi.scope_and_content as scope_and_content',
                'io.access_restrictions as access_restrictions',
                'io.created_at as created_at',
                'io.updated_at as updated_at',
                's.slug',
            ])
            ->groupBy('li.id')
            ->orderBy('ioi.title');

        // Apply optional filters from $params
        if (!empty($params['material_type'])) {
            $query->where('li.material_type', $params['material_type']);
        }
        if (!empty($params['language'])) {
            $query->where('li.language', $params['language']);
        }
        if (!empty($params['publisher'])) {
            $query->where('li.publisher', 'LIKE', '%' . $params['publisher'] . '%');
        }
        if (!empty($params['date_from'])) {
            $query->where('li.publication_date', '>=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $query->where('li.publication_date', '<=', $params['date_to']);
        }
        if (!empty($params['search'])) {
            $term = $params['search'];
            $query->where(function ($q) use ($term) {
                $q->where('ioi.title', 'LIKE', "%{$term}%")
                  ->orWhere('li.isbn', 'LIKE', "%{$term}%")
                  ->orWhere('lic.name', 'LIKE', "%{$term}%");
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    // ========================================================================
    // CSV Export
    // ========================================================================

    public function toCsv(iterable $items): array
    {
        $headers = [
            'Title', 'Author(s)', 'ISBN', 'ISSN', 'DOI', 'LCCN', 'OCLC Number',
            'Publisher', 'Publication Date', 'Publication Place', 'Edition',
            'Material Type', 'Language', 'Call Number', 'Dewey Decimal',
            'Classification', 'Pagination', 'Subjects', 'Description',
            'Access Restrictions', 'URL', 'Created', 'Updated',
        ];

        $rows = [];
        foreach ($items as $item) {
            $item = is_array($item) ? (object) $item : $item;
            $slug = $item->slug ?? '';
            $url = $slug ? (\sfConfig::get('app_relative_url_root', '') . '/library/' . $slug) : '';

            $rows[] = [
                $this->escapeCsv($item->title ?? ''),
                $this->escapeCsv($item->primary_creator ?? ''),
                $this->escapeCsv($item->isbn ?? ''),
                $this->escapeCsv($item->issn ?? ''),
                $this->escapeCsv($item->doi ?? ''),
                $this->escapeCsv($item->lccn ?? ''),
                $this->escapeCsv($item->oclc_number ?? ''),
                $this->escapeCsv($item->publisher ?? ''),
                $this->escapeCsv($item->publication_date ?? ''),
                $this->escapeCsv($item->publication_place ?? ''),
                $this->escapeCsv($item->edition_statement ?? ''),
                $this->escapeCsv($item->material_type ?? ''),
                $this->escapeCsv($item->language ?? ''),
                $this->escapeCsv($item->call_number ?? ''),
                $this->escapeCsv($item->dewey_decimal ?? ''),
                $this->escapeCsv($item->classification_number ?? ''),
                $this->escapeCsv($item->pagination ?? ''),
                $this->escapeCsv($item->subjects ?? ''),
                $this->escapeCsv($item->description ?? ''),
                $this->escapeCsv($item->access_restrictions ?? ''),
                $this->escapeCsv($url),
                $this->escapeCsv($item->created_at ?? ''),
                $this->escapeCsv($item->updated_at ?? ''),
            ];
        }

        $lines = [];
        $lines[] = implode(',', $headers);
        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        $content = implode("\n", $lines);
        $filename = 'library_export_' . date('Y-m-d') . '.csv';

        return [
            'content'  => $content,
            'filename' => $filename,
            'mime'     => 'text/csv; charset=utf-8',
        ];
    }

    protected function escapeCsv(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    // ========================================================================
    // BibTeX Export
    // ========================================================================

    /**
     * Convert library items to BibTeX format.
     *
     * Entry type mapping:
     *   book              → book
     *   article / serial  → article
     *   thesis            → phdthesis / mastersthesis
     *   conference        → inproceedings
     *   video / film      → misc
     *   audio             → misc
     *   (default)          → misc
     */
    public function toBibtex(iterable $items): array
    {
        $entries = [];

        foreach ($items as $item) {
            $item = is_array($item) ? (object) $item : $item;
            $bibtex = $this->itemToBibtexEntry($item);
            if ($bibtex !== null) {
                $entries[] = $bibtex;
            }
        }

        $content = implode("\n\n", $entries);
        $filename = 'library_export_' . date('Y-m-d') . '.bib';

        return [
            'content'  => $content,
            'filename' => $filename,
            'mime'     => 'application/x-bibtex; charset=utf-8',
        ];
    }

    protected function itemToBibtexEntry(object $item): ?string
    {
        $title = trim($item->title ?? '');
        if (empty($title)) {
            return null;
        }

        $citeKey = $this->makeCiteKey($item);
        $type = $this->mapMaterialTypeToBibtex($item->material_type ?? '');

        $fields = [];

        $this->bibAdd($fields, 'title', $title);
        $this->bibAdd($fields, 'author', $item->primary_creator ?? '');
        $this->bibAdd($fields, 'publisher', $item->publisher ?? '');
        $this->bibAdd($fields, 'year', $item->publication_date ? substr($item->publication_date, 0, 4) : '');
        $this->bibAdd($fields, 'address', $item->publication_place ?? '');
        $this->bibAdd($fields, 'edition', $item->edition_statement ?? '');
        $this->bibAdd($fields, 'isbn', $item->isbn ?? '');
        $this->bibAdd($fields, 'issn', $item->issn ?? '');
        $this->bibAdd($fields, 'doi', $item->doi ?? '');
        $this->bibAdd($fields, 'lccn', $item->lccn ?? '');
        $this->bibAdd($fields, 'note', $item->description ?? '');

        if (!empty($item->pagination)) {
            $pages = $item->pagination;
            // Convert page count like "234" to "234" (BibTeX uses pages for ranges)
            $this->bibAdd($fields, 'pages', $pages);
        }

        // Optional: add URL
        $slug = $item->slug ?? '';
        if ($slug) {
            $url = (\sfConfig::get('app_relative_url_root', '') . '/library/' . $slug);
            $this->bibAdd($fields, 'url', $url);
        }

        // Build field lines
        $fieldLines = [];
        foreach ($fields as $key => $value) {
            if ($value !== '') {
                $fieldLines[] = "  $key = {" . $this->bibEscape($value) . "}";
            }
        }

        if (empty($fieldLines)) {
            return null;
        }

        return "@$type{$citeKey},\n" . implode(",\n", $fieldLines);
    }

    protected function bibAdd(array &$fields, string $key, string $value): void
    {
        $v = trim($value);
        if ($v !== '') {
            $fields[$key] = $v;
        }
    }

    protected function bibEscape(string $value): string
    {
        // Escape special BibTeX characters
        $value = str_replace(['&', '%', '#', '_'], ['\\&', '\\%', '\\#', '\\_'], $value);
        return $value;
    }

    protected function mapMaterialTypeToBibtex(?string $materialType): string
    {
        static $map = [
            'book'             => 'book',
            'ebook'            => 'book',
            'serial'           => 'article',
            'journal'          => 'article',
            'magazine'         => 'article',
            'thesis'           => 'phdthesis',
            'dissertation'     => 'phdthesis',
            'conference paper' => 'inproceedings',
            'conference'       => 'inproceedings',
            'video'            => 'misc',
            'film'             => 'misc',
            'dvd'              => 'misc',
            'audio'            => 'misc',
            'cd'               => 'misc',
            'map'              => 'misc',
        ];

        $key = strtolower(trim($materialType ?? ''));
        return $map[$key] ?? 'misc';
    }

    protected function makeCiteKey(object $item): string
    {
        $author = preg_replace('/[^a-zA-Z]/', '', $item->primary_creator ?? '');
        $author = $author ?: 'unknown';
        $author = strtolower(substr($author, 0, min(8, strlen($author))));

        $year = '';
        if (!empty($item->publication_date)) {
            preg_match('/\d{4}/', $item->publication_date, $m);
            $year = $m[0] ?? '';
        }

        $titleWord = '';
        if (!empty($item->title)) {
            $words = preg_split('/\s+/', $item->title);
            $titleWord = preg_replace('/[^a-zA-Z]/', '', $words[0] ?? '');
            $titleWord = strtolower(substr($titleWord, 0, 6));
        }

        return strtolower("{$author}{$year}{$titleWord}");
    }

    // ========================================================================
    // RIS Export
    // ========================================================================

    /**
     * Convert library items to RIS format.
     *
     * @link https://en.wikipedia.org/wiki/RIS_(file_format)
     */
    public function toRis(iterable $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $item = is_array($item) ? (object) $item : $item;
            $entryLines = $this->itemToRisLines($item);
            if (!empty($entryLines)) {
                $lines = array_merge($lines, $entryLines, ['ER  -']);
            }
        }

        $content = implode("\n", $lines) . "\n";
        $filename = 'library_export_' . date('Y-m-d') . '.ris';

        return [
            'content'  => $content,
            'filename' => $filename,
            'mime'     => 'application/x-research-info-systems; charset=utf-8',
        ];
    }

    protected function itemToRisLines(object $item): array
    {
        $title = trim($item->title ?? '');
        if (empty($title)) {
            return [];
        }

        $lines = [];

        // TY - Type
        $lines[] = 'TY  - ' . $this->mapMaterialTypeToRis($item->material_type ?? '');

        // TI / T1 - Title
        $lines[] = 'TI  - ' . $this->risEscape($title);

        // A1 - Author
        if (!empty($item->primary_creator)) {
            $lines[] = 'A1  - ' . $this->risEscape($item->primary_creator);
        }

        // PB - Publisher
        if (!empty($item->publisher)) {
            $lines[] = 'PB  - ' . $this->risEscape($item->publisher);
        }

        // PY - Publication year
        if (!empty($item->publication_date)) {
            preg_match('/\d{4}/', $item->publication_date, $m);
            $lines[] = 'PY  - ' . ($m[0] ?? substr($item->publication_date, 0, 4));
        }

        // PP - Place of publication
        if (!empty($item->publication_place)) {
            $lines[] = 'PP  - ' . $this->risEscape($item->publication_place);
        }

        // SN - ISBN / ISSN
        if (!empty($item->isbn)) {
            $lines[] = 'SN  - ' . $item->isbn;
        }
        if (!empty($item->issn)) {
            $lines[] = 'SN  - ' . $item->issn;
        }

        // DO - DOI
        if (!empty($item->doi)) {
            $lines[] = 'DO  - ' . $item->doi;
        }

        // UR - URL
        $slug = $item->slug ?? '';
        if ($slug) {
            $url = (\sfConfig::get('app_relative_url_root', '') . '/library/' . $slug);
            $lines[] = 'UR  - ' . $url;
        }

        // KW - Keywords / Subjects
        if (!empty($item->subjects)) {
            foreach (preg_split('/;\s*/', $item->subjects) as $subject) {
                $subject = trim($subject);
                if ($subject) {
                    $lines[] = 'KW  - ' . $this->risEscape($subject);
                }
            }
        }

        // AB - Abstract / Description
        if (!empty($item->description)) {
            $lines[] = 'AB  - ' . $this->risEscape($item->description);
        }

        // N1 - General notes
        if (!empty($item->scope_and_content)) {
            $lines[] = 'N1  - ' . $this->risEscape($item->scope_and_content);
        }

        // LA - Language
        if (!empty($item->language)) {
            $lines[] = 'LA  - ' . $item->language;
        }

        // ET - Edition
        if (!empty($item->edition_statement)) {
            $lines[] = 'ET  - ' . $this->risEscape($item->edition_statement);
        }

        // SP - Pagination (start page)
        if (!empty($item->pagination)) {
            $pages = $item->pagination;
            // If it's a single number, that's the total pages, not a range
            $lines[] = 'SP  - ' . $pages;
        }

        // CN - Call number
        if (!empty($item->call_number)) {
            $lines[] = 'CN  - ' . $item->call_number;
        }

        // Call number / classification
        if (!empty($item->dewey_decimal)) {
            $lines[] = 'CN  - ' . $item->dewey_decimal;
        }

        // ID - LCCN / OCLC identifiers
        if (!empty($item->lccn)) {
            $lines[] = 'ID  - LCCN: ' . $item->lccn;
        }
        if (!empty($item->oclc_number)) {
            $lines[] = 'ID  - OCLC: ' . $item->oclc_number;
        }

        // Y2 - Date added to library
        if (!empty($item->created_at)) {
            $lines[] = 'Y2  - ' . substr($item->created_at, 0, 10);
        }

        return $lines;
    }

    protected function mapMaterialTypeToRis(?string $materialType): string
    {
        static $map = [
            'book'             => 'BOOK',
            'ebook'            => 'EBOOK',
            'serial'           => 'JOUR',
            'journal'          => 'JOUR',
            'magazine'         => 'JOUR',
            'thesis'           => 'THES',
            'dissertation'     => 'THES',
            'conference paper' => 'CONF',
            'conference'       => 'CONF',
            'video'            => 'VIDEO',
            'film'             => 'VIDEO',
            'dvd'              => 'VIDEO',
            'audio'            => 'SOUND',
            'cd'               => 'SOUND',
            'map'              => 'MAP',
            'photograph'      => 'IMAGE',
        ];

        $key = strtolower(trim($materialType ?? ''));
        return $map[$key] ?? 'GEN';
    }

    protected function risEscape(string $value): string
    {
        // Remove newlines, trim
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }
}
