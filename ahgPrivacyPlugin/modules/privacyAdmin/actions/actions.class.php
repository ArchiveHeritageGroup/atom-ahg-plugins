<?php
use Illuminate\Database\Capsule\Manager as DB;

class privacyAdminActions extends sfActions
{
    public function preExecute()
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';

        // downloadPdf can be accessed by anyone (public access for viewing redacted PDFs)
        if ($this->getActionName() === 'downloadPdf') {
            return;
        }

        // All other actions require authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Actions that editors can access
        $editorAllowedActions = [
            'piiScanAjax', 'piiScan', 'piiReview', 'piiEntityAction', 'piiScanObject', 'piiScanRun',
            'addManualRedaction', 'getRedactedTerms', 'removeManualRedaction',
            // Visual redaction actions
            'visualRedactionEditor', 'getVisualRedactions', 'saveVisualRedaction', 'deleteVisualRedaction',
            'getNerEntitiesForPage', 'applyVisualRedactions', 'getDocumentInfo', 'downloadRedactedFile'
        ];
        $currentAction = $this->getActionName();

        // Check admin permission (editors can access PII-related actions)
        if (!$this->context->user->hasCredential('administrator')) {
            if (!in_array($currentAction, $editorAllowedActions) || !$this->context->user->hasCredential('editor')) {
                $this->forward('admin', 'secure');
            }
        }
    }

    protected function getService(): \ahgPrivacyPlugin\Service\PrivacyService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        return new \ahgPrivacyPlugin\Service\PrivacyService();
    }

    protected function getUserId(): ?int
    {
        return $this->getUser()->getAttribute('user_id');
    }

    protected function getJurisdiction(): ?string
    {
        $j = $this->getRequest()->getParameter('jurisdiction', 'all');
        return $j === 'all' ? null : $j;
    }

    /**
     * Dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        $service = $this->getService();
        $jurisdiction = $this->getJurisdiction();
        $this->stats = $service->getDashboardStats($jurisdiction);
        $this->config = $service->getConfig($jurisdiction ?? 'popia');
        $this->recentDsars = $service->getDsarList(['jurisdiction' => $jurisdiction, 'limit' => 5]);
        $this->openBreaches = $service->getBreachList(['status' => 'investigating', 'jurisdiction' => $jurisdiction]);
        $this->notificationCount = $service->getNotificationCount($this->getUserId());

        // Get active jurisdiction from regional architecture
        $this->activeJurisdiction = $this->getJurisdictionManager()->getActiveJurisdiction();
    }

    // =====================
    // DSAR Management
    // =====================

    public function executeDsarList(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->dsars = $service->getDsarList([
            'status' => $request->getParameter('status'),
            'jurisdiction' => $this->getJurisdiction() ?? $request->getParameter('jurisdiction'),
            'overdue' => $request->getParameter('overdue')
        ]);
        $this->requestTypes = \ahgPrivacyPlugin\Service\PrivacyService::getRequestTypes(
            $this->getJurisdiction() ?? 'popia'
        );
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
    }

    public function executeDsarView(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->dsar = $service->getDsar($request->getParameter('id'));
        $this->logs = $service->getDsarLogs($request->getParameter('id'));
        
        if (!$this->dsar) {
            $this->forward404();
        }

        $this->requestTypes = \ahgPrivacyPlugin\Service\PrivacyService::getRequestTypes($this->dsar->jurisdiction);
        $this->jurisdictionInfo = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictionConfig($this->dsar->jurisdiction);
    }

    public function executeDsarAdd(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        $defaultJurisdiction = $this->getJurisdiction() ?? 'popia';
        $this->requestTypes = \ahgPrivacyPlugin\Service\PrivacyService::getRequestTypes($defaultJurisdiction);
                $service = new \ahgPrivacyPlugin\Service\PrivacyService();
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->idTypes = \ahgPrivacyPlugin\Service\PrivacyService::getIdTypes();
        $this->defaultJurisdiction = $defaultJurisdiction;
        $this->officers = $this->getService()->getOfficers();

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $id = $service->createDsar($request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'DSAR created successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $id]);
        }
    }

    public function executeDsarEdit(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->dsar = $service->getDsar($request->getParameter('id'));
        
        if (!$this->dsar) {
            $this->forward404();
        }
        
        // Get i18n data
        $this->dsarI18n = \Illuminate\Database\Capsule\Manager::table('privacy_dsar_i18n')
            ->where('id', $this->dsar->id)
            ->where('culture', 'en')
            ->first();
        
        $this->requestTypes = \ahgPrivacyPlugin\Service\PrivacyService::getRequestTypes($this->dsar->jurisdiction ?? 'popia');
        $this->jurisdictions = $service->getEnabledJurisdictions();
        $this->idTypes = \ahgPrivacyPlugin\Service\PrivacyService::getIdTypes();
        $this->statusOptions = \ahgPrivacyPlugin\Service\PrivacyService::getDsarStatuses();
        $this->outcomeOptions = \ahgPrivacyPlugin\Service\PrivacyService::getDsarOutcomes();
        $this->officers = $service->getOfficers();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        
        if ($request->isMethod('post')) {
            $service->updateDsar($request->getParameter('id'), $request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'DSAR updated successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $request->getParameter('id')]);
        }
    }

    public function executeDsarUpdate(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $service = $this->getService();
        $service->updateDsar(
            $request->getParameter('id'),
            $request->getPostParameters(),
            $this->getUserId()
        );

        $this->getUser()->setFlash('success', 'DSAR updated successfully');
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $request->getParameter('id')]);
    }

    // =====================
    // Breach Management
    // =====================

    public function executeBreachList(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->breaches = $service->getBreachList([
            'status' => $request->getParameter('status'),
            'severity' => $request->getParameter('severity'),
            'jurisdiction' => $this->getJurisdiction()
        ]);
        $this->severityLevels = \ahgPrivacyPlugin\Service\PrivacyService::getSeverityLevels();
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
    }

    public function executeBreachView(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->breach = $service->getBreach($request->getParameter('id'));
        
        if (!$this->breach) {
            $this->forward404();
        }

        $this->breachTypes = \ahgPrivacyPlugin\Service\PrivacyService::getBreachTypes();
        $this->severityLevels = \ahgPrivacyPlugin\Service\PrivacyService::getSeverityLevels();
        $this->jurisdictionInfo = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictionConfig($this->breach->jurisdiction);
    }

    public function executeBreachAdd(sfWebRequest $request)
    {
        $this->breachTypes = \ahgPrivacyPlugin\Service\PrivacyService::getBreachTypes();
        $this->severityLevels = \ahgPrivacyPlugin\Service\PrivacyService::getSeverityLevels();
        $service = $this->getService();
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->defaultJurisdiction = $this->getJurisdiction() ?? 'popia';

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $id = $service->createBreach($request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'Breach reported successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $id]);
        }
    }

    public function executeBreachEdit(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->breach = $service->getBreach($request->getParameter('id'));
        
        if (!$this->breach) {
            $this->forward404();
        }
        
        // Get i18n data
        $this->breachI18n = \Illuminate\Database\Capsule\Manager::table('privacy_breach_i18n')
            ->where('id', $this->breach->id)
            ->where('culture', 'en')
            ->first();
        
        $this->breachTypes = \ahgPrivacyPlugin\Service\PrivacyService::getBreachTypes();
        $this->severityLevels = \ahgPrivacyPlugin\Service\PrivacyService::getSeverityLevels();
        $this->statusOptions = \ahgPrivacyPlugin\Service\PrivacyService::getBreachStatuses();
        $this->riskLevels = \ahgPrivacyPlugin\Service\PrivacyService::getRiskLevels();
        $this->jurisdictions = $service->getEnabledJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        
        if ($request->isMethod('post')) {
            $service->updateBreach($request->getParameter('id'), $request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'Breach updated successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $request->getParameter('id')]);
        }
    }

    public function executeBreachUpdate(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $service = $this->getService();
        $service->updateBreach(
            $request->getParameter('id'),
            $request->getPostParameters(),
            $this->getUserId()
        );

        $this->getUser()->setFlash('success', 'Breach updated successfully');
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $request->getParameter('id')]);
    }

    // =====================
    // ROPA Management
    // =====================

    public function executeRopaList(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->activities = $service->getRopaList([
            'status' => $request->getParameter('status'),
            'jurisdiction' => $this->getJurisdiction()
        ]);
        $this->lawfulBases = \ahgPrivacyPlugin\Service\PrivacyService::getLawfulBases(
            $this->getJurisdiction() ?? 'popia'
        );
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
    }

    public function executeRopaView(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->activity = $service->getRopa($request->getParameter('id'));
        
        if (!$this->activity) {
            $this->forward404();
        }

        $this->lawfulBases = \ahgPrivacyPlugin\Service\PrivacyService::getLawfulBases(
            $this->activity->jurisdiction ?? 'popia'
        );
    }

    public function executeRopaAdd(sfWebRequest $request)
    {
        $defaultJurisdiction = $this->getJurisdiction() ?? 'popia';
        $this->lawfulBases = \ahgPrivacyPlugin\Service\PrivacyService::getLawfulBases($defaultJurisdiction);
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->defaultJurisdiction = $defaultJurisdiction;
        $this->officers = $this->getService()->getOfficers();

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $id = $service->saveRopa($request->getPostParameters(), null, $this->getUserId());
            $this->getUser()->setFlash('success', 'Processing activity added successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
        }
    }

    public function executeRopaEdit(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->activity = $service->getRopa($request->getParameter('id'));

        if (!$this->activity) {
            $this->forward404();
        }

        $this->lawfulBases = \ahgPrivacyPlugin\Service\PrivacyService::getLawfulBases(
            $this->activity->jurisdiction ?? 'popia'
        );
        $this->officers = $this->getService()->getOfficers();
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();

        if ($request->isMethod('post')) {
            $service->saveRopa($request->getPostParameters(), $request->getParameter('id'), $this->getUserId());
            $this->getUser()->setFlash('success', 'Processing activity updated successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $request->getParameter('id')]);
        }
    }

    // =====================
    // PAIA Requests (South Africa)
    // =====================

    public function executePaiaList(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->requests = $service->getPaiaRequests([
            'status' => $request->getParameter('status'),
            'section' => $request->getParameter('section')
        ]);
        $this->paiaTypes = \ahgPrivacyPlugin\Service\PrivacyService::getPAIARequestTypes();
    }

    public function executePaiaAdd(sfWebRequest $request)
    {
        $this->paiaTypes = \ahgPrivacyPlugin\Service\PrivacyService::getPAIARequestTypes();

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $id = $service->createPaiaRequest($request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'PAIA request created successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'paiaList']);
        }
    }

    // =====================
    // Officers Management
    // =====================

    public function executeOfficerList(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->officers = $service->getOfficers($this->getJurisdiction());
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
    }

    public function executeOfficerAdd(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $service->saveOfficer($request->getPostParameters());
            $this->getUser()->setFlash('success', 'Privacy officer added successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'officerList']);
        }
    }

    public function executeOfficerEdit(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        $service = $this->getService();
        $this->officer = $service->getOfficer($request->getParameter('id'));
        if (!$this->officer) {
            $this->forward404();
        }
        if ($request->isMethod('post')) {
            $service->saveOfficer($request->getPostParameters(), $request->getParameter('id'));
            $this->getUser()->setFlash('success', 'Privacy officer updated successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'officerList']);
        }
    }

    // =====================
    // Configuration
    // =====================

    public function executeConfig(sfWebRequest $request)
    {
        $service = $this->getService();
        $jurisdiction = $request->getParameter('jurisdiction', 'popia');
        $this->config = $service->getConfig($jurisdiction, true);
        $this->officers = $service->getOfficers($jurisdiction);
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        $this->currentJurisdiction = $jurisdiction;
        $this->jurisdictionInfo = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictionConfig($jurisdiction);

        if ($request->isMethod('post')) {
            $service->saveConfig($request->getPostParameters());
            $this->getUser()->setFlash('success', 'Configuration saved successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'config', 'jurisdiction' => $jurisdiction]);
        }
    }

    // =====================
    // Reports & Export
    // =====================

    public function executeReport(sfWebRequest $request)
    {
        $service = $this->getService();
        $jurisdiction = $this->getJurisdiction();
        $this->stats = $service->getDashboardStats($jurisdiction);
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();

        $dsarQuery = \Illuminate\Database\Capsule\Manager::table('privacy_dsar')
            ->selectRaw('request_type, COUNT(*) as count');
        if ($jurisdiction) {
            $dsarQuery->where('jurisdiction', $jurisdiction);
        }
        $this->dsarsByType = $dsarQuery->groupBy('request_type')->get();

        $breachQuery = \Illuminate\Database\Capsule\Manager::table('privacy_breach')
            ->selectRaw('severity, COUNT(*) as count');
        if ($jurisdiction) {
            $breachQuery->where('jurisdiction', $jurisdiction);
        }
        $this->breachesBySeverity = $breachQuery->groupBy('severity')->get();

        // By jurisdiction
        $this->dsarsByJurisdiction = \Illuminate\Database\Capsule\Manager::table('privacy_dsar')
            ->selectRaw('jurisdiction, COUNT(*) as count')
            ->groupBy('jurisdiction')
            ->get();
    }

    public function executeExport(sfWebRequest $request)
    {
        $type = $request->getParameter('type', 'dsar');
        $format = $request->getParameter('format', 'csv');
        $service = $this->getService();
        $jurisdiction = $this->getJurisdiction();

        switch ($type) {
            case 'dsar':
                $data = $service->getDsarList(['jurisdiction' => $jurisdiction]);
                $filename = 'dsar_export_' . date('Y-m-d');
                break;
            case 'breach':
                $data = $service->getBreachList(['jurisdiction' => $jurisdiction]);
                $filename = 'breach_export_' . date('Y-m-d');
                break;
            case 'ropa':
                $data = $service->getRopaList(['jurisdiction' => $jurisdiction]);
                $filename = 'ropa_export_' . date('Y-m-d');
                break;
            default:
                $this->forward404();
        }

        if ($format === 'csv') {
            $this->getResponse()->setContentType('text/csv');
            $this->getResponse()->setHttpHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
            
            $output = fopen('php://output', 'w');
            if ($data->isNotEmpty()) {
                fputcsv($output, array_keys((array)$data->first()));
                foreach ($data as $row) {
                    fputcsv($output, (array)$row);
                }
            }
            fclose($output);
            return sfView::NONE;
        }

        $this->data = $data;
        $this->type = $type;
    }

    // =====================
    // Consent Management
    // =====================

    public function executeConsentList(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->consents = $service->getConsentRecords([
            'status' => $request->getParameter('status')
        ]);
    }
    public function executeConsentAdd(sfWebRequest $request)
    {
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->defaultJurisdiction = $this->getJurisdiction() ?? 'popia';
        $this->consentMethods = ['form' => 'Online Form', 'email' => 'Email', 'verbal' => 'Verbal', 'written' => 'Written Document', 'checkbox' => 'Checkbox/Tick Box'];
        
        if ($request->isMethod('post')) {
            $service = $this->getService();
            $id = $service->recordConsent($request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'Consent recorded successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $id]);
        }
    }

    public function executeConsentView(sfWebRequest $request)
    {
        $this->consent = \Illuminate\Database\Capsule\Manager::table('privacy_consent_record')
            ->where('id', $request->getParameter('id'))
            ->first();
        
        if (!$this->consent) {
            $this->forward404();
        }
    }

    public function executeConsentEdit(sfWebRequest $request)
    {
        $this->consent = \Illuminate\Database\Capsule\Manager::table('privacy_consent_record')
            ->where('id', $request->getParameter('id'))
            ->first();
        
        if (!$this->consent) {
            $this->forward404();
        }
        
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->consentMethods = ['form' => 'Online Form', 'email' => 'Email', 'verbal' => 'Verbal', 'written' => 'Written Document', 'checkbox' => 'Checkbox/Tick Box'];
        
        if ($request->isMethod('post')) {
            $data = $request->getPostParameters();
            \Illuminate\Database\Capsule\Manager::table('privacy_consent_record')
                ->where('id', $request->getParameter('id'))
                ->update([
                    'data_subject_id' => $data['data_subject_id'] ?? $this->consent->data_subject_id,
                    'subject_name' => $data['subject_name'] ?? null,
                    'subject_email' => $data['subject_email'] ?? null,
                    'purpose' => $data['purpose'] ?? $this->consent->purpose,
                    'consent_given' => isset($data['consent_given']) ? 1 : 0,
                    'consent_method' => $data['consent_method'] ?? 'form',
                    'source' => $data['source'] ?? null,
                    'status' => $data['status'] ?? 'active'
                ]);
            $this->getUser()->setFlash('success', 'Consent record updated');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $request->getParameter('id')]);
        }
    }

    public function executeConsentWithdraw(sfWebRequest $request)
    {
        if ($request->isMethod('post')) {
            $service = $this->getService();
            $service->withdrawConsent($request->getParameter('id'), $request->getParameter('reason'), $this->getUserId());
            $this->getUser()->setFlash('success', 'Consent withdrawn successfully');
        }
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'consentList']);
    }

    // =====================
    // Complaint Management
    // =====================

    public function executeComplaintList(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';

        $this->complaints = \Illuminate\Database\Capsule\Manager::table('privacy_complaint')
            ->when($request->getParameter('status'), function($q, $status) {
                return $q->where('status', $status);
            })
            ->when($this->getJurisdiction(), function($q, $j) {
                return $q->where('jurisdiction', $j);
            })
            ->orderByDesc('created_at')
            ->get();

        $this->complaintTypes = [
            'unauthorized_access' => 'Unauthorized Access',
            'unauthorized_disclosure' => 'Unauthorized Disclosure',
            'inaccurate_data' => 'Inaccurate Data',
            'failure_to_respond' => 'Failure to Respond',
            'excessive_collection' => 'Excessive Collection',
            'unsolicited_marketing' => 'Unsolicited Marketing',
            'security_breach' => 'Security Breach',
            'other' => 'Other'
        ];
    }

    public function executeComplaintAdd(sfWebRequest $request)
    {
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->defaultJurisdiction = $this->getJurisdiction() ?? 'popia';
        $this->complaintTypes = ['data_breach' => 'Data Breach', 'unauthorized_access' => 'Unauthorized Access', 'consent_violation' => 'Consent Violation', 'rights_denial' => 'Rights Denial', 'marketing' => 'Unsolicited Marketing', 'other' => 'Other'];
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        
        if ($request->isMethod('post')) {
            $data = $request->getPostParameters();
            $reference = 'COMP-' . strtoupper($data['jurisdiction'] ?? 'POPIA') . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $id = \Illuminate\Database\Capsule\Manager::table('privacy_complaint')->insertGetId([
                'reference_number' => $reference,
                'jurisdiction' => $data['jurisdiction'] ?? 'popia',
                'complainant_name' => $data['complainant_name'],
                'complainant_email' => $data['complainant_email'] ?? null,
                'complainant_phone' => $data['complainant_phone'] ?? null,
                'complaint_type' => $data['complaint_type'],
                'description' => $data['description'] ?? null,
                'date_of_incident' => $data['date_of_incident'] ?? null,
                'status' => 'received',
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->getUser()->setFlash('success', 'Complaint logged successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'complaintView', 'id' => $id]);
        }
    }

    public function executeComplaintEdit(sfWebRequest $request)
    {
        $this->complaint = \Illuminate\Database\Capsule\Manager::table('privacy_complaint')
            ->where('id', $request->getParameter('id'))
            ->first();
        
        if (!$this->complaint) {
            $this->forward404();
        }
        
        $this->jurisdictions = $this->getService()->getEnabledJurisdictions();
        $this->complaintTypes = ['data_breach' => 'Data Breach', 'unauthorized_access' => 'Unauthorized Access', 'consent_violation' => 'Consent Violation', 'rights_denial' => 'Rights Denial', 'marketing' => 'Unsolicited Marketing', 'other' => 'Other'];
        $this->statusOptions = ['received' => 'Received', 'investigating' => 'Investigating', 'resolved' => 'Resolved', 'escalated' => 'Escalated', 'closed' => 'Closed'];
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')->select('id', 'username', 'email')->get();
        
        if ($request->isMethod('post')) {
            $data = $request->getPostParameters();
            $updates = [
                'complainant_name' => $data['complainant_name'],
                'complainant_email' => $data['complainant_email'] ?? null,
                'complainant_phone' => $data['complainant_phone'] ?? null,
                'complaint_type' => $data['complaint_type'],
                'description' => $data['description'] ?? null,
                'date_of_incident' => $data['date_of_incident'] ?? null,
                'status' => $data['status'] ?? 'received',
                'assigned_to' => $data['assigned_to'] ?? null,
                'resolution' => $data['resolution'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($data['status'] === 'resolved' && empty($this->complaint->resolved_date)) {
                $updates['resolved_date'] = date('Y-m-d');
            }
            
            \Illuminate\Database\Capsule\Manager::table('privacy_complaint')
                ->where('id', $request->getParameter('id'))
                ->update($updates);
            
            $this->getUser()->setFlash('success', 'Complaint updated successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'complaintView', 'id' => $request->getParameter('id')]);
        }
    }

    public function executeComplaintView(sfWebRequest $request)
    {
        $this->complaint = \Illuminate\Database\Capsule\Manager::table('privacy_complaint')
            ->where('id', $request->getParameter('id'))
            ->first();

        if (!$this->complaint) {
            $this->forward404();
        }
    }

    public function executeComplaintUpdate(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        \Illuminate\Database\Capsule\Manager::table('privacy_complaint')
            ->where('id', $request->getParameter('id'))
            ->update([
                'status' => $request->getParameter('status'),
                'assigned_to' => $request->getParameter('assigned_to'),
                'resolution' => $request->getParameter('resolution'),
                'resolved_date' => $request->getParameter('status') === 'resolved' ? date('Y-m-d') : null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->getUser()->setFlash('success', 'Complaint updated');
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'complaintView', 'id' => $request->getParameter('id')]);
    }

    // =====================
    // Jurisdiction Management
    // =====================

    public function executeJurisdictionList(sfWebRequest $request)
    {
        $this->jurisdictions = \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')
            ->orderBy('sort_order')
            ->get();
    }

    public function executeJurisdictionAdd(sfWebRequest $request)
    {
        $this->jurisdiction = null;
        $this->regions = ['Africa', 'Europe', 'North America', 'South America', 'Asia', 'Oceania', 'International'];

        if ($request->isMethod('post')) {
            $this->saveJurisdiction($request);
            $this->getUser()->setFlash('success', 'Jurisdiction added successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']);
        }
    }

    public function executeJurisdictionEdit(sfWebRequest $request)
    {
        $this->jurisdiction = \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')
            ->where('id', $request->getParameter('id'))
            ->first();

        if (!$this->jurisdiction) {
            $this->forward404();
        }

        $this->regions = ['Africa', 'Europe', 'North America', 'South America', 'Asia', 'Oceania', 'International'];

        if ($request->isMethod('post')) {
            $this->saveJurisdiction($request, $this->jurisdiction->id);
            $this->getUser()->setFlash('success', 'Jurisdiction updated successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']);
        }
    }

    public function executeJurisdictionToggle(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $j = \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')->where('id', $id)->first();

        if ($j) {
            \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')
                ->where('id', $id)
                ->update(['is_active' => !$j->is_active]);
        }

        $this->getUser()->setFlash('success', 'Jurisdiction status updated');
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']);
    }

    public function executeJurisdictionDelete(sfWebRequest $request)
    {
        $id = $request->getParameter('id');

        // Check if jurisdiction is in use
        $code = \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')
            ->where('id', $id)
            ->value('code');

        $inUse = \Illuminate\Database\Capsule\Manager::table('privacy_dsar')
            ->where('jurisdiction', $code)
            ->exists();

        if ($inUse) {
            $this->getUser()->setFlash('error', 'Cannot delete - jurisdiction is in use');
        } else {
            \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')->where('id', $id)->delete();
            $this->getUser()->setFlash('success', 'Jurisdiction deleted');
        }

        $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']);
    }

    protected function saveJurisdiction(sfWebRequest $request, $id = null)
    {
        $data = [
            'code' => strtolower($request->getParameter('code')),
            'name' => $request->getParameter('name'),
            'full_name' => $request->getParameter('full_name'),
            'country' => $request->getParameter('country'),
            'region' => $request->getParameter('region'),
            'regulator' => $request->getParameter('regulator'),
            'regulator_url' => $request->getParameter('regulator_url'),
            'dsar_days' => (int)$request->getParameter('dsar_days', 30),
            'breach_hours' => (int)$request->getParameter('breach_hours', 72),
            'effective_date' => $request->getParameter('effective_date') ?: null,
            'related_laws' => json_encode(array_filter(explode("\n", $request->getParameter('related_laws', '')))),
            'icon' => $request->getParameter('icon'),
            'is_active' => $request->getParameter('is_active') ? 1 : 0,
            'sort_order' => (int)$request->getParameter('sort_order', 99),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            \Illuminate\Database\Capsule\Manager::table('privacy_jurisdiction')->insert($data);
        }
    }

    // =====================
    // ROPA Approval Actions
    // =====================
    public function executeRopaSubmit(sfWebRequest $request)
    {
        $service = $this->getService();
        $id = $request->getParameter('id');
        $officerId = $request->getParameter('officer_id');
        
        if ($service->submitRopaForApproval($id, $this->getUserId(), $officerId)) {
            $this->getUser()->setFlash('success', 'Processing activity submitted for review');
        } else {
            $this->getUser()->setFlash('error', 'Unable to submit for review. Only draft items can be submitted.');
        }
        
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
    }

    public function executeRopaApprove(sfWebRequest $request)
    {
        $service = $this->getService();
        $id = $request->getParameter('id');
        $comment = $request->getParameter('comment');
        
        if (!$service->isPrivacyOfficer($this->getUserId()) && !$this->context->user->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Only Privacy Officers can approve records');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
        }
        
        if ($service->approveRopa($id, $this->getUserId(), $comment)) {
            $this->getUser()->setFlash('success', 'Processing activity approved');
        } else {
            $this->getUser()->setFlash('error', 'Unable to approve. Only pending review items can be approved.');
        }
        
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
    }

    public function executeRopaReject(sfWebRequest $request)
    {
        $service = $this->getService();
        $id = $request->getParameter('id');
        $reason = $request->getParameter('reason');
        
        if (!$service->isPrivacyOfficer($this->getUserId()) && !$this->context->user->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Only Privacy Officers can reject records');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
        }
        
        if (empty($reason)) {
            $this->getUser()->setFlash('error', 'Please provide a reason for rejection');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
        }
        
        if ($service->rejectRopa($id, $this->getUserId(), $reason)) {
            $this->getUser()->setFlash('success', 'Processing activity returned for changes');
        } else {
            $this->getUser()->setFlash('error', 'Unable to reject. Only pending review items can be rejected.');
        }
        
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $id]);
    }

    // =====================
    // Notifications
    // =====================
    public function executeNotifications(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->notifications = $service->getUnreadNotifications($this->getUserId(), 50);
    }

    public function executeNotificationRead(sfWebRequest $request)
    {
        $service = $this->getService();
        $id = $request->getParameter('id');
        $service->markNotificationRead($id, $this->getUserId());
        
        // Get notification to redirect to link
        $notification = DB::table('privacy_notification')->find($id);
        if ($notification && $notification->link) {
            $this->redirect($notification->link);
        }
        
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'notifications']);
    }

    public function executeNotificationMarkAllRead(sfWebRequest $request)
    {
        $service = $this->getService();
        $service->markAllNotificationsRead($this->getUserId());
        $this->getUser()->setFlash('success', 'All notifications marked as read');
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'notifications']);
    }

    // =====================
    // PII Detection
    // =====================

    protected function getPiiService(): \ahgPrivacyPlugin\Service\PiiDetectionService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiDetectionService.php';
        return new \ahgPrivacyPlugin\Service\PiiDetectionService();
    }

    public function executePiiScan(sfWebRequest $request)
    {
        $piiService = $this->getPiiService();
        $this->stats = $piiService->getStatistics();
        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function($j) {
                $j->on('actor_i18n.id', '=', 'repository.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select(['repository.id', 'actor_i18n.authorized_form_of_name as name'])
            ->orderBy('name')
            ->get();

        // Get recent high-risk objects
        $this->highRiskObjects = DB::table('ahg_ner_extraction as e')
            ->join('information_object_i18n as i18n', function($j) {
                $j->on('i18n.id', '=', 'e.object_id')->where('i18n.culture', '=', 'en');
            })
            ->where('e.backend_used', 'pii_detector')
            ->where('e.entity_count', '>', 0)
            ->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('ahg_ner_entity')
                  ->whereColumn('ahg_ner_entity.extraction_id', 'e.id')
                  ->where('ahg_ner_entity.status', 'flagged');
            })
            ->select(['e.object_id', 'i18n.title', 'e.entity_count', 'e.extracted_at'])
            ->orderByDesc('e.extracted_at')
            ->limit(20)
            ->get();
    }

    public function executePiiScanRun(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'piiScan']);
        }

        $piiService = $this->getPiiService();
        $filters = [];

        if ($request->getParameter('repository_id')) {
            $filters['repository_id'] = (int)$request->getParameter('repository_id');
        }

        $limit = (int)$request->getParameter('limit', 50);
        $results = $piiService->batchScan($filters, $limit);

        $this->getUser()->setFlash('success', sprintf(
            'Scanned %d objects. Found PII in %d objects (%d high-risk).',
            $results['scanned'],
            $results['with_pii'],
            $results['high_risk']
        ));

        $this->redirect(['module' => 'privacyAdmin', 'action' => 'piiScan']);
    }

    public function executePiiScanObject(sfWebRequest $request)
    {
        $objectId = (int)$request->getParameter('id');
        if (!$objectId) {
            $this->forward404();
        }

        $piiService = $this->getPiiService();
        $this->scanResult = $piiService->scanObject($objectId);
        $this->object = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->first();

        if ($request->isMethod('post') && $request->getParameter('save')) {
            $piiService->saveScanResults($objectId, $this->scanResult, $this->getUserId());
            $this->getUser()->setFlash('success', 'PII scan results saved');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'piiScanObject', 'id' => $objectId]);
        }
    }

    public function executePiiReview(sfWebRequest $request)
    {
        // Get pending PII entities for review
        $this->entities = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->join('information_object as io', 'io.id', '=', 'e.object_id')
            ->join('information_object_i18n as i18n', function($j) {
                $j->on('i18n.id', '=', 'e.object_id')->where('i18n.culture', '=', 'en');
            })
            ->join('slug', function($j) {
                $j->on('slug.object_id', '=', 'io.id');
            })
            ->where('ex.backend_used', 'pii_detector')
            ->whereIn('e.status', ['pending', 'flagged'])
            ->select([
                'e.id', 'e.object_id', 'e.entity_type', 'e.entity_value',
                'e.confidence', 'e.status', 'e.created_at',
                'i18n.title as object_title',
                'slug.slug as object_slug'
            ])
            ->orderByDesc('e.status') // flagged first
            ->orderByDesc('e.created_at')
            ->limit(100)
            ->get();
    }

    public function executePiiEntityAction(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $entityId = (int)$request->getParameter('entity_id');
        $action = $request->getParameter('entity_action');

        $validActions = ['approved', 'rejected', 'redacted'];
        if (!in_array($action, $validActions)) {
            $this->getUser()->setFlash('error', 'Invalid action');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'piiReview']);
        }

        // Get the object ID before updating
        $entity = DB::table('ahg_ner_entity')->where('id', $entityId)->first();
        $objectId = $entity ? $entity->object_id : null;

        DB::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => $action,
                'reviewed_by' => $this->getUserId(),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ]);

        // Clear PDF redaction cache for this object when status changes
        if ($objectId) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
            $pdfService = new \ahgPrivacyPlugin\Service\PdfRedactionService();
            $pdfService->clearCache($objectId);

            // Also clear the PII masking cache
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
            \ahgPrivacyPlugin\Service\PiiMaskingService::clearCache($objectId);
        }

        $this->getUser()->setFlash('success', 'Entity marked as ' . $action);

        if ($request->isXmlHttpRequest()) {
            return $this->renderText(json_encode(['success' => true]));
        }

        $this->redirect(['module' => 'privacyAdmin', 'action' => 'piiReview']);
    }

    /**
     * AJAX endpoint for single-object PII scan (called from information object page)
     */
    public function executePiiScanAjax(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = (int)$request->getParameter('id');
        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $piiService = $this->getPiiService();
            $result = $piiService->scanObject($objectId);

            // Save results if entities found
            if ($result['summary']['total'] > 0) {
                $piiService->saveScanResults($objectId, $result, $this->getUserId());
            }

            // Format entities by type for display
            $entitiesByType = [];
            foreach ($result['entities'] as $entity) {
                $type = $entity['type'];
                if (!isset($entitiesByType[$type])) {
                    $entitiesByType[$type] = [];
                }
                $entitiesByType[$type][] = [
                    'value' => $entity['value'],
                    'risk' => $entity['risk_level'],
                    'confidence' => round($entity['confidence'] * 100),
                ];
            }

            return $this->renderText(json_encode([
                'success' => true,
                'entity_count' => $result['summary']['total'],
                'high_risk' => $result['summary']['high_risk'],
                'risk_score' => $result['risk_score'],
                'entities' => $entitiesByType,
                'summary' => $result['summary'],
            ]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Serve a redacted PDF document
     * Route: /privacyAdmin/downloadPdf?id=<object_id>
     */
    public function executeDownloadPdf(sfWebRequest $request)
    {
        $objectId = (int)$request->getParameter('id');

        if (!$objectId) {
            $this->forward404('Object ID required');
        }

        // Load the redaction service
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
        $service = new \ahgPrivacyPlugin\Service\PdfRedactionService();

        // Get the digital object path
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject) {
            $this->forward404('No digital object found');
        }

        // Build the file path (matches QubitDigitalObject::getAbsolutePath())
        // Path format: sf_web_dir + path + name
        $originalPath = sfConfig::get('sf_web_dir') . $digitalObject->path . $digitalObject->name;

        // Check if it's a PDF
        $mimeType = $digitalObject->mime_type ?? mime_content_type($originalPath);
        if (stripos($mimeType, 'pdf') === false) {
            // Not a PDF, redirect to normal download
            $this->redirect($this->context->getRouting()->generate(null, [
                'module' => 'digitalobject',
                'action' => 'view',
                'slug' => $request->getParameter('slug')
            ]));
        }

        // Check if user can bypass redaction (admin)
        if (\ahgPrivacyPlugin\Service\PdfRedactionService::canBypassRedaction()) {
            // Serve original file
            $this->servePdf($originalPath, $digitalObject->name ?? 'document.pdf');
            return sfView::NONE;
        }

        // Get redacted version
        $result = $service->getRedactedPdf($objectId, $originalPath);

        if (!$result['success']) {
            // Log error and serve original (fallback)
            error_log('PDF redaction failed for object ' . $objectId . ': ' . ($result['error'] ?? 'Unknown error'));
            $this->servePdf($originalPath, $digitalObject->name ?? 'document.pdf');
            return sfView::NONE;
        }

        // Serve the redacted PDF
        $filename = pathinfo($digitalObject->name ?? 'document.pdf', PATHINFO_FILENAME);
        $this->servePdf($result['path'], $filename . '_redacted.pdf');

        return sfView::NONE;
    }

    /**
     * Helper to serve a PDF file
     */
    protected function servePdf(string $path, string $filename): void
    {
        if (!file_exists($path)) {
            $this->forward404('File not found');
        }

        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setHttpHeader('Content-Type', 'application/pdf');
        $response->setHttpHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
        $response->setHttpHeader('Content-Length', filesize($path));
        $response->setHttpHeader('Cache-Control', 'private, max-age=0');

        $response->sendHttpHeaders();
        readfile($path);
    }

    /**
     * Clear redacted PDF cache for an object (call when PII status changes)
     */
    public function executeClearPdfCache(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $objectId = (int)$request->getParameter('id');

        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
        $service = new \ahgPrivacyPlugin\Service\PdfRedactionService();

        if ($objectId) {
            $service->clearCache($objectId);
            $message = 'Cache cleared for object ' . $objectId;
        } else {
            $service->clearAllCache();
            $message = 'All PDF redaction cache cleared';
        }

        if ($request->isXmlHttpRequest()) {
            return $this->renderText(json_encode(['success' => true, 'message' => $message]));
        }

        $this->getUser()->setFlash('success', $message);
        $this->redirect(['module' => 'privacyAdmin', 'action' => 'piiScan']);
    }

    /**
     * AJAX endpoint for adding manual redaction from PDF text selection
     * POST /privacyAdmin/addManualRedaction
     *
     * Parameters:
     *   - object_id: int - The information object ID
     *   - text: string - The selected text to redact
     *   - redact_all: bool - Whether to redact all instances (default true)
     */
    public function executeAddManualRedaction(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'POST method required'
            ]));
        }

        $objectId = (int)$request->getParameter('object_id');
        $text = trim($request->getParameter('text', ''));
        $redactAll = $request->getParameter('redact_all', true);

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        if (empty($text)) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Text selection required'
            ]));
        }

        // Limit text length to prevent abuse
        if (strlen($text) > 500) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Selected text too long (max 500 characters)'
            ]));
        }

        try {
            // Check if this exact text is already marked for this object
            $existing = DB::table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->where('entity_text', $text)
                ->first();

            if ($existing) {
                // Update existing entry to redacted status
                DB::table('ahg_ner_entity')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => 'redacted',
                        'reviewed_by' => $this->getUserId(),
                        'reviewed_at' => date('Y-m-d H:i:s'),
                    ]);
                $entityId = $existing->id;
            } else {
                // Insert new entity
                $entityId = DB::table('ahg_ner_entity')->insertGetId([
                    'object_id' => $objectId,
                    'entity_text' => $text,
                    'entity_type' => 'MANUAL',
                    'confidence' => 1.0,
                    'source' => 'user_selection',
                    'status' => 'redacted',
                    'reviewed_by' => $this->getUserId(),
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Clear the PDF redaction cache so it regenerates
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
            $pdfService = new \ahgPrivacyPlugin\Service\PdfRedactionService();
            $pdfService->clearCache($objectId);

            // Also clear PII masking cache
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
            \ahgPrivacyPlugin\Service\PiiMaskingService::clearCache($objectId);

            return $this->renderText(json_encode([
                'success' => true,
                'message' => 'Text marked for redaction',
                'entity_id' => $entityId,
                'text' => $text,
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Failed to save redaction: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * AJAX endpoint to get current redacted terms for an object
     * GET /privacyAdmin/getRedactedTerms?id=<object_id>
     */
    public function executeGetRedactedTerms(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = (int)$request->getParameter('id');

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $terms = DB::table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->where('status', 'redacted')
                ->select('id', 'entity_text', 'entity_type', 'source')
                ->get();

            return $this->renderText(json_encode([
                'success' => true,
                'terms' => $terms,
                'count' => count($terms),
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * AJAX endpoint to remove a manual redaction
     * POST /privacyAdmin/removeManualRedaction
     */
    public function executeRemoveManualRedaction(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'POST method required'
            ]));
        }

        $entityId = (int)$request->getParameter('entity_id');

        if (!$entityId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Entity ID required'
            ]));
        }

        try {
            // Get the object ID before deleting
            $entity = DB::table('ahg_ner_entity')->where('id', $entityId)->first();

            if (!$entity) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error' => 'Entity not found'
                ]));
            }

            $objectId = $entity->object_id;

            // Only allow removing MANUAL entries, change others to 'approved'
            if ($entity->source === 'user_selection' || $entity->entity_type === 'MANUAL') {
                DB::table('ahg_ner_entity')->where('id', $entityId)->delete();
            } else {
                DB::table('ahg_ner_entity')
                    ->where('id', $entityId)
                    ->update([
                        'status' => 'approved',
                        'reviewed_by' => $this->getUserId(),
                        'reviewed_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            // Clear the PDF redaction cache
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
            $pdfService = new \ahgPrivacyPlugin\Service\PdfRedactionService();
            $pdfService->clearCache($objectId);

            return $this->renderText(json_encode([
                'success' => true,
                'message' => 'Redaction removed',
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    // =====================
    // Visual Redaction Editor
    // =====================

    protected function getVisualRedactionService(): \ahgPrivacyPlugin\Service\VisualRedactionService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/VisualRedactionService.php';
        return new \ahgPrivacyPlugin\Service\VisualRedactionService();
    }

    /**
     * Visual Redaction Editor page
     * GET /privacyAdmin/visualRedactionEditor?id=<object_id>
     */
    public function executeVisualRedactionEditor(sfWebRequest $request)
    {
        $objectId = (int)$request->getParameter('id');
        if (!$objectId) {
            $this->forward404('Object ID required');
        }

        // Get object info using QubitInformationObject for proper access
        $this->object = QubitInformationObject::getById($objectId);
        if (!$this->object) {
            // Fallback to database query
            $this->object = DB::table('information_object_i18n as i')
                ->leftJoin('slug', 'slug.object_id', '=', 'i.id')
                ->leftJoin('information_object as io', 'io.id', '=', 'i.id')
                ->where('i.id', $objectId)
                ->where('i.culture', 'en')
                ->select(['i.id', 'i.title', 'slug.slug', 'io.identifier as referenceCode'])
                ->first();
        }

        if (!$this->object) {
            $this->forward404('Object not found');
        }

        // Get document info
        $service = $this->getVisualRedactionService();
        $this->docInfo = $service->getDocumentInfo($objectId);

        if ($this->docInfo) {
            // Add URL for the document
            $digitalObject = DB::table('digital_object')
                ->where('object_id', $objectId)
                ->first();
            if ($digitalObject) {
                $this->docInfo['url'] = $digitalObject->path . $digitalObject->name;
            }
        }

        // Get existing redaction regions
        $this->regions = $service->getRegionsForObject($objectId);
    }

    /**
     * Get visual redaction regions for an object
     * GET /privacyAdmin/getVisualRedactions?id=<object_id>&page=<page_number>
     */
    public function executeGetVisualRedactions(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = (int)$request->getParameter('id');
        $page = $request->getParameter('page');

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $service = $this->getVisualRedactionService();
            $regions = $service->getRegionsForObject($objectId, $page ? (int)$page : null);

            // Parse coordinates JSON for each region
            $regionsArray = $regions->map(function ($region) {
                $region->coordinates = json_decode($region->coordinates, true);
                return $region;
            })->toArray();

            return $this->renderText(json_encode([
                'success' => true,
                'regions' => $regionsArray,
                'count' => count($regionsArray)
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Save visual redaction regions
     * POST /privacyAdmin/saveVisualRedaction
     */
    public function executeSaveVisualRedaction(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'POST method required'
            ]));
        }

        // Get JSON body
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Invalid JSON data'
            ]));
        }

        $objectId = (int)($data['object_id'] ?? 0);
        $page = (int)($data['page'] ?? 1);
        $regions = $data['regions'] ?? [];

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $service = $this->getVisualRedactionService();
            $savedIds = $service->batchSaveRegions($objectId, $page, $regions, $this->getUserId());

            // Get updated regions
            $updatedRegions = $service->getRegionsForObject($objectId, $page);
            $regionsArray = $updatedRegions->map(function ($region) {
                $region->coordinates = json_decode($region->coordinates, true);
                return $region;
            })->toArray();

            return $this->renderText(json_encode([
                'success' => true,
                'message' => 'Regions saved',
                'saved_count' => count($savedIds),
                'regions' => $regionsArray
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Delete a visual redaction region
     * POST /privacyAdmin/deleteVisualRedaction
     */
    public function executeDeleteVisualRedaction(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'POST method required'
            ]));
        }

        $regionId = (int)$request->getParameter('region_id');

        if (!$regionId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Region ID required'
            ]));
        }

        try {
            $service = $this->getVisualRedactionService();
            $result = $service->deleteRegion($regionId);

            return $this->renderText(json_encode([
                'success' => $result,
                'message' => $result ? 'Region deleted' : 'Region not found'
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Get NER entities for a specific page
     * GET /privacyAdmin/getNerEntitiesForPage?id=<object_id>&page=<page_number>
     */
    public function executeGetNerEntitiesForPage(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = (int)$request->getParameter('id');
        $page = (int)($request->getParameter('page') ?? 1);

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $service = $this->getVisualRedactionService();
            $entities = $service->getNerEntitiesForPage($objectId, $page);

            return $this->renderText(json_encode([
                'success' => true,
                'entities' => $entities->toArray(),
                'count' => $entities->count()
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Apply visual redactions - marks regions as 'applied' and clears cache
     * so the viewer automatically shows redacted content (like PII Review workflow)
     * POST /privacyAdmin/applyVisualRedactions
     */
    public function executeApplyVisualRedactions(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'POST method required'
            ]));
        }

        // Get JSON body
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        $objectId = (int)($data['object_id'] ?? $request->getParameter('id'));

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $userId = $this->getUserId();
            $now = date('Y-m-d H:i:s');

            // Get pending/approved regions
            $regions = DB::table('privacy_visual_redaction')
                ->where('object_id', $objectId)
                ->whereIn('status', ['pending', 'approved'])
                ->get();

            if ($regions->isEmpty()) {
                return $this->renderText(json_encode([
                    'success' => true,
                    'message' => 'No regions to apply',
                    'region_count' => 0
                ]));
            }

            // Mark all pending/approved regions as 'applied'
            $updated = DB::table('privacy_visual_redaction')
                ->where('object_id', $objectId)
                ->whereIn('status', ['pending', 'approved'])
                ->update([
                    'status' => 'applied',
                    'applied_at' => $now,
                    'reviewed_by' => $userId,
                    'reviewed_at' => $now,
                    'updated_at' => $now
                ]);

            // Clear the PdfRedactionService cache so viewer regenerates redacted output
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
            $pdfService = new \ahgPrivacyPlugin\Service\PdfRedactionService();
            $pdfService->clearCache($objectId);

            // Also clear VisualRedactionService cache if it exists
            $visualService = $this->getVisualRedactionService();
            $visualService->clearCache($objectId);

            // Get the slug for view URL
            $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');

            return $this->renderText(json_encode([
                'success' => true,
                'message' => "Applied {$updated} visual redaction(s). View the record to see redacted content.",
                'region_count' => $updated,
                'view_url' => $slug ? url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $slug]) : null
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Get document info (page count, dimensions, type)
     * GET /privacyAdmin/getDocumentInfo?id=<object_id>
     */
    public function executeGetDocumentInfo(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = (int)$request->getParameter('id');

        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Object ID required'
            ]));
        }

        try {
            $service = $this->getVisualRedactionService();
            $docInfo = $service->getDocumentInfo($objectId);

            if (!$docInfo) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error' => 'No digital object found'
                ]));
            }

            // Add URL for the document
            $digitalObject = DB::table('digital_object')
                ->where('object_id', $objectId)
                ->first();

            if ($digitalObject) {
                $docInfo['url'] = $digitalObject->path . $digitalObject->name;
            }

            return $this->renderText(json_encode([
                'success' => true,
                'document' => $docInfo
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    /**
     * Download redacted file
     * GET /privacyAdmin/downloadRedactedFile?id=<object_id>
     */
    public function executeDownloadRedactedFile(sfWebRequest $request)
    {
        $objectId = (int)$request->getParameter('id');

        if (!$objectId) {
            $this->forward404('Object ID required');
        }

        try {
            $service = $this->getVisualRedactionService();
            $cachedPath = $service->getCachedRedaction($objectId);

            if (!$cachedPath || !file_exists($cachedPath)) {
                // Try to generate
                $result = $service->applyRedactions($objectId, $this->getUserId());
                if (!$result['success'] || !isset($result['redacted_path'])) {
                    $this->forward404('Redacted file not available');
                }
                $cachedPath = $result['redacted_path'];
            }

            // Get original filename
            $digitalObject = DB::table('digital_object')
                ->where('object_id', $objectId)
                ->first();

            $filename = $digitalObject->name ?? 'redacted_document';
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $downloadName = $baseName . '_redacted.' . $ext;

            // Determine mime type
            $mimeType = $digitalObject->mime_type ?? mime_content_type($cachedPath);

            // Serve the file
            $response = $this->getResponse();
            $response->clearHttpHeaders();
            $response->setHttpHeader('Content-Type', $mimeType);
            $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"');
            $response->setHttpHeader('Content-Length', filesize($cachedPath));
            $response->setHttpHeader('Cache-Control', 'private, max-age=0');

            $response->sendHttpHeaders();
            readfile($cachedPath);

            return sfView::NONE;

        } catch (\Exception $e) {
            $this->forward404('Error: ' . $e->getMessage());
        }
    }

    // =====================================================
    // Regional Jurisdiction Management (New Architecture)
    // =====================================================

    protected function getJurisdictionManager(): \JurisdictionManager
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Jurisdictions/JurisdictionManager.php';
        return \JurisdictionManager::getInstance();
    }

    /**
     * List all available jurisdictions (regional architecture)
     */
    public function executeJurisdictions(sfWebRequest $request)
    {
        $manager = $this->getJurisdictionManager();
        $this->jurisdictions = $manager->getAvailableJurisdictions();
        $this->activeJurisdiction = $manager->getActiveJurisdiction();

        // Group by region
        $this->byRegion = [];
        foreach ($this->jurisdictions as $j) {
            $region = $j->region ?? 'International';
            if (!isset($this->byRegion[$region])) {
                $this->byRegion[$region] = [];
            }
            $this->byRegion[$region][] = $j;
        }
        ksort($this->byRegion);
    }

    /**
     * Install a jurisdiction
     */
    public function executeJurisdictionInstall(sfWebRequest $request)
    {
        $code = $request->getParameter('code');
        if (!$code) {
            $this->getUser()->setFlash('error', 'Jurisdiction code required');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictions']);
        }

        $manager = $this->getJurisdictionManager();
        $result = $manager->installJurisdiction($code);

        if ($result['success']) {
            if (!empty($result['already_installed'])) {
                $this->getUser()->setFlash('notice', $result['message']);
            } else {
                $this->getUser()->setFlash('success', sprintf(
                    '%s installed successfully. Lawful bases: %d, Special categories: %d, Request types: %d, Rules: %d',
                    $result['full_name'] ?? $code,
                    $result['lawful_bases_installed'] ?? 0,
                    $result['special_categories_installed'] ?? 0,
                    $result['request_types_installed'] ?? 0,
                    $result['compliance_rules_installed'] ?? 0
                ));
            }
        } else {
            $this->getUser()->setFlash('error', 'Installation failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictions']);
    }

    /**
     * Uninstall a jurisdiction
     */
    public function executeJurisdictionUninstall(sfWebRequest $request)
    {
        $code = $request->getParameter('code');
        if (!$code) {
            $this->getUser()->setFlash('error', 'Jurisdiction code required');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictions']);
        }

        $manager = $this->getJurisdictionManager();
        $result = $manager->uninstallJurisdiction($code);

        if ($result['success']) {
            $this->getUser()->setFlash('success', $result['message']);
        } else {
            $this->getUser()->setFlash('error', 'Uninstall failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictions']);
    }

    /**
     * Set active jurisdiction
     */
    public function executeJurisdictionSetActive(sfWebRequest $request)
    {
        $code = $request->getParameter('code');
        $repositoryId = $request->getParameter('repository_id');

        if (!$code) {
            $this->getUser()->setFlash('error', 'Jurisdiction code required');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictions']);
        }

        $manager = $this->getJurisdictionManager();
        $result = $manager->setActiveJurisdiction($code, $repositoryId ? (int)$repositoryId : null);

        if ($result['success']) {
            $scope = $repositoryId ? "repository #{$repositoryId}" : 'all repositories';
            $this->getUser()->setFlash('success', sprintf(
                'Active jurisdiction set to %s for %s',
                $result['jurisdiction_name'] ?? $code,
                $scope
            ));
        } else {
            $this->getUser()->setFlash('error', 'Activation failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $this->redirect(['module' => 'privacyAdmin', 'action' => 'jurisdictions']);
    }

    /**
     * Show jurisdiction details
     */
    public function executeJurisdictionInfo(sfWebRequest $request)
    {
        $code = $request->getParameter('code');
        if (!$code) {
            $this->forward404('Jurisdiction code required');
        }

        $manager = $this->getJurisdictionManager();
        $jurisdictions = $manager->getAvailableJurisdictions();
        $this->jurisdiction = null;

        foreach ($jurisdictions as $j) {
            if ($j->code === $code) {
                $this->jurisdiction = $j;
                break;
            }
        }

        if (!$this->jurisdiction) {
            $this->forward404('Jurisdiction not found');
        }

        $this->activeJurisdiction = $manager->getActiveJurisdiction();

        // Get installed components if installed
        if ($this->jurisdiction->is_installed) {
            $this->stats = $manager->getJurisdictionStats($code);
            $this->lawfulBases = $manager->getLawfulBases($code);
            $this->specialCategories = $manager->getSpecialCategories($code);
            $this->requestTypes = $manager->getRequestTypes($code);
            $this->complianceRules = $manager->getComplianceRules($code);
        }
    }
}
