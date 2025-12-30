<?php use_helper('Text'); ?>
<h1 class="h3 mb-4"><?php echo __('Data Subject Access Requests (DSAR)'); ?></h1>
<div class="mb-3">
    <a href="/admin/privacy" class="btn btn-secondary"><?php echo __('Back'); ?></a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDsarModal">
        <i class="fas fa-plus me-1"></i><?php echo __('New DSAR'); ?>
    </button>
</div>

<!-- Stats -->
<div class="row mb-3">
    <div class="col-md-3"><div class="card bg-light"><div class="card-body text-center"><h4><?php echo $stats['total'] ?? 0; ?></h4><small>Total</small></div></div></div>
    <div class="col-md-3"><div class="card bg-warning"><div class="card-body text-center"><h4><?php echo $stats['pending'] ?? 0; ?></h4><small>Pending</small></div></div></div>
    <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body text-center"><h4><?php echo $stats['overdue'] ?? 0; ?></h4><small>Overdue</small></div></div></div>
    <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center"><h4><?php echo $stats['completed'] ?? 0; ?></h4><small>Completed</small></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($requests)): ?>
            <table class="table table-striped">
                <thead><tr><th>Reference</th><th>Type</th><th>Subject</th><th>Received</th><th>Deadline</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): 
                    $isOverdue = ($r->status ?? '') !== 'completed' && strtotime($r->deadline_date ?? '') < time();
                ?>
                    <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                        <td><code><?php echo esc_entities($r->reference ?? ''); ?></code></td>
                        <td><?php echo esc_entities($r->request_type ?? ''); ?></td>
                        <td><?php echo esc_entities($r->data_subject_name ?? ''); ?></td>
                        <td><?php echo esc_entities($r->received_date ?? ''); ?></td>
                        <td>
                            <?php echo esc_entities($r->deadline_date ?? ''); ?>
                            <?php if ($isOverdue): ?><span class="badge bg-danger">OVERDUE</span><?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $statusClass = match($r->status ?? 'pending') {
                                'completed' => 'success',
                                'in_progress' => 'info',
                                'pending' => 'warning',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($r->status ?? 'pending'); ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-success" onclick="updateDsarStatus(<?php echo $r->id; ?>, 'completed')" title="Mark Complete"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-outline-info" onclick="updateDsarStatus(<?php echo $r->id; ?>, 'in_progress')" title="In Progress"><i class="fas fa-spinner"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-4"><?php echo __('No DSAR requests. Click "New DSAR" to log one.'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Add DSAR Modal -->
<div class="modal fade" id="addDsarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="/admin/privacy/dsar">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-clock me-2"></i><?php echo __('Log New DSAR'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        POPIA requires response within <strong>30 days</strong>. Deadline will be calculated automatically.
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Request Type'); ?> *</label>
                            <select name="request_type" class="form-select" required>
                                <option value="access">Access (POPIA S23 / GDPR Art.15)</option>
                                <option value="rectification">Rectification (POPIA S24 / GDPR Art.16)</option>
                                <option value="erasure">Erasure (POPIA S24 / GDPR Art.17)</option>
                                <option value="restriction">Restriction (GDPR Art.18)</option>
                                <option value="portability">Portability (GDPR Art.20)</option>
                                <option value="objection">Objection (POPIA S11(3) / GDPR Art.21)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Date Received'); ?> *</label>
                            <input type="date" name="received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Data Subject Name'); ?> *</label>
                            <input type="text" name="data_subject_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Email'); ?></label>
                            <input type="email" name="data_subject_email" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('ID Verification Type'); ?></label>
                            <select name="data_subject_id_type" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="id_document">ID Document</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Assigned To'); ?></label>
                            <input type="text" name="assigned_to_name" class="form-control" placeholder="Staff member name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Notes'); ?></label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Details of the request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Log Request'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
function updateDsarStatus(id, status) {
    if (confirm('Update status to ' + status + '?')) {
        fetch('/admin/privacy/dsar/update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&status=' + status
        }).then(() => location.reload());
    }
}
</script>
