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

<!-- Status filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="research"><input type="hidden" name="action" value="extractionJobs"><input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="queued" <?php echo ($sf_request->getParameter('status') === 'queued') ? 'selected' : ''; ?>>Queued</option>
                    <option value="running" <?php echo ($sf_request->getParameter('status') === 'running') ? 'selected' : ''; ?>>Running</option>
                    <option value="completed" <?php echo ($sf_request->getParameter('status') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo ($sf_request->getParameter('status') === 'failed') ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<?php if (empty($jobs)): ?>
    <div class="alert alert-info">No extraction jobs yet.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Type</th><th>Status</th><th>Progress</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $j): ?>
            <tr data-job-id="<?php echo (int) $j->id; ?>" data-status="<?php echo htmlspecialchars($j->status); ?>">
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($j->extraction_type); ?></span></td>
                <td><span class="badge bg-<?php echo match($j->status) { 'queued' => 'secondary', 'running' => 'primary', 'completed' => 'success', 'failed' => 'danger', default => 'dark' }; ?> job-status"><?php echo ucfirst($j->status); ?></span></td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <?php $pct = $j->total_items > 0 ? round(($j->processed_items / $j->total_items) * 100) : 0; ?>
                        <div class="progress-bar job-progress" style="width: <?php echo $pct; ?>%"><?php echo (int) $j->processed_items; ?>/<?php echo (int) $j->total_items; ?></div>
                    </div>
                </td>
                <td><?php echo $j->created_at; ?></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewExtractionJob', 'id' => $j->id]); ?>" class="btn btn-outline-primary">View</a>
                        <?php if (in_array($j->status, ['queued', 'running'])): ?>
                            <button class="btn btn-outline-danger cancel-job-btn" data-id="<?php echo (int) $j->id; ?>" title="Cancel"><i class="fas fa-stop"></i></button>
                        <?php elseif ($j->status === 'failed'): ?>
                            <button class="btn btn-outline-warning retry-job-btn" data-id="<?php echo (int) $j->id; ?>" title="Retry"><i class="fas fa-redo"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
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
                    <div class="mb-3"><label class="form-label">Collection ID</label><input type="number" name="collection_id" id="jobCollectionId" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Extraction Type</label><select name="extraction_type" class="form-select"><option value="ner">NER</option><option value="ocr">OCR</option><option value="summarize">Summarize</option><option value="translate">Translate</option><option value="spellcheck">Spellcheck</option><option value="face_detection">Face Detection</option><option value="form_extraction">Form Extraction</option></select></div>
                    <div class="mb-3"><label class="form-label">Language (optional)</label><input type="text" name="language" class="form-control" placeholder="en"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Job</button></div>
            </div>
        </form>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Cancel job
    document.querySelectorAll('.cancel-job-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Cancel this extraction job?')) return;
            var id = this.dataset.id;
            fetch('/research/extraction-job/create', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'form_action=cancel&job_id=' + id
            }).then(function() { location.reload(); });
        });
    });

    // Retry job
    document.querySelectorAll('.retry-job-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Retry this extraction job?')) return;
            var id = this.dataset.id;
            fetch('/research/extraction-job/create', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'form_action=retry&job_id=' + id
            }).then(function() { location.reload(); });
        });
    });

    // Poll for running jobs
    var hasRunning = document.querySelectorAll('tr[data-status="running"], tr[data-status="queued"]').length > 0;
    if (hasRunning) {
        setInterval(function() {
            document.querySelectorAll('tr[data-status="running"], tr[data-status="queued"]').forEach(function(row) {
                var jobId = row.dataset.jobId;
                fetch('/research/extraction-job/' + jobId + '?ajax=1')
                    .then(function(r) { return r.text(); })
                    .then(function() { /* Full page reload is simpler for status updates */ });
            });
            location.reload();
        }, 10000); // Poll every 10 seconds
    }
});
</script>
