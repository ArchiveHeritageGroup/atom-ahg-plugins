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
 * GRAP Heritage Asset Form.
 *
 * Form for editing GRAP 103 financial accounting data.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapHeritageAssetForm extends sfForm
{
    /**
     * Recognition status options (GRAP 103.14-21).
     */
    public static function getRecognitionStatusChoices()
    {
        return [
            '' => '',
            'recognized' => 'Recognised as heritage asset',
            'not_recognized' => 'Not recognised (disclosure only)',
            'pending' => 'Pending assessment',
            'operational' => 'Reclassified as operational asset',
        ];
    }

    /**
     * Measurement basis options (GRAP 103.22-49).
     */
    public static function getMeasurementBasisChoices()
    {
        return [
            '' => '',
            'cost' => 'Cost',
            'fair_value' => 'Fair value',
            'deemed_cost' => 'Deemed cost',
            'nominal' => 'Nominal value (R1)',
            'not_practicable' => 'Not practicable to measure',
        ];
    }

    /**
     * Asset class options.
     */
    public static function getAssetClassChoices()
    {
        return [
            '' => '',
            'art_collections' => 'Art collections',
            'archaeological_sites' => 'Archaeological sites',
            'heritage_buildings' => 'Heritage buildings',
            'monuments' => 'Monuments and statues',
            'archives' => 'Archives and manuscripts',
            'natural_heritage' => 'Natural heritage sites',
            'museum_collections' => 'Museum collections',
            'library_collections' => 'Library collections',
            'cultural_artifacts' => 'Cultural artifacts',
            'other' => 'Other heritage assets',
        ];
    }

    /**
     * Acquisition method options.
     */
    public static function getAcquisitionMethodChoices()
    {
        return [
            '' => '',
            'purchase' => 'Purchase',
            'donation' => 'Donation/Gift',
            'bequest' => 'Bequest',
            'transfer' => 'Transfer from other entity',
            'exchange' => 'Exchange',
            'found' => 'Found property',
            'confiscation' => 'Confiscation/Forfeiture',
            'fieldwork' => 'Fieldwork/Excavation',
            'unknown' => 'Unknown/Historical',
        ];
    }

    /**
     * Depreciation policy options (GRAP 103.50-58).
     */
    public static function getDepreciationPolicyChoices()
    {
        return [
            '' => '',
            'not_depreciated' => 'Not depreciated (indefinite useful life)',
            'depreciated' => 'Depreciated',
            'partial' => 'Partially depreciated (components)',
        ];
    }

    /**
     * Depreciation method options.
     */
    public static function getDepreciationMethodChoices()
    {
        return [
            '' => '',
            'straight_line' => 'Straight-line',
            'reducing_balance' => 'Reducing balance',
            'units_production' => 'Units of production',
        ];
    }

    /**
     * Valuation method options.
     */
    public static function getValuationMethodChoices()
    {
        return [
            '' => '',
            'market_approach' => 'Market approach',
            'cost_approach' => 'Cost approach (replacement)',
            'income_approach' => 'Income approach',
            'expert_opinion' => 'Expert opinion',
            'indexed' => 'Indexed historical cost',
        ];
    }

    /**
     * Revaluation frequency options.
     */
    public static function getRevaluationFrequencyChoices()
    {
        return [
            '' => '',
            'annual' => 'Annually',
            'triennial' => 'Every 3 years',
            'five_yearly' => 'Every 5 years',
            'as_needed' => 'As needed',
            'not_required' => 'Not required',
        ];
    }

    /**
     * Heritage significance options (GRAP 103.70-79).
     */
    public static function getHeritageSignificanceChoices()
    {
        return [
            '' => '',
            'international' => 'International significance',
            'national' => 'National significance',
            'provincial' => 'Provincial significance',
            'local' => 'Local significance',
            'institutional' => 'Institutional significance',
        ];
    }

    /**
     * Condition rating options.
     */
    public static function getConditionRatingChoices()
    {
        return [
            '' => '',
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'critical' => 'Critical',
        ];
    }

    /**
     * Configure form.
     */
    public function configure()
    {
		$this->validatorSchema->setOption('allow_extra_fields', true);

        // Recognition & Measurement (GRAP 103.14-49)
        $this->setWidget('recognition_status', new sfWidgetFormSelect([
            'choices' => self::getRecognitionStatusChoices(),
        ]));
        $this->setValidator('recognition_status', new sfValidatorChoice([
            'choices' => array_keys(self::getRecognitionStatusChoices()),
            'required' => false,
        ]));

        $this->setWidget('recognition_status_reason', new sfWidgetFormTextarea());
        $this->setValidator('recognition_status_reason', new sfValidatorString(['required' => false]));

        $this->setWidget('measurement_basis', new sfWidgetFormSelect([
            'choices' => self::getMeasurementBasisChoices(),
        ]));
        $this->setValidator('measurement_basis', new sfValidatorChoice([
            'choices' => array_keys(self::getMeasurementBasisChoices()),
            'required' => false,
        ]));

        $this->setWidget('recognition_date', new sfWidgetFormInput(['type' => 'date']));
        $this->setValidator('recognition_date', new sfValidatorDate(['required' => false]));

        $this->setWidget('initial_carrying_amount', new sfWidgetFormInput());
        $this->setValidator('initial_carrying_amount', new sfValidatorNumber(['required' => false]));

        $this->setWidget('current_carrying_amount', new sfWidgetFormInput());
        $this->setValidator('current_carrying_amount', new sfValidatorNumber(['required' => false]));

        // Classification
        $this->setWidget('asset_class', new sfWidgetFormSelect([
            'choices' => self::getAssetClassChoices(),
        ]));
        $this->setValidator('asset_class', new sfValidatorChoice([
            'choices' => array_keys(self::getAssetClassChoices()),
            'required' => false,
        ]));

        $this->setWidget('asset_sub_class', new sfWidgetFormInput());
        $this->setValidator('asset_sub_class', new sfValidatorString(['required' => false, 'max_length' => 100]));

        // Acquisition
        $this->setWidget('acquisition_method', new sfWidgetFormSelect([
            'choices' => self::getAcquisitionMethodChoices(),
        ]));
        $this->setValidator('acquisition_method', new sfValidatorChoice([
            'choices' => array_keys(self::getAcquisitionMethodChoices()),
            'required' => false,
        ]));

        $this->setWidget('acquisition_date', new sfWidgetFormInput(['type' => 'date']));
        $this->setValidator('acquisition_date', new sfValidatorDate(['required' => false]));

        $this->setWidget('cost_of_acquisition', new sfWidgetFormInput());
        $this->setValidator('cost_of_acquisition', new sfValidatorNumber(['required' => false]));

        $this->setWidget('fair_value_at_acquisition', new sfWidgetFormInput());
        $this->setValidator('fair_value_at_acquisition', new sfValidatorNumber(['required' => false]));

        // Financial Classification
        $this->setWidget('gl_account_code', new sfWidgetFormInput());
        $this->setValidator('gl_account_code', new sfValidatorString(['required' => false, 'max_length' => 50]));

        $this->setWidget('cost_center', new sfWidgetFormInput());
        $this->setValidator('cost_center', new sfValidatorString(['required' => false, 'max_length' => 50]));

        $this->setWidget('fund_source', new sfWidgetFormInput());
        $this->setValidator('fund_source', new sfValidatorString(['required' => false, 'max_length' => 100]));

        // Depreciation (GRAP 103.50-58)
        $this->setWidget('depreciation_policy', new sfWidgetFormSelect([
            'choices' => self::getDepreciationPolicyChoices(),
        ]));
        $this->setValidator('depreciation_policy', new sfValidatorChoice([
            'choices' => array_keys(self::getDepreciationPolicyChoices()),
            'required' => false,
        ]));

        $this->setWidget('useful_life_years', new sfWidgetFormInput(['type' => 'number']));
        $this->setValidator('useful_life_years', new sfValidatorInteger(['required' => false, 'min' => 0]));

        $this->setWidget('residual_value', new sfWidgetFormInput());
        $this->setValidator('residual_value', new sfValidatorNumber(['required' => false]));

        $this->setWidget('depreciation_method', new sfWidgetFormSelect([
            'choices' => self::getDepreciationMethodChoices(),
        ]));
        $this->setValidator('depreciation_method', new sfValidatorChoice([
            'choices' => array_keys(self::getDepreciationMethodChoices()),
            'required' => false,
        ]));

        $this->setWidget('accumulated_depreciation', new sfWidgetFormInput());
        $this->setValidator('accumulated_depreciation', new sfValidatorNumber(['required' => false]));

        // Revaluation (GRAP 103.42-49)
        $this->setWidget('last_valuation_date', new sfWidgetFormInput(['type' => 'date']));
        $this->setValidator('last_valuation_date', new sfValidatorDate(['required' => false]));

        $this->setWidget('last_valuation_amount', new sfWidgetFormInput());
        $this->setValidator('last_valuation_amount', new sfValidatorNumber(['required' => false]));

        $this->setWidget('valuer_name', new sfWidgetFormInput());
        $this->setValidator('valuer_name', new sfValidatorString(['required' => false, 'max_length' => 255]));

        $this->setWidget('valuer_credentials', new sfWidgetFormInput());
        $this->setValidator('valuer_credentials', new sfValidatorString(['required' => false, 'max_length' => 255]));

        $this->setWidget('valuation_method', new sfWidgetFormSelect([
            'choices' => self::getValuationMethodChoices(),
        ]));
        $this->setValidator('valuation_method', new sfValidatorChoice([
            'choices' => array_keys(self::getValuationMethodChoices()),
            'required' => false,
        ]));

        $this->setWidget('revaluation_frequency', new sfWidgetFormSelect([
            'choices' => self::getRevaluationFrequencyChoices(),
        ]));
        $this->setValidator('revaluation_frequency', new sfValidatorChoice([
            'choices' => array_keys(self::getRevaluationFrequencyChoices()),
            'required' => false,
        ]));

        // Disclosure (GRAP 103.70-79)
        $this->setWidget('heritage_significance', new sfWidgetFormSelect([
            'choices' => self::getHeritageSignificanceChoices(),
        ]));
        $this->setValidator('heritage_significance', new sfValidatorChoice([
            'choices' => array_keys(self::getHeritageSignificanceChoices()),
            'required' => false,
        ]));

        $this->setWidget('condition_rating', new sfWidgetFormSelect([
            'choices' => self::getConditionRatingChoices(),
        ]));
        $this->setValidator('condition_rating', new sfValidatorChoice([
            'choices' => array_keys(self::getConditionRatingChoices()),
            'required' => false,
        ]));

        $this->setWidget('restrictions_on_use', new sfWidgetFormTextarea());
        $this->setValidator('restrictions_on_use', new sfValidatorString(['required' => false]));

        $this->setWidget('conservation_commitments', new sfWidgetFormTextarea());
        $this->setValidator('conservation_commitments', new sfValidatorString(['required' => false]));

        // Insurance
        $this->setWidget('insurance_value', new sfWidgetFormInput());
        $this->setValidator('insurance_value', new sfValidatorNumber(['required' => false]));

        $this->setWidget('insurance_policy_number', new sfWidgetFormInput());
        $this->setValidator('insurance_policy_number', new sfValidatorString(['required' => false, 'max_length' => 100]));

        $this->setWidget('insurance_provider', new sfWidgetFormInput());
        $this->setValidator('insurance_provider', new sfValidatorString(['required' => false, 'max_length' => 255]));

        $this->setWidget('insurance_expiry_date', new sfWidgetFormInput(['type' => 'date']));
        $this->setValidator('insurance_expiry_date', new sfValidatorDate(['required' => false]));

        // Location
        $this->setWidget('current_location', new sfWidgetFormInput());
        $this->setValidator('current_location', new sfValidatorString(['required' => false, 'max_length' => 255]));

        // Notes
        $this->setWidget('notes', new sfWidgetFormTextarea());
        $this->setValidator('notes', new sfValidatorString(['required' => false]));

        // Set default values from passed data
        if (!empty($this->defaults)) {
            $this->setDefaults($this->defaults);
        }

// Set labels
        $this->widgetSchema->setLabels([
            'recognition_status' => 'Recognition status',
            'recognition_status_reason' => 'Recognition status reason',
            'measurement_basis' => 'Measurement basis',
            'recognition_date' => 'Recognition date',
            'initial_carrying_amount' => 'Initial carrying amount (R)',
            'current_carrying_amount' => 'Current carrying amount (R)',
            'asset_class' => 'Asset class',
            'asset_sub_class' => 'Asset sub-class',
            'acquisition_method' => 'Acquisition method',
            'acquisition_date' => 'Acquisition date',
            'cost_of_acquisition' => 'Cost of acquisition (R)',
            'fair_value_at_acquisition' => 'Fair value at acquisition (R)',
            'gl_account_code' => 'GL account code',
            'cost_center' => 'Cost centre',
            'fund_source' => 'Fund source',
            'depreciation_policy' => 'Depreciation policy',
            'useful_life_years' => 'Useful life (years)',
            'residual_value' => 'Residual value (R)',
            'depreciation_method' => 'Depreciation method',
            'accumulated_depreciation' => 'Accumulated depreciation (R)',
            'last_valuation_date' => 'Last valuation date',
            'last_valuation_amount' => 'Last valuation amount (R)',
            'valuer_name' => 'Valuer name',
            'valuer_credentials' => 'Valuer credentials',
            'valuation_method' => 'Valuation method',
            'revaluation_frequency' => 'Revaluation frequency',
            'heritage_significance' => 'Heritage significance',
            'condition_rating' => 'Condition rating',
            'restrictions_on_use' => 'Restrictions on use or disposal',
            'conservation_commitments' => 'Conservation commitments',
            'insurance_value' => 'Insurance value (R)',
            'insurance_policy_number' => 'Insurance policy number',
            'insurance_provider' => 'Insurance provider',
            'insurance_expiry_date' => 'Insurance expiry date',
            'current_location' => 'Current location',
            'notes' => 'Notes',
        ]);

        $this->widgetSchema->setNameFormat('grap[%s]');
    }
}
