<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Heritage Asset Accounting') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3">
                <i class="fas fa-landmark me-2"></i><?php echo __('Heritage Asset Accounting') ?>
            </h1>
            <p class="text-muted"><?php echo __('Multi-standard heritage asset financial accounting') ?></p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50"><?php echo __('Total Assets') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50"><?php echo __('Recognised') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($stats['recognised']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="text-dark-50"><?php echo __('Pending') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($stats['pending']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="text-white-50"><?php echo __('Total Value') ?></h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_value'], 2) ?></h2>
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
                        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'browse']) ?>" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i><?php echo __('Browse Assets') ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'add']) ?>" class="btn btn-outline-success">
                            <i class="fas fa-plus me-2"></i><?php echo __('Add Asset') ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'index']) ?>" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i><?php echo __('Reports') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- By Asset Class -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-th-large me-2"></i><?php echo __('By Asset Class') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['by_class'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($stats['by_class'] as $class): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo esc_entities($class->class_name ?: 'Unclassified') ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $class->count ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0"><?php echo __('No assets yet') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Accounting Standards -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Supported Standards') ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($standards as $standard): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo esc_entities($standard->code) ?></strong><br>
                                    <small class="text-muted"><?php echo esc_entities($standard->country) ?></small>
                                </div>
                                <?php if ($standard->capitalisation_required): ?>
                                    <span class="badge bg-success">Required</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Optional</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i><em>Required</em> = capitalisation mandatory; <em>Optional</em> = at discretion</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Assets -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Recent Assets') ?></h5>
                    <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'browse']) ?>" class="btn btn-sm btn-light">
                        <?php echo __('View All') ?>
                    </a>
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
                                        <th><?php echo __('Standard') ?></th>
                                        <th><?php echo __('Status') ?></th>
                                        <th class="text-end"><?php echo __('Carrying Amount') ?></th>
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
                                            <td><?php echo esc_entities($asset->standard_code ?: '-') ?></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'recognised' => 'success',
                                                    'not_recognised' => 'secondary',
                                                    'pending' => 'warning',
                                                    'derecognised' => 'danger'
                                                ];
                                                $color = $statusColors[$asset->recognition_status] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color ?>"><?php echo ucfirst($asset->recognition_status) ?></span>
                                            </td>
                                            <td class="text-end"><?php echo number_format($asset->current_carrying_amount, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-landmark fa-3x text-muted mb-3"></i>
                            <p class="text-muted"><?php echo __('No heritage assets recorded yet.') ?></p>
                            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'add']) ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i><?php echo __('Add First Asset') ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
