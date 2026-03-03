<?php
/**
 * Reusable form partial for custom field definition create/edit.
 *
 * Variables: $definition (object|null), $entityTypes, $fieldTypes, $taxonomies, $fieldGroups
 */
$def = $definition;
$isEdit = !empty($def);
$saveUrl = url_for(['module' => 'customFieldAdmin', 'action' => 'save']);
$indexUrl = url_for(['module' => 'customFieldAdmin', 'action' => 'index']);
?>

<form id="cf-definition-form" method="post" action="<?php echo $saveUrl; ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?php echo $def->id; ?>">
    <?php endif; ?>

    <div class="row">
        <!-- Left column — main fields -->
        <div class="col-md-8">
            <div class="mb-3">
                <label for="cf-field-label" class="form-label">Field Label <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="cf-field-label" name="field_label"
                       value="<?php echo htmlspecialchars($def->field_label ?? ''); ?>" required
                       placeholder="e.g. Schedule Code">
                <div class="form-text">The display name shown on forms and views.</div>
            </div>

            <div class="mb-3">
                <label for="cf-field-key" class="form-label">Field Key</label>
                <input type="text" class="form-control font-monospace" id="cf-field-key" name="field_key"
                       value="<?php echo htmlspecialchars($def->field_key ?? ''); ?>"
                       <?php echo $isEdit ? 'readonly' : ''; ?>
                       pattern="[a-z0-9_]+" placeholder="auto-generated from label">
                <div class="form-text">Machine name (lowercase, underscores). Auto-generated on create, cannot be changed after.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cf-field-type" class="form-label">Field Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="cf-field-type" name="field_type" required>
                        <?php foreach ($fieldTypes as $typeKey => $typeLabel): ?>
                            <option value="<?php echo $typeKey; ?>"
                                <?php echo ($def->field_type ?? 'text') === $typeKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($typeLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cf-entity-type" class="form-label">Entity Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="cf-entity-type" name="entity_type" required
                            <?php echo $isEdit ? 'disabled' : ''; ?>>
                        <option value="">— Select —</option>
                        <?php foreach ($entityTypes as $etKey => $etLabel): ?>
                            <option value="<?php echo $etKey; ?>"
                                <?php echo ($def->entity_type ?? '') === $etKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($etLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($def->entity_type); ?>">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dropdown taxonomy selector (shown only when type=dropdown) -->
            <div class="mb-3" id="cf-dropdown-taxonomy-wrap" style="display:none;">
                <label for="cf-dropdown-taxonomy" class="form-label">Dropdown Taxonomy</label>
                <select class="form-select" id="cf-dropdown-taxonomy" name="dropdown_taxonomy">
                    <option value="">— Select taxonomy —</option>
                    <?php foreach ($taxonomies as $tax): ?>
                        <option value="<?php echo htmlspecialchars($tax->taxonomy); ?>"
                            <?php echo ($def->dropdown_taxonomy ?? '') === $tax->taxonomy ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tax->taxonomy_label); ?> (<?php echo htmlspecialchars($tax->taxonomy); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Select which ahg_dropdown taxonomy to use for values.</div>
            </div>

            <div class="mb-3">
                <label for="cf-field-group" class="form-label">Field Group</label>
                <input type="text" class="form-control" id="cf-field-group" name="field_group"
                       value="<?php echo htmlspecialchars($def->field_group ?? ''); ?>"
                       list="cf-field-group-list" placeholder="e.g. Legacy Data, Tracking">
                <datalist id="cf-field-group-list">
                    <?php foreach ($fieldGroups as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="form-text">Group related fields under a section heading.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cf-default-value" class="form-label">Default Value</label>
                    <input type="text" class="form-control" id="cf-default-value" name="default_value"
                           value="<?php echo htmlspecialchars($def->default_value ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cf-validation-rule" class="form-label">Validation Rule</label>
                    <input type="text" class="form-control font-monospace" id="cf-validation-rule" name="validation_rule"
                           value="<?php echo htmlspecialchars($def->validation_rule ?? ''); ?>"
                           placeholder="e.g. max:255 or regex:/^[A-Z]/">
                </div>
            </div>

            <div class="mb-3">
                <label for="cf-help-text" class="form-label">Help Text</label>
                <input type="text" class="form-control" id="cf-help-text" name="help_text"
                       value="<?php echo htmlspecialchars($def->help_text ?? ''); ?>"
                       placeholder="Guidance shown below the input field">
            </div>

            <div class="mb-3">
                <label for="cf-sort-order" class="form-label">Sort Order</label>
                <input type="number" class="form-control" id="cf-sort-order" name="sort_order"
                       value="<?php echo (int) ($def->sort_order ?? 0); ?>" style="max-width:120px">
            </div>
        </div>

        <!-- Right column — checkboxes -->
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header"><h6 class="mb-0">Options</h6></div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cf-is-required" name="is_required" value="1"
                            <?php echo ($def->is_required ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cf-is-required">Required</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cf-is-searchable" name="is_searchable" value="1"
                            <?php echo ($def->is_searchable ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cf-is-searchable">Searchable</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cf-is-visible-public" name="is_visible_public" value="1"
                            <?php echo ($def->is_visible_public ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cf-is-visible-public">Visible on Public View</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cf-is-visible-edit" name="is_visible_edit" value="1"
                            <?php echo ($def->is_visible_edit ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cf-is-visible-edit">Visible on Edit Form</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cf-is-repeatable" name="is_repeatable" value="1"
                            <?php echo ($def->is_repeatable ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cf-is-repeatable">Repeatable (multiple values)</label>
                    </div>
                    <hr>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="cf-is-active" name="is_active" value="1"
                            <?php echo ($def->is_active ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cf-is-active">Active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" id="cf-save-btn">
            <i class="bi bi-check-circle"></i> <?php echo $isEdit ? 'Update Field' : 'Create Field'; ?>
        </button>
        <a href="<?php echo $indexUrl; ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('cf-definition-form');
    var fieldTypeSelect = document.getElementById('cf-field-type');
    var dropdownWrap = document.getElementById('cf-dropdown-taxonomy-wrap');
    var labelInput = document.getElementById('cf-field-label');
    var keyInput = document.getElementById('cf-field-key');
    var isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;

    // Toggle dropdown taxonomy visibility
    function toggleDropdown() {
        dropdownWrap.style.display = (fieldTypeSelect.value === 'dropdown') ? '' : 'none';
    }
    fieldTypeSelect.addEventListener('change', toggleDropdown);
    toggleDropdown();

    // Auto-generate field_key from label (only on create)
    if (!isEdit) {
        labelInput.addEventListener('input', function() {
            if (!keyInput.dataset.manual) {
                var key = this.value.toLowerCase().trim()
                    .replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
                keyInput.value = key.substring(0, 100);
            }
        });
        keyInput.addEventListener('input', function() {
            this.dataset.manual = '1';
        });
    }

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('cf-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        fetch(form.action, { method: 'POST', body: new FormData(form) })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    window.location.href = <?php echo json_encode($indexUrl); ?>;
                } else {
                    alert(data.error || 'Save failed.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> <?php echo $isEdit ? 'Update Field' : 'Create Field'; ?>';
                }
            })
            .catch(function() {
                alert('Network error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> <?php echo $isEdit ? 'Update Field' : 'Create Field'; ?>';
            });
    });
});
</script>
