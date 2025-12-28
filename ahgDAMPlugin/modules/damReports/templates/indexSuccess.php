<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('DAM Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'damReports', 'action' => 'assets']); ?>"><i class="fas fa-file me-2"></i><?php echo __('Assets'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'damReports', 'action' => 'metadata']); ?>"><i class="fas fa-info-circle me-2"></i><?php echo __('Metadata'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'damReports', 'action' => 'iptc']); ?>"><i class="fas fa-camera me-2"></i><?php echo __('IPTC Data'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'damReports', 'action' => 'storage']); ?>"><i class="fas fa-hdd me-2"></i><?php echo __('Storage'); ?></a></li>
    </ul>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-photo-video"></i> <?php echo __('Digital Asset Management Reports'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
?>
<div class="dam-reports-dashboard">
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['total']); ?></h2>
                    <p class="mb-0"><?php echo __('Total Assets'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2><?php echo formatBytes($stats['totalSize']); ?></h2>
                    <p class="mb-0"><?php echo __('Total Storage'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['withMetadata']); ?></h2>
                    <p class="mb-0"><?php echo __('With Metadata'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2><?php echo number_format($stats['recentUploads']); ?></h2>
                    <p class="mb-0"><?php echo __('Recent (30 days)'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- By MIME Type -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('By File Type'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['byMimeType'] as $type): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><code><?php echo esc_specialchars($type->mime_type ?? 'unknown'); ?></code></span>
                        <span>
                            <span class="badge bg-primary"><?php echo $type->count; ?></span>
                            <small class="text-muted ms-2"><?php echo formatBytes($type->size); ?></small>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Metadata Coverage'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-info-circle me-2 text-muted"></i><?php echo __('With Extracted Metadata'); ?></span>
                        <span class="badge bg-success"><?php echo $stats['withMetadata']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-camera me-2 text-muted"></i><?php echo __('With IPTC Data'); ?></span>
                        <span class="badge bg-info"><?php echo $stats['withIptc']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-map-marker-alt me-2 text-muted"></i><?php echo __('With GPS Coordinates'); ?></span>
                        <span class="badge bg-warning"><?php echo $stats['withGps']; ?></span>
                    </li>
                </ul>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'assets']); ?>" class="btn btn-primary btn-sm w-100"><?php echo __('View All Assets'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>
