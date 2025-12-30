<?php

declare(strict_types=1);

use AtomExtensions\Database\DatabaseBootstrap;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * GRAP Service.
 *
 * Provides GRAP (Generally Recognised Accounting Practice) functionality
 * for heritage asset management using Laravel Query Builder.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class MuseumGrapService
{
    protected string $culture;

    protected ?int $userId;

    protected string $table = 'spectrum_grap_data';

    public function __construct(string $culture = 'en', ?int $userId = null)
    {
        $this->culture = $culture;
        $this->userId = $userId;

        // Initialize database if not already done
        if (null === DatabaseBootstrap::getCapsule()) {
            DatabaseBootstrap::initializeFromAtom();
        }
    }

    // ========================================================================
    // CRUD OPERATIONS
    // ========================================================================

    /**
     * Create GRAP data record.
     *
     * @param array<string, mixed> $data
     */
    public function create(int $informationObjectId, array $data): int
    {
        $data['information_object_id'] = $informationObjectId;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = DB::table($this->table)->insertGetId($data);
        $this->logAudit($informationObjectId, 'grap', $id, 'create', $data);

        return $id;
    }

    /**
     * Get GRAP data by ID.
     */
    public function getById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    /**
     * Get GRAP data by information object ID.
     */
    public function getByInformationObjectId(int $informationObjectId): ?object
    {
        return DB::table($this->table)
            ->where('information_object_id', $informationObjectId)
            ->first();
    }

    /**
     * Update GRAP data record.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $old = $this->getById($id);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = DB::table($this->table)->where('id', $id)->update($data);

        if ($old) {
            $this->logAudit(
                (int) $old->information_object_id,
                'grap',
                $id,
                'update',
                $data,
                (array) $old
            );
        }

        return $result > 0;
    }

    /**
     * Update by information object ID (create if not exists).
     *
     * @param array<string, mixed> $data
     */
    public function updateOrCreate(int $informationObjectId, array $data): int
    {
        $existing = $this->getByInformationObjectId($informationObjectId);

        if ($existing) {
            $this->update((int) $existing->id, $data);

            return (int) $existing->id;
        }

        return $this->create($informationObjectId, $data);
    }

    /**
     * Delete GRAP data record.
     */
    public function delete(int $id): bool
    {
        $old = $this->getById($id);

        if ($old) {
            $this->logAudit(
                (int) $old->information_object_id,
                'grap',
                $id,
                'delete',
                [],
                (array) $old
            );
        }

        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    // ========================================================================
    // SEARCH & FILTERING
    // ========================================================================

    /**
     * Search GRAP data with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function search(array $filters = []): Collection
    {
        $query = DB::table($this->table . ' as g')
            ->join('information_object as i', 'g.information_object_id', '=', 'i.id')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('i.id', '=', 'i18n.id')
                    ->where('i18n.culture', $this->culture);
            })
            ->select(
                'g.*',
                'i.identifier',
                'i18n.title'
            );

        $this->applyFilters($query, $filters);

        return collect($query->orderBy('g.created_at', 'desc')->get());
    }

    /**
     * Get paginated results.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $query = DB::table($this->table . ' as g')
            ->join('information_object as i', 'g.information_object_id', '=', 'i.id')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('i.id', '=', 'i18n.id')
                    ->where('i18n.culture', $this->culture);
            });

        $this->applyFilters($query, $filters);

        $countQuery = clone $query;
        $total = $countQuery->count();

        $items = $query
            ->select(
                'g.*',
                'i.identifier',
                'i18n.title'
            )
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->orderBy('g.created_at', 'desc')
            ->get();

        return [
            'items' => collect($items),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Apply filters to query builder.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<string, mixed>               $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['asset_class'])) {
            $query->where('g.asset_class', $filters['asset_class']);
        }

        if (!empty($filters['recognition_status'])) {
            $query->where('g.recognition_status', $filters['recognition_status']);
        }

        if (!empty($filters['measurement_basis'])) {
            $query->where('g.measurement_basis', $filters['measurement_basis']);
        }

        if (!empty($filters['cost_center'])) {
            $query->where('g.cost_center', 'like', '%' . $filters['cost_center'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->where('g.initial_recognition_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('g.initial_recognition_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['gl_account_code'])) {
            $query->where('g.gl_account_code', $filters['gl_account_code']);
        }

        if (!empty($filters['compliance_status'])) {
            if ('compliant' === $filters['compliance_status']) {
                $query->whereNotNull('g.recognition_status')
                    ->whereNotNull('g.measurement_basis')
                    ->whereNotNull('g.initial_recognition_date')
                    ->whereNotNull('g.initial_recognition_value')
                    ->whereNotNull('g.acquisition_method_grap');
            } elseif ('non_compliant' === $filters['compliance_status']) {
                $query->where(function ($q) {
                    $q->whereNull('g.recognition_status')
                        ->orWhereNull('g.measurement_basis')
                        ->orWhereNull('g.initial_recognition_date')
                        ->orWhereNull('g.initial_recognition_value')
                        ->orWhereNull('g.acquisition_method_grap');
                });
            }
        }
    }

    // ========================================================================
    // COMPLIANCE CHECKING
    // ========================================================================

    /**
     * Check if item is GRAP compliant.
     */
    public function isCompliant(int $informationObjectId): bool
    {
        $issues = $this->getComplianceIssues($informationObjectId);

        return 0 === count($issues);
    }

    /**
     * Get compliance issues for an item.
     *
     * @return array<int, string>
     */
    public function getComplianceIssues(int $informationObjectId): array
    {
        $data = $this->getByInformationObjectId($informationObjectId);

        if (!$data) {
            return ['No GRAP data recorded'];
        }

        $issues = [];

        if (empty($data->recognition_status)) {
            $issues[] = 'Recognition status not set';
        }

        if (empty($data->measurement_basis)) {
            $issues[] = 'Measurement basis not set';
        }

        if (empty($data->initial_recognition_date)) {
            $issues[] = 'Initial recognition date not set';
        }

        if (empty($data->initial_recognition_value)) {
            $issues[] = 'Initial recognition value not set';
        }

        if (empty($data->acquisition_method_grap)) {
            $issues[] = 'Acquisition method not set';
        }

        if ('donation' === $data->acquisition_method_grap && empty($data->fair_value_at_acquisition)) {
            $issues[] = 'Donated items require fair value at acquisition';
        }

        if ('revaluation_model' === $data->measurement_basis && empty($data->last_revaluation_date)) {
            $issues[] = 'Revaluation model requires revaluation date';
        }

        if ('not_recognised' === $data->recognition_status && empty($data->recognition_status_reason)) {
            $issues[] = 'Non-recognised items require a reason';
        }

        if (empty($data->asset_class)) {
            $issues[] = 'Asset class not set';
        }

        if (empty($data->gl_account_code)) {
            $issues[] = 'GL account code not set';
        }

        return $issues;
    }

    /**
     * Run compliance check on all items.
     *
     * @return array<string, mixed>
     */
    public function runComplianceCheck(): array
    {
        $allItems = DB::table($this->table)->get();
        $compliant = 0;
        $nonCompliant = 0;
        $issues = [];

        foreach ($allItems as $item) {
            $itemIssues = $this->getComplianceIssues((int) $item->information_object_id);

            if (0 === count($itemIssues)) {
                ++$compliant;
            } else {
                ++$nonCompliant;
                $issues[$item->id] = $itemIssues;
            }
        }

        return [
            'total' => count($allItems),
            'compliant' => $compliant,
            'non_compliant' => $nonCompliant,
            'compliance_rate' => count($allItems) > 0
                ? round(($compliant / count($allItems)) * 100, 2)
                : 0,
            'issues' => $issues,
        ];
    }

    // ========================================================================
    // FINANCIAL CALCULATIONS
    // ========================================================================

    /**
     * Calculate carrying amount for an item.
     */
    public function calculateCarryingAmount(int $informationObjectId): float
    {
        $data = $this->getByInformationObjectId($informationObjectId);

        if (!$data) {
            return 0.0;
        }

        $baseValue = 'revaluation_model' === $data->measurement_basis && $data->revaluation_amount
            ? (float) $data->revaluation_amount
            : (float) ($data->initial_recognition_value ?? 0);

        return $baseValue - (float) ($data->accumulated_depreciation ?? 0);
    }

    /**
     * Calculate annual depreciation.
     */
    public function calculateAnnualDepreciation(int $informationObjectId): float
    {
        $data = $this->getByInformationObjectId($informationObjectId);

        if (!$data || 'depreciated' !== $data->depreciation_policy) {
            return 0.0;
        }

        if (empty($data->useful_life_years) || $data->useful_life_years <= 0) {
            return 0.0;
        }

        $depreciableAmount = (float) ($data->initial_recognition_value ?? 0)
            - (float) ($data->residual_value ?? 0);

        if ('straight_line' === $data->depreciation_method || empty($data->depreciation_method)) {
            return $depreciableAmount / (int) $data->useful_life_years;
        }

        return 0.0;
    }

    // ========================================================================
    // REPORTING
    // ========================================================================

    /**
     * Get statistics for dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $total = DB::table($this->table)->count();

        $byAssetClass = DB::table($this->table)
            ->select('asset_class', DB::raw('count(*) as count'))
            ->groupBy('asset_class')
            ->get();

        $byRecognitionStatus = DB::table($this->table)
            ->select('recognition_status', DB::raw('count(*) as count'))
            ->groupBy('recognition_status')
            ->get();

        $byMeasurementBasis = DB::table($this->table)
            ->select('measurement_basis', DB::raw('count(*) as count'))
            ->groupBy('measurement_basis')
            ->get();

        $totalValue = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->sum('initial_recognition_value');

        $totalCarryingAmount = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->selectRaw(
                'SUM(COALESCE(revaluation_amount, initial_recognition_value) '
                . '- COALESCE(accumulated_depreciation, 0)) as total'
            )
            ->value('total');

        return [
            'total' => $total,
            'by_asset_class' => collect($byAssetClass)->mapWithKeys(
                fn ($item) => [$item->asset_class ?? 'unclassified' => $item->count]
            )->all(),
            'by_recognition_status' => collect($byRecognitionStatus)->mapWithKeys(
                fn ($item) => [$item->recognition_status ?? 'unknown' => $item->count]
            )->all(),
            'by_measurement_basis' => collect($byMeasurementBasis)->mapWithKeys(
                fn ($item) => [$item->measurement_basis ?? 'unknown' => $item->count]
            )->all(),
            'total_initial_value' => (float) $totalValue,
            'total_carrying_amount' => (float) ($totalCarryingAmount ?? 0),
        ];
    }

    /**
     * Get balance sheet data grouped by asset class.
     *
     * @return array<string, mixed>
     */
    public function getBalanceSheetData(): array
    {
        $data = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->select(
                'asset_class',
                'gl_account_code',
                DB::raw('SUM(initial_recognition_value) as total_initial_value'),
                DB::raw('SUM(COALESCE(accumulated_depreciation, 0)) as total_depreciation'),
                DB::raw(
                    'SUM(COALESCE(revaluation_amount, initial_recognition_value) '
                    . '- COALESCE(accumulated_depreciation, 0)) as total_carrying_amount'
                ),
                DB::raw('COUNT(*) as item_count')
            )
            ->groupBy('asset_class', 'gl_account_code')
            ->orderBy('asset_class')
            ->get();

        $totals = [
            'total_initial_value' => 0.0,
            'total_depreciation' => 0.0,
            'total_carrying_amount' => 0.0,
            'total_items' => 0,
        ];

        foreach ($data as $row) {
            $totals['total_initial_value'] += (float) $row->total_initial_value;
            $totals['total_depreciation'] += (float) $row->total_depreciation;
            $totals['total_carrying_amount'] += (float) $row->total_carrying_amount;
            $totals['total_items'] += (int) $row->item_count;
        }

        return [
            'by_class' => collect($data)->all(),
            'totals' => $totals,
        ];
    }

    /**
     * Get journal entries for a date range.
     *
     * @return array<int, object>
     */
    public function getJournalEntries(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table($this->table . ' as g')
            ->join('information_object as i', 'g.information_object_id', '=', 'i.id')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('i.id', '=', 'i18n.id')
                    ->where('i18n.culture', $this->culture);
            })
            ->where('g.recognition_status', 'recognised')
            ->select(
                'g.id',
                'g.information_object_id',
                'g.gl_account_code',
                'g.cost_center',
                'g.initial_recognition_date',
                'g.initial_recognition_value',
                'g.acquisition_method_grap',
                'i.identifier',
                'i18n.title'
            );

        if ($dateFrom) {
            $query->where('g.initial_recognition_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('g.initial_recognition_date', '<=', $dateTo);
        }

        return $query->orderBy('g.initial_recognition_date', 'desc')->get()->all();
    }

    /**
     * Get reconciliation report data.
     *
     * @return array<string, mixed>
     */
    public function getReconciliationData(): array
    {
        $openingBalance = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->sum('initial_recognition_value');

        $additions = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->whereYear('initial_recognition_date', date('Y'))
            ->sum('initial_recognition_value');

        $revaluations = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->whereNotNull('revaluation_amount')
            ->selectRaw(
                'SUM(revaluation_amount - initial_recognition_value) as total'
            )
            ->value('total');

        $depreciation = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->sum('accumulated_depreciation');

        $impairments = DB::table($this->table)
            ->where('recognition_status', 'recognised')
            ->sum('impairment_loss_amount');

        $disposals = DB::table($this->table)
            ->whereNotNull('derecognition_date')
            ->sum('derecognition_value');

        $closingBalance = (float) $openingBalance
            + (float) $additions
            + (float) ($revaluations ?? 0)
            - (float) ($depreciation ?? 0)
            - (float) ($impairments ?? 0)
            - (float) ($disposals ?? 0);

        return [
            'opening_balance' => (float) $openingBalance,
            'additions' => (float) $additions,
            'revaluations' => (float) ($revaluations ?? 0),
            'depreciation' => (float) ($depreciation ?? 0),
            'impairments' => (float) ($impairments ?? 0),
            'disposals' => (float) ($disposals ?? 0),
            'closing_balance' => $closingBalance,
        ];
    }

    /**
     * Get GRAP 103 (Heritage Assets) disclosure data.
     *
     * @return array<string, mixed>
     */
    public function getGrap103DisclosureData(): array
    {
        $heritageAssets = DB::table($this->table)
            ->where('asset_class', 'heritage_asset')
            ->where('recognition_status', 'recognised')
            ->get();

        $byMeasurement = DB::table($this->table)
            ->where('asset_class', 'heritage_asset')
            ->where('recognition_status', 'recognised')
            ->select(
                'measurement_basis',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(initial_recognition_value) as total_value')
            )
            ->groupBy('measurement_basis')
            ->get();

        $restrictions = DB::table($this->table)
            ->where('asset_class', 'heritage_asset')
            ->whereNotNull('restrictions_use_disposal')
            ->where('restrictions_use_disposal', '!=', '')
            ->get();

        $conservationCommitments = DB::table($this->table)
            ->where('asset_class', 'heritage_asset')
            ->whereNotNull('conservation_commitments')
            ->where('conservation_commitments', '!=', '')
            ->get();

        $notRecognised = DB::table($this->table)
            ->where('asset_class', 'heritage_asset')
            ->where('recognition_status', 'not_recognised')
            ->get();

        return [
            'total_heritage_assets' => count($heritageAssets),
            'total_value' => collect($heritageAssets)->sum('initial_recognition_value'),
            'by_measurement_basis' => collect($byMeasurement)->all(),
            'items_with_restrictions' => count($restrictions),
            'restrictions' => collect($restrictions)->all(),
            'items_with_conservation_commitments' => count($conservationCommitments),
            'conservation_commitments' => collect($conservationCommitments)->all(),
            'not_recognised_count' => count($notRecognised),
            'not_recognised_items' => collect($notRecognised)->all(),
        ];
    }

    // ========================================================================
    // AUDIT LOG
    // ========================================================================

    /**
     * Log audit entry.
     *
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $oldValues
     */
    protected function logAudit(
        int $objectId,
        string $procedureType,
        int $procedureId,
        string $action,
        array $newValues = [],
        array $oldValues = []
    ): void {
        // Check if audit table exists
        if (!DB::getSchemaBuilder()->hasTable('spectrum_audit_log')) {
            return;
        }

        DB::table('spectrum_audit_log')->insert([
            'object_id' => $objectId,
            'procedure_type' => $procedureType,
            'procedure_id' => $procedureId,
            'action' => $action,
            'action_date' => date('Y-m-d H:i:s'),
            'user_id' => $this->userId,
            'user_name' => $this->getUserName(),
            'ip_address' => $this->getIpAddress(),
            'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
            'new_values' => !empty($newValues) ? json_encode($newValues) : null,
        ]);
    }

    /**
     * Get username for current user.
     */
    protected function getUserName(): ?string
    {
        if (!$this->userId) {
            return null;
        }

        $user = DB::table('user')
            ->where('id', $this->userId)
            ->first();

        return $user->username ?? null;
    }

    /**
     * Get client IP address.
     */
    protected function getIpAddress(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
