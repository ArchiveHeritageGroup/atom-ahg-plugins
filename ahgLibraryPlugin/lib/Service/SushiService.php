<?php

declare(strict_types=1);

/**
 * SushiService
 *
 * SUSHI 5.0 endpoint logic - validates COUNTER/SUSHI headers,
 * routes report requests to LibraryCounterService, returns JSON or XML.
 *
 * @package ahgLibraryPlugin
 * @subpackage Service
 *
 * @see https://www.niso.org/standards-committees/sushi
 * @see https://opencounterets.github.io/SUSHI/getting-started.html
 */

namespace AtomExtensions\Services;

use Exception;
use sfConfig;

class SushiService
{
    // SUSHI error codes
    public const E_NO_USAGE        = 1000;
    public const E_TIMEOUT         = 2000;
    public const E_SYNTAX          = 3000;
    public const E_DATE_RANGE      = 4000;
    public const E_LIMIT_EXCEEDED  = 5000;
    public const E_AUTH_FAILED     = 6000;
    public const E_INTERNAL        = 9000;

    protected ?LibraryCounterService $counter;

    public function __construct(?LibraryCounterService $counter = null)
    {
        $this->counter = $counter;
    }

    protected function counter(): LibraryCounterService
    {
        if ($this->counter === null) {
            $this->counter = new LibraryCounterService();
        }
        return $this->counter;
    }

    // ── Request / Response ─────────────────────────────────────────────────────

    /**
     * Harvest a COUNTER R5 report.
     *
     * @param string $reportType   TR_J1 | DR | PR | IR | TR_J3
     * @param string $begin       Start date YYYY-MM-DD
     * @param string $end         End date YYYY-MM-DD
     * @param string $format      'json' | 'xml'
     * @return array ['status'=>int, 'body'=>string, 'content_type'=>string]
     */
    public function harvest(string $reportType, string $begin, string $end, string $format = 'json'): array
    {
        // Validate date range
        if (!$this->validateDateRange($begin, $end)) {
            return $this->sushiError(self::E_DATE_RANGE, 'Invalid or out-of-range date', $format);
        }

        // Report type filter (match COUNTER R5 supported reports)
        $allowed = ['TR_J1', 'DR', 'PR', 'IR', 'TR_J3'];
        if (!in_array($reportType, $allowed, true)) {
            return $this->sushiError(self::E_SYNTAX, "Unsupported report type: $reportType", $format);
        }

        $svc = new LibraryCounterService($begin, $end);

        $records = match ($reportType) {
            'TR_J1' => $svc->TR_J1(),
            'TR_J3' => $svc->TR_J3(),
            'DR'    => $svc->DR(),
            'PR'    => $svc->PR(),
            'IR'    => $svc->IR(),
            default => [],
        };

        $filtered = $svc instanceof LibraryCounterService ? $svc::filterForSUSHI($records) : $records;
        $filtered = array_filter($records, fn($r) => $this->hasMetrics($r));

        if (empty($filtered)) {
            return $this->sushiError(self::E_NO_USAGE, 'No usage data for the requested period', $format);
        }

        $body = $svc->toJson($reportType, $records);

        if ($format === 'xml') {
            $body = $this->toSushiXml($reportType, $records);
            return [
                'status'       => 200,
                'body'         => $body,
                'content_type' => 'application/xml;charset="UTF-8"',
            ];
        }

        return [
            'status'       => 200,
            'body'         => $body,
            'content_type' => 'application/json;charset="UTF-8"',
        ];
    }

    /**
     * Validate a COUNTER/SUSHI request (check API key / customer / requestor).
     *
     * @param array $headers  Key => Value of relevant HTTP headers
     * @return array ['valid'=>bool, 'code'=>int, 'message'=>string]
     */
    public function validateRequest(array $headers): array
    {
        // SUSHI 5.0 expects these three headers to be present
        $required = [
            'Requestor-Id'  => 'X-Requestor-Id',
            'Customer-Id'   => 'X-Customer-Id',
            'API-Key'       => 'X-API-Key',
        ];

        foreach ($required as $key => $headerName) {
            $value = $headers[$headerName]
                ?? $headers[$key]
                ?? $headers[strtolower($headerName)]
                ?? $headers[strtolower(str_replace('X-', '', $headerName))]
                ?? null;

            if (empty($value)) {
                return [
                    'valid'  => false,
                    'code'   => self::E_SYNTAX,
                    'message' => "Missing required SUSHI header: $key",
                ];
            }
        }

        $apiKey = $headers['X-API-Key'] ?? $headers['api_key'] ?? null;

        // Compare against configured key (null means not yet set - allow for initial setup)
        $storedKey = LibraryCounterService::getSetting('sushi_api_key');
        if ($storedKey !== null && $storedKey !== '' && $apiKey !== $storedKey) {
            return [
                'valid'  => false,
                'code'   => self::E_AUTH_FAILED,
                'message' => 'Invalid API key',
            ];
        }

        return ['valid' => true, 'code' => 0, 'message' => ''];
    }

    /**
     * Test connectivity to the configured SUSHI endpoint.
     * Performs a dry-run harvest of TR_J1 for the current month.
     *
     * @param array $settings  Override settings for the test (optional)
     * @return array ['ok'=>bool, 'message'=>string, 'details'=>array]
     */
    public function testConnection(array $settings = []): array
    {
        // Allow test with provided settings or stored settings
        $sushiUrl = $settings['sushi_url'] ?? LibraryCounterService::getSetting('sushi_url');
        $apiKey   = $settings['sushi_api_key'] ?? LibraryCounterService::getSetting('sushi_api_key');
        $requestorId = $settings['sushi_requestor_id'] ?? LibraryCounterService::getSetting('sushi_requestor_id');
        $customerId  = $settings['sushi_customer_id'] ?? LibraryCounterService::getSetting('sushi_customer_id');

        if (empty($sushiUrl)) {
            return ['ok' => false, 'message' => 'SUSHI endpoint URL is not configured.'];
        }

        // Build test request
        $begin = date('Y-m-01');
        $end   = date('Y-m-d');
        $url   = rtrim($sushiUrl, '/') . '/counter5';
        $params = http_build_query([
            'report_type' => 'TR_J1',
            'begin_date' => $begin,
            'end_date'   => $end,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url . '?' . $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'X-Requestor-Id: ' . ($requestorId ?? 'test'),
                'X-Customer-Id: ' . ($customerId ?? 'test'),
                'X-API-Key: ' . ($apiKey ?? ''),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'ok'       => false,
                'message'  => "cURL error: $curlError",
                'details'  => ['url' => $url, 'http_code' => 0],
            ];
        }

        if ($httpCode === 0) {
            return [
                'ok'       => false,
                'message'  => "Could not connect to $sushiUrl",
                'details'  => ['url' => $url, 'http_code' => 0],
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'ok'       => true,
                'message'  => "Connected successfully (HTTP $httpCode)",
                'details'  => ['url' => $url, 'http_code' => $httpCode, 'response_preview' => substr($response, 0, 200)],
            ];
        }

        // Parse error from response body
        $errorBody = json_decode($response, true);
        $errorMsg  = $errorBody['api_exception']['Message'] ?? $errorBody['message'] ?? "HTTP $httpCode";

        return [
            'ok'       => false,
            'message'  => "SUSHI endpoint returned $httpCode: $errorMsg",
            'details'  => ['url' => $url, 'http_code' => $httpCode, 'response' => substr($response, 0, 400)],
        ];
    }

    // ── Error Responses ───────────────────────────────────────────────────────

    /**
     * Build a SUSHI error in JSON or XML format.
     */
    public function sushiError(int $code, string $message, string $format = 'json'): array
    {
        $id     = $code > 9000 ? 'InternalError' : 'APIException';
        $number = (string) $code;

        if ($format === 'xml') {
            $body = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Body>
    <SushiException xmlns="http://www.niso.org/sushi/errors">
      <Code>{$number}</Code>
      <Message>{$this->escapeXml($message)}</Message>
      <Description>SUSHI error code {$code}</Description>
    </SushiException>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
            return [
                'status'       => $this->httpStatusFor($code),
                'body'         => $body,
                'content_type' => 'application/xml;charset="UTF-8"',
            ];
        }

        // W3C Entity-Tei JSON
        $body = json_encode([
            'api_exception' => [
                '@context' => 'http://refubium.fu-berlin.de/SushiAPI/v5',
                'Code'     => $number,
                'Message'  => $message,
                'Description' => 'SUSHI 5.0 error',
                'Severity' => 'error',
                'Data_Errors' => [['Code' => $number, 'Message' => $message]],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return [
            'status'       => $this->httpStatusFor($code),
            'body'         => $body,
            'content_type' => 'application/json;charset="UTF-8"',
        ];
    }

    // ── Settings ───────────────────────────────────────────────────────────────

    public function getSettings(): array
    {
        return [
            'sushi_url'             => LibraryCounterService::getSetting('sushi_url'),
            'sushi_api_key'         => LibraryCounterService::getSetting('sushi_api_key'),
            'sushi_requestor_id'    => LibraryCounterService::getSetting('sushi_requestor_id'),
            'sushi_customer_id'     => LibraryCounterService::getSetting('sushi_customer_id'),
            'sushi_requestor_name'  => LibraryCounterService::getSetting('sushi_requestor_name'),
            'sushi_requestor_email' => LibraryCounterService::getSetting('sushi_requestor_email'),
        ];
    }

    public function saveSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            LibraryCounterService::setSetting($key, $value);
        }
    }

    // ── Private helpers ─────────────────────────────────────────────────────────

    protected function validateDateRange(string $begin, string $end): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $begin)) return false;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   return false;

        $start = date_create($begin);
        $stop  = date_create($end);
        if (!$start || !$stop || $start > $stop) return false;

        // Reject ranges spanning more than 1 year
        $diff = date_diff($start, $stop);
        if ($diff->y > 1) return false;

        return true;
    }

    protected function httpStatusFor(int $code): int
    {
        return match (true) {
            $code >= 6000 => 401,
            $code >= 5000 => 429,
            $code >= 4000 => 400,
            $code >= 3000 => 400,
            $code >= 2000 => 504,
            default       => 200,
        };
    }

    protected function hasMetrics(array $record): bool
    {
        foreach ($record as $v) {
            if (is_numeric($v) && (int) $v > 0) return true;
        }
        return false;
    }

    protected function escapeXml(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render COUNTER records as SUSHI XML response (W3C Entity-Tei).
     */
    protected function toSushiXml(string $reportType, array $records): string
    {
        $xmlRecords = '';
        foreach ($records as $rec) {
            $fields = '';
            foreach ($rec as $k => $v) {
                if ($v === null) continue;
                $tag = preg_replace('/[_\s]+/', '', ucwords($k, '_ '));
                $fields .= "  <{$tag}>{$this->escapeXml((string) $v)}</{$tag}>\n";
            }
            $xmlRecords .= "  <Report>\n{$fields}  </Report>\n";
        }

        $created = date('c');
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<COUNTERxrReport xmlns="http://www.niso.org/sushi/counter">
  <Report created="{$created}">
    <Report_ID>{$reportType}</Report_ID>
{$xmlRecords}  </Report>
</COUNTERxrReport>
XML;
    }
}