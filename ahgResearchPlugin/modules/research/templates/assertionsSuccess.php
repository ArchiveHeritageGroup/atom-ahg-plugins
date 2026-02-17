<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Assertions</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Assertions</h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'knowledgeGraph', 'project_id' => $project->id]); ?>" class="btn btn-outline-primary"><i class="fas fa-project-diagram me-1"></i> Knowledge Graph</a>
</div>

<div class="mb-3">
    <form method="get" class="row g-2">
        <input type="hidden" name="module" value="research"><input type="hidden" name="action" value="assertions"><input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
        <div class="col-auto"><select name="assertion_type" class="form-select form-select-sm"><option value="">All Types</option><option value="biographical">Biographical</option><option value="chronological">Chronological</option><option value="spatial">Spatial</option><option value="relational">Relational</option><option value="attributive">Attributive</option></select></div>
        <div class="col-auto"><select name="status" class="form-select form-select-sm"><option value="">All Statuses</option><option value="proposed">Proposed</option><option value="verified">Verified</option><option value="disputed">Disputed</option></select></div>
        <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
    </form>
</div>

<?php if (empty($assertions)): ?>
    <div class="alert alert-info">No assertions yet.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Subject</th><th>Predicate</th><th>Object</th><th>Type</th><th>Status</th><th>Confidence</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($assertions as $a): ?>
            <tr>
                <td><?php echo htmlspecialchars($a->subject_label ?? $a->subject_type . ':' . $a->subject_id); ?></td>
                <td><strong><?php echo htmlspecialchars($a->predicate); ?></strong></td>
                <td><?php echo htmlspecialchars($a->object_label ?? $a->object_value ?? ''); ?></td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($a->assertion_type); ?></span></td>
                <td><span class="badge bg-<?php echo match($a->status) { 'proposed' => 'info', 'verified' => 'success', 'disputed' => 'warning', 'retracted' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($a->status); ?></span></td>
                <td><?php echo $a->confidence !== null ? number_format((float)$a->confidence, 1) . '%' : '-'; ?></td>
                <td><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewAssertion', 'id' => $a->id]); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
