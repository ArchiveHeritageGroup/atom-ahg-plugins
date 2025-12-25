<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-cog"></i> <?php echo __('3D Viewer Settings Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php if (empty($settings)): ?>
<div class="alert alert-info"><?php echo __('No 3D models configured yet.'); ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Model'); ?></th>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Auto Rotate'); ?></th>
                <th><?php echo __('Speed'); ?></th>
                <th><?php echo __('Camera'); ?></th>
                <th><?php echo __('FOV'); ?></th>
                <th><?php echo __('AR'); ?></th>
                <th><?php echo __('Background'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($settings as $s): ?>
            <tr>
                <td><code><?php echo esc_specialchars($s->filename); ?></code></td>
                <td><small><?php echo esc_specialchars($s->title ?? '-'); ?></small></td>
                <td><?php echo $s->auto_rotate ? '<i class="fas fa-sync text-success"></i>' : '-'; ?></td>
                <td><?php echo $s->rotation_speed; ?></td>
                <td><small><?php echo esc_specialchars($s->camera_orbit ?? '-'); ?></small></td>
                <td><?php echo esc_specialchars($s->field_of_view ?? '-'); ?></td>
                <td><?php echo $s->ar_enabled ? '<i class="fas fa-mobile-alt text-success"></i> ' . $s->ar_placement : '-'; ?></td>
                <td><span style="display:inline-block;width:20px;height:20px;background:<?php echo $s->background_color ?? '#f5f5f5'; ?>;border:1px solid #ccc;"></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
