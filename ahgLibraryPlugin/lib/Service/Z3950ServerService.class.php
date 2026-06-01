<?php

declare(strict_types=1);

/**
 * Z3950ServerService
 *
 * Z39.50 *server* implementation (ISO 23950) for PSIS / AtoM.
 *
 * PSIS already ships a Z39.50 *client* (Z3950Service) and an SRU-over-HTTP
 * server (SruService). This service adds the missing half: answering raw
 * binary Z39.50 client queries (Koha, Evergreen, VTLS, EndNote, RefWorks)
 * directly against the AtoM library catalogue.
 *
 * Handles: Init, Search, Present, Close, DeleteResultSet APDUs.
 *   - BER encoding/decoding via BerEncoder
 *   - Query: PQF (Prefix Query Format) parsed into Laravel QB constraints
 *   - Records: built as ISO 2709 MARC-21 from library_item rows
 *
 * The TCP socket loop lives in the z3950:server CLI command; this service
 * is transport-agnostic and operates on raw APDU byte strings.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 *
 * @see https://www.loc.gov/standards/sru/bib-1.html
 */

namespace AtomExtensions\Services;

use Illuminate\Database\Capsule\Manager as DB;

class Z3950ServerService
{
    private BerEncoder $encoder;

    /** @var array<string,array> In-memory result sets keyed by name */
    private array $resultSets = [];

    /** Server-side cap on a single result set (prevents memory blowups) */
    private int $maxResultSet = 1000;

    /** BIB-1 use attribute → logical catalogue index */
    private const USE_MAP = [
        '1'    => 'author',
        '4'    => 'title',
        '7'    => 'isbn',
        '8'    => 'issn',
        '21'   => 'subject',
        '1003' => 'author',
        '1016' => 'any',
        '1024' => 'any',
    ];

    /** BIB-1 relation attribute → SQL operator */
    private const REL_MAP = [
        '1' => '=',
        '2' => '<',
        '3' => '>',
        '4' => '<=',
        '5' => '>=',
        '6' => '<>',
    ];

    public function __construct(?BerEncoder $encoder = null)
    {
        $this->encoder = $encoder ?? new BerEncoder();
    }

    public function setMaxResultSet(int $max): void
    {
        $this->maxResultSet = max(1, $max);
    }

    // ──── APDU routing ─────────────────────────────────────────────────────

    /**
     * Route a raw Z39.50 package (with header) to the correct APDU handler.
     *
     * @return array{type:string, response:string, resultCount:?int, error:?string}
     */
    public function routePackage(string $packet): array
    {
        $apdu = $this->encoder->unwrapPackageHeader($packet);
        if ($apdu === '') {
            return ['type' => 'unknown', 'response' => '', 'resultCount' => null, 'error' => 'empty APDU'];
        }

        $type = $this->detectApduType($apdu);

        switch ($type) {
            case 'initRequest':
                return [
                    'type'        => 'init_request',
                    'response'    => $this->handleInit($apdu),
                    'resultCount' => null,
                    'error'       => null,
                ];
            case 'searchRequest':
                return $this->handleSearch($apdu);
            case 'presentRequest':
                return [
                    'type'        => 'present_request',
                    'response'    => $this->handlePresent($apdu),
                    'resultCount' => null,
                    'error'       => null,
                ];
            case 'close':
                return [
                    'type'        => 'close',
                    'response'    => $this->handleClose($apdu),
                    'resultCount' => null,
                    'error'       => null,
                ];
            case 'deleteResultSet':
                return [
                    'type'        => 'delete_result_set',
                    'response'    => $this->handleDeleteResultSet($apdu),
                    'resultCount' => null,
                    'error'       => null,
                ];
            default:
                return [
                    'type'        => 'unknown',
                    'response'    => $this->encoder->encodeClose(BerEncoder::REFERENCE_ID_PREFIX, 0),
                    'resultCount' => null,
                    'error'       => 'unsupported APDU type',
                ];
        }
    }

    /**
     * Detect the APDU type from raw BER bytes by inspecting the leading OID.
     */
    public function detectApduType(string $apdu): string
    {
        // Wire format (see BerEncoder::encode*Response): the APDU begins with the
        // OID SEQUENCE { OID } directly, followed by the body SEQUENCE. There is
        // no extra outer SEQUENCE wrapper.
        $len = strlen($apdu);
        if ($len < 6 || ord($apdu[0]) !== BerEncoder::TAG_SEQUENCE) {
            return 'unknown';
        }

        // Decode the OID SEQUENCE length, then the inner OID tag/length.
        [$seqLenBytes] = $this->encoder->decodeLengthRet($apdu, 1);
        $oidTagPos = 1 + $seqLenBytes;
        if ($oidTagPos >= $len || ord($apdu[$oidTagPos]) !== BerEncoder::TAG_OID) {
            return 'unknown';
        }

        [$oidLenBytes, $oidLen] = $this->encoder->decodeLengthRet($apdu, $oidTagPos + 1);
        $oidStart = $oidTagPos + 1 + $oidLenBytes;
        if ($oidStart + $oidLen > $len) {
            return 'unknown';
        }

        $oidBody = substr($apdu, $oidStart, $oidLen);
        $arcs = $this->encoder->decodeOidValue($oidBody);

        if (count($arcs) >= 7 && $arcs[0] === 1 && $arcs[1] === 2
            && $arcs[2] === 840 && $arcs[3] === 10003 && $arcs[4] === 9 && $arcs[5] === 100) {
            return match ($arcs[6] ?? 0) {
                1       => 'initRequest',
                2       => 'initResponse',
                6       => 'searchRequest',
                7       => 'searchResponse',
                13      => 'presentRequest',
                14      => 'presentResponse',
                19      => 'deleteResultSet',
                23      => 'close',
                default => 'unknown',
            };
        }

        return 'unknown';
    }

    // ──── APDU handlers ──────────────────────────────────────────────────────

    private function handleInit(string $apdu): string
    {
        $body = $this->extractApduBody($apdu);
        if ($body === '') {
            return $this->encoder->encodeInitResponse(BerEncoder::REFERENCE_ID_PREFIX);
        }

        $parsed = $this->encoder->decodeInitRequest($body);
        $referenceId = $parsed['referenceId'] !== '' ? $parsed['referenceId'] : BerEncoder::REFERENCE_ID_PREFIX;

        return $this->encoder->encodeInitResponse($referenceId);
    }

    /**
     * @return array{type:string, response:string, resultCount:?int, error:?string}
     */
    private function handleSearch(string $apdu): array
    {
        $body = $this->extractApduBody($apdu);
        $parsed = $this->encoder->decodeSearchRequest($body);

        $referenceId = $parsed['referenceId'] !== '' ? $parsed['referenceId'] : BerEncoder::REFERENCE_ID_PREFIX;
        $resultSetId = $parsed['resultSetName'] !== '' ? $parsed['resultSetName'] : 'default';
        $maxRecords  = $parsed['maxRecords'] > 0 ? $parsed['maxRecords'] : 10;
        $query       = $parsed['query'];

        $error = null;
        $count = 0;
        $records = '';

        try {
            $rows = $this->runCatalogueSearch($query, $this->maxResultSet);
            $count = count($rows);

            $encoded = [];
            foreach ($rows as $row) {
                $encoded[] = $this->buildMarc21($row);
            }
            // Store the full encoded set; Present slices it.
            $this->resultSets[$resultSetId] = [
                'referenceId' => $referenceId,
                'records'     => $encoded,
                'count'       => $count,
            ];

            // Return the first slice inline with the search response.
            $records = $this->packRecords(array_slice($encoded, 0, max(1, $maxRecords)));
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $response = $this->encoder->encodeSearchResponse(
            $referenceId,
            $count,
            $resultSetId,
            $records,
            $count + 1
        );

        return [
            'type'        => 'search_request',
            'response'    => $response,
            'resultCount' => $count,
            'error'       => $error,
        ];
    }

    private function handlePresent(string $apdu): string
    {
        $body = $this->extractApduBody($apdu);
        $parsed = $this->encoder->decodePresentRequest($body);

        $referenceId    = $parsed['referenceId'] !== '' ? $parsed['referenceId'] : BerEncoder::REFERENCE_ID_PREFIX;
        $resultSetId    = $parsed['resultSetId'] !== '' ? $parsed['resultSetId'] : 'default';
        $resultSetStart = max(1, (int) $parsed['resultSetStartPoint']);
        $maxRecords     = $parsed['maxRecords'] > 0 ? $parsed['maxRecords'] : 10;

        $set = $this->resultSets[$resultSetId] ?? null;
        if ($set === null) {
            // resultSetStatus 3 = result set does not exist
            return $this->encoder->encodePresentResponse($referenceId, 0, 0, '', 3);
        }

        $slice = array_slice($set['records'], $resultSetStart - 1, $maxRecords);
        $records = $this->packRecords($slice);
        $numberReturned = count($slice);
        $nextPosition = $resultSetStart + $numberReturned;
        if ($nextPosition > $set['count']) {
            $nextPosition = 0;
        }

        return $this->encoder->encodePresentResponse(
            $referenceId,
            $nextPosition,
            $numberReturned,
            $records
        );
    }

    private function handleClose(string $apdu): string
    {
        $body = $this->extractApduBody($apdu);

        $referenceId = BerEncoder::REFERENCE_ID_PREFIX;
        $closeStatus = 0;

        $pos = 0;
        $len = strlen($body);
        while ($pos < $len) {
            $tag = ord($body[$pos]);
            [$lenBytes, $valLen] = $this->encoder->decodeLengthRet($body, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($body, $valStart, $valLen);

            if ($tag === BerEncoder::TAG_OCTET && $referenceId === BerEncoder::REFERENCE_ID_PREFIX) {
                if ($value !== '') {
                    $referenceId = $value;
                }
            } elseif ($tag === BerEncoder::TAG_INTEGER) {
                $closeStatus = $this->encoder->decodeIntegerValue($value);
            }

            $pos = $valStart + $valLen;
        }

        return $this->encoder->encodeClose($referenceId, $closeStatus);
    }

    private function handleDeleteResultSet(string $apdu): string
    {
        $body = $this->extractApduBody($apdu);
        $parsed = $this->encoder->decodeSearchRequest($body);

        $referenceId = $parsed['referenceId'] !== '' ? $parsed['referenceId'] : BerEncoder::REFERENCE_ID_PREFIX;
        $resultSetId = $parsed['resultSetName'] !== '' ? $parsed['resultSetName'] : 'default';

        unset($this->resultSets[$resultSetId]);

        return $this->encoder->encodeDeleteResultSetResponse($referenceId, 0);
    }

    /**
     * Strip the outer SEQUENCE + OID SEQUENCE wrapper and return the inner
     * APDU body SEQUENCE content.
     */
    private function extractApduBody(string $apdu): string
    {
        // Skip the leading OID SEQUENCE { OID }, then return the content of the
        // following body SEQUENCE.
        $len = strlen($apdu);
        if ($len === 0 || ord($apdu[0]) !== BerEncoder::TAG_SEQUENCE) {
            return '';
        }

        [$seqLenBytes, $seqLen] = $this->encoder->decodeLengthRet($apdu, 1);
        $bodySeqStart = 1 + $seqLenBytes + $seqLen;
        if ($bodySeqStart >= $len || ord($apdu[$bodySeqStart]) !== BerEncoder::TAG_SEQUENCE) {
            return '';
        }

        [$bodyLenBytes, $bodyLen] = $this->encoder->decodeLengthRet($apdu, $bodySeqStart + 1);

        return substr($apdu, $bodySeqStart + 1 + $bodyLenBytes, $bodyLen);
    }

    /**
     * Wrap each MARC record with the Z39.50 record separators expected by the
     * BerEncoder counters (\x1e ... \x1e\x1d per record).
     */
    private function packRecords(array $marcRecords): string
    {
        $out = '';
        foreach ($marcRecords as $marc) {
            $out .= "\x1e" . $marc . "\x1e\x1d";
        }

        return $out;
    }

    // ──── PQF → Laravel QB ───────────────────────────────────────────────────

    /**
     * Run a catalogue search from a PQF query string.
     *
     * @return array<int,object> library_item rows enriched with title
     */
    public function runCatalogueSearch(string $pqf, int $limit): array
    {
        $clauses = $this->parsePqf($pqf);

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
            ->where('ioi.culture', 'en')
            ->select([
                'io.id as io_id',
                'ioi.title',
                'li.id',
                'li.isbn',
                'li.issn',
                'li.publisher',
                'li.publication_date',
                'li.material_type',
                'li.call_number',
                'li.language',
                'li.description',
            ]);

        foreach ($clauses as $clause) {
            $this->applyClause($query, $clause);
        }

        return $query->orderBy('ioi.title')->limit($limit)->get()->all();
    }

    /**
     * Parse a PQF query into a flat list of clauses.
     *
     * Supports the common subset emitted by Koha / yaz-client:
     *   @attr 1=4 "term"           (title)
     *   @and @attr 1=4 "a" @attr 1=1003 "b"
     *   @or  ...
     *   bare "term"                (any index)
     *
     * @return array<int,array{index:string, term:string, relation:string, boolean:string}>
     */
    public function parsePqf(string $pqf): array
    {
        $pqf = trim(preg_replace('/\s+/', ' ', $pqf) ?? '');
        if ($pqf === '') {
            return [];
        }

        $boolean = 'AND';
        if (str_starts_with($pqf, '@or ')) {
            $boolean = 'OR';
            $pqf = trim(substr($pqf, 4));
        } elseif (str_starts_with($pqf, '@and ')) {
            $boolean = 'AND';
            $pqf = trim(substr($pqf, 5));
        } elseif (str_starts_with($pqf, '@not ')) {
            $boolean = 'NOT';
            $pqf = trim(substr($pqf, 5));
        }

        $clauses = [];
        // Find each @attr group, otherwise treat the whole thing as a bare term.
        if (preg_match_all('/@attr\s+\d+\s*=\s*(\d+)\s+(?:@attr\s+\d+\s*=\s*(\d+)\s+)?(?:"([^"]*)"|(\S+))/', $pqf, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $useAttr  = $m[1];
                $relAttr  = $m[2] ?? '';
                $term     = $m[3] !== '' ? $m[3] : ($m[4] ?? '');
                $index    = self::USE_MAP[$useAttr] ?? 'any';
                $relation = ($relAttr !== '' && isset(self::REL_MAP[$relAttr])) ? self::REL_MAP[$relAttr] : '=';

                if ($term === '') {
                    continue;
                }

                $clauses[] = [
                    'index'    => $index,
                    'term'     => $term,
                    'relation' => $relation,
                    'boolean'  => $boolean,
                ];
                // Subsequent clauses keep the same top-level boolean.
            }
        }

        if (empty($clauses)) {
            // Bare term, any index.
            $term = trim($pqf, '"');
            if ($term !== '' && !str_starts_with($term, '@')) {
                $clauses[] = [
                    'index'    => 'any',
                    'term'     => $term,
                    'relation' => '=',
                    'boolean'  => 'AND',
                ];
            }
        }

        return $clauses;
    }

    private function applyClause($query, array $clause): void
    {
        $term = $clause['term'];
        // Right-truncation: term* → term%
        $truncated = false;
        if (str_ends_with($term, '*') || str_ends_with($term, '?')) {
            $term = rtrim($term, '*?');
            $truncated = true;
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
        $like = $truncated ? ($escaped . '%') : ('%' . $escaped . '%');
        $exact = ($clause['relation'] === '<>');

        $method = match ($clause['boolean']) {
            'OR'    => 'orWhere',
            default => 'where',
        };
        $negate = ($clause['boolean'] === 'NOT');

        $applyCol = function ($q) use ($clause, $like, $term, $negate, $exact) {
            $op = ($negate || $exact) ? 'NOT LIKE' : 'LIKE';

            switch ($clause['index']) {
                case 'title':
                    $q->where('ioi.title', $op, $like);
                    break;
                case 'isbn':
                    $q->where('li.isbn', $op, $like);
                    break;
                case 'issn':
                    $q->where('li.issn', $op, $like);
                    break;
                case 'author':
                    $q->whereExists(function ($sub) use ($like, $op) {
                        $sub->select(DB::raw(1))
                            ->from('library_item_creator as lic')
                            ->whereColumn('lic.library_item_id', 'li.id')
                            ->where('lic.name', $op === 'NOT LIKE' ? 'NOT LIKE' : 'LIKE', $like);
                    });
                    break;
                case 'subject':
                    $q->where('li.material_type', $op, $like);
                    break;
                default: // any
                    $q->where(function ($inner) use ($like, $op) {
                        if ($op === 'NOT LIKE') {
                            $inner->where('ioi.title', 'NOT LIKE', $like)
                                  ->where('li.isbn', 'NOT LIKE', $like)
                                  ->where('li.publisher', 'NOT LIKE', $like);
                        } else {
                            $inner->where('ioi.title', 'LIKE', $like)
                                  ->orWhere('li.isbn', 'LIKE', $like)
                                  ->orWhere('li.publisher', 'LIKE', $like);
                        }
                    });
                    break;
            }
        };

        $query->$method(function ($q) use ($applyCol) {
            $applyCol($q);
        });
    }

    // ──── MARC-21 record building (ISO 2709) ─────────────────────────────────

    /**
     * Build an ISO 2709 MARC-21 record from a catalogue row.
     */
    public function buildMarc21(object $row): string
    {
        $ioId  = (int) ($row->io_id ?? 0);
        $itemId = (int) ($row->id ?? 0);

        $authors = [];
        if ($itemId > 0) {
            $authors = DB::table('library_item_creator')
                ->where('library_item_id', $itemId)
                ->orderBy('id')
                ->limit(5)
                ->pluck('name')
                ->all();
        }

        $title = (string) ($row->title ?? '');
        $isbn  = (string) ($row->isbn ?? '');
        $issn  = (string) ($row->issn ?? '');
        $pub   = (string) ($row->publisher ?? '');
        $date  = (string) ($row->publication_date ?? '');
        $call  = (string) ($row->call_number ?? '');
        $desc  = (string) ($row->description ?? '');

        // Build variable fields: [tag, indicators, subfield-data]
        $fields = [];
        $fields[] = ['001', '', (string) $ioId];
        if ($isbn !== '') {
            $fields[] = ['020', '  ', $this->subfield('a', $isbn)];
        }
        if ($issn !== '') {
            $fields[] = ['022', '  ', $this->subfield('a', $issn)];
        }
        if (!empty($authors)) {
            $fields[] = ['100', '1 ', $this->subfield('a', (string) $authors[0])];
        }
        if ($title !== '') {
            $fields[] = ['245', '10', $this->subfield('a', $title)];
        }
        if ($pub !== '' || $date !== '') {
            $sub = '';
            if ($pub !== '') {
                $sub .= $this->subfield('b', $pub);
            }
            if ($date !== '') {
                $sub .= $this->subfield('c', $date);
            }
            $fields[] = ['260', '  ', $sub];
        }
        if ($call !== '') {
            $fields[] = ['050', '00', $this->subfield('a', $call)];
        }
        if ($desc !== '') {
            $fields[] = ['520', '  ', $this->subfield('a', $desc)];
        }
        foreach (array_slice($authors, 1) as $extra) {
            $fields[] = ['700', '1 ', $this->subfield('a', (string) $extra)];
        }

        return $this->assembleIso2709($fields);
    }

    private function subfield(string $code, string $value): string
    {
        // \x1f = subfield delimiter
        return "\x1f" . $code . $value;
    }

    /**
     * Assemble an ISO 2709 MARC record from variable fields.
     * Control fields (00X) carry no indicators; data fields prepend indicators.
     */
    private function assembleIso2709(array $fields): string
    {
        $fieldTerm  = "\x1e"; // field terminator
        $recordTerm = "\x1d"; // record terminator

        $directory = '';
        $data = '';
        $startPos = 0;

        foreach ($fields as [$tag, $indicators, $content]) {
            $isControl = str_starts_with($tag, '00');
            $fieldData = $isControl ? $content : $indicators . $content;
            $fieldData .= $fieldTerm;

            $len = strlen($fieldData);
            $directory .= str_pad($tag, 3, '0', STR_PAD_LEFT)
                . str_pad((string) $len, 4, '0', STR_PAD_LEFT)
                . str_pad((string) $startPos, 5, '0', STR_PAD_LEFT);
            $data .= $fieldData;
            $startPos += $len;
        }

        $directory .= $fieldTerm;
        $body = $directory . $data . $recordTerm;

        $baseAddress = 24 + strlen($directory);
        $recordLength = 24 + strlen($body);

        // Leader (24 bytes). Positions 0-4 length, 12-16 base address.
        $leader = str_pad((string) $recordLength, 5, '0', STR_PAD_LEFT)
            . 'nam a22'
            . str_pad((string) $baseAddress, 5, '0', STR_PAD_LEFT)
            . ' a 4500';
        // Ensure exactly 24 bytes.
        $leader = substr(str_pad($leader, 24), 0, 24);

        return $leader . $body;
    }

    // ──── Accessors (for testing / introspection) ───────────────────────────

    public function getResultSet(string $name): ?array
    {
        return $this->resultSets[$name] ?? null;
    }

    public function clearResultSets(): void
    {
        $this->resultSets = [];
    }
}
