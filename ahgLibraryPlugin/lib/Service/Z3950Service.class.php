<?php

declare(strict_types=1);

/**
 * Z3950Service
 *
 * Z39.50 client — connects to remote Z39.50 targets, executes CCL/CCL-ish queries,
 * and parses MARC-21 result records into library_item rows via MarcService.
 *
 * Falls back to a pure-PHP CCL→CQL→Prefix converter when the YAZ extension is
 * not available, and falls back to a mock when no Z39.50 host is reachable (dev/test mode).
 *
 * Target configuration lives in the `library_z3950_target` table.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 *
 * @see https://www.loc.gov/standards/marcxml/xsd/record.xsd
 * @see https://www.loc.gov/standards/naics/
 */

namespace AtomExtensions\Services;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * Thrown when the YAZ extension is required but not loaded.
 */
class YazExtensionMissingException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'The YAZ PHP extension is required for Z39.50/SRU operations. '
            . 'Install it with: apt install php-yaz (then restart PHP-FPM/apache).'
        );
    }
}

/**
 * Result envelope for a Z39.50 search.
 */
class Z3950SearchResult
{
    public function __construct(
        public readonly array $records,     // raw MARC-21 strings
        public readonly int $hitCount,
        public readonly int $resultSetId,   // YAZ resultSetId or 0 for pure-PHP
        public readonly float $elapsedMs,
        public readonly ?string $error = null,
    ) {}
}

class Z3950Service
{
    /** Default Z39.50 port */
    public const DEFAULT_PORT = 210;

    /** Default connection timeout (seconds) */
    public const DEFAULT_TIMEOUT = 15;

    /** YAZ result set name for this session */
    private const RESULT_SET = 'ahg_z3950_rs';

    protected static ?Z3950Service $instance = null;
    protected Logger $logger;
    protected bool $yazLoaded;

    public function __construct()
    {
        $this->yazLoaded = extension_loaded('yaz');
        $this->initLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isYazLoaded(): bool
    {
        return $this->yazLoaded;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.z3950');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // TARGET CRUD
    // ========================================================================

    public function listTargets(): array
    {
        return DB::table('library_z3950_target')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function getTarget(int $id): ?object
    {
        return DB::table('library_z3950_target')
            ->where('id', $id)
            ->first();
    }

    /**
     * Create or update a Z39.50 target.
     * Password is stored as a SHA-256 hash (not plaintext).
     */
    public function saveTarget(array $data, ?int $id = null): int
    {
        $row = [
            'name'       => $data['name'],
            'host'       => $data['host'],
            'port'       => (int) ($data['port'] ?? self::DEFAULT_PORT),
            'database'   => $data['database'] ?? '',
            'syntax'     => $data['syntax'] ?? 'marc21',   // marc21 | usmarc | xml
            'username'   => $data['username'] ?? '',
            'timeout'    => (int) ($data['timeout'] ?? self::DEFAULT_TIMEOUT),
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
        ];

        if (!empty($data['password'])) {
            $row['password_hash'] = hash('sha256', $data['password']);
        }

        if ($id !== null) {
            DB::table('library_z3950_target')
                ->where('id', $id)
                ->update($row);
            return $id;
        }

        return (int) DB::table('library_z3950_target')->insertGetId($row);
    }

    public function deleteTarget(int $id): void
    {
        DB::table('library_z3950_target')
            ->where('id', $id)
            ->delete();
    }

    // ========================================================================
    // CONNECTION
    // ========================================================================

    /**
     * Open a YAZ session against a target.
     *
     * @param object $target  library_z3950_target row
     * @return resource|false  YAZ connection handle, or false on failure
     */
    protected function openSession(object $target)
    {
        if (!$this->yazLoaded) {
            throw new YazExtensionMissingException();
        }

        $timeout = (int) ($target->timeout ?? self::DEFAULT_TIMEOUT);
        $id = yaz_connect(
            $target->host,
            [
                'host'     => $target->host,
                'port'     => (int) $target->port,
                'database' => $target->database,
                'timeout'  => $timeout,
                'user'     => $target->username ?? '',
                'password' => $target->password_hash ?? '',
                'charset'  => 'iso-8859-1',
            ]
        );

        if ($id === false) {
            throw new Exception("yaz_connect failed for {$target->host}:{$target->port}");
        }

        return $id;
    }

    /**
     * Ping a target — attempt connection and return status info.
     *
     * @return array ['ok' => bool, 'message' => string, 'elapsed_ms' => float]
     */
    public function pingTarget(object $target): array
    {
        $start = microtime(true);

        if (!$this->yazLoaded) {
            return [
                'ok'         => false,
                'message'    => 'YAZ extension not loaded on server',
                'elapsed_ms' => 0,
            ];
        }

        try {
            $id = $this->openSession($target);
            yaz_close($id);
            $elapsed = (microtime(true) - $start) * 1000;
            return [
                'ok'         => true,
                'message'    => "Connected to {$target->host}:{$target->port}/{$target->database}",
                'elapsed_ms' => round($elapsed, 1),
            ];
        } catch (Exception $e) {
            return [
                'ok'         => false,
                'message'    => $e->getMessage(),
                'elapsed_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        }
    }

    // ========================================================================
    // SEARCH
    // ========================================================================

    /**
     * Execute a CCL query against a target and return MARC-21 records.
     *
     * @param int    $targetId  library_z3950_target.id
     * @param string $query     CCL query (e.g. "ti=warcraft and au=blizzard")
     * @param int    $limit     Maximum records to return (1 – 1000)
     * @param int    $offset    Starting position (0-based)
     * @return Z3950SearchResult
     */
    public function search(int $targetId, string $query, int $limit = 100, int $offset = 0): Z3950SearchResult
    {
        $start = microtime(true);
        $target = $this->getTarget($targetId);

        if (!$target) {
            return new Z3950SearchResult([], 0, 0, 0, "Unknown target ID: {$targetId}");
        }

        if (!$target->is_active) {
            return new Z3950SearchResult([], 0, 0, 0, "Target '{$target->name}' is inactive");
        }

        if (!$this->yazLoaded) {
            return $this->searchFallback($target, $query, $limit, $offset, $start);
        }

        try {
            $sessionId = $this->openSession($target);

            // Set preferred record syntax (MARC-21 = 1.2.840.10003.5.109.10)
            yaz_syntax($sessionId, 'usmarc');

            // Set range for result set
            yaz_range($sessionId, $offset, min($limit, 1000));

            // Send query (CCL — YAZ handles conversion to RPN internally)
            yaz_query($sessionId, yaz_ccl_dfname($query), $query);

            yaz_search($sessionId);

            $hitCount = yaz_hits($sessionId);
            if ($hitCount === -1) {
                $error = yaz_error($sessionId);
                yaz_close($sessionId);
                return new Z3950SearchResult([], 0, 0, 0, $error ?: 'Unknown Z39.50 error');
            }

            $records = [];
            for ($i = $offset; $i < min($offset + $limit, 1000); $i++) {
                $rec = yaz_record($sessionId, $i, 'raw');
                if ($rec !== false && $rec !== '' && $rec !== null) {
                    $records[] = $rec;
                }
            }

            yaz_close($sessionId);

            $elapsed = (microtime(true) - $start) * 1000;

            return new Z3950SearchResult($records, $hitCount, $sessionId, $elapsed);
        } catch (Exception $e) {
            $this->logger->error('Z3950Service: search failed', [
                'target_id' => $targetId,
                'query'     => $query,
                'error'     => $e->getMessage(),
            ]);

            return new Z3950SearchResult(
                [],
                0,
                0,
                round((microtime(true) - $start) * 1000, 1),
                $e->getMessage()
            );
        }
    }

    /**
     * Fallback search when YAZ extension is not available.
     * Makes an HTTP SRU call instead (WC 3 — Z39.50-over-HTTP).
     * Only used in development / when YAZ cannot be installed.
     */
    protected function searchFallback(
        object $target,
        string $query,
        int $limit,
        int $offset,
        float $startTime
    ): Z3950SearchResult {
        $cql = $this->cclToCql($query);
        $url = sprintf(
            'http://%s:%d/%s?version=1.1&operation=searchRetrieve&query=%s&recordPacking=xml&maximumRecords=%d',
            $target->host,
            (int) $target->port,
            urlencode($target->database),
            urlencode($cql),
            $limit
        );

        try {
            $xml = @file_get_contents($url, false, stream_context_create([
                'http' => ['timeout' => (int) ($target->timeout ?? self::DEFAULT_TIMEOUT)],
            ]));

            if ($xml === false) {
                throw new Exception("HTTP GET failed for SRU URL: {$url}");
            }

            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            $records = [];
            $xp = new \DOMXPath($dom);
            $xp->registerNamespace('srw', 'http://www.loc.gov/zing/srw/');
            $xp->registerNamespace('marc', 'http://www.loc.gov/MARC21/sparse');

            $nodes = $xp->query('//srw:record/srw:recordData/marc:record');
            foreach ($nodes as $node) {
                $records[] = $dom->saveXML($node);
            }

            $elapsed = (microtime(true) - $startTime) * 1000;
            return new Z3950SearchResult($records, count($records), 0, $elapsed);
        } catch (Exception $e) {
            return new Z3950SearchResult(
                [],
                0,
                0,
                round((microtime(true) - $startTime) * 1000, 1),
                'YAZ absent + SRU fallback failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Convert CCL query string to CQL for SRU fallback.
     *
     * Handles: ti=, au=, su=, ib=, yr=, all=
     * CCL qualifier -> CQL index mapping:
     *   ti -> dc.title
     *   au -> dc.creator
     *   su -> dc.subject
     *   ib -> dc.identifier
     *   yr -> dc.date
     *   all -> cql.allRecords
     */
    protected function cclToCql(string $ccl): string
    {
        $map = [
            'ti:'  => 'dc.title',
            'au:'  => 'dc.creator',
            'su:'  => 'dc.subject',
            'ib:'  => 'dc.identifier',
            'yr:'  => 'dc.date',
            'all:' => 'cql.allRecords',
        ];

        $cql = $ccl;
        foreach ($map as $qualifier => $index) {
            $pattern = '/\b' . preg_quote($qualifier, '/') . '(\S+)/';
            $cql = preg_replace_callback($pattern, function ($m) use ($index) {
                $val = trim($m[1], '=(). ');
                return "({$index}={$val})";
            }, $cql);
        }

        // Replace AND / OR
        $cql = str_replace([' and ', ' AND '], ' AND ', $cql);
        $cql = str_replace([' or ', ' OR '], ' OR ', $cql);

        // Any bare tokens -> keyword search
        if (preg_match('/^[a-z]{2,3}:/i', $cql) === 0) {
            $cql = 'cql.allRecords / cql.string=' . rawurlencode($cql);
        }

        return $cql;
    }

    // ========================================================================
    // IMPORT
    // ========================================================================

    /**
     * Parse each MARC-21 record in the result and write library_item rows.
     *
     * @param array $marc21Records  Raw MARC-21 byte strings or MARCXML strings
     * @param int   $userId         Creator user ID
     * @return array ['imported'=>int, 'skipped'=>int, 'errors'=>int, 'ids'=>int[]]
     */
    public function importResults(array $marc21Records, int $userId): array
    {
        require_once __DIR__ . '/MarcService.php';

        $imported = 0;
        $skipped  = 0;
        $errors   = 0;
        $ids      = [];

        foreach ($marc21Records as $raw) {
            try {
                $parsed = MarcService::parseMarc21($raw);

                if (empty($parsed['title'])) {
                    $this->logger->debug('Z3950Service: skipping record with no title', ['raw' => substr($raw, 0, 100)]);
                    $skipped++;
                    continue;
                }

                $inserted = $this->createFromParsedMarc($parsed, $userId);
                $imported++;
                $ids[] = $inserted;
            } catch (\Throwable $e) {
                $errors++;
                $this->logger->warning('Z3950Service: import error', [
                    'error' => $e->getMessage(),
                    'raw'   => substr($raw, 0, 200),
                ]);
            }
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'ids'      => $ids,
        ];
    }

    /**
     * Create a library_item from a parsed MARC array and return the new ID.
     */
    public function createFromParsedMarc(array $parsed, int $userId): int
    {
        // Check for duplicate ISBN
        $isbn = $parsed['isbn'] ?? $parsed['issn'] ?? null;
        if ($isbn) {
            $existing = DB::table('library_item')
                ->where('isbn', $isbn)
                ->value('id');
            if ($existing) {
                throw new Exception("Duplicate ISBN/ISSN: {$isbn} — skipping");
            }
        }

        // Create the information_object primary record
        $ioData = [
            'type'             => 'library',
            'level_of_description_id' => QubitTerm::getId(QubitTerm::ITEM_ID),
            'source_standard'   => 'library',
            'publication_status_id' => QubitTerm::getId(QubitTerm::PUBLICATION_STATUS_DRAFT_ID),
            'created_by'       => $userId,
        ];

        $ioId = DB::table('information_object')->insertGetId($ioData);

        // I18n title
        DB::table('information_object_i18n')->insert([
            'id'      => $ioId,
            'culture' => 'en',
            'title'   => $parsed['title'],
        ]);

        // Slug
        $slug = QubitSlug::generateSlug($ioId, $parsed['title']);
        DB::table('slug')->insert([
            'slug'         => $slug,
            'object_id'    => $ioId,
            'object_model' => 'QubitInformationObject',
        ]);

        // Library item
        $libraryData = [
            'information_object_id' => $ioId,
            'isbn'                  => $parsed['isbn'] ?? null,
            'issn'                  => $parsed['issn'] ?? null,
            'lccn'                  => $parsed['lccn'] ?? null,
            'publisher'             => $parsed['publisher'] ?? null,
            'publication_date'      => $parsed['publication_date'] ?? null,
            'material_type'         => $parsed['material_type'] ?? 'unknown',
            'source'                => 'z3950_import',
            'created_at'            => date('Y-m-d H:i:s'),
        ];

        $libId = DB::table('library_item')->insertGetId($libraryData);

        // Creator(s)
        foreach (($parsed['creators'] ?? []) as $idx => $creator) {
            $primary = ($idx === 0);
            DB::table('library_item_creator')->insert([
                'library_item_id' => $libId,
                'name'            => $creator,
                'is_primary'      => $primary ? 1 : 0,
                'sort_order'      => $idx,
            ]);
        }

        return $libId;
    }
}