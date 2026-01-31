<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-shield-alt me-2"></i>CDPA Compliance Dashboard</h1>
            <p class="text-muted">Zimbabwe Cyber and Data Protection Act [Chapter 12:07] - POTRAZ Regulated</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'reports']); ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-alt me-1"></i> Reports
            </a>
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'config']); ?>" class="btn btn-outline-secondary">
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
            <div class="card <?php echo $stats['license'] ? 'border-success' : 'border-danger'; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['license'] ? $stats['license_days_remaining'] : '-'; ?></h3>
                    <p class="text-muted mb-0">License Days Remaining</p>
                    <small class="text-<?php echo $stats['license'] ? 'success' : 'danger'; ?>">
                        <?php echo $stats['license'] ? $stats['license_status'] : 'Not Registered'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['requests']['overdue'] > 0 ? 'border-danger' : ''; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['requests']['pending']; ?></h3>
                    <p class="text-muted mb-0">Pending Requests</p>
                    <?php if ($stats['requests']['overdue'] > 0): ?>
                        <small class="text-danger"><?php echo $stats['requests']['overdue']; ?> overdue</small>
                    <?php else: ?>
                        <small class="text-success">None overdue</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['breaches']['open'] > 0 ? 'border-warning' : ''; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['breaches']['open']; ?></h3>
                    <p class="text-muted mb-0">Open Breaches</p>
                    <small class="text-muted"><?php echo $stats['breaches']['this_year']; ?> this year</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo $stats['processing_activities']; ?></h3>
                    <p class="text-muted mb-0">Processing Activities</p>
                    <small class="text-muted"><?php echo $stats['consent']['active']; ?> active consents</small>
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
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'license']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card me-2"></i> POTRAZ License
                        <?php if (!$stats['license']): ?>
                            <span class="badge bg-danger float-end">Required</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpo']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-shield me-2"></i> Data Protection Officer
                        <?php if (!$stats['dpo']): ?>
                            <span class="badge bg-danger float-end">Required</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-clock me-2"></i> Data Subject Requests
                        <?php if ($stats['requests']['pending'] > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?php echo $stats['requests']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'processing']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-cogs me-2"></i> Processing Register
                    </a>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpia']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-check me-2"></i> DPIA
                        <?php if ($stats['dpia']['pending'] > 0): ?>
                            <span class="badge bg-info float-end"><?php echo $stats['dpia']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'consent']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-check-circle me-2"></i> Consent Management
                    </a>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-exclamation-triangle me-2"></i> Breach Register
                        <?php if ($stats['breaches']['open'] > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $stats['breaches']['open']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Requests -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>Pending Requests</h5>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requestCreate']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($pendingRequests->isEmpty()): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                            <p class="mb-0">No pending requests</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pendingRequests as $req): ?>
                                <?php $isOverdue = strtotime($req->due_date) < time(); ?>
                                <li class="list-group-item <?php echo $isOverdue ? 'list-group-item-danger' : ''; ?>">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($req->data_subject_name); ?></strong>
                                            <br><small class="text-muted"><?php echo ucfirst($req->request_type); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $isOverdue ? 'danger' : 'warning'; ?>">
                                                <?php echo $isOverdue ? 'OVERDUE' : 'Due: ' . $req->due_date; ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php if (!$pendingRequests->isEmpty()): ?>
                    <div class="card-footer text-center">
                        <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests', 'status' => 'pending']); ?>">View All Pending</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Issues & Warnings -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Issues & Warnings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($compliance['issues']) && empty($compliance['warnings'])): ?>
                        <div class="text-center text-success">
                            <i class="fas fa-check-circle fa-3x mb-2"></i>
                            <p>All compliance requirements met!</p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($compliance['issues'])): ?>
                            <h6 class="text-danger"><i class="fas fa-times-circle me-1"></i>Issues</h6>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- License & DPO Info -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>POTRAZ License</h5>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'licenseEdit']); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($stats['license']): ?>
                        <table class="table table-sm mb-0">
                            <tr><th>License Number</th><td><?php echo htmlspecialchars($stats['license']->license_number); ?></td></tr>
                            <tr><th>Tier</th><td><span class="badge bg-info"><?php echo strtoupper($stats['license']->tier); ?></span></td></tr>
                            <tr><th>Organization</th><td><?php echo htmlspecialchars($stats['license']->organization_name); ?></td></tr>
                            <tr><th>Expiry Date</th><td><?php echo $stats['license']->expiry_date; ?></td></tr>
                            <tr><th>Status</th><td>
                                <span class="badge bg-<?php echo 'active' === $stats['license_status'] ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($stats['license_status']); ?>
                                </span>
                            </td></tr>
                        </table>
                    <?php else: ?>
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p>No POTRAZ license registered</p>
                            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'licenseEdit']); ?>" class="btn btn-danger">Register License</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Data Protection Officer</h5>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpoEdit']); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($stats['dpo']): ?>
                        <table class="table table-sm mb-0">
                            <tr><th>Name</th><td><?php echo htmlspecialchars($stats['dpo']->name); ?></td></tr>
                            <tr><th>Email</th><td><?php echo htmlspecialchars($stats['dpo']->email); ?></td></tr>
                            <tr><th>Phone</th><td><?php echo htmlspecialchars($stats['dpo']->phone ?? '-'); ?></td></tr>
                            <tr><th>Appointed</th><td><?php echo $stats['dpo']->appointment_date; ?></td></tr>
                            <tr><th>Form DP2</th><td>
                                <span class="badge bg-<?php echo $stats['dpo']->form_dp2_submitted ? 'success' : 'warning'; ?>">
                                    <?php echo $stats['dpo']->form_dp2_submitted ? 'Submitted' : 'Not Submitted'; ?>
                                </span>
                            </td></tr>
                        </table>
                    <?php else: ?>
                        <div class="text-center text-danger">
                            <i class="fas fa-user-slash fa-2x mb-2"></i>
                            <p>No DPO appointed</p>
                            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpoEdit']); ?>" class="btn btn-danger">Appoint DPO</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
