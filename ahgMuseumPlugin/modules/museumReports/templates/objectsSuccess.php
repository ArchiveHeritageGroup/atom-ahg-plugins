<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <div class="mb-3">
            <select name="work_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All Work Types'); ?></option>
                <?php foreach ($workTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo ($filters['workType'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <select name="classification" class="form-select form-select-sm">
                <option value=""><?php echo __('All Classifications'); ?></option>
                <?php foreach ($classifications as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($filters['classification'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <select name="condition" class="form-select form-select-sm">
                <option value=""><?php echo __('All Conditions'); ?></option>
                <?php foreach ($conditions as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($filters['condition'] ?? '') === $c ? 'selected' : ''; ?>><?php echo ucfirst($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'exportCsv', 'report' => 'objects']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></a>
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-cube"></i> <?php echo __('Museum Objects Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info"><strong><?php echo count($objects); ?></strong> <?php echo __('objects found'); ?></div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Work Type'); ?></th>
                <th><?php echo __('Classification'); ?></th>
                <th><?php echo __('Materials'); ?></th>
                <th><?php echo __('Condition'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($objects as $o): ?>
            <tr>
                <td>
                    <strong><?php echo esc_specialchars($o->title ?? 'Untitled'); ?></strong>
                    <?php if ($o->identifier): ?><br><small class="text-muted"><?php echo esc_specialchars($o->identifier); ?></small><?php endif; ?>
                </td>
                <td><span class="badge bg-secondary"><?php echo esc_specialchars($o->work_type ?? '-'); ?></span></td>
                <td><small><?php echo esc_specialchars($o->classification ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars(substr($o->materials ?? '-', 0, 50)); ?></small></td>
                <td>
                    <?php if ($o->condition_term): ?>
                    <span class="badge bg-<?php echo in_array($o->condition_term, ['poor', 'critical']) ? 'danger' : 'success'; ?>"><?php echo ucfirst($o->condition_term); ?></span>
                    <?php else: ?>-<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
