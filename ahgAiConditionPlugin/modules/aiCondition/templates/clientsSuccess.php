<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('API Clients') ?></h6></div>
        <div class="card-body py-2 small">
            <p class="text-muted"><?php echo __('Manage SaaS API clients for external condition assessment access.') ?></p>
            <button type="button" class="btn btn-success btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="fas fa-plus me-1"></i><?php echo __('Add Client') ?>
            </button>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary w-100">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-key me-2"></i><?php echo __('API Client Management') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Manage SaaS API keys and usage') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if (empty($clients)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No API clients configured.') ?>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th><?php echo __('Name') ?></th>
                <th><?php echo __('Organization') ?></th>
                <th><?php echo __('Tier') ?></th>
                <th class="text-center"><?php echo __('Usage') ?></th>
                <th><?php echo __('API Key') ?></th>
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
</script>
<?php end_slot() ?>
