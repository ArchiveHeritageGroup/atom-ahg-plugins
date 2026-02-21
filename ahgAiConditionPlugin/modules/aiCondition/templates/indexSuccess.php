<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="fas fa-robot me-1"></i><?php echo __('AI Condition') ?></h6>
        </div>
        <div class="card-body py-2">
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>" class="btn btn-success btn-sm w-100 mb-2">
                <i class="fas fa-camera me-1"></i><?php echo __('New Assessment') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'bulk']) ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="fas fa-layer-group me-1"></i><?php echo __('Bulk Scan') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'training']) ?>" class="btn btn-outline-info btn-sm w-100 mb-2">
                <i class="fas fa-brain me-1"></i><?php echo __('Model Training') ?>
            </a>
        </div>
    </div>
    <!-- Stats -->
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Statistics') ?></h6></div>
        <div class="card-body py-2 small">
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo __('Total Assessments') ?></span>
                <strong><?php echo $stats['total'] ?? 0 ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo __('Confirmed') ?></span>
                <strong class="text-success"><?php echo $stats['confirmed'] ?? 0 ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo __('Pending Review') ?></span>
                <strong class="text-warning"><?php echo $stats['pending'] ?? 0 ?></strong>
            </div>
            <div class="d-flex justify-content-between">
                <span><?php echo __('Avg Score') ?></span>
                <strong><?php echo $stats['avg_score'] ?? '--' ?></strong>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-robot me-2"></i><?php echo __('AI Condition Assessment') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Settings and API client management') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<!-- Settings -->
<form method="post" action="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>">
    <input type="hidden" name="form_action" value="save_settings">

    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-plug me-2"></i><?php echo __('Service Connection') ?></h6></div>
        <div class="card-body">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Service URL') ?></label>
                <div class="col-sm-7">
                    <input type="url" class="form-control form-control-sm" name="ai_condition_service_url" value="<?php echo esc_entities($settings['ai_condition_service_url']) ?>">
                </div>
                <div class="col-sm-2">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="testBtn">
                        <i class="fas fa-plug me-1"></i><?php echo __('Test') ?>
                    </button>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('API Key') ?></label>
                <div class="col-sm-9">
                    <input type="text" class="form-control form-control-sm" name="ai_condition_api_key" value="<?php echo esc_entities($settings['ai_condition_api_key']) ?>">
                </div>
            </div>
            <div id="testResult" style="display:none"></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i><?php echo __('Assessment Defaults') ?></h6></div>
        <div class="card-body">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Min Confidence') ?></label>
                <div class="col-sm-9">
                    <input type="number" class="form-control form-control-sm" name="ai_condition_min_confidence" value="<?php echo esc_entities($settings['ai_condition_min_confidence']) ?>" min="0.1" max="0.9" step="0.05">
                    <div class="form-text"><?php echo __('Minimum confidence threshold for damage detection (0.1 - 0.9)') ?></div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Overlay Enabled') ?></label>
                <div class="col-sm-9">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ai_condition_overlay_enabled" value="1" <?php echo $settings['ai_condition_overlay_enabled'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small"><?php echo __('Generate annotated overlay images with bounding boxes') ?></label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Auto-Scan on Upload') ?></label>
                <div class="col-sm-9">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ai_condition_auto_scan" value="1" <?php echo $settings['ai_condition_auto_scan'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small"><?php echo __('Automatically scan digital objects when uploaded') ?></label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Alert Grade') ?></label>
                <div class="col-sm-9">
                    <select class="form-select form-select-sm" name="ai_condition_notify_grade">
                        <?php foreach (['excellent','good','fair','poor','critical'] as $g): ?>
                        <option value="<?php echo $g ?>" <?php echo $settings['ai_condition_notify_grade'] === $g ? 'selected' : '' ?>><?php echo ucfirst($g) ?></option>
                        <?php endforeach ?>
                    </select>
                    <div class="form-text"><?php echo __('Notify when condition grade is at or below this level') ?></div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mb-4">
        <i class="fas fa-save me-1"></i><?php echo __('Save Settings') ?>
    </button>
</form>

<!-- API Clients -->
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-key me-2"></i><?php echo __('API Clients') ?></h6>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addClientModal">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Client') ?>
        </button>
    </div>
    <div class="card-body p-0">
        <?php if (empty($clients)): ?>
        <div class="p-3 text-center text-muted small">
            <i class="fas fa-info-circle me-1"></i><?php echo __('No API clients configured.') ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Name') ?></th>
                        <th><?php echo __('Organization') ?></th>
                        <th><?php echo __('Tier') ?></th>
                        <th class="text-center"><?php echo __('Usage') ?></th>
                        <th><?php echo __('API Key') ?></th>
                        <th class="text-center"><?php echo __('Training') ?></th>
                        <th class="text-center"><?php echo __('Status') ?></th>
                        <th class="text-end"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?php echo esc_entities($c->name) ?></td>
                        <td class="small"><?php echo esc_entities($c->organization ?? '') ?></td>
                        <td><span class="badge bg-info"><?php echo ucfirst($c->tier) ?></span></td>
                        <td class="text-center">
                            <span class="small"><?php echo $c->scans_used ?? 0 ?> / <?php echo number_format($c->monthly_limit) ?></span>
                        </td>
                        <td>
                            <code class="small user-select-all"><?php echo esc_entities(substr($c->api_key, 0, 12)) ?>...</code>
                        </td>
                        <td class="text-center">
                            <?php if ($c->is_active): ?>
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" <?php echo !empty($c->can_contribute_training) ? 'checked' : '' ?>
                                    onchange="toggleTraining(<?php echo $c->id ?>, this.checked ? 1 : 0)"
                                    title="<?php echo __('Allow client to contribute training data') ?>">
                            </div>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif ?>
                        </td>
                        <td class="text-center">
                            <?php if ($c->is_active): ?>
                            <span class="badge bg-success"><?php echo __('Active') ?></span>
                            <?php else: ?>
                            <span class="badge bg-danger"><?php echo __('Revoked') ?></span>
                            <?php endif ?>
                        </td>
                        <td class="text-end">
                            <?php if ($c->is_active): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="revokeClient(<?php echo $c->id ?>)">
                                <i class="fas fa-ban"></i>
                            </button>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Training Data Approval -->
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i><?php echo __('Client Training Data Approval') ?></h6>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            <?php echo __('Review and approve client data for use as model training data. Client consent documentation must be uploaded before approval.') ?>
        </p>

        <?php
        $approvalClients = [];
        foreach ($clients as $c) {
            if ($c->is_active && !empty($c->can_contribute_training)) {
                $approvalClients[] = $c;
            }
        }
        ?>

        <?php if (empty($approvalClients)): ?>
        <div class="text-center text-muted small py-3">
            <i class="fas fa-info-circle me-1"></i><?php echo __('No clients have training contributions enabled. Toggle the Training switch in the API Clients table above.') ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Client') ?></th>
                        <th class="text-center"><?php echo __('Contributions') ?></th>
                        <th class="text-center"><?php echo __('Pending') ?></th>
                        <th class="text-center"><?php echo __('Approved') ?></th>
                        <th><?php echo __('Consent Document') ?></th>
                        <th class="text-center"><?php echo __('Training Status') ?></th>
                        <th class="text-end"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvalClients as $ac):
                        $cStats = $trainingContributions[$ac->id] ?? null;
                        $totalContrib = $cStats ? $cStats->total : 0;
                        $pendingContrib = $cStats ? $cStats->pending : 0;
                        $approvedContrib = $cStats ? $cStats->approved : 0;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_entities($ac->name) ?></strong>
                            <br><small class="text-muted"><?php echo esc_entities($ac->organization ?? '') ?></small>
                        </td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo $totalContrib ?></span></td>
                        <td class="text-center">
                            <?php if ($pendingContrib > 0): ?>
                            <span class="badge bg-warning text-dark"><?php echo $pendingContrib ?></span>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif ?>
                        </td>
                        <td class="text-center">
                            <?php if ($approvedContrib > 0): ?>
                            <span class="badge bg-success"><?php echo $approvedContrib ?></span>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if (!empty($ac->training_approval_doc)): ?>
                                <a href="/<?php echo esc_entities($ac->training_approval_doc) ?>" target="_blank" class="small text-success">
                                    <i class="fas fa-file-alt me-1"></i><?php echo __('View Document') ?>
                                </a>
                            <?php else: ?>
                                <span class="small text-warning"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Not uploaded') ?></span>
                            <?php endif ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" onclick="uploadConsent(<?php echo $ac->id ?>, '<?php echo esc_entities($ac->name) ?>')">
                                <i class="fas fa-upload"></i>
                            </button>
                        </td>
                        <td class="text-center">
                            <?php if ($ac->training_approved): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Approved') ?></span>
                                <?php if ($ac->training_approved_at): ?>
                                <br><small class="text-muted"><?php echo date('d M Y', strtotime($ac->training_approved_at)) ?></small>
                                <?php endif ?>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i><?php echo __('Pending') ?></span>
                            <?php endif ?>
                        </td>
                        <td class="text-end">
                            <?php if (!$ac->training_approved): ?>
                                <button type="button" class="btn btn-sm btn-success" onclick="approveTraining(<?php echo $ac->id ?>, '<?php echo esc_entities($ac->name) ?>')"
                                    <?php echo empty($ac->training_approval_doc) ? 'disabled title="' . __('Upload consent document first') . '"' : '' ?>>
                                    <i class="fas fa-check me-1"></i><?php echo __('Approve') ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-info me-1" onclick="pushTrainingData(<?php echo $ac->id ?>)" <?php echo $approvedContrib < 1 ? 'disabled' : '' ?>>
                                    <i class="fas fa-paper-plane me-1"></i><?php echo __('Push to Training') ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="revokeTrainingApproval(<?php echo $ac->id ?>)">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Upload Consent Document Modal -->
<div class="modal fade" id="uploadConsentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-upload me-2"></i><?php echo __('Upload Consent Document') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted"><?php echo __('Upload a signed consent/approval document from the client authorizing use of their data for model training.') ?></p>
                <p class="small"><strong><?php echo __('Client:') ?></strong> <span id="consentClientName"></span></p>
                <input type="hidden" id="consentClientId">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Document') ?> <span class="text-danger">*</span></label>
                    <input type="file" class="form-control form-control-sm" id="consentFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="form-text"><?php echo __('Accepted formats: PDF, DOC, DOCX, JPG, PNG') ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="submitConsent()">
                    <i class="fas fa-upload me-1"></i><?php echo __('Upload') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('Add API Client') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="clientName">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Organization') ?></label>
                    <input type="text" class="form-control form-control-sm" id="clientOrg">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Email') ?> <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-sm" id="clientEmail">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Tier') ?></label>
                    <select class="form-select form-select-sm" id="clientTier">
                        <option value="free">Free (50/month)</option>
                        <option value="standard">Standard (500/month)</option>
                        <option value="pro">Professional (5000/month)</option>
                        <option value="enterprise">Enterprise (Unlimited)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Monthly Limit') ?></label>
                    <input type="number" class="form-control form-control-sm" id="clientLimit" value="50">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-success" onclick="saveClient()"><?php echo __('Create') ?></button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('testBtn').addEventListener('click', function() {
    var el = document.getElementById('testResult');
    el.style.display = '';
    el.innerHTML = '<div class="alert alert-info py-1 small"><i class="fas fa-spinner fa-spin me-1"></i>Testing...</div>';

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTest']) ?>')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var d = data.data || {};
            var models = d.models || {};
            var detector = models.detector || {};
            el.innerHTML = '<div class="alert alert-success py-1 small"><i class="fas fa-check me-1"></i>Connected! Version: ' + (d.version || 'unknown') + ', Detector: ' + (detector.mode || 'unknown') + ', GPU: ' + (d.gpu && d.gpu.available ? 'Yes' : 'No') + '</div>';
        } else {
            el.innerHTML = '<div class="alert alert-danger py-1 small"><i class="fas fa-times me-1"></i>' + (data.error || 'Connection failed') + '</div>';
        }
    })
    .catch(function() {
        el.innerHTML = '<div class="alert alert-danger py-1 small"><i class="fas fa-times me-1"></i>Network error</div>';
    });
});

function saveClient() {
    var data = 'name=' + encodeURIComponent(document.getElementById('clientName').value)
        + '&organization=' + encodeURIComponent(document.getElementById('clientOrg').value)
        + '&email=' + encodeURIComponent(document.getElementById('clientEmail').value)
        + '&tier=' + document.getElementById('clientTier').value
        + '&monthly_limit=' + document.getElementById('clientLimit').value;

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiClientSave']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) location.reload();
        else alert(d.error || 'Error');
    });
}

function revokeClient(id) {
    if (!confirm('Revoke this API key? The client will lose access.')) return;
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiClientRevoke']) ?>?id=' + id, {method:'POST'})
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.success) location.reload(); });
}

function toggleTraining(id, enabled) {
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiClientTrainingToggle']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&enabled=' + enabled
    }).then(function(r) { return r.json(); }).then(function() {
        location.reload();
    });
}

function uploadConsent(clientId, clientName) {
    document.getElementById('consentClientId').value = clientId;
    document.getElementById('consentClientName').textContent = clientName;
    document.getElementById('consentFile').value = '';
    var modal = new bootstrap.Modal(document.getElementById('uploadConsentModal'));
    modal.show();
}

function submitConsent() {
    var clientId = document.getElementById('consentClientId').value;
    var fileInput = document.getElementById('consentFile');
    if (!fileInput.files.length) {
        alert('Please select a file.');
        return;
    }
    var formData = new FormData();
    formData.append('id', clientId);
    formData.append('consent_doc', fileInput.files[0]);

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiClientUploadConsent']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('uploadConsentModal')).hide();
            location.reload();
        } else {
            alert(d.error || 'Upload failed');
        }
    });
}

function approveTraining(clientId, clientName) {
    if (!confirm('Approve training data usage for client "' + clientName + '"?\n\nThis will allow their contributed assessment data to be used for model training.')) return;

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiClientApproveTraining']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + clientId + '&approve_action=approve'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) location.reload();
        else alert(d.error || 'Approval failed');
    });
}

function revokeTrainingApproval(clientId) {
    if (!confirm('Revoke training approval for this client? Pending contributions will remain but no new data will be used.')) return;

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiClientApproveTraining']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + clientId + '&approve_action=revoke'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.success) location.reload(); });
}

function pushTrainingData(clientId) {
    if (!confirm('Push approved contributions to the training pipeline? This will build a dataset from the client\'s approved data.')) return;

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiPushTrainingData']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'client_id=' + clientId
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            alert('Training data pushed successfully!');
            location.reload();
        } else {
            alert(d.error || 'Push failed');
        }
    });
}
</script>
<?php end_slot() ?>
