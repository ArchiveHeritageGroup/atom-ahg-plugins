<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter Options'); ?></h4>
    <form method="get" action="<?php echo url_for(['module' => 'galleryReports', 'action' => 'loans']); ?>">
        <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?></label>
            <select name="loan_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($loanTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $filters['loanType'] === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Status'); ?></label>
            <select name="status" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $filters['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Year'); ?></label>
            <select name="year" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo $filters['year'] == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="overdue" value="1" id="overdue" <?php echo $filters['overdue'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="overdue"><?php echo __('Overdue only'); ?></label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply Filters'); ?></button>
        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'loans']); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?php echo __('Clear'); ?></a>
    </form>
    <hr>
    <h5><?php echo __('Summary'); ?></h5>
    <ul class="list-unstyled small">
        <li><strong><?php echo __('Total Insurance:'); ?></strong> R <?php echo number_format($summary['totalInsuranceValue'] ?? 0, 2); ?></li>
        <li><strong><?php echo __('Total Fees:'); ?></strong> R <?php echo number_format($summary['totalLoanFees'] ?? 0, 2); ?></li>
        <li><strong><?php echo __('Overdue:'); ?></strong> <span class="text-danger"><?php echo $summary['overdueCount'] ?? 0; ?></span></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exportCsv', 'report' => 'loans']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export CSV'); ?></a>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Dashboard'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-exchange-alt"></i> <?php echo __('Loans Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info">
    <strong><?php echo count($loans); ?></strong> <?php echo __('loans found'); ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Loan #'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Institution'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Dates'); ?></th>
                <th><?php echo __('Objects'); ?></th>
                <th><?php echo __('Insurance'); ?></th>
                <th><?php echo __('Days'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($loans as $l): ?>
            <tr class="<?php echo ($l->days_remaining < 0 && $l->status === 'on_loan') ? 'table-danger' : ''; ?>">
                <td><strong><?php echo esc_specialchars($l->loan_number); ?></strong></td>
                <td><span class="badge bg-<?php echo $l->loan_type === 'incoming' ? 'info' : 'warning'; ?>"><?php echo ucfirst($l->loan_type); ?></span></td>
                <td><?php echo esc_specialchars($l->institution_name); ?></td>
                <td>
                    <?php
                    $statusColors = ['inquiry' => 'secondary', 'requested' => 'info', 'approved' => 'primary', 'agreed' => 'success', 'in_transit_out' => 'warning', 'on_loan' => 'success', 'in_transit_return' => 'warning', 'returned' => 'secondary', 'cancelled' => 'danger', 'declined' => 'danger'];
                    $color = $statusColors[$l->status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst(str_replace('_', ' ', $l->status)); ?></span>
                </td>
                <td><?php echo $l->loan_start_date ? date('d M Y', strtotime($l->loan_start_date)) : '-'; ?> - <?php echo $l->loan_end_date ? date('d M Y', strtotime($l->loan_end_date)) : '-'; ?></td>
                <td class="text-center"><?php echo $l->object_count; ?></td>
                <td class="text-end">R <?php echo number_format($l->insurance_value ?? 0, 2); ?></td>
                <td class="text-center">
                    <?php if ($l->status === 'on_loan'): ?>
                        <?php if ($l->days_remaining < 0): ?>
                            <span class="badge bg-danger"><?php echo abs($l->days_remaining); ?> overdue</span>
                        <?php elseif ($l->days_remaining <= 14): ?>
                            <span class="badge bg-warning"><?php echo $l->days_remaining; ?> left</span>
                        <?php else: ?>
                            <span class="badge bg-success"><?php echo $l->days_remaining; ?> left</span>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
