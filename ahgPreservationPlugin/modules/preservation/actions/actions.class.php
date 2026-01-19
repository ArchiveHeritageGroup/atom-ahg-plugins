<?php

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
            ->leftJoin('information_object as io', 'do.information_object_id', '=', 'io.id')
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
            ->leftJoin('information_object as io', 'do.information_object_id', '=', 'io.id')
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
}
