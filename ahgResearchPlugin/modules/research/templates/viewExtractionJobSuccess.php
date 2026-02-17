<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Extraction Job #<?php echo (int) $job->id; ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><?php echo ucfirst($job->extraction_type); ?> Extraction Job</h1>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Job Details</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-<?php echo match($job->status) { 'queued' => 'secondary', 'running' => 'primary', 'completed' => 'success', 'failed' => 'danger', default => 'dark' }; ?>"><?php echo ucfirst($job->status); ?></span></dd>
                    <dt class="col-sm-3">Progress</dt><dd class="col-sm-9"><?php echo (int) $job->processed_items; ?> / <?php echo (int) $job->total_items; ?></dd>
                    <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?php echo $job->created_at; ?></dd>
                    <?php if ($job->completed_at): ?><dt class="col-sm-3">Completed</dt><dd class="col-sm-9"><?php echo $job->completed_at; ?></dd><?php endif; ?>
                    <?php if ($job->error_log): ?><dt class="col-sm-3">Errors</dt><dd class="col-sm-9"><pre class="bg-light p-2 small"><?php echo htmlspecialchars($job->error_log); ?></pre></dd><?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($results)): ?>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Results (<?php echo count($results); ?>)</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Object</th><th>Type</th><th>Confidence</th><th>Model</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?php echo (int) $r->object_id; ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($r->result_type); ?></span></td>
                        <td><?php echo $r->confidence !== null ? number_format((float)$r->confidence * 100, 1) . '%' : '-'; ?></td>
                        <td><small><?php echo htmlspecialchars($r->model_version ?? ''); ?></small></td>
                        <td><a href="<?php echo url_for(['module' => 'research', 'action' => 'validationQueue']); ?>" class="btn btn-sm btn-outline-warning">Review</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
