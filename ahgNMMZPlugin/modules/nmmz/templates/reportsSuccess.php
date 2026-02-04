<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
            <h1><i class="fas fa-file-alt me-2"></i>NMMZ Reports</h1>
            <p class="text-muted">Generate compliance and status reports</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-monument fa-3x text-primary mb-3"></i>
                    <h5>National Monuments Report</h5>
                    <p class="text-muted small">Summary of all registered national monuments, their status and condition.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-vase fa-3x text-info mb-3"></i>
                    <h5>Antiquities Inventory</h5>
                    <p class="text-muted small">Complete inventory of registered antiquities with provenance information.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-export fa-3x text-warning mb-3"></i>
                    <h5>Export Permits Summary</h5>
                    <p class="text-muted small">Summary of export permit applications and their statuses.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-map-marker-alt fa-3x text-success mb-3"></i>
                    <h5>Archaeological Sites Report</h5>
                    <p class="text-muted small">Overview of archaeological sites and their protection status.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-check fa-3x text-danger mb-3"></i>
                    <h5>HIA Summary Report</h5>
                    <p class="text-muted small">Heritage Impact Assessments summary and outcomes.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-secondary mb-3"></i>
                    <h5>Compliance Overview</h5>
                    <p class="text-muted small">Overall compliance status and pending actions summary.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Report generation functionality will be available in a future update.
    </div>
</div>
