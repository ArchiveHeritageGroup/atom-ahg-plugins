<?php decorate_with('layout_2col'); ?>
<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('3D Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'models']); ?>"><i class="fas fa-cube me-2"></i><?php echo __('3D Models'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'hotspots']); ?>"><i class="fas fa-map-pin me-2"></i><?php echo __('Hotspots'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'thumbnails']); ?>"><i class="fas fa-image me-2"></i><?php echo __('Thumbnails'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'digitalObjects']); ?>"><i class="fas fa-file me-2"></i><?php echo __('3D Files'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'settings']); ?>"><i class="fas fa-cog me-2"></i><?php echo __('Viewer Settings'); ?></a></li>
    </ul>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-cube"></i> <?php echo __('3D Object Reports Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="threeDReports-dashboard">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['totalModels']); ?></h2>
                    <p class="mb-0"><?php echo __('3D Models'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['digitalObjects3D']); ?></h2>
                    <p class="mb-0"><?php echo __('3D Files'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['totalHotspots']); ?></h2>
                    <p class="mb-0"><?php echo __('Hotspots'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2><?php echo formatBytes($stats['totalSize']); ?></h2>
                    <p class="mb-0"><?php echo __('Total Size'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('By Format'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($stats['byFormat'])): ?>
                    <li class="list-group-item text-muted"><?php echo __('No models yet'); ?></li>
                    <?php else: ?>
                    <?php foreach ($stats['byFormat'] as $f): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><code>.<?php echo strtoupper($f->format); ?></code></span>
                        <span class="badge bg-primary"><?php echo $f->count; ?></span>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Coverage'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-image me-2 text-success"></i><?php echo __('With Thumbnails'); ?></span>
                        <span class="badge bg-success"><?php echo $stats['withThumbnails']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-photo-video me-2 text-info"></i><?php echo __('With Posters'); ?></span>
                        <span class="badge bg-info"><?php echo $stats['withPosters']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-mobile-alt me-2 text-warning"></i><?php echo __('AR Enabled'); ?></span>
                        <span class="badge bg-warning"><?php echo $stats['arEnabled']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>
