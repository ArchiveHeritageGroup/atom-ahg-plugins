<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Assertion Batch Review</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Assertion Batch Review <span class="badge bg-warning"><?php echo count($assertions ?? []); ?> proposed</span></h1>
</div>

<?php if (empty($assertions)): ?>
    <div class="alert alert-success">No proposed assertions to review.</div>
<?php else: ?>
<form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'assertionBatchReview', 'project_id' => $projectId]); ?>">
    <input type="hidden" name="form_action" value="batch_review">

    <div class="card mb-3">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <div>
                <input type="checkbox" id="selectAll" class="form-check-input me-2">
                <label for="selectAll" class="form-check-label">Select All</label>
            </div>
            <div class="d-flex gap-2">
                <select name="new_status" class="form-select form-select-sm" style="width:auto;">
                    <option value="verified">Verify</option>
                    <option value="disputed">Dispute</option>
                    <option value="retracted">Retract</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check-double me-1"></i>Apply to Selected</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;"></th>
                    <th>Subject</th>
                    <th>Predicate</th>
                    <th>Object</th>
                    <th>Type</th>
                    <th>Evidence</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($assertions as $a): ?>
                <tr>
                    <td><input type="checkbox" name="assertion_ids[]" value="<?php echo (int) $a->id; ?>" class="form-check-input assertion-cb"></td>
                    <td>
                        <small>
                            <?php echo htmlspecialchars(($a->subject_type ?? '') . ':' . ($a->subject_id ?? '')); ?>
                            <?php if (!empty($a->subject_label)): ?><br><span class="text-muted"><?php echo htmlspecialchars($a->subject_label); ?></span><?php endif; ?>
                        </small>
                    </td>
                    <td><strong><?php echo htmlspecialchars($a->predicate ?? ''); ?></strong></td>
                    <td>
                        <small>
                            <?php echo htmlspecialchars(($a->object_type ?? '') . ':' . ($a->object_id ?? '')); ?>
                            <?php if (!empty($a->object_label)): ?><br><span class="text-muted"><?php echo htmlspecialchars($a->object_label); ?></span><?php endif; ?>
                        </small>
                    </td>
                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($a->assertion_type ?? ''); ?></span></td>
                    <td><?php echo (int) ($a->evidence_count ?? 0); ?></td>
                    <td><small><?php echo $a->created_at ?? ''; ?></small></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewAssertion', 'id' => $a->id]); ?>" class="btn btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                            <button type="button" class="btn btn-outline-success single-verify" data-id="<?php echo (int) $a->id; ?>" title="Verify"><i class="fas fa-check"></i></button>
                            <button type="button" class="btn btn-outline-danger single-dispute" data-id="<?php echo (int) $a->id; ?>" title="Dispute"><i class="fas fa-times"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Select all
    document.getElementById('selectAll')?.addEventListener('change', function() {
        var checked = this.checked;
        document.querySelectorAll('.assertion-cb').forEach(function(cb) { cb.checked = checked; });
    });

    // Single verify
    document.querySelectorAll('.single-verify').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            fetch('/research/assertion/' + id + '/status', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({form_action: 'update_status', status: 'verified'})
            }).then(function(r){return r.json();}).then(function(d){
                if(d.success) location.reload(); else alert(d.error||'Error');
            });
        });
    });

    // Single dispute
    document.querySelectorAll('.single-dispute').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var reason = prompt('Reason for dispute (optional):');
            if (reason === null) return;
            var id = this.dataset.id;
            fetch('/research/assertion/' + id + '/status', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({form_action: 'update_status', status: 'disputed', reason: reason})
            }).then(function(r){return r.json();}).then(function(d){
                if(d.success) location.reload(); else alert(d.error||'Error');
            });
        });
    });
});
</script>
