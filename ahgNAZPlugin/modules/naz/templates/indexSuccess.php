<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-landmark me-2"></i>NAZ Compliance Dashboard</h1>
            <p class="text-muted">National Archives of Zimbabwe Act [Chapter 25:06] - 25-Year Rule</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'reports']); ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-alt me-1"></i> Reports
            </a>
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'config']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Settings
            </a>
        </div>
    </div>

    <!-- Compliance Status Banner -->
    <?php
    $statusColors = [
        'compliant' => 'success',
        'warning' => 'warning',
        'non_compliant' => 'danger',
    ];
    $statusColor = $statusColors[$compliance['status']] ?? 'secondary';
    ?>
    <div class="alert alert-<?php echo $statusColor; ?> mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-<?php echo 'compliant' === $compliance['status'] ? 'check-circle' : 'exclamation-triangle'; ?> fa-2x me-3"></i>
            <div>
                <h5 class="mb-1">Compliance Status: <?php echo ucfirst($compliance['status']); ?></h5>
                <?php if (!empty($compliance['issues'])): ?>
                    <p class="mb-0"><?php echo count($compliance['issues']); ?> issue(s) require attention</p>
                <?php elseif (!empty($compliance['warnings'])): ?>
                    <p class="mb-0"><?php echo count($compliance['warnings']); ?> warning(s) to review</p>
                <?php else: ?>
                    <p class="mb-0">All compliance requirements met</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3><?php echo $stats['closures']['active']; ?></h3>
                    <p class="text-muted mb-0">Active Closures</p>
                    <?php if ($stats['closures']['expiring_soon'] > 0): ?>
                        <small class="text-warning"><?php echo $stats['closures']['expiring_soon']; ?> expiring within 1 year</small>
                    <?php else: ?>
                        <small class="text-muted">25-year closure periods</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['permits']['pending'] > 0 ? 'border-warning' : ''; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['permits']['active']; ?></h3>
                    <p class="text-muted mb-0">Active Permits</p>
                    <?php if ($stats['permits']['pending'] > 0): ?>
                        <small class="text-warning"><?php echo $stats['permits']['pending']; ?> pending approval</small>
                    <?php else: ?>
                        <small class="text-muted">Research permits</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo $stats['researchers']['total']; ?></h3>
                    <p class="text-muted mb-0">Registered Researchers</p>
                    <small class="text-muted"><?php echo $stats['researchers']['local']; ?> local, <?php echo $stats['researchers']['foreign']; ?> foreign</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['transfers']['pending'] > 0 ? 'border-info' : ''; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['transfers']['pending']; ?></h3>
                    <p class="text-muted mb-0">Pending Transfers</p>
                    <small class="text-muted"><?php echo $stats['transfers']['this_year']; ?> accessioned this year</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Links -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Actions</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-lock me-2"></i> Closure Periods
                        <?php if ($stats['closures']['expiring_soon'] > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?php echo $stats['closures']['expiring_soon']; ?> expiring</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card me-2"></i> Research Permits
                        <?php if ($stats['permits']['pending'] > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?php echo $stats['permits']['pending']; ?> pending</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researchers']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Researcher Registry
                    </a>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'schedules']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> Records Schedules
                        <span class="badge bg-secondary float-end"><?php echo $stats['schedules']; ?></span>
                    </a>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-truck me-2"></i> Records Transfers
                        <?php if ($stats['transfers']['pending'] > 0): ?>
                            <span class="badge bg-info float-end"><?php echo $stats['transfers']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'protectedRecords']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-shield-alt me-2"></i> Protected Records
                        <?php if ($stats['protected'] > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $stats['protected']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Permits -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Pending Permits</h5>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitCreate']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($pendingPermits->isEmpty()): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                            <p class="mb-0">No pending permit applications</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pendingPermits as $permit): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($permit->first_name.' '.$permit->last_name); ?></strong>
                                            <br><small class="text-muted">
                                                <?php echo ucfirst($permit->researcher_type); ?> |
                                                <?php echo $permit->permit_number; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permitView', 'id' => $permit->id]); ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                Review
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php if (!$pendingPermits->isEmpty()): ?>
                    <div class="card-footer text-center">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits', 'status' => 'pending']); ?>">View All Pending</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expiring Closures -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Expiring Closures</h5>
                    <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closureCreate']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($expiringClosures->isEmpty()): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-lock fa-2x mb-2"></i>
                            <p class="mb-0">No closures expiring within 1 year</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($expiringClosures as $closure): ?>
                                <?php
                                $daysLeft = floor((strtotime($closure->end_date) - time()) / 86400);
                                $urgency = $daysLeft < 90 ? 'danger' : ($daysLeft < 180 ? 'warning' : 'info');
                                ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($closure->record_title ?? 'Record #'.$closure->information_object_id); ?></strong>
                                            <br><small class="text-muted"><?php echo ucfirst($closure->closure_type); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $urgency; ?>">
                                            <?php echo $daysLeft; ?> days
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php if (!$expiringClosures->isEmpty()): ?>
                    <div class="card-footer text-center">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures']); ?>">View All Closures</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Issues & Warnings -->
    <?php if (!empty($compliance['issues']) || !empty($compliance['warnings'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Compliance Issues & Warnings</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($compliance['issues'])): ?>
                            <h6 class="text-danger"><i class="fas fa-times-circle me-1"></i>Issues (Require Immediate Action)</h6>
                            <ul class="list-unstyled mb-3">
                                <?php foreach ($compliance['issues'] as $issue): ?>
                                    <li class="mb-1"><i class="fas fa-exclamation-circle text-danger me-1"></i> <?php echo htmlspecialchars($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($compliance['warnings'])): ?>
                            <h6 class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Warnings</h6>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($compliance['warnings'] as $warning): ?>
                                    <li class="mb-1"><i class="fas fa-exclamation-triangle text-warning me-1"></i> <?php echo htmlspecialchars($warning); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Key Legislation Reference -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-gavel me-2"></i>Key Legislation Reference</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Section 10 - Closure Period:</strong>
                            <p class="mb-0 small text-muted">Records closed for <?php echo $config['closure_period_years'] ?? 25; ?> years from date of creation</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Research Permit Fees:</strong>
                            <p class="mb-0 small text-muted">Foreign researchers: US$<?php echo $config['foreign_permit_fee_usd'] ?? 200; ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Permit Validity:</strong>
                            <p class="mb-0 small text-muted"><?php echo $config['permit_validity_months'] ?? 12; ?> months from approval</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
