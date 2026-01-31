<?php

require_once dirname(__FILE__) . '/RegionAdapterInterface.php';
require_once dirname(__FILE__) . '/BaseRegionAdapter.php';

/**
 * AfricaIpsasAdapter - IPSAS 45 adapter for African countries.
 *
 * Countries: Zimbabwe, Kenya, Nigeria, Ghana, Tanzania, Uganda, Rwanda, Botswana, Zambia, Malawi
 */
class AfricaIpsasAdapter extends BaseRegionAdapter
{
    protected string $regionCode = 'africa_ipsas';
    protected string $standardCode = 'IPSAS45';

    /**
     * Generate Auditor General format report.
     *
     * @param array $options Report options
     *
     * @return array Report data
     */
    public function generateAuditorGeneralReport(array $options = []): array
    {
        return $this->generateReport('valuation_summary', $options);
    }

    /**
     * Generate asset reconciliation statement per IPSAS 45.88(e).
     *
     * @param string $financialYear Financial year (e.g., '2025')
     *
     * @return array Reconciliation data
     */
    public function generateReconciliation(string $financialYear): array
    {
        $yearStart = $financialYear . '-' . $this->getFinancialYearStart();
        $yearEnd = date('Y-m-d', strtotime($yearStart . ' +1 year -1 day'));

        // Get opening balances (assets at year start)
        $openingAssets = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->where('a.created_at', '<', $yearStart)
            ->where('a.recognition_status', 'recognised')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(current_carrying_amount), 0) as value')
            ->first();

        // Get additions during year
        $additions = \Illuminate\Database\Capsule\Manager::table('heritage_movement_register as m')
            ->join('heritage_asset as a', 'a.id', '=', 'm.asset_id')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->where('m.movement_type', 'acquisition')
            ->whereBetween('m.movement_date', [$yearStart, $yearEnd])
            ->count();

        // Get disposals during year
        $disposals = \Illuminate\Database\Capsule\Manager::table('heritage_movement_register as m')
            ->join('heritage_asset as a', 'a.id', '=', 'm.asset_id')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->where('m.movement_type', 'disposal')
            ->whereBetween('m.movement_date', [$yearStart, $yearEnd])
            ->count();

        // Get revaluations
        $revaluations = \Illuminate\Database\Capsule\Manager::table('heritage_valuation_history as v')
            ->join('heritage_asset as a', 'a.id', '=', 'v.asset_id')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->whereBetween('v.valuation_date', [$yearStart, $yearEnd])
            ->selectRaw('COALESCE(SUM(new_value - COALESCE(previous_value, 0)), 0) as net_change')
            ->first();

        // Get impairments
        $impairments = \Illuminate\Database\Capsule\Manager::table('heritage_impairment_assessment as i')
            ->join('heritage_asset as a', 'a.id', '=', 'i.asset_id')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->whereBetween('i.impairment_date', [$yearStart, $yearEnd])
            ->selectRaw('COALESCE(SUM(impairment_loss), 0) as total')
            ->first();

        // Get closing balances
        $closingAssets = \Illuminate\Database\Capsule\Manager::table('heritage_asset as a')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->where('s.code', $this->standardCode)
            ->where('a.recognition_status', 'recognised')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(current_carrying_amount), 0) as value')
            ->first();

        return [
            'success' => true,
            'financial_year' => $financialYear,
            'period' => ['start' => $yearStart, 'end' => $yearEnd],
            'standard' => $this->standardCode,
            'region' => $this->regionCode,
            'reconciliation' => [
                'opening_balance' => [
                    'count' => $openingAssets->count ?? 0,
                    'value' => $openingAssets->value ?? 0,
                ],
                'additions' => $additions,
                'disposals' => $disposals,
                'revaluations' => $revaluations->net_change ?? 0,
                'impairments' => $impairments->total ?? 0,
                'closing_balance' => [
                    'count' => $closingAssets->count ?? 0,
                    'value' => $closingAssets->value ?? 0,
                ],
            ],
        ];
    }
}
