<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <ul class="list-unstyled">
        <li><strong><?php echo __('Total Spaces:'); ?></strong> <?php echo $summary['totalSpaces']; ?></li>
        <li><strong><?php echo __('Total Area:'); ?></strong> <?php echo number_format($summary['totalArea'], 2); ?> m²</li>
        <li><strong><?php echo __('Wall Length:'); ?></strong> <?php echo number_format($summary['totalWallLength'], 2); ?> m</li>
        <li><strong><?php echo __('Climate Controlled:'); ?></strong> <?php echo $summary['climateControlled']; ?></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Dashboard'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-th-large"></i> <?php echo __('Gallery Spaces'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Space'); ?></th>
                <th><?php echo __('Venue'); ?></th>
                <th><?php echo __('Area (m²)'); ?></th>
                <th><?php echo __('Wall Length (m)'); ?></th>
                <th><?php echo __('Height (m)'); ?></th>
                <th><?php echo __('Climate'); ?></th>
                <th><?php echo __('Max Weight (kg)'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($spaces as $s): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($s->name); ?></strong><?php if ($s->description): ?><br><small class="text-muted"><?php echo esc_specialchars(substr($s->description, 0, 50)); ?></small><?php endif; ?></td>
                <td><?php echo esc_specialchars($s->venue_name ?? '-'); ?></td>
                <td class="text-end"><?php echo $s->area_sqm ? number_format($s->area_sqm, 2) : '-'; ?></td>
                <td class="text-end"><?php echo $s->wall_length_m ? number_format($s->wall_length_m, 2) : '-'; ?></td>
                <td class="text-end"><?php echo $s->height_m ? number_format($s->height_m, 2) : '-'; ?></td>
                <td class="text-center"><?php echo $s->climate_controlled ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                <td class="text-end"><?php echo $s->max_weight_kg ? number_format($s->max_weight_kg, 2) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
