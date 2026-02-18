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
                    <?php foreach (['collection', 'project', 'snapshot', 'annotation', 'assertion'] as $tt): ?>
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
                    <button class="btn btn-sm btn-outline-danger delete-policy-btn" data-policy-id="<?php echo (int) $p->id; ?>" title="Delete"><i class="fas fa-trash"></i></button>
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
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create ODRL Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Target Type *</label>
          <select id="policyTargetType" class="form-select">
            <option value="">Select...</option>
            <option value="collection">Collection</option>
            <option value="project">Project</option>
            <option value="snapshot">Snapshot</option>
            <option value="annotation">Annotation</option>
            <option value="assertion">Assertion</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Target *</label>
          <select id="policyTargetSearch" placeholder="Type 2+ characters to search..."></select>
          <input type="hidden" id="policyTargetId" value="">
          <small class="text-muted">Search for the project, collection, or other entity to apply this policy to.</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Policy Type *</label>
          <select id="policyType" class="form-select">
            <option value="permission">Permission</option>
            <option value="prohibition">Prohibition</option>
            <option value="obligation">Obligation</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Action Type *</label>
          <select id="policyActionType" class="form-select">
            <option value="use">Use</option>
            <option value="reproduce">Reproduce</option>
            <option value="distribute">Distribute</option>
            <option value="modify">Modify</option>
            <option value="archive">Archive</option>
            <option value="display">Display</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Constraints (JSON, optional)</label>
          <textarea id="policyConstraints" class="form-control" rows="3" placeholder='{"date_from": "2026-01-01", "max_uses": 10}'></textarea>
          <small class="text-muted">Keys: researcher_ids (array), date_from, date_to, max_uses</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitPolicyBtn">Create Policy</button>
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
    // Target search Tom Select
    var searchUrl = '/index.php/research/ajax/search-entities';
    var targetEl = document.getElementById('policyTargetSearch');
    var parentEl = targetEl.closest('.modal-body') || document.body;
    var tsTarget = new TomSelect('#policyTargetSearch', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title'],
        maxItems: 1,
        dropdownParent: parentEl,
        placeholder: 'Type 2+ characters to search...',
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            var targetType = document.getElementById('policyTargetType').value;
            if (!targetType) { callback(); return; }
            fetch(searchUrl + '?q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(targetType))
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    var items = (j.items || []).map(function(i) {
                        return { id: String(i.id), title: i.title || ('ID: ' + i.id) };
                    });
                    callback(items);
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

    // Reset Tom Select when target type changes
    document.getElementById('policyTargetType').addEventListener('change', function() {
        tsTarget.clear(); tsTarget.clearOptions();
        document.getElementById('policyTargetId').value = '';
    });

    // Create policy
    document.getElementById('submitPolicyBtn').addEventListener('click', function() {
        var targetType = document.getElementById('policyTargetType').value;
        var targetId = document.getElementById('policyTargetId').value;
        var policyType = document.getElementById('policyType').value;
        var actionType = document.getElementById('policyActionType').value;
        var constraints = document.getElementById('policyConstraints').value.trim();

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
        if (constraints) {
            try { body.constraints_json = JSON.parse(constraints); }
            catch (e) { alert('Invalid JSON in constraints.'); return; }
        }

        fetch('/index.php/research/odrl/create', {
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

    // Delete policy
    document.querySelectorAll('.delete-policy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this policy? This cannot be undone.')) return;
            var policyId = this.dataset.policyId;
            fetch('/index.php/research/odrl/delete/' + policyId, {
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
