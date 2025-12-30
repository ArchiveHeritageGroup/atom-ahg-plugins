<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter Options'); ?></h4>
    <form method="get" action="<?php echo url_for(['module' => 'galleryReports', 'action' => 'artists']); ?>">
        <div class="mb-3">
            <label class="form-label"><?php echo __('Represented'); ?></label>
            <select name="represented" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <option value="1" <?php echo $filters['represented'] === '1' ? 'selected' : ''; ?>><?php echo __('Yes'); ?></option>
                <option value="0" <?php echo $filters['represented'] === '0' ? 'selected' : ''; ?>><?php echo __('No'); ?></option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Nationality'); ?></label>
            <select name="nationality" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($nationalities as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo $filters['nationality'] === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?></label>
            <select name="artist_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($artistTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $filters['artistType'] === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Active'); ?></label>
            <select name="active" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <option value="1" <?php echo $filters['active'] === '1' ? 'selected' : ''; ?>><?php echo __('Yes'); ?></option>
                <option value="0" <?php echo $filters['active'] === '0' ? 'selected' : ''; ?>><?php echo __('No'); ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply Filters'); ?></button>
        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'artists']); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?php echo __('Clear'); ?></a>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exportCsv', 'report' => 'artists']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export CSV'); ?></a>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Dashboard'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-palette"></i> <?php echo __('Artists Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info">
    <strong><?php echo count($artists); ?></strong> <?php echo __('artists found'); ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Name'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Nationality'); ?></th>
                <th><?php echo __('Represented'); ?></th>
                <th><?php echo __('Exhibitions'); ?></th>
                <th><?php echo __('Active'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($artists as $a): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($a->display_name); ?></strong><?php if ($a->birth_date || $a->death_date): ?><br><small class="text-muted"><?php echo $a->birth_date ? date('Y', strtotime($a->birth_date)) : '?'; ?> - <?php echo $a->death_date ? date('Y', strtotime($a->death_date)) : ''; ?></small><?php endif; ?></td>
                <td><?php echo ucfirst($a->artist_type ?? 'individual'); ?></td>
                <td><?php echo esc_specialchars($a->nationality ?? '-'); ?></td>
                <td><?php echo $a->represented ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                <td class="text-center"><?php echo $a->exhibition_count ?? 0; ?></td>
                <td><?php echo $a->is_active ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
