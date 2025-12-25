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
    <h4><?php echo __('Summary'); ?></h4>
    <ul class="list-unstyled">
        <li><strong><?php echo $summary['total']; ?></strong> <?php echo __('3D files'); ?></li>
        <li><strong><?php echo formatBytes($summary['totalSize']); ?></strong> <?php echo __('total size'); ?></li>
    </ul>
    <hr>
    <p><i class="fas fa-check text-success me-2"></i><?php echo __('With Model Config:'); ?> <?php echo $summary['withModel']; ?></p>
    <p><i class="fas fa-times text-warning me-2"></i><?php echo __('Without Config:'); ?> <?php echo $summary['withoutModel']; ?></p>
    
    <?php if ($summary['withoutModel'] > 0): ?>
    <hr>
    <form method="post" action="<?php echo url_for(['module' => 'threeDReports', 'action' => 'bulkCreateConfig']); ?>">
        <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Create configs for all <?php echo $summary['withoutModel']; ?> unconfigured 3D files?');">
            <i class="fas fa-magic me-2"></i><?php echo __('Create All Configs'); ?>
        </button>
    </form>
    <?php endif; ?>
    
    <hr>
    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-file"></i> <?php echo __('3D Digital Objects'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('File'); ?></th>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('MIME Type'); ?></th>
                <th><?php echo __('Size'); ?></th>
                <th><?php echo __('Model Config'); ?></th>
                <th><?php echo __('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($objects as $o): ?>
            <tr>
                <td><code><?php echo esc_specialchars($o->name); ?></code></td>
                <td>
                    <?php if ($o->slug): ?>
                    <a href="/<?php echo $o->slug; ?>"><?php echo esc_specialchars($o->title ?? 'Untitled'); ?></a>
                    <?php else: ?>
                    <?php echo esc_specialchars($o->title ?? '-'); ?>
                    <?php endif; ?>
                </td>
                <td><small><?php echo esc_specialchars($o->mime_type ?? '-'); ?></small></td>
                <td><?php echo formatBytes($o->byte_size ?? 0); ?></td>
                <td><?php echo $o->model_id ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>'; ?></td>
                <td>
                    <?php if (!$o->model_id): ?>
                    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'createConfig', 'do_id' => $o->id, 'object_id' => $o->object_id]); ?>" 
                       class="btn btn-sm btn-outline-success" title="<?php echo __('Create Config'); ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'settings']); ?>" 
                       class="btn btn-sm btn-outline-info" title="<?php echo __('View Settings'); ?>">
                        <i class="fas fa-cog"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($o->slug): ?>
                    <a href="/<?php echo $o->slug; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View Object'); ?>">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
