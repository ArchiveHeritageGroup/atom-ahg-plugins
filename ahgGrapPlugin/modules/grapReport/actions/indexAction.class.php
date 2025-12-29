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
 * GRAP Reports Dashboard Action.
 *
 * Main dashboard for GRAP 103 financial reports and asset management.
 * Uses Laravel Query Builder for PHP 8.3 compatibility.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapReportIndexAction extends sfAction
{
    public function execute($request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('admin/login');
        }

        $this->grapService = new GrapService();

        // Get statistics for dashboard
        $this->stats = $this->grapService->getStatistics();

        // Get summary by class
        $this->summaryByClass = $this->grapService->getSummaryByClass();

        // Get recent GRAP entries with slug for linking
        $this->recentAssets = $this->getRecentAssets(10);

        // Get items needing attention (with slugs)
        $this->overdueValuations = $this->getValuationScheduleWithSlugs();
        $this->expiredInsurance = $this->getInsuranceExpiryWithSlugs();

        // Compliance issues (items below 50%)
        $compliance = $this->getComplianceReportWithSlugs();
        $this->complianceIssues = array_filter($compliance, function ($item) {
            return $item['compliance_percentage'] < 50;
        });
    }

    /**
     * Get recent GRAP entries with information object details.
     *
     * @param int $limit Number of records to return
     *
     * @return array
     */
    protected function getRecentAssets(int $limit = 10): array
    {
        $results = DB::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->select([
                'g.*',
                'io.identifier as reference_code',
                'ioi.title',
                's.slug',
            ])
            ->whereNull('g.derecognition_date')
            ->orderByDesc('g.updated_at')
            ->limit($limit)
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Get valuation schedule with slugs for linking.
     *
     * @return array
     */
    protected function getValuationScheduleWithSlugs(): array
    {
        // First check if view exists, otherwise use direct query
        try {
            $results = DB::table('v_grap_valuation_schedule as v')
                ->join('slug as s', 'v.object_id', '=', 's.object_id')
                ->select(['v.*', 's.slug'])
                ->whereIn('v.valuation_status', ['Overdue', 'Never valued'])
                ->orderBy('v.last_valuation_date')
                ->get();

            return $results->map(fn ($row) => (array) $row)->toArray();
        } catch (\Exception $e) {
            // Fallback if view doesn't exist
            return $this->getValuationScheduleFallback();
        }
    }

    /**
     * Fallback method for valuation schedule if view doesn't exist.
     *
     * @return array
     */
    protected function getValuationScheduleFallback(): array
    {
        $results = DB::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->select([
                'g.object_id',
                'io.identifier as reference_code',
                'ioi.title',
                's.slug',
                'g.last_valuation_date',
                'g.revaluation_frequency',
            ])
            ->whereNull('g.derecognition_date')
            ->where(function ($query) {
                $query->whereNull('g.last_valuation_date')
                    ->orWhere('g.last_valuation_date', '<', DB::raw("DATE_SUB(CURDATE(), INTERVAL 5 YEAR)"));
            })
            ->orderBy('g.last_valuation_date')
            ->get();

        return $results->map(function ($row) {
            $arr = (array) $row;
            $arr['valuation_status'] = empty($row->last_valuation_date) ? 'Never valued' : 'Overdue';

            return $arr;
        })->toArray();
    }

    /**
     * Get insurance expiry with slugs for linking.
     *
     * @return array
     */
    protected function getInsuranceExpiryWithSlugs(): array
    {
        try {
            $results = DB::table('v_grap_insurance_expiry as v')
                ->join('slug as s', 'v.object_id', '=', 's.object_id')
                ->select(['v.*', 's.slug'])
                ->whereIn('v.insurance_status', ['Expired', 'Expiring soon', 'No insurance'])
                ->orderBy('v.insurance_expiry_date')
                ->get();

            return $results->map(fn ($row) => (array) $row)->toArray();
        } catch (\Exception $e) {
            // Fallback if view doesn't exist
            return $this->getInsuranceExpiryFallback();
        }
    }

    /**
     * Fallback method for insurance expiry if view doesn't exist.
     *
     * @return array
     */
    protected function getInsuranceExpiryFallback(): array
    {
        $results = DB::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->select([
                'g.object_id',
                'io.identifier as reference_code',
                'ioi.title',
                's.slug',
                'g.insurance_expiry_date',
                'g.insurance_value',
            ])
            ->whereNull('g.derecognition_date')
            ->where(function ($query) {
                $query->whereNull('g.insurance_expiry_date')
                    ->orWhere('g.insurance_expiry_date', '<', DB::raw('CURDATE()'))
                    ->orWhere('g.insurance_expiry_date', '<', DB::raw("DATE_ADD(CURDATE(), INTERVAL 30 DAY)"));
            })
            ->orderBy('g.insurance_expiry_date')
            ->get();

        return $results->map(function ($row) {
            $arr = (array) $row;
            if (empty($row->insurance_expiry_date)) {
                $arr['insurance_status'] = 'No insurance';
            } elseif ($row->insurance_expiry_date < date('Y-m-d')) {
                $arr['insurance_status'] = 'Expired';
            } else {
                $arr['insurance_status'] = 'Expiring soon';
            }

            return $arr;
        })->toArray();
    }

    /**
     * Get compliance report with slugs for linking.
     *
     * @return array
     */
    protected function getComplianceReportWithSlugs(): array
    {
        try {
            $results = DB::table('v_grap_compliance_check as v')
                ->join('slug as s', 'v.object_id', '=', 's.object_id')
                ->select(['v.*', 's.slug'])
                ->orderBy('v.compliance_percentage')
                ->get();

            return $results->map(fn ($row) => (array) $row)->toArray();
        } catch (\Exception $e) {
            // Fallback if view doesn't exist
            return $this->getComplianceReportFallback();
        }
    }

    /**
     * Fallback method for compliance report if view doesn't exist.
     *
     * @return array
     */
    protected function getComplianceReportFallback(): array
    {
        $results = DB::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->select([
                'g.object_id',
                'io.identifier as reference_code',
                'ioi.title',
                's.slug',
                'g.recognition_status',
                'g.measurement_basis',
                'g.asset_class',
                'g.acquisition_method',
                'g.current_carrying_amount',
            ])
            ->whereNull('g.derecognition_date')
            ->get();

        return $results->map(function ($row) {
            $arr = (array) $row;
            // Calculate simple compliance percentage
            $fields = ['recognition_status', 'measurement_basis', 'asset_class', 'acquisition_method', 'current_carrying_amount'];
            $filled = 0;
            foreach ($fields as $field) {
                if (!empty($row->$field)) {
                    ++$filled;
                }
            }
            $arr['compliance_percentage'] = round(($filled / count($fields)) * 100);

            return $arr;
        })->toArray();
    }
}
