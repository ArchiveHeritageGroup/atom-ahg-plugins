<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <p><strong><?php echo $summary['totalCreators']; ?></strong> <?php echo __('unique creators'); ?></p>
    <?php if (!empty($summary['byRole'])): ?>
    <h5><?php echo __('By Role'); ?></h5>
    <ul class="list-unstyled small">
        <?php foreach ($summary['byRole'] as $r): ?>
        <li><?php echo ucfirst($r->creator_role); ?>: <?php echo $r->count; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <hr>
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-user-edit"></i> <?php echo __('Creators Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Creator'); ?></th>
                <th><?php echo __('Role'); ?></th>
                <th><?php echo __('Attribution'); ?></th>
                <th><?php echo __('School'); ?></th>
                <th><?php echo __('Objects'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($creators as $c): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($c->creator_identity); ?></strong></td>
                <td><span class="badge bg-secondary"><?php echo esc_specialchars($c->creator_role ?? '-'); ?></span></td>
                <td><small><?php echo esc_specialchars($c->creator_attribution ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars($c->school ?? '-'); ?></small></td>
                <td><span class="badge bg-primary"><?php echo $c->object_count; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
