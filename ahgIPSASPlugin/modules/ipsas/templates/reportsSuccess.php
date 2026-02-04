<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
            <h1><i class="fas fa-chart-bar me-2"></i>Financial Reports</h1>
            <p class="text-muted">IPSAS-compliant financial reporting</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                    <h5>Asset Register</h5>
                    <p class="text-muted small">Complete listing of heritage assets with valuations</p>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>?report=asset_register" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Download CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-balance-scale fa-3x text-success mb-3"></i>
                    <h5>Valuation Summary</h5>
                    <p class="text-muted small">Summary of asset valuations for <?php echo $year ?? date('Y'); ?></p>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>?report=valuation_summary&year=<?php echo $year ?? date('Y'); ?>" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-download me-1"></i> Download CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                    <h5>Impairment Report</h5>
                    <p class="text-muted small">Summary of recognized and pending impairments</p>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>?report=impairments&year=<?php echo $year ?? date('Y'); ?>" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-download me-1"></i> Download CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-3x text-info mb-3"></i>
                    <h5>Insurance Coverage</h5>
                    <p class="text-muted small">Insurance policies and coverage gaps</p>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>?report=insurance" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-download me-1"></i> Download CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                    <h5>Financial Year Summary</h5>
                    <p class="text-muted small">Year-end financial summary for heritage assets</p>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'financialYear']); ?>?year=<?php echo $year ?? date('Y'); ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-eye me-1"></i> View Summary
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-dark mb-3"></i>
                    <h5>Compliance Checklist</h5>
                    <p class="text-muted small">IPSAS compliance status and requirements</p>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>?report=compliance" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-download me-1"></i> Download CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
