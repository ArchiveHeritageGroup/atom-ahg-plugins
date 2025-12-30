<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter Options'); ?></h4>
    <form method="get" action="<?php echo url_for(['module' => 'galleryReports', 'action' => 'facilityReports']); ?>">
        <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?></label>
            <select name="report_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <option value="incoming" <?php echo $filters['reportType'] === 'incoming' ? 'selected' : ''; ?>><?php echo __('Incoming'); ?></option>
                <option value="outgoing" <?php echo $filters['reportType'] === 'outgoing' ? 'selected' : ''; ?>><?php echo __('Outgoing'); ?></option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Approved'); ?></label>
            <select name="approved" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <option value="1" <?php echo $filters['approved'] === '1' ? 'selected' : ''; ?>><?php echo __('Yes'); ?></option>
                <option value="0" <?php echo $filters['approved'] === '0' ? 'selected' : ''; ?>><?php echo __('No'); ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply Filters'); ?></button>
        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'facilityReports']); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?php echo __('Clear'); ?></a>
    </form>
    <hr>
    <h5><?php echo __('Compliance Summary'); ?></h5>
    <ul class="list-unstyled small">
        <li><i class="fas fa-fire text-danger me-2"></i><?php echo __('Fire Detection:'); ?> <?php echo $compliance['withFireDetection']; ?></li>
        <li><i class="fas fa-thermometer-half text-info me-2"></i><?php echo __('Climate Control:'); ?> <?php echo $compliance['withClimateControl']; ?></li>
        <li><i class="fas fa-shield-alt text-primary me-2"></i><?php echo __('24hr Security:'); ?> <?php echo $compliance['with24hrSecurity']; ?></li>
        <li><i class="fas fa-hands text-success me-2"></i><?php echo __('Trained Handlers:'); ?> <?php echo $compliance['withTrainedHandlers']; ?></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Dashboard'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-building"></i> <?php echo __('Facility Reports'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info">
    <strong><?php echo count($reports); ?></strong> <?php echo __('facility reports found'); ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Institution'); ?></th>
                <th><?php echo __('Loan'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Fire'); ?></th>
                <th><?php echo __('Climate'); ?></th>
                <th><?php echo __('Security'); ?></th>
                <th><?php echo __('Handlers'); ?></th>
                <th><?php echo __('Approved'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td><?php echo esc_specialchars($r->institution_name ?? $r->loan_institution ?? '-'); ?></td>
                <td><?php echo esc_specialchars($r->loan_number ?? '-'); ?></td>
                <td><span class="badge bg-<?php echo $r->report_type === 'incoming' ? 'info' : 'warning'; ?>"><?php echo ucfirst($r->report_type); ?></span></td>
                <td class="text-center"><?php echo $r->fire_detection ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                <td class="text-center"><?php echo $r->climate_controlled ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                <td class="text-center"><?php echo $r->security_24hr ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                <td class="text-center"><?php echo $r->trained_handlers ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                <td class="text-center"><?php echo $r->approved ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">Pending</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
