<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']); ?>">Form Templates</a></li>
                    <li class="breadcrumb-item active">Form Builder</li>
                </ol>
            </nav>
            <h1><i class="fas fa-edit me-2"></i>Form Builder: <?php echo htmlspecialchars($template->name); ?></h1>
            <p class="text-muted"><?php echo htmlspecialchars($template->description ?? ''); ?></p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateEdit', 'id' => $template->id]); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Settings
            </a>
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'preview', 'id' => $template->id]); ?>" class="btn btn-outline-info">
                <i class="fas fa-eye me-1"></i> Preview
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Field Palette -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-toolbox me-2"></i>Field Types</h5>
                </div>
                <div class="card-body p-2">
                    <div class="field-palette">
                        <div class="palette-item" draggable="true" data-field-type="text">
                            <i class="fas fa-font me-2"></i> Text
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="textarea">
                            <i class="fas fa-paragraph me-2"></i> Text Area
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="select">
                            <i class="fas fa-list me-2"></i> Dropdown
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="date">
                            <i class="fas fa-calendar me-2"></i> Date
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="date_range">
                            <i class="fas fa-calendar-alt me-2"></i> Date Range
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="autocomplete">
                            <i class="fas fa-search me-2"></i> Autocomplete
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="taxonomy">
                            <i class="fas fa-tags me-2"></i> Taxonomy
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="actor">
                            <i class="fas fa-user me-2"></i> Actor Relation
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="checkbox">
                            <i class="fas fa-check-square me-2"></i> Checkbox
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="file">
                            <i class="fas fa-file me-2"></i> File Upload
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="heading">
                            <i class="fas fa-heading me-2"></i> Section Heading
                        </div>
                        <div class="palette-item" draggable="true" data-field-type="divider">
                            <i class="fas fa-minus me-2"></i> Divider
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>AtoM Fields</h5>
                </div>
                <div class="card-body p-2">
                    <div class="field-palette">
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="title">
                            <i class="fas fa-heading me-2"></i> Title
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="identifier">
                            <i class="fas fa-barcode me-2"></i> Identifier
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="level_of_description">
                            <i class="fas fa-layer-group me-2"></i> Level
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="scope_and_content">
                            <i class="fas fa-align-left me-2"></i> Scope & Content
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="extent_and_medium">
                            <i class="fas fa-ruler me-2"></i> Extent
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="dates">
                            <i class="fas fa-calendar me-2"></i> Dates
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="creators">
                            <i class="fas fa-user-edit me-2"></i> Creators
                        </div>
                        <div class="palette-item atom-field" draggable="true" data-field-type="atom" data-atom-field="subjects">
                            <i class="fas fa-tags me-2"></i> Subjects
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Canvas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-th-list me-2"></i>Form Layout</h5>
                    <span class="badge bg-info"><?php echo count($fields); ?> fields</span>
                </div>
                <div class="card-body">
                    <div id="form-canvas" class="form-canvas" data-template-id="<?php echo $template->id; ?>">
                        <?php if (empty($fields)): ?>
                            <div class="empty-canvas text-center text-muted py-5">
                                <i class="fas fa-arrows-alt fa-3x mb-3"></i>
                                <p>Drag fields here to build your form</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                                <div class="field-item" data-field-id="<?php echo $field->id; ?>" data-sort="<?php echo $field->sort_order; ?>">
                                    <div class="field-handle"><i class="fas fa-grip-vertical"></i></div>
                                    <div class="field-content">
                                        <div class="field-label">
                                            <?php echo htmlspecialchars($field->label); ?>
                                            <?php if ($field->is_required): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="field-meta">
                                            <span class="badge bg-secondary"><?php echo $field->field_type; ?></span>
                                            <?php if ($field->atom_field): ?>
                                                <span class="badge bg-info"><?php echo $field->atom_field; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="field-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-field" data-field-id="<?php echo $field->id; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-field" data-field-id="<?php echo $field->id; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field Properties -->
        <div class="col-md-3">
            <div class="card" id="field-properties-panel">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Field Properties</h5>
                </div>
                <div class="card-body">
                    <div id="no-field-selected" class="text-center text-muted py-4">
                        <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                        <p>Select a field to edit its properties</p>
                    </div>
                    <form id="field-properties-form" style="display: none;">
                        <input type="hidden" id="prop-field-id" name="field_id">

                        <div class="mb-3">
                            <label for="prop-label" class="form-label">Label</label>
                            <input type="text" class="form-control" id="prop-label" name="label">
                        </div>

                        <div class="mb-3">
                            <label for="prop-name" class="form-label">Field Name</label>
                            <input type="text" class="form-control" id="prop-name" name="field_name">
                            <div class="form-text">Internal field identifier</div>
                        </div>

                        <div class="mb-3">
                            <label for="prop-help" class="form-label">Help Text</label>
                            <textarea class="form-control" id="prop-help" name="help_text" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="prop-placeholder" class="form-label">Placeholder</label>
                            <input type="text" class="form-control" id="prop-placeholder" name="placeholder">
                        </div>

                        <div class="mb-3">
                            <label for="prop-default" class="form-label">Default Value</label>
                            <input type="text" class="form-control" id="prop-default" name="default_value">
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="prop-required" name="is_required">
                            <label class="form-check-label" for="prop-required">Required</label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="prop-readonly" name="is_readonly">
                            <label class="form-check-label" for="prop-readonly">Read Only</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Save Field
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.field-palette {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.palette-item {
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: grab;
    transition: all 0.2s;
}

.palette-item:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.palette-item.atom-field {
    background: #e7f1ff;
    border-color: #b6d4fe;
}

.form-canvas {
    min-height: 400px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 16px;
}

.form-canvas.drag-over {
    border-color: #0d6efd;
    background-color: #f0f7ff;
}

.field-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    margin-bottom: 8px;
    transition: all 0.2s;
}

.field-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.field-item.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

.field-item.dragging {
    opacity: 0.5;
}

.field-handle {
    cursor: grab;
    padding: 0 12px 0 4px;
    color: #adb5bd;
}

.field-content {
    flex: 1;
}

.field-label {
    font-weight: 500;
}

.field-meta {
    margin-top: 4px;
}

.field-actions {
    display: flex;
    gap: 4px;
}

.empty-canvas {
    color: #adb5bd;
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var canvas = document.getElementById('form-canvas');
    var templateId = canvas.dataset.templateId;
    var selectedField = null;

    // Drag and drop from palette
    document.querySelectorAll('.palette-item').forEach(function(item) {
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                type: 'new',
                fieldType: this.dataset.fieldType,
                atomField: this.dataset.atomField || null
            }));
        });
    });

    canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });

    canvas.addEventListener('dragleave', function(e) {
        this.classList.remove('drag-over');
    });

    canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        var data = JSON.parse(e.dataTransfer.getData('text/plain'));
        if (data.type === 'new') {
            addNewField(data.fieldType, data.atomField);
        }
    });

    function addNewField(fieldType, atomField) {
        var label = atomField ? atomField.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); }) : 'New ' + fieldType + ' field';

        fetch('<?php echo url_for(['module' => 'forms', 'action' => 'fieldAdd']); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'template_id=' + templateId + '&field_type=' + fieldType + '&label=' + encodeURIComponent(label) + '&atom_field=' + (atomField || '')
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert('Error adding field: ' + data.error);
            }
        });
    }

    // Field selection
    document.querySelectorAll('.field-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.field-actions')) return;

            document.querySelectorAll('.field-item').forEach(function(f) {
                f.classList.remove('selected');
            });
            this.classList.add('selected');
            selectedField = this.dataset.fieldId;
            loadFieldProperties(selectedField);
        });
    });

    // Edit field button
    document.querySelectorAll('.btn-edit-field').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var fieldId = this.dataset.fieldId;
            loadFieldProperties(fieldId);
            document.querySelector('[data-field-id="' + fieldId + '"]').classList.add('selected');
        });
    });

    // Delete field button
    document.querySelectorAll('.btn-delete-field').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this field?')) return;

            var fieldId = this.dataset.fieldId;
            fetch('<?php echo url_for(['module' => 'forms', 'action' => 'fieldDelete']); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'field_id=' + fieldId
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    function loadFieldProperties(fieldId) {
        fetch('<?php echo url_for(['module' => 'forms', 'action' => 'fieldGet']); ?>?field_id=' + fieldId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(field) {
            document.getElementById('no-field-selected').style.display = 'none';
            document.getElementById('field-properties-form').style.display = 'block';

            document.getElementById('prop-field-id').value = field.id;
            document.getElementById('prop-label').value = field.label || '';
            document.getElementById('prop-name').value = field.field_name || '';
            document.getElementById('prop-help').value = field.help_text || '';
            document.getElementById('prop-placeholder').value = field.placeholder || '';
            document.getElementById('prop-default').value = field.default_value || '';
            document.getElementById('prop-required').checked = field.is_required == 1;
            document.getElementById('prop-readonly').checked = field.is_readonly == 1;
        });
    }

    // Save field properties
    document.getElementById('field-properties-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var params = new URLSearchParams(formData).toString();

        fetch('<?php echo url_for(['module' => 'forms', 'action' => 'fieldUpdate']); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert('Error saving field');
            }
        });
    });

    // Make fields sortable (using native drag)
    var sortableItems = document.querySelectorAll('.field-item');
    sortableItems.forEach(function(item) {
        item.setAttribute('draggable', 'true');

        item.addEventListener('dragstart', function(e) {
            this.classList.add('dragging');
            e.dataTransfer.setData('text/plain', JSON.stringify({
                type: 'reorder',
                fieldId: this.dataset.fieldId
            }));
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            var dragging = document.querySelector('.dragging');
            if (dragging && dragging !== this) {
                var rect = this.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;
                if (e.clientY < midY) {
                    this.parentNode.insertBefore(dragging, this);
                } else {
                    this.parentNode.insertBefore(dragging, this.nextSibling);
                }
            }
        });

        item.addEventListener('drop', function(e) {
            e.preventDefault();
            saveFieldOrder();
        });
    });

    function saveFieldOrder() {
        var order = [];
        document.querySelectorAll('.field-item').forEach(function(item, index) {
            order.push({ id: item.dataset.fieldId, sort: index });
        });

        fetch('<?php echo url_for(['module' => 'forms', 'action' => 'fieldReorder']); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order: order })
        });
    }
});
</script>
