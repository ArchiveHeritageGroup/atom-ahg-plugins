<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <ul class="list-unstyled">
        <li><i class="fas fa-check text-success me-2"></i><?php echo __('With Thumbnails:'); ?> <?php echo $summary['withThumbnails']; ?></li>
        <li><i class="fas fa-times text-danger me-2"></i><?php echo __('Without:'); ?> <?php echo $summary['withoutThumbnails']; ?></li>
        <li><i class="fas fa-image text-info me-2"></i><?php echo __('With Posters:'); ?> <?php echo $summary['withPosters']; ?></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-image"></i> <?php echo __('3D Thumbnail Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php if (!empty($withoutThumbnails)): ?>
<div class="card mb-4 border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Missing Thumbnails'); ?> (<?php echo count($withoutThumbnails); ?>)</h5>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($withoutThumbnails as $m): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
                <code><?php echo esc_specialchars($m->filename); ?></code>
                <?php if ($m->title): ?><br><small class="text-muted"><?php echo esc_specialchars($m->title); ?></small><?php endif; ?>
            </span>
            <?php if ($m->slug): ?>
            <a href="/<?php echo $m->slug; ?>" class="btn btn-sm btn-outline-primary">View</a>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($withThumbnails)): ?>
<div class="card border-success">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check me-2"></i><?php echo __('With Thumbnails'); ?> (<?php echo count($withThumbnails); ?>)</h5>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($withThumbnails as $m): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
                <code><?php echo esc_specialchars($m->filename); ?></code>
                <?php if ($m->title): ?><br><small class="text-muted"><?php echo esc_specialchars($m->title); ?></small><?php endif; ?>
            </span>
            <div>
                <?php if ($m->thumbnail): ?>
                <img src="<?php echo $m->thumbnail; ?>" alt="Thumbnail" style="max-height:40px;" class="me-2">
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php end_slot(); ?>
