<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter Options'); ?></h4>
    <form method="get" action="<?php echo url_for(['module' => 'galleryReports', 'action' => 'valuations']); ?>">
        <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?></label>
            <select name="valuation_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($valuationTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $filters['valuationType'] === $t ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $t)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Current Only'); ?></label>
            <select name="current" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <option value="1" <?php echo $filters['current'] === '1' ? 'selected' : ''; ?>><?php echo __('Current'); ?></option>
                <option value="0" <?php echo $filters['current'] === '0' ? 'selected' : ''; ?>><?php echo __('Historical'); ?></option>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="expiring" value="1" id="expiring" <?php echo $filters['expiring'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="expiring"><?php echo __('Expiring within 90 days'); ?></label>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Min Value (R)'); ?></label>
            <input type="number" name="min_value" class="form-control form-control-sm" value="<?php echo $filters['minValue']; ?>">
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Max Value (R)'); ?></label>
            <input type="number" name="max_value" class="form-control form-control-sm" value="<?php echo $filters['maxValue']; ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply Filters'); ?></button>
        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'valuations']); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?php echo __('Clear'); ?></a>
    </form>
    <hr>
    <h5><?php echo __('Summary'); ?></h5>
    <ul class="list-unstyled small">
        <li><strong><?php echo __('Total Current Value:'); ?></strong><br>R <?php echo number_format($summary['totalCurrentValue'] ?? 0, 2); ?></li>
        <li><strong><?php echo __('Average Value:'); ?></strong><br>R <?php echo number_format($summary['avgValue'] ?? 0, 2); ?></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exportCsv', 'report' => 'valuations']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export CSV'); ?></a>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Dashboard'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-coins"></i> <?php echo __('Valuations Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info">
    <strong><?php echo count($valuations); ?></strong> <?php echo __('valuations found'); ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Value'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Valid Until'); ?></th>
                <th><?php echo __('Appraiser'); ?></th>
                <th><?php echo __('Current'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($valuations as $v): ?>
            <tr class="<?php echo ($v->is_current && $v->valid_until && strtotime($v->valid_until) <= strtotime('+90 days')) ? 'table-warning' : ''; ?>">
                <td><?php echo esc_specialchars($v->object_title ?? 'Object #' . $v->object_id); ?></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $v->valuation_type)); ?></span></td>
                <td class="text-end"><strong><?php echo $v->currency ?? 'ZAR'; ?> <?php echo number_format($v->value_amount, 2); ?></strong></td>
                <td><?php echo date('d M Y', strtotime($v->valuation_date)); ?></td>
                <td>
                    <?php if ($v->valid_until): ?>
                        <?php if (strtotime($v->valid_until) < time()): ?>
                            <span class="text-danger"><?php echo date('d M Y', strtotime($v->valid_until)); ?></span>
                        <?php elseif (strtotime($v->valid_until) <= strtotime('+90 days')): ?>
                            <span class="text-warning"><?php echo date('d M Y', strtotime($v->valid_until)); ?></span>
                        <?php else: ?>
                            <?php echo date('d M Y', strtotime($v->valid_until)); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo esc_specialchars($v->appraiser_name ?? '-'); ?></td>
                <td class="text-center"><?php echo $v->is_current ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
