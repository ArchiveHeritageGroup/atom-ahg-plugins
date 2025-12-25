<?php decorate_with('layout_2col'); ?>
<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('By Method'); ?></h4>
    <ul class="list-unstyled">
    <?php foreach ($byMethod as $m): ?>
    <li><?php echo ucfirst($m->acquisition_method ?? 'Unknown'); ?>: <strong><?php echo $m->count; ?></strong></li>
    <?php endforeach; ?>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>
<?php slot('title'); ?><h1><i class="fas fa-hand-holding"></i> <?php echo __('Acquisitions Report'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>
<?php if (empty($acquisitions)): ?>
<div class="alert alert-info"><?php echo __('No acquisitions recorded.'); ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('Method'); ?></th><th><?php echo __('Source'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($acquisitions as $a): ?>
        <tr>
            <td><?php if ($a->slug): ?><a href="/<?php echo $a->slug; ?>"><?php echo esc_specialchars($a->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo $a->acquisition_date ?? '-'; ?></td>
            <td><?php echo esc_specialchars($a->acquisition_method ?? '-'); ?></td>
            <td><?php echo esc_specialchars($a->source ?? $a->acquired_from ?? '-'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
