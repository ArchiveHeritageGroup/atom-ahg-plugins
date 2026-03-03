<?php use_helper('I18N'); ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2><i class="bi bi-input-cursor-text"></i> Custom Fields</h2>
            <p class="text-muted mb-0">
                Define custom metadata fields for any entity type.
                <?php echo $totalDefs; ?> field(s) defined, <?php echo $activeDefs; ?> active.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Import
            </button>
            <a href="<?php echo url_for(['module' => 'customFieldAdmin', 'action' => 'export']); ?>"
               class="btn btn-outline-secondary"><i class="bi bi-download"></i> Export All</a>
            <a href="<?php echo url_for(['module' => 'customFieldAdmin', 'action' => 'edit']); ?>"
               class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Field</a>
        </div>
    </div>

    <?php if (empty($definitionsByEntity)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No custom fields defined yet.
            <a href="<?php echo url_for(['module' => 'customFieldAdmin', 'action' => 'edit']); ?>">Create your first field</a>.
        </div>
    <?php else: ?>
        <?php foreach ($definitionsByEntity as $entityType => $definitions): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-tag"></i>
                        <?php echo htmlspecialchars($entityTypes[$entityType] ?? $entityType); ?>
                        <span class="badge bg-secondary"><?php echo count($definitions); ?></span>
                    </h5>
                    <a href="<?php echo url_for(['module' => 'customFieldAdmin', 'action' => 'export'])
                        . '?entity_type=' . urlencode($entityType); ?>"
                       class="btn btn-sm btn-outline-secondary" title="Export"><i class="bi bi-download"></i></a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30px"></th>
                                <th>Key</th>
                                <th>Label</th>
                                <th>Type</th>
                                <th>Group</th>
                                <th class="text-center">Req</th>
                                <th class="text-center">Search</th>
                                <th class="text-center">Public</th>
                                <th class="text-center">Repeat</th>
                                <th class="text-center">Active</th>
                                <th style="width:100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="cf-sortable" data-entity-type="<?php echo htmlspecialchars($entityType); ?>">
                            <?php foreach ($definitions as $def): ?>
                                <tr data-id="<?php echo $def->id; ?>" class="<?php echo $def->is_active ? '' : 'table-secondary'; ?>">
                                    <td class="cf-drag-handle" style="cursor:grab" title="Drag to reorder">
                                        <i class="bi bi-grip-vertical"></i>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($def->field_key); ?></code></td>
                                    <td><?php echo htmlspecialchars($def->field_label); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($def->field_type); ?></span>
                                        <?php if ($def->field_type === 'dropdown' && $def->dropdown_taxonomy): ?>
                                            <small class="text-muted">(<?php echo htmlspecialchars($def->dropdown_taxonomy); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($def->field_group ?? ''); ?></td>
                                    <td class="text-center"><?php echo $def->is_required ? '<i class="bi bi-check text-success"></i>' : ''; ?></td>
                                    <td class="text-center"><?php echo $def->is_searchable ? '<i class="bi bi-check text-success"></i>' : ''; ?></td>
                                    <td class="text-center"><?php echo $def->is_visible_public ? '<i class="bi bi-check text-success"></i>' : ''; ?></td>
                                    <td class="text-center"><?php echo $def->is_repeatable ? '<i class="bi bi-check text-success"></i>' : ''; ?></td>
                                    <td class="text-center">
                                        <?php if ($def->is_active): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo url_for(['module' => 'customFieldAdmin', 'action' => 'edit'])
                                            . '?id=' . $def->id; ?>"
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger cf-delete-btn"
                                                data-id="<?php echo $def->id; ?>"
                                                data-label="<?php echo htmlspecialchars($def->field_label); ?>"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload"></i> Import Field Definitions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Paste JSON exported from another instance. Existing fields (same key + entity type) will be skipped.</p>
                <textarea id="cf-import-json" class="form-control font-monospace" rows="10"
                          placeholder='[{"field_key":"barcode","field_label":"Barcode","field_type":"text","entity_type":"informationobject",...}]'></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="cf-import-submit">
                    <i class="bi bi-upload"></i> Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash"></i> Delete Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate the field "<strong id="delete-field-label"></strong>"?</p>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="cf-hard-delete" value="1">
                    <label class="form-check-label text-danger" for="cf-hard-delete">
                        Permanently delete (only possible if no values exist)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="cf-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var deleteUrl = <?php echo json_encode(url_for(['module' => 'customFieldAdmin', 'action' => 'delete'])); ?>;
    var reorderUrl = <?php echo json_encode(url_for(['module' => 'customFieldAdmin', 'action' => 'reorder'])); ?>;
    var importUrl = <?php echo json_encode(url_for(['module' => 'customFieldAdmin', 'action' => 'import'])); ?>;

    // Delete handler
    var deleteId = 0;
    document.querySelectorAll('.cf-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            deleteId = this.dataset.id;
            document.getElementById('delete-field-label').textContent = this.dataset.label;
            document.getElementById('cf-hard-delete').checked = false;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    document.getElementById('cf-delete-confirm').addEventListener('click', function() {
        var hardDelete = document.getElementById('cf-hard-delete').checked ? 1 : 0;
        var form = new FormData();
        form.append('id', deleteId);
        form.append('hard_delete', hardDelete);
        fetch(deleteUrl, { method: 'POST', body: form })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) { location.reload(); }
                else { alert(data.error || 'Delete failed.'); }
            });
    });

    // Import handler
    document.getElementById('cf-import-submit').addEventListener('click', function() {
        var json = document.getElementById('cf-import-json').value.trim();
        if (!json) { alert('Paste JSON first.'); return; }
        var form = new FormData();
        form.append('import_json', json);
        fetch(importUrl, { method: 'POST', body: form })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.error || 'Import failed.');
                }
            });
    });

    // Drag-drop reorder (simple via sortable rows)
    if (typeof window.cfInitSortable === 'function') {
        document.querySelectorAll('.cf-sortable').forEach(function(tbody) {
            window.cfInitSortable(tbody, reorderUrl);
        });
    }
});
</script>
