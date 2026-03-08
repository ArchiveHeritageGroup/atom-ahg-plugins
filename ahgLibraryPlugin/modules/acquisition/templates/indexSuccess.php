<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Acquisitions — Purchase Orders'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($notice)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $notice; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<!-- Budget summary cards -->
<?php $rawBudgets = $sf_data->getRaw('budgets'); ?>
<?php if (!empty($rawBudgets)): ?>
  <div class="row mb-4">
    <?php
      $totalAllocated = 0;
      $totalSpent = 0;
      $totalAvailable = 0;
      foreach ($rawBudgets as $b) {
          $totalAllocated += (float) $b->allocated_amount;
          $totalSpent += (float) $b->spent_amount;
          $totalAvailable += (float) ($b->available_amount ?? ($b->allocated_amount - $b->spent_amount - ($b->encumbered_amount ?? 0)));
      }
    ?>
    <div class="col-md-4">
      <div class="card text-center border-primary">
        <div class="card-body py-2">
          <div class="fs-4 fw-bold text-primary"><?php echo number_format($totalAllocated, 2); ?></div>
          <small class="text-muted"><?php echo __('Total allocated (%1%)', ['%1%' => date('Y')]); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center border-warning">
        <div class="card-body py-2">
          <div class="fs-4 fw-bold text-warning"><?php echo number_format($totalSpent, 2); ?></div>
          <small class="text-muted"><?php echo __('Total spent'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center border-success">
        <div class="card-body py-2">
          <div class="fs-4 fw-bold text-success"><?php echo number_format($totalAvailable, 2); ?></div>
          <small class="text-muted"><?php echo __('Total available'); ?></small>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Action bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'orderEdit']); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('New Order'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'budgets']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-wallet me-1"></i><?php echo __('Budgets'); ?>
    </a>
  </div>
  <span class="text-muted"><?php echo __('%1% order(s) found', ['%1%' => (int) $total]); ?></span>
</div>

<!-- Search/filter bar -->
<div class="card shadow-sm mb-4">
  <div class="card-body py-2">
    <form method="get" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'index']); ?>">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label for="acq_q" class="form-label mb-0"><?php echo __('Search'); ?></label>
          <input type="text" class="form-control form-control-sm" id="acq_q" name="q"
                 placeholder="<?php echo __('Order number or vendor...'); ?>"
                 value="<?php echo esc_entities($q ?? ''); ?>">
        </div>
        <div class="col-md-3">
          <label for="acq_status" class="form-label mb-0"><?php echo __('Status'); ?></label>
          <select class="form-select form-select-sm" id="acq_status" name="order_status">
            <option value=""><?php echo __('All statuses'); ?></option>
            <?php foreach (['pending', 'partial', 'received', 'cancelled'] as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo ($orderStatus ?? '') === $s ? 'selected' : ''; ?>>
                <?php echo esc_entities(ucfirst($s)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="acq_type" class="form-label mb-0"><?php echo __('Type'); ?></label>
          <select class="form-select form-select-sm" id="acq_type" name="order_type">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach (['purchase', 'standing', 'gift', 'exchange', 'approval'] as $t): ?>
              <option value="<?php echo $t; ?>" <?php echo ($sf_data->getRaw('orderType') ?? '') === $t ? 'selected' : ''; ?>>
                <?php echo esc_entities(ucfirst($t)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-outline-primary btn-sm w-100">
            <i class="fas fa-search me-1"></i><?php echo __('Filter'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Orders table -->
<div class="card shadow-sm">
  <div class="card-body p-0">
    <?php $rawResults = $sf_data->getRaw('results'); ?>
    <?php if (empty($rawResults)): ?>
      <div class="p-3 text-muted">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No orders found.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Order #'); ?></th>
              <th><?php echo __('Vendor'); ?></th>
              <th><?php echo __('Date'); ?></th>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th class="text-end"><?php echo __('Total'); ?></th>
              <th class="text-center"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawResults as $order): ?>
              <?php
                $statusBadge = 'bg-secondary';
                switch ($order->order_status ?? '') {
                    case 'pending': $statusBadge = 'bg-warning text-dark'; break;
                    case 'partial': $statusBadge = 'bg-info text-dark'; break;
                    case 'received': $statusBadge = 'bg-success'; break;
                    case 'cancelled': $statusBadge = 'bg-danger'; break;
                }
                $typeBadge = 'bg-primary';
                switch ($order->order_type ?? '') {
                    case 'standing': $typeBadge = 'bg-info text-dark'; break;
                    case 'gift': $typeBadge = 'bg-success'; break;
                    case 'exchange': $typeBadge = 'bg-warning text-dark'; break;
                    case 'approval': $typeBadge = 'bg-secondary'; break;
                }
              ?>
              <tr>
                <td>
                  <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'order', 'order_id' => $order->id]); ?>">
                    <code><?php echo esc_entities($order->order_number ?? '-'); ?></code>
                  </a>
                </td>
                <td><?php echo esc_entities($order->vendor_name ?? '-'); ?></td>
                <td><?php echo esc_entities($order->order_date ?? '-'); ?></td>
                <td><span class="badge <?php echo $typeBadge; ?>"><?php echo esc_entities(ucfirst($order->order_type ?? '-')); ?></span></td>
                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo esc_entities(ucfirst($order->order_status ?? '-')); ?></span></td>
                <td class="text-end"><?php echo esc_entities($order->currency ?? 'USD'); ?> <?php echo number_format((float) ($order->total_amount ?? 0), 2); ?></td>
                <td class="text-center">
                  <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'order', 'order_id' => $order->id]); ?>"
                     class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
                    <i class="fas fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
  <nav class="mt-3" aria-label="<?php echo __('Orders pagination'); ?>">
    <ul class="pagination justify-content-center mb-0">
      <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index', 'q' => $q, 'order_status' => $orderStatus, 'page' => $page - 1]); ?>">
          <?php echo __('Previous'); ?>
        </a>
      </li>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?php echo $i === (int) $page ? 'active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index', 'q' => $q, 'order_status' => $orderStatus, 'page' => $i]); ?>">
            <?php echo $i; ?>
          </a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index', 'q' => $q, 'order_status' => $orderStatus, 'page' => $page + 1]); ?>">
          <?php echo __('Next'); ?>
        </a>
      </li>
    </ul>
  </nav>
<?php endif; ?>
