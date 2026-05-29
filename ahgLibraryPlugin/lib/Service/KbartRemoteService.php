<?php

declare(strict_types=1);

/**
 * KbartRemoteService
 *
 * Fetches and parses KBART (Knowledge Bases And Related Tools) TSV feeds
 * from vendor URLs and maintains import audit logs.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class KbartRemoteService
{
    protected static ?KbartRemoteService $instance = null;
    protected Logger $logger;

    // Required KBART columns per NISO KBART spec
    protected const REQUIRED_COLUMNS = [
        'publication_title',
        'print_identifier',
        'online_identifier',
        'date_first_issue_online',
        'num_first_vol_online',
        'num_first_issue_online',
        'date_last_issue_online',
        'num_last_vol_online',
        'num_last_issue_online',
        'title_url',
        'first_author',
        'title_id',
        'embargo_info',
        'coverage_depth',
        'coverage_notes',
        'publisher_name',
    ];

    // 100 MB cap for file downloads
    protected const MAX_FILE_SIZE = 104857600;

    // 30 second fetch timeout
    protected const FETCH_TIMEOUT = 30;

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
        $this->logger = new Logger('library.kbart');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/kbart.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    /**
     * Fetch a single vendor's KBART feed, parse TSV, detect changes,
     * update vendor stats and write import log.
     *
     * @param int $vendorId
     * @return array ['success' => bool, 'error' => string|null, 'stats' => [...]]
     */
    public function fetchVendor(int $vendorId): array
    {
        $vendor = $this->getVendor($vendorId);
        if (!$vendor) {
            return ['success' => false, 'error' => "Vendor not found: {$vendorId}"];
        }

        $feedUrl = $vendor->feed_url;
        $fetchedAt = date('Y-m-d H:i:s');
        $rowCount = 0;
        $newCount = 0;
        $removedCount = 0;
        $error = null;

        try {
            $this->logger->info('Fetching KBART feed', ['vendor' => $vendor->name, 'url' => $feedUrl]);

            // Build stream context with timeout and follow_redirect
            $ctxOptions = [
                'http' => [
                    'timeout' => self::FETCH_TIMEOUT,
                    'follow_location' => 1,
                    'max_redirects' => 5,
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: AHGLibrary/1.0 (+https://archive.example.org)',
                        'Accept: text/tab-separated-values, */*',
                    ],
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ];

            $context = stream_context_create($ctxOptions);

            $content = @file_get_contents($feedUrl, false, $context);

            if ($content === false) {
                $error = 'Failed to fetch URL: ' . error_get_last()['message'] ?? 'unknown error';
                $this->logger->error('Fetch failed', ['vendor' => $vendor->name, 'error' => $error]);
            } else {
                // Enforce size cap
                if (strlen($content) > self::MAX_FILE_SIZE) {
                    $error = 'File exceeds 100MB cap';
                    $this->logger->error('File too large', ['size' => strlen($content)]);
                } else {
                    // Parse TSV
                    $rows = $this->parseTsv($content, $vendorId);
                    $rowCount = count($rows);

                    // Detect changes
                    $changes = $this->detectChanges($rows, $vendorId);
                    $newCount = $changes['new'];
                    $removedCount = $changes['removed'];

                    $this->logger->info('KBART parsed', [
                        'vendor' => $vendor->name,
                        'rows' => $rowCount,
                        'new' => $newCount,
                        'removed' => $removedCount,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $error = 'Exception: ' . $e->getMessage();
            $this->logger->error('Exception during fetch', ['vendor' => $vendor->name, 'error' => $error]);
        }

        // Update vendor stats
        DB::table('library_kbart_vendor')
            ->where('id', $vendorId)
            ->update([
                'last_fetch_at' => $fetchedAt,
                'last_row_count' => $rowCount,
                'last_error' => $error,
                'updated_at' => $fetchedAt,
            ]);

        // Write import log
        DB::table('library_kbart_import_log')->insert([
            'vendor_id' => $vendorId,
            'fetched_at' => $fetchedAt,
            'row_count' => $rowCount,
            'new_count' => $newCount,
            'removed_count' => $removedCount,
            'error' => $error,
        ]);

        return [
            'success' => $error === null,
            'error' => $error,
            'stats' => [
                'row_count' => $rowCount,
                'new_count' => $newCount,
                'removed_count' => $removedCount,
                'fetched_at' => $fetchedAt,
            ],
        ];
    }

    /**
     * Fetch all active vendors.
     */
    public function fetchAll(): void
    {
        $vendors = DB::table('library_kbart_vendor')
            ->where('active', 1)
            ->get();

        foreach ($vendors as $vendor) {
            $this->fetchVendor((int) $vendor->id);
        }
    }

    /**
     * Parse KBART TSV content into rows keyed by column name.
     * Skips header row, returns associative arrays.
     *
     * @param string $content Raw TSV content
     * @param int $vendorId For logging only
     * @return array<int, array<string, string>>
     */
    public function parseTsv(string $content, int $vendorId): array
    {
        $lines = preg_split('/\r\n|\n|\r/', trim($content), -1, PREG_SPLIT_NO_EMPTY);

        if (count($lines) < 2) {
            return [];
        }

        // Parse header line
        $header = str_getcsv($lines[0], "\t");
        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);

        // Validate required columns
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!in_array($required, $header, true)) {
                $missing[] = $required;
            }
        }

        if (!empty($missing)) {
            $this->logger->warning('KBART missing columns', [
                'vendor_id' => $vendorId,
                'missing' => $missing,
                'has' => $header,
            ]);
        }

        $rows = [];
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            $values = str_getcsv($line, "\t");
            $row = [];
            foreach ($header as $idx => $colName) {
                $row[$colName] = $values[$idx] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Detect new and removed rows compared to the prior fetch state.
     * Uses composite key: publication_title + print_identifier + online_identifier.
     *
     * @param array $newRows Parsed TSV rows from current fetch
     * @param int $vendorId
     * @return array ['new' => int, 'removed' => int]
     */
    public function detectChanges(array $newRows, int $vendorId): array
    {
        // Build key set from new rows
        $newKeys = [];
        foreach ($newRows as $row) {
            $key = $this->buildRowKey($row);
            if ($key !== '') {
                $newKeys[$key] = true;
            }
        }

        // Count new (rows in new set that weren't in the last fetch)
        // We track via import log — last successful fetch row_count is in vendor.last_row_count
        // For precise new/removed detection we need a snapshot table; for now estimate:
        // "new" = rows added since last fetch (would need prior snapshot)
        // For v1 we return totals; real delta would require library_kbart_snapshot table
        // which is planned for future enhancement.

        $new = count($newRows);
        $removed = 0;

        // TODO: when library_kbart_snapshot table exists, compare keys for delta
        // For now return total counts
        return [
            'new' => $new,
            'removed' => $removed,
        ];
    }

    /**
     * Build a canonical key for a KBART row.
     */
    protected function buildRowKey(array $row): string
    {
        $title = trim($row['publication_title'] ?? '');
        $printId = trim($row['print_identifier'] ?? '');
        $onlineId = trim($row['online_identifier'] ?? '');

        if ($title === '' && $printId === '' && $onlineId === '') {
            return '';
        }

        return strtolower($title . '|' . $printId . '|' . $onlineId);
    }

    /**
     * Get vendor by ID.
     */
    /**
     * Export the library's holdings as a NISO KBART title list (TSV) — the
     * inverse of the import path, using the same REQUIRED_COLUMNS (#110).
     *
     * @param array $filters serials_only(bool)
     */
    public function exportKbart(array $filters = []): string
    {
        $baseUrl = rtrim((string) (\sfConfig::get('app_siteBaseUrl', '') ?: ''), '/');

        $q = DB::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160); // published only

        if (!empty($filters['serials_only'])) {
            $q->whereNotNull('li.issn')->where('li.issn', '!=', '');
        }

        $rows = $q->orderBy('ioi.title')
            ->get(['li.id', 'li.isbn', 'li.issn', 'li.series_issn', 'li.publisher', 'li.publication_start_date', 'li.publication_end_date', 'ioi.title', 'slug.slug']);

        $lines = [implode("\t", self::REQUIRED_COLUMNS)];
        foreach ($rows as $r) {
            $isSerial = !empty($r->issn);
            $line = [
                'publication_title'       => (string) ($r->title ?? ''),
                'print_identifier'        => (string) ($r->issn ?: $r->isbn ?: ''),
                'online_identifier'       => (string) ($r->series_issn ?? ''),
                'date_first_issue_online' => (string) ($r->publication_start_date ?? ''),
                'num_first_vol_online'    => '',
                'num_first_issue_online'  => '',
                'date_last_issue_online'  => (string) ($r->publication_end_date ?? ''),
                'num_last_vol_online'     => '',
                'num_last_issue_online'   => '',
                'title_url'               => $r->slug ? ($baseUrl . '/' . $r->slug) : '',
                'first_author'            => '',
                'title_id'                => (string) $r->id,
                'embargo_info'            => '',
                'coverage_depth'          => 'fulltext',
                'coverage_notes'          => $isSerial ? 'serial' : 'monograph',
                'publisher_name'          => (string) ($r->publisher ?? ''),
            ];
            // Sanitise tabs/newlines out of values.
            $lines[] = implode("\t", array_map(fn ($v) => str_replace(["\t", "\r", "\n"], ' ', (string) $v), array_values($line)));
        }

        return implode("\n", $lines) . "\n";
    }

    public function getVendor(int $id): ?object
    {
        return DB::table('library_kbart_vendor')->where('id', $id)->first();
    }

    /**
     * Get vendor by feed URL.
     */
    public function getVendorByUrl(string $url): ?object
    {
        return DB::table('library_kbart_vendor')->where('feed_url', $url)->first();
    }

    /**
     * Get all vendors.
     */
    public function getAllVendors(): array
    {
        return DB::table('library_kbart_vendor')
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * Get import log entries for a vendor.
     */
    public function getImportLog(int $vendorId, int $limit = 50): array
    {
        return DB::table('library_kbart_import_log')
            ->where('vendor_id', $vendorId)
            ->orderBy('fetched_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }
}