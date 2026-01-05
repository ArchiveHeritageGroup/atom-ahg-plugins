<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('GRAP 103 Compliance Dashboard') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3">
                <i class="fas fa-balance-scale me-2"></i><?php echo __('GRAP 103 Compliance Dashboard') ?>
            </h1>
            <p class="text-muted"><?php echo __('South African heritage asset compliance monitoring') ?></p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50"><?php echo __('GRAP Assets') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50"><?php echo __('Compliant') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($complianceSummary['compliant']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="text-dark-50"><?php echo __('Partial') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($complianceSummary['partially_compliant']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50"><?php echo __('Non-Compliant') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($complianceSummary['non_compliant']) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Quick Actions') ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'batchCheck']) ?>" class="btn btn-outline-primary">
                            <i class="fas fa-check-double me-2"></i><?php echo __('Run Batch Compliance Check') ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'nationalTreasuryReport']) ?>" class="btn btn-outline-success">
                            <i class="fas fa-file-alt me-2"></i><?php echo __('National Treasury Report') ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'browse', 'standard_id' => 1]) ?>" class="btn btn-outline-info">
                            <i class="fas fa-list me-2"></i><?php echo __('Browse GRAP Assets') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Overview -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Compliance Overview') ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($complianceSummary['total_assets'] > 0): ?>
                        <?php 
                        $total = $complianceSummary['total_assets'];
                        $compliantPct = round(($complianceSummary['compliant'] / $total) * 100);
                        $partialPct = round(($complianceSummary['partially_compliant'] / $total) * 100);
                        $nonPct = round(($complianceSummary['non_compliant'] / $total) * 100);
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-success"><?php echo __('Compliant') ?></span>
                                <span><?php echo $compliantPct ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $compliantPct ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-warning"><?php echo __('Partially Compliant') ?></span>
                                <span><?php echo $partialPct ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo $partialPct ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-danger"><?php echo __('Non-Compliant') ?></span>
                                <span><?php echo $nonPct ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo $nonPct ?>%"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center"><?php echo __('No GRAP assets recorded yet') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- GRAP References -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i><?php echo __('GRAP 103 References') ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Recognition (103.14-25)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Measurement (103.26-51)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Depreciation (103.52-60)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Impairment (103.61-67)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Derecognition (103.68-73)</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Disclosure (103.74-82)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Assets -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Recent GRAP Assets') ?></h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recentAssets)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php echo __('Identifier') ?></th>
                                        <th><?php echo __('Title') ?></th>
                                        <th><?php echo __('Class') ?></th>
                                        <th><?php echo __('Status') ?></th>
                                        <th class="text-end"><?php echo __('Carrying Amount') ?></th>
                                        <th class="text-center"><?php echo __('Compliance') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAssets as $asset): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>">
                                                    <?php echo esc_entities($asset->object_identifier ?: 'N/A') ?>
                                                </a>
                                            </td>
                                            <td><?php echo esc_entities($asset->object_title ?: '-') ?></td>
                                            <td><?php echo esc_entities($asset->class_name ?: '-') ?></td>
                                            <td>
                                                <?php
                                                $statusColors = ['recognised' => 'success', 'not_recognised' => 'secondary', 'pending' => 'warning', 'derecognised' => 'danger'];
                                                $color = $statusColors[$asset->recognition_status] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color ?>"><?php echo ucfirst(str_replace('_', ' ', $asset->recognition_status)) ?></span>
                                            </td>
                                            <td class="text-end"><?php echo number_format($asset->current_carrying_amount, 2) ?></td>
                                            <td class="text-center">
                                                <a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'check', 'id' => $asset->id]) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                            <p class="text-muted"><?php echo __('No GRAP 103 assets recorded yet.') ?></p>
                            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'add']) ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i><?php echo __('Add First Asset') ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Heritage Accounting') ?>
        </a>
    </div>
</div>
