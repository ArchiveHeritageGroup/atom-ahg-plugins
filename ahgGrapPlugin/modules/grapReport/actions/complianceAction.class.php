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
 * GRAP Compliance Check Report Action.
 *
 * Shows items with incomplete GRAP data.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapReportComplianceAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('admin/login');
        }

        $this->items = $this->getComplianceReport();

        // Calculate summary stats
        $total = count($this->items);
        $compliant = count(array_filter($this->items, fn ($i) => ($i['compliance_percentage'] ?? 0) >= 80));
        $this->stats = [
            'total' => $total,
            'compliant' => $compliant,
            'non_compliant' => $total - $compliant,
            'average' => $total > 0 ? round(array_sum(array_column($this->items, 'compliance_percentage')) / $total, 1) : 0,
        ];
    }

    protected function getComplianceReport(): array
    {
        try {
            $results = DB::table('v_grap_compliance_check as v')
                ->join('slug as s', 'v.object_id', '=', 's.object_id')
                ->select(['v.*', 's.slug'])
                ->orderBy('v.compliance_percentage')
                ->get();

            return $results->map(fn ($row) => (array) $row)->toArray();
        } catch (\Exception $e) {
            // Fallback query - calculate compliance manually
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
                ->get();

            return $results->map(function ($row) {
                $arr = (array) $row;

                // Required GRAP 103 fields for compliance
                $requiredFields = [
                    'recognition_status',
                    'measurement_basis',
                    'asset_class',
                    'acquisition_method',
                    'acquisition_date',
                    'current_carrying_amount',
                    'heritage_significance',
                    'condition_rating',
                ];

                $recommendedFields = [
                    'gl_account_code',
                    'depreciation_policy',
                    'last_valuation_date',
                    'insurance_value',
                    'current_location',
                ];

                $filledRequired = 0;
                $filledRecommended = 0;
                $missingFields = [];

                foreach ($requiredFields as $field) {
                    if (!empty($row->$field)) {
                        ++$filledRequired;
                    } else {
                        $missingFields[] = $field;
                    }
                }

                foreach ($recommendedFields as $field) {
                    if (!empty($row->$field)) {
                        ++$filledRecommended;
                    }
                }

                // Calculate percentage (required fields weighted 70%, recommended 30%)
                $requiredPct = (count($requiredFields) > 0) ? ($filledRequired / count($requiredFields)) * 70 : 0;
                $recommendedPct = (count($recommendedFields) > 0) ? ($filledRecommended / count($recommendedFields)) * 30 : 0;

                $arr['compliance_percentage'] = round($requiredPct + $recommendedPct);
                $arr['missing_fields'] = implode(', ', array_slice($missingFields, 0, 3));
                $arr['missing_count'] = count($missingFields);

                return $arr;
            })->sortBy('compliance_percentage')->values()->toArray();
        }
    }
}
