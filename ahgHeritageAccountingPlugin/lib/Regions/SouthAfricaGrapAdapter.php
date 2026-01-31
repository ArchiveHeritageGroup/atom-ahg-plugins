<?php

require_once dirname(__FILE__) . '/RegionAdapterInterface.php';
require_once dirname(__FILE__) . '/BaseRegionAdapter.php';

/**
 * SouthAfricaGrapAdapter - GRAP 103 adapter for South Africa.
 *
 * Countries: South Africa
 * Regulatory Body: National Treasury / Accounting Standards Board (ASB)
 */
class SouthAfricaGrapAdapter extends BaseRegionAdapter
{
    protected string $regionCode = 'south_africa_grap';
    protected string $standardCode = 'GRAP103';

    /**
     * Generate National Treasury Annual Financial Statements heritage schedule.
     *
     * @param string $financialYear Financial year (e.g., '2024/2025')
     *
     * @return array Report data
     */
    public function generateNationalTreasuryReport(string $financialYear): array
    {
        // SA financial year is April-March
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
            ->selectRaw('m.movement_type, COUNT(*) as count')
            ->pluck('count', 'movement_type')
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
            'report_name' => 'National Treasury AFS Heritage Schedule',
            'financial_year' => $financialYear,
            'period' => ['start' => $yearStart, 'end' => $yearEnd],
            'standard' => 'GRAP 103',
            'regulatory_body' => 'National Treasury / ASB',
            'generated_at' => date('Y-m-d H:i:s'),
            'by_asset_class' => $assetsByClass->toArray(),
            'movements' => [
                'acquisitions' => $movements['acquisition'] ?? 0,
                'disposals' => $movements['disposal'] ?? 0,
                'transfers' => $movements['transfer'] ?? 0,
                'revaluations' => $movements['revaluation'] ?? 0,
                'impairments' => $movements['impairment'] ?? 0,
            ],
            'by_recognition_status' => [
                'recognised' => $statusCounts['recognised'] ?? 0,
                'not_recognised' => $statusCounts['not_recognised'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
            ],
            'totals' => [
                'total_assets' => $totals->total_count ?? 0,
                'total_carrying_amount' => $totals->total_value ?? 0,
                'currency' => 'ZAR',
            ],
        ];
    }

    /**
     * Check GRAP 103 capitalisation requirements.
     *
     * @param int $assetId Heritage asset ID
     *
     * @return array Capitalisation assessment
     */
    public function assessCapitalisation(int $assetId): array
    {
        $asset = \Illuminate\Database\Capsule\Manager::table('heritage_asset')
            ->where('id', $assetId)
            ->first();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        $issues = [];
        $recommendations = [];

        // GRAP 103 capitalisation criteria
        // 1. Probable future economic benefits or service potential
        if (empty($asset->significance_statement)) {
            $issues[] = 'Heritage significance not documented - required for capitalisation assessment';
        }

        // 2. Cost or fair value can be reliably measured
        $hasMeasurement = $asset->acquisition_cost > 0 ||
                         $asset->fair_value_at_acquisition > 0 ||
                         $asset->current_carrying_amount > 0;

        if (!$hasMeasurement) {
            $recommendations[] = 'Consider using nominal value (R1) if cost cannot be reliably determined';
        }

        // 3. Control criterion
        if (empty($asset->acquisition_method)) {
            $issues[] = 'Acquisition method not documented - needed to establish control';
        }

        // Determine capitalisation recommendation
        $shouldCapitalise = empty($issues) && $hasMeasurement;

        return [
            'success' => true,
            'asset_id' => $assetId,
            'current_status' => $asset->recognition_status,
            'should_capitalise' => $shouldCapitalise,
            'measurement_basis_recommended' => $this->recommendMeasurementBasis($asset),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'reference' => 'GRAP 103.14-28',
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
            return 'cost';
        }

        if ($asset->fair_value_at_acquisition > 0) {
            return 'fair_value';
        }

        if ('donation' === $asset->acquisition_method || 'bequest' === $asset->acquisition_method) {
            return 'fair_value';
        }

        return 'nominal';
    }
}
