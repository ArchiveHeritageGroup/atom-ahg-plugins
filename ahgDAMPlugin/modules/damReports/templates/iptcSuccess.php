<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <div class="mb-3">
            <input type="text" name="creator" class="form-control form-control-sm" value="<?php echo esc_specialchars($filters['creator'] ?? ''); ?>" placeholder="<?php echo __('Creator...'); ?>">
        </div>
        <div class="mb-3">
            <select name="country" class="form-select form-select-sm">
                <option value=""><?php echo __('All Countries'); ?></option>
                <?php foreach ($countries as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($filters['country'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <select name="license_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All Licenses'); ?></option>
                <?php foreach ($licenseTypes as $l): ?>
                <option value="<?php echo $l; ?>" <?php echo ($filters['licenseType'] ?? '') === $l ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $l)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <h5><?php echo __('By License'); ?></h5>
    <ul class="list-unstyled small">
        <?php foreach ($summary['byLicense'] as $l): ?>
        <li><?php echo ucfirst(str_replace('_', ' ', $l->license_type ?? 'none')); ?>: <?php echo $l->count; ?></li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'exportCsv', 'report' => 'iptc']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></a>
    <a href="<?php echo url_for(['module' => 'damReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-camera"></i> <?php echo __('IPTC Metadata Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info"><strong><?php echo count($iptcRecords); ?></strong> <?php echo __('records with IPTC data'); ?></div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Headline'); ?></th>
                <th><?php echo __('Creator'); ?></th>
                <th><?php echo __('Location'); ?></th>
                <th><?php echo __('License'); ?></th>
                <th><?php echo __('Copyright'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($iptcRecords as $i): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($i->headline ?? $i->title ?? '-'); ?></strong></td>
                <td><?php echo esc_specialchars($i->creator ?? '-'); ?></td>
                <td><small><?php echo esc_specialchars(implode(', ', array_filter([$i->city, $i->country]))); ?></small></td>
                <td><span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $i->license_type ?? 'none')); ?></span></td>
                <td><small><?php echo esc_specialchars(substr($i->copyright_notice ?? '-', 0, 50)); ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
