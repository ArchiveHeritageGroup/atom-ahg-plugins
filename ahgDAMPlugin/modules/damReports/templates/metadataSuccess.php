<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <div class="mb-3">
            <label class="form-label"><?php echo __('File Type'); ?></label>
            <select name="file_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($fileTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo ($filters['fileType'] ?? '') === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="has_gps" value="1" id="hasGps" <?php echo ($filters['hasGps'] ?? '') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="hasGps"><?php echo __('With GPS only'); ?></label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <h5><?php echo __('Summary'); ?></h5>
    <ul class="list-unstyled small">
        <?php foreach ($summary['byFileType'] as $t): ?>
        <li><?php echo ucfirst($t->file_type); ?>: <?php echo $t->count; ?></li>
        <?php endforeach; ?>
    </ul>
    <p><i class="fas fa-map-marker-alt me-2"></i><?php echo __('With GPS:'); ?> <?php echo $summary['withGps']; ?></p>
    <hr>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-info-circle"></i> <?php echo __('Metadata Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('File'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Creator'); ?></th>
                <th><?php echo __('Dimensions'); ?></th>
                <th><?php echo __('Camera'); ?></th>
                <th><?php echo __('GPS'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($metadata as $m): ?>
            <tr>
                <td><small><?php echo esc_specialchars($m->filename ?? '-'); ?></small></td>
                <td><span class="badge bg-secondary"><?php echo $m->file_type; ?></span></td>
                <td><small><?php echo esc_specialchars($m->creator ?? '-'); ?></small></td>
                <td><?php echo $m->image_width && $m->image_height ? "{$m->image_width}x{$m->image_height}" : '-'; ?></td>
                <td><small><?php echo esc_specialchars($m->camera_model ?? '-'); ?></small></td>
                <td><?php echo $m->gps_latitude ? '<i class="fas fa-check text-success"></i>' : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
