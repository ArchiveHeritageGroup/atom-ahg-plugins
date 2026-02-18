<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">RO-Crate Package</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">RO-Crate Package</h1>
    <button id="downloadRoCrate" class="btn btn-primary"><i class="fas fa-download me-1"></i> Download RO-Crate (JSON-LD)</button>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-1"></i> This page generates an <a href="https://www.researchobject.org/ro-crate/1.1/" target="_blank" rel="noopener">RO-Crate 1.1</a> compliant research object package for your project.
</div>

<!-- Root Dataset -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Root Dataset</h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?php echo htmlspecialchars($roCrate['name'] ?? $project->title); ?></dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?php echo htmlspecialchars($roCrate['description'] ?? $project->description ?? '-'); ?></dd>
            <?php if (!empty($roCrate['datePublished'])): ?>
            <dt class="col-sm-3">Date Published</dt><dd class="col-sm-9"><?php echo htmlspecialchars($roCrate['datePublished']); ?></dd>
            <?php endif; ?>
            <?php if (!empty($roCrate['license'])): ?>
            <dt class="col-sm-3">License</dt><dd class="col-sm-9"><?php echo htmlspecialchars($roCrate['license']); ?></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Conformance</dt><dd class="col-sm-9"><code>https://w3id.org/ro/crate/1.1</code></dd>
        </dl>
    </div>
</div>

<!-- Creators -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Creators (<?php echo count($roCrate['creators'] ?? []); ?>)</h5></div>
    <div class="card-body">
        <?php if (!empty($roCrate['creators'])): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Name</th><th>ORCID</th><th>Affiliation</th></tr></thead>
                <tbody>
                <?php foreach ($roCrate['creators'] as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['name'] ?? ''); ?></td>
                        <td><?php echo !empty($c['orcid']) ? '<a href="https://orcid.org/' . htmlspecialchars($c['orcid']) . '" target="_blank">' . htmlspecialchars($c['orcid']) . '</a>' : '-'; ?></td>
                        <td><?php echo htmlspecialchars($c['affiliation'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No creators specified.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Data Items -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Items (<?php echo count($roCrate['items'] ?? []); ?>)</h5></div>
    <div class="card-body" style="max-height:400px; overflow:auto;">
        <?php if (!empty($roCrate['items'])): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>ID</th><th>Type</th><th>Name</th></tr></thead>
                <tbody>
                <?php foreach ($roCrate['items'] as $item): ?>
                    <tr>
                        <td><small><code><?php echo htmlspecialchars($item['@id'] ?? $item['id'] ?? ''); ?></code></small></td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['@type'] ?? $item['type'] ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No items in this package.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Assertions in package -->
<?php if (!empty($roCrate['assertions'])): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Assertions (<?php echo count($roCrate['assertions']); ?>)</h5></div>
    <div class="card-body" style="max-height:300px; overflow:auto;">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Type</th><th>Predicate</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($roCrate['assertions'] as $a): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($a['assertion_type'] ?? $a['type'] ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars($a['predicate'] ?? ''); ?></td>
                        <td><span class="badge bg-<?php echo match($a['status'] ?? '') { 'verified' => 'success', 'proposed' => 'info', 'disputed' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($a['status'] ?? ''); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('downloadRoCrate').addEventListener('click', function() {
    var projectId = <?php echo (int) $project->id; ?>;
    fetch('/research/ro-crate/' + projectId + '?format=json')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/ld+json'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'ro-crate-metadata-project-' + projectId + '.jsonld';
            a.click();
        });
});
</script>
