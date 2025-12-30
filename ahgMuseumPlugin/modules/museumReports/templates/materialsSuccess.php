<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-layer-group"></i> <?php echo __('Materials & Techniques Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Materials'); ?></th>
                <th><?php echo __('Techniques'); ?></th>
                <th><?php echo __('Dimensions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($r->title ?? 'Untitled'); ?></strong></td>
                <td><small><?php echo esc_specialchars($r->materials ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars($r->techniques ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars($r->dimensions ?? $r->measurements ?? '-'); ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
