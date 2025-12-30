<?php
/**
 * Condition Template Selector and Form Partial
 * 
 * Include this in the Spectrum condition check edit form.
 * 
 * Variables expected:
 * - $templateService: ConditionTemplateService instance
 * - $conditionCheckId: Current condition check ID (or null for new)
 * - $selectedTemplateId: Currently selected template ID
 * - $materialType: Object's material type (for default template selection)
 * - $canEdit: Whether user can edit
 */

// Load service if not provided
if (!isset($templateService)) {
    require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/ConditionTemplateService.php';
    $templateService = new \AtoM\Framework\Services\ConditionTemplateService();
}

// Get all templates
$templates = $templateService->getAllTemplates();

// Get existing data if editing
$existingData = [];
if (isset($conditionCheckId) && $conditionCheckId) {
    $existingData = $templateService->getCheckData($conditionCheckId);
}

// Get selected template
$selectedTemplate = null;
if (isset($selectedTemplateId) && $selectedTemplateId) {
    $selectedTemplate = $templateService->getTemplate($selectedTemplateId);
} elseif (isset($materialType) && $materialType) {
    $selectedTemplate = $templateService->getDefaultTemplate($materialType);
}

// Group templates by material type for dropdown
$templatesByMaterial = [];
foreach ($templates as $t) {
    $templatesByMaterial[$t->material_type][] = $t;
}
?>

<div class="condition-template-container" id="conditionTemplateContainer">
    <!-- Template Selector -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>
                <?php echo __('Condition Assessment Template') ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="templateSelector">
                        <?php echo __('Select Assessment Template') ?>
                    </label>
                    <select class="form-select" id="templateSelector" name="template_id" <?php echo !$canEdit ? 'disabled' : '' ?>>
                        <option value=""><?php echo __('-- Select Template --') ?></option>
                        <?php foreach ($templatesByMaterial as $matType => $matTemplates): ?>
                        <optgroup label="<?php echo ucfirst(str_replace('_', ' ', $matType)) ?>">
                            <?php foreach ($matTemplates as $t): ?>
                            <option value="<?php echo $t->id ?>" 
                                    data-material="<?php echo $t->material_type ?>"
                                    <?php echo ($selectedTemplate && $selectedTemplate->id == $t->id) ? 'selected' : '' ?>>
                                <?php echo esc_entities($t->name) ?>
                                <?php echo $t->is_default ? ' ★' : '' ?>
                            </option>
                            <?php endforeach ?>
                        </optgroup>
                        <?php endforeach ?>
                    </select>
                    <small class="text-muted">
                        <?php echo __('Choose a template based on the object\'s material type') ?>
                    </small>
                </div>
                <div class="col-md-6">
                    <?php if ($selectedTemplate): ?>
                    <div class="alert alert-info mb-0 py-2">
                        <strong><?php echo esc_entities($selectedTemplate->name) ?></strong><br>
                        <small><?php echo esc_entities($selectedTemplate->description) ?></small>
                    </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Form Container -->
    <div id="templateFormContainer">
        <?php if ($selectedTemplate): ?>
            <?php echo $templateService->renderForm($selectedTemplate, $existingData, !$canEdit) ?>
        <?php else: ?>
            <div class="alert alert-secondary">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo __('Select a template above to display the assessment form.') ?>
            </div>
        <?php endif ?>
    </div>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    const templateSelector = document.getElementById('templateSelector');
    const formContainer = document.getElementById('templateFormContainer');
    
    if (templateSelector) {
        templateSelector.addEventListener('change', function() {
            const templateId = this.value;
            
            if (!templateId) {
                formContainer.innerHTML = '<div class="alert alert-secondary"><i class="fas fa-info-circle me-2"></i><?php echo __('Select a template above to display the assessment form.') ?></div>';
                return;
            }
            
            // Show loading
            formContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><?php echo __('Loading template...') ?></div>';
            
            // Fetch template form via AJAX
            fetch('/condition/template/' + templateId + '/form<?php echo isset($conditionCheckId) ? '?check_id=' . $conditionCheckId : '' ?>')
                .then(response => response.text())
                .then(html => {
                    formContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading template:', error);
                    formContainer.innerHTML = '<div class="alert alert-danger"><?php echo __('Error loading template. Please try again.') ?></div>';
                });
        });
    }
});
</script>

<style>
.condition-template-form .condition-section {
    border-left: 4px solid #28a745;
}

.condition-template-form .condition-field {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 0.75rem;
}

.condition-template-form .condition-field:last-child {
    border-bottom: none;
}

.condition-template-form .rating-field .form-check-inline {
    margin-right: 0.5rem;
}

.condition-template-form .rating-field .form-check-input:checked + .form-check-label {
    font-weight: bold;
    color: #28a745;
}

/* Rating visual enhancement */
.condition-template-form .rating-field {
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 0.25rem;
}

/* Print styles */
@media print {
    .condition-template-form select,
    .condition-template-form input[type="text"],
    .condition-template-form textarea {
        border: none !important;
        background: transparent !important;
    }
    
    .condition-template-form select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
}
</style>
