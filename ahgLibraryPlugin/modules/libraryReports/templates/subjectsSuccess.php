<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <div class="mb-3">
            <input type="text" name="q" class="form-control form-control-sm" value="<?php echo esc_specialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('Search...'); ?>">
        </div>
        <div class="mb-3">
            <select name="subject_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All Types'); ?></option>
                <?php foreach ($subjectTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo ($filters['subjectType'] ?? '') === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'exportCsv', 'report' => 'subjects']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></a>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-tags"></i> <?php echo __('Subjects Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th><?php echo __('Subject'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Source'); ?></th><th><?php echo __('Items'); ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $s): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($s->heading); ?></strong></td>
                <td><span class="badge bg-info"><?php echo ucfirst($s->subject_type ?? 'topic'); ?></span></td>
                <td><small><?php echo esc_specialchars($s->source ?? '-'); ?></small></td>
                <td><span class="badge bg-primary"><?php echo $s->item_count; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
