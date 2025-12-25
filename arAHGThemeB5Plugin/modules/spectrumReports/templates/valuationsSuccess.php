<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <p><strong><?php echo $summary['total']; ?></strong> <?php echo __('valuations'); ?></p>
    <p><strong>R <?php echo number_format($summary['totalValue'], 2); ?></strong><br><small><?php echo __('Total Value'); ?></small></p>
    <hr>
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-dollar-sign"></i> <?php echo __('Valuations Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php if (empty($valuations)): ?>
<div class="alert alert-info"><?php echo __('No valuations recorded.'); ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th><?php echo __('Object'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('Value'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Valuator'); ?></th></tr>
        </thead>
        <tbody>
        <?php foreach ($valuations as $v): ?>
        <tr>
            <td><?php if ($v->slug): ?><a href="/<?php echo $v->slug; ?>"><?php echo esc_specialchars($v->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo $v->valuation_date ?? '-'; ?></td>
            <td><strong>R <?php echo number_format($v->valuation_amount ?? 0, 2); ?></strong></td>
            <td><?php echo esc_specialchars($v->valuation_type ?? '-'); ?></td>
            <td><?php echo esc_specialchars($v->valuer_name ?? $v->valued_by ?? '-'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
