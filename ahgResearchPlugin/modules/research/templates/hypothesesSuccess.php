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
            <span class="badge bg-<?php echo match($h->status) { 'proposed' => 'info', 'testing' => 'warning', 'supported' => 'success', 'refuted' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($h->status); ?></span>
        </div>
        <small class="text-muted">Evidence: <?php echo (int) $h->evidence_count; ?> | <?php echo $h->created_at; ?></small>
        <?php if ($h->tags): ?><div class="mt-1"><?php foreach (explode(',', $h->tags) as $tag): ?><span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($tag)); ?></span><?php endforeach; ?></div><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

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
