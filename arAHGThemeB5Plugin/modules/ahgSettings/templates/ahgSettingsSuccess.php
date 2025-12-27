<?php slot('title', __('AHG Plugin Settings')); ?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="mb-0"><i class="fas fa-cogs"></i> AHG Plugin Settings</h1>
    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'global']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Admin Settings') ?>
    </a>
</div>
<p class="text-muted mb-4">Configure AHG theme and plugin settings</p>

<div class="row">
    <?php foreach ($sections as $key => $section): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    <i class="fas <?php echo $section['icon']; ?> fa-3x text-primary"></i>
                </div>
                <h5 class="card-title"><?php echo __($section['label']); ?></h5>
                <p class="card-text text-muted small"><?php echo __($section['description']); ?></p>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="/index.php/<?php echo $section['url']; ?>" class="btn btn-primary">
                    <i class="fas fa-cog"></i> <?php echo __('Configure'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- DAM Tools Card -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-info">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-photo-video fa-3x text-info"></i>
                </div>
                <h5 class="card-title"><?php echo __('Digital Asset Management'); ?></h5>
                <p class="card-text text-muted small"><?php echo __('PDF merge, digital objects, 3D viewer, and media tools'); ?></p>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'damTools']); ?>" class="btn btn-info">
                    <i class="fas fa-tools"></i> <?php echo __('Open Tools'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access - TIFF to PDF Merge -->
<div class="card mt-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Quick Access: TIFF to PDF Merge</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-0">
                    <strong>Create multi-page PDF documents from images</strong><br>
                    <small class="text-muted">Upload multiple TIFF, JPEG, or PNG files and merge them into a single PDF/A archival document. 
                    Jobs run in the background and can be attached directly to archival records.</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-file-pdf me-1"></i> Create PDF
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
// Show stats if available
try {
    require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
    require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/TiffPdfMergeRepository.php';
    $tiffRepo = new AtomFramework\Repositories\TiffPdfMergeRepository();
    $tiffStats = $tiffRepo->getStatistics();
    
    if ($tiffStats['total_jobs'] > 0): ?>
<div class="card mt-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>PDF Merge Statistics</h6>
    </div>
    <div class="card-body py-3">
        <div class="row text-center">
            <div class="col"><span class="fs-4 fw-bold text-primary"><?php echo $tiffStats['total_jobs']; ?></span><br><small class="text-muted">Total</small></div>
            <div class="col"><span class="fs-4 fw-bold text-success"><?php echo $tiffStats['completed']; ?></span><br><small class="text-muted">Completed</small></div>
            <div class="col"><span class="fs-4 fw-bold text-warning"><?php echo $tiffStats['pending']; ?></span><br><small class="text-muted">Pending</small></div>
            <div class="col"><span class="fs-4 fw-bold text-info"><?php echo $tiffStats['processing']; ?></span><br><small class="text-muted">Processing</small></div>
            <div class="col"><span class="fs-4 fw-bold text-danger"><?php echo $tiffStats['failed']; ?></span><br><small class="text-muted">Failed</small></div>
        </div>
    </div>
</div>
<?php endif;
} catch (Exception $e) { /* Silently fail if not set up yet */ }
?>

<style>
.card { transition: transform 0.2s, box-shadow 0.2s; }
.card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
</style>
