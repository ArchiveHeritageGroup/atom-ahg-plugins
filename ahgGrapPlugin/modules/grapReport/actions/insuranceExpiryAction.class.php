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
 * GRAP Insurance Expiry Report Action.
 *
 * Shows items with insurance issues.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapReportInsuranceExpiryAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('admin/login');
        }

        $this->items = $this->getInsuranceExpiry();
    }

    protected function getInsuranceExpiry(): array
    {
        try {
            $results = DB::table('v_grap_insurance_expiry as v')
                ->join('slug as s', 'v.object_id', '=', 's.object_id')
                ->select(['v.*', 's.slug'])
                ->orderBy('v.insurance_expiry_date')
                ->get();

            return $results->map(fn ($row) => (array) $row)->toArray();
        } catch (\Exception $e) {
            // Fallback query
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
                    'g.insurance_value',
                    'g.insurance_policy_number',
                    'g.insurance_provider',
                    'g.insurance_expiry_date',
                    'g.current_carrying_amount',
                ])
                ->whereNull('g.derecognition_date')
                ->orderBy('g.insurance_expiry_date')
                ->get();

            return $results->map(function ($row) {
                $arr = (array) $row;
                if (empty($row->insurance_expiry_date) && empty($row->insurance_value)) {
                    $arr['insurance_status'] = 'No insurance';
                } elseif (empty($row->insurance_expiry_date)) {
                    $arr['insurance_status'] = 'No expiry set';
                } elseif ($row->insurance_expiry_date < date('Y-m-d')) {
                    $arr['insurance_status'] = 'Expired';
                } elseif ($row->insurance_expiry_date < date('Y-m-d', strtotime('+30 days'))) {
                    $arr['insurance_status'] = 'Expiring soon';
                } else {
                    $arr['insurance_status'] = 'Current';
                }

                return $arr;
            })->toArray();
        }
    }
}
