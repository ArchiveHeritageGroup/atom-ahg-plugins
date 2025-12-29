<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * GRAP Service.
 *
 * Business logic for GRAP 103 heritage asset management.
 * Uses Laravel Query Builder for PHP 8.3 compatibility.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapService
{
    /**
     * Get GRAP data for an object.
     *
     * @param int $objectId The information object ID
     *
     * @return null|array
     */
    public function get(int $objectId): ?array
    {
        $row = DB::table('grap_heritage_asset')
            ->where('object_id', $objectId)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * Save GRAP data for an object.
     *
     * @param int      $objectId The information object ID
     * @param array    $data     The data to save
     * @param null|int $userId   The user ID
     *
     * @return bool
     */
    public function save(int $objectId, array $data, ?int $userId = null): bool
    {
        $data['object_id'] = $objectId;
        $data['updated_by'] = $userId;
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Check if record exists
        $existing = $this->get($objectId);

        if ($existing) {
            return $this->update($objectId, $data);
        }

        $data['created_by'] = $userId;
        $data['created_at'] = date('Y-m-d H:i:s');

        return $this->insert($data);
    }

    /**
     * Insert new GRAP record using Laravel Query Builder.
     *
     * @param array $data The data to insert
     *
     * @return bool
     */
    protected function insert(array $data): bool
    {
        return DB::table('grap_heritage_asset')->insert($data);
    }

    /**
     * Update existing GRAP record using Laravel Query Builder.
     *
     * @param int   $objectId The information object ID
     * @param array $data     The data to update
     *
     * @return bool
     */
    protected function update(int $objectId, array $data): bool
    {
        unset($data['object_id'], $data['created_by'], $data['created_at']);

        $affected = DB::table('grap_heritage_asset')
            ->where('object_id', $objectId)
            ->update($data);

        return $affected >= 0;
    }

    /**
     * Delete GRAP record using Laravel Query Builder.
     *
     * @param int $objectId The information object ID
     *
     * @return bool
     */
    public function delete(int $objectId): bool
    {
        $affected = DB::table('grap_heritage_asset')
            ->where('object_id', $objectId)
            ->delete();

        return $affected > 0;
    }

    /**
     * Get asset register (all recognized assets) using Laravel Query Builder.
     *
     * @param array $filters Optional filters
     *
     * @return array
     */
    public function getAssetRegister(array $filters = []): array
    {
        $query = DB::table('v_grap_asset_register');

        if (!empty($filters['asset_class'])) {
            $query->where('asset_class', $filters['asset_class']);
        }

        if (!empty($filters['gl_account_code'])) {
            $query->where('gl_account_code', 'LIKE', $filters['gl_account_code'].'%');
        }

        if (!empty($filters['recognition_status'])) {
            $query->where('recognition_status', $filters['recognition_status']);
        }

        $results = $query
            ->orderBy('gl_account_code')
            ->orderBy('asset_class')
            ->orderBy('reference_code')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get GRAP 103 summary by asset class using Laravel Query Builder.
     *
     * @return array
     */
    public function getSummaryByClass(): array
    {
        $results = DB::table('v_grap_103_summary')->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get items due for revaluation using Laravel Query Builder.
     *
     * @return array
     */
    public function getValuationSchedule(): array
    {
        $results = DB::table('v_grap_valuation_schedule')
            ->whereIn('valuation_status', ['Overdue', 'Never valued'])
            ->orderBy('last_valuation_date')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get insurance expiry report using Laravel Query Builder.
     *
     * @return array
     */
    public function getInsuranceExpiry(): array
    {
        $results = DB::table('v_grap_insurance_expiry')
            ->whereIn('insurance_status', ['Expired', 'Expiring soon', 'No insurance'])
            ->orderBy('insurance_expiry_date')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get compliance check report using Laravel Query Builder.
     *
     * @return array
     */
    public function getComplianceReport(): array
    {
        $results = DB::table('v_grap_compliance_check')
            ->orderBy('compliance_percentage')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get overall statistics using Laravel Query Builder.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_assets' => 0,
            'recognized_assets' => 0,
            'not_recognized_assets' => 0,
            'total_carrying_amount' => 0,
            'total_insurance_value' => 0,
            'overdue_valuations' => 0,
            'expired_insurance' => 0,
            'average_compliance' => 0,
        ];

        // Total and recognition counts
        $row = DB::table('grap_heritage_asset')
            ->whereNull('derecognition_date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN recognition_status = 'recognized' THEN 1 ELSE 0 END) as recognized")
            ->selectRaw("SUM(CASE WHEN recognition_status = 'not_recognized' THEN 1 ELSE 0 END) as not_recognized")
            ->selectRaw('SUM(COALESCE(current_carrying_amount, 0)) as total_carrying')
            ->selectRaw('SUM(COALESCE(insurance_value, 0)) as total_insurance')
            ->first();

        if ($row) {
            $stats['total_assets'] = (int) $row->total;
            $stats['recognized_assets'] = (int) $row->recognized;
            $stats['not_recognized_assets'] = (int) $row->not_recognized;
            $stats['total_carrying_amount'] = (float) $row->total_carrying;
            $stats['total_insurance_value'] = (float) $row->total_insurance;
        }

        // Overdue valuations
        $stats['overdue_valuations'] = (int) DB::table('v_grap_valuation_schedule')
            ->where('valuation_status', 'Overdue')
            ->count();

        // Expired insurance
        $stats['expired_insurance'] = (int) DB::table('v_grap_insurance_expiry')
            ->where('insurance_status', 'Expired')
            ->count();

        // Average compliance
        $avgCompliance = DB::table('v_grap_compliance_check')
            ->selectRaw('AVG(compliance_percentage) as avg_compliance')
            ->first();

        if ($avgCompliance && null !== $avgCompliance->avg_compliance) {
            $stats['average_compliance'] = round((float) $avgCompliance->avg_compliance, 1);
        }

        return $stats;
    }

    /**
     * Record a valuation using Laravel Query Builder.
     *
     * @param int      $objectId The information object ID
     * @param array    $data     The valuation data
     * @param null|int $userId   The user ID
     *
     * @return bool
     */
    public function recordValuation(int $objectId, array $data, ?int $userId = null): bool
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return false;
        }

        // Calculate surplus/deficit
        $previous = (float) ($grapData['current_carrying_amount'] ?? 0);
        $new = (float) $data['valuation_amount'];

        // Prepare history data
        $historyData = [
            'grap_asset_id' => $grapData['id'],
            'valuation_date' => $data['valuation_date'],
            'valuation_amount' => $data['valuation_amount'],
            'previous_amount' => $previous,
            'valuation_method' => $data['valuation_method'] ?? null,
            'valuer_name' => $data['valuer_name'] ?? null,
            'valuer_credentials' => $data['valuer_credentials'] ?? null,
            'valuer_organization' => $data['valuer_organization'] ?? null,
            'valuation_report_ref' => $data['valuation_report_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'revaluation_surplus' => $new > $previous ? $new - $previous : null,
            'revaluation_deficit' => $new < $previous ? $previous - $new : null,
        ];

        // Insert valuation history using Laravel Query Builder
        DB::table('grap_valuation_history')->insert($historyData);

        // Update main record
        return $this->update($objectId, [
            'last_valuation_date' => $data['valuation_date'],
            'last_valuation_amount' => $data['valuation_amount'],
            'current_carrying_amount' => $data['valuation_amount'],
            'valuer_name' => $data['valuer_name'] ?? $grapData['valuer_name'],
            'valuer_credentials' => $data['valuer_credentials'] ?? $grapData['valuer_credentials'],
            'valuation_method' => $data['valuation_method'] ?? $grapData['valuation_method'],
        ]);
    }

    /**
     * Get valuation history for an asset using Laravel Query Builder.
     *
     * @param int $objectId The information object ID
     *
     * @return array
     */
    public function getValuationHistory(int $objectId): array
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return [];
        }

        $results = DB::table('grap_valuation_history')
            ->where('grap_asset_id', $grapData['id'])
            ->orderByDesc('valuation_date')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Record an impairment assessment using Laravel Query Builder.
     *
     * @param int      $objectId The information object ID
     * @param array    $data     The impairment data
     * @param null|int $userId   The user ID
     *
     * @return bool
     */
    public function recordImpairment(int $objectId, array $data, ?int $userId = null): bool
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return false;
        }

        // Prepare impairment data
        $impairmentData = [
            'grap_asset_id' => $grapData['id'],
            'assessment_date' => $data['assessment_date'],
            'indicators_identified' => $data['indicators_identified'] ? 1 : 0,
            'indicator_description' => $data['indicator_description'] ?? null,
            'carrying_amount_before' => $grapData['current_carrying_amount'],
            'recoverable_amount' => $data['recoverable_amount'] ?? null,
            'impairment_loss' => $data['impairment_loss'] ?? null,
            'reversal_amount' => $data['reversal_amount'] ?? null,
            'assessor_name' => $data['assessor_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Insert impairment record using Laravel Query Builder
        DB::table('grap_impairment_assessment')->insert($impairmentData);

        // Update main record
        $updateData = [
            'last_impairment_date' => $data['assessment_date'],
            'impairment_indicators' => $data['indicators_identified'] ? 1 : 0,
        ];

        if (!empty($data['impairment_loss'])) {
            $updateData['impairment_loss'] = ($grapData['impairment_loss'] ?? 0) + $data['impairment_loss'];
            $updateData['current_carrying_amount'] = $grapData['current_carrying_amount'] - $data['impairment_loss'];
        }

        return $this->update($objectId, $updateData);
    }

    /**
     * Record asset movement using Laravel Query Builder.
     *
     * @param int      $objectId The information object ID
     * @param array    $data     The movement data
     * @param null|int $userId   The user ID
     *
     * @return bool
     */
    public function recordMovement(int $objectId, array $data, ?int $userId = null): bool
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return false;
        }

        $movementData = [
            'grap_asset_id' => $grapData['id'],
            'movement_date' => $data['movement_date'],
            'movement_type' => $data['movement_type'],
            'from_location' => $data['from_location'] ?? $grapData['current_location'],
            'to_location' => $data['to_location'] ?? null,
            'from_entity' => $data['from_entity'] ?? null,
            'to_entity' => $data['to_entity'] ?? null,
            'reason' => $data['reason'] ?? null,
            'authorization_ref' => $data['authorization_ref'] ?? null,
            'authorized_by' => $data['authorized_by'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Insert movement record using Laravel Query Builder
        $result = DB::table('grap_movement_register')->insert($movementData);

        // Update current location
        if ($result && !empty($data['to_location'])) {
            $this->update($objectId, ['current_location' => $data['to_location']]);
        }

        return $result;
    }

    /**
     * Derecognize an asset using Laravel Query Builder.
     *
     * @param int      $objectId The information object ID
     * @param array    $data     The derecognition data
     * @param null|int $userId   The user ID
     *
     * @return bool
     */
    public function derecognize(int $objectId, array $data, ?int $userId = null): bool
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return false;
        }

        $carryingAmount = (float) ($grapData['current_carrying_amount'] ?? 0);
        $proceeds = (float) ($data['proceeds'] ?? 0);
        $gainLoss = $proceeds - $carryingAmount;

        return $this->update($objectId, [
            'derecognition_date' => $data['derecognition_date'],
            'derecognition_reason' => $data['reason'],
            'derecognition_proceeds' => $proceeds,
            'gain_loss_on_disposal' => $gainLoss,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Export asset register to CSV.
     *
     * @param array $filters Optional filters
     *
     * @return string
     */
    public function exportToCsv(array $filters = []): string
    {
        $assets = $this->getAssetRegister($filters);

        $output = fopen('php://temp', 'r+');

        // Headers
        fputcsv($output, [
            'Reference Code',
            'Title',
            'Asset Class',
            'GL Account',
            'Cost Centre',
            'Recognition Status',
            'Measurement Basis',
            'Acquisition Date',
            'Acquisition Method',
            'Cost of Acquisition',
            'Current Carrying Amount',
            'Last Valuation Date',
            'Last Valuation Amount',
            'Accumulated Depreciation',
            'Heritage Significance',
            'Condition',
            'Location',
            'Insurance Value',
            'Insurance Expiry',
        ]);

        foreach ($assets as $asset) {
            fputcsv($output, [
                $asset['reference_code'] ?? '',
                $asset['title'] ?? '',
                $asset['asset_class'] ?? '',
                $asset['gl_account_code'] ?? '',
                $asset['cost_center'] ?? '',
                $asset['recognition_status'] ?? '',
                $asset['measurement_basis'] ?? '',
                $asset['acquisition_date'] ?? '',
                $asset['acquisition_method'] ?? '',
                $asset['cost_of_acquisition'] ?? '',
                $asset['current_carrying_amount'] ?? '',
                $asset['last_valuation_date'] ?? '',
                $asset['last_valuation_amount'] ?? '',
                $asset['accumulated_depreciation'] ?? '',
                $asset['heritage_significance'] ?? '',
                $asset['condition_rating'] ?? '',
                $asset['current_location'] ?? '',
                $asset['insurance_value'] ?? '',
                $asset['insurance_expiry_date'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get movement history for an asset using Laravel Query Builder.
     *
     * @param int $objectId The information object ID
     *
     * @return array
     */
    public function getMovementHistory(int $objectId): array
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return [];
        }

        $results = DB::table('grap_movement_register')
            ->where('grap_asset_id', $grapData['id'])
            ->orderByDesc('movement_date')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get impairment history for an asset using Laravel Query Builder.
     *
     * @param int $objectId The information object ID
     *
     * @return array
     */
    public function getImpairmentHistory(int $objectId): array
    {
        $grapData = $this->get($objectId);
        if (!$grapData) {
            return [];
        }

        $results = DB::table('grap_impairment_assessment')
            ->where('grap_asset_id', $grapData['id'])
            ->orderByDesc('assessment_date')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }
}
