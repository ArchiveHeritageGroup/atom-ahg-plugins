<?php use_helper('Text'); ?>
<h1 class="h3 mb-4"><?php echo __('Data Breach Register'); ?></h1>
<div class="mb-3">
    <a href="/admin/privacy" class="btn btn-secondary"><?php echo __('Back'); ?></a>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reportBreachModal">
        <i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Report Breach'); ?>
    </button>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($breaches)): ?>
            <table class="table table-striped">
                <thead><tr><th>Reference</th><th>Date</th><th>Type</th><th>Affected</th><th>Severity</th><th>Regulator</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($breaches as $b): ?>
                    <tr>
                        <td><code><?php echo esc_entities($b->reference ?? ''); ?></code></td>
                        <td><?php echo esc_entities(substr($b->incident_date ?? '', 0, 10)); ?></td>
                        <td><?php echo esc_entities($b->breach_type ?? ''); ?></td>
                        <td><?php echo number_format($b->individuals_affected ?? 0); ?></td>
                        <td>
                            <?php 
                            $sevClass = match($b->severity ?? 'medium') {
                                'critical', 'high' => 'danger',
                                'medium' => 'warning',
                                'low' => 'info',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?php echo $sevClass; ?>"><?php echo ucfirst($b->severity ?? ''); ?></span>
                        </td>
                        <td>
                            <?php if ($b->regulator_notified ?? false): ?>
                                <span class="badge bg-info" title="<?php echo esc_entities($b->notification_date ?? ''); ?>">Notified</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo ($b->status ?? '') == 'closed' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($b->status ?? 'open'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (($b->status ?? 'open') !== 'closed'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="closeBreach(<?php echo $b->id; ?>)" title="Close"><i class="fas fa-check"></i></button>
                                <?php if (!($b->regulator_notified ?? false)): ?>
                                    <button class="btn btn-sm btn-outline-info" onclick="notifyRegulator(<?php echo $b->id; ?>)" title="Mark Notified"><i class="fas fa-bell"></i></button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <p class="text-muted"><?php echo __('No breach incidents recorded'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Report Breach Modal -->
<div class="modal fade" id="reportBreachModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="/admin/privacy/breaches">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Report Data Breach'); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        POPIA requires notification to the Information Regulator within <strong>72 hours</strong> if the breach poses a risk to data subjects.
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Incident Date/Time'); ?> *</label>
                            <input type="datetime-local" name="incident_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Discovery Date/Time'); ?> *</label>
                            <input type="datetime-local" name="discovered_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Breach Type'); ?> *</label>
                            <select name="breach_type" class="form-select" required>
                                <option value="unauthorized_access">Unauthorized Access</option>
                                <option value="data_theft">Data Theft</option>
                                <option value="accidental_disclosure">Accidental Disclosure</option>
                                <option value="loss_of_equipment">Loss of Equipment</option>
                                <option value="cyber_attack">Cyber Attack</option>
                                <option value="ransomware">Ransomware</option>
                                <option value="phishing">Phishing</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Severity'); ?> *</label>
                            <select name="severity" class="form-select" required>
                                <option value="low">Low - Minor impact</option>
                                <option value="medium" selected>Medium - Moderate impact</option>
                                <option value="high">High - Significant impact</option>
                                <option value="critical">Critical - Severe impact</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Description'); ?> *</label>
                        <textarea name="description" class="form-control" rows="3" required placeholder="What happened?"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Data Affected'); ?></label>
                            <textarea name="data_affected" class="form-control" rows="2" placeholder="What types of data were compromised?"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Individuals Affected'); ?></label>
                            <input type="number" name="individuals_affected" class="form-control" min="0" placeholder="Estimated number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Root Cause'); ?></label>
                        <textarea name="root_cause" class="form-control" rows="2" placeholder="What caused the breach?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Containment Actions Taken'); ?></label>
                        <textarea name="containment_actions" class="form-control" rows="2" placeholder="What steps were taken to contain the breach?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo __('Report Breach'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function closeBreach(id) {
    if (confirm('Close this breach incident?')) {
        fetch('/admin/privacy/breaches/update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&status=closed'
        }).then(() => location.reload());
    }
}
function notifyRegulator(id) {
    if (confirm('Mark as notified to Information Regulator?')) {
        fetch('/admin/privacy/breaches/update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&regulator_notified=1'
        }).then(() => location.reload());
    }
}
</script>
