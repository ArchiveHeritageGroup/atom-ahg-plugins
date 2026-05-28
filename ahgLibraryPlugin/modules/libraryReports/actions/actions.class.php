<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\LibraryCounterService;
use AtomExtensions\Services\SushiService;

use Illuminate\Database\Capsule\Manager as DB;

// Symfony 1.x does not autoload namespaced plugin classes; load the services explicitly.
require_once sfConfig::get('sf_plugins_dir').'/ahgLibraryPlugin/lib/Service/LibraryCounterService.php';
require_once sfConfig::get('sf_plugins_dir').'/ahgLibraryPlugin/lib/Service/SushiService.php';

/**
 * Library Reports Module
 * Reports for library items, creators, subjects, circulation
 */
class libraryReportsActions extends AhgController
{
    protected function checkAccess()
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeIndex($request)
    {
        $this->checkAccess();

        $this->stats = [
            'items' => [
                'total' => DB::table('library_item')->count(),
                'available' => DB::table('library_item')->where('circulation_status', 'available')->count(),
                'onLoan' => DB::table('library_item')->where('circulation_status', 'on_loan')->count(),
                'reference' => DB::table('library_item')->where('circulation_status', 'reference')->count(),
            ],
            'byType' => DB::table('library_item')
                ->select('material_type', DB::raw('COUNT(*) as count'))
                ->groupBy('material_type')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray(),
            'creators' => DB::table('library_item_creator')->distinct('name')->count('name'),
            'subjects' => DB::table('library_item_subject')->distinct('heading')->count('heading'),
            'recentlyAdded' => DB::table('library_item')
                ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                ->count(),
        ];
    }

    public function executeCatalogue($request)
    {
        $this->checkAccess();

        $materialType = $request->getParameter('material_type');
        $status = $request->getParameter('status');
        $search = $request->getParameter('q');
        $callNumber = $request->getParameter('call_number');

        $query = DB::table('library_item as li')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('li.information_object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select(
                'li.*',
                'ioi.title',
                DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR "; ") FROM library_item_creator WHERE library_item_id = li.id AND role = "author" LIMIT 3) as authors')
            );

        if ($materialType) { $query->where('li.material_type', $materialType); }
        if ($status)        { $query->where('li.circulation_status', $status); }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('ioi.title', 'like', "%{$search}%")
                  ->orWhere('li.isbn', 'like', "%{$search}%")
                  ->orWhere('li.call_number', 'like', "%{$search}%")
                  ->orWhere('li.publisher', 'like', "%{$search}%");
            });
        }
        if ($callNumber) {
            $query->where('li.call_number', 'like', "{$callNumber}%");
        }

        $this->items = $query->orderBy('ioi.title')->get()->toArray();
        $this->filters = compact('materialType', 'status', 'search', 'callNumber');
        $this->materialTypes = DB::table('library_item')->distinct()->pluck('material_type')->toArray();
        $this->statuses = ['available', 'on_loan', 'reference', 'processing', 'missing', 'withdrawn'];
    }

    public function executeCreators($request)
    {
        $this->checkAccess();

        $role = $request->getParameter('role');
        $search = $request->getParameter('q');

        $query = DB::table('library_item_creator')
            ->select('name', 'role', DB::raw('COUNT(*) as item_count'))
            ->groupBy('name', 'role');

        if ($role)   { $query->where('role', $role); }
        if ($search) { $query->where('name', 'like', "%{$search}%"); }

        $this->creators = $query->orderBy('item_count', 'desc')->get()->toArray();
        $this->filters = compact('role', 'search');
        $this->roles = DB::table('library_item_creator')->distinct()->pluck('role')->toArray();
        $this->summary = [
            'totalCreators' => DB::table('library_item_creator')->distinct('name')->count('name'),
            'byRole' => DB::table('library_item_creator')
                ->select('role', DB::raw('COUNT(DISTINCT name) as count'))
                ->groupBy('role')
                ->get()
                ->toArray(),
        ];
    }

    public function executeSubjects($request)
    {
        $this->checkAccess();

        $subjectType = $request->getParameter('subject_type');
        $source = $request->getParameter('source');
        $search = $request->getParameter('q');

        $query = DB::table('library_item_subject')
            ->select('heading', 'subject_type', 'source', DB::raw('COUNT(*) as item_count'))
            ->groupBy('heading', 'subject_type', 'source');

        if ($subjectType) { $query->where('subject_type', $subjectType); }
        if ($source)       { $query->where('source', $source); }
        if ($search)       { $query->where('heading', 'like', "%{$search}%"); }

        $this->subjects = $query->orderBy('item_count', 'desc')->get()->toArray();
        $this->filters = compact('subjectType', 'source', 'search');
        $this->subjectTypes = DB::table('library_item_subject')->distinct()
            ->whereNotNull('subject_type')->pluck('subject_type')->toArray();
        $this->sources = DB::table('library_item_subject')->distinct()
            ->whereNotNull('source')->pluck('source')->toArray();
    }

    public function executePublishers($request)
    {
        $this->checkAccess();

        $this->publishers = DB::table('library_item')
            ->select('publisher', 'publication_place', DB::raw('COUNT(*) as item_count'))
            ->whereNotNull('publisher')->where('publisher', '!=', '')
            ->groupBy('publisher', 'publication_place')
            ->orderBy('item_count', 'desc')
            ->get()
            ->toArray();
    }

    public function executeCallNumbers($request)
    {
        $this->checkAccess();

        $this->callNumbers = DB::table('library_item as li')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('li.information_object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('li.call_number', 'li.classification_scheme', 'li.shelf_location',
                     'ioi.title', 'li.material_type')
            ->whereNotNull('li.call_number')
            ->orderBy('li.call_number')
            ->get()
            ->toArray();

        $this->summary = [
            'withCallNumber' => DB::table('library_item')->whereNotNull('call_number')
                                      ->where('call_number', '!=', '')->count(),
            'withoutCallNumber' => DB::table('library_item')
                                      ->where(function($q) {
                                          $q->whereNull('call_number')->orWhere('call_number', '');
                                      })->count(),
            'byScheme' => DB::table('library_item')
                ->select('classification_scheme', DB::raw('COUNT(*) as count'))
                ->whereNotNull('classification_scheme')
                ->groupBy('classification_scheme')
                ->get()
                ->toArray(),
        ];
    }

    public function executeExportCsv($request)
    {
        $this->checkAccess();

        $report = $request->getParameter('report');
        $filename = 'library_' . $report . '_' . date('Y-m-d') . '.csv';

        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition',
            'attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        switch ($report) {
            case 'catalogue':
                $data = DB::table('library_item as li')
                    ->leftJoin('information_object_i18n as ioi', function($join) {
                        $join->on('li.information_object_id', '=', 'ioi.id')
                             ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->select('ioi.title', 'li.material_type', 'li.call_number', 'li.isbn',
                             'li.publisher', 'li.publication_date', 'li.circulation_status')
                    ->get()
                    ->toArray();
                break;
            case 'creators':
                $data = DB::table('library_item_creator')
                    ->select('name', 'role', DB::raw('COUNT(*) as item_count'))
                    ->groupBy('name', 'role')->orderBy('name')
                    ->get()->toArray();
                break;
            case 'subjects':
                $data = DB::table('library_item_subject')
                    ->select('heading', 'subject_type', 'source', DB::raw('COUNT(*) as item_count'))
                    ->groupBy('heading', 'subject_type', 'source')->orderBy('heading')
                    ->get()->toArray();
                break;
            default:
                $data = [];
        }

        if (!empty($data)) {
            fputcsv($output, array_keys((array)$data[0]));
            foreach ($data as $row) { fputcsv($output, (array)$row); }
        }

        fclose($output);
        return sfView::NONE;
    }

    // ── COUNTER R5 Reports ─────────────────────────────────────────────────────

    public function executeCounter($request)
    {
        $this->checkAccess();

        $reportType = $request->getParameter('report_type', 'TR_J1');
        $begin      = $request->getParameter('begin_date', date('Y-01-01'));
        $end        = $request->getParameter('end_date', date('Y-m-d'));
        $format     = $request->getParameter('format', 'json');

        $svc = new LibraryCounterService($begin, $end);

        // Descriptions per report type
        $descriptions = [
            'TR_J1' => 'Total number of successful article requests by journal title. Metrics: Total Item Requests + Total Item Investigations. Breakdowns by data type, access type, and publication year.',
            'TR_J3' => 'Articles denied access due to no active subscription or usage limit exceeded. Use this report to monitor unmet demand and guide renewal decisions.',
            'DR'    => 'Usage aggregated by database / publisher platform. Shows total requests and investigations per material type across the reporting period.',
            'PR'    => 'Platform-wide totals for all metrics across all titles and databases. The broadest view of library usage.',
            'IR'    => 'Per-item usage for individual books, articles, and other items. Metrics broken down by item — use for collection analysis.',
        ];

        // If downloading, stream the report directly
        if ($request->getParameter('download')) {
            $records = match ($reportType) {
                'TR_J1' => $svc->TR_J1(),
                'TR_J3' => $svc->TR_J3(),
                'DR'    => $svc->DR(),
                'PR'    => $svc->PR(),
                'IR'    => $svc->IR(),
                default => [],
            };

            if ($format === 'tsv') {
                $content  = $svc->toTsv($reportType, $records);
                $filename = "counter_{$reportType}_" . date('Ymd') . '.tsv';
                $mime     = 'text/tab-separated-values';
            } else {
                $content  = $svc->toJson($reportType, $records);
                $filename = "counter_{$reportType}_" . date('Ymd') . '.json';
                $mime     = 'application/json';
            }

            $this->getResponse()->setHttpHeader('Content-Disposition',
                'attachment; filename="' . $filename . '"');
            $this->getResponse()->setContentType($mime);
            echo $content;
            return sfView::NONE;
        }

        // Preview: generate report and pass first 50 rows
        $records = match ($reportType) {
            'TR_J1' => $svc->TR_J1(),
            'TR_J3' => $svc->TR_J3(),
            'DR'    => $svc->DR(),
            'PR'    => $svc->PR(),
            'IR'    => $svc->IR(),
            default => [],
        };
        $this->reportData       = array_slice($records, 0, 50);
        $this->reportDescription = $descriptions[$reportType] ?? '';

        $this->reportTypes = [
            'TR_J1' => 'Journal Articles (Requests)',
            'TR_J3' => 'Journal Articles (Access Denied)',
            'DR'    => 'Database Report',
            'PR'    => 'Platform Report',
            'IR'    => 'Item Report',
        ];
        $this->selectedReport  = $reportType;
        $this->beginDate       = $begin;
        $this->endDate         = $end;
        $this->formats         = ['json' => 'JSON', 'tsv' => 'TSV'];
        $this->selectedFormat  = $format;

        // Preview count
        try {
            $this->previewTotal = DB::table('library_usage_event')
                ->whereBetween('created_at', [$begin, $end . ' 23:59:59'])
                ->count();
        } catch (Exception $e) {
            $this->previewTotal = 0;
        }

        // Sparkline: last 30 days of daily event counts
        $this->sparklineData = $this->buildSparkline(30);
    }

    /**
     * Build an array of {date, count} for the last N days.
     */
    protected function buildSparkline(int $days): array
    {
        try {
            $rows = DB::table('library_usage_event')
                ->where('created_at', '>=', date('Y-m-d', strtotime("-{$days} days")))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->toArray();

            $lookup = [];
            foreach ($rows as $r) { $lookup[$r->date] = (int) $r->count; }

            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $data[] = ['date' => $d, 'count' => $lookup[$d] ?? 0];
            }
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    public function executeSushiSettings($request)
    {
        $this->checkAccess();

        $svc = new SushiService();

        if ($request->getMethod() === 'POST') {
            // Test connection
            if ($request->getParameter('test_connection')) {
                $result = $svc->testConnection($this->getSushiSettingsForTest($svc));
                $this->testResult = $result;
            } else {
                $svc->saveSettings([
                    'sushi_url'              => trim($request->getParameter('sushi_url', '')),
                    'sushi_api_key'         => trim($request->getParameter('sushi_api_key', '')),
                    'sushi_requestor_id'     => trim($request->getParameter('sushi_requestor_id', '')),
                    'sushi_customer_id'     => trim($request->getParameter('sushi_customer_id', '')),
                    'sushi_requestor_name'  => trim($request->getParameter('sushi_requestor_name', '')),
                    'sushi_requestor_email' => trim($request->getParameter('sushi_requestor_email', '')),
                ]);
                $this->message = 'SUSHI settings saved.';
            }
        }

        $this->settings = $svc->getSettings();
        $this->testUrl  = '/sushi/counter5';

        // Access log — try library_sushi_access_log first, fall back to manual
        try {
            $this->accessLog = DB::table('library_sushi_access_log')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            $this->accessLog = [];
        }
    }

    /**
     * Build settings array for test connection (read current values).
     */
    protected function getSushiSettingsForTest(SushiService $svc): array
    {
        $s = $svc->getSettings();
        return [
            'sushi_url'              => $s['sushi_url'] ?? '',
            'sushi_api_key'         => $s['sushi_api_key'] ?? '',
            'sushi_requestor_id'     => $s['sushi_requestor_id'] ?? '',
            'sushi_customer_id'     => $s['sushi_customer_id'] ?? '',
            'sushi_requestor_name'  => $s['sushi_requestor_name'] ?? '',
            'sushi_requestor_email' => $s['sushi_requestor_email'] ?? '',
        ];
    }

    // ── Usage Event Capture ────────────────────────────────────────────────────

    /**
     * Track a usage event (fire-and-forget from OPAC or other entry points).
     * POST /libraryReports/trackEvent
     * Params: event_type, item_id, patron_id, metadata (JSON string), session_id
     */
    public function executeTrackEvent($request)
    {
        // Allow unauthenticated POST — OPAC visitors log anonymously
        $eventType = $request->getParameter('event_type', 'opac_view');
        $itemId    = $request->getParameter('item_id');
        $patronId  = $request->getParameter('patron_id');
        $metadata  = $request->getParameter('metadata');
        $sessionId = $request->getParameter('session_id',
                        $this->getUser()->getAttribute('session_id'));

        try {
            if ($metadata && is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?: null;
            }

            $id = LibraryCounterService::recordEvent(
                $eventType,
                $itemId ? (int) $itemId : null,
                $patronId ? (int) $patronId : null,
                $metadata,
                $request->getHttpHeader('X-Forwarded-For')
                    ?: ($_SERVER['REMOTE_ADDR'] ?? null),
                $request->getHttpHeader('User-Agent'),
                $sessionId
            );

            if ($request->getParameter('format') === 'json') {
                $this->getResponse()->setContentType('application/json');
                echo json_encode(['event_id' => $id, 'ok' => true]);
                return sfView::NONE;
            }
        } catch (Exception $e) {
            if ($request->getParameter('format') === 'json') {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
                return sfView::NONE;
            }
        }

        return sfView::NONE;
    }

    // ── FRBR Override & Stats ─────────────────────────────────────────────────

    /**
     * FRBR work-key override management.
     * GET  /libraryReports/frbrOverride — list all overrides + work-key stats
     * POST /libraryReports/frbrOverride — set / clear an override
     */
    public function executeFrbrOverride($request)
    {
        $this->checkAccess();

        $itemId  = $request->getParameter('item_id');
        $action  = $request->getParameter('action', 'list');
        $frbrSvc = \FrbrService::getInstance();
        $userId  = $this->getUser()->id ?? null;

        if ($request->getMethod() === 'POST') {
            $itemId = $request->getParameter('library_item_id');
            if (!$itemId) {
                $this->error = 'Missing library_item_id.';
            } else {
                try {
                    if ($action === 'force_group') {
                        $frbrSvc->setForceGroup(
                            (int) $itemId,
                            $request->getParameter('target_work_key') ?: null,
                            $request->getParameter('reason') ?: 'Admin override',
                            $userId
                        );
                        $this->message = 'Override applied.';
                    } elseif ($action === 'force_split') {
                        $frbrSvc->setForceSplit(
                            (int) $itemId,
                            $request->getParameter('reason') ?: 'Admin override',
                            $userId
                        );
                        $this->message = 'Split override applied.';
                    } elseif ($action === 'clear') {
                        $frbrSvc->clearOverride((int) $itemId);
                        $this->message = 'Override cleared.';
                    }
                } catch (Exception $e) {
                    $this->error = 'Failed: ' . $e->getMessage();
                }
            }
        }

        if ($itemId) {
            $this->searchedItem = DB::table('library_item as li')
                ->leftJoin('information_object_i18n as ioi', function($j) {
                    $j->on('li.information_object_id', '=', 'ioi.id')
                      ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->select('li.id', 'li.frbr_work_key', 'li.frbr_override_type',
                         'ioi.title as title', 'li.isbn')
                ->where('li.id', $itemId)
                ->first();
        }

        $this->overrides = DB::table('library_item_frbr_override as r')
            ->leftJoin('library_item as li', 'r.library_item_id', '=', 'li.id')
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('r.*', 'ioi.title')
            ->orderBy('r.created_at', 'desc')
            ->limit(200)
            ->get()
            ->toArray();

        $this->workKeyStats = [
            'total'   => DB::table('library_item')->count(),
            'keyed'   => DB::table('library_item')->whereNotNull('frbr_work_key')->count(),
            'grouped' => DB::table('library_item')->where('frbr_override_type', 'force_group')->count(),
            'split'   => DB::table('library_item')->where('frbr_override_type', 'force_split')->count(),
            'unkeyed' => DB::table('library_item')
                             ->whereNull('frbr_work_key')
                             ->where('frbr_override_type', 'none')->count(),
        ];
    }
}