<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-clipboard-list me-2"></i><?php echo __('Audit Trail'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-filter me-1"></i><?php echo __('Filters'); ?></span>
    <a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'export']); ?>" class="btn btn-sm btn-success">
      <i class="fas fa-download me-1"></i><?php echo __('Export CSV'); ?>
    </a>
  </div>
  <div class="card-body">
    <form method="get" class="row g-3">
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Action'); ?></label>
        <select name="action_filter" class="form-select form-select-sm">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach ($actions as $action): ?>
            <option value="<?php echo $action; ?>" <?php echo ($filters['action'] == $action) ? 'selected' : ''; ?>><?php echo $action; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Module'); ?></label>
        <select name="module_filter" class="form-select form-select-sm">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach ($modules as $module): ?>
            <option value="<?php echo $module; ?>" <?php echo ($filters['module'] == $module) ? 'selected' : ''; ?>><?php echo $module; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Username'); ?></label>
        <input type="text" name="username" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['username'] ?? ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Date From'); ?></label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $filters['date_from'] ?? ''; ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Date To'); ?></label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $filters['date_to'] ?? ''; ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary btn-sm me-1"><i class="fas fa-search"></i></button>
        <a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'index']); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <?php echo __('Showing %1% of %2% records', ['%1%' => count($logs), '%2%' => number_format($total)]); ?>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Date/Time'); ?></th>
          <th><?php echo __('User'); ?></th>
          <th><?php echo __('Action'); ?></th>
          <th><?php echo __('Module'); ?></th>
          <th><?php echo __('Entity'); ?></th>
          <th><?php echo __('IP Address'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td><small><?php echo $log->created_at; ?></small></td>
          <td><?php echo htmlspecialchars($log->username ?? 'anonymous'); ?></td>
          <td><span class="badge bg-<?php echo $log->action == 'delete' ? 'danger' : ($log->action == 'create' ? 'success' : 'primary'); ?>"><?php echo $log->action; ?></span></td>
          <td><small><?php echo $log->module; ?></small></td>
          <td><small><?php echo htmlspecialchars($log->entity_title ?? $log->entity_type); ?></small></td>
          <td><small class="text-muted"><?php echo $log->ip_address; ?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="card-footer">
    <nav>
      <ul class="pagination pagination-sm mb-0 justify-content-center">
        <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>
<?php end_slot() ?>
