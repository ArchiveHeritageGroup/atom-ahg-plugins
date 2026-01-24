<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <ul class="list-unstyled">
        <li><i class="fas fa-arrow-down text-success me-2"></i><?php echo __('Loans In:'); ?> <?php echo $summary['totalIn']; ?></li>
        <li><i class="fas fa-arrow-up text-warning me-2"></i><?php echo __('Loans Out:'); ?> <?php echo $summary['totalOut']; ?></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-exchange-alt"></i> <?php echo __('Loans Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<h4 class="text-success"><i class="fas fa-arrow-down me-2"></i><?php echo __('Loans In'); ?> (<?php echo count($loansIn); ?>)</h4>
<?php if (empty($loansIn)): ?>
<p class="text-muted"><?php echo __('No loans in recorded.'); ?></p>
<?php else: ?>
<div class="table-responsive mb-4">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Lender'); ?></th><th><?php echo __('Start'); ?></th><th><?php echo __('End'); ?></th><th><?php echo __('Status'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($loansIn as $l): ?>
        <tr>
            <td><?php if ($l->slug): ?><a href="/<?php echo $l->slug; ?>"><?php echo esc_specialchars($l->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo esc_specialchars($l->lender_name ?? $l->lender ?? '-'); ?></td>
            <td><?php echo $l->loan_start_date ?? $l->start_date ?? '-'; ?></td>
            <td><?php echo $l->loan_end_date ?? $l->end_date ?? '-'; ?></td>
            <td><span class="badge bg-info"><?php echo esc_specialchars($l->status ?? 'Active'); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<h4 class="text-warning"><i class="fas fa-arrow-up me-2"></i><?php echo __('Loans Out'); ?> (<?php echo count($loansOut); ?>)</h4>
<?php if (empty($loansOut)): ?>
<p class="text-muted"><?php echo __('No loans out recorded.'); ?></p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th><?php echo __('Object'); ?></th><th><?php echo __('Borrower'); ?></th><th><?php echo __('Start'); ?></th><th><?php echo __('End'); ?></th><th><?php echo __('Status'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($loansOut as $l): ?>
        <tr>
            <td><?php if ($l->slug): ?><a href="/<?php echo $l->slug; ?>"><?php echo esc_specialchars($l->title ?? 'Untitled'); ?></a><?php else: echo '-'; endif; ?></td>
            <td><?php echo esc_specialchars($l->borrower_name ?? $l->borrower ?? '-'); ?></td>
            <td><?php echo $l->loan_start_date ?? $l->start_date ?? '-'; ?></td>
            <td><?php echo $l->loan_end_date ?? $l->end_date ?? '-'; ?></td>
            <td><span class="badge bg-warning"><?php echo esc_specialchars($l->status ?? 'Active'); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
