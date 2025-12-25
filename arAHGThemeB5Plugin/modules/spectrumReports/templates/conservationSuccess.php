<?php decorate_with('layout_2col'); ?>
<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>
<?php slot('title'); ?><h1><i class="fas fa-tools"></i> <?php echo __('Conservation Report'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>
<?php if (empty($treatments)): ?>
<div class="alert alert-info"><?php echo __('No conservation treatments recorded.'); ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('Treatment'); ?></th><th><?php echo __('Conservator'); ?></th><th><?php echo __('Status'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($treatments as $t): ?>
        <tr>
            <td><?php if ($t->slug): ?><a href="/<?php echo $t->slug; ?>"><?php echo esc_specialchars($t->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo $t->treatment_date ?? $t->created_at ?? '-'; ?></td>
            <td><?php echo esc_specialchars($t->treatment_type ?? '-'); ?></td>
            <td><?php echo esc_specialchars($t->conservator ?? '-'); ?></td>
            <td><span class="badge bg-info"><?php echo esc_specialchars($t->status ?? 'Complete'); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
