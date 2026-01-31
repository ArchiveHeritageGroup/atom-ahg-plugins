<?php

require_once dirname(__FILE__) . '/RegionAdapterInterface.php';
require_once dirname(__FILE__) . '/BaseRegionAdapter.php';

/**
 * UkFrsAdapter - FRS 102 Section 34 adapter for United Kingdom.
 *
 * Countries: United Kingdom, Ireland
 * Regulatory Body: Financial Reporting Council / Charity Commission
 */
class UkFrsAdapter extends BaseRegionAdapter
{
    protected string $regionCode = 'uk_frs';
    protected string $standardCode = 'FRS102';

    /**
     * Generate Charity Commission SORP heritage assets report.
     *
     * @param string $financialYear Financial year (e.g., '2024/2025')
     *
     * @return array Report data
     */
    public function generateCharitySorpReport(string $financialYear): array
    {
        // UK financial year varies by organisation - commonly April-March for charities
        $parts = explode('/', $financialYear);
        $startYear = $parts[0] ?? date('Y');
        $yearStart = $startYear . '-04-01';
        $yearEnd = ((int) $startYear + 1) . '-03-31';

        // Get assets grouped by class
        $assetsByClass = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->leftJoin('heritage_asset_class as c', 'c.id', '=', 'a.asset_class_id')
            ->where('s.code', $this->standardCode)
            ->where('a.recognition_status', 'recognised')
            ->groupBy('c.id', 'c.name', 'c.code')
            ->selectRaw('c.code as class_code, c.name as class_name, COUNT(*) as count,
                         COALESCE(SUM(a.current_carrying_amount), 0) as carrying_amount,
                         COALESCE(SUM(a.acquisition_cost), 0) as cost')
            ->get();

        // Get movement summary
        $movements = \Illuminate\Database\Capsule\Manager::table('heritage_movement_register as m')
            ->join('heritage_asset as a', 'a.id', '=', 'm.asset_id')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->whereBetween('m.movement_date', [$yearStart, $yearEnd])
            ->groupBy('m.movement_type')
            ->selectRaw('m.movement_type, COUNT(*) as count, COALESCE(SUM(m.amount), 0) as amount')
            ->get()
            ->keyBy('movement_type')
            ->toArray();

        // Count by recognition status
        $statusCounts = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->groupBy('a.recognition_status')
            ->selectRaw('a.recognition_status, COUNT(*) as count')
            ->pluck('count', 'recognition_status')
            ->toArray();

        // Total values
        $totals = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->where('a.recognition_status', 'recognised')
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(a.current_carrying_amount), 0) as total_value')
            ->first();

        return [
            'success' => true,
            'report_name' => 'Charity SORP Heritage Assets Report',
            'financial_year' => $financialYear,
            'period' => ['start' => $yearStart, 'end' => $yearEnd],
            'standard' => 'FRS 102 Section 34',
            'sorp_reference' => 'Charities SORP (FRS 102) Module 10',
            'regulatory_body' => 'Charity Commission / FRC',
            'generated_at' => date('Y-m-d H:i:s'),
            'by_asset_class' => $assetsByClass->toArray(),
            'movements' => [
                'additions' => [
                    'count' => $movements['acquisition']->count ?? 0,
                    'amount' => $movements['acquisition']->amount ?? 0,
                ],
                'disposals' => [
                    'count' => $movements['disposal']->count ?? 0,
                    'amount' => $movements['disposal']->amount ?? 0,
                ],
                'revaluations' => [
                    'count' => $movements['revaluation']->count ?? 0,
                    'amount' => $movements['revaluation']->amount ?? 0,
                ],
                'impairments' => [
                    'count' => $movements['impairment']->count ?? 0,
                    'amount' => $movements['impairment']->amount ?? 0,
                ],
            ],
            'by_recognition_status' => [
                'recognised' => $statusCounts['recognised'] ?? 0,
                'not_recognised' => $statusCounts['not_recognised'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
            ],
            'totals' => [
                'total_assets' => $totals->total_count ?? 0,
                'total_carrying_amount' => $totals->total_value ?? 0,
                'currency' => 'GBP',
            ],
            'disclosures' => $this->getSorpDisclosureChecklist(),
        ];
    }

    /**
     * Get SORP disclosure checklist for heritage assets.
     *
     * @return array Disclosure checklist
     */
    protected function getSorpDisclosureChecklist(): array
    {
        return [
            'accounting_policy' => [
                'description' => 'Accounting policy for heritage assets',
                'requirement' => 'Disclose accounting policy adopted for heritage assets',
                'reference' => 'FRS 102.34.51',
            ],
            'nature_scale' => [
                'description' => 'Nature and scale of heritage assets',
                'requirement' => 'Describe nature and scale of heritage assets held',
                'reference' => 'FRS 102.34.53',
            ],
            'recognition_policy' => [
                'description' => 'Policy for items not recognised',
                'requirement' => 'Disclose why any heritage assets are not recognised in balance sheet',
                'reference' => 'FRS 102.34.54',
            ],
            'inalienability' => [
                'description' => 'Inalienability restrictions',
                'requirement' => 'Disclose any inalienability restrictions or significance',
                'reference' => 'Charities SORP 10.39',
            ],
            'valuation_method' => [
                'description' => 'Valuation method and frequency',
                'requirement' => 'State whether valued and describe valuation methodology',
                'reference' => 'FRS 102.34.52',
            ],
        ];
    }

    /**
     * Assess FRS 102 recognition criteria for a heritage asset.
     *
     * @param int $assetId Heritage asset ID
     *
     * @return array Recognition assessment
     */
    public function assessRecognition(int $assetId): array
    {
        $asset = \Illuminate\Database\Capsule\Manager::table('heritage_asset')
            ->where('id', $assetId)
            ->first();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        $issues = [];
        $recommendations = [];
        $frs102Criteria = [];

        // FRS 102 Section 34 recognition criteria
        // Criterion 1: Control
        if (empty($asset->acquisition_method)) {
            $issues[] = 'Acquisition method not documented - needed to establish control';
        }
        $frs102Criteria['control'] = !empty($asset->acquisition_method);

        // Criterion 2: Future economic benefits or service potential
        if (empty($asset->significance_statement)) {
            $issues[] = 'Heritage significance not documented';
        }
        $frs102Criteria['future_benefits'] = !empty($asset->significance_statement);

        // Criterion 3: Cost or fair value can be reliably measured
        $hasMeasurement = $asset->acquisition_cost > 0 ||
                         $asset->fair_value_at_acquisition > 0 ||
                         $asset->current_carrying_amount > 0;

        if (!$hasMeasurement) {
            $recommendations[] = 'Consider professional valuation or use nominal value if cost cannot be reliably determined';
            $recommendations[] = 'FRS 102 permits non-recognition if cost cannot be reliably measured';
        }
        $frs102Criteria['reliable_measurement'] = $hasMeasurement;

        // SORP-specific check: Inalienability
        $inalienableCheck = true;
        if (!empty($asset->restrictions) || !empty($asset->legal_status)) {
            $recommendations[] = 'Review inalienability restrictions for disclosure requirements';
        }

        // Determine recognition recommendation
        $shouldRecognise = $frs102Criteria['control'] &&
                          $frs102Criteria['future_benefits'] &&
                          $frs102Criteria['reliable_measurement'];

        return [
            'success' => true,
            'asset_id' => $assetId,
            'current_status' => $asset->recognition_status,
            'should_recognise' => $shouldRecognise,
            'frs102_criteria' => $frs102Criteria,
            'measurement_basis_recommended' => $this->recommendMeasurementBasis($asset),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'reference' => 'FRS 102 Section 34.48-50',
            'sorp_reference' => 'Charities SORP Module 10',
        ];
    }

    /**
     * Recommend measurement basis for an asset.
     *
     * @param object $asset Asset record
     *
     * @return string Recommended measurement basis
     */
    protected function recommendMeasurementBasis(object $asset): string
    {
        if ($asset->acquisition_cost > 0) {
            return 'historical_cost';
        }

        if ($asset->fair_value_at_acquisition > 0) {
            return 'fair_value';
        }

        if ('donation' === $asset->acquisition_method || 'bequest' === $asset->acquisition_method) {
            return 'fair_value_at_donation';
        }

        // UK allows non-recognition if measurement not possible
        return 'non_recognition';
    }

    /**
     * Generate summary for Annual Report trustees' note on heritage assets.
     *
     * @return array Summary data for trustees' report
     */
    public function generateTrusteesNote(): array
    {
        $totals = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->selectRaw('
                COUNT(*) as total_assets,
                SUM(CASE WHEN a.recognition_status = "recognised" THEN 1 ELSE 0 END) as recognised_count,
                SUM(CASE WHEN a.recognition_status = "recognised" THEN a.current_carrying_amount ELSE 0 END) as recognised_value,
                SUM(CASE WHEN a.recognition_status = "not_recognised" THEN 1 ELSE 0 END) as not_recognised_count
            ')
            ->first();

        // Get classes summary
        $classSummary = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->leftJoin('heritage_asset_class as c', 'c.id', '=', 'a.asset_class_id')
            ->where('s.code', $this->standardCode)
            ->groupBy('c.name')
            ->selectRaw('c.name as class_name, COUNT(*) as count')
            ->pluck('count', 'class_name')
            ->toArray();

        return [
            'success' => true,
            'note_title' => 'Heritage Assets',
            'summary' => [
                'total_heritage_items' => $totals->total_assets ?? 0,
                'items_on_balance_sheet' => $totals->recognised_count ?? 0,
                'balance_sheet_value' => $totals->recognised_value ?? 0,
                'items_not_recognised' => $totals->not_recognised_count ?? 0,
                'currency' => 'GBP',
            ],
            'by_category' => $classSummary,
            'disclosure_notes' => [
                'The charity holds heritage assets which contribute to its charitable purposes.',
                'Where reliable cost information is available, heritage assets are included in the balance sheet at cost or valuation.',
                'Where reliable cost information is not available and cannot be obtained at a cost commensurate with the benefit, heritage assets are not recognised in the balance sheet.',
            ],
        ];
    }
}
