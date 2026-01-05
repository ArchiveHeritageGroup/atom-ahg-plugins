<?php slot('title') ?><?php echo __('Heritage Asset Reports') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3"><i class="fas fa-chart-bar me-2"></i><?php echo __('Heritage Asset Reports') ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i><?php echo __('Asset Register') ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo __('Complete register of all heritage assets with carrying amounts and recognition status.') ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'assetRegister']) ?>" class="btn btn-primary">
                        <?php echo __('View Report') ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i><?php echo __('Valuation Report') ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo __('Assets with valuation history, revaluation surplus, and impairment losses.') ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'valuation']) ?>" class="btn btn-success">
                        <?php echo __('View Report') ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Movement Report') ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo __('Track loans, transfers, exhibitions and storage changes by date range.') ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'movement']) ?>" class="btn btn-info">
                        <?php echo __('View Report') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Standard-specific reports -->
    <h4 class="mt-4 mb-3"><?php echo __('Standard-Specific Reports') ?></h4>
    <div class="row">
        <?php foreach ($standards as $std): ?>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5><?php echo esc_entities($std->code) ?></h5>
                        <small class="text-muted"><?php echo esc_entities($std->country) ?></small>
                        <div class="mt-3">
                            <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'assetRegister', 'standard_id' => $std->id]) ?>" class="btn btn-sm btn-outline-primary">
                                <?php echo __('View') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
        </a>
    </div>
</div>
