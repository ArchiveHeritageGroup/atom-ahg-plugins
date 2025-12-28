<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Filter'); ?></h4>
    <form method="get">
        <div class="mb-3">
            <input type="text" name="q" class="form-control form-control-sm" value="<?php echo esc_specialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('Search...'); ?>">
        </div>
        <div class="mb-3">
            <select name="role" class="form-select form-select-sm">
                <option value=""><?php echo __('All Roles'); ?></option>
                <?php foreach ($roles as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo ($filters['role'] ?? '') === $r ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
    </form>
    <hr>
    <h5><?php echo __('Summary'); ?></h5>
    <p><strong><?php echo $summary['totalCreators']; ?></strong> <?php echo __('unique creators'); ?></p>
    <ul class="list-unstyled small">
        <?php foreach ($summary['byRole'] as $r): ?>
        <li><?php echo ucfirst($r->role ?? 'unknown'); ?>: <?php echo $r->count; ?></li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'exportCsv', 'report' => 'creators']); ?>" class="btn btn-success btn-sm w-100"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></a>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-user-edit"></i> <?php echo __('Creators Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th><?php echo __('Name'); ?></th><th><?php echo __('Role'); ?></th><th><?php echo __('Items'); ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($creators as $c): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($c->name); ?></strong></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst($c->role ?? 'author'); ?></span></td>
                <td><span class="badge bg-primary"><?php echo $c->item_count; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
