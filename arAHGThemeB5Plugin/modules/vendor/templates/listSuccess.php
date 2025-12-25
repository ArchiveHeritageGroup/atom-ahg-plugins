<?php echo get_partial('header', ['title' => 'Vendors']); ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-building me-2"></i>Vendors</h1>
        <div>
            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
            </a>
            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'add']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Vendor
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo url_for(['module' => 'vendor', 'action' => 'list']); ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, code, email..." value="<?php echo esc_entities($filters['search'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($filters['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo ($filters['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="pending_approval" <?php echo ($filters['status'] ?? '') === 'pending_approval' ? 'selected' : ''; ?>>Pending Approval</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="vendor_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($vendorTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($filters['vendor_type'] ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Service</label>
                    <select name="service_type_id" class="form-select">
                        <option value="">All Services</option>
                        <?php foreach ($serviceTypes as $service): ?>
                        <option value="<?php echo $service->id; ?>" <?php echo ($filters['service_type_id'] ?? '') == $service->id ? 'selected' : ''; ?>><?php echo esc_entities($service->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Insurance</label>
                    <select name="has_insurance" class="form-select">
                        <option value="">Any</option>
                        <option value="1" <?php echo ($filters['has_insurance'] ?? '') === '1' ? 'selected' : ''; ?>>Has Valid Insurance</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <span class="badge bg-secondary me-2"><?php echo $vendors->count(); ?></span> Vendors
        </div>
        <div class="card-body p-0">
            <?php if ($vendors->count() > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>City</th>
                            <th>Insurance</th>
                            <th>Transactions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td><code><?php echo esc_entities($vendor->vendor_code); ?></code></td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'view', 'slug' => $vendor->slug]); ?>">
                                    <strong><?php echo esc_entities($vendor->name); ?></strong>
                                </a>
                            </td>
                            <td><?php echo ucfirst($vendor->vendor_type); ?></td>
                            <td>
                                <?php if ($vendor->email): ?>
                                    <a href="mailto:<?php echo $vendor->email; ?>"><?php echo esc_entities($vendor->email); ?></a>
                                <?php elseif ($vendor->phone): ?>
                                    <?php echo esc_entities($vendor->phone); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_entities($vendor->city ?? '-'); ?></td>
                            <td>
                                <?php if ($vendor->has_insurance && $vendor->insurance_expiry_date): ?>
                                    <?php $expired = strtotime($vendor->insurance_expiry_date) < time(); ?>
                                    <span class="badge bg-<?php echo $expired ? 'danger' : 'success'; ?>">
                                        <?php echo $expired ? 'Expired' : 'Valid'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary" title="Total transactions"><?php echo $vendor->transaction_count ?? 0; ?></span>
                                <?php if (($vendor->active_transactions ?? 0) > 0): ?>
                                <span class="badge bg-primary" title="Active transactions"><?php echo $vendor->active_transactions; ?> active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger', 'pending_approval' => 'warning'];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$vendor->status] ?? 'secondary'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $vendor->status)); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'view', 'slug' => $vendor->slug]); ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'edit', 'slug' => $vendor->slug]); ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'addTransaction', 'vendor' => $vendor->slug]); ?>" class="btn btn-outline-success" title="New Transaction">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete" onclick="deleteVendor('<?php echo $vendor->slug; ?>', '<?php echo esc_entities($vendor->name); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-building fa-3x mb-3"></i>
                <p>No vendors found matching your criteria</p>
                <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'add']); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add First Vendor
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteVendorForm" method="post" action="" style="display: none;">
    <input type="hidden" name="_method" value="POST">
</form>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete vendor <strong id="deleteVendorName"></strong>?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone. All associated data will be permanently removed.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Delete Vendor
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteSlug = '';

function deleteVendor(slug, name) {
    deleteSlug = slug;
    document.getElementById('deleteVendorName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const form = document.getElementById('deleteVendorForm');
    form.action = '/index.php/vendor/' + deleteSlug + '/delete';
    form.submit();
});
</script>
