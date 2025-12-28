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
    <h4><?php echo __('Storage Summary'); ?></h4>
    <div class="alert alert-primary">
        <strong><?php echo formatBytes($storage['total']); ?></strong><br>
        <small><?php echo __('Total Storage Used'); ?></small>
    </div>
    <?php if ($storage['orphaned'] > 0): ?>
    <div class="alert alert-warning">
        <strong><?php echo $storage['orphaned']; ?></strong> <?php echo __('orphaned files'); ?>
    </div>
    <?php endif; ?>
    <hr>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-hdd"></i> <?php echo __('Storage Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?php echo __('Storage by Type'); ?></h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($storage['byMimeType'] as $t): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><code><?php echo esc_specialchars($t->mime_type ?? 'unknown'); ?></code> <small class="text-muted">(<?php echo $t->count; ?>)</small></span>
                    <strong><?php echo formatBytes($t->size); ?></strong>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?php echo __('Largest Files'); ?></h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($storage['largest'] as $f): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><small><?php echo esc_specialchars(basename($f->name)); ?></small></span>
                    <strong><?php echo formatBytes($f->byte_size); ?></strong>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php end_slot(); ?>
