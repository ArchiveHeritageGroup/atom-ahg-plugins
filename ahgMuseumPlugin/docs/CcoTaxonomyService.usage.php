<?php

/**
 * Example usage of CcoTaxonomyService in ahgMuseumPluginEditAction.
 *
 * Replace the static arrays with database-driven terms.
 */

// In your editAction.class.php, add these changes:

// 1. Add property for taxonomy service
// protected $ccoTaxonomyService;

// 2. Initialize in earlyExecute() or execute()
// $this->ccoTaxonomyService = new \ahgMuseumPlugin\Services\CcoTaxonomyService($this->getUserCulture());

// 3. Replace static array usage with service calls:

// BEFORE (static array):
// $this->form->setWidget('museum_creator_role', new sfWidgetFormSelect(['choices' => self::$creatorRoles]));

// AFTER (database-driven):
// $this->form->setWidget('museum_creator_role', new sfWidgetFormSelect([
//     'choices' => $this->ccoTaxonomyService->getCreatorRoles()
// ]));

// ============================================================================
// UPDATED addMuseumFields() METHOD EXAMPLE
// ============================================================================

/**
 * Add museum fields using CcoTaxonomyService.
 */
protected function addMuseumFields()
{
    // Initialize taxonomy service
    $ccoService = new \ahgMuseumPlugin\Services\CcoTaxonomyService($this->getUserCulture());

    // Work Type - from taxonomy
    $this->form->setWidget('museum_work_type', new sfWidgetFormSelect([
        'choices' => $ccoService->getWorkTypes(),
    ]));
    $this->form->setValidator('museum_work_type', new sfValidatorChoice([
        'choices' => array_keys($ccoService->getWorkTypes()),
        'required' => false,
    ]));
    $this->form->setDefault('museum_work_type', $this->museumData['work_type'] ?? '');

    // Materials - from taxonomy (multi-select)
    $this->form->setWidget('museum_materials', new sfWidgetFormSelect([
        'choices' => $ccoService->getMaterials(false),
        'multiple' => true,
    ], [
        'size' => 5,
    ]));
    $this->form->setValidator('museum_materials', new sfValidatorChoice([
        'choices' => array_keys($ccoService->getMaterials(false)),
        'required' => false,
        'multiple' => true,
    ]));
    $materials = isset($this->museumData['materials']) ? json_decode($this->museumData['materials'], true) : [];
    $this->form->setDefault('museum_materials', $materials ?: []);

    // Techniques - from taxonomy (multi-select)
    $this->form->setWidget('museum_techniques', new sfWidgetFormSelect([
        'choices' => $ccoService->getTechniques(false),
        'multiple' => true,
    ], [
        'size' => 5,
    ]));
    $this->form->setValidator('museum_techniques', new sfValidatorChoice([
        'choices' => array_keys($ccoService->getTechniques(false)),
        'required' => false,
        'multiple' => true,
    ]));
    $techniques = isset($this->museumData['techniques']) ? json_decode($this->museumData['techniques'], true) : [];
    $this->form->setDefault('museum_techniques', $techniques ?: []);

    // Creator Role - from taxonomy
    $this->addTaxonomySelectField('creator_role', $ccoService->getCreatorRoles());

    // Attribution Qualifier - from taxonomy
    $this->addTaxonomySelectField('creator_qualifier', $ccoService->getAttributionQualifiers());

    // Date Qualifier - from taxonomy
    $this->addTaxonomySelectField('creation_date_qualifier', $ccoService->getDateQualifiers());

    // Condition Term - from taxonomy
    $this->addTaxonomySelectField('condition_term', $ccoService->getConditionTerms());

    // Subject Type - from taxonomy
    $this->addTaxonomySelectField('subject_indexing_type', $ccoService->getSubjectTypes());

    // Inscription Type - from taxonomy
    $this->addTaxonomySelectField('inscription_type', $ccoService->getInscriptionTypes());

    // Related Work Type - from taxonomy
    $this->addTaxonomySelectField('related_work_type', $ccoService->getRelatedWorkTypes());

    // Rights Type - from taxonomy
    $this->addTaxonomySelectField('rights_type', $ccoService->getRightsTypes());

    // Add remaining text fields...
    $this->addTextField('measurements', true);
    $this->addTextField('creation_date_earliest');
    $this->addTextField('creation_date_latest');
    // ... etc.
}

/**
 * Helper to add taxonomy-based select field.
 */
protected function addTaxonomySelectField(string $name, array $choices)
{
    $fieldName = 'museum_'.$name;
    $this->form->setWidget($fieldName, new sfWidgetFormSelect(['choices' => $choices]));
    $this->form->setValidator($fieldName, new sfValidatorChoice([
        'choices' => array_keys($choices),
        'required' => false,
    ]));
    $this->form->setDefault($fieldName, $this->museumData[$name] ?? '');
}

// ============================================================================
// UPDATED INDEX ACTION - Loading labels from taxonomy
// ============================================================================

/**
 * In ahgMuseumPluginIndexAction, use CcoTaxonomyService for labels.
 */
protected function addControlledVocabularyLabels()
{
    $ccoService = new \ahgMuseumPlugin\Services\CcoTaxonomyService($this->getUserCulture());

    if (!empty($this->museumData['creator_role'])) {
        $this->museumData['creator_role_label'] = $ccoService->getCreatorRoleLabel($this->museumData['creator_role']);
    }

    if (!empty($this->museumData['creator_qualifier'])) {
        $this->museumData['creator_qualifier_label'] = $ccoService->getAttributionQualifierLabel($this->museumData['creator_qualifier']);
    }

    if (!empty($this->museumData['creation_date_qualifier'])) {
        $this->museumData['creation_date_qualifier_label'] = $ccoService->getDateQualifierLabel($this->museumData['creation_date_qualifier']);
    }

    if (!empty($this->museumData['condition_term'])) {
        $this->museumData['condition_term_label'] = $ccoService->getConditionTermLabel($this->museumData['condition_term']);
    }

    if (!empty($this->museumData['subject_indexing_type'])) {
        $this->museumData['subject_indexing_type_label'] = $ccoService->getSubjectTypeLabel($this->museumData['subject_indexing_type']);
    }

    if (!empty($this->museumData['inscription_type'])) {
        $this->museumData['inscription_type_label'] = $ccoService->getInscriptionTypeLabel($this->museumData['inscription_type']);
    }

    if (!empty($this->museumData['related_work_type'])) {
        $this->museumData['related_work_type_label'] = $ccoService->getRelatedWorkTypeLabel($this->museumData['related_work_type']);
    }

    if (!empty($this->museumData['rights_type'])) {
        $this->museumData['rights_type_label'] = $ccoService->getRightsTypeLabel($this->museumData['rights_type']);
    }

    if (!empty($this->museumData['work_type'])) {
        $this->museumData['work_type_label'] = $ccoService->getWorkTypeLabel($this->museumData['work_type']);
    }
}
