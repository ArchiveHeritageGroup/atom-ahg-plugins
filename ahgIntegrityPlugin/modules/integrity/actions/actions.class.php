<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class integrityActions extends AhgController
{
    protected function requireAdmin(): void
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !$user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    protected function getService(): IntegrityService
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/IntegrityService.php';

        return new IntegrityService();
    }

    protected function getRetentionService(): IntegrityRetentionService
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/IntegrityRetentionService.php';

        return new IntegrityRetentionService();
    }

    protected function getAlertService(): IntegrityAlertService
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/IntegrityAlertService.php';

        return new IntegrityAlertService();
    }

    // ------------------------------------------------------------------
    // Page actions
    // ------------------------------------------------------------------

    public function executeIndex($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        // Issue #190: Optional repository filter for drill-down
        $repositoryId = $request->getParameter('repository_id') ? (int) $request->getParameter('repository_id') : null;
        $this->filterRepositoryId = $repositoryId;

        $scope = $repositoryId ? ['repository_id' => $repositoryId] : [];

        $this->stats = $service->getDashboardStats($scope);
        $this->recentRuns = $service->getRecentRuns(10);
        $this->recentFailures = $service->getRecentFailures(10);

        // Issue #190: Enhanced stats
        $this->backlog = $service->calculateBacklog();
        $this->throughput = $service->calculateThroughput(7);
        $this->dailyTrend = $service->getDailyTrend(30, $scope);
        $this->repoBreakdown = $service->getRepositoryBreakdown();
        $this->failureBreakdown = $service->getFailureTypeBreakdown(30, $scope);
        $this->formatBreakdown = $service->getFormatBreakdown();
        $this->storageGrowth = $service->getStorageGrowth(30);

        // Repositories for filter dropdown
        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'repository.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->values()
            ->all();

        $this->pageTitle = 'Integrity Dashboard';
    }

    public function executeSchedules($request)
    {
        $this->requireAdmin();

        $this->schedules = DB::table('integrity_schedule')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();

        $this->pageTitle = 'Verification Schedules';
    }

    public function executeScheduleEdit($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $id = $request->getParameter('id');
        $this->schedule = $id ? DB::table('integrity_schedule')->where('id', (int) $id)->first() : null;
        $this->isNew = !$this->schedule;

        // Available repositories for scope dropdown
        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'repository.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->values()
            ->all();

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name', 'New Schedule'),
                'description' => $request->getParameter('description'),
                'scope_type' => $request->getParameter('scope_type', 'global'),
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'information_object_id' => $request->getParameter('information_object_id') ?: null,
                'algorithm' => $request->getParameter('algorithm', 'sha256'),
                'frequency' => $request->getParameter('frequency', 'weekly'),
                'cron_expression' => $request->getParameter('cron_expression') ?: null,
                'batch_size' => (int) $request->getParameter('batch_size', 200),
                'io_throttle_ms' => (int) $request->getParameter('io_throttle_ms', 0),
                'max_memory_mb' => (int) $request->getParameter('max_memory_mb', 512),
                'max_runtime_minutes' => (int) $request->getParameter('max_runtime_minutes', 120),
                'max_concurrent_runs' => (int) $request->getParameter('max_concurrent_runs', 1),
                'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
                'notify_on_failure' => $request->getParameter('notify_on_failure') ? 1 : 0,
                'notify_on_mismatch' => $request->getParameter('notify_on_mismatch') ? 1 : 0,
                'notify_email' => $request->getParameter('notify_email') ?: null,
            ];

            if ($this->isNew) {
                $service->createSchedule($data);
            } else {
                $service->updateSchedule((int) $id, $data);
            }

            $this->redirect(['module' => 'integrity', 'action' => 'schedules']);
        }

        $this->pageTitle = $this->isNew ? 'New Schedule' : 'Edit Schedule';
    }

    public function executeRuns($request)
    {
        $this->requireAdmin();

        $query = DB::table('integrity_run')
            ->leftJoin('integrity_schedule', 'integrity_run.schedule_id', '=', 'integrity_schedule.id')
            ->select('integrity_run.*', 'integrity_schedule.name as schedule_name');

        $scheduleId = $request->getParameter('schedule_id');
        if ($scheduleId) {
            $query->where('integrity_run.schedule_id', (int) $scheduleId);
            $this->filterScheduleId = (int) $scheduleId;
        }

        $status = $request->getParameter('status');
        if ($status) {
            $query->where('integrity_run.status', $status);
            $this->filterStatus = $status;
        }

        $this->runs = $query->orderByDesc('integrity_run.started_at')
            ->limit(100)
            ->get()
            ->values()
            ->all();

        $this->schedules = DB::table('integrity_schedule')
            ->orderBy('name')
            ->get()
            ->values()
            ->all();

        $this->pageTitle = 'Run History';
    }

    public function executeRunDetail($request)
    {
        $this->requireAdmin();

        $id = (int) $request->getParameter('id');
        $this->run = DB::table('integrity_run')
            ->leftJoin('integrity_schedule', 'integrity_run.schedule_id', '=', 'integrity_schedule.id')
            ->select('integrity_run.*', 'integrity_schedule.name as schedule_name')
            ->where('integrity_run.id', $id)
            ->first();

        if (!$this->run) {
            $this->forward404();
        }

        $this->ledgerEntries = DB::table('integrity_ledger')
            ->where('run_id', $id)
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->values()
            ->all();

        $this->outcomeBreakdown = DB::table('integrity_ledger')
            ->select('outcome', DB::raw('COUNT(*) as cnt'))
            ->where('run_id', $id)
            ->groupBy('outcome')
            ->pluck('cnt', 'outcome')
            ->all();

        $this->pageTitle = "Run #{$id}";
    }

    public function executeLedger($request)
    {
        $this->requireAdmin();

        $query = DB::table('integrity_ledger');

        $outcome = $request->getParameter('outcome');
        if ($outcome) {
            $query->where('outcome', $outcome);
            $this->filterOutcome = $outcome;
        }

        $repositoryId = $request->getParameter('repository_id');
        if ($repositoryId) {
            $query->where('repository_id', (int) $repositoryId);
            $this->filterRepositoryId = (int) $repositoryId;
        }

        $dateFrom = $request->getParameter('date_from');
        if ($dateFrom) {
            $query->where('verified_at', '>=', $dateFrom . ' 00:00:00');
            $this->filterDateFrom = $dateFrom;
        }

        $dateTo = $request->getParameter('date_to');
        if ($dateTo) {
            $query->where('verified_at', '<=', $dateTo . ' 23:59:59');
            $this->filterDateTo = $dateTo;
        }

        $this->entries = $query->orderByDesc('verified_at')
            ->limit(200)
            ->get()
            ->values()
            ->all();

        $this->pageTitle = 'Verification Ledger';
    }

    public function executeDeadLetter($request)
    {
        $this->requireAdmin();

        $query = DB::table('integrity_dead_letter');

        $status = $request->getParameter('status');
        if ($status) {
            $query->where('status', $status);
            $this->filterStatus = $status;
        }

        $this->entries = $query->orderByDesc('last_failure_at')
            ->limit(200)
            ->get()
            ->values()
            ->all();

        $this->statusCounts = DB::table('integrity_dead_letter')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        $this->pageTitle = 'Dead Letter Queue';
    }

    public function executeReport($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $this->stats = $service->getDashboardStats();

        $this->outcomeBreakdown = DB::table('integrity_ledger')
            ->select('outcome', DB::raw('COUNT(*) as cnt'))
            ->groupBy('outcome')
            ->pluck('cnt', 'outcome')
            ->all();

        $this->monthlyTrend = DB::table('integrity_ledger')
            ->select(
                DB::raw("DATE_FORMAT(verified_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN outcome = 'pass' THEN 1 ELSE 0 END) as passed"),
                DB::raw("SUM(CASE WHEN outcome != 'pass' THEN 1 ELSE 0 END) as failed")
            )
            ->where('verified_at', '>=', date('Y-m-d', strtotime('-12 months')))
            ->groupBy(DB::raw("DATE_FORMAT(verified_at, '%Y-%m')"))
            ->orderBy('month')
            ->get()
            ->values()
            ->all();

        $this->pageTitle = 'Integrity Report';
    }

    // ------------------------------------------------------------------
    // Issue #188: Export actions
    // ------------------------------------------------------------------

    public function executeExport($request)
    {
        $this->requireAdmin();

        // Load repositories for filter dropdown
        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'repository.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->values()
            ->all();

        $this->pageTitle = 'Export Ledger';
    }

    public function executeExportCsv($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $filters = $this->getExportFilters($request);
        $csv = $service->exportLedgerCsv($filters);

        $filename = 'integrity_ledger_' . date('Ymd_His') . '.csv';

        $response = $this->getResponse();
        $response->setContentType('text/csv');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setContent($csv);

        return sfView::NONE;
    }

    public function executeExportAuditor($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $filters = $this->getExportFilters($request);
        $tmpFile = $service->generateAuditorPack($filters);

        $filename = 'integrity_auditor_pack_' . date('Ymd_His') . '.zip';

        $response = $this->getResponse();
        $response->setContentType('application/zip');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setContent(file_get_contents($tmpFile));
        @unlink($tmpFile);

        return sfView::NONE;
    }

    protected function getExportFilters($request): array
    {
        $filters = [];
        if ($request->getParameter('date_from')) {
            $filters['date_from'] = $request->getParameter('date_from');
        }
        if ($request->getParameter('date_to')) {
            $filters['date_to'] = $request->getParameter('date_to');
        }
        if ($request->getParameter('repository_id')) {
            $filters['repository_id'] = $request->getParameter('repository_id');
        }
        if ($request->getParameter('outcome')) {
            $filters['outcome'] = $request->getParameter('outcome');
        }

        return $filters;
    }

    // ------------------------------------------------------------------
    // Issue #189: Retention policy actions
    // ------------------------------------------------------------------

    public function executePolicies($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $this->policies = $retentionService->listPolicies();
        $this->pageTitle = 'Retention Policies';
    }

    public function executePolicyEdit($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $id = $request->getParameter('id');
        $this->policy = $id ? $retentionService->getPolicy((int) $id) : null;
        $this->isNew = !$this->policy;

        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'repository.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->values()
            ->all();

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name', 'New Policy'),
                'description' => $request->getParameter('description'),
                'retention_period_days' => (int) $request->getParameter('retention_period_days', 0),
                'trigger_type' => $request->getParameter('trigger_type', 'ingest_date'),
                'scope_type' => $request->getParameter('scope_type', 'global'),
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'information_object_id' => $request->getParameter('information_object_id') ?: null,
                'object_format' => $request->getParameter('object_format') ?: null,
                'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
            ];

            if ($this->isNew) {
                $retentionService->createPolicy($data);
            } else {
                $retentionService->updatePolicy((int) $id, $data);
            }

            $this->redirect(['module' => 'integrity', 'action' => 'policies']);
        }

        $this->pageTitle = $this->isNew ? 'New Retention Policy' : 'Edit Retention Policy';
    }

    public function executeHolds($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $filters = [];
        $status = $request->getParameter('status');
        if ($status) {
            $filters['status'] = $status;
            $this->filterStatus = $status;
        }

        $this->holds = $retentionService->listHolds($filters);
        $this->pageTitle = 'Legal Holds';
    }

    public function executeDisposition($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $filters = [];
        $status = $request->getParameter('status');
        if ($status) {
            $filters['status'] = $status;
            $this->filterStatus = $status;
        }
        $policyId = $request->getParameter('policy_id');
        if ($policyId) {
            $filters['policy_id'] = (int) $policyId;
            $this->filterPolicyId = (int) $policyId;
        }

        $this->queue = $retentionService->listDispositionQueue($filters);
        $this->stats = $retentionService->getDispositionStats();
        $this->policies = $retentionService->listPolicies();
        $this->pageTitle = 'Disposition Queue';
    }

    // ------------------------------------------------------------------
    // Issue #190: Alert configuration
    // ------------------------------------------------------------------

    public function executeAlerts($request)
    {
        $this->requireAdmin();
        $alertService = $this->getAlertService();

        $this->alertConfigs = $alertService->listAlertConfigs();
        $this->pageTitle = 'Alert Configuration';
    }

    // ------------------------------------------------------------------
    // API endpoints (existing)
    // ------------------------------------------------------------------

    public function executeApiVerify($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $objectId = (int) $request->getParameter('object_id');
        if (!$objectId) {
            return $this->renderJson(['success' => false, 'error' => 'object_id required']);
        }

        try {
            $result = $service->verifyByObjectId($objectId, 'sha256', 'api');

            return $this->renderJson(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiRun($request)
    {
        $this->requireAdmin();

        $id = (int) $request->getParameter('id');
        $run = DB::table('integrity_run')->where('id', $id)->first();

        if (!$run) {
            return $this->renderJson(['success' => false, 'error' => 'Run not found']);
        }

        return $this->renderJson(['success' => true, 'run' => $run]);
    }

    public function executeApiScheduleToggle($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $id = (int) $request->getParameter('id');

        try {
            $service->toggleSchedule($id);
            $schedule = DB::table('integrity_schedule')->where('id', $id)->first();

            return $this->renderJson(['success' => true, 'is_enabled' => (bool) $schedule->is_enabled]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiScheduleDelete($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $id = (int) $request->getParameter('id');

        try {
            $service->deleteSchedule($id);

            return $this->renderJson(['success' => true]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiRunSchedule($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $id = (int) $request->getParameter('id');

        try {
            $result = $service->executeBatchVerification($id, 'api', $this->getUser()->getUserName());

            return $this->renderJson(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiDeadLetterAction($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $id = (int) $request->getParameter('id');
        $action = $request->getParameter('dead_letter_action');
        $notes = $request->getParameter('notes');

        $statusMap = [
            'acknowledge' => 'acknowledged',
            'investigate' => 'investigating',
            'resolve' => 'resolved',
            'ignore' => 'ignored',
            'reopen' => 'open',
        ];

        $status = $statusMap[$action] ?? null;
        if (!$status) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid action']);
        }

        $result = $service->updateDeadLetterStatus($id, $status, $notes, $this->getUser()->getUserName());

        return $this->renderJson(['success' => $result]);
    }

    public function executeApiStats($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        return $this->renderJson(['success' => true, 'stats' => $service->getDashboardStats()]);
    }

    // ------------------------------------------------------------------
    // API endpoints (Issue #189: Retention)
    // ------------------------------------------------------------------

    public function executeApiPolicyToggle($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $id = (int) $request->getParameter('id');

        try {
            $retentionService->togglePolicy($id);
            $policy = $retentionService->getPolicy($id);

            return $this->renderJson(['success' => true, 'is_enabled' => (bool) ($policy->is_enabled ?? false)]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiPolicyDelete($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $id = (int) $request->getParameter('id');

        try {
            $retentionService->deletePolicy($id);

            return $this->renderJson(['success' => true]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiHoldPlace($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $ioId = (int) $request->getParameter('information_object_id');
        $reason = $request->getParameter('reason');

        if (!$ioId || !$reason) {
            return $this->renderJson(['success' => false, 'error' => 'information_object_id and reason required']);
        }

        try {
            $holdId = $retentionService->placeHold($ioId, $reason, $this->getUser()->getUserName());

            return $this->renderJson(['success' => true, 'hold_id' => $holdId]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiHoldRelease($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $id = (int) $request->getParameter('id');

        try {
            $result = $retentionService->releaseHold($id, $this->getUser()->getUserName());

            return $this->renderJson(['success' => $result]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiDispositionAction($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $id = (int) $request->getParameter('id');
        $action = $request->getParameter('disposition_action');
        $notes = $request->getParameter('notes');

        if (!in_array($action, ['approved', 'rejected', 'pending_review'])) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid action. Must be approved, rejected, or pending_review']);
        }

        try {
            $result = $retentionService->reviewDisposition($id, $action, $this->getUser()->getUserName(), $notes);

            return $this->renderJson(['success' => $result]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiRetentionScan($request)
    {
        $this->requireAdmin();
        $retentionService = $this->getRetentionService();

        $policyId = $request->getParameter('policy_id') ? (int) $request->getParameter('policy_id') : null;

        try {
            $count = $retentionService->scanEligible($policyId);

            return $this->renderJson(['success' => true, 'queued' => $count]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // API endpoints (Issue #190: Alerts)
    // ------------------------------------------------------------------

    public function executeApiAlertSave($request)
    {
        $this->requireAdmin();
        $alertService = $this->getAlertService();

        $id = $request->getParameter('id');
        $data = [
            'alert_type' => $request->getParameter('alert_type'),
            'threshold_value' => $request->getParameter('threshold_value'),
            'comparison' => $request->getParameter('comparison', 'gt'),
            'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
            'email' => $request->getParameter('email') ?: null,
            'webhook_url' => $request->getParameter('webhook_url') ?: null,
            'webhook_secret' => $request->getParameter('webhook_secret') ?: null,
        ];

        try {
            if ($id) {
                $alertService->updateAlertConfig((int) $id, $data);

                return $this->renderJson(['success' => true, 'id' => (int) $id]);
            }

            $newId = $alertService->createAlertConfig($data);

            return $this->renderJson(['success' => true, 'id' => $newId]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeApiAlertDelete($request)
    {
        $this->requireAdmin();
        $alertService = $this->getAlertService();

        $id = (int) $request->getParameter('id');

        try {
            $alertService->deleteAlertConfig($id);

            return $this->renderJson(['success' => true]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // Issue #191: Paginated list API endpoints
    // ------------------------------------------------------------------

    public function executeApiLedger($request)
    {
        $this->requireAdmin();

        $limit = min((int) ($request->getParameter('limit') ?: 50), 500);
        $skip = max((int) ($request->getParameter('skip') ?: 0), 0);

        $query = DB::table('integrity_ledger');

        if ($request->getParameter('repository_id')) {
            $query->where('repository_id', (int) $request->getParameter('repository_id'));
        }
        if ($request->getParameter('outcome')) {
            $query->where('outcome', $request->getParameter('outcome'));
        }
        if ($request->getParameter('date_from')) {
            $query->where('verified_at', '>=', $request->getParameter('date_from') . ' 00:00:00');
        }
        if ($request->getParameter('date_to')) {
            $query->where('verified_at', '<=', $request->getParameter('date_to') . ' 23:59:59');
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')->skip($skip)->limit($limit)->get()->values()->all();

        return $this->renderJson([
            'success' => true,
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'data' => $rows,
        ]);
    }

    public function executeApiRuns($request)
    {
        $this->requireAdmin();

        $limit = min((int) ($request->getParameter('limit') ?: 50), 200);
        $skip = max((int) ($request->getParameter('skip') ?: 0), 0);

        $query = DB::table('integrity_run')
            ->leftJoin('integrity_schedule', 'integrity_run.schedule_id', '=', 'integrity_schedule.id')
            ->select('integrity_run.*', 'integrity_schedule.name as schedule_name');

        if ($request->getParameter('status')) {
            $query->where('integrity_run.status', $request->getParameter('status'));
        }

        $total = DB::table('integrity_run');
        if ($request->getParameter('status')) {
            $total->where('status', $request->getParameter('status'));
        }
        $totalCount = $total->count();

        $rows = $query->orderByDesc('integrity_run.started_at')->skip($skip)->limit($limit)->get()->values()->all();

        return $this->renderJson([
            'success' => true,
            'total' => $totalCount,
            'limit' => $limit,
            'skip' => $skip,
            'data' => $rows,
        ]);
    }

    public function executeApiHolds($request)
    {
        $this->requireAdmin();

        $limit = min((int) ($request->getParameter('limit') ?: 50), 200);
        $skip = max((int) ($request->getParameter('skip') ?: 0), 0);

        $query = DB::table('integrity_legal_hold');
        if ($request->getParameter('status')) {
            $query->where('status', $request->getParameter('status'));
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('placed_at')->skip($skip)->limit($limit)->get()->values()->all();

        return $this->renderJson([
            'success' => true,
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'data' => $rows,
        ]);
    }

    public function executeApiPolicies($request)
    {
        $this->requireAdmin();

        $limit = min((int) ($request->getParameter('limit') ?: 50), 200);
        $skip = max((int) ($request->getParameter('skip') ?: 0), 0);

        $total = DB::table('integrity_retention_policy')->count();
        $rows = DB::table('integrity_retention_policy')
            ->orderBy('id')
            ->skip($skip)
            ->limit($limit)
            ->get()
            ->values()
            ->all();

        return $this->renderJson([
            'success' => true,
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'data' => $rows,
        ]);
    }

    public function executeApiDailyTrend($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $days = min((int) ($request->getParameter('days') ?: 30), 365);
        $data = $service->getDailyTrend($days);

        return $this->renderJson(['success' => true, 'data' => $data]);
    }

    public function executeApiRepoBreakdown($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        return $this->renderJson(['success' => true, 'data' => $service->getRepositoryBreakdown()]);
    }

    public function executeApiFormatBreakdown($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        return $this->renderJson(['success' => true, 'data' => $service->getFormatBreakdown()]);
    }

    public function executeApiThroughput($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $days = min((int) ($request->getParameter('days') ?: 7), 90);
        $data = $service->calculateThroughput($days);

        return $this->renderJson(['success' => true, 'data' => $data]);
    }

    public function executeApiStorageGrowth($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $days = min((int) ($request->getParameter('days') ?: 30), 365);
        $data = $service->getStorageGrowth($days);

        return $this->renderJson(['success' => true, 'data' => $data]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function renderJson(array $data, int $status = 200)
    {
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data));

        return sfView::NONE;
    }
}
