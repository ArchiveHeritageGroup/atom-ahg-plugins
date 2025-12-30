<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter Options'); ?></h4>
    <form method="get" action="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exhibitions']); ?>">
        <div class="mb-3">
            <label class="form-label"><?php echo __('Status'); ?></label>
            <select name="status" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $filters['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?></label>
            <select name="type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($types as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $filters['type'] === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Year'); ?></label>
            <select name="year" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo $filters['year'] == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply Filters'); ?></button>
        <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exhibitions']); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?php echo __('Clear'); ?></a>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'exportCsv', 'report' => 'exhibitions']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export CSV'); ?></a>
    <a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Dashboard'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-images"></i> <?php echo __('Exhibitions Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info">
    <strong><?php echo count($exhibitions); ?></strong> <?php echo __('exhibitions found'); ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Venue'); ?></th>
                <th><?php echo __('Dates'); ?></th>
                <th><?php echo __('Objects'); ?></th>
                <th><?php echo __('Visitors'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exhibitions as $e): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($e->title); ?></strong><?php if ($e->subtitle): ?><br><small class="text-muted"><?php echo esc_specialchars($e->subtitle); ?></small><?php endif; ?></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst($e->exhibition_type); ?></span></td>
                <td>
                    <?php
                    $statusColors = ['planning' => 'warning', 'confirmed' => 'info', 'installing' => 'primary', 'open' => 'success', 'closing' => 'warning', 'closed' => 'secondary', 'cancelled' => 'danger'];
                    $color = $statusColors[$e->status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($e->status); ?></span>
                </td>
                <td><?php echo esc_specialchars($e->venue_name ?? '-'); ?></td>
                <td><?php echo $e->start_date ? date('d M Y', strtotime($e->start_date)) : '-'; ?><?php if ($e->end_date): ?> - <?php echo date('d M Y', strtotime($e->end_date)); ?><?php endif; ?></td>
                <td class="text-center"><?php echo $e->object_count; ?></td>
                <td class="text-end"><?php echo number_format($e->visitor_count ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
