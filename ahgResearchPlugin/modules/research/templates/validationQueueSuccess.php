<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Validation Queue</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Validation Queue <span class="badge bg-warning"><?php echo (int) $pendingCount; ?> pending</span></h1>
</div>

<!-- Stats bar -->
<?php $s = $stats ?? []; ?>
<div class="row mb-4">
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-4 fw-bold text-warning"><?php echo (int) ($s['pending'] ?? 0); ?></div><small class="text-muted">Pending</small></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-4 fw-bold text-success"><?php echo (int) ($s['accepted'] ?? 0); ?></div><small class="text-muted">Accepted</small></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-4 fw-bold text-danger"><?php echo (int) ($s['rejected'] ?? 0); ?></div><small class="text-muted">Rejected</small></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="fs-4 fw-bold text-info"><?php echo ($s['avg_confidence'] ?? null) !== null ? number_format((float)$s['avg_confidence'] * 100, 1) . '%' : '-'; ?></div><small class="text-muted">Avg Confidence</small></div></div></div>
</div>

<!-- Filter bar -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="research"><input type="hidden" name="action" value="validationQueue">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="pending" <?php echo ($sf_request->getParameter('status', 'pending') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="accepted" <?php echo ($sf_request->getParameter('status') === 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                    <option value="rejected" <?php echo ($sf_request->getParameter('status') === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    <option value="modified" <?php echo ($sf_request->getParameter('status') === 'modified') ? 'selected' : ''; ?>>Modified</option>
                    <option value="" <?php echo ($sf_request->getParameter('status') === '') ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Result Type</label>
                <select name="result_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['entity','summary','translation','transcription','form_field','face'] as $rt): ?>
                    <option value="<?php echo $rt; ?>" <?php echo ($sf_request->getParameter('result_type') === $rt) ? 'selected' : ''; ?>><?php echo ucfirst($rt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Extraction Type</label>
                <select name="extraction_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['ocr','ner','summarize','translate','spellcheck','face_detection','form_extraction'] as $et): ?>
                    <option value="<?php echo $et; ?>" <?php echo ($sf_request->getParameter('extraction_type') === $et) ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $et)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Min Confidence</label>
                <input type="number" name="min_confidence" class="form-control form-control-sm" style="width:100px" min="0" max="1" step="0.01" value="<?php echo htmlspecialchars($sf_request->getParameter('min_confidence', '')); ?>" placeholder="0.00">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<?php if (empty($queue['items'] ?? [])): ?>
    <div class="alert alert-success">No items matching your filters.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Object</th>
                <th>Extraction</th>
                <th>Result Type</th>
                <th>Model</th>
                <th>Confidence</th>
                <th>Reviewer</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($queue['items'] as $item): ?>
            <tr>
                <td><input type="checkbox" class="queue-item" value="<?php echo (int) $item->result_id; ?>"></td>
                <td>
                    <?php if (!empty($item->object_title)): ?>
                        <strong><?php echo htmlspecialchars(mb_substr($item->object_title, 0, 50)); ?></strong>
                    <?php else: ?>
                        Object #<?php echo (int) ($item->object_id ?? 0); ?>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars(str_replace('_', ' ', $item->extraction_type ?? '')); ?></span></td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item->result_type ?? ''); ?></span></td>
                <td><small class="text-muted"><?php echo htmlspecialchars(mb_substr($item->model_version ?? '-', 0, 20)); ?></small></td>
                <td>
                    <?php if (isset($item->confidence)): ?>
                    <div class="d-flex align-items-center gap-1">
                        <div class="progress" style="width:60px;height:6px">
                            <?php $pct = round((float)$item->confidence * 100); $color = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'); ?>
                            <div class="progress-bar bg-<?php echo $color; ?>" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <small><?php echo $pct; ?>%</small>
                    </div>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($item->reviewer_first_name)): ?>
                        <small><?php echo htmlspecialchars($item->reviewer_first_name . ' ' . $item->reviewer_last_name); ?></small>
                    <?php else: ?>
                        <small class="text-muted">-</small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?php echo match($item->status ?? '') { 'pending' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'modified' => 'info', default => 'secondary' }; ?>"><?php echo ucfirst($item->status ?? 'pending'); ?></span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary preview-btn" data-id="<?php echo (int) $item->result_id; ?>" data-data="<?php echo htmlspecialchars($item->data_json ?? '{}'); ?>" title="Preview"><i class="fas fa-eye"></i></button>
                        <?php if (($item->status ?? '') === 'pending'): ?>
                        <button class="btn btn-success validate-btn" data-id="<?php echo (int) $item->result_id; ?>" data-action="accept" title="Accept"><i class="fas fa-check"></i></button>
                        <button class="btn btn-warning modify-btn" data-id="<?php echo (int) $item->result_id; ?>" data-data="<?php echo htmlspecialchars($item->data_json ?? '{}'); ?>" title="Edit & Accept"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger validate-btn" data-id="<?php echo (int) $item->result_id; ?>" data-action="reject" title="Reject"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="d-flex gap-2 mt-3">
    <button class="btn btn-success" id="bulkAccept"><i class="fas fa-check-double me-1"></i>Bulk Accept Selected</button>
    <button class="btn btn-danger" id="bulkReject"><i class="fas fa-times-circle me-1"></i>Bulk Reject Selected</button>
</div>
<?php endif; ?>

<!-- Data Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Data Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><pre id="previewData" class="bg-light p-3 rounded" style="max-height:400px;overflow:auto;white-space:pre-wrap"></pre></div>
        </div>
    </div>
</div>

<!-- Modify Modal -->
<div class="modal fade" id="modifyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit & Accept</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted">Edit the JSON data below, then click "Accept with Changes".</p>
                <textarea id="modifyData" class="form-control font-monospace" rows="12"></textarea>
                <input type="hidden" id="modifyResultId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="modifyAcceptBtn" class="btn btn-success">Accept with Changes</button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        var checked = this.checked;
        document.querySelectorAll('.queue-item').forEach(function(cb) { cb.checked = checked; });
    });

    // Preview data
    document.querySelectorAll('.preview-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            try {
                var data = JSON.parse(this.dataset.data);
                document.getElementById('previewData').textContent = JSON.stringify(data, null, 2);
            } catch(e) {
                document.getElementById('previewData').textContent = this.dataset.data;
            }
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        });
    });

    // Modify (Edit & Accept)
    document.querySelectorAll('.modify-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('modifyResultId').value = this.dataset.id;
            try {
                var data = JSON.parse(this.dataset.data);
                document.getElementById('modifyData').value = JSON.stringify(data, null, 2);
            } catch(e) {
                document.getElementById('modifyData').value = this.dataset.data;
            }
            new bootstrap.Modal(document.getElementById('modifyModal')).show();
        });
    });

    document.getElementById('modifyAcceptBtn')?.addEventListener('click', function() {
        var resultId = document.getElementById('modifyResultId').value;
        var raw = document.getElementById('modifyData').value;
        try {
            var modified = JSON.parse(raw);
        } catch(e) { alert('Invalid JSON'); return; }
        fetch('/research/validate/' + resultId, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({form_action: 'modify', modified_data: modified})
        }).then(function(r){return r.json();}).then(function(d){
            if(d.success) location.reload(); else alert(d.error||'Error');
        });
    });

    // Accept/reject single item
    document.querySelectorAll('.validate-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var action = this.dataset.action;
            var body = {form_action: action};
            if (action === 'reject') {
                var reason = prompt('Rejection reason:');
                if (reason === null) return;
                body.reason = reason;
            }
            fetch('/research/validate/' + this.dataset.id, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify(body)
            }).then(function(r){return r.json();}).then(function(d){
                if(d.success) location.reload(); else alert(d.error||'Error');
            });
        });
    });

    // Bulk accept
    document.getElementById('bulkAccept')?.addEventListener('click', function() {
        var ids = Array.from(document.querySelectorAll('.queue-item:checked')).map(function(cb){return parseInt(cb.value);});
        if (!ids.length) return alert('Select items first');
        fetch('/research/bulk-validate', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({result_ids:ids,form_action:'accept'}) }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); });
    });

    // Bulk reject
    document.getElementById('bulkReject')?.addEventListener('click', function() {
        var ids = Array.from(document.querySelectorAll('.queue-item:checked')).map(function(cb){return parseInt(cb.value);});
        if (!ids.length) return alert('Select items first');
        var reason = prompt('Rejection reason:');
        if (reason === null) return;
        fetch('/research/bulk-validate', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({result_ids:ids,form_action:'reject',reason:reason||''}) }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); });
    });
});
</script>
