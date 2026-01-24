<?php
/**
 * DAM Tools Settings Page
 * Digital Asset Management including TIFF to PDF Merge
 */

$title = __('Digital Asset Management Tools');
slot('title', $title);
?>

<div class="ahg-settings-page">
    <!-- Back Link -->
    <div class="mb-3">
        <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'settings']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to AHG Settings'); ?>
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1><i class="fas fa-photo-video text-info"></i> <?php echo $title; ?></h1>
        <p class="text-muted"><?php echo __('Tools for managing digital assets, images, and documents'); ?></p>
    </div>

    <!-- Include TIFF PDF Merge Settings -->
    <?php include_partial('ahgSettings/tiffPdfMergeSettings'); ?>

    <!-- Additional DAM Tools -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-images fa-3x text-success mb-3"></i>
                    <h5>Digital Objects</h5>
                    <p class="text-muted small">Browse and manage all digital objects in the system.</p>
                    <a href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'browse']); ?>" class="btn btn-outline-success">
                        <i class="fas fa-search me-1"></i>Browse
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-3x text-info mb-3"></i>
                    <h5>Background Jobs</h5>
                    <p class="text-muted small">View status of all processing jobs.</p>
                    <a href="<?php echo url_for(['module' => 'jobs', 'action' => 'browse']); ?>" class="btn btn-outline-info">
                        <i class="fas fa-list me-1"></i>View Jobs
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-cube fa-3x text-warning mb-3"></i>
                    <h5>3D Objects</h5>
                    <p class="text-muted small">Manage 3D models and viewer settings.</p>
                    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'settings']); ?>" class="btn btn-outline-warning">
                        <i class="fas fa-cog me-1"></i>Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
