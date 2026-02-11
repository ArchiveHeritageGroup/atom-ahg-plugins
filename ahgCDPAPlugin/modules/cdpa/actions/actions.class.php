<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CDPA module actions - Zimbabwe Cyber and Data Protection Act compliance.
 */
class cdpaActions extends AhgController
{
    /**
     * Dashboard.
     */
    public function executeIndex($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->stats = $service->getDashboardStats();
        $this->compliance = $service->getComplianceStatus();
        $this->pendingRequests = $service->getPendingRequests()->take(5);
        $this->recentBreaches = $service->getBreaches()->take(5);
    }

    /**
     * License management.
     */
    public function executeLicense($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->license = $service->getCurrentLicense();
        $this->licenseStatus = $this->license ? $service->getLicenseStatus($this->license) : null;
    }

    /**
     * Edit license.
     */
    public function executeLicenseEdit($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->license = $service->getCurrentLicense();

        if ($request->isMethod('post')) {
            $service->saveLicense([
                'license_number' => $request->getParameter('license_number'),
                'tier' => $request->getParameter('tier'),
                'organization_name' => $request->getParameter('organization_name'),
                'registration_date' => $request->getParameter('registration_date'),
                'issue_date' => $request->getParameter('issue_date'),
                'expiry_date' => $request->getParameter('expiry_date'),
                'potraz_ref' => $request->getParameter('potraz_ref'),
                'data_subjects_count' => $request->getParameter('data_subjects_count'),
                'notes' => $request->getParameter('notes'),
            ]);

            $this->getUser()->setFlash('notice', 'License information saved.');
            $this->redirect(['module' => 'cdpa', 'action' => 'license']);
        }
    }

    /**
     * DPO management.
     */
    public function executeDpo($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->dpo = $service->getActiveDPO();
    }

    /**
     * Edit DPO.
     */
    public function executeDpoEdit($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->dpo = $service->getActiveDPO();

        if ($request->isMethod('post')) {
            $service->saveDPO([
                'name' => $request->getParameter('name'),
                'email' => $request->getParameter('email'),
                'phone' => $request->getParameter('phone'),
                'qualifications' => $request->getParameter('qualifications'),
                'hit_cert_number' => $request->getParameter('hit_cert_number'),
                'appointment_date' => $request->getParameter('appointment_date'),
                'term_end_date' => $request->getParameter('term_end_date'),
                'form_dp2_submitted' => $request->getParameter('form_dp2_submitted') ? 1 : 0,
                'form_dp2_date' => $request->getParameter('form_dp2_date'),
                'form_dp2_ref' => $request->getParameter('form_dp2_ref'),
            ]);

            $this->getUser()->setFlash('notice', 'DPO information saved.');
            $this->redirect(['module' => 'cdpa', 'action' => 'dpo']);
        }
    }

    /**
     * Data subject requests list.
     */
    public function executeRequests($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $status = $request->getParameter('status');
        $this->requests = $service->getRequests($status);
        $this->currentStatus = $status;
    }

    /**
     * View request.
     */
    public function executeRequestView($request)
    {
        $this->checkAdmin();

        $id = (int) $request->getParameter('id');
        $this->dsRequest = DB::table('cdpa_data_subject_request')->where('id', $id)->first();
        $this->forward404Unless($this->dsRequest);
    }

    /**
     * Create request.
     */
    public function executeRequestCreate($request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
            $service = new \ahgCDPAPlugin\Services\CDPAService();

            $id = $service->createRequest([
                'request_type' => $request->getParameter('request_type'),
                'data_subject_name' => $request->getParameter('data_subject_name'),
                'data_subject_email' => $request->getParameter('data_subject_email'),
                'data_subject_phone' => $request->getParameter('data_subject_phone'),
                'data_subject_id_number' => $request->getParameter('data_subject_id_number'),
                'description' => $request->getParameter('description'),
                'verification_method' => $request->getParameter('verification_method'),
            ]);

            $this->getUser()->setFlash('notice', 'Request created successfully.');
            $this->redirect(['module' => 'cdpa', 'action' => 'requestView', 'id' => $id]);
        }
    }

    /**
     * Processing activities list.
     */
    public function executeProcessing($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->activities = $service->getProcessingActivities();
    }

    /**
     * Create processing activity.
     */
    public function executeProcessingCreate($request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
            $service = new \ahgCDPAPlugin\Services\CDPAService();

            $service->createProcessingActivity([
                'name' => $request->getParameter('name'),
                'category' => $request->getParameter('category'),
                'data_types' => $request->getParameter('data_types'),
                'purpose' => $request->getParameter('purpose'),
                'legal_basis' => $request->getParameter('legal_basis'),
                'storage_location' => $request->getParameter('storage_location'),
                'international_country' => $request->getParameter('international_country'),
                'retention_period' => $request->getParameter('retention_period'),
                'safeguards' => $request->getParameter('safeguards'),
                'cross_border' => $request->getParameter('cross_border') ? 1 : 0,
                'cross_border_safeguards' => $request->getParameter('cross_border_safeguards'),
                'automated_decision' => $request->getParameter('automated_decision') ? 1 : 0,
                'children_data' => $request->getParameter('children_data') ? 1 : 0,
                'biometric_data' => $request->getParameter('biometric_data') ? 1 : 0,
                'health_data' => $request->getParameter('health_data') ? 1 : 0,
                'created_by' => $this->getUser()->getUserId(),
            ]);

            $this->getUser()->setFlash('notice', 'Processing activity created.');
            $this->redirect(['module' => 'cdpa', 'action' => 'processing']);
        }
    }

    /**
     * Edit processing activity.
     */
    public function executeProcessingEdit($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->activity = $service->getProcessingActivity((int) $request->getParameter('id'));
        $this->forward404Unless($this->activity);

        if ($request->isMethod('post')) {
            DB::table('cdpa_processing_activity')
                ->where('id', $this->activity->id)
                ->update([
                    'name' => $request->getParameter('name'),
                    'category' => $request->getParameter('category'),
                    'data_types' => $request->getParameter('data_types'),
                    'purpose' => $request->getParameter('purpose'),
                    'legal_basis' => $request->getParameter('legal_basis'),
                    'storage_location' => $request->getParameter('storage_location'),
                    'retention_period' => $request->getParameter('retention_period'),
                    'safeguards' => $request->getParameter('safeguards'),
                    'cross_border' => $request->getParameter('cross_border') ? 1 : 0,
                    'children_data' => $request->getParameter('children_data') ? 1 : 0,
                    'biometric_data' => $request->getParameter('biometric_data') ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->getUser()->setFlash('notice', 'Processing activity updated.');
            $this->redirect(['module' => 'cdpa', 'action' => 'processing']);
        }
    }

    /**
     * DPIA list.
     */
    public function executeDpia($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->dpias = $service->getDPIAs();
    }

    /**
     * Create DPIA.
     */
    public function executeDpiaCreate($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->activities = $service->getProcessingActivities();

        if ($request->isMethod('post')) {
            $id = $service->createDPIA([
                'name' => $request->getParameter('name'),
                'processing_activity_id' => $request->getParameter('processing_activity_id') ?: null,
                'description' => $request->getParameter('description'),
                'necessity_assessment' => $request->getParameter('necessity_assessment'),
                'risk_level' => $request->getParameter('risk_level'),
                'assessor_name' => $request->getParameter('assessor_name'),
                'created_by' => $this->getUser()->getUserId(),
            ]);

            $this->getUser()->setFlash('notice', 'DPIA created.');
            $this->redirect(['module' => 'cdpa', 'action' => 'dpiaView', 'id' => $id]);
        }
    }

    /**
     * View DPIA.
     */
    public function executeDpiaView($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->dpia = $service->getDPIA((int) $request->getParameter('id'));
        $this->forward404Unless($this->dpia);
    }

    /**
     * Consent management.
     */
    public function executeConsent($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $activeOnly = !$request->getParameter('show_all');
        $this->consents = $service->getConsentRecords($activeOnly);
        $this->showAll = !$activeOnly;
    }

    /**
     * Breach register.
     */
    public function executeBreaches($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $status = $request->getParameter('status');
        $this->breaches = $service->getBreaches($status);
        $this->currentStatus = $status;
    }

    /**
     * Create breach.
     */
    public function executeBreachCreate($request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
            $service = new \ahgCDPAPlugin\Services\CDPAService();

            $id = $service->createBreach([
                'incident_date' => $request->getParameter('incident_date'),
                'discovery_date' => $request->getParameter('discovery_date'),
                'description' => $request->getParameter('description'),
                'breach_type' => $request->getParameter('breach_type'),
                'data_affected' => $request->getParameter('data_affected'),
                'records_affected' => $request->getParameter('records_affected'),
                'data_subjects_affected' => $request->getParameter('data_subjects_affected'),
                'severity' => $request->getParameter('severity'),
                'root_cause' => $request->getParameter('root_cause'),
                'reported_by' => $this->getUser()->getUserId(),
            ]);

            $this->getUser()->setFlash('notice', 'Breach recorded. Remember to notify POTRAZ within 72 hours!');
            $this->redirect(['module' => 'cdpa', 'action' => 'breachView', 'id' => $id]);
        }
    }

    /**
     * View breach.
     */
    public function executeBreachView($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->breach = $service->getBreach((int) $request->getParameter('id'));
        $this->forward404Unless($this->breach);

        $this->notificationOverdue = $service->isBreachNotificationOverdue($this->breach);
    }

    /**
     * Reports.
     */
    public function executeReports($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        $this->stats = $service->getDashboardStats();
    }

    /**
     * Configuration.
     */
    public function executeConfig($request)
    {
        $this->checkAdmin();

        require_once $this->config('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        $service = new \ahgCDPAPlugin\Services\CDPAService();

        if ($request->isMethod('post')) {
            $service->setConfig('response_deadline_days', $request->getParameter('response_deadline_days'), 'integer');
            $service->setConfig('license_reminder_days', $request->getParameter('license_reminder_days'), 'integer');
            $service->setConfig('breach_notification_hours', $request->getParameter('breach_notification_hours'), 'integer');
            $service->setConfig('dpia_review_months', $request->getParameter('dpia_review_months'), 'integer');
            $service->setConfig('organization_name', $request->getParameter('organization_name'), 'string');
            $service->setConfig('organization_address', $request->getParameter('organization_address'), 'string');
            $service->setConfig('dpo_email', $request->getParameter('dpo_email'), 'string');

            $this->getUser()->setFlash('notice', 'Configuration saved.');
            $this->redirect(['module' => 'cdpa', 'action' => 'config']);
        }

        $this->config = [
            'response_deadline_days' => $service->getConfig('response_deadline_days', 30),
            'license_reminder_days' => $service->getConfig('license_reminder_days', 90),
            'breach_notification_hours' => $service->getConfig('breach_notification_hours', 72),
            'dpia_review_months' => $service->getConfig('dpia_review_months', 12),
            'organization_name' => $service->getConfig('organization_name', ''),
            'organization_address' => $service->getConfig('organization_address', ''),
            'dpo_email' => $service->getConfig('dpo_email', ''),
        ];
    }

    /**
     * Check admin access.
     */
    protected function checkAdmin(): void
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }
}
