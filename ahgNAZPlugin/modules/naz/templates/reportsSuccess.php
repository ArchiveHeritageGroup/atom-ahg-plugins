<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
            <h1><i class="fas fa-file-alt me-2"></i>NAZ Compliance Reports</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                    <h5>Closure Period Report</h5>
                    <p class="text-muted small">Summary of all closure periods and expiration dates.</p>
                    <button class="btn btn-outline-primary" disabled><i class="fas fa-download me-1"></i> Generate</button>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-id-card fa-3x text-success mb-3"></i>
                    <h5>Research Permits Report</h5>
                    <p class="text-muted small">Active permits, fees collected, researcher statistics.</p>
                    <button class="btn btn-outline-primary" disabled><i class="fas fa-download me-1"></i> Generate</button>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-truck fa-3x text-info mb-3"></i>
                    <h5>Transfers Report</h5>
                    <p class="text-muted small">Records transfers and accession statistics.</p>
                    <button class="btn btn-outline-primary" disabled><i class="fas fa-download me-1"></i> Generate</button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Report generation will be available in a future update.
    </div>
</div>
