<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item active">New Scan</li>
                </ol>
            </nav>
            <h1><i class="fas fa-search me-2"></i>Start Duplicate Scan</h1>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Scan Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Scan Scope</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="scope" id="scopeAll" value="all" checked>
                                <label class="form-check-label" for="scopeAll">
                                    <strong>Entire System</strong>
                                    <br><small class="text-muted">Scan all records across all repositories</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scopeRepo" value="repository">
                                <label class="form-check-label" for="scopeRepo">
                                    <strong>Specific Repository</strong>
                                    <br><small class="text-muted">Scan records within a single repository</small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4" id="repositorySelect" style="display: none;">
                            <label for="repository_id" class="form-label">Select Repository</label>
                            <select name="repository_id" id="repository_id" class="form-select">
                                <option value="">-- Select Repository --</option>
                                <?php foreach ($repositories as $repo): ?>
                                    <option value="<?php echo $repo->id; ?>">
                                        <?php echo htmlspecialchars($repo->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> This will create a scan job. To process the scan, run:
                            <br><code>php symfony dedupe:scan --all</code> or <code>php symfony dedupe:scan --repository=ID</code>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play me-1"></i> Start Scan Job
                        </button>
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Scanning</h5>
                </div>
                <div class="card-body">
                    <p>The duplicate scan will:</p>
                    <ul>
                        <li>Compare all records against each other using configured detection rules</li>
                        <li>Apply title similarity, identifier matching, and other algorithms</li>
                        <li>Record detected duplicates for review</li>
                    </ul>

                    <p class="mb-0"><strong>Tip:</strong> For large collections, start with a single repository to test results before scanning the entire system.</p>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-terminal me-2"></i>CLI Commands</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Full system scan:</strong></p>
                    <pre class="bg-light p-2 rounded mb-3">php symfony dedupe:scan --all</pre>

                    <p class="mb-2"><strong>Repository scan:</strong></p>
                    <pre class="bg-light p-2 rounded mb-3">php symfony dedupe:scan --repository=1</pre>

                    <p class="mb-2"><strong>Limited scan:</strong></p>
                    <pre class="bg-light p-2 rounded mb-0">php symfony dedupe:scan --limit=1000</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var scopeAll = document.getElementById('scopeAll');
    var scopeRepo = document.getElementById('scopeRepo');
    var repoSelect = document.getElementById('repositorySelect');

    function toggleRepoSelect() {
        repoSelect.style.display = scopeRepo.checked ? 'block' : 'none';
    }

    scopeAll.addEventListener('change', toggleRepoSelect);
    scopeRepo.addEventListener('change', toggleRepoSelect);
});
</script>
