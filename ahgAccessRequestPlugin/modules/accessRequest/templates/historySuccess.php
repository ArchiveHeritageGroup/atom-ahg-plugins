<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Access Request History'); ?></h1>
    <span class="small"><?php echo __('Full audit trail of all access request actions'); ?></span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<!-- Stats -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h2 class="text-primary mb-0"><?php echo number_format($stats['total_requests']); ?></h2>
        <small class="text-muted"><?php echo __('Total Requests'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h2 class="text-warning mb-0"><?php echo number_format($stats['pending']); ?></h2>
        <small class="text-muted"><?php echo __('Pending'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h2 class="text-success mb-0"><?php echo number_format($stats['approved']); ?></h2>
        <small class="text-muted"><?php echo __('Approved'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h2 class="text-danger mb-0"><?php echo number_format($stats['denied']); ?></h2>
        <small class="text-muted"><?php echo __('Denied'); ?></small>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'accessRequest', 'action' => 'history']); ?>" class="row g-2">
      <div class="col-md-4">
        <label class="form-label small mb-1"><?php echo __('Filter by request status'); ?></label>
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All statuses'); ?></option>
          <?php foreach (['pending', 'approved', 'denied', 'cancelled', 'expired'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-1"><?php echo __('Filter by action'); ?></label>
        <select name="action_filter" class="form-select form-select-sm">
          <option value=""><?php echo __('All actions'); ?></option>
          <?php foreach (['created', 'approved', 'denied', 'cancelled', 'expired', 'reviewed'] as $a): ?>
            <option value="<?php echo $a; ?>" <?php echo $actionFilter === $a ? 'selected' : ''; ?>><?php echo ucfirst($a); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary me-2"><i class="fas fa-filter me-1"></i><?php echo __('Apply'); ?></button>
        <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'history']); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Reset'); ?></a>
      </div>
    </form>
  </div>
</div>

<!-- Log table -->
<div class="card">
  <div class="card-header bg-primary text-white">
    <i class="fas fa-list me-2"></i><?php echo __('Audit Log'); ?>
    <span class="badge bg-light text-dark ms-2"><?php echo number_format($total); ?> <?php echo __('entries'); ?></span>
  </div>
  <?php if (empty($logs)): ?>
    <div class="card-body text-center text-muted py-5">
      <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
      <?php echo __('No audit log entries found.'); ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('When'); ?></th>
            <th><?php echo __('Action'); ?></th>
            <th><?php echo __('Request #'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Actor'); ?></th>
            <th><?php echo __('Details'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td class="text-nowrap"><small><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)); ?></small></td>
              <td>
                <?php
                  $actionColors = ['created' => 'info', 'approved' => 'success', 'denied' => 'danger', 'cancelled' => 'secondary', 'expired' => 'warning'];
                  $color = $actionColors[$log->action] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($log->action); ?></span>
              </td>
              <td>
                <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'view', 'id' => $log->request_id]); ?>">#<?php echo $log->request_id; ?></a>
              </td>
              <td><?php echo $log->request_status ? '<span class="badge bg-' . ($log->request_status === 'approved' ? 'success' : ($log->request_status === 'denied' ? 'danger' : 'warning')) . '">' . ucfirst($log->request_status) . '</span>' : '—'; ?></td>
              <td><small><?php echo esc_specialchars($log->actor_username ?: '—'); ?></small></td>
              <td><small class="text-muted"><?php echo esc_specialchars($log->details ?: ''); ?></small></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="card-footer">
        <nav>
          <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php $qs = ($statusFilter ? "&status={$statusFilter}" : '') . ($actionFilter ? "&action_filter={$actionFilter}" : ''); ?>
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1 . $qs; ?>">&laquo;</a></li>
            <?php endif; ?>
            <li class="page-item active"><span class="page-link"><?php echo __('Page %1% of %2%', ['%1%' => $page, '%2%' => $totalPages]); ?></span></li>
            <?php if ($page < $totalPages): ?>
              <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1 . $qs; ?>">&raquo;</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php end_slot(); ?>
