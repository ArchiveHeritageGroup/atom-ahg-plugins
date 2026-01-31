<?php

/**
 * IPSAS Module Actions
 *
 * Admin interface for heritage asset management under IPSAS standards
 */
class ipsasActions extends sfActions
{
    protected function getService(): \AhgIPSAS\Services\IPSASService
    {
        return new \AhgIPSAS\Services\IPSASService();
    }

    /**
     * Dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();

        $service = $this->getService();
        $this->stats = $service->getDashboardStats();
        $this->compliance = $service->getComplianceStatus();
        $this->config = $service->getAllConfig();

        // Recent assets
        $this->recentAssets = \Illuminate\Database\Capsule\Manager::table('ipsas_heritage_asset')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Expiring insurance
        $this->expiringInsurance = \Illuminate\Database\Capsule\Manager::table('ipsas_insurance')
            ->where('status', 'active')
            ->whereRaw('coverage_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
            ->orderBy('coverage_end')
            ->limit(5)
            ->get();
    }

    /**
     * Asset register
     */
    public function executeAssets(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'category_id' => $request->getParameter('category'),
            'status' => $request->getParameter('status'),
            'valuation_basis' => $request->getParameter('basis'),
            'search' => $request->getParameter('q'),
        ];

        $this->assets = $this->getService()->getAssets($filters);
        $this->categories = $this->getService()->getCategories();
        $this->currentCategory = $filters['category_id'];
        $this->currentStatus = $filters['status'];
        $this->currentBasis = $filters['valuation_basis'];
        $this->search = $filters['search'];
    }

    /**
     * Create asset
     */
    public function executeAssetCreate(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->categories = $this->getService()->getCategories();

        if ($request->isMethod('post')) {
            $id = $this->getService()->createAsset([
                'information_object_id' => $request->getParameter('information_object_id'),
                'category_id' => $request->getParameter('category_id'),
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'location' => $request->getParameter('location'),
                'repository_id' => $request->getParameter('repository_id'),
                'acquisition_date' => $request->getParameter('acquisition_date'),
                'acquisition_method' => $request->getParameter('acquisition_method'),
                'acquisition_source' => $request->getParameter('acquisition_source'),
                'acquisition_cost' => $request->getParameter('acquisition_cost'),
                'acquisition_currency' => $request->getParameter('acquisition_currency', 'USD'),
                'valuation_basis' => $request->getParameter('valuation_basis'),
                'current_value' => $request->getParameter('current_value'),
                'condition_rating' => $request->getParameter('condition_rating'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'ipsas', 'action' => 'assetView', 'id' => $id]);
        }
    }

    /**
     * View asset
     */
    public function executeAssetView(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->asset = $this->getService()->getAsset($id);

        if (!$this->asset) {
            $this->forward404('Asset not found');
        }

        $this->valuations = $this->getService()->getAssetValuations($id);
        $this->impairments = $this->getService()->getImpairments(['asset_id' => $id]);
    }

    /**
     * Edit asset
     */
    public function executeAssetEdit(sfWebRequest $request)
    {
        $this->checkAdmin();

        $id = $request->getParameter('id');
        $this->asset = $this->getService()->getAsset($id);

        if (!$this->asset) {
            $this->forward404('Asset not found');
        }

        $this->categories = $this->getService()->getCategories();

        if ($request->isMethod('post')) {
            $this->getService()->updateAsset($id, [
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'location' => $request->getParameter('location'),
                'status' => $request->getParameter('status'),
                'condition_rating' => $request->getParameter('condition_rating'),
                'risk_level' => $request->getParameter('risk_level'),
                'risk_notes' => $request->getParameter('risk_notes'),
            ], $this->getUser()->getAttribute('user_id'));

            $this->redirect(['module' => 'ipsas', 'action' => 'assetView', 'id' => $id]);
        }
    }

    /**
     * Valuations list
     */
    public function executeValuations(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'type' => $request->getParameter('type'),
            'year' => $request->getParameter('year', date('Y')),
        ];

        $this->valuations = $this->getService()->getValuations($filters);
        $this->currentType = $filters['type'];
        $this->currentYear = $filters['year'];
    }

    /**
     * Create valuation
     */
    public function executeValuationCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        $assetId = $request->getParameter('asset_id');
        if ($assetId) {
            $this->asset = $this->getService()->getAsset($assetId);
        }

        if ($request->isMethod('post')) {
            $this->getService()->createValuation([
                'asset_id' => $request->getParameter('asset_id'),
                'valuation_date' => $request->getParameter('valuation_date'),
                'valuation_type' => $request->getParameter('valuation_type'),
                'valuation_basis' => $request->getParameter('valuation_basis'),
                'previous_value' => $request->getParameter('previous_value'),
                'new_value' => $request->getParameter('new_value'),
                'valuer_name' => $request->getParameter('valuer_name'),
                'valuer_qualification' => $request->getParameter('valuer_qualification'),
                'valuer_type' => $request->getParameter('valuer_type'),
                'valuation_method' => $request->getParameter('valuation_method'),
                'market_evidence' => $request->getParameter('market_evidence'),
                'documentation_ref' => $request->getParameter('documentation_ref'),
                'notes' => $request->getParameter('notes'),
                'user_id' => $this->getUser()->getAttribute('user_id'),
            ]);

            $this->redirect(['module' => 'ipsas', 'action' => 'assetView', 'id' => $request->getParameter('asset_id')]);
        }
    }

    /**
     * Impairments list
     */
    public function executeImpairments(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->impairments = $this->getService()->getImpairments([
            'recognized_only' => $request->getParameter('recognized_only'),
        ]);
    }

    /**
     * Insurance policies
     */
    public function executeInsurance(sfWebRequest $request)
    {
        $this->checkAdmin();

        $filters = [
            'status' => $request->getParameter('status'),
        ];

        $this->policies = $this->getService()->getInsurancePolicies($filters);
        $this->currentStatus = $filters['status'];
    }

    /**
     * Reports
     */
    public function executeReports(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->year = $request->getParameter('year', date('Y'));
    }

    /**
     * Financial year summary
     */
    public function executeFinancialYear(sfWebRequest $request)
    {
        $this->checkAdmin();

        $year = $request->getParameter('year', date('Y'));
        $service = $this->getService();

        $this->year = $year;
        $this->summary = $service->calculateFinancialYearSummary($year);
    }

    /**
     * Configuration
     */
    public function executeConfig(sfWebRequest $request)
    {
        $this->checkAdmin();

        $service = $this->getService();

        if ($request->isMethod('post')) {
            $configs = [
                'default_currency' => $request->getParameter('default_currency'),
                'financial_year_start' => $request->getParameter('financial_year_start'),
                'depreciation_policy' => $request->getParameter('depreciation_policy'),
                'valuation_frequency_years' => $request->getParameter('valuation_frequency_years'),
                'insurance_review_months' => $request->getParameter('insurance_review_months'),
                'impairment_threshold_percent' => $request->getParameter('impairment_threshold_percent'),
                'nominal_value' => $request->getParameter('nominal_value'),
                'organization_name' => $request->getParameter('organization_name'),
                'accounting_standard' => $request->getParameter('accounting_standard'),
            ];

            foreach ($configs as $key => $value) {
                if (null !== $value) {
                    $service->setConfig($key, $value);
                }
            }

            $this->getUser()->setFlash('notice', 'Configuration saved');
            $this->redirect(['module' => 'ipsas', 'action' => 'config']);
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
