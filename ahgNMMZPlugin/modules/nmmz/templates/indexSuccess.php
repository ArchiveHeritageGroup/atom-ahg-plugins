<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-monument me-2"></i>NMMZ Compliance Dashboard</h1>
            <p class="text-muted">National Museums and Monuments of Zimbabwe Act [Chapter 25:11]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'reports']); ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-alt me-1"></i> Reports
            </a>
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'config']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Settings
            </a>
        </div>
    </div>

    <!-- Compliance Status -->
    <?php
    $statusColors = ['compliant' => 'success', 'warning' => 'warning', 'non_compliant' => 'danger'];
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
                    <p class="mb-0"><?php echo count($compliance['warnings']); ?> warning(s)</p>
                <?php else: ?>
                    <p class="mb-0">Heritage protection requirements met</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3><?php echo $stats['monuments']['total']; ?></h3>
                    <p class="text-muted mb-0">National Monuments</p>
                    <small class="text-success"><?php echo $stats['monuments']['gazetted']; ?> gazetted</small>
                    <?php if ($stats['monuments']['at_risk'] > 0): ?>
                        <small class="text-danger ms-2"><?php echo $stats['monuments']['at_risk']; ?> at risk</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo $stats['antiquities']['total']; ?></h3>
                    <p class="text-muted mb-0">Antiquities</p>
                    <small class="text-muted"><?php echo $stats['antiquities']['in_collection']; ?> in collection</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['permits']['pending'] > 0 ? 'border-warning' : ''; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['permits']['pending']; ?></h3>
                    <p class="text-muted mb-0">Pending Permits</p>
                    <small class="text-muted"><?php echo $stats['permits']['this_year']; ?> this year</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo $stats['sites']['total']; ?></h3>
                    <p class="text-muted mb-0">Archaeological Sites</p>
                    <?php if ($stats['sites']['at_risk'] > 0): ?>
                        <small class="text-danger"><?php echo $stats['sites']['at_risk']; ?> at risk</small>
                    <?php endif; ?>
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
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monuments']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-monument me-2"></i> National Monuments
                        <span class="badge bg-primary float-end"><?php echo $stats['monuments']['total']; ?></span>
                    </a>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquities']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-vase me-2"></i> Antiquities Register
                        <span class="badge bg-secondary float-end"><?php echo $stats['antiquities']['total']; ?></span>
                    </a>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permits']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-export me-2"></i> Export Permits
                        <?php if ($stats['permits']['pending'] > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?php echo $stats['permits']['pending']; ?> pending</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'sites']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-map-marker-alt me-2"></i> Archaeological Sites
                    </a>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'hia']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-check me-2"></i> Heritage Impact Assessments
                        <?php if ($stats['hia']['pending'] > 0): ?>
                            <span class="badge bg-info float-end"><?php echo $stats['hia']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Monuments -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-monument me-2"></i>Recent Monuments</h5>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monumentCreate']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recentMonuments->isEmpty()): ?>
                        <div class="p-3 text-center text-muted">No monuments registered</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentMonuments as $m): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monumentView', 'id' => $m->id]); ?>">
                                        <?php echo htmlspecialchars($m->monument_number); ?>
                                    </a>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($m->name, 0, 40)); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pending Permits -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-export me-2"></i>Pending Permits</h5>
                    <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitCreate']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($pendingPermits->isEmpty()): ?>
                        <div class="p-3 text-center text-success">
                            <i class="fas fa-check-circle"></i> No pending permits
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pendingPermits as $p): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permitView', 'id' => $p->id]); ?>">
                                                <?php echo htmlspecialchars($p->permit_number); ?>
                                            </a>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($p->applicant_name); ?></small>
                                        </div>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Issues & Warnings -->
    <?php if (!empty($compliance['issues']) || !empty($compliance['warnings'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Compliance Issues</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($compliance['issues'])): ?>
                            <h6 class="text-danger">Issues (Require Action)</h6>
                            <ul class="mb-3">
                                <?php foreach ($compliance['issues'] as $issue): ?>
                                    <li><?php echo htmlspecialchars($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($compliance['warnings'])): ?>
                            <h6 class="text-warning">Warnings</h6>
                            <ul class="mb-0">
                                <?php foreach ($compliance['warnings'] as $warning): ?>
                                    <li><?php echo htmlspecialchars($warning); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- World Heritage -->
    <?php if ($stats['monuments']['world_heritage'] > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-globe me-2"></i>UNESCO World Heritage Sites: <?php echo $stats['monuments']['world_heritage']; ?></h6>
                        <p class="mb-0 small text-muted">Zimbabwe has 5 inscribed World Heritage Sites including Great Zimbabwe, Khami Ruins, and Mana Pools.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
