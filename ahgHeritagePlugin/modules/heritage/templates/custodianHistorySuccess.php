<?php
/**
 * Heritage Custodian Audit History.
 */

decorate_with('layout_2col');

$logs = $historyData['logs'] ?? [];
$total = $historyData['total'] ?? 0;
$filters = $historyData['filters'] ?? [];
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-clock-history me-2"></i>Audit Trail
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'history']); ?>

<div class="mt-4">
    <h6 class="text-muted mb-3">Quick Filters</h6>
    <div class="list-group">
        <a href="?" class="list-group-item list-group-item-action <?php echo !$sf_request->getParameter('action_type') ? 'active' : ''; ?>">All Actions</a>
        <a href="?action_type=create" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('action_type') === 'create' ? 'active' : ''; ?>">Creates</a>
        <a href="?action_type=update" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('action_type') === 'update' ? 'active' : ''; ?>">Updates</a>
        <a href="?action_type=delete" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('action_type') === 'delete' ? 'active' : ''; ?>">Deletes</a>
    </div>
</div>
<?php end_slot(); ?>

<!-- Search/Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="<?php echo esc_specialchars($sf_request->getParameter('search', '')); ?>"
                       placeholder="User, object, or action...">
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?php echo esc_specialchars($sf_request->getParameter('date_from', '')); ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?php echo esc_specialchars($sf_request->getParameter('date_to', '')); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Audit Log</h5>
        <span class="badge bg-secondary"><?php echo number_format($total); ?> entries</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fs-1 mb-3 d-block"></i>
            <p>No audit log entries found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Object</th>
                        <th>Changes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($log->created_at)); ?></small>
                            <br>
                            <small><?php echo date('H:i:s', strtotime($log->created_at)); ?></small>
                        </td>
                        <td>
                            <strong><?php echo esc_specialchars($log->username ?? 'System'); ?></strong>
                            <?php if ($log->ip_address): ?>
                            <br><small class="text-muted"><?php echo esc_specialchars($log->ip_address); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $actionColors = [
                                'create' => 'success', 'update' => 'primary', 'delete' => 'danger',
                                'view' => 'info', 'download' => 'warning', 'approve' => 'success', 'deny' => 'danger'
                            ];
                            $color = $actionColors[$log->action] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($log->action); ?></span>
                        </td>
                        <td>
                            <?php if ($log->object_id): ?>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $log->object_slug ?? $log->object_id]); ?>">
                                <?php echo esc_specialchars(mb_strimwidth($log->object_title ?? "Object #{$log->object_id}", 0, 40, '...')); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->field_name): ?>
                            <small>
                                <strong><?php echo esc_specialchars($log->field_name); ?></strong>:
                                <?php if ($log->old_value): ?>
                                <span class="text-danger text-decoration-line-through"><?php echo esc_specialchars(mb_strimwidth($log->old_value, 0, 20, '...')); ?></span>
                                <?php endif; ?>
                                →
                                <span class="text-success"><?php echo esc_specialchars(mb_strimwidth($log->new_value ?? '', 0, 20, '...')); ?></span>
                            </small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                    data-log-id="<?php echo $log->id; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('detailModal').addEventListener('show.bs.modal', function(event) {
    const logId = event.relatedTarget.dataset.logId;
    const content = document.getElementById('logDetailContent');

    // In a full implementation, fetch details via AJAX
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

    // Placeholder - would load from API
    setTimeout(() => {
        content.innerHTML = `
            <p class="text-muted">Log ID: ${logId}</p>
            <p>Full details would be loaded here via API call.</p>
        `;
    }, 300);
});
</script>
