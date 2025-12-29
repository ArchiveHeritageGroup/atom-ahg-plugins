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
 * GRAP Heritage Asset Financial Data Edit Action.
 *
 * Manages GRAP 103 financial accounting data for heritage assets.
 * This is a standalone form accessible from the "More" menu on
 * information object view pages.
 *
 * Uses Laravel Query Builder for PHP 8.3 compatibility.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapEditAction extends sfAction
{
    /** @var QubitInformationObject */
    public $resource;

    /** @var sfForm */
    public $form;

    /** @var array GRAP data */
    public $grapData = [];

    /**
     * Execute action.
     */
    public function execute($request)
    {
        // Get the information object using Laravel Query Builder
        $this->resource = $this->loadResource($request->getParameter('slug'));

        if (!$this->resource instanceof QubitInformationObject) {
            $this->forward404();
        }

        // Check user has edit permission
        if (!($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }

        // Load GRAP data
        $this->loadGrapData();

        // Create form
        if (!class_exists('GrapHeritageAssetForm')) {
            require_once sfConfig::get('sf_plugins_dir').'/ahgGrapPlugin/lib/form/GrapHeritageAssetForm.class.php';
        }
		
		$this->form = new GrapHeritageAssetForm($this->grapData);

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->form->bind($request->getParameter('grap'));

            if ($this->form->isValid()) {
                $this->saveGrapData();

                // Add success notice
                $this->context->user->setFlash('notice', $this->context->i18n->__('GRAP financial data saved.'));

                // Redirect back to the information object
                $this->redirect([$this->resource, 'module' => 'informationobject']); 
            } else {
				//die('Errors: ' . print_r($this->form->getErrorSchema()->getErrors(), true));
			}
        }
    }

    /**
     * Load information object by slug using Laravel Query Builder.
     *
     * @param string $slug The slug to look up
     *
     * @return null|QubitInformationObject
     */
    protected function loadResource(?string $slug): ?QubitInformationObject
    {
        if (empty($slug)) {
            return null;
        }

        // Use Laravel Query Builder to find the object ID
        $result = DB::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$result) {
            return null;
        }
        // Load the QubitInformationObject using its native method
        $object = QubitInformationObject::getById($result->object_id);

        return $object instanceof QubitInformationObject ? $object : null;
    }

    /**
     * Load GRAP data from database using Laravel Query Builder.
     */
    protected function loadGrapData(): void
    {
        $row = DB::table('grap_heritage_asset')
            ->where('object_id', $this->resource->id)
            ->first();

        if ($row) {
            $this->grapData = (array) $row;
        } else {
            $this->grapData = ['object_id' => $this->resource->id];
        }
    }

    /**
     * Save GRAP data to database using Laravel Query Builder.
     */
    protected function saveGrapData(): void
    {
        $values = $this->form->getValues();

        // Prepare data array
        $data = [
            'object_id' => $this->resource->id,

            // Recognition & Measurement
            'recognition_status' => $values['recognition_status'] ?: null,
            'recognition_status_reason' => $values['recognition_status_reason'] ?: null,
            'measurement_basis' => $values['measurement_basis'] ?: null,
            'recognition_date' => $values['recognition_date'] ?: null,
            'initial_carrying_amount' => $this->parseDecimal($values['initial_carrying_amount']),
            'current_carrying_amount' => $this->parseDecimal($values['current_carrying_amount']),

            // Classification
            'asset_class' => $values['asset_class'] ?: null,
            'asset_sub_class' => $values['asset_sub_class'] ?: null,

            // Acquisition
            'acquisition_method' => $values['acquisition_method'] ?: null,
            'acquisition_date' => $values['acquisition_date'] ?: null,
            'cost_of_acquisition' => $this->parseDecimal($values['cost_of_acquisition']),
            'fair_value_at_acquisition' => $this->parseDecimal($values['fair_value_at_acquisition']),

            // Financial Classification
            'gl_account_code' => $values['gl_account_code'] ?: null,
            'cost_center' => $values['cost_center'] ?: null,
            'fund_source' => $values['fund_source'] ?: null,

            // Depreciation
            'depreciation_policy' => $values['depreciation_policy'] ?: null,
            'useful_life_years' => $values['useful_life_years'] ? (int) $values['useful_life_years'] : null,
            'residual_value' => $this->parseDecimal($values['residual_value']),
            'depreciation_method' => $values['depreciation_method'] ?: null,
            'accumulated_depreciation' => $this->parseDecimal($values['accumulated_depreciation']),

            // Revaluation
            'last_valuation_date' => $values['last_valuation_date'] ?: null,
            'last_valuation_amount' => $this->parseDecimal($values['last_valuation_amount']),
            'valuer_name' => $values['valuer_name'] ?: null,
            'valuer_credentials' => $values['valuer_credentials'] ?: null,
            'valuation_method' => $values['valuation_method'] ?: null,
            'revaluation_frequency' => $values['revaluation_frequency'] ?: null,

            // Disclosure (GRAP 103.70-79)
            'heritage_significance' => $values['heritage_significance'] ?: null,
            'condition_rating' => $values['condition_rating'] ?: null,
            'restrictions_on_use' => $values['restrictions_on_use'] ?: null,
            'conservation_commitments' => $values['conservation_commitments'] ?: null,

            // Insurance
            'insurance_value' => $this->parseDecimal($values['insurance_value']),
            'insurance_policy_number' => $values['insurance_policy_number'] ?: null,
            'insurance_provider' => $values['insurance_provider'] ?: null,
            'insurance_expiry_date' => $values['insurance_expiry_date'] ?: null,

            // Location
            'current_location' => $values['current_location'] ?: null,

            // Notes
            'notes' => $values['notes'] ?: null,

            // Audit fields
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Check if record exists using Laravel Query Builder
        $existing = DB::table('grap_heritage_asset')
            ->where('object_id', $this->resource->id)
            ->first();

        if ($existing) {
            // Update - remove object_id from data as it's the key
            unset($data['object_id']);

            DB::table('grap_heritage_asset')
                ->where('object_id', $this->resource->id)
                ->update($data);
        } else {
            // Insert - add created_at
            $data['created_at'] = date('Y-m-d H:i:s');

            // Get current user ID if available
            if ($this->context->user->isAuthenticated()) {
                $data['created_by'] = $this->context->user->getAttribute('user_id');
            }

            DB::table('grap_heritage_asset')->insert($data);
        }
    }

    /**
     * Parse decimal value from form input.
     *
     * @param mixed $value The value to parse
     *
     * @return null|float
     */
    protected function parseDecimal($value): ?float
    {
        if (null === $value || '' === $value) {
            return null;
        }

        // Remove currency symbols and spaces
        $value = preg_replace('/[R$€£\s,]/', '', (string) $value);

        return is_numeric($value) ? (float) $value : null;
    }
}
