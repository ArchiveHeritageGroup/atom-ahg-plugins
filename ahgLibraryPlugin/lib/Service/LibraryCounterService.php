<?php

declare(strict_types=1);

/**
 * LibraryCounterService
 *
 * Generates COUNTER R5 reports from library_usage_event data.
 * Supports: TR_J1 (Journal Title), DR (Database), PR (Platform), IR (Item), TR_J3.
 *
 * @package ahgLibraryPlugin
 * @subpackage Service
 *
 * @see https://opencounterets.github.io/COUNTER_Release5/
 */

namespace AtomExtensions\Services;

use Illuminate\Database\Capsule\Manager as DB;
use DateTime;
use DateTimeImmutable;
use DateInterval;
use Exception;

class LibraryCounterService
{
    // COUNTER R5 metric types used in this library
    public const METRIC_TOTAL_ITEM_REQUESTS   = 'Total_Item_Requests';
    public const METRIC_TOTAL_ITEM_INVEST     = 'Total_Item_Investigations';
    public const METRIC_ACCESS_DENIED         = 'Total_Access_Denied';
    public const METRIC_SEARCHES_RUN          = 'Searches_Registrations';
    public const METRIC_LINK_CLICKS           = 'Link_Opens';

    // Report IDs
    public const REPORT_TR_J1 = 'TR_J1';
    public const REPORT_DR   = 'DR';
    public const REPORT_PR   = 'PR';
    public const REPORT_IR   = 'IR';
    public const REPORT_TR_J3 = 'TR_J3';

    protected string $reportStart;
    protected string $reportEnd;
    protected string $institutionName;
    protected string $institutionId;
    protected ?string $platform;

    public function __construct(
        string $reportStart = '',
        string $reportEnd = '',
        string $institutionName = 'Default Institution',
        string $institutionId = '',
        ?string $platform = null
    ) {
        $this->reportStart = $reportStart ?: date('Y-01-01');
        $this->reportEnd   = $reportEnd   ?: date('Y-m-d');
        $this->institutionName = $institutionName;
        $this->institutionId   = $institutionId;
        $this->platform       = $platform ?? \sfConfig::get('app_library_name', 'AHG Library');
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * TR_J1 — Journal Title Report (Requests)
     * One row per unique journal / title / metric.
     */
    public function TR_J1(): array
    {
        $rows = $this->usageQuery()
            ->groupBy('li.id', 'event_type')
            ->select(
                'li.id',
                DB::raw("'Journal' AS Data_Type"),
                DB::raw("'Article' AS Access_Type"),
                DB::raw("'Regular' AS Access_Method"),
                DB::raw("'Continuing' AS Publication_Genre"),
                DB::raw("'Online' AS Delivery_Method"),
                DB::raw("'subscribed' AS YOP"),
                'event_type',
                DB::raw('COUNT(*) AS metric_count')
            )
            ->get()
            ->toArray();

        return $this->pivotToCounterFormat($rows, self::REPORT_TR_J1);
    }

    /**
     * TR_J3 — Journal Title Report (Access Denied)
     */
    public function TR_J3(): array
    {
        $rows = $this->usageQuery()
            ->where('lue.event_type', 'ir_access')
            ->where('lue.metadata', 'like', '%"denied":true%')
            ->groupBy('li.id', 'event_type')
            ->select(
                'li.id',
                DB::raw("'Journal' AS Data_Type"),
                DB::raw("'Article' AS Access_Type"),
                DB::raw("'Regular' AS Access_Method"),
                DB::raw("'Continuing' AS Publication_Genre"),
                DB::raw("'Online' AS Delivery_Method"),
                DB::raw("'subscribed' AS YOP"),
                'event_type',
                DB::raw('COUNT(*) AS metric_count')
            )
            ->get()
            ->toArray();

        return $this->pivotToCounterFormat($rows, self::REPORT_TR_J3);
    }

    /**
     * PR — Platform Report (aggregated platform totals)
     */
    public function PR(): array
    {
        $events = DB::table('library_usage_event as lue')
            ->whereBetween('lue.created_at', [$this->reportStart, $this->reportEnd . ' 23:59:59'])
            ->select('lue.event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('lue.event_type')
            ->get()
            ->toArray();

        $totalSearches = 0;
        $totalRequests = 0;
        $totalDenied   = 0;
        $totalInvestigations = 0;

        foreach ($events as $e) {
            switch ($e->event_type) {
                case 'search':
                    $totalSearches = (int) $e->count;
                    break;
                case 'opac_view':
                    $totalInvestigations = (int) $e->count;
                    break;
                case 'ir_access':
                    $totalRequests = (int) $e->count;
                    break;
                case 'link_click':
                    $totalInvestigations += (int) $e->count;
                    break;
            }
        }

        $now = (new \DateTime())->format('Y-m-d\TH:i:s\Z');

        return [[
            'Report_ID'                        => self::REPORT_PR,
            'Report_Name'                      => 'Platform Report',
            'Institution'                      => $this->institutionName,
            'Institution_ID'                   => $this->institutionId,
            'Platform'                         => $this->platform,
            'Metric_Types'                     => self::METRIC_TOTAL_ITEM_REQUESTS . '; '
                . self::METRIC_SEARCHES_RUN . '; '
                . self::METRIC_TOTAL_ITEM_INVEST,
            'Reporting_Period_Total'           => $totalRequests,
            'Reporting_Period_Total_Investigations' => $totalInvestigations,
            'Reporting_Period_Total_Searches'  => $totalSearches,
            'YTD_Total'                        => $totalRequests,
            'YTD_Total_Investigations'         => $totalInvestigations,
            'YTD_Total_Searches'               => $totalSearches,
            'Report_Created'                   => $now,
            'Created_By'                        => 'AHG Library / LibraryCounterService',
        ]];
    }

    /**
     * DR — Database Report (aggregated per material_type / platform)
     */
    public function DR(): array
    {
        $rows = DB::table('library_usage_event as lue')
            ->leftJoin('library_item as li', 'lue.library_item_id', '=', 'li.id')
            ->whereBetween('lue.created_at', [$this->reportStart, $this->reportEnd . ' 23:59:59'])
            ->whereIn('lue.event_type', ['opac_view', 'ir_access', 'link_click'])
            ->groupBy('li.material_type')
            ->select(
                'li.material_type',
                DB::raw('COUNT(CASE WHEN lue.event_type IN ("opac_view","link_click") THEN 1 END) AS investigations'),
                DB::raw('COUNT(CASE WHEN lue.event_type = "ir_access" THEN 1 END) AS requests'),
                DB::raw('COUNT(*) AS total')
            )
            ->get()
            ->toArray();

        $now = (new \DateTime())->format('Y-m-d\TH:i:s\Z');
        $records = [];

        foreach ($rows as $row) {
            $dbName = $row->material_type ?? 'Unknown';
            $records[] = [
                'Report_ID'       => self::REPORT_DR,
                'Report_Name'     => 'Database Report',
                'Database_Name'   => ucfirst($dbName),
                'Platform'        => $this->platform,
                'Creator'         => null,
                'Publisher'      => null,
                'Metric_Types'   => self::METRIC_TOTAL_ITEM_REQUESTS . '; '
                    . self::METRIC_TOTAL_ITEM_INVEST,
                'Reporting_Period_Total_Requests'      => (int) $row->requests,
                'Reporting_Period_Total_Investigations' => (int) $row->investigations,
                'YTD_Total_Requests'      => (int) $row->requests,
                'YTD_Total_Investigations' => (int) $row->investigations,
                'Report_Created'  => $now,
                'Created_By'       => 'AHG Library / LibraryCounterService',
            ];
        }

        return $records;
    }

    /**
     * IR — Item Report (per-item totals)
     */
    public function IR(): array
    {
        $rows = DB::table('library_usage_event as lue')
            ->leftJoin('library_item as li', 'lue.library_item_id', '=', 'li.id')
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereBetween('lue.created_at', [$this->reportStart, $this->reportEnd . ' 23:59:59'])
            ->whereIn('lue.event_type', ['opac_view', 'ir_access', 'link_click'])
            ->groupBy('li.id', 'lue.event_type')
            ->select(
                'li.id',
                'ioi.title',
                'li.isbn',
                'li.issn',
                'li.publisher',
                'li.material_type',
                'lue.event_type',
                DB::raw('COUNT(*) as metric_count')
            )
            ->get()
            ->toArray();

        return $this->pivotToCounterFormat($rows, self::REPORT_IR);
    }

    // ── Output Formatting ───────────────────────────────────────────────────────

    /**
     * Render a report as JSON.
     */
    public function toJson(string $reportId, array $data): string
    {
        return json_encode([
            'Report_ID'        => $reportId,
            'Report_Name'      => $this->reportName($reportId),
            'Institution'      => $this->institutionName,
            'Institution_ID'   => $this->institutionId,
            'Platform'         => $this->platform,
            'Created'          => (new \DateTime())->format('Y-m-d\TH:i:s\Z'),
            'Reporting_Period' => [
                'Begin' => $this->reportStart,
                'End'   => $this->reportEnd,
            ],
            'Records'          => $this->filterForSUSHI($data),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render a report as flattened TSV (COUNTER style).
     */
    public function toTsv(string $reportId, array $data): string
    {
        $records = $this->filterForSUSHI($data);
        if (empty($records)) {
            return "Report_SKipped\tNo data for the selected period\n";
        }

        $header = array_keys($records[0]);
        $lines  = [implode("\t", $header)];

        foreach ($records as $record) {
            $values = array_map(fn($v) => (string) $v, array_values($record));
            $lines[] = implode("\t", $values);
        }

        return implode("\n", $lines) . "\n";
    }

    // ── Usage Event Capture ────────────────────────────────────────────────────

    /**
     * Record a usage event. Called by OPAC actions and other entry points.
     *
     * @param string $eventType  One of: opac_view, link_click, ir_access, search, export
     * @param int|null $itemId   library_item.id (null for search events)
     * @param int|null $patronId library_patron.id (null for anonymous)
     * @param array|null $metadata JSON-serialisable extra data
     */
    public static function recordEvent(
        string $eventType,
        ?int $itemId = null,
        ?int $patronId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $sessionId = null
    ): int {
        $validTypes = ['opac_view','link_click','ir_access','search','export'];
        if (!in_array($eventType, $validTypes, true)) {
            throw new \Exception("Invalid event_type: $eventType");
        }

        // Guard: only log authenticated/anonymous patrons with valid item references
        $db = DB::connection();
        $row = [
            'event_type'       => $eventType,
            'library_item_id'  => $itemId,
            'patron_id'        => $patronId,
            'metadata'        => $metadata ? json_encode($metadata) : null,
            'ip_address'      => $ipAddress,
            'user_agent'       => $userAgent,
            'session_id'      => $sessionId,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        return (int) DB::table('library_usage_event')->insertGetId($row);
    }

    // ── Settings helpers ──────────────────────────────────────────────────────

    public static function getSetting(string $key): ?string
    {
        return DB::table('library_counter_settings')
            ->where('setting_key', $key)
            ->value('setting_value');
    }

    public static function setSetting(string $key, ?string $value): void
    {
        DB::table('library_counter_settings')
            ->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
            );
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    protected function usageQuery(): object
    {
        return DB::table('library_usage_event as lue')
            ->leftJoin('library_item as li', 'lue.library_item_id', '=', 'li.id')
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereBetween('lue.created_at', [$this->reportStart, $this->reportEnd . ' 23:59:59'])
            ->where('lue.event_type', '!=', 'search');
    }

    protected function pivotToCounterFormat(array $rows, string $reportId): array
    {
        $records = [];
        $now = (new \DateTime())->format('Y-m-d\TH:i:s\Z');

        // Group by item
        $grouped = [];
        foreach ($rows as $r) {
            $key = $r->id ?? 'anon';
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id'        => $r->id ?? null,
                    'isbn'      => $r->isbn ?? '',
                    'issn'      => $r->issn ?? '',
                    'publisher' => $r->publisher ?? '',
                    'title'     => $r->title ?? '',
                ];
            }
            // pivot event_type → metric_count
            $metricType = self::eventTypeToMetric($r->event_type);
            $grouped[$key]['_metrics'][$metricType] = (int) $r->metric_count;
        }

        foreach ($grouped as $itemId => $item) {
            $requests  = $item['_metrics'][self::METRIC_TOTAL_ITEM_REQUESTS]   ?? 0;
            $invest   = $item['_metrics'][self::METRIC_TOTAL_ITEM_INVEST]     ?? 0;
            $denied   = $item['_metrics'][self::METRIC_ACCESS_DENIED]          ?? 0;
            $linkOpen = $item['_metrics'][self::METRIC_LINK_CLICKS]            ?? 0;

            $records[] = array_merge([
                'Report_ID'                          => $reportId,
                'Report_Name'                        => $this->reportName($reportId),
                'Institution'                        => $this->institutionName,
                'Institution_ID'                     => $this->institutionId,
                'Platform'                           => $this->platform,
                'Proprietary_ID'                     => $itemId,
                'DOI'                                => null,
                'ISBN'                               => $item['isbn'] ?: null,
                'Print_ISSN'                        => null,
                'Electronic_ISSN'                    => $item['issn'] ?: null,
                'Journal_Title'                      => $item['title'] ?: null,
                'Article_Title'                      => null,
                'Data_Type'                          => 'Journal',
                'Access_Type'                        => 'Article',
                'Access_Method'                      => 'Regular',
                'Publication_Genre'                  => 'Continuing',
                'YOP'                                => null,
                'Period'                             => $this->reportStart . ' - ' . $this->reportEnd,
                'Metric_Types'                       => self::METRIC_TOTAL_ITEM_REQUESTS . '; '
                    . self::METRIC_TOTAL_ITEM_INVEST,
                'Reporting_Period_Total_Requests'    => $requests,
                'Reporting_Period_Total_Investigations' => $invest,
                'Reporting_Period_HTML_Requests'      => $requests,
                'Reporting_Period_PDF_Requests'      => 0,
                'Reporting_Period_XML_Requests'      => 0,
                'YTD_Total_Item_Requests'             => $requests,
                'YTD_Total_Item_Investigations'      => $invest,
                'Report_Created'                     => $now,
                'Created_By'                          => 'AHG Library / LibraryCounterService',
            ], $reportId === self::REPORT_TR_J3 ? [
                'Reporting_Period_Access_Denied'     => $denied,
                'YTD_Access_Denied'                  => $denied,
            ] : []);
        }

        return $records;
    }

    protected static function eventTypeToMetric(string $eventType): string
    {
        return match ($eventType) {
            'opac_view'  => self::METRIC_TOTAL_ITEM_INVEST,
            'ir_access'  => self::METRIC_TOTAL_ITEM_REQUESTS,
            'link_click' => self::METRIC_LINK_CLICKS,
            'export'     => self::METRIC_TOTAL_ITEM_REQUESTS,
            default      => self::METRIC_TOTAL_ITEM_REQUESTS,
        };
    }

    protected function reportName(string $reportId): string
    {
        return match ($reportId) {
            self::REPORT_TR_J1 => 'Journal Article Requests',
            self::REPORT_TR_J3 => 'Journal Article Access Denied',
            self::REPORT_DR    => 'Database Report',
            self::REPORT_PR    => 'Platform Report',
            self::REPORT_IR    => 'Item Report',
            default            => 'AHG Library COUNTER Report',
        };
    }

    /**
     * Strip rows that have zero metrics (SUSHI returns empty rather than zeros).
     */
    protected function filterForSUSHI(array $records): array
    {
        return array_values(array_filter($records, function($r) {
            // Keep if any metric column is non-zero
            foreach ($r as $v) {
                if (is_numeric($v) && (int) $v > 0) {
                    return true;
                }
            }
            return false;
        }));
    }
}
