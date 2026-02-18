<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Hypotheses</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Hypotheses</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createHypothesisModal"><i class="fas fa-lightbulb me-1"></i> New Hypothesis</button>
</div>

<?php if (empty($hypotheses)): ?>
    <div class="alert alert-info">No hypotheses yet. Create one to track your research claims.</div>
<?php else: ?>
<?php foreach ($hypotheses as $h): ?>
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h5><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewHypothesis', 'id' => $h->id]); ?>"><?php echo htmlspecialchars(mb_strimwidth($h->statement, 0, 120, '...')); ?></a></h5>
            <div class="d-flex align-items-start gap-2">
                <span class="badge bg-<?php echo match($h->status) { 'proposed' => 'info', 'testing' => 'warning', 'supported' => 'success', 'refuted' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($h->status); ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item edit-hypothesis-btn" href="#" data-id="<?php echo (int) $h->id; ?>" data-statement="<?php echo htmlspecialchars($h->statement); ?>" data-tags="<?php echo htmlspecialchars($h->tags ?? ''); ?>"><i class="fas fa-edit me-1"></i>Edit</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item status-change-btn" href="#" data-id="<?php echo (int) $h->id; ?>" data-status="testing"><i class="fas fa-flask me-1"></i>Mark Testing</a></li>
                        <li><a class="dropdown-item status-change-btn" href="#" data-id="<?php echo (int) $h->id; ?>" data-status="supported"><i class="fas fa-check me-1"></i>Mark Supported</a></li>
                        <li><a class="dropdown-item status-change-btn" href="#" data-id="<?php echo (int) $h->id; ?>" data-status="refuted"><i class="fas fa-times me-1"></i>Mark Refuted</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger delete-hypothesis-btn" href="#" data-id="<?php echo (int) $h->id; ?>"><i class="fas fa-trash me-1"></i>Delete</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <small class="text-muted">Evidence: <?php echo (int) $h->evidence_count; ?> | <?php echo $h->created_at; ?></small>
        <?php if ($h->tags): ?><div class="mt-1"><?php foreach (explode(',', $h->tags) as $tag): ?><span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($tag)); ?></span><?php endforeach; ?></div><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Create Hypothesis Modal -->
<div class="modal fade" id="createHypothesisModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'hypotheses', 'project_id' => $project->id]); ?>">
            <input type="hidden" name="form_action" value="create">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">New Hypothesis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Statement</label><textarea name="statement" class="form-control" rows="4" required></textarea></div>
                    <div class="mb-3"><label class="form-label">Tags (comma-separated)</label><input type="text" name="tags" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Hypothesis Modal -->
<div class="modal fade" id="editHypothesisModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Hypothesis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="editHypothesisId">
                <div class="mb-3"><label class="form-label">Statement</label><textarea id="editHypothesisStatement" class="form-control" rows="4" required></textarea></div>
                <div class="mb-3"><label class="form-label">Tags (comma-separated)</label><input type="text" id="editHypothesisTags" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="saveEditHypothesis">Save</button></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Edit hypothesis
    document.querySelectorAll('.edit-hypothesis-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('editHypothesisId').value = this.dataset.id;
            document.getElementById('editHypothesisStatement').value = this.dataset.statement;
            document.getElementById('editHypothesisTags').value = this.dataset.tags;
            new bootstrap.Modal(document.getElementById('editHypothesisModal')).show();
        });
    });

    document.getElementById('saveEditHypothesis')?.addEventListener('click', function() {
        var id = document.getElementById('editHypothesisId').value;
        var form = new URLSearchParams();
        form.append('form_action', 'update');
        form.append('statement', document.getElementById('editHypothesisStatement').value);
        form.append('tags', document.getElementById('editHypothesisTags').value);
        fetch('/research/hypothesis/' + id + '/update', {
            method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: form.toString()
        }).then(function() { location.reload(); });
    });

    // Status change
    document.querySelectorAll('.status-change-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.dataset.id;
            var status = this.dataset.status;
            var form = new URLSearchParams();
            form.append('form_action', 'update_status');
            form.append('status', status);
            fetch('/research/hypothesis/' + id + '/update', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: form.toString()
            }).then(function() { location.reload(); });
        });
    });

    // Delete hypothesis
    document.querySelectorAll('.delete-hypothesis-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Delete this hypothesis?')) return;
            var id = this.dataset.id;
            var form = new URLSearchParams();
            form.append('form_action', 'delete');
            fetch('/research/hypothesis/' + id + '/update', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: form.toString()
            }).then(function() { location.reload(); });
        });
    });
});
</script>
