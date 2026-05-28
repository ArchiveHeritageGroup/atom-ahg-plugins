<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Library Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'catalogue']); ?>"><i class="fas fa-book me-2"></i><?php echo __('Catalogue'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'creators']); ?>"><i class="fas fa-user-edit me-2"></i><?php echo __('Creators'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'subjects']); ?>"><i class="fas fa-tags me-2"></i><?php echo __('Subjects'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'publishers']); ?>"><i class="fas fa-building me-2"></i><?php echo __('Publishers'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'callNumbers']); ?>"><i class="fas fa-sort-alpha-down me-2"></i><?php echo __('Call Numbers'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'index']); ?>"><i class="fas fa-cloud-download-alt me-2"></i><?php echo __('KBART Vendors'); ?></a></li>
    </ul>
    <hr>
    <h5 class="text-primary"><i class="fas fa-table me-2"></i><?php echo __('COUNTER / SUSHI'); ?></h5>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'counter']); ?>"><i class="fas fa-chart-line me-2"></i><?php echo __('COUNTER Reports'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'sushiSettings']); ?>"><i class="fas fa-cloud me-2"></i><?php echo __('SUSHI Settings'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'frbrOverride']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('FRBR Overrides'); ?></a></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Library'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-book-reader"></i> <?php echo __('Library Reports Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="library-reports-dashboard">
    <!-- Items Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['items']['total']); ?></h2>
                    <p class="mb-0"><?php echo __('Total Items'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['items']['available']); ?></h2>
                    <p class="mb-0"><?php echo __('Available'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2><?php echo number_format($stats['items']['onLoan']); ?></h2>
                    <p class="mb-0"><?php echo __('On Loan'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['items']['reference']); ?></h2>
                    <p class="mb-0"><?php echo __('Reference'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- By Material Type -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('By Material Type'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['byType'] as $type): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo ucfirst(str_replace('_', ' ', $type->material_type)); ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $type->count; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Quick Stats'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-edit me-2 text-muted"></i><?php echo __('Unique Creators'); ?></span>
                        <span class="badge bg-success rounded-pill"><?php echo $stats['creators']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-tags me-2 text-muted"></i><?php echo __('Unique Subjects'); ?></span>
                        <span class="badge bg-info rounded-pill"><?php echo $stats['subjects']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-plus-circle me-2 text-muted"></i><?php echo __('Added (30 days)'); ?></span>
                        <span class="badge bg-warning rounded-pill"><?php echo $stats['recentlyAdded']; ?></span>
                    </li>
                </ul>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'catalogue']); ?>" class="btn btn-primary btn-sm w-100"><?php echo __('View Full Catalogue'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- COUNTER Reports -->
        <div class="col-md-4">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('COUNTER Reports'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                    <p class="text-muted small"><?php echo __('Generate SUSHI-compliant usage reports (TR_J1, TR_J3, DR, PR, IR) for COUNTER R5 compliance.'); ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'counter']); ?>" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-cog me-1"></i><?php echo __('Run Reports'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- SUSHI Settings -->
        <div class="col-md-4">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-cloud me-2"></i><?php echo __('SUSHI Endpoint'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-server fa-3x text-info mb-3"></i>
                    <p class="text-muted small"><?php echo __('Configure SUSHI 5.0 harvest credentials and view access log for automated COUNTER report retrieval.'); ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'sushiSettings']); ?>" class="btn btn-info btn-sm w-100">
                        <i class="fas fa-cog me-1"></i><?php echo __('Configure'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- FRBR Overrides -->
        <div class="col-md-4">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('FRBR Overrides'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-sitemap fa-3x text-warning mb-3"></i>
                    <p class="text-muted small"><?php echo __('Manage FRBR work-key force-group and force-split overrides. View work-key coverage statistics.'); ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'frbrOverride']); ?>" class="btn btn-warning btn-sm w-100">
                        <i class="fas fa-cog me-1"></i><?php echo __('Manage Overrides'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>