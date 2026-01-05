<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('GRAP 103 Compliance Check') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Compliance Check') ?></h1>
                <p class="text-muted mb-0"><?php echo esc_entities($asset->object_identifier) ?></p>
            </div>
            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Asset') ?>
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="display-4 <?php echo $complianceResult['summary']['status'] == 'compliant' ? 'text-success' : ($complianceResult['summary']['status'] == 'partially_compliant' ? 'text-warning' : 'text-danger') ?>">
                        <?php echo $complianceResult['summary']['score'] ?>%
                    </h1>
                    <p class="mb-0"><?php echo __('Compliance Score') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2><?php echo $complianceResult['summary']['passed'] ?></h2>
                    <p class="mb-0"><?php echo __('Passed') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h2><?php echo $complianceResult['summary']['warnings'] ?></h2>
                    <p class="mb-0"><?php echo __('Warnings') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h2><?php echo $complianceResult['summary']['failed'] ?></h2>
                    <p class="mb-0"><?php echo __('Failed') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Results -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i><?php echo __('Compliance Checklist') ?></h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;"><?php echo __('Code') ?></th>
                        <th><?php echo __('Check') ?></th>
                        <th><?php echo __('Reference') ?></th>
                        <th><?php echo __('Category') ?></th>
                        <th class="text-center"><?php echo __('Status') ?></th>
                        <th><?php echo __('Message') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complianceResult['checks'] as $check): ?>
                        <tr>
                            <td><code><?php echo $check['code'] ?></code></td>
                            <td><strong><?php echo $check['title'] ?></strong></td>
                            <td><small class="text-muted"><?php echo $check['reference'] ?></small></td>
                            <td>
                                <?php
                                $catColors = ['recognition' => 'primary', 'measurement' => 'info', 'disclosure' => 'secondary', 'documentation' => 'dark'];
                                $catColor = $catColors[$check['category']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $catColor ?>"><?php echo ucfirst($check['category']) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($check['status'] == 'passed'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Passed</span>
                                <?php elseif ($check['status'] == 'warning'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Warning</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Failed</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo $check['message'] ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i><?php echo __('Checked at: %1%', ['%1%' => $complianceResult['checked_at']]) ?>
            </small>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'edit', 'id' => $asset->id]) ?>" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i><?php echo __('Edit Asset to Fix Issues') ?>
        </a>
        <a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to GRAP Dashboard') ?>
        </a>
    </div>
</div>
