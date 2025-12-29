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

/**
 * GRAP 103 Disclosure Summary Action.
 *
 * Generates the GRAP 103 disclosure note for financial statements.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapReportDisclosureAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('admin/login');
        }

        $this->grapService = new GrapService();

        // Get summary by asset class (main disclosure table)
        $this->summaryByClass = $this->grapService->getSummaryByClass();

        // Calculate grand totals
        $this->grandTotals = [
            'asset_count' => array_sum(array_column($this->summaryByClass, 'asset_count')),
            'recognized_count' => array_sum(array_column($this->summaryByClass, 'recognized_count')),
            'not_recognized_count' => array_sum(array_column($this->summaryByClass, 'not_recognized_count')),
            'total_cost' => array_sum(array_column($this->summaryByClass, 'total_cost')),
            'total_carrying_amount' => array_sum(array_column($this->summaryByClass, 'total_carrying_amount')),
            'total_accumulated_depreciation' => array_sum(array_column($this->summaryByClass, 'total_accumulated_depreciation')),
            'total_impairment' => array_sum(array_column($this->summaryByClass, 'total_impairment')),
            'total_insurance_value' => array_sum(array_column($this->summaryByClass, 'total_insurance_value')),
        ];

        // Get assets not recognized (for separate disclosure)
        $this->notRecognized = $this->grapService->getAssetRegister(['recognition_status' => 'not_recognized']);

        // Get valuation information for disclosure
        $this->valuationSchedule = $this->grapService->getValuationSchedule();

        // Financial year (South African: April - March)
        $now = new DateTime();
        $month = (int) $now->format('n');
        if ($month >= 4) {
            $this->financialYear = $now->format('Y').'/'.(int) $now->format('Y') + 1;
        } else {
            $this->financialYear = ((int) $now->format('Y') - 1).'/'.$now->format('Y');
        }

        // Report date
        $this->reportDate = $now->format('d F Y');
    }
}
