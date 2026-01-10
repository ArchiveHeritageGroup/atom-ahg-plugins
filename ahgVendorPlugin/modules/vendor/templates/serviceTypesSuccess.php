<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for('ahg_vend_index'); ?>">Vendor Management</a></li>
        <li class="breadcrumb-item active">Service Types</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-tags me-2"></i>Service Types</h1>
    <div>
        <a href="<?php echo url_for('ahg_vend_index'); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceTypeModal">
            <i class="fas fa-plus me-1"></i>Add Service Type
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Available Service Types</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($serviceTypes) && count($serviceTypes) > 0): ?>
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="sortable" data-sort="name" style="cursor:pointer">Name <i class="fas fa-sort text-muted"></i></th>
                    <th class="sortable" data-sort="description" style="cursor:pointer">Description <i class="fas fa-sort text-muted"></i></th>
                    <th class="sortable" data-sort="status" style="cursor:pointer">Status <i class="fas fa-sort text-muted"></i></th>
                    <th class="text-end" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serviceTypes as $type): ?>
                <tr>
                    <td><strong><?php echo esc_entities($type->name); ?></strong></td>
                    <td><?php echo esc_entities($type->description ?? '-'); ?></td>
                    <td>
                        <?php if ($type->is_active ?? true): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary edit-type-btn" 
                                data-id="<?php echo $type->id; ?>"
                                data-name="<?php echo esc_entities($type->name); ?>"
                                data-description="<?php echo esc_entities($type->description ?? ''); ?>"
                                data-active="<?php echo ($type->is_active ?? true) ? '1' : '0'; ?>"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-type-btn"
                                data-id="<?php echo $type->id; ?>"
                                data-name="<?php echo esc_entities($type->name); ?>"
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-tags display-4 mb-3 d-block"></i>
            <p>No service types defined yet.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceTypeModal">
                <i class="fas fa-plus me-1"></i>Add First Service Type
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Service Type Modal -->
<div class="modal fade" id="addServiceTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for('ahg_vend_service_types'); ?>">
                <input type="hidden" name="form_action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Service Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Conservation, Digitisation, Storage">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this service type"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="addIsActive" value="1" checked>
                        <label class="form-check-label" for="addIsActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Service Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Type Modal -->
<div class="modal fade" id="editServiceTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for('ahg_vend_service_types'); ?>">
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="id" id="editTypeId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Service Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editTypeName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editTypeDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="editIsActive" value="1">
                        <label class="form-check-label" for="editIsActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteServiceTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for('ahg_vend_service_types'); ?>">
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" id="deleteTypeId">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete Service Type</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the service type "<strong id="deleteTypeName"></strong>"?</p>
                    <p class="text-danger mb-0"><small>This action cannot be undone. Transactions using this type will not be affected.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit button handler
    document.querySelectorAll('.edit-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editTypeId').value = this.dataset.id;
            document.getElementById('editTypeName').value = this.dataset.name;
            document.getElementById('editTypeDescription').value = this.dataset.description;
            document.getElementById('editIsActive').checked = this.dataset.active === '1';
            new bootstrap.Modal(document.getElementById('editServiceTypeModal')).show();
        });
    });
    
    // Delete button handler
    document.querySelectorAll('.delete-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('deleteTypeId').value = this.dataset.id;
            document.getElementById('deleteTypeName').textContent = this.dataset.name;
            new bootstrap.Modal(document.getElementById('deleteServiceTypeModal')).show();
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const table = document.querySelector(".table tbody");
    const headers = document.querySelectorAll("th.sortable");
    let sortDirection = {};
    
    headers.forEach(header => {
        header.addEventListener("click", function() {
            const sortKey = this.dataset.sort;
            const direction = sortDirection[sortKey] === "asc" ? "desc" : "asc";
            sortDirection[sortKey] = direction;
            
            // Update icons
            headers.forEach(h => {
                const icon = h.querySelector("i");
                icon.className = "fas fa-sort text-muted";
            });
            const icon = this.querySelector("i");
            icon.className = direction === "asc" ? "fas fa-sort-up text-primary" : "fas fa-sort-down text-primary";
            
            // Get rows and sort
            const rows = Array.from(table.querySelectorAll("tr"));
            rows.sort((a, b) => {
                let aVal, bVal;
                
                if (sortKey === "name") {
                    aVal = a.cells[0].textContent.trim().toLowerCase();
                    bVal = b.cells[0].textContent.trim().toLowerCase();
                } else if (sortKey === "description") {
                    aVal = a.cells[1].textContent.trim().toLowerCase();
                    bVal = b.cells[1].textContent.trim().toLowerCase();
                } else if (sortKey === "status") {
                    aVal = a.cells[2].textContent.trim().toLowerCase();
                    bVal = b.cells[2].textContent.trim().toLowerCase();
                }
                
                if (direction === "asc") {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });
            
            // Re-append sorted rows
            rows.forEach(row => table.appendChild(row));
        });
    });
});
</script>
<script id="sortable-table-script">
document.addEventListener("DOMContentLoaded", function() {
    const table = document.querySelector(".table tbody");
    if (!table) return;
    
    const headers = document.querySelectorAll("th.sortable");
    let sortDirection = {};
    
    headers.forEach(function(header) {
        header.addEventListener("click", function() {
            const sortKey = this.dataset.sort;
            const direction = sortDirection[sortKey] === "asc" ? "desc" : "asc";
            sortDirection[sortKey] = direction;
            
            // Update icons
            headers.forEach(function(h) {
                const icon = h.querySelector("i");
                if (icon) icon.className = "fas fa-sort text-muted";
            });
            const icon = this.querySelector("i");
            if (icon) icon.className = direction === "asc" ? "fas fa-sort-up text-primary" : "fas fa-sort-down text-primary";
            
            // Get rows and sort
            const rows = Array.from(table.querySelectorAll("tr"));
            rows.sort(function(a, b) {
                var aVal = "", bVal = "";
                
                if (sortKey === "name") {
                    aVal = a.cells[0] ? a.cells[0].textContent.trim().toLowerCase() : "";
                    bVal = b.cells[0] ? b.cells[0].textContent.trim().toLowerCase() : "";
                } else if (sortKey === "description") {
                    aVal = a.cells[1] ? a.cells[1].textContent.trim().toLowerCase() : "";
                    bVal = b.cells[1] ? b.cells[1].textContent.trim().toLowerCase() : "";
                } else if (sortKey === "status") {
                    aVal = a.cells[2] ? a.cells[2].textContent.trim().toLowerCase() : "";
                    bVal = b.cells[2] ? b.cells[2].textContent.trim().toLowerCase() : "";
                }
                
                if (direction === "asc") {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });
            
            // Re-append sorted rows
            rows.forEach(function(row) { table.appendChild(row); });
        });
    });
});
</script>