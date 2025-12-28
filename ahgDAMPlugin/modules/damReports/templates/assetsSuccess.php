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
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <div class="mb-3">
            <label class="form-label"><?php echo __('MIME Type'); ?></label>
            <select name="mime_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($mimeTypes as $m): ?>
                <option value="<?php echo $m; ?>" <?php echo ($filters['mimeType'] ?? '') === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Min Size (MB)'); ?></label>
            <input type="number" name="min_size" class="form-control form-control-sm" value="<?php echo $filters['minSize'] ?? ''; ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'exportCsv', 'report' => 'assets']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></a>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-file"></i> <?php echo __('Digital Assets Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info"><strong><?php echo count($assets); ?></strong> <?php echo __('assets found'); ?></div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Filename'); ?></th>
                <th><?php echo __('Record'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Size'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assets as $a): ?>
            <tr>
                <td><code><?php echo esc_specialchars(basename($a->name)); ?></code></td>
                <td><small><?php echo esc_specialchars($a->title ?? '-'); ?></small></td>
                <td><span class="badge bg-secondary"><?php echo esc_specialchars($a->mime_type ?? '-'); ?></span></td>
                <td class="text-end"><?php echo formatBytes($a->byte_size ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
