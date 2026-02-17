<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Extraction Jobs</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">AI Extraction Jobs</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createJobModal"><i class="fas fa-robot me-1"></i> New Job</button>
</div>

<?php if (empty($jobs)): ?>
    <div class="alert alert-info">No extraction jobs yet.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Type</th><th>Status</th><th>Progress</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $j): ?>
            <tr>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($j->extraction_type); ?></span></td>
                <td><span class="badge bg-<?php echo match($j->status) { 'queued' => 'secondary', 'running' => 'primary', 'completed' => 'success', 'failed' => 'danger', default => 'dark' }; ?>"><?php echo ucfirst($j->status); ?></span></td>
                <td>
                    <div class="progress" style="height: 20px;"><div class="progress-bar" style="width: <?php echo $j->total_items > 0 ? round(($j->processed_items / $j->total_items) * 100) : 0; ?>%"><?php echo (int) $j->processed_items; ?>/<?php echo (int) $j->total_items; ?></div></div>
                </td>
                <td><?php echo $j->created_at; ?></td>
                <td><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewExtractionJob', 'id' => $j->id]); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="modal fade" id="createJobModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'createExtractionJob', 'project_id' => $project->id]); ?>">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">New Extraction Job</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Collection ID</label><input type="number" name="collection_id" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Extraction Type</label><select name="extraction_type" class="form-select"><option value="ner">NER</option><option value="ocr">OCR</option><option value="summarize">Summarize</option><option value="translate">Translate</option><option value="spellcheck">Spellcheck</option><option value="face_detection">Face Detection</option><option value="form_extraction">Form Extraction</option></select></div>
                    <div class="mb-3"><label class="form-label">Language (optional)</label><input type="text" name="language" class="form-control" placeholder="en"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Job</button></div>
            </div>
        </form>
    </div>
</div>
