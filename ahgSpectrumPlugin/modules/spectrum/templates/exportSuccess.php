<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>">Spectrum</a></li>
        <li class="breadcrumb-item active">Export</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-download text-primary me-2"></i>Spectrum Data Export</h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<?php if ($identifier): ?>
<div class="alert alert-info">Exporting data for: <strong><?php echo htmlspecialchars($identifier); ?></strong></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-truck me-2"></i>Movements</h5></div>
            <div class="card-body"><h3><?php echo count($movements); ?></h3><p class="text-muted">records available</p></div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Condition Checks</h5></div>
            <div class="card-body"><h3><?php echo count($conditions); ?></h3><p class="text-muted">records available</p></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning"><h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Valuations</h5></div>
            <div class="card-body"><h3><?php echo count($valuations); ?></h3><p class="text-muted">records available</p></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Loans In</h5></div>
            <div class="card-body"><h3><?php echo count($loansIn); ?></h3><p class="text-muted">records available</p></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-sign-out-alt me-2"></i>Loans Out</h5></div>
            <div class="card-body"><h3><?php echo count($loansOut); ?></h3><p class="text-muted">records available</p></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Export Options</h5></div>
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <?php if ($slug): ?>
            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
            <?php endif; ?>
            <input type="hidden" name="download" value="1">

            <div class="col-md-4">
                <label class="form-label">Export Type</label>
                <select name="type" class="form-select">
                    <option value="movement">Movements (<?php echo count($movements); ?>)</option>
                    <option value="condition">Condition Checks (<?php echo count($conditions); ?>)</option>
                    <option value="valuation">Valuations (<?php echo count($valuations); ?>)</option>
                    <option value="loan">Loans (<?php echo count($loansIn) + count($loansOut); ?>)</option>
                    <option value="workflow">Workflow History</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Format</label>
                <select name="format" class="form-select">
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-download me-2"></i>Download
                </button>
            </div>
        </form>
    </div>
</div>
