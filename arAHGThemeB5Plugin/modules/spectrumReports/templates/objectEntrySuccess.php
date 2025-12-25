<?php decorate_with('layout_2col'); ?>
<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>
<?php slot('title'); ?><h1><i class="fas fa-sign-in-alt"></i> <?php echo __('Object Entry Report'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>
<?php if (empty($entries)): ?>
<div class="alert alert-info"><?php echo __('No object entries recorded.'); ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Entry Date'); ?></th><th><?php echo __('Entry Number'); ?></th><th><?php echo __('Depositor'); ?></th><th><?php echo __('Reason'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
            <td><?php if ($e->slug): ?><a href="/<?php echo $e->slug; ?>"><?php echo esc_specialchars($e->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo $e->entry_date ?? '-'; ?></td>
            <td><?php echo esc_specialchars($e->entry_number ?? '-'); ?></td>
            <td><?php echo esc_specialchars($e->depositor ?? '-'); ?></td>
            <td><?php echo esc_specialchars($e->entry_reason ?? '-'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
