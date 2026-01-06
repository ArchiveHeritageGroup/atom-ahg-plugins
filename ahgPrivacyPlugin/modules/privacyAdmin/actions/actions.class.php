<?php

class privacyAdminActions extends sfActions
{
    public function preExecute()
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        
        // Check admin permission
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
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

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $id = $service->createDsar($request->getPostParameters(), $this->getUserId());
            $this->getUser()->setFlash('success', 'DSAR created successfully');
            $this->redirect(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $id]);
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
    }

    public function executeOfficerAdd(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        $this->jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();

        if ($request->isMethod('post')) {
            $service = $this->getService();
            $service->saveOfficer($request->getPostParameters());
            $this->getUser()->setFlash('success', 'Privacy officer added successfully');
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
