<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Hypothesis</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2">Hypothesis</h1>
        <span class="badge bg-<?php echo match($hypothesis->status) { 'proposed' => 'info', 'testing' => 'warning', 'supported' => 'success', 'refuted' => 'danger', default => 'secondary' }; ?> me-2"><?php echo ucfirst($hypothesis->status); ?></span>
    </div>
    <div class="d-flex gap-2">
        <!-- Status change dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-exchange-alt me-1"></i>Change Status</button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item status-btn" href="#" data-status="proposed">Proposed</a></li>
                <li><a class="dropdown-item status-btn" href="#" data-status="testing">Testing</a></li>
                <li><a class="dropdown-item status-btn" href="#" data-status="supported">Supported</a></li>
                <li><a class="dropdown-item status-btn" href="#" data-status="refuted">Refuted</a></li>
            </ul>
        </div>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editHypothesisModal"><i class="fas fa-edit me-1"></i>Edit</button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Statement</h5></div>
    <div class="card-body">
        <p class="lead"><?php echo nl2br(htmlspecialchars($hypothesis->statement)); ?></p>
        <?php if ($hypothesis->tags): ?><div class="mt-2"><?php foreach (explode(',', $hypothesis->tags) as $tag): ?><span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($tag)); ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="mt-3 text-muted small">Evidence count: <?php echo (int) $hypothesis->evidence_count; ?> | Created: <?php echo $hypothesis->created_at; ?></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Evidence</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEvidenceModal"><i class="fas fa-plus me-1"></i>Add Evidence</button>
    </div>
    <div class="card-body">
        <?php if (!empty($hypothesis->evidence)): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Source</th><th>Relationship</th><th>Confidence</th><th>Note</th><th>Added</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($hypothesis->evidence as $ev): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ev->source_type . ':' . $ev->source_id); ?></td>
                        <td><span class="badge bg-<?php echo $ev->relationship === 'supports' ? 'success' : ($ev->relationship === 'refutes' ? 'danger' : 'secondary'); ?>"><?php echo ucfirst($ev->relationship); ?></span></td>
                        <td><?php echo $ev->confidence !== null ? number_format((float)$ev->confidence, 1) . '%' : '-'; ?></td>
                        <td><?php echo htmlspecialchars($ev->note ?? ''); ?></td>
                        <td><?php echo $ev->created_at; ?></td>
                        <td><button class="btn btn-sm btn-outline-danger remove-evidence-btn" data-id="<?php echo (int) $ev->id; ?>" title="Remove"><i class="fas fa-trash"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No evidence linked yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Hypothesis Modal -->
<div class="modal fade" id="editHypothesisModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Hypothesis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Statement</label><textarea id="editStatement" class="form-control" rows="4"><?php echo htmlspecialchars($hypothesis->statement); ?></textarea></div>
                <div class="mb-3"><label class="form-label">Tags</label><input type="text" id="editTags" class="form-control" value="<?php echo htmlspecialchars($hypothesis->tags ?? ''); ?>"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="saveEditBtn">Save</button></div>
        </div>
    </div>
</div>

<!-- Add Evidence Modal -->
<div class="modal fade" id="addEvidenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Evidence</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Source Type</label>
                    <select id="evSourceType" class="form-select">
                        <option value="information_object">Information Object</option>
                        <option value="actor">Actor</option>
                        <option value="annotation">Annotation</option>
                        <option value="assertion">Assertion</option>
                        <option value="external">External Source</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Source ID</label><input type="number" id="evSourceId" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label">Relationship</label>
                    <select id="evRelationship" class="form-select">
                        <option value="supports">Supports</option>
                        <option value="refutes">Refutes</option>
                        <option value="inconclusive">Inconclusive</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Confidence (%)</label><input type="number" id="evConfidence" class="form-control" min="0" max="100" step="0.1" placeholder="e.g. 85"></div>
                <div class="mb-3"><label class="form-label">Note</label><textarea id="evNote" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="saveEvidenceBtn">Add Evidence</button></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var hypothesisId = <?php echo (int) $hypothesis->id; ?>;

    // Status change
    document.querySelectorAll('.status-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = new URLSearchParams();
            form.append('form_action', 'update_status');
            form.append('status', this.dataset.status);
            fetch('/research/hypothesis/' + hypothesisId + '/update', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: form.toString()
            }).then(function() { location.reload(); });
        });
    });

    // Edit hypothesis
    document.getElementById('saveEditBtn')?.addEventListener('click', function() {
        var form = new URLSearchParams();
        form.append('form_action', 'update');
        form.append('statement', document.getElementById('editStatement').value);
        form.append('tags', document.getElementById('editTags').value);
        fetch('/research/hypothesis/' + hypothesisId + '/update', {
            method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: form.toString()
        }).then(function() { location.reload(); });
    });

    // Add evidence
    document.getElementById('saveEvidenceBtn')?.addEventListener('click', function() {
        var form = new URLSearchParams();
        form.append('form_action', 'add_evidence');
        form.append('source_type', document.getElementById('evSourceType').value);
        form.append('source_id', document.getElementById('evSourceId').value);
        form.append('relationship', document.getElementById('evRelationship').value);
        form.append('confidence', document.getElementById('evConfidence').value);
        form.append('note', document.getElementById('evNote').value);
        fetch('/research/hypothesis/' + hypothesisId + '/update', {
            method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: form.toString()
        }).then(function() { location.reload(); });
    });

    // Remove evidence
    document.querySelectorAll('.remove-evidence-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Remove this evidence?')) return;
            var form = new URLSearchParams();
            form.append('form_action', 'remove_evidence');
            form.append('evidence_id', this.dataset.id);
            fetch('/research/hypothesis/' + hypothesisId + '/update', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: form.toString()
            }).then(function() { location.reload(); });
        });
    });
});
</script>
