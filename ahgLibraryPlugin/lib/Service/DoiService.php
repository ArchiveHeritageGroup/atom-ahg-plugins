<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Service;

/**
 * DoiService — resolves DOIs via CrossRef and ISSNs via CrossRef / WorldCat SRU.
 *
 * Auto-detects identifier type:
 *   - DOI:    starts with "10."  (dx.doi.org / crossref.org)
 *   - ISSN:   8 digits, possibly with hyphen (e.g. 1234-5678)
 *   - ISBN:   10 or 13 digits
 *
 * CrossRef API: https://api.crossref.org/works/{doi}
 *   - Free, no auth required, ~50 req/s polite pool (email param recommended).
 *
 * @package ahgLibraryPlugin\Service
 */
class DoiService
{
    protected static ?DoiService $instance = null;

    protected string $email;    // polite pool email (CrossRef recommendation)
    protected bool $useCache = true;
    protected string $cacheDir;
    protected int $cacheTtl = 86400; // 24 hours

    public function __construct(?string $email = null)
    {
        $this->email = $email
            ?? \sfConfig::get('app_library_doi_email', 'johan@theahg.co.za');
        $this->cacheDir = \sfConfig::get('sf_cache_dir', '/tmp') . '/library/doi_cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public static function getInstance(?string $email = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($email);
        }
        return self::$instance;
    }

    // ========================================================================
    // Public entry point — auto-detect and resolve
    // ========================================================================

    /**
     * Resolve any recognised identifier (DOI / ISSN / ISBN).
     *
     * @param string $identifier  Raw input string
     * @return array{success: bool, type: string, identifier: string, data?: array, error?: string}
     */
    public function resolve(string $identifier): array
    {
        $identifier = trim($identifier);
        if (empty($identifier)) {
            return ['success' => false, 'type' => 'unknown', 'identifier' => '', 'error' => 'Empty identifier'];
        }

        $type = $this->detectType($identifier);

        return match ($type) {
            'doi'  => $this->resolveDoi($identifier),
            'issn' => $this->resolveIssn($identifier),
            'isbn' => $this->resolveIsbn($identifier),
            default => ['success' => false, 'type' => 'unknown', 'identifier' => $identifier, 'error' => 'Unrecognised identifier format'],
        };
    }

    // ========================================================================
    // Type detection
    // ========================================================================

    /**
     * Detect identifier type from string.
     *
     * @return string  doi | isbn | issn | unknown
     */
    public function detectType(string $input): string
    {
        $clean = $this->stripNonDigits($input);

        // DOI: starts with 10.
        if (preg_match('/^10\.\d{4,}\//', $input) || preg_match('/^10\.\d{4,}/', $input)) {
            return 'doi';
        }

        // ISSN: 8 digits with optional hyphen
        if (preg_match('/^\d{4}-\d{3}[\dX]$/', $input) || preg_match('/^\d{8}$/', $clean)) {
            return 'issn';
        }

        // ISBN-13: 13 digits
        if (preg_match('/^(978|979)\d{10}$/', $clean)) {
            return 'isbn';
        }

        // ISBN-10: 10 digits (last may be X)
        if (preg_match('/^\d{9}[\dX]$/', $clean) && strlen($clean) === 10) {
            return 'isbn';
        }

        return 'unknown';
    }

    // ========================================================================
    // DOI resolution (CrossRef)
    // ========================================================================

    /**
     * Resolve a DOI to metadata via CrossRef.
     *
     * @return array{success: bool, type: string, identifier: string, data?: array, error?: string}
     */
    public function resolveDoi(string $doi): array
    {
        $doi = $this->stripDoiPrefix($doi);

        // Check cache
        if ($this->useCache) {
            $cached = $this->getCachedResponse($doi);
            if ($cached !== null) {
                return $cached;
            }
        }

        $url = 'https://api.crossref.org/works/' . urlencode($doi);

        try {
            $response = $this->httpGet($url, [
                'User-Agent' => 'mailto:' . $this->email,
            ]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'type' => 'doi',
                'identifier' => $doi,
                'error' => 'CrossRef request failed: ' . $e->getMessage(),
            ];
        }

        if ($response === null) {
            return [
                'success' => false,
                'type' => 'doi',
                'identifier' => $doi,
                'error' => 'CrossRef returned no data',
            ];
        }

        $json = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($json['message'])) {
            return [
                'success' => false,
                'type' => 'doi',
                'identifier' => $doi,
                'error' => 'Invalid CrossRef response',
            ];
        }

        $work = $json['message'];
        $data = $this->parseDoiWork($work, $doi);

        $result = [
            'success' => true,
            'type' => 'doi',
            'identifier' => $doi,
            'data' => $data,
        ];

        if ($this->useCache) {
            $this->cacheResponse($doi, $result);
        }

        return $result;
    }

    /**
     * Parse a CrossRef /works response into a flat metadata array.
     */
    protected function parseDoiWork(array $work, string $doi): array
    {
        // Title
        $titles = $work['title'] ?? [];
        $title = $titles[0] ?? '';
        $subtitle = $titles[1] ?? '';

        // Authors
        $authors = [];
        foreach ($work['author'] ?? [] as $author) {
            $name = trim(($author['given'] ?? '') . ' ' . ($author['family'] ?? ''));
            $authors[] = ['name' => trim($name), 'type' => $author['type'] ?? 'person'];
        }

        // Publisher
        $publisher = $work['publisher'] ?? '';

        // Publication date
        $pubDate = '';
        foreach ($work['published-print']['date-parts'][0] ?? $work['published-online']['date-parts'][0] ?? [] as $part) {
            $pubDate = implode('-', array_map('str_pad', $part, [4, 2, 2], '0', STR_PAD_LEFT));
            break;
        }
        if (empty($pubDate)) {
            $pubDate = $work['created']['date-time'] ?? '';
            $pubDate = substr($pubDate, 0, 10);
        }

        // ISBNs (both ISBN-13 and ISBN-10 arrays)
        $isbn13 = $this->firstIsbn($work['ISBN-13'] ?? []);
        $isbn10 = $this->firstIsbn($work['ISBN-10'] ?? []);

        // ISSN (journal ISSN, not book)
        $issn = $work['ISSN'] ?? [];

        // Language
        $language = '';
        if (!empty($work['language'])) {
            $language = substr($work['language'], 0, 3);
        }

        // Description / abstract
        $description = '';
        if (!empty($work['abstract'])) {
            $description = strip_tags($work['abstract']);
        }

        // Subjects / keywords
        $subjects = [];
        foreach ($work['subject'] ?? [] as $subject) {
            $subjects[] = (string) $subject;
        }

        // DOI URL
        $doiUrl = 'https://doi.org/' . $doi;

        // Work type
        $workType = $work['type'] ?? '';

        // Page count
        $numberOfPages = '';
        if (!empty($work['page'])) {
            $numberOfPages = $work['page'];
        }

        // Volume / issue (for serials)
        $volume = $work['volume'] ?? '';
        $issue = $work['issue'] ?? '';

        // DOI prefix (publisher)
        $doiPrefix = '';
        if (preg_match('/^(10\.\d+)/', $doi, $m)) {
            $doiPrefix = $m[1];
        }

        return [
            'doi'           => $doi,
            'doi_url'       => $doiUrl,
            'title'         => $title,
            'subtitle'      => $subtitle,
            'authors'       => $authors,
            'publisher'     => $publisher,
            'publication_date' => $pubDate,
            'year'          => substr($pubDate, 0, 4),
            'isbn_13'       => $isbn13,
            'isbn_10'       => $isbn10,
            'issn'          => $issn[0] ?? '',
            'eissn'         => $work['ISSN-E'] ?? ($issn[1] ?? ''),
            'language'      => $language,
            'description'   => $description,
            'subjects'      => $subjects,
            'number_of_pages' => $numberOfPages,
            'volume'        => $volume,
            'issue'         => $issue,
            'work_type'     => $workType,
            'doi_prefix'    => $doiPrefix,
            'container_title' => $work['container-title'][0] ?? '',
            'url'           => $doiUrl,
            'source'        => 'crossref',
        ];
    }

    // ========================================================================
    // ISSN resolution (CrossRef journal lookup)
    // ========================================================================

    /**
     * Resolve an ISSN to journal metadata via CrossRef.
     *
     * @return array{success: bool, type: string, identifier: string, data?: array, error?: string}
     */
    public function resolveIssn(string $issn): array
    {
        $issn = $this->cleanIssn($issn);

        if ($this->useCache) {
            $cached = $this->getCachedResponse('issn:' . $issn);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Use CrossRef journals endpoint
        $url = 'https://api.crossref.org/journals/' . urlencode($issn);

        try {
            $response = $this->httpGet($url, [
                'User-Agent' => 'mailto:' . $this->email,
            ]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'type' => 'issn',
                'identifier' => $issn,
                'error' => 'CrossRef journal lookup failed: ' . $e->getMessage(),
            ];
        }

        if ($response === null) {
            return [
                'success' => false,
                'type' => 'issn',
                'identifier' => $issn,
                'error' => 'CrossRef returned no data for ISSN',
            ];
        }

        $json = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($json['message'])) {
            return [
                'success' => false,
                'type' => 'issn',
                'identifier' => $issn,
                'error' => 'Invalid CrossRef response for ISSN',
            ];
        }

        $journal = $json['message'];

        $data = [
            'issn' => $issn,
            'title' => $journal['title'] ?? '',
            'publisher' => $journal['publisher'] ?? '',
            'language' => $journal['language'] ?? '',
            'url' => $journal['URL'] ?? '',
            'subjects' => $journal['subjects'] ?? [],
            'source' => 'crossref',
        ];

        $result = [
            'success' => true,
            'type' => 'issn',
            'identifier' => $issn,
            'data' => $data,
        ];

        if ($this->useCache) {
            $this->cacheResponse('issn:' . $issn, $result);
        }

        return $result;
    }

    // ========================================================================
    // ISBN resolution (delegate to existing WorldCatService)
    // ========================================================================

    /**
     * Resolve ISBN by delegating to WorldCatService.
     *
     * @return array{success: bool, type: string, identifier: string, data?: array, error?: string}
     */
    public function resolveIsbn(string $isbn): array
    {
        try {
            $repo = new \ahgLibraryPlugin\Repository\IsbnLookupRepository();
            $svc  = new \ahgLibraryPlugin\Service\WorldCatService($repo);
            $result = $svc->lookup($isbn);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'type' => 'isbn',
                    'identifier' => $isbn,
                    'error' => $result['error'] ?? 'ISBN lookup failed',
                ];
            }

            return [
                'success' => true,
                'type' => 'isbn',
                'identifier' => $isbn,
                'source' => $result['source'] ?? 'unknown',
                'data' => $result['data'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'type' => 'isbn',
                'identifier' => $isbn,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ========================================================================
    // HTTP helper
    // ========================================================================

    /**
     * Perform a GET request.
     *
     * @throws \Exception on network error or HTTP 4xx/5xx
     */
    protected function httpGet(string $url, array $headers = []): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \Exception('curl_init failed');
        }

        $headerList = [];
        foreach ($headers as $key => $value) {
            $headerList[] = "$key: $value";
        }
        $headerList[] = 'Accept: application/json';
        $headerList[] = 'User-Agent: AHG-Heratio-Library/1.0 (mailto:' . $this->email . ')';

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headerList,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || !empty($error)) {
            throw new \Exception('curl error: ' . $error);
        }

        if ($code >= 400) {
            throw new \Exception("HTTP $code for $url");
        }

        return $body;
    }

    // ========================================================================
    // Cache helpers
    // ========================================================================

    protected function getCachedResponse(string $key): ?array
    {
        $path = $this->cacheFilePath($key);
        if (!file_exists($path)) {
            return null;
        }
        if (filemtime($path) + $this->cacheTtl < time()) {
            @unlink($path);
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }
        return json_decode($data, true);
    }

    protected function cacheResponse(string $key, array $data): void
    {
        $path = $this->cacheFilePath($key);
        @file_put_contents($path, json_encode($data), LOCK_EX);
    }

    protected function cacheFilePath(string $key): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-:]/', '_', $key);
        return $this->cacheDir . '/' . $safe . '.json';
    }

    // ========================================================================
    // String helpers
    // ========================================================================

    protected function stripDoiPrefix(string $doi): string
    {
        $doi = trim($doi);
        // Strip common URL prefixes
        $doi = preg_replace('#^https?://(?:dx\.)?doi\.org/#i', '', $doi);
        $doi = preg_replace('#^doi:#i', '', $doi);
        return trim($doi);
    }

    protected function cleanIssn(string $issn): string
    {
        return strtoupper(preg_replace('/[^0-9X]/', '', trim($issn)));
    }

    protected function stripNonDigits(string $input): string
    {
        return preg_replace('/[^0-9Xx]/', '', strtoupper($input));
    }

    protected function firstIsbn(array $isbns): string
    {
        foreach ($isbns as $isbn) {
            $clean = $this->stripNonDigits($isbn);
            if (strlen($clean) >= 10) {
                return $clean;
            }
        }
        return '';
    }
}
