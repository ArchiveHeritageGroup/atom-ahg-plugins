<?php

use Illuminate\Database\Capsule\Manager as DB;

class preservationActions extends sfActions
{
    private PreservationService $service;

    public function preExecute()
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPreservationPlugin/lib/PreservationService.php';
        $this->service = new PreservationService();
    }

    /**
     * Check admin access
     */
    private function checkAdminAccess()
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    // =========================================
    // DASHBOARD & UI ACTIONS
    // =========================================

    /**
     * Preservation dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->stats = $this->service->getStatistics();
        $this->recentEvents = DB::table('preservation_event')
            ->orderBy('event_datetime', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        $this->recentFixityChecks = $this->service->getFixityLog(10);

        // Objects at risk (failed fixity or risky formats)
        $this->atRiskObjects = DB::table('preservation_fixity_check as fc')
            ->join('digital_object as do', 'fc.digital_object_id', '=', 'do.id')
            ->leftJoin('information_object as io', 'do.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as io_i18n', function ($join) {
                $join->on('io.id', '=', 'io_i18n.id')
                     ->where('io_i18n.culture', '=', 'en');
            })
            ->where('fc.status', 'fail')
            ->where('fc.checked_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->select('do.id', 'do.name as filename', 'io_i18n.title', 'fc.checked_at', 'fc.error_message')
            ->orderBy('fc.checked_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * View preservation details for a single object
     */
    public function executeObject(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = $request->getParameter('id');

        $this->digitalObject = DB::table('digital_object as do')
            ->leftJoin('information_object as io', 'do.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as io_i18n', function ($join) {
                $join->on('io.id', '=', 'io_i18n.id')
                     ->where('io_i18n.culture', '=', 'en');
            })
            ->where('do.id', $id)
            ->select('do.*', 'io_i18n.title as object_title', 'io.slug')
            ->first();

        if (!$this->digitalObject) {
            $this->forward404('Digital object not found');
        }

        $this->checksums = $this->service->getChecksums($id);
        $this->events = $this->service->getEvents($id, 20);
        $this->fixityHistory = DB::table('preservation_fixity_check')
            ->where('digital_object_id', $id)
            ->orderBy('checked_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $this->formatInfo = DB::table('preservation_object_format as pof')
            ->leftJoin('preservation_format as pf', 'pof.format_id', '=', 'pf.id')
            ->where('pof.digital_object_id', $id)
            ->select('pof.*', 'pf.risk_level', 'pf.is_preservation_format', 'pf.preservation_action')
            ->first();
    }

    /**
     * Fixity check log
     */
    public function executeFixityLog(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $status = $request->getParameter('status');
        $this->currentStatus = $status;
        $this->checks = $this->service->getFixityLog(100, $status);

        $this->statusCounts = [
            'all' => DB::table('preservation_fixity_check')->count(),
            'pass' => DB::table('preservation_fixity_check')->where('status', 'pass')->count(),
            'fail' => DB::table('preservation_fixity_check')->where('status', 'fail')->count(),
            'error' => DB::table('preservation_fixity_check')->where('status', 'error')->count(),
        ];
    }

    /**
     * Preservation events log
     */
    public function executeEvents(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $eventType = $request->getParameter('type');

        $query = DB::table('preservation_event as pe')
            ->leftJoin('digital_object as do', 'pe.digital_object_id', '=', 'do.id')
            ->leftJoin('information_object as io', 'pe.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as io_i18n', function ($join) {
                $join->on('io.id', '=', 'io_i18n.id')
                     ->where('io_i18n.culture', '=', 'en');
            })
            ->select('pe.*', 'do.name as filename', 'io_i18n.title as object_title')
            ->orderBy('pe.event_datetime', 'desc')
            ->limit(200);

        if ($eventType) {
            $query->where('pe.event_type', $eventType);
        }

        $this->events = $query->get()->toArray();
        $this->currentType = $eventType;

        $this->eventTypes = DB::table('preservation_event')
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Format registry
     */
    public function executeFormats(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->formats = DB::table('preservation_format')
            ->orderBy('risk_level', 'desc')
            ->orderBy('format_name')
            ->get()
            ->toArray();

        // Get usage counts
        $this->formatCounts = DB::table('preservation_object_format')
            ->select('format_id', DB::raw('COUNT(*) as count'))
            ->groupBy('format_id')
            ->get()
            ->keyBy('format_id')
            ->toArray();
    }

    /**
     * Preservation policies
     */
    public function executePolicies(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->policies = DB::table('preservation_policy')
            ->orderBy('policy_type')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Preservation reports
     */
    public function executeReports(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->stats = $this->service->getStatistics();

        // Objects without checksums
        $this->objectsWithoutChecksums = DB::table('digital_object as do')
            ->leftJoin('preservation_checksum as pc', 'do.id', '=', 'pc.digital_object_id')
            ->whereNull('pc.id')
            ->select('do.id', 'do.name', 'do.byte_size')
            ->limit(50)
            ->get()
            ->toArray();

        // Objects with stale verification
        $this->staleVerification = DB::table('preservation_checksum as pc')
            ->join('digital_object as do', 'pc.digital_object_id', '=', 'do.id')
            ->where('pc.verified_at', '<', date('Y-m-d', strtotime('-30 days')))
            ->select('do.id', 'do.name', 'pc.algorithm', 'pc.verified_at')
            ->limit(50)
            ->get()
            ->toArray();

        // High risk formats
        $this->highRiskObjects = DB::table('preservation_object_format as pof')
            ->join('preservation_format as pf', 'pof.format_id', '=', 'pf.id')
            ->join('digital_object as do', 'pof.digital_object_id', '=', 'do.id')
            ->whereIn('pf.risk_level', ['high', 'critical'])
            ->select('do.id', 'do.name', 'pf.format_name', 'pf.risk_level')
            ->limit(50)
            ->get()
            ->toArray();
    }

    // =========================================
    // API ACTIONS
    // =========================================

    /**
     * API: Generate checksums for a digital object
     */
    public function executeApiGenerateChecksum(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = $request->getParameter('id');
        $algorithms = $request->getParameter('algorithms', 'sha256');
        $algorithms = is_array($algorithms) ? $algorithms : explode(',', $algorithms);

        try {
            $results = $this->service->generateChecksums($id, $algorithms);

            return $this->renderText(json_encode([
                'success' => true,
                'checksums' => $results,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Verify fixity for a digital object
     */
    public function executeApiVerifyFixity(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = $request->getParameter('id');
        $algorithm = $request->getParameter('algorithm');
        $checkedBy = $this->getUser()->getAttribute('user_name', 'user');

        try {
            $results = $this->service->verifyFixity($id, $algorithm, $checkedBy);

            return $this->renderText(json_encode([
                'success' => true,
                'results' => $results,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Get preservation statistics
     */
    public function executeApiStats(sfWebRequest $request)
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->renderText(json_encode([
                'success' => true,
                'stats' => $stats,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    // =========================================
    // VIRUS SCANNING
    // =========================================

    /**
     * Virus scan dashboard
     */
    public function executeVirusScan(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->clamAvAvailable = $this->service->isClamAvAvailable();
        $this->clamAvVersion = $this->clamAvAvailable ? $this->service->getClamAvVersion() : null;

        // Get scan statistics
        $this->scanStats = DB::table('preservation_virus_scan')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent scans
        $this->recentScans = DB::table('preservation_virus_scan as pvs')
            ->join('digital_object as do', 'pvs.digital_object_id', '=', 'do.id')
            ->select('pvs.*', 'do.name as filename')
            ->orderBy('pvs.scanned_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Objects never scanned
        $this->unscannedobjects = DB::table('digital_object as do')
            ->leftJoin('preservation_virus_scan as pvs', 'do.id', '=', 'pvs.digital_object_id')
            ->where('do.usage_id', 140)
            ->whereNull('pvs.id')
            ->count();
    }

    /**
     * API: Scan single object for virus
     */
    public function executeApiVirusScan(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = $request->getParameter('id');
        $quarantine = $request->getParameter('quarantine', true);
        $scannedBy = $this->getUser()->getAttribute('user_name', 'user');

        try {
            $result = $this->service->scanForVirus($id, $quarantine, $scannedBy);

            return $this->renderText(json_encode([
                'success' => true,
                'result' => $result,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    // =========================================
    // FORMAT CONVERSION
    // =========================================

    /**
     * Format conversion dashboard
     */
    public function executeConversion(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->tools = $this->service->getConversionTools();

        // Conversion statistics
        $this->conversionStats = DB::table('preservation_format_conversion')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent conversions
        $this->recentConversions = DB::table('preservation_format_conversion as pfc')
            ->join('digital_object as do', 'pfc.digital_object_id', '=', 'do.id')
            ->select('pfc.*', 'do.name as filename')
            ->orderBy('pfc.created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Objects needing conversion
        $this->pendingConversions = DB::table('digital_object as do')
            ->join('preservation_format as pf', 'do.mime_type', '=', 'pf.mime_type')
            ->leftJoin('preservation_format_conversion as pfc', function ($join) {
                $join->on('do.id', '=', 'pfc.digital_object_id')
                    ->where('pfc.status', '=', 'completed');
            })
            ->where('do.usage_id', 140)
            ->whereNotNull('pf.migration_target_id')
            ->whereNull('pfc.id')
            ->count();
    }

    /**
     * API: Convert single object format
     */
    public function executeApiConvert(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = $request->getParameter('id');
        $targetFormat = $request->getParameter('format');
        $quality = $request->getParameter('quality', 95);
        $createdBy = $this->getUser()->getAttribute('user_name', 'user');

        try {
            $result = $this->service->convertFormat($id, $targetFormat, ['quality' => $quality], $createdBy);

            return $this->renderText(json_encode([
                'success' => true,
                'result' => $result,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    // =========================================
    // BACKUP & REPLICATION
    // =========================================

    /**
     * Backup verification dashboard
     */
    public function executeBackup(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        // Verification statistics
        $this->verificationStats = DB::table('preservation_backup_verification')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent verifications
        $this->recentVerifications = DB::table('preservation_backup_verification')
            ->orderBy('verified_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Replication targets
        $this->replicationTargets = DB::table('preservation_replication_target')
            ->get()
            ->toArray();

        // Recent replication logs
        $this->recentReplications = DB::table('preservation_replication_log as prl')
            ->join('preservation_replication_target as prt', 'prl.target_id', '=', 'prt.id')
            ->select('prl.*', 'prt.name as target_name', 'prt.target_type')
            ->orderBy('prl.started_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * API: Verify specific backup
     */
    public function executeApiVerifyBackup(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $path = $request->getParameter('path');
        $type = $request->getParameter('type', 'full');
        $checksum = $request->getParameter('checksum');
        $verifiedBy = $this->getUser()->getAttribute('user_name', 'user');

        try {
            $result = $this->service->verifyBackup($path, $type, $checksum, $verifiedBy);

            return $this->renderText(json_encode([
                'success' => true,
                'result' => $result,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Extended dashboard with all new features
     */
    public function executeExtended(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->stats = $this->service->getExtendedStatistics();
        $this->clamAvAvailable = $this->service->isClamAvAvailable();
        $this->tools = $this->service->getConversionTools();
    }

    // =========================================
    // FORMAT IDENTIFICATION (SIEGFRIED/PRONOM)
    // =========================================

    /**
     * Format identification dashboard
     */
    public function executeIdentification(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->stats = $this->service->getIdentificationStatistics();
        $this->siegfriedAvailable = $this->service->isSiegfriedAvailable();
        $this->siegfriedVersion = $this->service->getSiegfriedVersion();

        // Recent identifications
        $this->recentIdentifications = $this->service->getIdentificationLog(20);

        // Identifications with warnings
        $this->identificationsWithWarnings = DB::table('preservation_object_format as pof')
            ->join('digital_object as do', 'pof.digital_object_id', '=', 'do.id')
            ->whereNotNull('pof.warning')
            ->where('pof.warning', '!=', '')
            ->select('pof.*', 'do.name as object_name')
            ->orderByDesc('pof.identification_date')
            ->limit(10)
            ->get()
            ->toArray();

        // Top 10 formats
        $this->topFormats = DB::table('preservation_object_format')
            ->selectRaw('puid, format_name, COUNT(*) as count')
            ->groupBy('puid', 'format_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        // Format registry by risk level
        $this->formatsByRisk = DB::table('preservation_format')
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();
    }

    /**
     * API: Identify a single object
     */
    public function executeApiIdentify(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $objectId = (int) $request->getParameter('object_id');
        $reidentify = $request->getParameter('reidentify') === 'true';

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'object_id is required',
            ]));
        }

        try {
            if ($reidentify) {
                $result = $this->service->reidentifyFormat($objectId);
            } else {
                $result = $this->service->identifyFormat($objectId);
            }

            return $this->renderText(json_encode($result));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    // =========================================
    // WORKFLOW SCHEDULER UI
    // =========================================

    /**
     * Workflow Scheduler Dashboard
     */
    public function executeScheduler(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->schedules = $this->service->getWorkflowSchedules();
        $this->stats = $this->service->getSchedulerStatistics();
        $this->recentRuns = $this->service->getWorkflowRuns(null, 20);

        // Add workflow type info to each schedule
        foreach ($this->schedules as &$schedule) {
            $schedule->typeInfo = $this->service->getWorkflowTypeInfo($schedule->workflow_type);
            $schedule->scheduleDescription = $schedule->cron_expression
                ? $this->service->describeCronExpression($schedule->cron_expression)
                : 'Manual only';
        }
    }

    /**
     * View/Edit single schedule
     */
    public function executeScheduleEdit(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');

        if ($id) {
            $this->schedule = $this->service->getWorkflowSchedule($id);
            if (!$this->schedule) {
                $this->forward404('Schedule not found');
            }
            $this->runs = $this->service->getWorkflowRuns($id, 10);
        } else {
            $this->schedule = null;
            $this->runs = [];
        }

        $this->workflowTypes = [
            'format_identification' => 'Format Identification (Siegfried/PRONOM)',
            'fixity_check' => 'Fixity Check (Checksums)',
            'virus_scan' => 'Virus Scan (ClamAV)',
            'format_conversion' => 'Format Conversion',
            'backup_verification' => 'Backup Verification',
            'replication' => 'Replication',
        ];

        // Handle form submission
        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'workflow_type' => $request->getParameter('workflow_type'),
                'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
                'schedule_type' => $request->getParameter('schedule_type', 'cron'),
                'cron_expression' => $request->getParameter('cron_expression'),
                'batch_limit' => (int) $request->getParameter('batch_limit', 100),
                'timeout_minutes' => (int) $request->getParameter('timeout_minutes', 60),
                'notify_on_failure' => $request->getParameter('notify_on_failure') ? 1 : 0,
                'notify_email' => $request->getParameter('notify_email'),
            ];

            try {
                if ($id) {
                    $this->service->updateWorkflowSchedule($id, $data);
                    $this->getUser()->setFlash('notice', 'Schedule updated successfully.');
                } else {
                    $data['created_by'] = $this->getUser()->getAttribute('user_name', 'admin');
                    $id = $this->service->createWorkflowSchedule($data);
                    $this->getUser()->setFlash('notice', 'Schedule created successfully.');
                }

                $this->redirect(['module' => 'preservation', 'action' => 'scheduler']);
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * API: Toggle schedule enabled/disabled
     */
    public function executeApiScheduleToggle(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');

        if (!$id) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Schedule ID is required',
            ]));
        }

        try {
            $result = $this->service->toggleWorkflowSchedule($id);
            $schedule = $this->service->getWorkflowSchedule($id);

            return $this->renderText(json_encode([
                'success' => $result,
                'is_enabled' => $schedule ? $schedule->is_enabled : null,
                'next_run_at' => $schedule ? $schedule->next_run_at : null,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Run a schedule manually
     */
    public function executeApiScheduleRun(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');

        if (!$id) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Schedule ID is required',
            ]));
        }

        try {
            $userName = $this->getUser()->getAttribute('user_name', 'admin');
            $result = $this->service->executeWorkflow($id, 'manual', $userName);

            return $this->renderText(json_encode($result));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Delete a schedule
     */
    public function executeApiScheduleDelete(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');

        if (!$id) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Schedule ID is required',
            ]));
        }

        try {
            $result = $this->service->deleteWorkflowSchedule($id);

            return $this->renderText(json_encode([
                'success' => $result,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * View workflow run details
     */
    public function executeScheduleRunView(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $runId = (int) $request->getParameter('id');

        $run = DB::table('preservation_workflow_run as r')
            ->join('preservation_workflow_schedule as s', 'r.schedule_id', '=', 's.id')
            ->where('r.id', $runId)
            ->select('r.*', 's.name as schedule_name', 's.workflow_type')
            ->first();

        if (!$run) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Run not found',
            ]));
        }

        $run->summary = $run->summary ? json_decode($run->summary, true) : null;

        return $this->renderText(json_encode([
            'success' => true,
            'run' => $run,
        ]));
    }

    // =========================================
    // OAIS PACKAGES (SIP/AIP/DIP)
    // =========================================

    /**
     * OAIS Packages Dashboard
     */
    public function executePackages(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $type = $request->getParameter('type');
        $status = $request->getParameter('status');

        $this->packages = $this->service->getPackages($type, $status, 50);
        $this->stats = $this->service->getPackageStatistics();
        $this->currentType = $type;
        $this->currentStatus = $status;
    }

    /**
     * View/Edit single package
     */
    public function executePackageEdit(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');

        if ($id) {
            $this->package = $this->service->getPackage($id);
            if (!$this->package) {
                $this->forward404('Package not found');
            }
            $this->objects = $this->service->getPackageObjects($id);
            $this->events = $this->service->getPackageEvents($id, 20);
        } else {
            $this->package = null;
            $this->objects = [];
            $this->events = [];
        }

        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action');

            try {
                if ('create' === $action) {
                    $data = [
                        'name' => $request->getParameter('name'),
                        'description' => $request->getParameter('description'),
                        'package_type' => $request->getParameter('package_type'),
                        'package_format' => $request->getParameter('package_format', 'bagit'),
                        'manifest_algorithm' => $request->getParameter('manifest_algorithm', 'sha256'),
                        'originator' => $request->getParameter('originator'),
                        'submission_agreement' => $request->getParameter('submission_agreement'),
                        'retention_period' => $request->getParameter('retention_period'),
                        'created_by' => $this->getUser()->getAttribute('user_name', 'admin'),
                    ];

                    $newId = $this->service->createPackage($data);
                    $this->getUser()->setFlash('notice', 'Package created successfully.');
                    $this->redirect(['module' => 'preservation', 'action' => 'packageEdit', 'id' => $newId]);
                } elseif ('update' === $action && $id) {
                    $data = [
                        'name' => $request->getParameter('name'),
                        'description' => $request->getParameter('description'),
                        'originator' => $request->getParameter('originator'),
                        'submission_agreement' => $request->getParameter('submission_agreement'),
                        'retention_period' => $request->getParameter('retention_period'),
                    ];

                    $this->service->updatePackage($id, $data);
                    $this->getUser()->setFlash('notice', 'Package updated successfully.');
                    $this->redirect(['module' => 'preservation', 'action' => 'packageEdit', 'id' => $id]);
                }
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Package detail view
     */
    public function executePackageView(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');

        $this->package = $this->service->getPackage($id);
        if (!$this->package) {
            $this->forward404('Package not found');
        }

        $this->objects = $this->service->getPackageObjects($id);
        $this->events = $this->service->getPackageEvents($id, 50);

        // Get parent/child packages
        $this->parentPackage = null;
        $this->childPackages = [];

        if ($this->package->parent_package_id) {
            $this->parentPackage = $this->service->getPackage($this->package->parent_package_id);
        }

        $this->childPackages = DB::table('preservation_package')
            ->where('parent_package_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * API: Add object to package
     */
    public function executeApiPackageAddObject(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');
        $objectId = (int) $request->getParameter('object_id');

        if (!$packageId || !$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id and object_id are required',
            ]));
        }

        try {
            $result = $this->service->addObjectToPackage($packageId, $objectId);

            return $this->renderText(json_encode([
                'success' => true,
                'id' => $result,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Remove object from package
     */
    public function executeApiPackageRemoveObject(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');
        $objectId = (int) $request->getParameter('object_id');

        if (!$packageId || !$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id and object_id are required',
            ]));
        }

        try {
            $result = $this->service->removeObjectFromPackage($packageId, $objectId);

            return $this->renderText(json_encode([
                'success' => $result,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Build package
     */
    public function executeApiPackageBuild(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');

        if (!$packageId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id is required',
            ]));
        }

        try {
            $result = $this->service->buildBagItPackage($packageId);

            return $this->renderText(json_encode($result));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Validate package
     */
    public function executeApiPackageValidate(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');

        if (!$packageId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id is required',
            ]));
        }

        try {
            $result = $this->service->validateBagItPackage($packageId);

            return $this->renderText(json_encode($result));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Export package
     */
    public function executeApiPackageExport(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');
        $format = $request->getParameter('format', 'zip');

        if (!$packageId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id is required',
            ]));
        }

        try {
            $result = $this->service->exportPackage($packageId, $format);

            return $this->renderText(json_encode($result));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Delete package
     */
    public function executeApiPackageDelete(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');

        if (!$packageId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id is required',
            ]));
        }

        try {
            $result = $this->service->deletePackage($packageId);

            return $this->renderText(json_encode([
                'success' => $result,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Convert package (SIP->AIP or AIP->DIP)
     */
    public function executeApiPackageConvert(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $packageId = (int) $request->getParameter('package_id');
        $targetType = $request->getParameter('target_type');

        if (!$packageId || !$targetType) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'package_id and target_type are required',
            ]));
        }

        try {
            $package = $this->service->getPackage($packageId);

            if ('aip' === $targetType && 'sip' === $package->package_type) {
                $newId = $this->service->convertSipToAip($packageId, [
                    'created_by' => $this->getUser()->getAttribute('user_name', 'admin'),
                ]);
            } elseif ('dip' === $targetType && 'aip' === $package->package_type) {
                $newId = $this->service->createDipFromAip($packageId, [
                    'created_by' => $this->getUser()->getAttribute('user_name', 'admin'),
                ]);
            } else {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error' => "Invalid conversion: {$package->package_type} -> {$targetType}",
                ]));
            }

            $newPackage = $this->service->getPackage($newId);

            return $this->renderText(json_encode([
                'success' => true,
                'new_package_id' => $newId,
                'new_package_uuid' => $newPackage->uuid,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Download package export file
     */
    public function executePackageDownload(sfWebRequest $request)
    {
        $this->checkAdminAccess();

        $packageId = (int) $request->getParameter('id');
        $package = $this->service->getPackage($packageId);

        if (!$package || !$package->export_path || !file_exists($package->export_path)) {
            $this->forward404('Package export not found');
        }

        $filename = basename($package->export_path);
        $this->getResponse()->setHttpHeader('Content-Type', 'application/octet-stream');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $this->getResponse()->setHttpHeader('Content-Length', filesize($package->export_path));

        $this->getResponse()->sendHttpHeaders();
        readfile($package->export_path);

        return sfView::NONE;
    }
}
