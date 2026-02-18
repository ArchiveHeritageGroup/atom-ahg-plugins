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

<!-- Pack Metadata -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Project Metadata</h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Title</dt><dd class="col-sm-9"><?php echo htmlspecialchars($project->title); ?></dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-<?php echo match($project->status ?? '') { 'active' => 'success', 'completed' => 'primary', 'on_hold' => 'warning', default => 'secondary' }; ?>"><?php echo ucfirst($project->status ?? 'unknown'); ?></span></dd>
            <?php if (!empty($project->description)): ?>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?php echo htmlspecialchars($project->description); ?></dd>
            <?php endif; ?>
            <?php if (!empty($project->institution_name)): ?>
            <dt class="col-sm-3">Institution</dt><dd class="col-sm-9"><?php echo htmlspecialchars($project->institution_name); ?></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?php echo $project->created_at ?? ''; ?></dd>
            <?php if (!empty($pack['integrity_hash'])): ?>
            <dt class="col-sm-3">Integrity Hash</dt><dd class="col-sm-9"><code><?php echo htmlspecialchars($pack['integrity_hash']); ?></code></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<!-- Snapshots -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Snapshots (<?php echo count($pack['snapshots'] ?? []); ?>)</h5>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'snapshots', 'project_id' => $project->id]); ?>" class="btn btn-sm btn-outline-primary">Manage</a>
    </div>
    <div class="card-body">
        <?php if (!empty($pack['snapshots'])): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Label</th><th>Created</th><th>Items</th></tr></thead>
                <tbody>
                <?php foreach ($pack['snapshots'] as $snap): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($snap['label'] ?? $snap['name'] ?? 'Snapshot'); ?></td>
                        <td><small><?php echo $snap['created_at'] ?? ''; ?></small></td>
                        <td><?php echo (int) ($snap['item_count'] ?? 0); ?></td>
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
    <div class="card-header"><h5 class="mb-0">Search Queries (<?php echo count($pack['search_queries'] ?? []); ?>)</h5></div>
    <div class="card-body">
        <?php if (!empty($pack['search_queries'])): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($pack['search_queries'] as $sq): ?>
            <li class="list-group-item px-0">
                <code><?php echo htmlspecialchars($sq['query'] ?? $sq['search_query'] ?? ''); ?></code>
                <small class="text-muted d-block"><?php echo $sq['created_at'] ?? ''; ?></small>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <p class="text-muted mb-0">No search queries recorded.</p>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Assertions -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Assertions (<?php echo count($pack['assertions'] ?? []); ?>)</h5></div>
            <div class="card-body" style="max-height:300px; overflow:auto;">
                <?php if (!empty($pack['assertions'])): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($pack['assertions'] as $a): ?>
                    <li class="list-group-item px-0 py-1">
                        <small><span class="badge bg-light text-dark"><?php echo htmlspecialchars($a['assertion_type'] ?? $a['type'] ?? ''); ?></span> <?php echo htmlspecialchars($a['predicate'] ?? ''); ?></small>
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
            <div class="card-header"><h5 class="mb-0">Extraction Jobs (<?php echo count($pack['extraction_jobs'] ?? []); ?>)</h5></div>
            <div class="card-body" style="max-height:300px; overflow:auto;">
                <?php if (!empty($pack['extraction_jobs'])): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($pack['extraction_jobs'] as $ej): ?>
                    <li class="list-group-item px-0 py-1">
                        <small><span class="badge bg-light text-dark"><?php echo htmlspecialchars($ej['extraction_type'] ?? $ej['type'] ?? ''); ?></span> <?php echo ucfirst($ej['status'] ?? ''); ?> - <?php echo (int) ($ej['processed_items'] ?? 0); ?>/<?php echo (int) ($ej['total_items'] ?? 0); ?></small>
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
