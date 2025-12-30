<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Gallery Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exhibitions']); ?>"><i class="fas fa-images me-2"></i><?php echo __('Exhibitions'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'artists']); ?>"><i class="fas fa-palette me-2"></i><?php echo __('Artists'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'loans']); ?>"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Loans'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'valuations']); ?>"><i class="fas fa-coins me-2"></i><?php echo __('Valuations'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'facilityReports']); ?>"><i class="fas fa-building me-2"></i><?php echo __('Facility Reports'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'spaces']); ?>"><i class="fas fa-th-large me-2"></i><?php echo __('Spaces'); ?></a></li>
    </ul>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-paint-brush"></i> <?php echo __('Gallery Reports Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="gallery-reports-dashboard">
    <!-- Exhibitions Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-images me-2"></i><?php echo __('Exhibitions'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h2 class="text-primary"><?php echo number_format($stats['exhibitions']['total']); ?></h2>
                            <p class="text-muted"><?php echo __('Total'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <h2 class="text-success"><?php echo number_format($stats['exhibitions']['open']); ?></h2>
                            <p class="text-muted"><?php echo __('Currently Open'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <h2 class="text-warning"><?php echo number_format($stats['exhibitions']['planning']); ?></h2>
                            <p class="text-muted"><?php echo __('In Planning'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <h2 class="text-info"><?php echo number_format($stats['exhibitions']['upcoming']); ?></h2>
                            <p class="text-muted"><?php echo __('Upcoming'); ?></p>
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exhibitions']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View Report'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Artists & Loans Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-palette me-2"></i><?php echo __('Artists'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h3><?php echo number_format($stats['artists']['total']); ?></h3>
                            <small class="text-muted"><?php echo __('Total'); ?></small>
                        </div>
                        <div class="col-4">
                            <h3><?php echo number_format($stats['artists']['represented']); ?></h3>
                            <small class="text-muted"><?php echo __('Represented'); ?></small>
                        </div>
                        <div class="col-4">
                            <h3><?php echo number_format($stats['artists']['active']); ?></h3>
                            <small class="text-muted"><?php echo __('Active'); ?></small>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'artists']); ?>" class="btn btn-sm btn-outline-success"><?php echo __('View Report'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Loans'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <h3><?php echo number_format($stats['loans']['total']); ?></h3>
                            <small class="text-muted"><?php echo __('Total'); ?></small>
                        </div>
                        <div class="col-3">
                            <h3><?php echo number_format($stats['loans']['active']); ?></h3>
                            <small class="text-muted"><?php echo __('Active'); ?></small>
                        </div>
                        <div class="col-3">
                            <h3><?php echo number_format($stats['loans']['incoming']); ?></h3>
                            <small class="text-muted"><?php echo __('Incoming'); ?></small>
                        </div>
                        <div class="col-3">
                            <h3><?php echo number_format($stats['loans']['outgoing']); ?></h3>
                            <small class="text-muted"><?php echo __('Outgoing'); ?></small>
                        </div>
                    </div>
                    <?php if ($stats['loans']['pending'] > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0 py-2">
                        <i class="fas fa-clock me-2"></i><?php echo $stats['loans']['pending']; ?> <?php echo __('pending requests'); ?>
                    </div>
                    <?php endif; ?>
                    <div class="text-end mt-3">
                        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'loans']); ?>" class="btn btn-sm btn-outline-info"><?php echo __('View Report'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Valuations Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-coins me-2"></i><?php echo __('Valuations'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h3><?php echo number_format($stats['valuations']['total']); ?></h3>
                            <small class="text-muted"><?php echo __('Total Records'); ?></small>
                        </div>
                        <div class="col-md-3">
                            <h3><?php echo number_format($stats['valuations']['current']); ?></h3>
                            <small class="text-muted"><?php echo __('Current'); ?></small>
                        </div>
                        <div class="col-md-3">
                            <h3>R <?php echo number_format($stats['valuations']['totalValue'], 2); ?></h3>
                            <small class="text-muted"><?php echo __('Total Value'); ?></small>
                        </div>
                        <div class="col-md-3">
                            <?php if ($stats['valuations']['expiringSoon'] > 0): ?>
                            <h3 class="text-danger"><?php echo number_format($stats['valuations']['expiringSoon']); ?></h3>
                            <small class="text-danger"><?php echo __('Expiring Soon'); ?></small>
                            <?php else: ?>
                            <h3 class="text-success">0</h3>
                            <small class="text-muted"><?php echo __('Expiring Soon'); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'valuations']); ?>" class="btn btn-sm btn-outline-warning"><?php echo __('View Report'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>
