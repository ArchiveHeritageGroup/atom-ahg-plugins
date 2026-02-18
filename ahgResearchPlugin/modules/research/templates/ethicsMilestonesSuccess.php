<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Ethics Milestones</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Ethics Milestones</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMilestoneModal"><i class="fas fa-plus me-1"></i> Add Milestone</button>
</div>

<?php if (empty($milestones)): ?>
    <div class="alert alert-info">No ethics milestones yet. Add one to track your ethics review process.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>#</th><th>Title</th><th>Type</th><th>Status</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($milestones as $i => $m): ?>
            <tr>
                <td><?php echo (int) ($m->sort_order ?? $i + 1); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($m->title); ?></strong>
                    <?php if (!empty($m->description)): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($m->description, 0, 100, '...')); ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($m->milestone_type ?? 'ethics'); ?></span></td>
                <td><span class="badge bg-<?php echo match($m->status ?? '') { 'completed' => 'success', 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($m->status ?? 'pending'); ?></span></td>
                <td><small><?php echo $m->created_at ?? ''; ?></small></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <?php if (($m->status ?? '') === 'pending'): ?>
                            <button class="btn btn-outline-success milestone-status-btn" data-id="<?php echo (int) $m->id; ?>" data-status="approved" title="Approve"><i class="fas fa-check"></i></button>
                            <button class="btn btn-outline-danger milestone-status-btn" data-id="<?php echo (int) $m->id; ?>" data-status="rejected" title="Reject"><i class="fas fa-times"></i></button>
                        <?php elseif (($m->status ?? '') === 'approved'): ?>
                            <button class="btn btn-outline-primary milestone-status-btn" data-id="<?php echo (int) $m->id; ?>" data-status="completed" title="Mark Complete"><i class="fas fa-flag-checkered"></i></button>
                        <?php endif; ?>
                        <button class="btn btn-outline-danger milestone-delete-btn" data-id="<?php echo (int) $m->id; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Add Milestone Modal -->
<div class="modal fade" id="addMilestoneModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'ethicsMilestones', 'project_id' => $project->id]); ?>">
            <input type="hidden" name="form_action" value="add_milestone">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Ethics Milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="milestone_type" class="form-select">
                            <option value="ethics">Ethics Review</option>
                            <option value="irb_approval">IRB Approval</option>
                            <option value="consent">Informed Consent</option>
                            <option value="data_management">Data Management Plan</option>
                            <option value="risk_assessment">Risk Assessment</option>
                            <option value="compliance_check">Compliance Check</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Milestone</button></div>
            </div>
        </form>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var projectId = <?php echo (int) $project->id; ?>;

    // Status change buttons
    document.querySelectorAll('.milestone-status-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var status = this.dataset.status;
            if (!confirm('Change milestone status to "' + status + '"?')) return;
            fetch('<?php echo url_for(['module' => 'research', 'action' => 'ethicsMilestones', 'project_id' => $project->id]); ?>', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'form_action=update_status&milestone_id=' + id + '&status=' + status
            }).then(function() { location.reload(); });
        });
    });

    // Delete buttons
    document.querySelectorAll('.milestone-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this milestone?')) return;
            var id = this.dataset.id;
            fetch('<?php echo url_for(['module' => 'research', 'action' => 'ethicsMilestones', 'project_id' => $project->id]); ?>', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'form_action=delete_milestone&milestone_id=' + id
            }).then(function() { location.reload(); });
        });
    });
});
</script>
