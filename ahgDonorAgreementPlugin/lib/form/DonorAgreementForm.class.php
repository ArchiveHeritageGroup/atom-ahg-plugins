<?php

use ahgCorePlugin\Services\AhgTaxonomyService;

class DonorAgreementForm extends sfForm
{
    protected $taxonomyService;

    public function configure()
    {
        $this->taxonomyService = new AhgTaxonomyService();
        $statusChoices = $this->taxonomyService->getAgreementStatuses(false);

        $this->setWidgets([
            'agreement_type_id' => new sfWidgetFormChoice(['choices' => $this->getAgreementTypeChoices()]),
            'title' => new sfWidgetFormInputText(),
            'description' => new sfWidgetFormTextarea(),
            'status' => new sfWidgetFormChoice(['choices' => $statusChoices]),
            'donor_id' => new sfWidgetFormInputHidden(),
            'donor_name' => new sfWidgetFormInputText(),
            'institution_name' => new sfWidgetFormInputText(),
            'legal_representative' => new sfWidgetFormInputText(),
            'legal_representative_title' => new sfWidgetFormInputText(),
            'repository_representative' => new sfWidgetFormInputText(),
            'repository_representative_title' => new sfWidgetFormInputText(),
            'agreement_date' => new sfWidgetFormInputText(['type' => 'date']),
            'effective_date' => new sfWidgetFormInputText(['type' => 'date']),
            'expiry_date' => new sfWidgetFormInputText(['type' => 'date']),
            'review_date' => new sfWidgetFormInputText(['type' => 'date']),
            'scope_description' => new sfWidgetFormTextarea(),
            'extent_statement' => new sfWidgetFormInputText(),
            'transfer_date' => new sfWidgetFormInputText(['type' => 'date']),
            'general_terms' => new sfWidgetFormTextarea(),
            'special_conditions' => new sfWidgetFormTextarea(),
            'accession_id' => new sfWidgetFormInputHidden(),
            'information_object_id' => new sfWidgetFormInputHidden(),
            'internal_notes' => new sfWidgetFormTextarea(),
            'is_template' => new sfWidgetFormInputCheckbox(),
        ]);

        $this->setValidators([
            'agreement_type_id' => new sfValidatorInteger(['required' => true]),
            'title' => new sfValidatorString(['required' => true, 'max_length' => 500]),
            'description' => new sfValidatorString(['required' => false]),
            'status' => new sfValidatorChoice(['choices' => array_keys($statusChoices)]),
            'donor_id' => new sfValidatorInteger(['required' => false]),
            'donor_name' => new sfValidatorString(['required' => false, 'max_length' => 255]),
            'institution_name' => new sfValidatorString(['required' => false, 'max_length' => 255]),
            'legal_representative' => new sfValidatorString(['required' => false, 'max_length' => 255]),
            'legal_representative_title' => new sfValidatorString(['required' => false, 'max_length' => 255]),
            'repository_representative' => new sfValidatorString(['required' => false, 'max_length' => 255]),
            'repository_representative_title' => new sfWidgetFormInputText(['required' => false, 'max_length' => 255]),
            'agreement_date' => new sfValidatorDate(['required' => false]),
            'effective_date' => new sfValidatorDate(['required' => false]),
            'expiry_date' => new sfValidatorDate(['required' => false]),
            'review_date' => new sfValidatorDate(['required' => false]),
            'scope_description' => new sfValidatorString(['required' => false]),
            'extent_statement' => new sfValidatorString(['required' => false, 'max_length' => 255]),
            'transfer_date' => new sfValidatorDate(['required' => false]),
            'general_terms' => new sfValidatorString(['required' => false]),
            'special_conditions' => new sfValidatorString(['required' => false]),
            'accession_id' => new sfValidatorInteger(['required' => false]),
            'information_object_id' => new sfValidatorInteger(['required' => false]),
            'internal_notes' => new sfValidatorString(['required' => false]),
            'is_template' => new sfValidatorBoolean(['required' => false]),
        ]);

        $this->widgetSchema->setNameFormat('agreement[%s]');
    }

    protected function getAgreementTypeChoices(): array
    {
        $choices = ['' => '-- Select --'];
        $rows = \Illuminate\Database\Capsule\Manager::table('agreement_type')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->select('id', 'name')
            ->get();

        foreach ($rows as $row) {
            $choices[$row->id] = $row->name;
        }

        return $choices;
    }
}
