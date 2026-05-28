<?php

declare(strict_types=1);

/**
 * SruService
 *
 * SRU (Search/Retrieve via URL) — HTTP endpoint at /api/sru.
 *
 * Operations supported:
 *   - searchRetrieve  (CQL query → XML result set)
 *   - explain         (server capability diagnostics)
 *
 * Auth: X-API-Key header, scope "sru".
 * The SRU result set is the Heratio library catalog reflected through the
 * information_object + library_item tables.
 *
 * SRU 1.1 / 1.2 compliant.
 * See: https://www.loc.gov/standards/sru/
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

namespace AtomExtensions\Services;

use Illuminate\Database\Capsule\Manager as DB;
use sfConfig;

class SruService
{
    /** SRU versions we implement */
    public const SUPPORTED_VERSIONS = ['1.1', '1.2'];

    public const DEFAULT_VERSION = '1.1';
    public const DEFAULT_RECORDS = 20;
    public const MAX_RECORDS     = 500;

    public const XML_NS   = 'http://www.loc.gov/zing/srw/';
    public const XML_DC   = 'info:srw/cql-context-set/1/dc-v1.1';
    public const XML_CQL  = 'info:srw/cql-context-set/1/cql-v1.1';

    // -----------------------------------------------------------------------
    // XML generation helpers
    // -----------------------------------------------------------------------

    protected function xmlHeader(string $version): string
    {
        $schema = $version >= '1.2'
            ? 'http://www.loc.gov/zing/srw/sru.xsd'
            : 'http://www.loc.gov/zing/srw/';
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<srw:searchRetrieveResponse xmlns:srw="{$this->XML_NS}"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/"
  xmlns:xcql="http://www.loc.gov/zing/cql/xcql/"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="{$schema}">

XML;
    }

    protected function xmlFooter(): string
    {
        return "</srw:searchRetrieveResponse>\n";
    }

    /**
     * Render a single SRU diagnostics diagnostic block.
     */
    protected function xmlDiagnostics(array $diagnostics): string
    {
        $out = "  <srw:diagnostics>\n";
        foreach ($diagnostics as $diag) {
            $id     = (int) ($diag['id'] ?? 1);
            $uri    = htmlspecialchars($diag['uri'] ?? "http://www.loc.gov/zing/srw/diagnostic/#{$id}");
            $detail = htmlspecialchars($diag['message'] ?? 'Unknown error');
            $out .= <<<XML
    <diag:diagnostic xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/">
      <diag:uri>{$uri}</diag:uri>
      <diag:message>{$detail}</diag:message>
    </diag:diagnostic>

XML;
        }
        $out .= "  </srw:diagnostics>\n";
        return $out;
    }

    /**
     * Render one SRU result record with the library item fields.
     */
    protected function xmlRecord(array $row, string $recordPacking, int $position): string
    {
        $pos = $position;
        if ($recordPacking === 'xml') {
            // Return record as MARCXML inside srw:recordData
            $id     = (int) ($row['id'] ?? $row['io_id'] ?? 0);
            $ioId   = (int) ($row['io_id'] ?? 0);
            $title  = htmlspecialchars($row['title'] ?? '');
            $creator = htmlspecialchars($row['creator'] ?? '');
            $pub   = htmlspecialchars($row['publisher'] ?? '');
            $date  = htmlspecialchars($row['publication_date'] ?? '');
            $isbn  = htmlspecialchars($row['isbn'] ?? '');
            $issn  = htmlspecialchars($row['issn'] ?? '');
            $mat   = htmlspecialchars($row['material_type'] ?? '');
            $call  = htmlspecialchars($row['call_number'] ?? '');
            $lang  = htmlspecialchars($row['language'] ?? '');
            $desc  = htmlspecialchars($row['description'] ?? '');

            $marxml = <<<XML
    <srw:record>
      <srw:recordPacking>xml</srw:recordPacking>
      <srw:recordSchema>info:srw/cql-context-set/1/marcxml-v1.1</srw:recordSchema>
      <srw:recordData>
        <record xmlns="http://www.loc.gov/MARC21/slim">
          <leader>00000cam a2200000 a 4500</leader>
          <controlfield tag="001">{$id}</controlfield>
          <controlfield tag="008">" . date('ymd') . str_pad($lang, 3, '   ') . "    " . str_repeat(' ', 9) . "d</controlfield>
          <datafield tag="245" ind1="0" ind2="0">
            <subfield code="a">{$title}</subfield>
          </datafield>
          <datafield tag="100" ind1="1" ind2="0">
            <subfield code="a">{$creator}</subfield>
          </datafield>
          <datafield tag="260" ind1=" " ind2=" ">
            <subfield code="b">{$pub}</subfield>
            <subfield code="c">{$date}</subfield>
          </datafield>
          <datafield tag="020" ind1=" " ind2=" ">
            <subfield code="a">{$isbn}</subfield>
          </datafield>
          <datafield tag="022" ind1=" " ind2=" ">
            <subfield code="a">{$issn}</subfield>
          </datafield>
          <datafield tag="050" ind1="0" ind2="0">
            <subfield code="a">{$call}</subfield>
          </datafield>
          <datafield tag="300" ind1=" " ind2=" ">
            <subfield code="a">{$mat}</subfield>
          </datafield>
          <datafield tag="520" ind1=" " ind2=" ">
            <subfield code="a">{$desc}</subfield>
          </datafield>
        </record>
      </srw:recordData>
    </srw:record>

XML;
            return $marxml;
        }

        // Default: SRU XML (dc: fields)
        $slug = htmlspecialchars($row['slug'] ?? '');
        $out = <<<XML
    <srw:record>
      <srw:recordPacking>xml</srw:recordPacking>
      <srw:recordSchema>info:srw/cql-context-set/1/dc-v1.1</srw:recordSchema>
      <srw:recordData>
        <dc:record xmlns:dc="http://purl.org/dc/elements/1.1/">
          <dc:title>{$title}</dc:title>
          <dc:creator>{$creator}</dc:creator>
          <dc:publisher>{$pub}</dc:publisher>
          <dc:date>{$date}</dc:date>
          <dc:identifier>urn:uuid:{$ioId}</dc:identifier>
          <dc:identifier>ISBN:{$isbn}</dc:identifier>
          <dc:identifier>ISSN:{$issn}</dc:identifier>
          <dc:language>{$lang}</dc:language>
          <dc:subject>{$mat}</dc:subject>
          <dc:description>{$desc}</dc:description>
          <dc:relation>slug:{$slug}</dc:relation>
        </dc:record>
      </srw:recordData>
    </srw:record>

XML;
        return $out;
    }

    // -----------------------------------------------------------------------
    // QUERY
    // -----------------------------------------------------------------------

    /**
     * Execute a CQL search and return an XML response string.
     *
     * @param array $params   normalised SRU parameters
     * @return string         XML response
     */
    public function searchRetrieve(array $params): string
    {
        $version        = $params['version']         ?? self::DEFAULT_VERSION;
        $query          = $params['query']           ?? '';
        $recordPacking  = $params['recordPacking']   ?? 'xml';
        $recordsPerPage = min((int) ($params['maximumRecords'] ?? self::DEFAULT_RECORDS), self::MAX_RECORDS);
        $startRecord    = max(1, (int) ($params['startRecord'] ?? 1));
        $sortKeys       = $params['sortKeys']        ?? '';

        $diagnostics = [];

        // Version check
        if (!in_array($version, self::SUPPORTED_VERSIONS, true)) {
            return $this->xmlHeader($version)
                . $this->xmlDiagnostics([[
                    'id'      => 8,   // unsupported version
                    'uri'     => 'http://www.loc.gov/zing/srw/diagnostic/',
                    'message' => "SRU version '{$version}' not supported. Use one of: " . implode(', ', self::SUPPORTED_VERSIONS),
                ]])
                . $this->xmlFooter();
        }

        // Query required
        if ($query === '') {
            return $this->xmlHeader($version)
                . $this->xmlDiagnostics([[
                    'id'      => 4,
                    'uri'     => 'http://www.loc.gov/zing/srw/diagnostic/',
                    'message' => 'CQL query is required',
                ]])
                . $this->xmlFooter();
        }

        // Resolve CQL sort keys
        $orderBy = '';
        if ($sortKeys !== '') {
            $orderBy = $this->resolveSortKeys($sortKeys);
        }

        // Execute search
        try {
            $db = DB::connection();
            $results = $db->table('information_object as io')
                ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
                ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
                ->where('ioi.culture', 'en')
                ->where('io.source_standard', 'library')
                ->when($query !== '' && $query !== 'cql.allRecords', function ($q) use ($query) {
                    $this->applyCqlFilter($q, $query);
                })
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

            $total = (clone $results)->count();

            $rows = $db->table('information_object as io')
                ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
                ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->leftJoin('library_item_creator as lic', function ($j) {
                    $j->on('li.id', '=', 'lic.library_item_id')
                      ->where('lic.is_primary', '=', 1);
                })
                ->where('ioi.culture', 'en')
                ->where('io.source_standard', 'library')
                ->select([
                    'io.id as io_id',
                    'ioi.title',
                    's.slug',
                    'li.id',
                    'li.isbn',
                    'li.issn',
                    'li.publisher',
                    'li.publication_date',
                    'li.material_type',
                    'li.call_number',
                    'li.language',
                    'li.description',
                    'lic.name as creator',
                ])
                ->when($query !== '' && $query !== 'cql.allRecords', function ($q) use ($query) {
                    $this->applyCqlFilter($q, $query);
                })
                ->when($orderBy !== '', function ($q) use ($orderBy) {
                    $q->orderByRaw($orderBy);
                }, function ($q) {
                    $q->orderBy('ioi.title');
                })
                ->offset($startRecord - 1)
                ->limit($recordsPerPage)
                ->get()
                ->all();
        } catch (\Throwable $e) {
            return $this->xmlHeader($version)
                . $this->xmlDiagnostics([[
                    'id'      => 10,  // system error
                    'uri'     => 'http://www.loc.gov/zing/srw/diagnostic/',
                    'message' => 'Database error: ' . $e->getMessage(),
                ]])
                . $this->xmlFooter();
        }

        // Build response
        $header = $this->xmlHeader($version);

        $next = '';
        if ($startRecord + $recordsPerPage - 1 < $total) {
            $next = (string) ($startRecord + $recordsPerPage);
        }
        $previous = $startRecord > 1
            ? (string) max(1, $startRecord - $recordsPerPage)
            : '';

        $respData = <<<XML
  <srw:version>{$version}</srw:version>
  <srw:numberOfRecords>{$total}</srw:numberOfRecords>
  <srw:resultSetIdleTime>300</srw:resultSetIdleTime>
  <srw:records>
XML;

        $recordsXml = '';
        $pos = $startRecord;
        foreach ($rows as $row) {
            $r = is_array($row) ? $row : (array) $row;
            $recordsXml .= $this->xmlRecord($r, $recordPacking, $pos);
            $pos++;
        }

        $escQuery = htmlspecialchars($query);

        $extraRequestData = '';
        if ($sortKeys !== '') {
            $escSort = htmlspecialchars($sortKeys);
            $extraRequestData .= "    <srw:extraRequestData>\n      <srw:sortKeys>{$escSort}</srw:sortKeys>\n    </srw:extraRequestData>\n";
        }

        return $header . $respData . $recordsXml . <<<XML
  </srw:records>
  <srw:echoedSearchRetrieveRequest>
    <srw:version>{$version}</srw:version>
    <srw:query>{$escQuery}</srw:query>
    <srw:recordPacking>{$recordPacking}</srw:recordPacking>
    <srw:maximumRecords>{$recordsPerPage}</srw:maximumRecords>
    <srw:startRecord>{$startRecord}</srw:startRecord>
    {$next}
    {$previous}
    {$extraRequestData}
  </srw:echoedSearchRetrieveRequest>
  {$this->xmlFooter()}
XML;
    }

    /**
     * Return an SRU explainresponse — server capability document.
     */
    public function explain(): string
    {
        $version = self::DEFAULT_VERSION;
        $host = htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost');
        $port  = htmlspecialchars((string)($_SERVER['SERVER_PORT'] ?? 443));

        $xml = <<<'SRU_XML'
<?xml version="1.0" encoding="UTF-8"?>
<srw:explainResponse xmlns:srw="http://www.loc.gov/zing/srw/">
  <srw:version>1.1</srw:version>
  <srw:record>
    <srw:recordSchema>http://www.loc.gov/standards/sru/specs/explain.xsd</srw:recordSchema>
    <srw:recordData>
      <explain xmlns="http://www.loc.gov/zing/srw/">
        <serverInfo>
          <host>' . $host . '</host>
          <port>' . $port . '</port>
          <database>library</database>
        </serverInfo>
        <schemaInfo>
          <schema identifier="info:srw/cql-context-set/1/dc-v1.1" name="dc" title="Dublin Core">
            <title>Dublin Core</title>
          </schema>
          <schema identifier="info:srw/cql-context-set/1/cql-v1.1" name="cql" title="CQL">
            <title>Common Query Language</title>
          </schema>
        </schemaInfo>
        <indexInfo>
          <index field="dc.title" title="Title">
            <maps><map><name set="dc">title</name></map></maps>
          </index>
          <index field="dc.creator" title="Creator / Author">
            <maps><map><name set="dc">creator</name></map></maps>
          </index>
          <index field="dc.subject" title="Subject">
            <maps><map><name set="dc">subject</name></map></maps>
          </index>
          <index field="dc.identifier" title="Identifier (ISBN/ISSN)">
            <maps><map><name set="dc">identifier</name></map></maps>
          </index>
          <index field="dc.publisher" title="Publisher">
            <maps><map><name set="dc">publisher</name></map></maps>
          </index>
          <index field="dc.date" title="Publication Date">
            <maps><map><name set="dc">date</name></map></maps>
          </index>
        </indexInfo>
        <configInfo>
          <default>
            <maximumRecords>20</maximumRecords>
            <recordPacking>xml</recordPacking>
          </default>
        </configInfo>
      </explain>
    </srw:recordData>
  </srw:record>
</srw:explainResponse>
SRU_XML;

        return $xml;
    }

    // -----------------------------------------------------------------------
    // CQL FILTER BUILDER
    // -----------------------------------------------------------------------

    /**
     * Apply a CQL query to a query builder instance.
     * Supports:
     *   - dc.title / dc.creator / dc.subject / dc.identifier / dc.publisher / dc.date
     *   - Relational operators: =, <>, <, <=, >, >=
     *   - Boolean: AND / OR (parenthesised groups)
     *   - Sorting: sortKeys=cql.sortKeys "dc.title ascending"
     */
    protected function applyCqlFilter($q, string $cql): void
    {
        $cql = trim($cql);

        // Strip outer parentheses if fully wrapped
        while (str_starts_with($cql, '(') && str_ends_with($cql, ')')) {
            $cql = substr($cql, 1, -1);
        }

        // Split on top-level AND / OR (simplified — no nested parens)
        $parts = $this->splitCqlTokens($cql);
        foreach ($parts as $part) {
            $part = trim($part);
            if (in_array(mb_strtoupper($part), ['AND', 'OR'], true)) {
                continue;
            }

            $op = 'LIKE';
            $val = $part;

            // Detect relational operator
            if (preg_match('/^(dc\.[a-z]+)\s*(<?>=?)\s*(["\'](.*?)["\']|(.*?))$/', $part, $m)) {
                $field = $m[1];
                $op    = $m[2];
                $val   = trim($m[4] . $m[5]);

                if (in_array($op, ['<>', '!='])) {
                    $op = 'NOT LIKE';
                } elseif (in_array($op, ['<', '<='], true)) {
                    $op = '<';
                } elseif (in_array($op, ['>', '>='], true)) {
                    $op = '>';
                } else {
                    $op = 'LIKE';
                }
            } elseif (preg_match('/^(dc\.[a-z]+)\s+(.*)$/', $part, $m)) {
                $field = $m[1];
                $val   = trim($m[2]);
            } else {
                // No qualifier — free-text search across title + creator
                $q->where(function ($inner) use ($val) {
                    $v = '%' . $val . '%';
                    $inner->where('ioi.title', 'LIKE', $v)
                          ->orWhere('lic.name', 'LIKE', $v);
                });
                continue;
            }

            // Map CQL index → DB column
            $col = match ($field) {
                'dc.title'      => 'ioi.title',
                'dc.creator'    => 'lic.name',
                'dc.subject'    => 'li.material_type',
                'dc.identifier' => 'li.isbn',
                'dc.publisher'  => 'li.publisher',
                'dc.date'       => 'li.publication_date',
                default         => 'ioi.title',
            };

            if ($op === 'LIKE') {
                $q->where($col, 'LIKE', '%' . $val . '%');
            } elseif ($op === 'NOT LIKE') {
                $q->where(function ($inner) use ($col, $val) {
                    $inner->where($col, 'NOT LIKE', '%' . $val . '%')
                          ->orWhereNull($col);
                });
            } else {
                $q->where($col, $op, $val);
            }
        }
    }

    /**
     * Split a CQL string on AND/OR boundaries (assumes no nested parens in the top-level token).
     */
    protected function splitCqlTokens(string $cql): array
    {
        $tokens = preg_split(
            '/\s+(AND|OR)\s+/i',
            $cql,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // Collapse AND/OR markers back with surrounding parts
        $segments = [];
        $current  = '';
        foreach ($tokens as $token) {
            if (in_array(mb_strtoupper(trim($token)), ['AND', 'OR'], true)) {
                if ($current !== '') {
                    $segments[] = trim($current);
                    $current = '';
                }
                $segments[] = trim($token);
            } else {
                $current .= ' ' . $token;
            }
        }
        if ($current !== '') {
            $segments[] = trim($current);
        }
        return $segments;
    }

    /**
     * Map CQL sortKeys value to SQL ORDER BY clause.
     * Handles: dc.title [ascending/descending], dc.date [asc/desc].
     */
    protected function resolveSortKeys(string $sortKeys): string
    {
        $sortKeys = trim($sortKeys);
        if ($sortKeys === '') {
            return '';
        }

        // Extract field + direction from "dc.title ascending"
        // Handles quoted or unquoted values
        if (preg_match('/^(dc\.[a-z]+)\s*(ascending|descending|asc|desc)?/i', $sortKeys, $m)) {
            $field = $m[1];
            $dir   = mb_strtoupper($m[2] ?? 'ASC');

            $col = match ($field) {
                'dc.title'   => 'ioi.title',
                'dc.date'    => 'li.publication_date',
                'dc.creator' => 'lic.name',
                default      => 'ioi.title',
            };

            return "{$col} {$dir}";
        }

        return '';
    }
}
