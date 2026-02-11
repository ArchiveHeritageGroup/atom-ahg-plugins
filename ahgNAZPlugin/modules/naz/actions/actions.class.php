<?php

/**
 * NAZ Module Actions
 *
 * Admin interface for National Archives of Zimbabwe Act compliance:
 * - Dashboard with compliance status
 * - Closure period management
 * - Research permit administration
 * - Researcher registry
 * - Records schedule management
 * - Transfer tracking
 * - Protected records
 */
class nazActions extends AhgActions
{
    /**
     * Get the NAZ service
     */
    protected function getService(): \AhgNAZ\Services\NAZService
    {
        return new \AhgNAZ\Services\NAZService();
    }

    /**
     * Main dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();

        $service = $this->getService();

        $this->stats = $service->getDashboardStats();
        $this->compliance = $service->getComplianceStatus();
        $this->config = $service->getAllConfig();

        // Get pending items for quick view
        $this->pendingPermits = \Illuminate\Database\Capsule\Manager::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->where('p.status', 'pending')
            ->select(['p.*', 'r.first_name', 'r.last_name', 'r.researcher_type'])
            ->orderBy('p.created_at')
            ->limit(5)
            ->get();

        // Get expiring closures
        $this->expiringClosures = \Illuminate\Database\Capsule\Manager::table('naz_closure_period as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('c.status', 'active')
            ->whereNotNull('c.end_date')
            ->whereRaw('c.end_date <= DATE_ADD(CURDATE(), INTERVAL 1 YEAR)')
            ->select(['c.*', 'ioi.title as record_title'])
            ->orderBy('c.end_date')
            ->limit(5)
            ->get();
    }

    // =========================================================================
    // CLOSURE PERIODS
    // =========================================================================

    public function executeClosures(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'status' => $request->getParameter('status'),
            'type' => $request->getParameter('type'),
        ];

        $this->closures = $this->getService()->getClosures($filters);
        $this->currentStatus = $filters['status'];
        $this->currentType = $filters['type'];
    }

    public function executeClosureCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $service = $this->getService();

            $startDate = $request->getParameter('start_date');
            $years = (int) $request->getParameter('years', 25);
            $closureType = $request->getParameter('closure_type', 'standard');

            $endDate = null;
            if ('indefinite' !== $closureType) {
                $endDate = date('Y-m-d', strtotime("+{$years} years", strtotime($startDate)));
            }

            $id = $service->createClosure([
                'information_object_id' => $request->getParameter('information_object_id'),
                'closure_type' => $closureType,
                'closure_reason' => $request->getParameter('closure_reason'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'years' => $years,
                'authority_reference' => $request->getParameter('authority_reference'),
                'review_date' => $request->getParameter('review_date') ?: null,
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'naz', 'action' => 'closures']);
        }
    }

    public function executeClosureEdit(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->closure = \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
            ->where('id', $id)
            ->first();

        if (!$this->closure) {
            $this->forward404('Closure period not found');
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ('release' === $action) {
                $this->getService()->releaseClosure(
                    $id,
                    $this->getUser()->getAttribute('user_id'),
                    $request->getParameter('release_notes')
                );
            } else {
                // Update closure
                \Illuminate\Database\Capsule\Manager::table('naz_closure_period')
                    ->where('id', $id)
                    ->update([
                        'closure_reason' => $request->getParameter('closure_reason'),
                        'review_date' => $request->getParameter('review_date') ?: null,
                        'authority_reference' => $request->getParameter('authority_reference'),
                    ]);
            }

            $this->redirect(['module' => 'naz', 'action' => 'closures']);
        }
    }

    // =========================================================================
    // RESEARCHERS
    // =========================================================================

    public function executeResearchers(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'type' => $request->getParameter('type'),
            'status' => $request->getParameter('status'),
            'search' => $request->getParameter('q'),
        ];

        $this->researchers = $this->getService()->getResearchers($filters);
        $this->currentType = $filters['type'];
        $this->currentStatus = $filters['status'];
        $this->search = $filters['search'];
    }

    public function executeResearcherCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createResearcher([
                'researcher_type' => $request->getParameter('researcher_type'),
                'title' => $request->getParameter('title'),
                'first_name' => $request->getParameter('first_name'),
                'last_name' => $request->getParameter('last_name'),
                'email' => $request->getParameter('email'),
                'phone' => $request->getParameter('phone'),
                'nationality' => $request->getParameter('nationality'),
                'passport_number' => $request->getParameter('passport_number'),
                'national_id' => $request->getParameter('national_id'),
                'institution' => $request->getParameter('institution'),
                'position' => $request->getParameter('position'),
                'address' => $request->getParameter('address'),
                'city' => $request->getParameter('city'),
                'country' => $request->getParameter('country'),
                'research_interests' => $request->getParameter('research_interests'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'naz', 'action' => 'researcherView', 'id' => $id]);
        }
    }

    public function executeResearcherView(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->researcher = $this->getService()->getResearcher($id);

        if (!$this->researcher) {
            $this->forward404('Researcher not found');
        }

        // Get permits for this researcher
        $this->permits = $this->getService()->getPermits(['researcher_id' => $id]);

        // Get visits
        $this->visits = $this->getService()->getResearcherVisits($id);
    }

    // =========================================================================
    // RESEARCH PERMITS
    // =========================================================================

    public function executePermits(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'status' => $request->getParameter('status'),
        ];

        $this->permits = $this->getService()->getPermits($filters);
        $this->currentStatus = $filters['status'];
    }

    public function executePermitCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        // Get researchers for dropdown
        $this->researchers = $this->getService()->getResearchers(['status' => 'active']);

        if ($request->isMethod('post')) {
            $service = $this->getService();

            // Calculate default end date (1 year from start)
            $startDate = $request->getParameter('start_date');
            $validityMonths = (int) $service->getConfig('permit_validity_months', 12);
            $endDate = $request->getParameter('end_date') ?: date('Y-m-d', strtotime("+{$validityMonths} months", strtotime($startDate)));

            $id = $service->createPermit([
                'researcher_id' => $request->getParameter('researcher_id'),
                'permit_type' => $request->getParameter('permit_type', 'general'),
                'research_topic' => $request->getParameter('research_topic'),
                'research_purpose' => $request->getParameter('research_purpose'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'restrictions' => $request->getParameter('restrictions'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'naz', 'action' => 'permitView', 'id' => $id]);
        }
    }

    public function executePermitView(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->permit = $this->getService()->getPermit($id);

        if (!$this->permit) {
            $this->forward404('Permit not found');
        }

        // Handle actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            $service = $this->getService();
            $userId = $this->getUser()->getAttribute('user_id');

            switch ($action) {
                case 'approve':
                    $service->approvePermit($id, $userId);
                    break;
                case 'reject':
                    \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                        ->where('id', $id)
                        ->update([
                            'status' => 'rejected',
                            'rejection_reason' => $request->getParameter('rejection_reason'),
                        ]);
                    break;
                case 'payment':
                    $service->recordPermitPayment($id, $request->getParameter('receipt_number'), $userId);
                    break;
                case 'revoke':
                    \Illuminate\Database\Capsule\Manager::table('naz_research_permit')
                        ->where('id', $id)
                        ->update(['status' => 'revoked']);
                    break;
            }

            $this->redirect(['module' => 'naz', 'action' => 'permitView', 'id' => $id]);
        }
    }

    // =========================================================================
    // RECORDS SCHEDULES
    // =========================================================================

    public function executeSchedules(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'status' => $request->getParameter('status'),
            'agency' => $request->getParameter('agency'),
            'disposal_action' => $request->getParameter('disposal'),
        ];

        $this->schedules = $this->getService()->getSchedules($filters);
        $this->currentStatus = $filters['status'];
    }

    public function executeScheduleCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createSchedule([
                'agency_name' => $request->getParameter('agency_name'),
                'agency_code' => $request->getParameter('agency_code'),
                'record_series' => $request->getParameter('record_series'),
                'description' => $request->getParameter('description'),
                'retention_period_active' => $request->getParameter('retention_period_active'),
                'retention_period_semi' => $request->getParameter('retention_period_semi', 0),
                'disposal_action' => $request->getParameter('disposal_action'),
                'legal_authority' => $request->getParameter('legal_authority'),
                'classification' => $request->getParameter('classification', 'useful'),
                'access_restriction' => $request->getParameter('access_restriction', 'open'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'naz', 'action' => 'scheduleView', 'id' => $id]);
        }
    }

    public function executeScheduleView(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->schedule = $this->getService()->getSchedule($id);

        if (!$this->schedule) {
            $this->forward404('Schedule not found');
        }

        // Handle approval
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ('approve' === $action) {
                \Illuminate\Database\Capsule\Manager::table('naz_records_schedule')
                    ->where('id', $id)
                    ->update([
                        'status' => 'approved',
                        'approved_by' => $request->getParameter('approved_by'),
                        'approval_date' => date('Y-m-d'),
                        'effective_date' => $request->getParameter('effective_date') ?: date('Y-m-d'),
                    ]);
            }

            $this->redirect(['module' => 'naz', 'action' => 'scheduleView', 'id' => $id]);
        }
    }

    // =========================================================================
    // TRANSFERS
    // =========================================================================

    public function executeTransfers(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'status' => $request->getParameter('status'),
            'agency' => $request->getParameter('agency'),
        ];

        $this->transfers = $this->getService()->getTransfers($filters);
        $this->currentStatus = $filters['status'];
    }

    public function executeTransferCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        // Get approved schedules for dropdown
        $this->schedules = $this->getService()->getSchedules(['status' => 'approved']);

        if ($request->isMethod('post')) {
            $id = $this->getService()->createTransfer([
                'transferring_agency' => $request->getParameter('transferring_agency'),
                'agency_contact' => $request->getParameter('agency_contact'),
                'agency_email' => $request->getParameter('agency_email'),
                'agency_phone' => $request->getParameter('agency_phone'),
                'schedule_id' => $request->getParameter('schedule_id') ?: null,
                'transfer_type' => $request->getParameter('transfer_type', 'scheduled'),
                'description' => $request->getParameter('description'),
                'date_range_start' => $request->getParameter('date_range_start'),
                'date_range_end' => $request->getParameter('date_range_end'),
                'quantity_linear_metres' => $request->getParameter('quantity_linear_metres'),
                'quantity_boxes' => $request->getParameter('quantity_boxes'),
                'quantity_items' => $request->getParameter('quantity_items'),
                'contains_restricted' => $request->getParameter('contains_restricted') ? 1 : 0,
                'restriction_details' => $request->getParameter('restriction_details'),
                'proposed_date' => $request->getParameter('proposed_date'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'naz', 'action' => 'transferView', 'id' => $id]);
        }
    }

    public function executeTransferView(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->transfer = $this->getService()->getTransfer($id);

        if (!$this->transfer) {
            $this->forward404('Transfer not found');
        }

        $this->items = $this->getService()->getTransferItems($id);

        // Handle status updates
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');
            $service = $this->getService();
            $userId = $this->getUser()->getAttribute('user_id');

            $extraData = [];
            if ('accessioned' === $action) {
                $extraData['accession_number'] = $request->getParameter('accession_number');
                $extraData['location_assigned'] = $request->getParameter('location_assigned');
            }

            $service->updateTransferStatus($id, $action, $userId, $extraData);

            $this->redirect(['module' => 'naz', 'action' => 'transferView', 'id' => $id]);
        }
    }

    // =========================================================================
    // PROTECTED RECORDS
    // =========================================================================

    public function executeProtectedRecords(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'status' => $request->getParameter('status', 'active'),
            'type' => $request->getParameter('type'),
        ];

        $this->records = $this->getService()->getProtectedRecords($filters);
        $this->currentStatus = $filters['status'];
        $this->currentType = $filters['type'];
    }

    // =========================================================================
    // REPORTS
    // =========================================================================

    public function executeReports(sfWebRequest $request)
    {
        $this->checkAdmin();

        $this->reportType = $request->getParameter('type', 'summary');
        $this->year = $request->getParameter('year', date('Y'));
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    public function executeConfig(sfWebRequest $request)
    {
        $this->checkAdmin();

        $service = $this->getService();

        if ($request->isMethod('post')) {
            $configs = [
                'closure_period_years' => $request->getParameter('closure_period_years'),
                'foreign_permit_fee_usd' => $request->getParameter('foreign_permit_fee_usd'),
                'local_permit_fee_usd' => $request->getParameter('local_permit_fee_usd'),
                'permit_validity_months' => $request->getParameter('permit_validity_months'),
                'transfer_reminder_months' => $request->getParameter('transfer_reminder_months'),
                'naz_repository_name' => $request->getParameter('naz_repository_name'),
                'director_name' => $request->getParameter('director_name'),
                'naz_email' => $request->getParameter('naz_email'),
                'naz_phone' => $request->getParameter('naz_phone'),
            ];

            foreach ($configs as $key => $value) {
                if (null !== $value) {
                    $service->setConfig($key, $value);
                }
            }

            $this->getUser()->setFlash('notice', 'Configuration saved successfully');
            $this->redirect(['module' => 'naz', 'action' => 'config']);
        }

        $this->config = $service->getAllConfig();
    }

    /**
     * Check if user is admin
     */
    protected function checkAdmin(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }
}
