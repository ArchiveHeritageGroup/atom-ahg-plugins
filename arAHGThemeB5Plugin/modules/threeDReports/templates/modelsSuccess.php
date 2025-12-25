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
            <select name="format" class="form-select form-select-sm">
                <option value=""><?php echo __('All Formats'); ?></option>
                <?php foreach ($formats as $f): ?>
                <option value="<?php echo $f; ?>" <?php echo ($filters['format'] ?? '') === $f ? 'selected' : ''; ?>>.<?php echo strtoupper($f); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <select name="has_thumbnail" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <option value="1" <?php echo ($filters['hasThumbnail'] ?? '') === '1' ? 'selected' : ''; ?>><?php echo __('With Thumbnail'); ?></option>
                <option value="0" <?php echo ($filters['hasThumbnail'] ?? '') === '0' ? 'selected' : ''; ?>><?php echo __('Without Thumbnail'); ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-cube"></i> <?php echo __('3D Models Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info"><strong><?php echo count($models); ?></strong> <?php echo __('models found'); ?></div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Model'); ?></th>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Format'); ?></th>
                <th><?php echo __('Size'); ?></th>
                <th><?php echo __('Thumb'); ?></th>
                <th><?php echo __('AR'); ?></th>
                <th><?php echo __('Public'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($models as $m): ?>
            <tr>
                <td><code><?php echo esc_specialchars($m->filename); ?></code></td>
                <td>
                    <?php if ($m->slug): ?>
                    <a href="/<?php echo $m->slug; ?>"><?php echo esc_specialchars($m->title ?? 'Untitled'); ?></a>
                    <?php else: ?>
                    <?php echo esc_specialchars($m->title ?? '-'); ?>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-secondary">.<?php echo strtoupper($m->format); ?></span></td>
                <td><?php echo formatBytes($m->file_size ?? 0); ?></td>
                <td><?php echo $m->thumbnail ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                <td><?php echo $m->ar_enabled ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                <td><?php echo $m->is_public ? '<i class="fas fa-globe text-success"></i>' : '<i class="fas fa-lock text-warning"></i>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
