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
        require_once dirname(__DIR__, 2) . '/lib/Services/IntegrityService.php';

        return new IntegrityService();
    }

    // ------------------------------------------------------------------
    // Page actions
    // ------------------------------------------------------------------

    public function executeIndex($request)
    {
        $this->requireAdmin();
        $service = $this->getService();

        $this->stats = $service->getDashboardStats();
        $this->recentRuns = $service->getRecentRuns(10);
        $this->recentFailures = $service->getRecentFailures(10);
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
    // API endpoints
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
    // Helpers
    // ------------------------------------------------------------------

    protected function renderJson(array $data)
    {
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data));

        return sfView::NONE;
    }
}
