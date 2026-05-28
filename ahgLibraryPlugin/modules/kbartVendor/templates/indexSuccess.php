<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('KBART Management'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Library Reports'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>"><i class="fas fa-book me-2"></i><?php echo __('Library Catalogue'); ?></a></li>
    </ul>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-cloud-download-alt"></i> <?php echo __('KBART Vendor Management'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php // Flash messages ?>
<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i><?php echo __('Add New KBART Vendor'); ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'add']); ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php echo __('Vendor Name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="<?php echo __('e.g. EBSCO KBART'); ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><?php echo __('KBART Feed URL'); ?> <span class="text-danger">*</span></label>
                    <input type="url" name="feed_url" class="form-control" placeholder="https://example.com/kbart/vendor.tsv" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1" checked>
                        <label class="form-check-label" for="active"><?php echo __('Active'); ?></label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> <?php echo __('Add'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($vendors)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No KBART vendors configured. Add one above to get started.'); ?>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-server me-2"></i><?php echo __('Configured Vendors'); ?> (<?php echo count($vendors); ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th><?php echo __('Vendor'); ?></th>
                    <th><?php echo __('Feed URL'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th class="text-center"><?php echo __('Last Fetch'); ?></th>
                    <th class="text-center"><?php echo __('Rows'); ?></th>
                    <th><?php echo __('Last Error'); ?></th>
                    <th class="text-center"><?php echo __('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vendors as $vendor): ?>
                <tr class="<?php echo $vendor->active ? '' : 'table-secondary'; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($vendor->name); ?></strong>
                    </td>
                    <td>
                        <small>
                            <a href="<?php echo htmlspecialchars($vendor->feed_url); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo htmlspecialchars($vendor->feed_url); ?>">
                                <?php echo htmlspecialchars(\Qubit:: truncateUrl($vendor->feed_url, 60)); ?>
                                <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </small>
                    </td>
                    <td>
                        <?php if ($vendor->active): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Active'); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-pause me-1"></i><?php echo __('Inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($vendor->last_fetch_at): ?>
                            <small><?php echo date('Y-m-d H:i', strtotime($vendor->last_fetch_at)); ?></small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($vendor->last_row_count !== null): ?>
                            <span class="badge bg-primary"><?php echo number_format($vendor->last_row_count); ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($vendor->last_error): ?>
                            <span class="text-danger" title="<?php echo htmlspecialchars($vendor->last_error); ?>">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars(\Qubit:: truncate($vendor->last_error, 40)); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-success"><i class="fas fa-check"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <!-- Fetch Now -->
                            <form method="POST" action="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'fetch', 'id' => $vendor->id]); ?>" style="display:inline;">
                                <button type="submit" class="btn btn-success btn-sm" title="<?php echo __('Fetch Now'); ?>" onclick="return confirm('<?php echo __('Fetch KBART feed now?'); ?>');">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </form>

                            <!-- Import Log -->
                            <a href="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'importLog', 'id' => $vendor->id]); ?>" class="btn btn-info btn-sm" title="<?php echo __('View Log'); ?>">
                                <i class="fas fa-history"></i>
                            </a>

                            <!-- Toggle Active -->
                            <form method="POST" action="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'toggle', 'id' => $vendor->id]); ?>" style="display:inline;">
                                <button type="submit" class="btn btn-warning btn-sm" title="<?php echo $vendor->active ? __('Disable') : __('Enable'); ?>">
                                    <i class="fas fa-toggle-<?php echo $vendor->active ? 'on' : 'off'; ?>"></i>
                                </button>
                            </form>

                            <!-- Delete -->
                            <form method="POST" action="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'delete', 'id' => $vendor->id]); ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('Delete vendor'); ?>: <?php echo htmlspecialchars($vendor->name); ?>?');">
                                <button type="submit" class="btn btn-danger btn-sm" title="<?php echo __('Delete'); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php // Edit modal ?>
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Edit Vendor'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'edit']); ?>" id="editVendorForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_vendor_id">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Vendor Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_vendor_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('KBART Feed URL'); ?> <span class="text-danger">*</span></label>
                        <input type="url" name="feed_url" id="edit_vendor_url" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="edit_vendor_active" value="1">
                        <label class="form-check-label" for="edit_vendor_active"><?php echo __('Active'); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Save Changes'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit button click — populate modal
document.querySelectorAll('.edit-vendor-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_vendor_id').value = this.dataset.id;
        document.getElementById('edit_vendor_name').value = this.dataset.name;
        document.getElementById('edit_vendor_url').value = this.dataset.url;
        document.getElementById('edit_vendor_active').checked = this.dataset.active === '1';
        new bootstrap.Modal(document.getElementById('editVendorModal')).show();
    });
});
</script>
<?php end_slot(); ?>