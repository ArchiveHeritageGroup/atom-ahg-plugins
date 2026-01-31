<?php

/**
 * IPSAS Service - Heritage Asset Management
 *
 * Service class for managing heritage assets under IPSAS:
 * - Asset register management
 * - Valuation tracking
 * - Impairment assessment
 * - Insurance management
 * - Depreciation calculations
 * - Financial reporting
 *
 * @package    ahgIPSASPlugin
 * @subpackage Services
 */

namespace AhgIPSAS\Services;

use Illuminate\Database\Capsule\Manager as DB;

class IPSASService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $stats = [];

        // Asset counts by status
        $stats['assets'] = [
            'total' => DB::table('ipsas_heritage_asset')->count(),
            'active' => DB::table('ipsas_heritage_asset')->where('status', 'active')->count(),
            'on_loan' => DB::table('ipsas_heritage_asset')->where('status', 'on_loan')->count(),
            'disposed' => DB::table('ipsas_heritage_asset')->where('status', 'disposed')->count(),
        ];

        // Total values
        $stats['values'] = [
            'total' => DB::table('ipsas_heritage_asset')
                ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
                ->sum('current_value'),
            'insured' => DB::table('ipsas_heritage_asset')
                ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
                ->sum('insured_value'),
        ];

        // Valuation breakdown
        $stats['valuation_basis'] = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->selectRaw('valuation_basis, COUNT(*) as count, SUM(current_value) as value')
            ->groupBy('valuation_basis')
            ->pluck('count', 'valuation_basis')
            ->toArray();

        // Category breakdown
        $stats['categories'] = DB::table('ipsas_heritage_asset as a')
            ->join('ipsas_asset_category as c', 'a.category_id', '=', 'c.id')
            ->whereNotIn('a.status', ['disposed', 'lost', 'destroyed'])
            ->selectRaw('c.name, COUNT(*) as count, SUM(a.current_value) as value')
            ->groupBy('c.id', 'c.name')
            ->get();

        // Insurance
        $stats['insurance'] = [
            'expiring_soon' => DB::table('ipsas_insurance')
                ->where('status', 'active')
                ->whereRaw('coverage_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
                ->count(),
            'total_insured' => DB::table('ipsas_insurance')
                ->where('status', 'active')
                ->sum('sum_insured'),
        ];

        // Recent activity
        $stats['recent_valuations'] = DB::table('ipsas_valuation')
            ->whereYear('valuation_date', date('Y'))
            ->count();

        return $stats;
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatus(): array
    {
        $issues = [];
        $warnings = [];

        // Check for assets without valuation
        $unvalued = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->whereNull('current_value')
            ->count();

        if ($unvalued > 0) {
            $warnings[] = "{$unvalued} assets have no recorded value";
        }

        // Check for overdue revaluations (> 5 years)
        $overdueRevaluations = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->where('valuation_basis', 'fair_value')
            ->where(function ($q) {
                $q->whereNull('current_value_date')
                  ->orWhereRaw('current_value_date < DATE_SUB(CURDATE(), INTERVAL 5 YEAR)');
            })
            ->count();

        if ($overdueRevaluations > 0) {
            $issues[] = "{$overdueRevaluations} assets overdue for revaluation";
        }

        // Check for expiring insurance
        $expiringInsurance = DB::table('ipsas_insurance')
            ->where('status', 'active')
            ->whereRaw('coverage_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
            ->count();

        if ($expiringInsurance > 0) {
            $warnings[] = "{$expiringInsurance} insurance policies expiring within 30 days";
        }

        // Check for uninsured high-value assets
        $uninsuredHighValue = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->where('current_value', '>', 10000)
            ->whereNull('insured_value')
            ->count();

        if ($uninsuredHighValue > 0) {
            $warnings[] = "{$uninsuredHighValue} high-value assets are uninsured";
        }

        // Check for pending impairment reviews
        $criticalCondition = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->where('condition_rating', 'critical')
            ->count();

        if ($criticalCondition > 0) {
            $issues[] = "{$criticalCondition} assets in critical condition - impairment review needed";
        }

        $status = 'compliant';
        if (!empty($issues)) {
            $status = 'non_compliant';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    // =========================================================================
    // ASSET MANAGEMENT
    // =========================================================================

    /**
     * Get assets with filtering
     */
    public function getAssets(array $filters = [])
    {
        $query = DB::table('ipsas_heritage_asset as a')
            ->leftJoin('ipsas_asset_category as c', 'a.category_id', '=', 'c.id')
            ->select([
                'a.*',
                'c.name as category_name',
                'c.code as category_code',
            ]);

        if (!empty($filters['category_id'])) {
            $query->where('a.category_id', $filters['category_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('a.status', $filters['status']);
        }

        if (!empty($filters['valuation_basis'])) {
            $query->where('a.valuation_basis', $filters['valuation_basis']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('a.title', 'like', "%{$search}%")
                  ->orWhere('a.asset_number', 'like', "%{$search}%")
                  ->orWhere('a.description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['min_value'])) {
            $query->where('a.current_value', '>=', $filters['min_value']);
        }

        if (!empty($filters['max_value'])) {
            $query->where('a.current_value', '<=', $filters['max_value']);
        }

        return $query->orderBy('a.asset_number')->get();
    }

    /**
     * Create asset
     */
    public function createAsset(array $data): int
    {
        // Generate asset number
        $category = null;
        if (!empty($data['category_id'])) {
            $category = DB::table('ipsas_asset_category')
                ->where('id', $data['category_id'])
                ->first();
        }
        $prefix = $category ? $category->code : 'AST';
        $year = date('Y');
        $count = DB::table('ipsas_heritage_asset')
            ->whereYear('created_at', $year)
            ->count() + 1;
        $assetNumber = sprintf('%s-%s-%04d', $prefix, $year, $count);

        $id = DB::table('ipsas_heritage_asset')->insertGetId([
            'asset_number' => $assetNumber,
            'information_object_id' => $data['information_object_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'repository_id' => $data['repository_id'] ?? null,
            'acquisition_date' => $data['acquisition_date'] ?? null,
            'acquisition_method' => $data['acquisition_method'] ?? 'unknown',
            'acquisition_source' => $data['acquisition_source'] ?? null,
            'acquisition_cost' => $data['acquisition_cost'] ?? null,
            'acquisition_currency' => $data['acquisition_currency'] ?? 'USD',
            'valuation_basis' => $data['valuation_basis'] ?? 'nominal',
            'current_value' => $data['current_value'] ?? $this->getConfig('nominal_value', 1.00),
            'current_value_currency' => $data['current_value_currency'] ?? 'USD',
            'current_value_date' => date('Y-m-d'),
            'depreciation_policy' => $data['depreciation_policy'] ?? 'none',
            'useful_life_years' => $data['useful_life_years'] ?? null,
            'status' => 'active',
            'condition_rating' => $data['condition_rating'] ?? 'good',
            'created_by' => $data['user_id'],
        ]);

        // Create initial valuation record
        $this->createValuation([
            'asset_id' => $id,
            'valuation_date' => date('Y-m-d'),
            'valuation_type' => 'initial',
            'valuation_basis' => $data['valuation_basis'] ?? 'nominal',
            'previous_value' => 0,
            'new_value' => $data['current_value'] ?? $this->getConfig('nominal_value', 1.00),
            'valuer_type' => 'internal',
            'notes' => 'Initial registration',
            'user_id' => $data['user_id'],
        ]);

        $this->logAction('asset_created', 'heritage_asset', $id, $data['user_id'], null, $data);

        return $id;
    }

    /**
     * Get asset by ID
     */
    public function getAsset(int $id)
    {
        return DB::table('ipsas_heritage_asset as a')
            ->leftJoin('ipsas_asset_category as c', 'a.category_id', '=', 'c.id')
            ->select([
                'a.*',
                'c.name as category_name',
                'c.code as category_code',
            ])
            ->where('a.id', $id)
            ->first();
    }

    /**
     * Update asset
     */
    public function updateAsset(int $id, array $data, int $userId): bool
    {
        $old = DB::table('ipsas_heritage_asset')->where('id', $id)->first();
        if (!$old) {
            return false;
        }

        $updateData = array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => $data['status'] ?? null,
            'condition_rating' => $data['condition_rating'] ?? null,
            'insured_value' => $data['insured_value'] ?? null,
            'insurance_policy' => $data['insurance_policy'] ?? null,
            'insurance_expiry' => $data['insurance_expiry'] ?? null,
            'risk_level' => $data['risk_level'] ?? null,
            'risk_notes' => $data['risk_notes'] ?? null,
        ], function ($v) {
            return null !== $v;
        });

        if (!empty($updateData)) {
            DB::table('ipsas_heritage_asset')
                ->where('id', $id)
                ->update($updateData);

            $this->logAction('asset_updated', 'heritage_asset', $id, $userId, (array) $old, $updateData);
        }

        return true;
    }

    // =========================================================================
    // VALUATIONS
    // =========================================================================

    /**
     * Get valuations for an asset
     */
    public function getAssetValuations(int $assetId)
    {
        return DB::table('ipsas_valuation')
            ->where('asset_id', $assetId)
            ->orderBy('valuation_date', 'desc')
            ->get();
    }

    /**
     * Create valuation
     */
    public function createValuation(array $data): int
    {
        $changeAmount = ($data['new_value'] ?? 0) - ($data['previous_value'] ?? 0);
        $changePercent = $data['previous_value'] > 0
            ? ($changeAmount / $data['previous_value']) * 100
            : 0;

        $id = DB::table('ipsas_valuation')->insertGetId([
            'asset_id' => $data['asset_id'],
            'valuation_date' => $data['valuation_date'],
            'valuation_type' => $data['valuation_type'],
            'valuation_basis' => $data['valuation_basis'],
            'previous_value' => $data['previous_value'] ?? 0,
            'new_value' => $data['new_value'],
            'currency' => $data['currency'] ?? 'USD',
            'change_amount' => $changeAmount,
            'change_percent' => $changePercent,
            'valuer_name' => $data['valuer_name'] ?? null,
            'valuer_qualification' => $data['valuer_qualification'] ?? null,
            'valuer_type' => $data['valuer_type'] ?? 'internal',
            'valuation_method' => $data['valuation_method'] ?? null,
            'market_evidence' => $data['market_evidence'] ?? null,
            'documentation_ref' => $data['documentation_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['user_id'],
        ]);

        // Update asset's current value
        DB::table('ipsas_heritage_asset')
            ->where('id', $data['asset_id'])
            ->update([
                'current_value' => $data['new_value'],
                'current_value_date' => $data['valuation_date'],
                'valuation_basis' => $data['valuation_basis'],
            ]);

        $this->logAction('valuation_recorded', 'valuation', $id, $data['user_id'], null, $data);

        return $id;
    }

    /**
     * Get all valuations with filtering
     */
    public function getValuations(array $filters = [])
    {
        $query = DB::table('ipsas_valuation as v')
            ->join('ipsas_heritage_asset as a', 'v.asset_id', '=', 'a.id')
            ->select([
                'v.*',
                'a.asset_number',
                'a.title as asset_title',
            ]);

        if (!empty($filters['type'])) {
            $query->where('v.valuation_type', $filters['type']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('v.valuation_date', $filters['year']);
        }

        if (!empty($filters['asset_id'])) {
            $query->where('v.asset_id', $filters['asset_id']);
        }

        return $query->orderBy('v.valuation_date', 'desc')->get();
    }

    // =========================================================================
    // IMPAIRMENTS
    // =========================================================================

    /**
     * Create impairment assessment
     */
    public function createImpairment(array $data): int
    {
        $impairmentLoss = max(0, ($data['carrying_amount'] ?? 0) - ($data['recoverable_amount'] ?? 0));

        $id = DB::table('ipsas_impairment')->insertGetId([
            'asset_id' => $data['asset_id'],
            'assessment_date' => $data['assessment_date'],
            'physical_damage' => $data['physical_damage'] ?? 0,
            'obsolescence' => $data['obsolescence'] ?? 0,
            'decline_in_demand' => $data['decline_in_demand'] ?? 0,
            'market_value_decline' => $data['market_value_decline'] ?? 0,
            'other_indicator' => $data['other_indicator'] ?? 0,
            'indicator_description' => $data['indicator_description'] ?? null,
            'carrying_amount' => $data['carrying_amount'],
            'recoverable_amount' => $data['recoverable_amount'] ?? null,
            'impairment_loss' => $impairmentLoss,
            'impairment_recognized' => $data['impairment_recognized'] ?? 0,
            'recognition_date' => $data['impairment_recognized'] ? $data['assessment_date'] : null,
            'notes' => $data['notes'] ?? null,
            'assessed_by' => $data['user_id'],
        ]);

        // If impairment recognized, create valuation record
        if (!empty($data['impairment_recognized']) && $impairmentLoss > 0) {
            $this->createValuation([
                'asset_id' => $data['asset_id'],
                'valuation_date' => $data['assessment_date'],
                'valuation_type' => 'impairment',
                'valuation_basis' => 'fair_value',
                'previous_value' => $data['carrying_amount'],
                'new_value' => $data['recoverable_amount'],
                'notes' => 'Impairment recognized',
                'user_id' => $data['user_id'],
            ]);
        }

        return $id;
    }

    /**
     * Get impairments
     */
    public function getImpairments(array $filters = [])
    {
        $query = DB::table('ipsas_impairment as i')
            ->join('ipsas_heritage_asset as a', 'i.asset_id', '=', 'a.id')
            ->select([
                'i.*',
                'a.asset_number',
                'a.title as asset_title',
            ]);

        if (!empty($filters['asset_id'])) {
            $query->where('i.asset_id', $filters['asset_id']);
        }

        if (!empty($filters['recognized_only'])) {
            $query->where('i.impairment_recognized', 1);
        }

        return $query->orderBy('i.assessment_date', 'desc')->get();
    }

    // =========================================================================
    // INSURANCE
    // =========================================================================

    /**
     * Get insurance policies
     */
    public function getInsurancePolicies(array $filters = [])
    {
        $query = DB::table('ipsas_insurance as i')
            ->leftJoin('ipsas_heritage_asset as a', 'i.asset_id', '=', 'a.id')
            ->select([
                'i.*',
                'a.asset_number',
                'a.title as asset_title',
            ]);

        if (!empty($filters['status'])) {
            $query->where('i.status', $filters['status']);
        }

        if (!empty($filters['expiring_within_days'])) {
            $days = (int) $filters['expiring_within_days'];
            $query->where('i.status', 'active')
                  ->whereRaw("i.coverage_end <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)");
        }

        return $query->orderBy('i.coverage_end')->get();
    }

    /**
     * Create insurance policy
     */
    public function createInsurance(array $data): int
    {
        $id = DB::table('ipsas_insurance')->insertGetId([
            'asset_id' => $data['asset_id'] ?? null,
            'policy_number' => $data['policy_number'],
            'policy_type' => $data['policy_type'],
            'insurer' => $data['insurer'],
            'coverage_start' => $data['coverage_start'],
            'coverage_end' => $data['coverage_end'],
            'sum_insured' => $data['sum_insured'],
            'currency' => $data['currency'] ?? 'USD',
            'premium' => $data['premium'] ?? null,
            'deductible' => $data['deductible'] ?? null,
            'coverage_details' => $data['coverage_details'] ?? null,
            'exclusions' => $data['exclusions'] ?? null,
            'status' => 'active',
            'broker_name' => $data['broker_name'] ?? null,
            'broker_contact' => $data['broker_contact'] ?? null,
            'created_by' => $data['user_id'],
        ]);

        // Update asset's insured value if linked
        if (!empty($data['asset_id'])) {
            DB::table('ipsas_heritage_asset')
                ->where('id', $data['asset_id'])
                ->update([
                    'insured_value' => $data['sum_insured'],
                    'insurance_policy' => $data['policy_number'],
                    'insurance_expiry' => $data['coverage_end'],
                ]);
        }

        return $id;
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /**
     * Get all categories
     */
    public function getCategories()
    {
        return DB::table('ipsas_asset_category')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    // =========================================================================
    // FINANCIAL REPORTING
    // =========================================================================

    /**
     * Get financial year summary
     */
    public function getFinancialYearSummary(?string $year = null)
    {
        $year = $year ?? date('Y');

        return DB::table('ipsas_financial_year_summary')
            ->where('financial_year', $year)
            ->first();
    }

    /**
     * Calculate financial year summary
     */
    public function calculateFinancialYearSummary(string $year): array
    {
        $yearStart = $this->getConfig('financial_year_start', '01-01');
        $startDate = $year.'-'.$yearStart;
        $endDate = date('Y-m-d', strtotime($startDate.' +1 year -1 day'));

        // Opening values (assets at start of year)
        $openingAssets = DB::table('ipsas_heritage_asset')
            ->whereDate('created_at', '<', $startDate)
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->count();

        $openingValue = DB::table('ipsas_heritage_asset')
            ->whereDate('created_at', '<', $startDate)
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->sum('acquisition_cost');

        // Additions during year
        $additions = DB::table('ipsas_heritage_asset')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->selectRaw('COUNT(*) as count, SUM(acquisition_cost) as value')
            ->first();

        // Disposals during year
        $disposals = DB::table('ipsas_disposal')
            ->whereDate('disposal_date', '>=', $startDate)
            ->whereDate('disposal_date', '<=', $endDate)
            ->selectRaw('COUNT(*) as count, SUM(carrying_value) as value')
            ->first();

        // Revaluations
        $revaluationsUp = DB::table('ipsas_valuation')
            ->where('valuation_type', 'revaluation')
            ->whereDate('valuation_date', '>=', $startDate)
            ->whereDate('valuation_date', '<=', $endDate)
            ->where('change_amount', '>', 0)
            ->sum('change_amount');

        $revaluationsDown = DB::table('ipsas_valuation')
            ->where('valuation_type', 'revaluation')
            ->whereDate('valuation_date', '>=', $startDate)
            ->whereDate('valuation_date', '<=', $endDate)
            ->where('change_amount', '<', 0)
            ->sum('change_amount');

        // Impairments
        $impairments = DB::table('ipsas_impairment')
            ->where('impairment_recognized', 1)
            ->whereDate('recognition_date', '>=', $startDate)
            ->whereDate('recognition_date', '<=', $endDate)
            ->sum('impairment_loss');

        // Closing values
        $closingAssets = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->count();

        $closingValue = DB::table('ipsas_heritage_asset')
            ->whereNotIn('status', ['disposed', 'lost', 'destroyed'])
            ->sum('current_value');

        return [
            'financial_year' => $year,
            'year_start' => $startDate,
            'year_end' => $endDate,
            'opening_total_assets' => $openingAssets,
            'opening_total_value' => $openingValue,
            'additions_count' => $additions->count ?? 0,
            'additions_value' => $additions->value ?? 0,
            'disposals_count' => $disposals->count ?? 0,
            'disposals_value' => $disposals->value ?? 0,
            'revaluations_increase' => $revaluationsUp,
            'revaluations_decrease' => abs($revaluationsDown),
            'impairments' => $impairments,
            'closing_total_assets' => $closingAssets,
            'closing_total_value' => $closingValue,
        ];
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        $config = DB::table('ipsas_config')
            ->where('config_key', $key)
            ->first();

        return $config ? $config->config_value : $default;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): void
    {
        DB::table('ipsas_config')
            ->updateOrInsert(
                ['config_key' => $key],
                ['config_value' => $value]
            );
    }

    /**
     * Get all configuration
     */
    public function getAllConfig(): array
    {
        return DB::table('ipsas_config')
            ->pluck('config_value', 'config_key')
            ->toArray();
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    /**
     * Log an action
     */
    protected function logAction(
        string $actionType,
        string $entityType,
        int $entityId,
        ?int $userId = null,
        ?array $oldValue = null,
        ?array $newValue = null
    ): void {
        DB::table('ipsas_audit_log')->insert([
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
        ]);
    }
}
