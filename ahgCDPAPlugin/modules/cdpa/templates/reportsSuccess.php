<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
            <h1><i class="fas fa-file-alt me-2"></i>CDPA Compliance Reports</h1>
            <p class="text-muted">Generate compliance documentation and reports</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                    <h5>Processing Activities Report</h5>
                    <p class="text-muted small">Record of Processing Activities (ROPA) for POTRAZ compliance.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-clock fa-3x text-info mb-3"></i>
                    <h5>Data Subject Requests Report</h5>
                    <p class="text-muted small">Summary of data subject requests and response times.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Breach Register Report</h5>
                    <p class="text-muted small">Complete register of data breaches and notifications.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
                    <h5>DPIA Summary Report</h5>
                    <p class="text-muted small">Summary of Data Protection Impact Assessments.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-warning mb-3"></i>
                    <h5>Consent Audit Report</h5>
                    <p class="text-muted small">Audit trail of consent records and withdrawals.</p>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-pie fa-3x text-secondary mb-3"></i>
                    <h5>Compliance Overview</h5>
                    <p class="text-muted small">Overall CDPA compliance status summary.</p>
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

    <!-- Quick Stats -->
    <div class="row mt-4">
        <div class="col-12">
            <h5>Current Statistics</h5>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $stats['processing_activities'] ?? 0; ?></h3>
                    <small class="text-muted">Processing Activities</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $stats['requests']['total'] ?? 0; ?></h3>
                    <small class="text-muted">Total Requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $stats['breaches']['total'] ?? 0; ?></h3>
                    <small class="text-muted">Total Breaches</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $stats['consent']['total'] ?? 0; ?></h3>
                    <small class="text-muted">Consent Records</small>
                </div>
            </div>
        </div>
    </div>
</div>
