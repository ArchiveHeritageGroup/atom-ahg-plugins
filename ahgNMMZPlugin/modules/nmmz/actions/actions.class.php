<?php

/**
 * NMMZ Module Actions
 *
 * Admin interface for National Museums and Monuments of Zimbabwe Act compliance
 */
class nmmzActions extends sfActions
{
    protected function getService(): \AhgNMMZ\Services\NMMZService
    {
        return new \AhgNMMZ\Services\NMMZService();
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();
        $service = $this->getService();

        $this->stats = $service->getDashboardStats();
        $this->compliance = $service->getComplianceStatus();
        $this->config = $service->getAllConfig();

        $this->recentMonuments = \Illuminate\Database\Capsule\Manager::table('nmmz_monument')
            ->orderBy('created_at', 'desc')->limit(5)->get();

        $this->pendingPermits = \Illuminate\Database\Capsule\Manager::table('nmmz_export_permit')
            ->where('status', 'pending')->orderBy('created_at')->limit(5)->get();
    }

    // Monuments
    public function executeMonuments(sfWebRequest $request)
    {
        $this->checkAdmin();
        $filters = [
            'category_id' => $request->getParameter('category'),
            'status' => $request->getParameter('status'),
            'province' => $request->getParameter('province'),
            'search' => $request->getParameter('q'),
        ];
        $this->monuments = $this->getService()->getMonuments($filters);
        $this->categories = $this->getService()->getCategories();
        $this->currentCategory = $filters['category_id'];
        $this->currentStatus = $filters['status'];
    }

    public function executeMonumentCreate(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->categories = $this->getService()->getCategories();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createMonument([
                'category_id' => $request->getParameter('category_id'),
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'historical_significance' => $request->getParameter('historical_significance'),
                'province' => $request->getParameter('province'),
                'district' => $request->getParameter('district'),
                'location_description' => $request->getParameter('location_description'),
                'gps_latitude' => $request->getParameter('gps_latitude'),
                'gps_longitude' => $request->getParameter('gps_longitude'),
                'protection_level' => $request->getParameter('protection_level'),
                'legal_status' => $request->getParameter('legal_status'),
                'ownership_type' => $request->getParameter('ownership_type'),
                'condition_rating' => $request->getParameter('condition_rating'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->redirect(['module' => 'nmmz', 'action' => 'monumentView', 'id' => $id]);
        }
    }

    public function executeMonumentView(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->monument = $this->getService()->getMonument($request->getParameter('id'));
        if (!$this->monument) {
            $this->forward404('Monument not found');
        }
        $this->inspections = $this->getService()->getMonumentInspections($this->monument->id);
    }

    // Antiquities
    public function executeAntiquities(sfWebRequest $request)
    {
        $this->checkAdmin();
        $filters = [
            'status' => $request->getParameter('status'),
            'object_type' => $request->getParameter('type'),
            'search' => $request->getParameter('q'),
        ];
        $this->antiquities = $this->getService()->getAntiquities($filters);
        $this->currentStatus = $filters['status'];
    }

    public function executeAntiquityCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createAntiquity([
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'object_type' => $request->getParameter('object_type'),
                'material' => $request->getParameter('material'),
                'estimated_age_years' => $request->getParameter('estimated_age_years'),
                'provenance' => $request->getParameter('provenance'),
                'find_location' => $request->getParameter('find_location'),
                'dimensions' => $request->getParameter('dimensions'),
                'condition_rating' => $request->getParameter('condition_rating'),
                'current_location' => $request->getParameter('current_location'),
                'estimated_value' => $request->getParameter('estimated_value'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->redirect(['module' => 'nmmz', 'action' => 'antiquityView', 'id' => $id]);
        }
    }

    public function executeAntiquityView(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->antiquity = $this->getService()->getAntiquity($request->getParameter('id'));
        if (!$this->antiquity) {
            $this->forward404('Antiquity not found');
        }
    }

    // Export Permits
    public function executePermits(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->permits = $this->getService()->getPermits([
            'status' => $request->getParameter('status'),
        ]);
        $this->currentStatus = $request->getParameter('status');
    }

    public function executePermitCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createPermit([
                'applicant_name' => $request->getParameter('applicant_name'),
                'applicant_address' => $request->getParameter('applicant_address'),
                'applicant_email' => $request->getParameter('applicant_email'),
                'applicant_phone' => $request->getParameter('applicant_phone'),
                'applicant_type' => $request->getParameter('applicant_type'),
                'antiquity_id' => $request->getParameter('antiquity_id') ?: null,
                'object_description' => $request->getParameter('object_description'),
                'quantity' => $request->getParameter('quantity', 1),
                'estimated_value' => $request->getParameter('estimated_value'),
                'export_purpose' => $request->getParameter('export_purpose'),
                'purpose_details' => $request->getParameter('purpose_details'),
                'destination_country' => $request->getParameter('destination_country'),
                'destination_institution' => $request->getParameter('destination_institution'),
                'export_date_proposed' => $request->getParameter('export_date_proposed'),
                'return_date' => $request->getParameter('return_date'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->redirect(['module' => 'nmmz', 'action' => 'permitView', 'id' => $id]);
        }
    }

    public function executePermitView(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->permit = $this->getService()->getPermit($request->getParameter('id'));
        if (!$this->permit) {
            $this->forward404('Permit not found');
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');
            $userId = $this->getUser()->getAttribute('user_id');

            if ('approve' === $action) {
                $this->getService()->approvePermit(
                    $this->permit->id,
                    $userId,
                    $request->getParameter('conditions')
                );
            } elseif ('reject' === $action) {
                \Illuminate\Database\Capsule\Manager::table('nmmz_export_permit')
                    ->where('id', $this->permit->id)
                    ->update([
                        'status' => 'rejected',
                        'rejection_reason' => $request->getParameter('rejection_reason'),
                        'reviewed_by' => $userId,
                        'review_date' => date('Y-m-d'),
                    ]);
            }

            $this->redirect(['module' => 'nmmz', 'action' => 'permitView', 'id' => $this->permit->id]);
        }
    }

    // Archaeological Sites
    public function executeSites(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->sites = $this->getService()->getSites([
            'province' => $request->getParameter('province'),
            'protection_status' => $request->getParameter('status'),
        ]);
        $this->currentStatus = $request->getParameter('status');
    }

    public function executeSiteCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createSite([
                'name' => $request->getParameter('name'),
                'site_type' => $request->getParameter('site_type'),
                'description' => $request->getParameter('description'),
                'province' => $request->getParameter('province'),
                'district' => $request->getParameter('district'),
                'location_description' => $request->getParameter('location_description'),
                'gps_latitude' => $request->getParameter('gps_latitude'),
                'gps_longitude' => $request->getParameter('gps_longitude'),
                'period' => $request->getParameter('period'),
                'discovery_date' => $request->getParameter('discovery_date'),
                'discovered_by' => $request->getParameter('discovered_by'),
                'protection_status' => $request->getParameter('protection_status'),
                'research_potential' => $request->getParameter('research_potential'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->redirect(['module' => 'nmmz', 'action' => 'siteView', 'id' => $id]);
        }
    }

    public function executeSiteView(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->site = $this->getService()->getSite($request->getParameter('id'));
        if (!$this->site) {
            $this->forward404('Site not found');
        }
    }

    // Heritage Impact Assessments
    public function executeHia(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->hias = $this->getService()->getHIAs([
            'status' => $request->getParameter('status'),
            'province' => $request->getParameter('province'),
        ]);
        $this->currentStatus = $request->getParameter('status');
    }

    public function executeHiaCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createHIA([
                'project_name' => $request->getParameter('project_name'),
                'project_type' => $request->getParameter('project_type'),
                'project_description' => $request->getParameter('project_description'),
                'project_location' => $request->getParameter('project_location'),
                'province' => $request->getParameter('province'),
                'district' => $request->getParameter('district'),
                'developer_name' => $request->getParameter('developer_name'),
                'developer_contact' => $request->getParameter('developer_contact'),
                'developer_email' => $request->getParameter('developer_email'),
                'assessor_name' => $request->getParameter('assessor_name'),
                'assessor_qualification' => $request->getParameter('assessor_qualification'),
                'impact_level' => $request->getParameter('impact_level'),
                'impact_description' => $request->getParameter('impact_description'),
                'mitigation_measures' => $request->getParameter('mitigation_measures'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->redirect(['module' => 'nmmz', 'action' => 'hia']);
        }
    }

    public function executeReports(sfWebRequest $request)
    {
        $this->checkAdmin();
    }

    public function executeConfig(sfWebRequest $request)
    {
        $this->checkAdmin();
        $service = $this->getService();

        if ($request->isMethod('post')) {
            $configs = [
                'antiquity_age_years' => $request->getParameter('antiquity_age_years'),
                'export_permit_fee_usd' => $request->getParameter('export_permit_fee_usd'),
                'export_permit_validity_days' => $request->getParameter('export_permit_validity_days'),
                'nmmz_contact_email' => $request->getParameter('nmmz_contact_email'),
                'nmmz_contact_phone' => $request->getParameter('nmmz_contact_phone'),
                'director_name' => $request->getParameter('director_name'),
            ];

            foreach ($configs as $key => $value) {
                if (null !== $value) {
                    $service->setConfig($key, $value);
                }
            }

            $this->getUser()->setFlash('notice', 'Configuration saved');
            $this->redirect(['module' => 'nmmz', 'action' => 'config']);
        }

        $this->config = $service->getAllConfig();
    }

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
