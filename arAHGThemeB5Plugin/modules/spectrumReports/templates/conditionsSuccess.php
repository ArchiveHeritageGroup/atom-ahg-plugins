<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('By Condition'); ?></h4>
    <ul class="list-unstyled">
        <?php foreach ($byCondition as $c): ?>
        <li><?php echo ucfirst($c->overall_condition ?? 'Unknown'); ?>: <strong><?php echo $c->count; ?></strong></li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-heartbeat"></i> <?php echo __('Condition Checks Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info"><strong><?php echo $summary['total']; ?></strong> <?php echo __('condition checks recorded'); ?></div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th><?php echo __('Object'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('Condition'); ?></th><th><?php echo __('Checked By'); ?></th><th><?php echo __('Notes'); ?></th></tr>
        </thead>
        <tbody>
        <?php foreach ($conditions as $c): ?>
        <tr>
            <td><?php if ($c->slug): ?><a href="/<?php echo $c->slug; ?>"><?php echo esc_specialchars($c->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo $c->check_date ?? '-'; ?></td>
            <td>
                <?php 
                $cond = $c->overall_condition ?? 'unknown';
                $colors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger'];
                ?>
                <span class="badge bg-<?php echo $colors[$cond] ?? 'secondary'; ?>"><?php echo ucfirst($cond); ?></span>
            </td>
            <td><?php echo esc_specialchars($c->checked_by ?? $c->assessor ?? '-'); ?></td>
            <td><small><?php echo esc_specialchars(substr($c->notes ?? $c->condition_notes ?? '', 0, 50)); ?></small></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
