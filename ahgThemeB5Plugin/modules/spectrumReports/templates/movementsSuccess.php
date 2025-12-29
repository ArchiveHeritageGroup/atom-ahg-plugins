<?php decorate_with('layout_2col'); ?>
<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>
<?php slot('title'); ?><h1><i class="fas fa-truck"></i> <?php echo __('Movements Report'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>
<?php if (empty($movements)): ?>
<div class="alert alert-info"><?php echo __('No movements recorded.'); ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('From'); ?></th><th><?php echo __('To'); ?></th><th><?php echo __('Reason'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($movements as $m): ?>
        <tr>
            <td><?php if ($m->slug): ?><a href="/<?php echo $m->slug; ?>"><?php echo esc_specialchars($m->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo $m->movement_date ?? '-'; ?></td>
            <td><?php echo esc_specialchars($m->from_location ?? '-'); ?></td>
            <td><?php echo esc_specialchars($m->to_location ?? '-'); ?></td>
            <td><?php echo esc_specialchars($m->reason ?? '-'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
