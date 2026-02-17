<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'documentTemplates']); ?>">Document Templates</a></li>
        <li class="breadcrumb-item active"><?php echo $template ? 'Edit Template' : 'New Template'; ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-file-alt text-primary me-2"></i><?php echo $template ? __('Edit Document Template') : __('New Document Template'); ?></h1>

<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post">
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Template Details'); ?></h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Template Name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($template->name ?? ''); ?>" placeholder="e.g. Death Certificate, Land Title Deed">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Document Type'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="document_type" class="form-control" required value="<?php echo htmlspecialchars($template->document_type ?? ''); ?>" placeholder="e.g. certificate, deed, correspondence, register" list="docTypeList">
                    <datalist id="docTypeList">
                        <option value="certificate">
                        <option value="deed">
                        <option value="correspondence">
                        <option value="register">
                        <option value="report">
                        <option value="form">
                        <option value="map">
                        <option value="photograph">
                        <option value="newspaper">
                        <option value="ledger">
                    </datalist>
                    <small class="text-muted"><?php echo __('Category of document this template applies to'); ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Description'); ?></label>
                    <textarea name="description" class="form-control" rows="3" placeholder="<?php echo __('Describe what this template is used for...'); ?>"><?php echo htmlspecialchars($template->description ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo __('Extraction Fields'); ?></h5>
                <button type="button" class="btn btn-sm btn-primary" id="addFieldBtn"><i class="fas fa-plus me-1"></i><?php echo __('Add Field'); ?></button>
            </div>
            <div class="card-body">
                <p class="text-muted small"><?php echo __('Define the fields that should be extracted from documents matching this template.'); ?></p>
                <div id="fieldsContainer">
                    <!-- Dynamic field rows inserted here -->
                </div>
                <div id="noFieldsMsg" class="text-center text-muted py-3" style="display:none;">
                    <i class="fas fa-info-circle me-1"></i><?php echo __('No fields defined. Click "Add Field" to begin.'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="fields_json" id="fieldsJsonInput">

<div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'documentTemplates']); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
    </a>
    <div>
        <?php if ($template): ?>
            <button type="button" class="btn btn-outline-danger me-2" id="deleteBtn"><i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?></button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo $template ? __('Save Changes') : __('Create Template'); ?>
        </button>
    </div>
</div>
</form>

<?php if ($template): ?>
<form method="post" id="deleteForm" style="display:none;">
    <input type="hidden" name="form_action" value="delete">
</form>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var fields = [];
    var container = document.getElementById('fieldsContainer');
    var noMsg = document.getElementById('noFieldsMsg');
    var jsonInput = document.getElementById('fieldsJsonInput');

    var fieldTypes = [
        {value: 'text', label: 'Text'},
        {value: 'date', label: 'Date'},
        {value: 'number', label: 'Number'},
        {value: 'person', label: 'Person Name'},
        {value: 'place', label: 'Place Name'},
        {value: 'organization', label: 'Organisation'},
        {value: 'identifier', label: 'Identifier/Reference'},
        {value: 'enum', label: 'Enumerated List'}
    ];

    // Load existing fields
    <?php
    $existingFields = [];
    if ($template && !empty($template->fields_json)) {
        $decoded = is_string($template->fields_json) ? json_decode($template->fields_json, true) : $template->fields_json;
        if (is_array($decoded)) { $existingFields = $decoded; }
    }
    ?>
    var existing = <?php echo json_encode($existingFields); ?>;
    if (existing && existing.length) {
        for (var i = 0; i < existing.length; i++) {
            addField(existing[i]);
        }
    }
    updateUI();

    document.getElementById('addFieldBtn').addEventListener('click', function() {
        addField({name: '', type: 'text', required: false, description: ''});
    });

    function addField(data) {
        var idx = fields.length;
        fields.push(data);

        var row = document.createElement('div');
        row.className = 'card mb-2 field-row';
        row.dataset.idx = idx;

        var typeOptions = '';
        for (var t = 0; t < fieldTypes.length; t++) {
            var sel = data.type === fieldTypes[t].value ? ' selected' : '';
            typeOptions += '<option value="' + fieldTypes[t].value + '"' + sel + '>' + fieldTypes[t].label + '</option>';
        }

        row.innerHTML =
            '<div class="card-body p-2">' +
                '<div class="row g-2 align-items-center">' +
                    '<div class="col-md-4">' +
                        '<input type="text" class="form-control form-control-sm field-name" placeholder="Field name" value="' + escHtml(data.name || '') + '">' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<select class="form-select form-select-sm field-type">' + typeOptions + '</select>' +
                    '</div>' +
                    '<div class="col-md-2 text-center">' +
                        '<div class="form-check">' +
                            '<input class="form-check-input field-required" type="checkbox"' + (data.required ? ' checked' : '') + '>' +
                            '<label class="form-check-label small">Req</label>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-3 text-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary move-up-btn" title="Move up"><i class="fas fa-arrow-up"></i></button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary move-down-btn" title="Move down"><i class="fas fa-arrow-down"></i></button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-danger remove-btn" title="Remove"><i class="fas fa-times"></i></button>' +
                    '</div>' +
                '</div>' +
                '<div class="mt-1">' +
                    '<input type="text" class="form-control form-control-sm field-desc" placeholder="Description (optional)" value="' + escHtml(data.description || '') + '">' +
                '</div>' +
            '</div>';

        container.appendChild(row);

        row.querySelector('.remove-btn').addEventListener('click', function() {
            row.remove();
            rebuildFields();
        });

        row.querySelector('.move-up-btn').addEventListener('click', function() {
            var prev = row.previousElementSibling;
            if (prev) { container.insertBefore(row, prev); rebuildFields(); }
        });

        row.querySelector('.move-down-btn').addEventListener('click', function() {
            var next = row.nextElementSibling;
            if (next) { container.insertBefore(next, row); rebuildFields(); }
        });

        updateUI();
    }

    function rebuildFields() {
        fields = [];
        var rows = container.querySelectorAll('.field-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].dataset.idx = i;
            fields.push(readRow(rows[i]));
        }
        updateUI();
    }

    function readRow(row) {
        return {
            name: row.querySelector('.field-name').value.trim(),
            type: row.querySelector('.field-type').value,
            required: row.querySelector('.field-required').checked,
            description: row.querySelector('.field-desc').value.trim()
        };
    }

    function updateUI() {
        noMsg.style.display = container.children.length === 0 ? '' : 'none';
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    // Serialize fields to hidden input on submit
    document.querySelector('form').addEventListener('submit', function() {
        var rows = container.querySelectorAll('.field-row');
        var result = [];
        for (var i = 0; i < rows.length; i++) {
            var f = readRow(rows[i]);
            if (f.name) { result.push(f); }
        }
        jsonInput.value = JSON.stringify(result);
    });

    // Delete button
    var deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Delete this template? This cannot be undone.')) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
});
</script>
