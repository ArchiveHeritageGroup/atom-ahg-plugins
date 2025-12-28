<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-building"></i> <?php echo __('Publishers Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th><?php echo __('Publisher'); ?></th><th><?php echo __('Place'); ?></th><th><?php echo __('Items'); ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($publishers as $p): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($p->publisher); ?></strong></td>
                <td><?php echo esc_specialchars($p->publication_place ?? '-'); ?></td>
                <td><span class="badge bg-primary"><?php echo $p->item_count; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
