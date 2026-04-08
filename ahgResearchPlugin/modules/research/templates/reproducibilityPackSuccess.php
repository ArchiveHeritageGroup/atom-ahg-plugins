<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Reproducibility Pack</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Reproducibility Pack</h1>
    <button id="downloadPack" class="btn btn-primary"><i class="fas fa-download me-1"></i> Download Pack (JSON)</button>
</div>

<!-- Summary cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <h4 class="mb-0"><?php echo count($milestones ?? []); ?></h4>
            <small class="text-muted">Milestones</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <h4 class="mb-0"><?php echo count($snapshots ?? []); ?></h4>
            <small class="text-muted">Snapshots</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <h4 class="mb-0"><?php echo count($resources ?? []); ?></h4>
            <small class="text-muted">Resources</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <h4 class="mb-0"><?php echo count($assertions ?? []); ?></h4>
            <small class="text-muted">Assertions</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <h4 class="mb-0"><?php echo count($hypotheses ?? []); ?></h4>
            <small class="text-muted">Hypotheses</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <h4 class="mb-0"><?php echo count($extractionJobs ?? []); ?></h4>
            <small class="text-muted">Extraction Jobs</small>
        </div></div>
    </div>
</div>

<!-- Project Metadata -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Project Metadata</h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Title</dt><dd class="col-sm-9"><?php echo htmlspecialchars($project->title); ?></dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-<?php echo match($project->status ?? '') { 'active' => 'success', 'completed' => 'primary', 'on_hold' => 'warning', default => 'secondary' }; ?>"><?php echo ucfirst($project->status ?? 'unknown'); ?></span></dd>
            <?php if (!empty($project->description)): ?>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?php echo htmlspecialchars($project->description); ?></dd>
            <?php endif; ?>
            <?php if (!empty($project->institution_name ?? $project->institution ?? null)): ?>
            <dt class="col-sm-3">Institution</dt><dd class="col-sm-9"><?php echo htmlspecialchars($project->institution_name ?? $project->institution ?? ''); ?></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?php echo $project->created_at ?? ''; ?></dd>
            <dt class="col-sm-3">Integrity Hash</dt><dd class="col-sm-9"><code><?php echo hash('sha256', json_encode([$project->id, count($assertions ?? []), count($snapshots ?? []), count($milestones ?? [])])); ?></code></dd>
        </dl>
    </div>
</div>

<!-- Snapshots -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Snapshots (<?php echo count($snapshots ?? []); ?>)</h5>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'snapshots', 'project_id' => $project->id]); ?>" class="btn btn-sm btn-outline-primary">Manage</a>
    </div>
    <div class="card-body">
        <?php if (!empty($snapshots)): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Label</th><th>Created</th><th>Items</th></tr></thead>
                <tbody>
                <?php foreach ($snapshots as $snap): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($snap->title ?? $snap->label ?? $snap->name ?? 'Snapshot'); ?></td>
                        <td><small><?php echo $snap->created_at ?? ''; ?></small></td>
                        <td><?php echo (int) ($snap->item_count ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No snapshots.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Search Queries -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Search Queries (<?php echo count($searchQueries ?? []); ?>)</h5></div>
    <div class="card-body">
        <?php if (!empty($searchQueries)): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($searchQueries as $sq): ?>
            <li class="list-group-item px-0">
                <code><?php echo htmlspecialchars($sq->search_query ?? $sq->query ?? ''); ?></code>
                <small class="text-muted d-block"><?php echo $sq->created_at ?? ''; ?></small>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <p class="text-muted mb-0">No search queries recorded.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Milestones -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Milestones (<?php echo count($milestones ?? []); ?>)</h5>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'ethicsMilestones', 'project_id' => $project->id]); ?>" class="btn btn-sm btn-outline-primary">Manage</a>
    </div>
    <ul class="list-group list-group-flush">
        <?php if (!empty($milestones)): ?>
            <?php foreach ($milestones as $m): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($m->title ?? ''); ?></span>
                <span class="badge bg-<?php echo match($m->status ?? '') { 'completed' => 'success', 'approved' => 'success', 'in_progress' => 'primary', default => 'secondary' }; ?>"><?php echo ucfirst(str_replace('_', ' ', $m->status ?? 'pending')); ?></span>
            </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item text-muted small">No milestones.</li>
        <?php endif; ?>
    </ul>
</div>

<div class="row">
    <!-- Assertions -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Assertions (<?php echo count($assertions ?? []); ?>)</h5></div>
            <div class="card-body" style="max-height:300px; overflow:auto;">
                <?php if (!empty($assertions)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($assertions as $a): ?>
                    <li class="list-group-item px-0 py-1">
                        <small>
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($a->assertion_type ?? $a->type ?? ''); ?></span>
                            <strong><?php echo htmlspecialchars($a->subject_label ?? ''); ?></strong>
                            <?php echo htmlspecialchars($a->predicate ?? ''); ?>
                            <strong><?php echo htmlspecialchars($a->object_label ?? $a->object_value ?? ''); ?></strong>
                        </small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No assertions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Extraction Jobs -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Extraction Jobs (<?php echo count($extractionJobs ?? []); ?>)</h5></div>
            <div class="card-body" style="max-height:300px; overflow:auto;">
                <?php if (!empty($extractionJobs)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($extractionJobs as $ej): ?>
                    <li class="list-group-item px-0 py-1">
                        <small><span class="badge bg-light text-dark"><?php echo htmlspecialchars($ej->extraction_type ?? $ej->type ?? ''); ?></span> <?php echo ucfirst($ej->status ?? ''); ?> - <?php echo (int) ($ej->processed_items ?? 0); ?>/<?php echo (int) ($ej->total_items ?? 0); ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No extraction jobs.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resources -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Resources (<?php echo count($resources ?? []); ?>)</h5></div>
    <ul class="list-group list-group-flush">
        <?php if (!empty($resources)): ?>
            <?php foreach ($resources as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <?php if (!empty($r->external_url)): ?>
                    <a href="<?php echo htmlspecialchars($r->external_url); ?>" target="_blank"><?php echo htmlspecialchars($r->title ?? $r->external_url); ?> <i class="fas fa-external-link-alt fa-xs"></i></a>
                    <?php else: ?>
                    <?php echo htmlspecialchars($r->title ?? ''); ?>
                    <?php endif; ?>
                </span>
                <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $r->resource_type ?? '')); ?></span>
            </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item text-muted small">No resources.</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Hypotheses -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Hypotheses (<?php echo count($hypotheses ?? []); ?>)</h5></div>
    <ul class="list-group list-group-flush">
        <?php if (!empty($hypotheses)): ?>
            <?php foreach ($hypotheses as $h): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars(mb_strimwidth($h->statement ?? '', 0, 80, '...')); ?></span>
                <span class="badge bg-<?php echo match($h->status ?? '') { 'supported' => 'success', 'refuted' => 'danger', 'testing' => 'info', default => 'warning' }; ?>"><?php echo ucfirst($h->status ?? 'proposed'); ?></span>
            </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item text-muted small">No hypotheses.</li>
        <?php endif; ?>
    </ul>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('downloadPack').addEventListener('click', function() {
    var projectId = <?php echo (int) $project->id; ?>;
    fetch('/research/reproducibility/' + projectId + '?format=json')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'reproducibility-pack-project-' + projectId + '.json';
            a.click();
        });
});
</script>
