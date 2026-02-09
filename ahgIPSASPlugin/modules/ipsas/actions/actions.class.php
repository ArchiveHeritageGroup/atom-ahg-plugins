<?php

/**
 * IPSAS Module Actions
 *
 * Admin interface for heritage asset management under IPSAS standards
 */
class ipsasActions extends AhgActions
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

        $report = $request->getParameter('report');
        $format = $request->getParameter('format', 'csv');

        if ($report) {
            return $this->generateReport($report, $format, $this->year);
        }
    }

    /**
     * Generate and download report
     */
    protected function generateReport(string $report, string $format, string $year)
    {
        $service = $this->getService();
        $data = [];
        $headers = [];
        $filename = '';

        switch ($report) {
            case 'asset_register':
                $filename = "asset_register_{$year}";
                $headers = ['Asset #', 'Title', 'Category', 'Location', 'Valuation Basis', 'Current Value', 'Currency', 'Status', 'Condition', 'Acquisition Date', 'Acquisition Method'];
                $assets = $service->getAssets([]);
                foreach ($assets as $a) {
                    $data[] = [
                        $a->asset_number,
                        $a->title,
                        $a->category_name ?? '',
                        $a->location ?? '',
                        ucfirst(str_replace('_', ' ', $a->valuation_basis ?? '')),
                        $a->current_value ?? 0,
                        $a->current_value_currency ?? 'USD',
                        ucfirst(str_replace('_', ' ', $a->status ?? '')),
                        ucfirst($a->condition_rating ?? ''),
                        $a->acquisition_date ?? '',
                        ucfirst($a->acquisition_method ?? ''),
                    ];
                }
                break;

            case 'valuation_summary':
                $filename = "valuation_summary_{$year}";
                $headers = ['Date', 'Asset #', 'Asset Title', 'Type', 'Basis', 'Previous Value', 'New Value', 'Change', 'Valuer'];
                $valuations = $service->getValuations(['year' => $year]);
                foreach ($valuations as $v) {
                    $data[] = [
                        $v->valuation_date,
                        $v->asset_number,
                        $v->asset_title,
                        ucfirst($v->valuation_type),
                        ucfirst(str_replace('_', ' ', $v->valuation_basis ?? '')),
                        $v->previous_value ?? 0,
                        $v->new_value ?? 0,
                        ($v->new_value ?? 0) - ($v->previous_value ?? 0),
                        $v->valuer_name ?? '',
                    ];
                }
                break;

            case 'impairments':
                $filename = "impairments_{$year}";
                $headers = ['Date', 'Asset #', 'Asset Title', 'Carrying Value', 'Recoverable Amount', 'Impairment Loss', 'Recognized'];
                $impairments = $service->getImpairments([]);
                foreach ($impairments as $i) {
                    $data[] = [
                        $i->assessment_date,
                        $i->asset_number,
                        $i->asset_title,
                        $i->carrying_amount ?? 0,
                        $i->recoverable_amount ?? 0,
                        $i->impairment_loss ?? 0,
                        $i->impairment_recognized ? 'Yes' : 'No',
                    ];
                }
                break;

            case 'insurance':
                $filename = "insurance_coverage_{$year}";
                $headers = ['Policy #', 'Asset #', 'Asset Title', 'Insurer', 'Type', 'Sum Insured', 'Currency', 'Coverage Start', 'Coverage End', 'Status'];
                $policies = $service->getInsurancePolicies([]);
                foreach ($policies as $p) {
                    $data[] = [
                        $p->policy_number,
                        $p->asset_number ?? 'Blanket',
                        $p->asset_title ?? '-',
                        $p->insurer,
                        ucfirst(str_replace('_', ' ', $p->policy_type ?? '')),
                        $p->sum_insured ?? 0,
                        $p->currency ?? 'USD',
                        $p->coverage_start,
                        $p->coverage_end,
                        ucfirst($p->status ?? ''),
                    ];
                }
                break;

            case 'compliance':
                $filename = "compliance_report_{$year}";
                $headers = ['Check', 'Status', 'Count', 'Details'];
                $compliance = $service->getComplianceStatus();
                $stats = $service->getDashboardStats();

                $data[] = ['Total Assets', 'Info', $stats['assets']['total'], 'Total heritage assets in register'];
                $data[] = ['Active Assets', 'Info', $stats['assets']['active'], 'Assets currently active'];
                $data[] = ['Total Value', 'Info', number_format($stats['values']['total'] ?? 0, 2), 'Combined current value'];
                $data[] = ['Insured Value', 'Info', number_format($stats['values']['insured'] ?? 0, 2), 'Combined insured value'];

                foreach ($compliance['issues'] as $issue) {
                    $data[] = ['Issue', 'Non-Compliant', '-', $issue];
                }
                foreach ($compliance['warnings'] as $warning) {
                    $data[] = ['Warning', 'Warning', '-', $warning];
                }
                if (empty($compliance['issues']) && empty($compliance['warnings'])) {
                    $data[] = ['Overall Status', 'Compliant', '-', 'No issues or warnings found'];
                }
                break;

            default:
                $this->getUser()->setFlash('error', 'Unknown report type');
                $this->redirect(['module' => 'ipsas', 'action' => 'reports']);
                return;
        }

        // Generate CSV
        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->getResponse()->setHttpHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
        $this->getResponse()->setHttpHeader('Pragma', 'no-cache');
        $this->getResponse()->setHttpHeader('Expires', '0');

        $this->getResponse()->sendHttpHeaders();

        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers
        fputcsv($output, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);

        return sfView::NONE;
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
