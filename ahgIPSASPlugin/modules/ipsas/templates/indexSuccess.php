<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-coins me-2"></i>Heritage Asset Management</h1>
            <p class="text-muted">IPSAS-Compliant Heritage Asset Accounting</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'reports']); ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-alt me-1"></i> Reports
            </a>
            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'config']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Settings
            </a>
        </div>
    </div>

    <!-- Compliance Status -->
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
                <h5 class="mb-1">IPSAS Compliance: <?php echo ucfirst($compliance['status']); ?></h5>
                <?php if (!empty($compliance['issues'])): ?>
                    <p class="mb-0"><?php echo count($compliance['issues']); ?> issue(s) require attention</p>
                <?php elseif (!empty($compliance['warnings'])): ?>
                    <p class="mb-0"><?php echo count($compliance['warnings']); ?> warning(s) to review</p>
                <?php else: ?>
                    <p class="mb-0">All IPSAS requirements met</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3><?php echo number_format($stats['assets']['total']); ?></h3>
                    <p class="text-muted mb-0">Total Assets</p>
                    <small class="text-muted"><?php echo $stats['assets']['active']; ?> active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3>$<?php echo number_format($stats['values']['total'], 0); ?></h3>
                    <p class="text-muted mb-0">Total Value</p>
                    <small class="text-muted"><?php echo $config['default_currency'] ?? 'USD'; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3>$<?php echo number_format($stats['values']['insured'], 0); ?></h3>
                    <p class="text-muted mb-0">Insured Value</p>
                    <small class="text-muted">Total insurance coverage</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['insurance']['expiring_soon'] > 0 ? 'border-warning' : ''; ?>">
                <div class="card-body text-center">
                    <h3><?php echo $stats['insurance']['expiring_soon']; ?></h3>
                    <p class="text-muted mb-0">Insurance Expiring</p>
                    <small class="text-warning">Within 30 days</small>
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
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-archive me-2"></i> Asset Register
                        <span class="badge bg-primary float-end"><?php echo $stats['assets']['total']; ?></span>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetCreate']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Add New Asset
                    </a>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'valuations']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calculator me-2"></i> Valuations
                        <span class="badge bg-info float-end"><?php echo $stats['recent_valuations']; ?> this year</span>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'impairments']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Impairment Reviews
                    </a>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'insurance']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-shield-alt me-2"></i> Insurance Policies
                        <?php if ($stats['insurance']['expiring_soon'] > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?php echo $stats['insurance']['expiring_soon']; ?> expiring</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'financialYear']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> Financial Year Summary
                    </a>
                </div>
            </div>
        </div>

        <!-- Valuation Basis Breakdown -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Valuation Basis</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['valuation_basis'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($stats['valuation_basis'] as $basis => $count): ?>
                                <?php
                                $basisColors = [
                                    'historical_cost' => 'primary',
                                    'fair_value' => 'success',
                                    'nominal' => 'warning',
                                    'not_recognized' => 'secondary',
                                ];
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo ucfirst(str_replace('_', ' ', $basis)); ?>
                                    <span class="badge bg-<?php echo $basisColors[$basis] ?? 'secondary'; ?>"><?php echo $count; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center">No assets registered</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>By Category</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['categories']) && $stats['categories']->count() > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($stats['categories'] as $cat): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($cat->name); ?></span>
                                    <span>
                                        <span class="badge bg-secondary"><?php echo $cat->count; ?></span>
                                        <small class="text-muted">$<?php echo number_format($cat->value, 0); ?></small>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center">No categorized assets</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Assets & Expiring Insurance -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recently Added</h5>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetCreate']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recentAssets->isEmpty()): ?>
                        <div class="p-3 text-center text-muted">No assets registered yet</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentAssets as $asset): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $asset->id]); ?>">
                                                <?php echo htmlspecialchars($asset->asset_number); ?>
                                            </a>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($asset->title, 0, 40)); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-secondary">
                                                $<?php echo number_format($asset->current_value ?? 0, 0); ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Expiring Insurance</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($expiringInsurance->isEmpty()): ?>
                        <div class="p-3 text-center text-success">
                            <i class="fas fa-check-circle"></i> No policies expiring soon
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($expiringInsurance as $policy): ?>
                                <?php $daysLeft = floor((strtotime($policy->coverage_end) - time()) / 86400); ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($policy->policy_number); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($policy->insurer); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $daysLeft < 7 ? 'danger' : 'warning'; ?>">
                                                <?php echo $daysLeft; ?> days
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Compliance Issues -->
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

    <!-- IPSAS Reference -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-book me-2"></i>IPSAS Reference</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>IPSAS 17:</strong>
                            <p class="mb-0 small text-muted">Property, Plant & Equipment - heritage asset guidance</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Valuation Policy:</strong>
                            <p class="mb-0 small text-muted">Fair value revaluations every <?php echo $config['valuation_frequency_years'] ?? 5; ?> years</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Current Standard:</strong>
                            <p class="mb-0 small text-muted"><?php echo $config['accounting_standard'] ?? 'IPSAS'; ?> - <?php echo $config['organization_name'] ?? 'Not configured'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
