<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-sync me-2"></i>Bulk Sync DOIs</h1>
            <p class="text-muted">Sync all DOI metadata with DataCite</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['total'] ?? 0 ?></h3>
                    <p class="mb-0">Total DOIs</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['findable'] ?? 0 ?></h3>
                    <p class="mb-0">Findable</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo $stats['queue_pending'] ?? 0 ?></h3>
                    <p class="mb-0">Queue Pending</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sync Options</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'doi', 'action' => 'sync']) ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Filter by Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Active DOIs</option>
                                    <option value="findable">Findable only</option>
                                    <option value="registered">Registered only</option>
                                    <option value="draft">Draft only</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Filter by Repository</label>
                                <select name="repository_id" class="form-select">
                                    <option value="">All Repositories</option>
                                    <?php foreach ($repositories as $repo): ?>
                                        <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->name ?? 'Repository #' . $repo->id) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="queue_all" value="1" class="form-check-input" id="queue_all">
                                <label class="form-check-label" for="queue_all">
                                    Queue all for background processing (recommended for large numbers)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="limit-field">
                            <label class="form-label">Limit (for direct sync)</label>
                            <select name="limit" class="form-select" style="max-width: 200px;">
                                <option value="10">10 DOIs</option>
                                <option value="50" selected>50 DOIs</option>
                                <option value="100">100 DOIs</option>
                            </select>
                            <div class="form-text">Direct sync processes immediately but is limited. Use queue for larger batches.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync me-1"></i> Start Sync
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">CLI Alternative</h5>
                </div>
                <div class="card-body">
                    <p class="small">For large sync operations, use the CLI:</p>
                    <pre class="small bg-light p-2 rounded"><code>php symfony doi:sync --all</code></pre>
                    <p class="small text-muted mb-0">
                        The CLI version supports more options and better progress reporting.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">What Gets Synced?</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li class="mb-2">Title and descriptions</li>
                        <li class="mb-2">Creator/contributor information</li>
                        <li class="mb-2">Dates and subjects</li>
                        <li class="mb-2">Landing page URLs</li>
                        <li>All mapped DataCite fields</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('queue_all').addEventListener('change', function() {
    document.getElementById('limit-field').style.display = this.checked ? 'none' : 'block';
});
</script>
