<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-history text-primary me-2"></i><?php echo __('Audit Trail'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row mb-4">
  <div class="col-md-2">
    <div class="card text-center bg-light">
      <div class="card-body py-2">
        <h4 class="mb-0 text-primary"><?php echo number_format($stats['total']); ?></h4>
        <small class="text-muted">Total</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-light">
      <div class="card-body py-2">
        <h4 class="mb-0 text-info"><?php echo number_format($stats['today']); ?></h4>
        <small class="text-muted">Today</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-light">
      <div class="card-body py-2">
        <h4 class="mb-0 text-success"><?php echo number_format($stats['creates']); ?></h4>
        <small class="text-muted">Creates</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-light">
      <div class="card-body py-2">
        <h4 class="mb-0 text-warning"><?php echo number_format($stats['updates']); ?></h4>
        <small class="text-muted">Updates</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-light">
      <div class="card-body py-2">
        <h4 class="mb-0 text-danger"><?php echo number_format($stats['deletes']); ?></h4>
        <small class="text-muted">Deletes</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <a href="<?php echo url_for(['module' => 'audit', 'action' => 'export', 'table' => $sf_request->getParameter('table'), 'from_date' => $sf_request->getParameter('from_date'), 'to_date' => $sf_request->getParameter('to_date')]); ?>" class="btn btn-outline-secondary w-100 h-100 d-flex align-items-center justify-content-center">
      <i class="fas fa-download me-1"></i>Export CSV
    </a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><i class="fas fa-filter me-2"></i>Filters</div>
  <div class="card-body">
    <form method="get" class="row g-3">
      <div class="col-md-2">
        <label class="form-label">Table</label>
        <select name="table" class="form-select form-select-sm">
          <option value="">All Tables</option>
          <?php foreach ($tables as $table): ?>
            <option value="<?php echo $table; ?>" <?php echo $sf_request->getParameter('table') === $table ? 'selected' : ''; ?>><?php echo $table; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Action</label>
        <select name="form_action" class="form-select form-select-sm">
          <option value="">All Actions</option>
          <option value="create" <?php echo $sf_request->getParameter('form_action') === 'create' ? 'selected' : ''; ?>>Create</option>
          <option value="update" <?php echo $sf_request->getParameter('form_action') === 'update' ? 'selected' : ''; ?>>Update</option>
          <option value="delete" <?php echo $sf_request->getParameter('form_action') === 'delete' ? 'selected' : ''; ?>>Delete</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">From Date</label>
        <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo $sf_request->getParameter('from_date'); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">To Date</label>
        <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo $sf_request->getParameter('to_date'); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control form-control-sm" value="<?php echo $sf_request->getParameter('q'); ?>" placeholder="Search...">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fas fa-search"></i> Filter</button>
        <a href="<?php echo url_for(['module' => 'audit', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-list me-2"></i>Audit Log</span>
    <span class="badge bg-secondary"><?php echo number_format($totalCount); ?> entries</span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($logs)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fas fa-inbox fa-3x mb-3"></i>
        <p>No audit entries found</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date/Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Table</th>
              <th>Record</th>
              <th>Changes</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td class="small"><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)); ?></td>
                <td><?php echo htmlspecialchars($log->user_name ?? 'System'); ?></td>
                <td>
                  <?php
                  $class = 'secondary';
                  if ($log->action === 'create') $class = 'success';
                  elseif ($log->action === 'update') $class = 'warning';
                  elseif ($log->action === 'delete') $class = 'danger';
                  ?>
                  <span class="badge bg-<?php echo $class; ?>"><?php echo ucfirst($log->action); ?></span>
                </td>
                <td><code><?php echo $log->table_name; ?></code></td>
                <td><code>#<?php echo $log->record_id; ?></code></td>
                <td class="small">
                  <?php if ($log->field_name): ?>
                    <strong><?php echo $log->field_name; ?>:</strong>
                    <span class="text-danger"><?php echo htmlspecialchars(substr($log->old_value ?? '', 0, 20)); ?></span>
                    <i class="fas fa-arrow-right mx-1 text-muted"></i>
                    <span class="text-success"><?php echo htmlspecialchars(substr($log->new_value ?? '', 0, 20)); ?></span>
                  <?php else: ?>
                    <?php echo htmlspecialchars($log->action_description ?? ''); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo url_for(['module' => 'audit', 'action' => 'view', 'id' => $log->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <?php if ($totalPages > 1): ?>
    <div class="card-footer">
      <nav>
        <ul class="pagination pagination-sm justify-content-center mb-0">
          <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'audit', 'action' => 'index', 'page' => $i, 'table' => $sf_request->getParameter('table'), 'form_action' => $sf_request->getParameter('form_action'), 'from_date' => $sf_request->getParameter('from_date'), 'to_date' => $sf_request->getParameter('to_date')]); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<div class="mt-3">
  <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings']); ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i>Back to Settings
  </a>
</div>
<?php end_slot() ?>
