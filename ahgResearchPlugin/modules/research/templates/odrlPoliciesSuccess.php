<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">ODRL Policies</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">ODRL Policies</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPolicyModal"><i class="fas fa-plus me-1"></i>Create Policy</button>
</div>

<!-- Filter bar -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="research"><input type="hidden" name="action" value="odrlPolicies">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Target Type</label>
                <select name="filter_target_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['archival_description', 'collection', 'project', 'snapshot', 'annotation', 'assertion'] as $tt): ?>
                    <option value="<?php echo $tt; ?>" <?php echo (($filters['target_type'] ?? '') === $tt) ? 'selected' : ''; ?>><?php echo ucfirst($tt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Policy Type</label>
                <select name="filter_policy_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="permission" <?php echo (($filters['policy_type'] ?? '') === 'permission') ? 'selected' : ''; ?>>Permission</option>
                    <option value="prohibition" <?php echo (($filters['policy_type'] ?? '') === 'prohibition') ? 'selected' : ''; ?>>Prohibition</option>
                    <option value="obligation" <?php echo (($filters['policy_type'] ?? '') === 'obligation') ? 'selected' : ''; ?>>Obligation</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Action Type</label>
                <select name="filter_action_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['use', 'reproduce', 'distribute', 'modify', 'archive', 'display'] as $at): ?>
                    <option value="<?php echo $at; ?>" <?php echo (($filters['action_type'] ?? '') === $at) ? 'selected' : ''; ?>><?php echo ucfirst($at); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<?php if (empty($policies['items'] ?? [])): ?>
    <div class="alert alert-info">No ODRL policies found. Create one to get started.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Target</th>
                <th>Policy Type</th>
                <th>Action</th>
                <th>Constraints</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($policies['items'] as $p): ?>
            <tr>
                <td><?php echo (int) $p->id; ?></td>
                <td>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($p->target_type); ?></span>
                    #<?php echo (int) $p->target_id; ?>
                </td>
                <td>
                    <?php
                    $badgeClass = 'bg-info';
                    if ($p->policy_type === 'permission') { $badgeClass = 'bg-success'; }
                    elseif ($p->policy_type === 'prohibition') { $badgeClass = 'bg-danger'; }
                    elseif ($p->policy_type === 'obligation') { $badgeClass = 'bg-warning text-dark'; }
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($p->policy_type); ?></span>
                </td>
                <td><code><?php echo htmlspecialchars($p->action_type); ?></code></td>
                <td>
                    <?php if (!empty($p->constraints_json)):
                        $constraints = json_decode($p->constraints_json, true);
                        if (is_array($constraints)):
                            foreach ($constraints as $ck => $cv): ?>
                                <small class="d-block text-muted"><?php echo htmlspecialchars($ck); ?>: <?php echo htmlspecialchars(is_array($cv) ? implode(', ', $cv) : $cv); ?></small>
                            <?php endforeach;
                        else: ?>
                            <small class="text-muted">-</small>
                        <?php endif;
                    else: ?>
                        <small class="text-muted">None</small>
                    <?php endif; ?>
                </td>
                <td><small><?php echo htmlspecialchars($p->created_at ?? ''); ?></small></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary edit-policy-btn"
                            data-policy-id="<?php echo (int) $p->id; ?>"
                            data-target-type="<?php echo htmlspecialchars($p->target_type, ENT_QUOTES); ?>"
                            data-target-id="<?php echo (int) $p->target_id; ?>"
                            data-policy-type="<?php echo htmlspecialchars($p->policy_type, ENT_QUOTES); ?>"
                            data-action-type="<?php echo htmlspecialchars($p->action_type, ENT_QUOTES); ?>"
                            data-constraints="<?php echo htmlspecialchars($p->constraints_json ?? '{}', ENT_QUOTES); ?>"
                            title="Edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn btn-outline-danger delete-policy-btn" data-policy-id="<?php echo (int) $p->id; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?php echo ($i === $currentPage) ? 'active' : ''; ?>">
            <a class="page-link" href="?module=research&action=odrlPolicies&page=<?php echo $i; ?><?php
                if (!empty($filters['target_type'])) { echo '&filter_target_type=' . urlencode($filters['target_type']); }
                if (!empty($filters['policy_type'])) { echo '&filter_policy_type=' . urlencode($filters['policy_type']); }
                if (!empty($filters['action_type'])) { echo '&filter_action_type=' . urlencode($filters['action_type']); }
            ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<!-- Create Policy Modal -->
<div class="modal fade" id="createPolicyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create ODRL Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Target Type <span class="text-danger">*</span></label>
            <select id="policyTargetType" class="form-select" required>
              <option value="">Select...</option>
              <option value="archival_description">Archival Description</option>
              <option value="collection">Collection</option>
              <option value="project">Project</option>
              <option value="snapshot">Snapshot</option>
              <option value="annotation">Annotation</option>
              <option value="assertion">Assertion</option>
            </select>
          </div>
          <div class="col-md-6 mb-3" id="targetSearchGroup" style="display:none;">
            <label class="form-label fw-semibold" id="targetSearchLabel">Target <span class="text-danger">*</span></label>
            <select id="policyTargetSearch"></select>
            <input type="hidden" id="policyTargetId" value="">
            <small class="text-muted">Type at least 2 characters to search</small>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Policy Type</label>
            <select id="policyType" class="form-select">
              <option value="permission">Permission</option>
              <option value="prohibition">Prohibition</option>
              <option value="obligation">Obligation</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Action Type</label>
            <select id="policyActionType" class="form-select">
              <option value="use">Use</option>
              <option value="reproduce">Reproduce</option>
              <option value="distribute">Distribute</option>
              <option value="modify">Modify</option>
              <option value="archive">Archive</option>
              <option value="display">Display</option>
            </select>
          </div>
        </div>
        <hr class="my-3">
        <h6 class="text-muted mb-3">Constraints <small>(optional)</small></h6>
        <div class="mb-3">
          <label class="form-label">Restrict to Researchers</label>
          <select id="policyResearchers" multiple placeholder="Search researchers..."></select>
          <small class="text-muted">Leave empty to apply to all researchers</small>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Date From</label>
            <input type="date" id="policyDateFrom" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Date To</label>
            <input type="date" id="policyDateTo" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Max Uses</label>
            <input type="number" id="policyMaxUses" class="form-control" min="0" placeholder="Unlimited">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitPolicyBtn"><i class="fas fa-check me-1"></i>Create Policy</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Policy Modal -->
<div class="modal fade" id="editPolicyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit ODRL Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editPolicyId">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Target Type <span class="text-danger">*</span></label>
            <select id="editTargetType" class="form-select" required>
              <option value="">Select...</option>
              <option value="archival_description">Archival Description</option>
              <option value="collection">Collection</option>
              <option value="project">Project</option>
              <option value="snapshot">Snapshot</option>
              <option value="annotation">Annotation</option>
              <option value="assertion">Assertion</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold" id="editTargetSearchLabel">Target <span class="text-danger">*</span></label>
            <select id="editTargetSearch"></select>
            <input type="hidden" id="editTargetId" value="">
            <small class="text-muted">Type at least 2 characters to search</small>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Policy Type</label>
            <select id="editPolicyType" class="form-select">
              <option value="permission">Permission</option>
              <option value="prohibition">Prohibition</option>
              <option value="obligation">Obligation</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Action Type</label>
            <select id="editActionType" class="form-select">
              <option value="use">Use</option>
              <option value="reproduce">Reproduce</option>
              <option value="distribute">Distribute</option>
              <option value="modify">Modify</option>
              <option value="archive">Archive</option>
              <option value="display">Display</option>
            </select>
          </div>
        </div>
        <hr class="my-3">
        <h6 class="text-muted mb-3">Constraints <small>(optional)</small></h6>
        <div class="mb-3">
          <label class="form-label">Restrict to Researchers</label>
          <select id="editResearchers" multiple placeholder="Search researchers..."></select>
          <small class="text-muted">Leave empty to apply to all researchers</small>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Date From</label>
            <input type="date" id="editDateFrom" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Date To</label>
            <input type="date" id="editDateTo" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Max Uses</label>
            <input type="number" id="editMaxUses" class="form-control" min="0" placeholder="Unlimited">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="updatePolicyBtn"><i class="fas fa-check me-1"></i>Save Changes</button>
      </div>
    </div>
  </div>
</div>

<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<style <?php echo $na; ?>>
.modal .ts-dropdown { z-index: 1060 !important; }
</style>
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" <?php echo $na; ?>></script>
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var searchUrl = '/research/ajax/search-entities';
    var modalBody = document.querySelector('#createPolicyModal .modal-body');
    var targetLabels = {
        archival_description: 'Archival Description',
        collection: 'Collection',
        project: 'Project',
        snapshot: 'Snapshot',
        annotation: 'Annotation',
        assertion: 'Assertion'
    };

    // Target search TomSelect
    var tsTarget = new TomSelect('#policyTargetSearch', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title'],
        maxItems: 1,
        dropdownParent: modalBody,
        placeholder: 'Select target type first...',
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            var targetType = document.getElementById('policyTargetType').value;
            if (!targetType) { callback(); return; }
            fetch(searchUrl + '?q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(targetType))
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    callback((j.items || []).map(function(i) {
                        return { id: String(i.id), title: i.title || ('ID: ' + i.id) };
                    }));
                })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item) { return '<div class="py-1"><strong>' + item.title + '</strong></div>'; },
            item: function(item) { return '<div>' + item.title + '</div>'; },
            no_results: function() { return '<div class="no-results p-2 text-muted">No results found</div>'; }
        },
        onChange: function(value) {
            document.getElementById('policyTargetId').value = value || '';
        }
    });

    // Researcher multi-select TomSelect
    var tsResearchers = new TomSelect('#policyResearchers', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title'],
        dropdownParent: modalBody,
        placeholder: 'Search researchers...',
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            fetch(searchUrl + '?q=' + encodeURIComponent(query) + '&type=researcher')
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    callback((j.items || []).map(function(i) {
                        return { id: String(i.id), title: i.title || i.email || ('ID: ' + i.id) };
                    }));
                })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item) { return '<div class="py-1">' + item.title + '</div>'; },
            item: function(item) { return '<div>' + item.title + '</div>'; },
            no_results: function() { return '<div class="no-results p-2 text-muted">No researchers found</div>'; }
        }
    });

    // Show/hide target search when target type changes
    document.getElementById('policyTargetType').addEventListener('change', function() {
        var type = this.value;
        var group = document.getElementById('targetSearchGroup');
        var label = document.getElementById('targetSearchLabel');
        tsTarget.clear(); tsTarget.clearOptions();
        document.getElementById('policyTargetId').value = '';
        if (type) {
            group.style.display = '';
            label.innerHTML = (targetLabels[type] || 'Target') + ' <span class="text-danger">*</span>';
            tsTarget.settings.placeholder = 'Search ' + (targetLabels[type] || 'target') + '...';
            tsTarget.inputState();
        } else {
            group.style.display = 'none';
        }
    });

    // Create policy
    document.getElementById('submitPolicyBtn').addEventListener('click', function() {
        var targetType = document.getElementById('policyTargetType').value;
        var targetId = document.getElementById('policyTargetId').value;
        var policyType = document.getElementById('policyType').value;
        var actionType = document.getElementById('policyActionType').value;

        if (!targetType || !targetId) {
            alert('Target Type and Target are required.');
            return;
        }

        var body = {
            target_type: targetType,
            target_id: parseInt(targetId),
            policy_type: policyType,
            action_type: actionType
        };

        // Build constraints from form fields
        var constraints = {};
        var researcherIds = tsResearchers.getValue();
        if (researcherIds && researcherIds.length > 0) {
            constraints.researcher_ids = researcherIds.map(function(id) { return parseInt(id); });
        }
        var dateFrom = document.getElementById('policyDateFrom').value;
        if (dateFrom) { constraints.date_from = dateFrom; }
        var dateTo = document.getElementById('policyDateTo').value;
        if (dateTo) { constraints.date_to = dateTo; }
        var maxUses = document.getElementById('policyMaxUses').value;
        if (maxUses !== '' && parseInt(maxUses) > 0) { constraints.max_uses = parseInt(maxUses); }

        if (Object.keys(constraints).length > 0) {
            body.constraints_json = constraints;
        }

        fetch('/research/odrl/create', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to create policy.');
            }
        })
        .catch(function(err) { alert('Error: ' + err.message); });
    });

    // ---- Edit Policy ----
    var editModalBody = document.querySelector('#editPolicyModal .modal-body');

    var tsEditTarget = new TomSelect('#editTargetSearch', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title'],
        maxItems: 1,
        dropdownParent: editModalBody,
        placeholder: 'Search target...',
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            var tt = document.getElementById('editTargetType').value;
            if (!tt) { callback(); return; }
            fetch(searchUrl + '?q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(tt))
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    callback((j.items || []).map(function(i) {
                        return { id: String(i.id), title: i.title || ('ID: ' + i.id) };
                    }));
                })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item) { return '<div class="py-1"><strong>' + item.title + '</strong></div>'; },
            item: function(item) { return '<div>' + item.title + '</div>'; },
            no_results: function() { return '<div class="no-results p-2 text-muted">No results found</div>'; }
        },
        onChange: function(value) {
            document.getElementById('editTargetId').value = value || '';
        }
    });

    var tsEditResearchers = new TomSelect('#editResearchers', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title'],
        dropdownParent: editModalBody,
        placeholder: 'Search researchers...',
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            fetch(searchUrl + '?q=' + encodeURIComponent(query) + '&type=researcher')
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    callback((j.items || []).map(function(i) {
                        return { id: String(i.id), title: i.title || i.email || ('ID: ' + i.id) };
                    }));
                })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item) { return '<div class="py-1">' + item.title + '</div>'; },
            item: function(item) { return '<div>' + item.title + '</div>'; },
            no_results: function() { return '<div class="no-results p-2 text-muted">No researchers found</div>'; }
        }
    });

    document.getElementById('editTargetType').addEventListener('change', function() {
        tsEditTarget.clear(); tsEditTarget.clearOptions();
        document.getElementById('editTargetId').value = '';
        var label = document.getElementById('editTargetSearchLabel');
        label.innerHTML = (targetLabels[this.value] || 'Target') + ' <span class="text-danger">*</span>';
    });

    // Edit button click — populate modal
    document.querySelectorAll('.edit-policy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var d = this.dataset;
            document.getElementById('editPolicyId').value = d.policyId;
            document.getElementById('editTargetType').value = d.targetType;
            document.getElementById('editPolicyType').value = d.policyType;
            document.getElementById('editActionType').value = d.actionType;

            // Set target label
            var label = document.getElementById('editTargetSearchLabel');
            label.innerHTML = (targetLabels[d.targetType] || 'Target') + ' <span class="text-danger">*</span>';

            // Pre-load the current target into TomSelect
            tsEditTarget.clear(); tsEditTarget.clearOptions();
            document.getElementById('editTargetId').value = d.targetId;
            tsEditTarget.addOption({ id: String(d.targetId), title: d.targetType + ' #' + d.targetId });
            tsEditTarget.setValue(String(d.targetId), true);

            // Resolve target name via search endpoint
            fetch(searchUrl + '?q=&type=' + encodeURIComponent(d.targetType) + '&id=' + d.targetId)
                .catch(function() {});

            // Parse constraints
            var c = {};
            try { c = JSON.parse(d.constraints || '{}'); } catch(e) {}
            document.getElementById('editDateFrom').value = c.date_from || '';
            document.getElementById('editDateTo').value = c.date_to || '';
            document.getElementById('editMaxUses').value = c.max_uses || '';

            // Researchers
            tsEditResearchers.clear(); tsEditResearchers.clearOptions();
            if (c.researcher_ids && Array.isArray(c.researcher_ids)) {
                c.researcher_ids.forEach(function(rid) {
                    tsEditResearchers.addOption({ id: String(rid), title: 'Researcher #' + rid });
                    tsEditResearchers.addItem(String(rid), true);
                });
            }

            new bootstrap.Modal(document.getElementById('editPolicyModal')).show();
        });
    });

    // Save edit
    document.getElementById('updatePolicyBtn').addEventListener('click', function() {
        var policyId = document.getElementById('editPolicyId').value;
        var targetType = document.getElementById('editTargetType').value;
        var targetId = document.getElementById('editTargetId').value;
        var policyType = document.getElementById('editPolicyType').value;
        var actionType = document.getElementById('editActionType').value;

        if (!targetType || !targetId) {
            alert('Target Type and Target are required.');
            return;
        }

        var body = {
            target_type: targetType,
            target_id: parseInt(targetId),
            policy_type: policyType,
            action_type: actionType
        };

        var constraints = {};
        var rIds = tsEditResearchers.getValue();
        if (rIds && rIds.length > 0) {
            constraints.researcher_ids = rIds.map(function(id) { return parseInt(id); });
        }
        var df = document.getElementById('editDateFrom').value;
        if (df) { constraints.date_from = df; }
        var dt = document.getElementById('editDateTo').value;
        if (dt) { constraints.date_to = dt; }
        var mu = document.getElementById('editMaxUses').value;
        if (mu !== '' && parseInt(mu) > 0) { constraints.max_uses = parseInt(mu); }

        if (Object.keys(constraints).length > 0) {
            body.constraints_json = constraints;
        } else {
            body.constraints_json = null;
        }

        fetch('/research/odrl/update/' + policyId, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) { window.location.reload(); }
            else { alert(data.error || 'Failed to update policy.'); }
        })
        .catch(function(err) { alert('Error: ' + err.message); });
    });

    // Delete policy
    document.querySelectorAll('.delete-policy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this policy? This cannot be undone.')) return;
            var policyId = this.dataset.policyId;
            fetch('/research/odrl/delete/' + policyId, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) { window.location.reload(); }
                else { alert(data.error || 'Failed to delete policy.'); }
            })
            .catch(function(err) { alert('Error: ' + err.message); });
        });
    });
});
</script>
