<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">DOI Minting</li>
    </ol>
</nav>

<h1 class="h2 mb-4">DOI Minting</h1>

<!-- Current DOI Status -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">DOI Status</h5></div>
    <div class="card-body">
        <?php if (!empty($currentDoi)): ?>
            <div class="alert alert-success mb-0">
                <strong>DOI Minted:</strong>
                <a href="https://doi.org/<?php echo htmlspecialchars($currentDoi); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($currentDoi); ?></a>
                <?php if (!empty($doiMintedAt)): ?>
                    <br><small class="text-muted">Minted on: <?php echo htmlspecialchars($doiMintedAt); ?></small>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No DOI has been minted for this project yet. Fill in the metadata below and mint one.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Metadata Form -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Project Metadata for DOI</h5></div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" id="doiTitle" class="form-control" value="<?php echo htmlspecialchars($project->title); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Creators</label>
            <input type="text" id="doiCreators" class="form-control" value="<?php echo htmlspecialchars($creatorsString ?? ''); ?>" placeholder="Name1, Name2, ...">
            <small class="text-muted">Comma-separated list of creator names</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea id="doiDescription" class="form-control" rows="3"><?php echo htmlspecialchars($project->description ?? ''); ?></textarea>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Publication Year</label>
                <input type="number" id="doiYear" class="form-control" value="<?php echo date('Y'); ?>" min="1900" max="2099">
            </div>
            <div class="col-md-4">
                <label class="form-label">Resource Type</label>
                <select id="doiResourceType" class="form-select">
                    <option value="Dataset">Dataset</option>
                    <option value="Collection">Collection</option>
                    <option value="Text">Text</option>
                    <option value="Software">Software</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Publisher</label>
                <input type="text" id="doiPublisher" class="form-control" value="<?php echo htmlspecialchars($project->institution_name ?? 'The Archive and Heritage Group'); ?>">
            </div>
        </div>
    </div>
</div>

<!-- Mint Button + Result -->
<div class="d-flex gap-2 mb-4">
    <?php if (empty($currentDoi)): ?>
        <button id="mintDoiBtn" class="btn btn-primary"><i class="fas fa-fingerprint me-1"></i> Mint DOI</button>
    <?php else: ?>
        <button id="mintDoiBtn" class="btn btn-warning"><i class="fas fa-sync me-1"></i> Update DOI Metadata</button>
    <?php endif; ?>
</div>

<div id="doiResult" class="d-none">
    <div class="alert" id="doiAlert"></div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('mintDoiBtn').addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';

    var payload = {
        title: document.getElementById('doiTitle').value,
        creators: document.getElementById('doiCreators').value.split(',').map(function(s){return s.trim();}).filter(Boolean),
        description: document.getElementById('doiDescription').value,
        publication_year: parseInt(document.getElementById('doiYear').value),
        resource_type: document.getElementById('doiResourceType').value,
        publisher: document.getElementById('doiPublisher').value
    };

    fetch('/research/doi/<?php echo (int) $project->id; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); }).then(function(data) {
        var resultDiv = document.getElementById('doiResult');
        var alertDiv = document.getElementById('doiAlert');
        resultDiv.classList.remove('d-none');
        if (data.doi || data.success) {
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = '<strong>Success!</strong> DOI: <a href="https://doi.org/' + (data.doi || '') + '" target="_blank">' + (data.doi || 'Minted') + '</a>';
            setTimeout(function() { location.reload(); }, 2000);
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = data.error || 'DOI minting failed. Check DataCite configuration.';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-fingerprint me-1"></i> Mint DOI';
    }).catch(function(e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-fingerprint me-1"></i> Mint DOI';
        var resultDiv = document.getElementById('doiResult');
        var alertDiv = document.getElementById('doiAlert');
        resultDiv.classList.remove('d-none');
        alertDiv.className = 'alert alert-danger';
        alertDiv.textContent = 'Network error: ' + e.message;
    });
});
</script>
