<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <ul class="list-unstyled">
        <li><i class="fas fa-check text-success me-2"></i><?php echo __('With Call #:'); ?> <?php echo $summary['withCallNumber']; ?></li>
        <li><i class="fas fa-times text-danger me-2"></i><?php echo __('Without:'); ?> <?php echo $summary['withoutCallNumber']; ?></li>
    </ul>
    <?php if (!empty($summary['byScheme'])): ?>
    <h5><?php echo __('By Scheme'); ?></h5>
    <ul class="list-unstyled small">
        <?php foreach ($summary['byScheme'] as $s): ?>
        <li><?php echo esc_specialchars($s->classification_scheme); ?>: <?php echo $s->count; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <hr>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-sort-alpha-down"></i> <?php echo __('Call Numbers Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th><?php echo __('Call #'); ?></th><th><?php echo __('Title'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Shelf'); ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($callNumbers as $c): ?>
            <tr>
                <td><code><?php echo esc_specialchars($c->call_number); ?></code></td>
                <td><?php echo esc_specialchars($c->title ?? '-'); ?></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst($c->material_type ?? '-'); ?></span></td>
                <td><small><?php echo esc_specialchars($c->shelf_location ?? '-'); ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
