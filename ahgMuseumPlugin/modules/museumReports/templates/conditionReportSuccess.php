<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <select name="condition_term" class="form-select form-select-sm mb-3">
            <option value=""><?php echo __('All Conditions'); ?></option>
            <?php foreach ($conditionTerms as $c): ?>
            <option value="<?php echo $c; ?>" <?php echo ($filters['conditionTerm'] ?? '') === $c ? 'selected' : ''; ?>><?php echo ucfirst($c); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <h5><?php echo __('Summary'); ?></h5>
    <?php foreach ($summary['byCondition'] as $c): ?>
    <p class="mb-1"><span class="badge bg-<?php echo in_array($c->condition_term, ['poor', 'critical']) ? 'danger' : 'success'; ?>"><?php echo ucfirst($c->condition_term); ?></span> <?php echo $c->count; ?></p>
    <?php endforeach; ?>
    <?php if ($summary['needingTreatment'] > 0): ?>
    <div class="alert alert-warning mt-3"><strong><?php echo $summary['needingTreatment']; ?></strong> <?php echo __('need treatment'); ?></div>
    <?php endif; ?>
    <hr>
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-heartbeat"></i> <?php echo __('Condition Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Condition'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Treatment'); ?></th>
                <th><?php echo __('Notes'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($r->title ?? 'Untitled'); ?></strong></td>
                <td><span class="badge bg-<?php echo in_array($r->condition_term, ['poor', 'critical']) ? 'danger' : 'success'; ?>"><?php echo ucfirst($r->condition_term ?? '-'); ?></span></td>
                <td><?php echo $r->condition_date ? date('d M Y', strtotime($r->condition_date)) : '-'; ?></td>
                <td><small><?php echo esc_specialchars($r->treatment_type ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars(substr($r->condition_description ?? '-', 0, 80)); ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
