<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter Options'); ?></h4>
    <form method="get" action="<?php echo url_for(['module' => 'libraryReports', 'action' => 'catalogue']); ?>">
        <div class="mb-3">
            <label class="form-label"><?php echo __('Search'); ?></label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?php echo esc_specialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('Title, ISBN, Publisher...'); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Material Type'); ?></label>
            <select name="material_type" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($materialTypes as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo ($filters['materialType'] ?? '') === $t ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $t)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label"><?php echo __('Status'); ?></label>
            <select name="status" class="form-select form-select-sm">
                <option value=""><?php echo __('All'); ?></option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo ($filters['status'] ?? '') === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
        <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'catalogue']); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?php echo __('Clear'); ?></a>
    </form>
    <hr>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'exportCsv', 'report' => 'catalogue']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export CSV'); ?></a>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-book"></i> <?php echo __('Library Catalogue Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="alert alert-info"><strong><?php echo count($items); ?></strong> <?php echo __('items found'); ?></div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Author(s)'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Call #'); ?></th>
                <th><?php echo __('ISBN'); ?></th>
                <th><?php echo __('Publisher'); ?></th>
                <th><?php echo __('Status'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($item->title ?? 'Untitled'); ?></strong></td>
                <td><small><?php echo esc_specialchars($item->authors ?? '-'); ?></small></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst($item->material_type); ?></span></td>
                <td><code><?php echo esc_specialchars($item->call_number ?? '-'); ?></code></td>
                <td><small><?php echo esc_specialchars($item->isbn ?? '-'); ?></small></td>
                <td><small><?php echo esc_specialchars($item->publisher ?? '-'); ?></small></td>
                <td>
                    <?php
                    $statusColors = ['available' => 'success', 'on_loan' => 'warning', 'reference' => 'info', 'processing' => 'secondary', 'missing' => 'danger', 'withdrawn' => 'dark'];
                    $color = $statusColors[$item->circulation_status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst(str_replace('_', ' ', $item->circulation_status)); ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
