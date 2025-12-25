<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <select name="hotspot_type" class="form-select form-select-sm mb-3">
            <option value=""><?php echo __('All Types'); ?></option>
            <?php foreach ($hotspotTypes as $t): ?>
            <option value="<?php echo $t; ?>" <?php echo ($filters['hotspotType'] ?? '') === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <h5><?php echo __('By Type'); ?></h5>
    <ul class="list-unstyled small">
        <?php foreach ($summary['byType'] as $t): ?>
        <li><?php echo ucfirst($t->hotspot_type); ?>: <?php echo $t->count; ?></li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-map-pin"></i> <?php echo __('Hotspots Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php if (empty($hotspots)): ?>
<div class="alert alert-info"><?php echo __('No hotspots configured yet.'); ?></div>
<?php else: ?>
<div class="alert alert-info"><strong><?php echo count($hotspots); ?></strong> <?php echo __('hotspots found'); ?></div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Model'); ?></th>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Position'); ?></th>
                <th><?php echo __('Visible'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hotspots as $h): ?>
            <tr>
                <td><?php echo esc_specialchars($h->hotspot_title ?? '-'); ?></td>
                <td><span class="badge bg-info"><?php echo ucfirst($h->hotspot_type); ?></span></td>
                <td><small><?php echo esc_specialchars($h->model_name ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars($h->object_title ?? '-'); ?></small></td>
                <td><code><?php echo "{$h->position_x}, {$h->position_y}, {$h->position_z}"; ?></code></td>
                <td><?php echo $h->is_visible ? '<i class="fas fa-eye text-success"></i>' : '<i class="fas fa-eye-slash text-muted"></i>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php end_slot(); ?>
